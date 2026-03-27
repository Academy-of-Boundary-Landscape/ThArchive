=== Bilibili HTML Cleaner ===
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress admin tool for cleaning Bilibili article / opus HTML into Markdown and a reusable LLM prompt.

== Description ==

Bilibili HTML Cleaner is a small WordPress admin tool plugin.

It is designed for editors who copy raw HTML from Bilibili article or opus pages and want to:

- extract the main content area
- remove obvious page noise
- convert cleaned HTML into Markdown
- generate a prompt for manual follow-up in ChatGPT, DeepSeek, or similar tools

This plugin does not call any AI API directly.

== Features ==

- Admin tool page under `Tools`
- Bilibili-specific content extraction
- HTML to Markdown conversion
- Optional image retention
- Prompt generation for creative relay activity pages

== Installation ==

1. Upload the `bili-html-cleaner` folder to `/wp-content/plugins/`, or upload the plugin zip in WordPress admin.
2. Activate the plugin through the `Plugins` screen.
3. Go to `Tools -> Bilibili HTML Cleaner`.

== Frequently Asked Questions ==

= Does it require Composer on the server? =

No. The packaged plugin includes `vendor/`.

= Does it publish content automatically? =

No. It only cleans HTML, converts Markdown, and generates a prompt.

= Does it keep images? =

Images are optional and disabled by default.
