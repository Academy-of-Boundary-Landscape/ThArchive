<?php
/**
 * Plugin Name: THArchive Core
 * Description: 东方Project同人活动归档站核心
 * Version: 1.1.2
 * Author: renko_1055
 * Last Updated: 2026.4.10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'THARCHIVE_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'THARCHIVE_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'THARCHIVE_CORE_VERSION', '1.1.2' );

require_once THARCHIVE_CORE_PATH . 'includes/helpers.php';
require_once THARCHIVE_CORE_PATH . 'includes/post-types.php';
require_once THARCHIVE_CORE_PATH . 'includes/taxonomies.php';
require_once THARCHIVE_CORE_PATH . 'includes/meta-fields.php';
require_once THARCHIVE_CORE_PATH . 'includes/admin-meta-boxes.php';
require_once THARCHIVE_CORE_PATH . 'includes/admin-save.php';
require_once THARCHIVE_CORE_PATH . 'includes/admin-review.php';
require_once THARCHIVE_CORE_PATH . 'includes/admin-export.php';
require_once THARCHIVE_CORE_PATH . 'includes/admin-import.php';
require_once THARCHIVE_CORE_PATH . 'includes/submission-form.php';
require_once THARCHIVE_CORE_PATH . 'includes/submission-handler.php';
require_once THARCHIVE_CORE_PATH . 'includes/archive-app.php';
require_once THARCHIVE_CORE_PATH . 'includes/carousel-app.php';
/**
 * 初始化核心数据结构
 */
function tharchive_core_init() {
	tharchive_register_post_types();
	tharchive_register_taxonomies();
	tharchive_register_meta_fields();
}
add_action( 'init', 'tharchive_core_init' );

/**
 * 激活插件时刷新 rewrite
 */
function tharchive_core_activate() {
	tharchive_core_init();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'tharchive_core_activate' );

/**
 * 停用插件时刷新 rewrite
 */
function tharchive_core_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'tharchive_core_deactivate' );
