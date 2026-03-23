<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 注册核心内容类型：活动
 */
function tharchive_register_post_types() {
	$labels = array(
		'name'                  => '活动',
		'singular_name'         => '活动',
		'menu_name'             => '活动归档',
		'name_admin_bar'        => '活动',
		'add_new'               => '新建活动',
		'add_new_item'          => '新建活动',
		'new_item'              => '新活动',
		'edit_item'             => '编辑活动',
		'view_item'             => '查看活动',
		'all_items'             => '全部活动',
		'search_items'          => '搜索活动',
		'not_found'             => '未找到活动',
		'not_found_in_trash'    => '回收站中没有活动',
		'featured_image'        => '活动封面图',
		'set_featured_image'    => '设置封面图',
		'remove_featured_image' => '移除封面图',
		'use_featured_image'    => '使用此图作为封面图',
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'show_in_rest'       => true,
		'has_archive'        => true,
		'rewrite'            => array(
			'slug'       => 'events',
			'with_front' => false,
		),
		'menu_position'      => 5,
		'menu_icon'          => 'dashicons-calendar-alt',
		'supports'           => array(
			'title',         // 活动标题
			'editor',        // 活动正文
			'excerpt',       // 活动简介
			'thumbnail',     // 封面图
			'custom-fields', // 为 REST 暴露 meta 做准备
		),
		'taxonomies'         => array(
			'event_type',
			'event_status',
			'organizer',
			'touhou_topic',
			'touhou_character',
		),
		'show_in_menu'       => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_nav_menus'  => true,
		'query_var'          => true,
	);

	register_post_type( 'relay_event', $args );
}

/**
 * relay_event 使用经典编辑器，不使用区块编辑器。
 *
 * @param bool   $use_block_editor 是否启用区块编辑器。
 * @param string $post_type        文章类型。
 * @return bool
 */
function tharchive_disable_block_editor_for_relay_event( $use_block_editor, $post_type ) {
	if ( 'relay_event' === $post_type ) {
		return false;
	}

	return $use_block_editor;
}
add_filter( 'use_block_editor_for_post_type', 'tharchive_disable_block_editor_for_relay_event', 10, 2 );

/**
 * 移除 relay_event 的 Elementor 支持，避免进入页面构建器式编辑。
 *
 * @return void
 */
function tharchive_disable_elementor_for_relay_event() {
	if ( post_type_supports( 'relay_event', 'elementor' ) ) {
		remove_post_type_support( 'relay_event', 'elementor' );
	}
}
add_action( 'init', 'tharchive_disable_elementor_for_relay_event', 30 );

/**
 * 调整 relay_event 编辑页的标题占位提示。
 *
 * @param string  $placeholder 原占位文案。
 * @param WP_Post $post        当前文章。
 * @return string
 */
function tharchive_relay_event_title_placeholder( $placeholder, $post ) {
	if ( $post instanceof WP_Post && 'relay_event' === $post->post_type ) {
		return '填写活动标题，例如：秘封组角色日接力 2026';
	}

	return $placeholder;
}
add_filter( 'enter_title_here', 'tharchive_relay_event_title_placeholder', 10, 2 );

/**
 * 在 relay_event 编辑页显示录入说明，弱化“文章”编辑感。
 *
 * @return void
 */
function tharchive_render_relay_event_editor_notice() {
	$screen = get_current_screen();

	if ( ! $screen || 'relay_event' !== $screen->post_type || 'post' !== $screen->base ) {
		return;
	}

	echo '<div class="notice notice-info"><p><strong>这里录入的是活动条目，不是普通文章。</strong> 请把标题、一句话简介、活动说明、封面图和下方结构化字段补齐即可，不需要使用页面构建器式编辑。</p></div>';
}
add_action( 'admin_notices', 'tharchive_render_relay_event_editor_notice' );

/**
 * 调整 relay_event 侧边栏 Meta Box 顺序，优先突出封面图。
 *
 * @return void
 */
function tharchive_adjust_relay_event_side_meta_boxes() {
	global $wp_meta_boxes;

	if ( empty( $wp_meta_boxes['relay_event']['side'] ) ) {
		return;
	}

	foreach ( array( 'core', 'high', 'default', 'low' ) as $priority ) {
		if ( empty( $wp_meta_boxes['relay_event']['side'][ $priority ]['postimagediv'] ) ) {
			continue;
		}

		$post_image_box = $wp_meta_boxes['relay_event']['side'][ $priority ]['postimagediv'];
		unset( $wp_meta_boxes['relay_event']['side'][ $priority ]['postimagediv'] );
		$post_image_box['title'] = '活动封面图';

		if ( empty( $wp_meta_boxes['relay_event']['side']['high'] ) || ! is_array( $wp_meta_boxes['relay_event']['side']['high'] ) ) {
			$wp_meta_boxes['relay_event']['side']['high'] = array();
		}

		$wp_meta_boxes['relay_event']['side']['high'] = array_merge(
			array( 'postimagediv' => $post_image_box ),
			$wp_meta_boxes['relay_event']['side']['high']
		);

		break;
	}
}
add_action( 'add_meta_boxes_relay_event', 'tharchive_adjust_relay_event_side_meta_boxes', 20 );
