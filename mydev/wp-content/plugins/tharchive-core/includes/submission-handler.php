<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 获取并清洗返回地址
 *
 * @return string
 */
function tharchive_get_submitted_return_url() {
	$return_url = home_url( '/' );

	if ( isset( $_POST['_tharchive_return_url'] ) ) {
		$return_url = esc_url_raw( wp_unslash( $_POST['_tharchive_return_url'] ) );
	}

	if ( empty( $return_url ) ) {
		$return_url = home_url( '/' );
	}

	return $return_url;
}

/**
 * 跳转回投稿页
 *
 * @param string $status 状态码
 * @param string $detail 失败细节码
 * @return void
 */
function tharchive_redirect_submission_result( $status, $detail = '' ) {
	$query_args = array(
		'tharchive_submit' => sanitize_key( $status ),
	);

	if ( '' !== $detail ) {
		$query_args['tharchive_submit_detail'] = sanitize_text_field( $detail );
	}

	$return_url = tharchive_get_submitted_return_url();
	$redirect   = add_query_arg( $query_args, $return_url );

	wp_safe_redirect( $redirect );
	exit;
}

/**
 * 收集缺失的必填字段
 *
 * @param array $required_values 必填字段键值
 * @return array
 */
function tharchive_collect_missing_required_fields( $required_values ) {
	$missing = array();

	foreach ( $required_values as $key => $value ) {
		if ( '' === trim( (string) $value ) ) {
			$missing[] = sanitize_key( $key );
		}
	}

	return $missing;
}

/**
 * 获取或创建 taxonomy term
 *
 * @param string $term_name term 名称
 * @param string $taxonomy  taxonomy 名称
 * @return int
 */
function tharchive_get_or_create_term_id( $term_name, $taxonomy ) {
	$term_name = trim( sanitize_text_field( $term_name ) );

	if ( '' === $term_name ) {
		return 0;
	}

	$existing = get_term_by( 'name', $term_name, $taxonomy );
	if ( $existing && ! is_wp_error( $existing ) ) {
		return (int) $existing->term_id;
	}

	$inserted = wp_insert_term( $term_name, $taxonomy );
	if ( is_wp_error( $inserted ) || empty( $inserted['term_id'] ) ) {
		return 0;
	}

	return (int) $inserted['term_id'];
}

/**
 * 保存文本 meta
 *
 * @param int    $post_id 文章 ID
 * @param string $key     meta key
 * @param string $value   值
 * @param bool   $long    是否长文本
 * @return void
 */
function tharchive_save_submitted_text_meta( $post_id, $key, $value, $long = false ) {
	$value = wp_unslash( $value );
	$value = $long ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
	update_post_meta( $post_id, $key, $value );
}

/**
 * 保存 URL meta
 *
 * @param int    $post_id 文章 ID
 * @param string $key     meta key
 * @param string $value   值
 * @return void
 */
function tharchive_save_submitted_url_meta( $post_id, $key, $value ) {
	$value = esc_url_raw( wp_unslash( $value ) );
	update_post_meta( $post_id, $key, $value );
}

/**
 * 处理单张上传并生成 attachment
 *
 * @param string $field_name 文件字段名
 * @param int    $post_id    文章 ID
 * @return int
 */
