<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 注册短文本 meta
 */
function tharchive_register_string_meta( $key, $long_text = false ) {
	register_post_meta(
		'relay_event',
		$key,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'default'           => '',
			'sanitize_callback' => $long_text ? 'sanitize_textarea_field' : 'sanitize_text_field',
		)
	);
}

/**
 * 注册 URL meta
 */
function tharchive_register_url_meta( $key ) {
	register_post_meta(
		'relay_event',
		$key,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
}

/**
 * 注册整数 meta
 */
function tharchive_register_integer_meta( $key ) {
	register_post_meta(
		'relay_event',
		$key,
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'default'           => 0,
			'sanitize_callback' => 'absint',
		)
	);
}

/**
 * 注册活动相关 meta
 */
function tharchive_register_meta_fields() {

	/**
	 * 基础信息
	 * - excerpt: 活动简介（用 WP 自带摘要）
	 * - thumbnail: 封面图（用 WP 自带特色图片）
	 */
	tharchive_register_integer_meta( 'event_year' );
	tharchive_register_string_meta( 'event_date' );      // 建议格式：YYYY-MM-DD
	tharchive_register_string_meta( 'event_date_end' );  // 可空

	/**
	 * 主办与参与说明
	 */
	tharchive_register_string_meta( 'organizer_contact', true );
	tharchive_register_string_meta( 'registration_info', true );
	tharchive_register_string_meta( 'deadline_info', true );
	tharchive_register_string_meta( 'publish_platform_info', true );
	tharchive_register_string_meta( 'rules_markdown', true );

	/**
	 * 归档信息
	 */
	tharchive_register_url_meta( 'bilibili_summary_url' );
	tharchive_register_url_meta( 'archive_site_url' );
	tharchive_register_string_meta( 'extra_archive_links', true );
	tharchive_register_string_meta( 'archive_summary_markdown', true );
	tharchive_register_string_meta( 'participant_count', true );
	tharchive_register_url_meta( 'source_summary_url' );
	tharchive_register_string_meta( 'source_raw_text', true );
	tharchive_register_string_meta( 'other_notes', true );

	/**
	 * 活动图集 / 海报 / 宣传图
	 * 存 attachment ID 数组
	 */
	register_post_meta(
		'relay_event',
		'gallery_images',
		array(
			'type'              => 'array',
			'single'            => true,
			'default'           => array(),
			'sanitize_callback' => 'tharchive_sanitize_integer_array',
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'integer',
					),
				),
			),
		)
	);
}