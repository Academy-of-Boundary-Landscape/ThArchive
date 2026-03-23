<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 读取活动 meta。
 *
 * @param int    $post_id 文章 ID。
 * @param string $key meta key。
 * @param mixed  $default 默认值。
 * @return mixed
 */
if ( ! function_exists( 'tharchive_get_event_meta' ) ) {
	function tharchive_get_event_meta( $post_id, $key, $default = '' ) {
		$value = get_post_meta( $post_id, $key, true );

		if ( '' === $value || null === $value ) {
			return $default;
		}

		return $value;
	}
}

/**
 * 获取 taxonomy terms。
 *
 * @param int    $post_id 文章 ID。
 * @param string $taxonomy taxonomy。
 * @return WP_Term[]
 */
function tharchive_get_event_terms( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	return $terms;
}

/**
 * 格式化活动日期区间。
 *
 * @param int $post_id 文章 ID。
 * @return string
 */
function tharchive_get_event_date_label( $post_id ) {
	$start = (string) tharchive_get_event_meta( $post_id, 'event_date', '' );
	$end   = (string) tharchive_get_event_meta( $post_id, 'event_date_end', '' );

	$start_label = tharchive_format_event_date( $start );
	$end_label   = tharchive_format_event_date( $end );

	if ( '' === $start_label ) {
		return '';
	}

	if ( '' === $end_label || $start_label === $end_label ) {
		return $start_label;
	}

	return $start_label . ' - ' . $end_label;
}

/**
 * 格式化日期字符串。
 *
 * @param string $date 日期字符串。
 * @return string
 */
function tharchive_format_event_date( $date ) {
	$date = trim( (string) $date );

	if ( '' === $date ) {
		return '';
	}

	$timestamp = strtotime( $date );

	if ( false === $timestamp ) {
		return $date;
	}

	return wp_date( 'Y.m.d', $timestamp );
}

/**
 * 渲染活动标签组。
 *
 * @param int    $post_id 文章 ID。
 * @param string $taxonomy taxonomy。
 * @param string $label 区块标题。
 * @param bool   $link_terms 是否输出为可点击链接。
 * @return string
 */
function tharchive_render_event_term_group( $post_id, $taxonomy, $label, $link_terms = true ) {
	$terms = tharchive_get_event_terms( $post_id, $taxonomy );

	if ( empty( $terms ) ) {
		return '';
	}

	$items = array();

	foreach ( $terms as $term ) {
		$text = esc_html( $term->name );

		if ( $link_terms ) {
			$link = get_term_link( $term );
		} else {
			$link = new WP_Error( 'tharchive_term_link_disabled', 'Term links disabled for this context.' );
		}

		if ( ! is_wp_error( $link ) ) {
			$items[] = '<a class="tharchive-chip" href="' . esc_url( $link ) . '">' . $text . '</a>';
		} else {
			$items[] = '<span class="tharchive-chip">' . $text . '</span>';
		}
	}

	return '<div class="tharchive-meta-block"><span class="tharchive-meta-block__label">' . esc_html( $label ) . '</span><div class="tharchive-chip-list">' . implode( '', $items ) . '</div></div>';
}

/**
 * 获取按钮配置。
 *
 * @param int $post_id 文章 ID。
 * @return array<int,array<string,string|bool>>
 */
function tharchive_get_event_action_links( $post_id ) {
	$links = array();

	$summary_url = (string) tharchive_get_event_meta( $post_id, 'bilibili_summary_url', '' );
	$summary_url = esc_url_raw( $summary_url );
	$links[]     = array(
		'label'    => '查看总结专栏',
		'url'      => $summary_url,
		'external' => true,
		'disabled' => '' === $summary_url,
	);

	$archive_site_url = (string) tharchive_get_event_meta( $post_id, 'archive_site_url', '' );
	$archive_site_url = esc_url_raw( $archive_site_url );
	$links[]          = array(
		'label'    => '查看独立归档站',
		'url'      => $archive_site_url,
		'external' => true,
		'disabled' => '' === $archive_site_url,
	);

	$archive_link = get_post_type_archive_link( 'relay_event' );
	if ( $archive_link ) {
		$links[] = array(
			'label'    => '返回活动列表',
			'url'      => $archive_link,
			'external' => false,
		);
	}

	return $links;
}

/**
 * 渲染活动按钮组。
 *
 * @param int $post_id 文章 ID。
 * @return string
 */
