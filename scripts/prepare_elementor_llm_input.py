#!/usr/bin/env python3
"""Convert THArchive Elementor export JSON into LLM-friendly Markdown files.

Input:
- JSON exported by the THArchive Core admin export tool.

Output:
- one Markdown file per post
- a lightweight manifest JSON for later automation

The script keeps only the fields that matter most for the next stage:
- title
- main body markdown / body sections
- extracted button text + url
"""

from __future__ import annotations

import argparse
import json
import re
from pathlib import Path
from typing import Any


MULTI_NL_RE = re.compile(r"\n{3,}")
WHITESPACE_LINE_RE = re.compile(r"[ \t]+\n")


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Prepare THArchive Elementor export JSON into Markdown files."
    )
    parser.add_argument(
        "--input",
        default="scripts/output/tharchive-elementor-export-20260325-014410-0-29.json",
        help="Input JSON exported by the WordPress admin export tool.",
    )
    parser.add_argument(
        "--output-dir",
        default="scripts/output/elementor_markdown",
        help="Directory for generated Markdown files.",
    )
    return parser.parse_args()


def normalize_markdown(text: str) -> str:
    text = (text or "").replace("\r\n", "\n").replace("\r", "\n").strip()
    text = WHITESPACE_LINE_RE.sub("\n", text)
    text = MULTI_NL_RE.sub("\n\n", text)
    return text.strip()


def slugify_fragment(value: str) -> str:
    value = (value or "").strip().lower()
    value = re.sub(r"[^a-z0-9\u4e00-\u9fff]+", "-", value)
    value = value.strip("-")
    return value[:48] or "untitled"


def unique_sections(item: dict[str, Any]) -> list[str]:
    sections = []
    seen = set()

    main_body = normalize_markdown(str(item.get("main_body_markdown") or ""))
    if main_body:
        sections.append(main_body)
        seen.add(main_body)

    for section in item.get("elementor_sections") or []:
        if not isinstance(section, dict):
            continue
        body = normalize_markdown(str(section.get("body_markdown") or ""))
        if not body or body in seen:
            continue
        sections.append(body)
        seen.add(body)

    return sections


def unique_button_links(item: dict[str, Any]) -> list[dict[str, str]]:
    results: list[dict[str, str]] = []
    seen: set[tuple[str, str]] = set()

    for button in item.get("button_links") or []:
        if not isinstance(button, dict):
            continue
        text = str(button.get("text") or "").strip()
        url = str(button.get("url") or "").strip()
        widget_type = str(button.get("widget_type") or "").strip()
        if not url:
            continue
        key = (text, url)
        if key in seen:
            continue
        seen.add(key)
        results.append(
            {
                "text": text,
                "url": url,
                "widget_type": widget_type,
            }
        )

    return results


def build_markdown(item: dict[str, Any]) -> str:
    post_id = item.get("id")
    title = str(item.get("post_title") or "").strip() or f"未命名文章 {post_id}"
    permalink = str(item.get("permalink") or "").strip()
    post_type = str(item.get("post_type") or "").strip()
    post_status = str(item.get("post_status") or "").strip()
    post_date = str(item.get("post_date") or "").strip()

    sections = unique_sections(item)
    button_links = unique_button_links(item)

    lines: list[str] = [
        f"# {title}",
        "",
        "这是一份从 WordPress Elementor 旧文章导出的整理稿。",
        "当前只保留标题、正文主体和页面按钮信息，供后续大模型抽取结构化字段使用。",
        "",
        f"- 原始文章 ID：{post_id}",
    ]

    if post_type:
        lines.append(f"- 文章类型：{post_type}")
    if post_status:
        lines.append(f"- 文章状态：{post_status}")
    if post_date:
        lines.append(f"- 发布时间：{post_date}")
    if permalink:
        lines.append(f"- 原始链接：{permalink}")

    lines.extend(["", "## 正文内容", ""])

    if sections:
        for index, body in enumerate(sections, start=1):
            if index == 1:
                lines.append(body)
            else:
                lines.extend([f"### 追加正文片段 {index}", "", body])
            lines.append("")
    else:
        lines.extend(["（未抽取到正文内容）", ""])

    lines.extend(["## 页面按钮与链接", ""])

    if button_links:
        for button in button_links:
            text = button["text"] or "未命名按钮"
            url = button["url"]
            widget_type = button["widget_type"]
            suffix = f" [{widget_type}]" if widget_type else ""
            lines.append(f"- {text}{suffix}：{url}")
    else:
        lines.append("- （未抽取到按钮链接）")

    return normalize_markdown("\n".join(lines)) + "\n"


def build_manifest_item(item: dict[str, Any], markdown_name: str) -> dict[str, Any]:
    sections = unique_sections(item)
    button_links = unique_button_links(item)
    return {
        "id": item.get("id"),
        "post_title": item.get("post_title") or "",
        "post_type": item.get("post_type") or "",
        "post_status": item.get("post_status") or "",
        "permalink": item.get("permalink") or "",
        "markdown_file": markdown_name,
        "section_count": len(sections),
        "button_link_count": len(button_links),
    }


def main() -> int:
    args = parse_args()
    input_path = Path(args.input)
    output_dir = Path(args.output_dir)
    markdown_dir = output_dir / "records"
    markdown_dir.mkdir(parents=True, exist_ok=True)

    payload = json.loads(input_path.read_text(encoding="utf-8"))
    items = payload.get("items") or []
    if not isinstance(items, list):
        raise ValueError("Input JSON must contain an 'items' list.")

    manifest: list[dict[str, Any]] = []

    for item in items:
        if not isinstance(item, dict):
            continue

        post_id = item.get("id")
        title = str(item.get("post_title") or "")
        filename = f"{post_id}-{slugify_fragment(title)}.md"
        target = markdown_dir / filename
        target.write_text(build_markdown(item), encoding="utf-8")
        manifest.append(build_manifest_item(item, filename))

    summary = {
        "source_file": str(input_path),
        "record_count": len(manifest),
        "output_dir": str(output_dir),
    }

    (output_dir / "manifest.json").write_text(
        json.dumps(manifest, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )
    (output_dir / "summary.json").write_text(
        json.dumps(summary, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )

    print(
        f"Generated {len(manifest)} Markdown files in {markdown_dir} "
        f"from {input_path}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
