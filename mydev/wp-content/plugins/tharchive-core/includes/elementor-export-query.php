<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 获取 Elementor 旧文导出页中可选的 post type。
 *
 * @return array<string, WP_Post_Type>
 */
function tharchive_get_elementor_export_post_type_choices() {
	$post_types = get_post_types(
		array(
			'show_ui' => true,
		),
		'objects'
	);

	unset( $post_types['attachment'] );
	unset( $post_types['revision'] );
	unset( $post_types['nav_menu_item'] );
	unset( $post_types['custom_css'] );
	unset( $post_types['customize_changeset'] );
	unset( $post_types['oembed_cache'] );
	unset( $post_types['user_request'] );
	unset( $post_types['wp_block'] );
	unset( $post_types['wp_template'] );
	unset( $post_types['wp_template_part'] );
	unset( $post_types['wp_navigation'] );

	return $post_types;
}

/**
 * 获取 Elementor 旧文导出的默认筛选。
 *
 * @return array<string, mixed>
 */
function tharchive_get_elementor_export_defaults() {
	return array(
		'post_types'            => array( 'post', 'page' ),
		'post_statuses'         => array( 'publish', 'draft', 'private' ),
		'limit'                 => 50,
		'offset'                => 0,
		'include_raw_elementor' => 0,
	);
}

/**
 * 读取并清洗导出筛选项。
 *
 * @param array $input 原始输入。
 * @return array<string, mixed>
 */
function tharchive_sanitize_elementor_export_request( $input ) {
	$defaults   = tharchive_get_elementor_export_defaults();
	$allowed_pt = array_keys( tharchive_get_elementor_export_post_type_choices() );
	$statuses   = get_post_stati( array(), 'names' );

	$post_types = isset( $input['post_types'] ) ? (array) $input['post_types'] : array();
	$post_types = array_map( 'sanitize_key', $post_types );
	$post_types = array_values( array_intersect( $post_types, $allowed_pt ) );

	if ( empty( $post_types ) ) {
		$post_types = $defaults['post_types'];
	}

	$post_statuses = isset( $input['post_statuses'] ) ? (array) $input['post_statuses'] : array();
	$post_statuses = array_map( 'sanitize_key', $post_statuses );
	$post_statuses = array_values( array_intersect( $post_statuses, $statuses ) );

	if ( empty( $post_statuses ) ) {
		$post_statuses = $defaults['post_statuses'];
	}

	$limit  = isset( $input['limit'] ) ? absint( $input['limit'] ) : (int) $defaults['limit'];
	$offset = isset( $input['offset'] ) ? absint( $input['offset'] ) : (int) $defaults['offset'];

	if ( $limit <= 0 ) {
		$limit = (int) $defaults['limit'];
	}

	$limit = min( $limit, 500 );

	return array(
		'post_types'            => $post_types,
		'post_statuses'         => $post_statuses,
		'limit'                 => $limit,
		'offset'                => $offset,
		'include_raw_elementor' => ! empty( $input['include_raw_elementor'] ) ? 1 : 0,
	);
}

/**
 * 查询符合筛选条件的 Elementor 文章数量。
 *
 * @param array $filters 筛选条件。
 * @return int
 */
