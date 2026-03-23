<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 生成 taxonomy labels
 */
function tharchive_build_taxonomy_labels( $name ) {
	return array(
		'name'              => $name,
		'singular_name'     => $name,
		'search_items'      => '搜索' . $name,
		'all_items'         => '全部' . $name,
		'edit_item'         => '编辑' . $name,
		'update_item'       => '更新' . $name,
		'add_new_item'      => '添加' . $name,
		'new_item_name'     => '新' . $name,
		'menu_name'         => $name,
	);
}

/**
 * 注册活动相关 taxonomy
 */
function tharchive_register_taxonomies() {

	// 活动类型：角色日接力 / 一般接力 / 征稿 / 线下活动 ...
	register_taxonomy(
		'event_type',
		array( 'relay_event' ),
		array(
			'labels'            => tharchive_build_taxonomy_labels( '活动类型' ),
			'public'            => true,
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'       => 'event-type',
				'with_front' => false,
			),
		)
	);

	// 活动状态：征集中 / 进行中 / 已结束 / 已归档 ...
	register_taxonomy(
		'event_status',
		array( 'relay_event' ),
		array(
			'labels'            => tharchive_build_taxonomy_labels( '活动状态' ),
			'public'            => true,
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'       => 'event-status',
				'with_front' => false,
			),
		)
	);

	// 主办方：某主催组 / 某社团 / 某个人
	register_taxonomy(
		'organizer',
		array( 'relay_event' ),
		array(
			'labels'            => tharchive_build_taxonomy_labels( '主办方' ),
			'public'            => true,
			'hierarchical'      => false,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'       => 'organizer',
				'with_front' => false,
			),
		)
	);

	// 东方主题标签：秘封组 / 红魔馆 / 地灵殿 / 旧作 / 某 CP ...
	register_taxonomy(
		'touhou_topic',
		array( 'relay_event' ),
		array(
			'labels'            => tharchive_build_taxonomy_labels( '东方主题标签' ),
			'public'            => true,
			'hierarchical'      => false,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'       => 'touhou-topic',
				'with_front' => false,
			),
		)
	);

	// 东方角色：灵梦 / 魔理沙 / 堇子 / 莲子 ...
	register_taxonomy(
		'touhou_character',
		array( 'relay_event' ),
		array(
			'labels'            => tharchive_build_taxonomy_labels( '东方角色' ),
			'public'            => true,
			'hierarchical'      => false,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'       => 'touhou-character',
				'with_front' => false,
			),
		)
	);
}