# Bilibili HTML Cleaner

`Bilibili HTML Cleaner` 是一个 WordPress 后台工具插件，用于把 Bilibili 专栏 / Opus 页面 HTML 清洗为较干净的正文，再转换成 Markdown，并附带生成一段可复制到大模型网页中的 Prompt。

这个插件的定位是“手工辅助工具”，不直接调用任何模型 API，也不自动发文。

## 功能

- 在 WordPress 后台提供一个工具页
- 粘贴 Bilibili 页面 HTML 后，定向提取正文区域
- 清理评论区、侧边栏、版权区、按钮等常见噪音
- 将正文转换成 Markdown
- 生成适合继续交给大模型整理的 Prompt
- 图片保留为可选项，默认关闭

## 适用场景

- 东方 Project 创作接力活动页
- Bilibili 专栏活动说明页
- Opus / 动态汇总页
- 需要从原始网页 HTML 中快速抽出正文并继续人工整理的场景

## 安装

### 直接安装到 WordPress

1. 将整个 `bili-html-cleaner` 目录打包为 zip。
2. 进入 WordPress 后台。
3. 打开“插件 -> 安装插件 -> 上传插件”。
4. 上传 zip 并启用插件。
5. 启用后在“工具 -> Bilibili HTML Cleaner”中使用。

### 以源码方式放入插件目录

1. 将本目录放入 `wp-content/plugins/`。
2. 确保目录内包含 `vendor/`。
3. 在 WordPress 后台启用插件。

## 使用方法

1. 打开“工具 -> Bilibili HTML Cleaner”。
2. 粘贴完整的 Bilibili 页面 HTML。
3. 按需勾选“保留图片”。
4. 点击“清洗并转换”。
5. 复制生成的 Markdown 或 Prompt。

## 依赖

- PHP 8.1+
- WordPress 6.2+
- Composer 依赖已包含在 `vendor/` 中，最终用户不需要自行运行 `composer install`

## 开发说明

主要目录：

- `mydev/wp-content/plugins/bili-html-cleaner/bili-html-cleaner.php`：插件入口
- `mydev/wp-content/plugins/bili-html-cleaner/src/AdminPage.php`：后台工具页
- `mydev/wp-content/plugins/bili-html-cleaner/src/HtmlExtractor.php`：Bilibili 页面正文提取与 HTML 清洗
- `mydev/wp-content/plugins/bili-html-cleaner/src/MarkdownService.php`：HTML 转 Markdown
- `mydev/wp-content/plugins/bili-html-cleaner/src/PromptBuilder.php`：Prompt 拼接
- `mydev/wp-content/plugins/bili-html-cleaner/assets/`：后台样式与脚本

如需重新安装依赖，可在插件目录执行：

```bash
composer install --no-dev --classmap-authoritative
```

## 打包建议

发布给朋友或部署到其他站点时，建议 zip 中保留以下内容：

- 插件主文件
- `src/`
- `assets/`
- `vendor/`
- `composer.json`
- `composer.lock`
- `README.md`
- `readme.txt`
- `LICENSE`

## 当前边界

- 第一版主要针对 Bilibili 专栏 / Opus 结构做定向清洗
- 不保证适配所有 Bilibili 页面变体
- 不调用 ChatGPT / DeepSeek / OpenAI API
- 不自动发布文章到 WordPress

## License

GPL-2.0-or-later