function tharchive_count_elementor_export_posts( $filters ) {
	$query = new WP_Query(
		array(
			'post_type'              => $filters['post_types'],
			'post_status'            => $filters['post_statuses'],
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				array(
					'key'     => '_elementor_data',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	return (int) $query->found_posts;
}

/**
 * 查询需要导出的 Elementor 文章。
 *
 * @param array $filters 筛选条件。
 * @return WP_Post[]
 */
function tharchive_get_elementor_export_posts( $filters ) {
	$query = new WP_Query(
		array(
			'post_type'              => $filters['post_types'],
			'post_status'            => $filters['post_statuses'],
			'posts_per_page'         => $filters['limit'],
			'offset'                 => $filters['offset'],
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
			'meta_query'             => array(
				array(
					'key'     => '_elementor_data',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	return $query->posts;
}

/**
 * 收集文章 taxonomy 信息。
 *
 * @param WP_Post $post 文章对象。
 * @return array<string, array<int, array<string, mixed>>>
 */
function tharchive_collect_elementor_export_terms( $post ) {
	$taxonomies = get_object_taxonomies( $post->post_type, 'objects' );
	$result     = array();

	foreach ( $taxonomies as $taxonomy => $taxonomy_obj ) {
		$terms = get_the_terms( $post, $taxonomy );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			continue;
		}

		$result[ $taxonomy ] = array_map(
			function( $term ) {
				return array(
					'term_id' => (int) $term->term_id,
					'name'    => $term->name,
					'slug'    => $term->slug,
				);
			},
			$terms
		);
	}

	return $result;
}

/**
 * 聚合图片 URL。
 *
 * @param WP_Post $post     当前文章。
 * @param array   $sections 已抽出的 section。
 * @return array<int, string>
 */
function tharchive_collect_elementor_export_images( $post, $sections ) {
	$image_urls = array();
	$featured   = get_the_post_thumbnail_url( $post, 'full' );

	if ( $featured ) {
		$image_urls[] = $featured;
	}

	foreach ( $sections as $section ) {
		if ( empty( $section['image_urls'] ) || ! is_array( $section['image_urls'] ) ) {
			continue;
		}

		$image_urls = array_merge( $image_urls, $section['image_urls'] );
	}

	return array_values( array_unique( array_filter( $image_urls ) ) );
}

/**
 * 准备单篇文章的导出记录。
 *
 * @param WP_Post $post                  文章对象。
 * @param bool    $include_raw_elementor 是否附带原始 Elementor JSON。
 * @return array<string, mixed>
 */
function tharchive_prepare_elementor_export_record( $post, $include_raw_elementor = false ) {
	$elementor_data          = tharchive_decode_elementor_export_json( get_post_meta( $post->ID, '_elementor_data', true ) );
	$elementor_page_settings = tharchive_decode_elementor_export_json( get_post_meta( $post->ID, '_elementor_page_settings', true ) );
	$elementor_sections      = tharchive_collect_elementor_export_sections( $elementor_data, $post->ID );
	$button_links            = tharchive_collect_elementor_export_button_links( $elementor_data, $post->ID );
	$terms_by_taxonomy       = tharchive_collect_elementor_export_terms( $post );
	$all_images              = tharchive_collect_elementor_export_images( $post, $elementor_sections );
	$main_body_markdown      = tharchive_choose_elementor_export_main_body( $elementor_sections, $post );

	$record = array(
		'id'                 => (int) $post->ID,
		'post_type'          => $post->post_type,
		'post_status'        => $post->post_status,
		'post_name'          => $post->post_name,
		'post_title'         => get_the_title( $post ),
		'post_excerpt'       => $post->post_excerpt,
		'post_date'          => $post->post_date,
		'post_modified'      => $post->post_modified,
		'permalink'          => get_permalink( $post ),
		'featured_image_url' => get_the_post_thumbnail_url( $post, 'full' ) ?: '',
		'terms'              => $terms_by_taxonomy,
		'raw_meta'           => array(
			'_elementor_edit_mode' => (string) get_post_meta( $post->ID, '_elementor_edit_mode', true ),
			'_thumbnail_id'        => (int) get_post_thumbnail_id( $post ),
		),
		'elementor_sections' => $elementor_sections,
		'button_links'       => $button_links,
		'image_urls'         => $all_images,
		'plain_text'         => trim( wp_strip_all_tags( $main_body_markdown ) ),
		'main_body_markdown' => $main_body_markdown,
	);

	if ( ! empty( $elementor_page_settings ) ) {
		$record['raw_meta']['_elementor_page_settings'] = $elementor_page_settings;
	}

	if ( $include_raw_elementor ) {
		$record['raw_elementor_data'] = $elementor_data;
	}

	return $record;
}

/**
 * 批量准备导出记录。
 *
 * @param WP_Post[] $posts   文章集合。
 * @param bool      $include_raw_elementor 是否附带原始 Elementor JSON。
 * @return array<int, array<string, mixed>>
 */
function tharchive_prepare_elementor_export_records( $posts, $include_raw_elementor = false ) {
	$items = array();

	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		$items[] = tharchive_prepare_elementor_export_record( $post, $include_raw_elementor );
	}

	return $items;
}
