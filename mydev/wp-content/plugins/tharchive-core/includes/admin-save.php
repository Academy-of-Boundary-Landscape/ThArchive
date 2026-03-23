<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 保存文本类 meta
 */
function tharchive_save_text_meta( $post_id, $key, $long_text = false ) {
	if ( ! isset( $_POST[ $key ] ) ) {
		return;
	}

	$value = wp_unslash( $_POST[ $key ] );
	$value = $long_text ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );

	update_post_meta( $post_id, $key, $value );
}

/**
 * 保存 URL 类 meta
 */
function tharchive_save_url_meta( $post_id, $key ) {
	if ( ! isset( $_POST[ $key ] ) ) {
		return;
	}

	$value = wp_unslash( $_POST[ $key ] );
	$value = esc_url_raw( $value );

	update_post_meta( $post_id, $key, $value );
}

/**
 * 保存整数 meta
 */
function tharchive_save_integer_meta( $post_id, $key ) {
	if ( ! isset( $_POST[ $key ] ) ) {
		return;
	}

	$value = absint( wp_unslash( $_POST[ $key ] ) );
	update_post_meta( $post_id, $key, $value );
}

/**
 * 保存活动图集 ID 列表
 */
function tharchive_save_gallery_images_meta( $post_id ) {
	if ( ! isset( $_POST['gallery_images'] ) ) {
		return;
	}

	$raw = wp_unslash( $_POST['gallery_images'] );

	// 按逗号、空格、换行分割
	$parts = preg_split( '/[\s,]+/', $raw );
	$parts = is_array( $parts ) ? $parts : array();

	$ids = array_map( 'absint', $parts );
	$ids = array_filter(
		$ids,
		function( $item ) {
			return $item > 0;
		}
	);

	$ids = array_values( array_unique( $ids ) );

	update_post_meta( $post_id, 'gallery_images', $ids );
}

/**
 * 保存 relay_event 的附加信息
 *
 * @param int     $post_id 文章 ID
 * @param WP_Post $post    文章对象
 */
function tharchive_save_event_meta( $post_id, $post ) {

	// 只处理 relay_event
	if ( ! isset( $post->post_type ) || 'relay_event' !== $post->post_type ) {
		return;
	}

	// nonce 检查
	if ( ! isset( $_POST['tharchive_event_meta_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tharchive_event_meta_nonce'] ) ), 'tharchive_save_event_meta' ) ) {
		return;
	}

	// 自动保存 / 修订版本时不处理
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	// 权限检查
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	/**
	 * 基础时间信息
	 */
	tharchive_save_integer_meta( $post_id, 'event_year' );
	tharchive_save_text_meta( $post_id, 'event_date' );
	tharchive_save_text_meta( $post_id, 'event_date_end' );

	/**
	 * 主办与参与说明
	 */
	tharchive_save_text_meta( $post_id, 'organizer_contact', true );
	tharchive_save_text_meta( $post_id, 'registration_info', true );
	tharchive_save_text_meta( $post_id, 'deadline_info', true );
	tharchive_save_text_meta( $post_id, 'publish_platform_info', true );
	tharchive_save_text_meta( $post_id, 'rules_markdown', true );

	/**
	 * 归档信息
	 */
	tharchive_save_url_meta( $post_id, 'bilibili_summary_url' );
	tharchive_save_url_meta( $post_id, 'archive_site_url' );
	tharchive_save_text_meta( $post_id, 'extra_archive_links', true );
	tharchive_save_text_meta( $post_id, 'archive_summary_markdown', true );
	tharchive_save_text_meta( $post_id, 'participant_count', true );

	/**
	 * 原始来源
	 */
	tharchive_save_url_meta( $post_id, 'source_summary_url' );
	tharchive_save_text_meta( $post_id, 'source_raw_text', true );
	tharchive_save_text_meta( $post_id, 'other_notes', true );

	/**
	 * 图集
	 */
	tharchive_save_gallery_images_meta( $post_id );
}
add_action( 'save_post_relay_event', 'tharchive_save_event_meta', 10, 2 );