function tharchive_render_event_actions( $post_id ) {
	$links = tharchive_get_event_action_links( $post_id );

	if ( empty( $links ) ) {
		return '';
	}

	$items = array();

	foreach ( $links as $link ) {
		$is_disabled = ! empty( $link['disabled'] );
		$rel    = ! empty( $link['external'] ) ? ' noreferrer noopener' : '';
		$target = ! empty( $link['external'] ) ? ' target="_blank"' : '';
		$class  = 'tharchive-action';

		if ( '返回活动列表' === $link['label'] ) {
			$class .= ' tharchive-action--ghost';
		}

		if ( $is_disabled ) {
			$items[] = '<span class="' . esc_attr( $class . ' tharchive-action--disabled' ) . '" aria-disabled="true">' . esc_html( (string) $link['label'] ) . '</span>';
			continue;
		}

		$items[] = '<a class="' . esc_attr( $class ) . '" href="' . esc_url( (string) $link['url'] ) . '"' . $target . ( $rel ? ' rel="' . esc_attr( trim( $rel ) ) . '"' : '' ) . '>' . esc_html( (string) $link['label'] ) . '</a>';
	}

	return '<div class="tharchive-action-group">' . implode( '', $items ) . '</div>';
}

/**
 * 渲染活动图集。
 *
 * @param int $post_id 文章 ID。
 * @return string
 */
function tharchive_render_event_gallery( $post_id ) {
	$image_ids = tharchive_get_event_meta( $post_id, 'gallery_images', array() );

	if ( ! is_array( $image_ids ) || empty( $image_ids ) ) {
		return '';
	}

	$items = array();

	foreach ( $image_ids as $image_id ) {
		$image_id = absint( $image_id );

		if ( $image_id <= 0 ) {
			continue;
		}

		$full_url   = wp_get_attachment_image_url( $image_id, 'full' );
		$image_html = wp_get_attachment_image( $image_id, 'large', false, array( 'loading' => 'lazy' ) );
		$caption    = wp_get_attachment_caption( $image_id );

		if ( ! $full_url || ! $image_html ) {
			continue;
		}

		$item = '<figure class="tharchive-gallery__item"><a href="' . esc_url( $full_url ) . '" target="_blank" rel="noreferrer noopener">' . $image_html . '</a>';

		if ( $caption ) {
			$item .= '<figcaption>' . esc_html( $caption ) . '</figcaption>';
		}

		$item .= '</figure>';
		$items[] = $item;
	}

	if ( empty( $items ) ) {
		return '';
	}

	return '<div class="tharchive-gallery">' . implode( '', $items ) . '</div>';
}

/**
 * 渲染活动信息卡。
 *
 * @param int $post_id 文章 ID。
 * @return string
 */
function tharchive_render_event_meta_cards( $post_id ) {
	$cards = array();

	$date_label = tharchive_get_event_date_label( $post_id );
	if ( '' !== $date_label ) {
		$cards[] = array(
			'label' => '活动日期',
			'value' => $date_label,
		);
	}

	$status_names = wp_list_pluck( tharchive_get_event_terms( $post_id, 'event_status' ), 'name' );
	if ( ! empty( $status_names ) ) {
		$cards[] = array(
			'label' => '活动状态',
			'value' => implode( ' / ', $status_names ),
		);
	}

	$character_names = wp_list_pluck( tharchive_get_event_terms( $post_id, 'touhou_character' ), 'name' );
	if ( ! empty( $character_names ) ) {
		$cards[] = array(
			'label' => '东方角色',
			'value' => implode( ' / ', $character_names ),
		);
		}

	$organizer_names = wp_list_pluck( tharchive_get_event_terms( $post_id, 'organizer' ), 'name' );
	if ( ! empty( $organizer_names ) ) {
		$cards[] = array(
			'label' => '主办方',
			'value' => implode( ' / ', $organizer_names ),
		);
	}

	$topic_names = wp_list_pluck( tharchive_get_event_terms( $post_id, 'touhou_topic' ), 'name' );
	if ( ! empty( $topic_names ) ) {
		$cards[] = array(
			'label' => '主题标签',
			'value' => implode( ' / ', $topic_names ),
		);
	}

	if ( empty( $cards ) ) {
		return '';
	}

	$html = '<div class="tharchive-info-grid">';

	foreach ( $cards as $card ) {
		$html .= '<div class="tharchive-info-card"><span class="tharchive-info-card__label">' . esc_html( $card['label'] ) . '</span><strong class="tharchive-info-card__value">' . esc_html( $card['value'] ) . '</strong></div>';
	}

	$html .= '</div>';

	return $html;
}
