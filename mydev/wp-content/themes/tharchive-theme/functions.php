<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_template_directory() . '/inc/tharchive-markdown.php';
require_once get_template_directory() . '/inc/tharchive-template-tags.php';

/**
 * 主题初始化。
 */
function tharchive_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);
}
add_action( 'after_setup_theme', 'tharchive_theme_setup' );

/**
 * 复选框设置清洗。
 *
 * @param mixed $value 输入值。
 * @return bool
 */
function tharchive_theme_sanitize_checkbox( $value ) {
	return (bool) $value;
}

/**
 * 注册主题外观设置。
 *
 * @param WP_Customize_Manager $wp_customize 自定义器实例。
 * @return void
 */
function tharchive_theme_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'tharchive_visual_options',
		array(
			'title'       => __( 'THArchive 视觉选项', 'tharchive-theme' ),
			'priority'    => 35,
			'description' => __( '控制内容区面板底色与全局氛围遮罩。', 'tharchive-theme' ),
		)
	);

	$wp_customize->add_setting(
		'tharchive_enable_panel_tint',
		array(
			'default'           => false,
			'sanitize_callback' => 'tharchive_theme_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'tharchive_enable_panel_tint',
		array(
			'type'    => 'checkbox',
			'section' => 'tharchive_visual_options',
			'label'   => __( '启用内容区面板底色（半透明深色）', 'tharchive-theme' ),
		)
	);

	$wp_customize->add_setting(
		'tharchive_enable_global_overlay',
		array(
			'default'           => false,
			'sanitize_callback' => 'tharchive_theme_sanitize_checkbox',
		)
	);

	$wp_customize->add_control(
		'tharchive_enable_global_overlay',
		array(
			'type'    => 'checkbox',
			'section' => 'tharchive_visual_options',
			'label'   => __( '启用全局氛围遮罩（body::after）', 'tharchive-theme' ),
		)
	);
}
add_action( 'customize_register', 'tharchive_theme_customize_register' );

/**
 * 生成主题资源版本号。
 *
 * @param string $relative_path 相对主题目录路径。
 * @return string
 */
function tharchive_theme_asset_version( $relative_path ) {
	$absolute_path = get_theme_file_path( $relative_path );

	if ( file_exists( $absolute_path ) ) {
		return (string) filemtime( $absolute_path );
	}

	return wp_get_theme()->get( 'Version' );
}

/**
 * 获取“提交接力信息”页面地址。
 *
 * @return string
 */
function tharchive_theme_get_submit_url() {
	$candidates = array( 'submit' );

	foreach ( $candidates as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			return get_permalink( $page );
		}
	}

	return home_url( '/submit/' );
}

/**
 * 获取“接力活动列表”页面地址。
 *
 * @return string
 */
function tharchive_theme_get_relay_list_url() {
	$candidates = array( 'relay-list', 'relay_event', 'relay-events' );

	foreach ( $candidates as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			return get_permalink( $page );
		}
	}

	$archive_link = get_post_type_archive_link( 'relay_event' );
	if ( $archive_link ) {
		return $archive_link;
	}

	return home_url( '/relay-list/' );
}

/**
 * 获取“近期接力”页面地址（列表页日历视图）。
 *
 * @return string
 */
function tharchive_theme_get_recent_relay_url() {
	$relay_list_url = tharchive_theme_get_relay_list_url();

	return add_query_arg( 'view', 'calendar', $relay_list_url );
}

/**
 * 获取“关于”页面地址。
 *
 * @return string
 */
function tharchive_theme_get_about_url() {
	$candidates = array( 'about' );

	foreach ( $candidates as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			return get_permalink( $page );
		}
	}

	return home_url( '/about/' );
}

/**
 * 加载前台资源。
 */
function tharchive_enqueue_assets() {
	wp_enqueue_style(
		'tharchive-theme-style',
		get_stylesheet_uri(),
		array(),
		tharchive_theme_asset_version( 'style.css' )
	);

	if ( is_front_page() ) {
		wp_enqueue_style(
			'tharchive-front-page',
			get_theme_file_uri( 'assets/css/front-page.css' ),
			array( 'tharchive-theme-style' ),
			tharchive_theme_asset_version( 'assets/css/front-page.css' )
		);
	}

	if ( is_singular( 'relay_event' ) ) {
		wp_enqueue_style(
			'tharchive-single-relay-event',
			get_theme_file_uri( 'assets/css/single-relay-event.css' ),
			array( 'tharchive-theme-style' ),
			tharchive_theme_asset_version( 'assets/css/single-relay-event.css' )
		);
	}

	if ( is_home() || is_archive() || is_post_type_archive( 'relay_event' ) ) {
		wp_enqueue_style(
			'tharchive-archive',
			get_theme_file_uri( 'assets/css/archive.css' ),
			array( 'tharchive-theme-style' ),
			tharchive_theme_asset_version( 'assets/css/archive.css' )
		);
	}

	$enable_panel_tint      = (bool) get_theme_mod( 'tharchive_enable_panel_tint', false );
	$enable_global_overlay  = (bool) get_theme_mod( 'tharchive_enable_global_overlay', false );
	$panel_bg               = $enable_panel_tint ? 'rgba(10, 15, 28, 0.75)' : 'transparent';
	$panel_bg_strong        = $enable_panel_tint ? 'rgba(6, 9, 18, 0.85)' : 'transparent';
	$panel_blur             = $enable_panel_tint ? '12px' : '0px';

	$dynamic_css = ':root {'
		. '--bg-panel: ' . $panel_bg . ';'
		. '--bg-panel-strong: ' . $panel_bg_strong . ';'
		. '--panel-blur: ' . $panel_blur . ';'
		. '}';

	if ( ! $enable_global_overlay ) {
		$dynamic_css .= 'body::after { display: none !important; }';
	}

	wp_add_inline_style( 'tharchive-theme-style', $dynamic_css );
}
add_action( 'wp_enqueue_scripts', 'tharchive_enqueue_assets' );