function tharchive_handle_single_image_upload( $field_name, $post_id ) {
	if ( empty( $_FILES[ $field_name ]['name'] ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_handle_upload( $field_name, $post_id );

	if ( is_wp_error( $attachment_id ) ) {
		return 0;
	}

	return (int) $attachment_id;
}

/**
 * 处理多张图集上传
 *
 * @param string $field_name 文件字段名
 * @param int    $post_id    文章 ID
 * @return array
 */
function tharchive_handle_multiple_image_uploads( $field_name, $post_id ) {
	if ( empty( $_FILES[ $field_name ]['name'] ) || ! is_array( $_FILES[ $field_name ]['name'] ) ) {
		return array();
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$uploaded_ids = array();
	$file_count   = count( $_FILES[ $field_name ]['name'] );

	for ( $i = 0; $i < $file_count; $i++ ) {
		if ( empty( $_FILES[ $field_name ]['name'][ $i ] ) ) {
			continue;
		}

		$temp_key = 'tharchive_temp_gallery_' . $i;

		$_FILES[ $temp_key ] = array(
			'name'     => $_FILES[ $field_name ]['name'][ $i ],
			'type'     => $_FILES[ $field_name ]['type'][ $i ],
			'tmp_name' => $_FILES[ $field_name ]['tmp_name'][ $i ],
			'error'    => $_FILES[ $field_name ]['error'][ $i ],
			'size'     => $_FILES[ $field_name ]['size'][ $i ],
		);

		$attachment_id = media_handle_upload( $temp_key, $post_id );

		unset( $_FILES[ $temp_key ] );

		if ( ! is_wp_error( $attachment_id ) && $attachment_id > 0 ) {
			$uploaded_ids[] = (int) $attachment_id;
		}
	}

	return array_values( array_unique( $uploaded_ids ) );
}

/**
 * 投稿处理器
 *
 * @return void
 */
function tharchive_handle_front_submission() {
	if ( ! isset( $_POST['tharchive_front_submit_nonce'] ) ) {
		tharchive_redirect_submission_result( 'nonce_error', 'nonce_missing' );
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['tharchive_front_submit_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'tharchive_front_submit_event' ) ) {
		tharchive_redirect_submission_result( 'nonce_error', 'nonce_invalid' );
	}

	$title = isset( $_POST['tharchive_title'] ) ? sanitize_text_field( wp_unslash( $_POST['tharchive_title'] ) ) : '';

	if ( '' === $title ) {
		tharchive_redirect_submission_result( 'title_missing', 'title_empty' );
	}

	$excerpt = isset( $_POST['tharchive_excerpt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tharchive_excerpt'] ) ) : '';
	$content = isset( $_POST['tharchive_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tharchive_content'] ) ) : '';
	$character = isset( $_POST['tharchive_character'] ) ? sanitize_text_field( wp_unslash( $_POST['tharchive_character'] ) ) : '';
	$organizer = isset( $_POST['tharchive_organizer'] ) ? sanitize_text_field( wp_unslash( $_POST['tharchive_organizer'] ) ) : '';
	$event_date = isset( $_POST['tharchive_event_date'] ) ? sanitize_text_field( wp_unslash( $_POST['tharchive_event_date'] ) ) : '';

	$missing_required_fields = tharchive_collect_missing_required_fields(
		array(
			'excerpt'   => $excerpt,
			'content'   => $content,
			'character' => $character,
			'organizer' => $organizer,
			'eventDate' => $event_date,
		)
	);

	if ( ! empty( $missing_required_fields ) ) {
		tharchive_redirect_submission_result( 'required_missing', 'missing:' . implode( ',', $missing_required_fields ) );
	}

	$cover_file_name  = isset( $_FILES['tharchive_cover_image']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['tharchive_cover_image']['name'] ) ) : '';
	$cover_file_error = isset( $_FILES['tharchive_cover_image']['error'] ) ? absint( $_FILES['tharchive_cover_image']['error'] ) : -1;

	if ( '' === $cover_file_name ) {
		tharchive_redirect_submission_result( 'cover_missing', 'cover_name_empty:error_' . $cover_file_error );
	}

	if ( 0 !== $cover_file_error ) {
		tharchive_redirect_submission_result( 'cover_missing', 'cover_upload_error_' . $cover_file_error );
	}

	$postarr = array(
		'post_type'    => 'relay_event',
		'post_status'  => 'pending',
		'post_title'   => $title,
		'post_excerpt' => $excerpt,
		'post_content' => $content,
	);

	if ( is_user_logged_in() ) {
		$postarr['post_author'] = get_current_user_id();
	}

	$post_id = wp_insert_post( $postarr, true );

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		$insert_error_detail = is_wp_error( $post_id ) ? 'wp_error:' . $post_id->get_error_code() : 'post_id_empty';
		tharchive_redirect_submission_result( 'insert_error', $insert_error_detail );
	}

	/**
	 * taxonomy
	 */
	$character_term_id = tharchive_get_or_create_term_id( $character, 'touhou_character' );
	if ( $character_term_id > 0 ) {
		wp_set_object_terms( $post_id, array( $character_term_id ), 'touhou_character', false );
	}

	$organizer_term_id = tharchive_get_or_create_term_id( $organizer, 'organizer' );
	if ( $organizer_term_id > 0 ) {
		wp_set_object_terms( $post_id, array( $organizer_term_id ), 'organizer', false );
	}

	$status_term_id = tharchive_get_or_create_term_id( '待审核', 'event_status' );
	if ( $status_term_id > 0 ) {
		wp_set_object_terms( $post_id, array( $status_term_id ), 'event_status', false );
	}

	/**
	 * meta
	 */
	$event_year = 0;
	$event_date = isset( $_POST['tharchive_event_date'] ) ? sanitize_text_field( wp_unslash( $_POST['tharchive_event_date'] ) ) : '';

	if ( 0 === $event_year && preg_match( '/^(\d{4})-\d{2}-\d{2}$/', $event_date, $matches ) ) {
		$event_year = absint( $matches[1] );
	}

	update_post_meta( $post_id, 'event_year', $event_year );
	tharchive_save_submitted_text_meta( $post_id, 'event_date', isset( $_POST['tharchive_event_date'] ) ? $_POST['tharchive_event_date'] : '' );
	update_post_meta( $post_id, '_tharchive_submission_channel', 'front_submission' );
	update_post_meta( $post_id, '_tharchive_submission_created_at', current_time( 'mysql' ) );

	tharchive_save_submitted_url_meta( $post_id, 'bilibili_summary_url', isset( $_POST['tharchive_bilibili_summary_url'] ) ? $_POST['tharchive_bilibili_summary_url'] : '' );
	tharchive_save_submitted_url_meta( $post_id, 'archive_site_url', isset( $_POST['tharchive_archive_site_url'] ) ? $_POST['tharchive_archive_site_url'] : '' );
	tharchive_save_submitted_text_meta( $post_id, 'source_raw_text', isset( $_POST['tharchive_source_raw_text'] ) ? $_POST['tharchive_source_raw_text'] : '', true );
	tharchive_save_submitted_text_meta( $post_id, 'other_notes', isset( $_POST['tharchive_other_notes'] ) ? $_POST['tharchive_other_notes'] : '', true );

	/**
	 * 图片
	 */
	$cover_attachment_id = tharchive_handle_single_image_upload( 'tharchive_cover_image', $post_id );
	if ( $cover_attachment_id > 0 ) {
		set_post_thumbnail( $post_id, $cover_attachment_id );
	}

	$gallery_attachment_ids = tharchive_handle_multiple_image_uploads( 'tharchive_gallery_images', $post_id );
	if ( ! empty( $gallery_attachment_ids ) ) {
		update_post_meta( $post_id, 'gallery_images', $gallery_attachment_ids );
	}

	tharchive_redirect_submission_result( 'success' );
}
add_action( 'admin_post_tharchive_submit_event', 'tharchive_handle_front_submission' );
add_action( 'admin_post_nopriv_tharchive_submit_event', 'tharchive_handle_front_submission' );
