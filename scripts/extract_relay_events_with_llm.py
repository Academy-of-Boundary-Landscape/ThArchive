#!/usr/bin/env python3
"""Batch extract relay_event import JSON from Markdown via OpenAI-compatible API.

This script reads Markdown files produced by `prepare_elementor_llm_input.py`,
calls an OpenAI-compatible chat completion endpoint, and writes:

- relay_event_import.json: array of records directly importable by the WP tool
- relay_event_results.jsonl: one line per file with raw extraction result metadata
- relay_event_errors.jsonl: failures for retry/debug

Designed for OpenAI-compatible providers, defaulting to Alibaba DashScope:
- DASHSCOPE_API_KEY
- OPENAI_API_KEY
- OPENAI_BASE_URL=https://dashscope.aliyuncs.com/compatible-mode/v1
- OPENAI_MODEL=qwen3.5-plus
"""

from __future__ import annotations

import argparse
import json
import os
import re
import sys
import time
from urllib.parse import urlparse
from concurrent.futures import ThreadPoolExecutor, as_completed
from dataclasses import dataclass
from pathlib import Path
from typing import Any

from openai import OpenAI

try:
    from dotenv import load_dotenv
except ImportError:  # pragma: no cover - optional dependency
    load_dotenv = None


REPO_ROOT = Path(__file__).resolve().parent.parent

if load_dotenv is not None:
    load_dotenv(dotenv_path=REPO_ROOT / ".env")


SYSTEM_PROMPT = """你是一个将旧活动页面整理成 WordPress relay_event 导入数据的助手。

你必须输出一个 JSON 对象，并且必须包含以下 8 个字符串字段：
- title
- excerpt
- content
- character
- organizer
- event_date
- cover_image
- bilibili_summary_url

要求：
1. 只输出 JSON，不要输出额外说明。
2. 如果无法确定某个字段，就填空字符串。
3. 这是“旧页面转 relay_event”的结构化抽取，不要输出旧页面导航、页脚、站点说明、无关按钮文案。
4. excerpt 必须是一句简洁中文简介，尽量 20-60 字，概括“这是什么活动”。
5. title 和 content 可能已经由程序本地抽取；如果用户提示里明确说明会由程序填充正文，你可以把 content 留空字符串，不必重写整篇正文。
6. 如果确实需要生成 content，它应保留活动主体说明，使用 Markdown。优先保留活动介绍、规则、棒次安排、参与者表格、时间信息。
7. 与活动归档直接相关的补充链接信息也可以保留在 content 里，例如：B站总结专栏、贴吧总结帖、小红书帖子、外部归档页、活动专题页。可以把它们整理成正文末尾的“相关链接”小节。
8. 不要把站点导航、关于页入口、首页按钮、全站页脚链接、明显无关的站内跳转保留进 content。
9. character 可以根据标题或正文合理推断；如果不确定再留空。
10. organizer 只有在文本里明显出现主催、主办方、企划方时再填写；不要把参与者、作者名单、接力参与者当主办方。
11. event_date 必须是 YYYY-MM-DD；如果无法确认具体日期，留空。只有年份或只有“2.18”这类不完整日期时，只有在上下文能明确补全年份时才填写。
12. cover_image 暂时只接受字符串；如果 Markdown 中没有可靠封面图地址，就留空，不要编造。
13. bilibili_summary_url 只填写“B站总结专栏 / B站总结 / 总结专栏 / opus 专栏”这类明确指向 Bilibili 总结页面的链接。普通 B 站主页、作者主页、视频链接不要填。如果是其它平台的总结链接，不要填到 bilibili_summary_url，但可以保留在 content 的“相关链接”里。
14. 不要编造不存在的信息；不确定时宁可留空。
"""


