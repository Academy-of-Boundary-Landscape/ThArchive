<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 读取活动 meta。
 *
 * @param int    $post_id 文章 ID。
 * @param string $key     meta key。
 * @param mixed  $default 默认值。
 * @return mixed
 */
function tharchive_get_event_meta( $post_id, $key, $default = '' ) {
	$value = get_post_meta( $post_id, $key, true );

	if ( '' === $value || null === $value ) {
		return $default;
	}

	return $value;
}

/**
 * 解析逗号分隔的整数列表（主要给图片 ID 列表使用）
 *
 * @param string $raw 原始字符串（逗号、空格或换行分隔）
 * @return array
 */
function tharchive_parse_comma_separated_integers( $raw ) {
	if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
		return array();
	}

	$parts = preg_split( '/[\s,]+/', $raw );
	$parts = is_array( $parts ) ? $parts : array();
	$ids   = array_map( 'absint', $parts );
	$ids   = array_filter(
		$ids,
		function( $item ) {
			return $item > 0;
		}
	);

	return array_values( array_unique( $ids ) );
}

/**
 * 清洗整数数组（主要给图片 ID 列表使用）
 *
 * @param mixed $value 原始值
 * @return array
 */
function tharchive_sanitize_integer_array( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$value = array_map( 'absint', $value );
	$value = array_filter(
		$value,
		function( $item ) {
			return $item > 0;
		}
	);

	return array_values( $value );
}
