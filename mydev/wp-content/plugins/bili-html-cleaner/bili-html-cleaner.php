<?php
/**
 * Plugin Name: Bilibili HTML Cleaner
 * Description: 在 WordPress 后台清洗 Bilibili HTML 并转换为 Markdown。
 * Version: 0.1.0
 * Author: THArchive Project
 * Requires at least: 6.2
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bili-html-cleaner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BILI_HTML_CLEANER_VERSION', '0.1.0' );
define( 'BILI_HTML_CLEANER_PATH', plugin_dir_path( __FILE__ ) );
define( 'BILI_HTML_CLEANER_URL', plugin_dir_url( __FILE__ ) );

$autoload = BILI_HTML_CLEANER_PATH . 'vendor/autoload.php';

if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

require_once BILI_HTML_CLEANER_PATH . 'src/HtmlExtractor.php';
require_once BILI_HTML_CLEANER_PATH . 'src/MarkdownService.php';
require_once BILI_HTML_CLEANER_PATH . 'src/PromptBuilder.php';
require_once BILI_HTML_CLEANER_PATH . 'src/AdminPage.php';

add_action(
	'plugins_loaded',
	static function () {
		$page = new BiliHtmlCleaner\AdminPage(
			new BiliHtmlCleaner\HtmlExtractor(),
			new BiliHtmlCleaner\MarkdownService(),
			new BiliHtmlCleaner\PromptBuilder()
		);

		$page->register();
	}
);