JSON_REPAIR_SYSTEM_PROMPT = """你是一个 JSON 修复助手。

你的任务不是重新理解业务，而是把上一个模型返回的内容修复成一个严格合法的 JSON 对象。

要求：
1. 只输出 JSON，不要输出解释。
2. JSON 顶层必须是对象。
3. 必须包含以下 8 个字符串字段：
- title
- excerpt
- content
- character
- organizer
- event_date
- cover_image
- bilibili_summary_url
4. 如果某个字段缺失、类型不对或无法确定，填空字符串。
5. 不要新增 schema 之外的字段。
"""


USER_PROMPT_TEMPLATE = """请从下面这份 Markdown 中抽取 relay_event 导入字段。

注意：
1. title 和 content 会由程序优先使用本地抽取结果。
2. 为了避免输出过长，除非本地正文为空，否则 content 请返回空字符串。
3. title 如果“已提取标题”已经合理，你可以直接返回同样的标题；不确定时也可留空。

输出 JSON schema：
{{
  "title": "",
  "excerpt": "",
  "content": "",
  "character": "",
  "organizer": "",
  "event_date": "",
  "cover_image": "",
  "bilibili_summary_url": ""
}}

已提取标题：
{title}

已提取发布时间：
{publish_date}

正文摘要（截断后）：
```md
{body_excerpt}
```

相关链接摘要：
```md
{links_excerpt}
```
"""


JSON_REPAIR_USER_PROMPT_TEMPLATE = """请把下面这段内容修复成合法 JSON。

目标 JSON schema：
{{
  "title": "",
  "excerpt": "",
  "content": "",
  "character": "",
  "organizer": "",
  "event_date": "",
  "cover_image": "",
  "bilibili_summary_url": ""
}}

待修复内容：
```text
{bad_json}
```
"""


REQUIRED_KEYS = (
    "title",
    "excerpt",
    "content",
    "character",
    "organizer",
    "event_date",
    "cover_image",
    "bilibili_summary_url",
)


@dataclass
class ExtractionResult:
    source_file: str
    success: bool
    payload: dict[str, str] | None = None
    raw_response: str | None = None
    error: str | None = None
    duration_seconds: float = 0.0
    repaired_json: bool = False


def log(message: str) -> None:
    timestamp = time.strftime("%H:%M:%S")
    print(f"[{timestamp}] {message}", flush=True)


def extract_markdown_title(markdown: str) -> str:
    for line in markdown.splitlines():
        stripped = line.strip()
        if stripped.startswith("# "):
            return stripped[2:].strip()
    return ""


def extract_markdown_section(markdown: str, heading: str) -> str:
    lines = markdown.splitlines()
    capture = False
    captured: list[str] = []

    for line in lines:
        if line.startswith("## "):
            if line.strip() == heading:
                capture = True
                continue
            if capture:
                break

        if capture:
            captured.append(line)

    return "\n".join(captured).strip()


def extract_markdown_metadata_value(markdown: str, label: str) -> str:
    prefix = f"- {label}："
    for line in markdown.splitlines():
        if line.startswith(prefix):
            return line[len(prefix):].strip()
    return ""


def truncate_text(text: str, max_chars: int) -> str:
    text = text.strip()
    if max_chars <= 0 or len(text) <= max_chars:
        return text
    return text[:max_chars].rstrip() + "\n\n[内容已截断]"


def normalize_link_label(label: str) -> str:
    label = label.strip()
    label = re.sub(r"\s*\[button\]\s*$", "", label, flags=re.IGNORECASE)
    label = re.sub(r"\s+", " ", label)
    return label.strip("：: ").strip()


def normalize_links_section(links: str) -> str:
    lines = [line.rstrip() for line in links.splitlines()]
    formatted: list[str] = []
    seen: set[tuple[str, str]] = set()

    for line in lines:
        stripped = line.strip()
        if not stripped:
            continue

        match = re.match(r"^-\s*(.+?)\s*[：:]\s*(https?://\S+)\s*$", stripped)
        if match:
            label = normalize_link_label(match.group(1))
            url = match.group(2).strip()
            key = (label, url)
            if key in seen:
                continue
            seen.add(key)
            formatted.append(f"- [{label}]({url})")
            continue

        if stripped.startswith("- "):
            if stripped not in seen:
                seen.add((stripped, ""))
                formatted.append(stripped)
            continue

        formatted.append(stripped)

    return "\n".join(formatted).strip()


def build_local_payload(markdown: str) -> dict[str, str]:
    title = extract_markdown_title(markdown)
    body = extract_markdown_section(markdown, "## 正文内容")
    links = normalize_links_section(extract_markdown_section(markdown, "## 页面按钮与链接"))

    content_parts: list[str] = []
    if body:
        content_parts.append(body)
    if links:
        content_parts.append("## 相关链接\n\n" + links)

    return {
        "title": title,
        "publish_date": extract_markdown_metadata_value(markdown, "发布时间"),
        "content": "\n\n".join(part.strip() for part in content_parts if part.strip()).strip(),
        "body_excerpt": truncate_text(body, 6000),
        "links_excerpt": truncate_text(links, 2000),
    }


def compose_final_payload(local_payload: dict[str, str], model_payload: dict[str, str]) -> dict[str, str]:
    return {
        "title": local_payload.get("title", "") or model_payload.get("title", ""),
        "excerpt": model_payload.get("excerpt", ""),
        "content": local_payload.get("content", "") or model_payload.get("content", ""),
        "character": model_payload.get("character", ""),
        "organizer": model_payload.get("organizer", ""),
        "event_date": model_payload.get("event_date", ""),
        "cover_image": model_payload.get("cover_image", ""),
        "bilibili_summary_url": model_payload.get("bilibili_summary_url", ""),
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Batch extract relay_event import JSON with OpenAI-compatible API."
    )
    parser.add_argument(
        "--input-dir",
        default="scripts/output/elementor_markdown/records",
        help="Directory containing Markdown source files.",
    )
    parser.add_argument(
        "--output-dir",
        default="scripts/output/relay_event_extraction",
        help="Directory for extracted JSON outputs.",
    )
    parser.add_argument(
        "--workers",
        type=int,
        default=4,
        help="Thread worker count.",
    )
    parser.add_argument(
        "--model",
        default=(
            os.environ.get("DASHSCOPE_MODEL")
            or os.environ.get("OPENAI_MODEL")
            or "qwen3.5-plus"
        ),
        help="Model name. Default reads DASHSCOPE_MODEL / OPENAI_MODEL, fallback qwen3.5-plus.",
    )
    parser.add_argument(
        "--json-repair-model",
        default=(
            os.environ.get("DASHSCOPE_JSON_REPAIR_MODEL")
            or os.environ.get("JSON_REPAIR_MODEL")
            or os.environ.get("DASHSCOPE_MODEL")
            or os.environ.get("OPENAI_MODEL")
            or "qwen3.5-plus"
        ),
        help="Model used for JSON repair fallback. Defaults to the same DashScope model.",
    )
    parser.add_argument(
        "--base-url",
        default=(
            os.environ.get("DASHSCOPE_BASE_URL")
            or os.environ.get("OPENAI_BASE_URL")
            or "https://dashscope.aliyuncs.com/compatible-mode/v1"
        ),
        help="OpenAI-compatible base URL. Default reads DASHSCOPE_BASE_URL / OPENAI_BASE_URL, fallback DashScope compatible-mode URL.",
    )
    parser.add_argument(
        "--api-key",
        default=os.environ.get("DASHSCOPE_API_KEY") or os.environ.get("OPENAI_API_KEY", ""),
        help="API key. Default reads DASHSCOPE_API_KEY / OPENAI_API_KEY from .env or environment.",
    )
    parser.add_argument(
        "--temperature",
        type=float,
        default=0.2,
        help="Sampling temperature.",
    )
    parser.add_argument(
        "--limit",
        type=int,
        default=0,
        help="Optional limit for number of Markdown files.",
    )
    parser.add_argument(
        "--overwrite",
        action="store_true",
        help="Overwrite existing output files.",
    )
    parser.add_argument(
        "--request-timeout",
        type=float,
        default=120.0,
        help="Timeout in seconds for a single model request.",
    )
    return parser.parse_args()


def build_client(api_key: str, base_url: str, request_timeout: float) -> OpenAI:
    if not api_key:
        raise ValueError("Missing API key. Set DASHSCOPE_API_KEY / OPENAI_API_KEY in .env or pass --api-key.")
    return OpenAI(api_key=api_key, base_url=base_url, timeout=request_timeout, max_retries=1)


def request_json_object(
    client: OpenAI,
    model: str,
    temperature: float,
    system_prompt: str,
    user_prompt: str,
    max_tokens: int,
) -> str:
    completion = client.chat.completions.create(
        model=model,
        temperature=temperature,
        max_tokens=max_tokens,
        response_format={"type": "json_object"},
        messages=[
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": user_prompt},
        ],
    )

    return completion.choices[0].message.content or ""


def repair_json_payload(
    client: OpenAI,
    repair_model: str,
    bad_json: str,
) -> str:
    return request_json_object(
        client=client,
        model=repair_model,
        temperature=0,
        system_prompt=JSON_REPAIR_SYSTEM_PROMPT,
        user_prompt=JSON_REPAIR_USER_PROMPT_TEMPLATE.format(bad_json=bad_json),
        max_tokens=1200,
    )


def validate_payload(data: Any) -> dict[str, str]:
    if not isinstance(data, dict):
        raise ValueError("Model output is not a JSON object.")

    normalized: dict[str, str] = {}
    for key in REQUIRED_KEYS:
        value = data.get(key, "")
        normalized[key] = value.strip() if isinstance(value, str) else str(value or "").strip()

    event_date = normalized.get("event_date", "")
    if event_date and not re.match(r"^\d{4}-\d{2}-\d{2}$", event_date):
        normalized["event_date"] = ""

    bilibili_url = normalized.get("bilibili_summary_url", "")
    if bilibili_url:
        parsed = urlparse(bilibili_url)
        host = (parsed.netloc or "").lower()
        if not parsed.scheme or not host or "bilibili.com" not in host:
            normalized["bilibili_summary_url"] = ""

    return normalized


def extract_one(
    api_key: str,
    base_url: str,
    model: str,
    json_repair_model: str,
    temperature: float,
    request_timeout: float,
    markdown_path: Path,
) -> ExtractionResult:
    started_at = time.perf_counter()
    client = build_client(api_key, base_url, request_timeout)
    markdown = markdown_path.read_text(encoding="utf-8")
    local_payload = build_local_payload(markdown)

    content = request_json_object(
        client=client,
        model=model,
        temperature=temperature,
        system_prompt=SYSTEM_PROMPT,
        user_prompt=USER_PROMPT_TEMPLATE.format(
            title=local_payload.get("title", ""),
            publish_date=local_payload.get("publish_date", ""),
            body_excerpt=local_payload.get("body_excerpt", ""),
            links_excerpt=local_payload.get("links_excerpt", ""),
        ),
        max_tokens=1200,
    )
    repaired_json = False

    try:
        parsed = json.loads(content)
        model_payload = validate_payload(parsed)
    except Exception:
        log(f"[repair] {markdown_path.name} -> {json_repair_model}")
        repaired_content = repair_json_payload(
            client=client,
            repair_model=json_repair_model,
            bad_json=content,
        )
        parsed = json.loads(repaired_content)
        model_payload = validate_payload(parsed)
        content = repaired_content
        repaired_json = True

    payload = compose_final_payload(local_payload, model_payload)

    return ExtractionResult(
        source_file=markdown_path.name,
        success=True,
        payload=payload,
        raw_response=content,
        duration_seconds=time.perf_counter() - started_at,
        repaired_json=repaired_json,
    )


def write_jsonl(path: Path, rows: list[dict[str, Any]]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8") as handle:
        for row in rows:
            handle.write(json.dumps(row, ensure_ascii=False) + "\n")


def main() -> int:
    args = parse_args()
    input_dir = Path(args.input_dir)
    output_dir = Path(args.output_dir)

    if not input_dir.exists():
        raise FileNotFoundError(f"Input directory not found: {input_dir}")

    output_dir.mkdir(parents=True, exist_ok=True)

    import_json_path = output_dir / "relay_event_import.json"
    results_jsonl_path = output_dir / "relay_event_results.jsonl"
    errors_jsonl_path = output_dir / "relay_event_errors.jsonl"
    summary_path = output_dir / "summary.json"

    if not args.overwrite and import_json_path.exists():
        raise FileExistsError(
            f"Output already exists: {import_json_path}. Use --overwrite to replace."
        )

    markdown_files = sorted(input_dir.glob("*.md"))
    if args.limit > 0:
        markdown_files = markdown_files[: args.limit]

    total_files = len(markdown_files)
    if total_files == 0:
        raise FileNotFoundError(f"No Markdown files found in: {input_dir}")

    successful_payloads: list[dict[str, str]] = []
    successful_rows: list[dict[str, Any]] = []
    error_rows: list[dict[str, Any]] = []
    completed_count = 0

    log(
        "Start extraction: "
        f"files={total_files} workers={max(1, args.workers)} "
        f"model={args.model} repair_model={args.json_repair_model} "
        f"timeout={args.request_timeout}s base_url={args.base_url}"
    )

    def task(markdown_path: Path) -> ExtractionResult:
        log(f"[start] {markdown_path.name}")
        return extract_one(
            args.api_key,
            args.base_url,
            args.model,
            args.json_repair_model,
            args.temperature,
            args.request_timeout,
            markdown_path,
        )

    with ThreadPoolExecutor(max_workers=max(1, args.workers)) as executor:
        future_map = {
            executor.submit(task, markdown_path): markdown_path
            for markdown_path in markdown_files
        }

        for future in as_completed(future_map):
            markdown_path = future_map[future]
            try:
                result = future.result()
                successful_payloads.append(result.payload or {})
                successful_rows.append(
                    {
                        "source_file": result.source_file,
                        "payload": result.payload,
                        "raw_response": result.raw_response,
                        "duration_seconds": round(result.duration_seconds, 3),
                        "repaired_json": result.repaired_json,
                    }
                )
                completed_count += 1
                log(
                    f"[ok] {markdown_path.name} "
                    f"({completed_count}/{total_files}, {result.duration_seconds:.1f}s"
                    f"{', repaired' if result.repaired_json else ''})"
                )
            except Exception as exc:  # pragma: no cover - network/runtime path
                error_rows.append(
                    {
                        "source_file": markdown_path.name,
                        "error": str(exc),
                    }
                )
                completed_count += 1
                log(
                    f"[error] {markdown_path.name} "
                    f"({completed_count}/{total_files}): {exc}"
                )

    import_json_path.write_text(
        json.dumps(successful_payloads, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )
    write_jsonl(results_jsonl_path, successful_rows)
    write_jsonl(errors_jsonl_path, error_rows)

    summary = {
        "input_dir": str(input_dir),
        "output_dir": str(output_dir),
        "model": args.model,
        "json_repair_model": args.json_repair_model,
        "base_url": args.base_url,
        "requested_files": len(markdown_files),
        "success_count": len(successful_rows),
        "error_count": len(error_rows),
        "workers": args.workers,
        "repair_count": sum(1 for row in successful_rows if row.get("repaired_json")),
    }
    summary_path.write_text(
        json.dumps(summary, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )

    log(
        f"Done. success={len(successful_rows)} error={len(error_rows)} "
        f"output={output_dir}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
