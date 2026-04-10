<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 组织 archive app 的前端启动配置。
 *
 * @return array
 */
function tharchive_get_archive_bootstrap_data() {
	return array(
		'restUrl'    => esc_url_raw( rest_url() ),
		'archiveUrl' => get_post_type_archive_link( 'relay_event' ),
		'mountId'    => 'tharchive-relay-index',
	);
}

/**
 * 获取 archive app 资源版本。
 *
 * @param string $relative_path 资源相对路径。
 * @return string
 */
function tharchive_get_archive_app_asset_version( $relative_path ) {
	$asset_path = THARCHIVE_CORE_PATH . ltrim( $relative_path, '/' );

	if ( file_exists( $asset_path ) ) {
		return (string) filemtime( $asset_path );
	}

	return THARCHIVE_CORE_VERSION;
}

/**
 * 注册 relay_event REST 年份筛选参数。
 *
 * @param array $params REST collection 参数。
 * @return array
 */
function tharchive_register_archive_app_collection_params( $params ) {
	$params['event_year'] = array(
		'description'       => '按 event_year 元字段筛选活动。',
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'validate_callback' => static function ( $value ) {
			return empty( $value ) || absint( $value ) > 0;
		},
	);

	$params['event_date_after'] = array(
		'description'       => '筛选与该日期有重叠的活动：event_date_end >= 该日期，无 end 则回退 event_date（YYYY-MM-DD）。',
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'validate_callback' => static function ( $value ) {
			return empty( $value ) || (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value );
		},
	);

	$params['event_date_before'] = array(
		'description'       => '筛选与该日期有重叠的活动：event_date <= 该日期（YYYY-MM-DD）。',
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'validate_callback' => static function ( $value ) {
			return empty( $value ) || (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value );
		},
	);

	// 提升 per_page 上限，日历视图按月拉取时需要单次返回该月全部活动。
	if ( isset( $params['per_page']['maximum'] ) ) {
		$params['per_page']['maximum'] = 999;
	}

	return $params;
}
add_filter( 'rest_relay_event_collection_params', 'tharchive_register_archive_app_collection_params' );

/**
 * 将 archive app 的年份筛选映射到 WP_Query。
 *
 * @param array           $args    WP_Query 参数。
 * @param WP_REST_Request $request REST 请求。
 * @return array
 */
function tharchive_filter_archive_app_rest_query( $args, $request ) {
	$meta_query = isset( $args['meta_query'] ) && is_array( $args['meta_query'] )
		? $args['meta_query']
		: array();

	$event_year = absint( $request->get_param( 'event_year' ) );
	if ( $event_year > 0 ) {
		$meta_query[] = array(
			'key'     => 'event_year',
			'value'   => $event_year,
			'compare' => '=',
			'type'    => 'NUMERIC',
		);
	}

	$date_after  = sanitize_text_field( (string) $request->get_param( 'event_date_after' ) );
	$date_before = sanitize_text_field( (string) $request->get_param( 'event_date_before' ) );

	// Overlap semantics: eventStart <= filterEnd && eventEnd >= filterStart.
	// event_date_after = filterStart → event must not end before this date.
	// event_date_end is an optional admin-only field; most records only have event_date.
	// When event_date_end is missing or empty, fall back to event_date.
	if ( $date_after && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_after ) ) {
		$meta_query[] = array(
			'relation' => 'OR',
			array(
				'key'     => 'event_date_end',
				'value'   => $date_after,
				'compare' => '>=',
				'type'    => 'DATE',
			),
			array(
				'relation' => 'AND',
				// event_date_end absent or saved as empty string by admin.
				array(
					'relation' => 'OR',
					array(
						'key'     => 'event_date_end',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'event_date_end',
						'value'   => '',
						'compare' => '=',
					),
				),
				array(
					'key'     => 'event_date',
					'value'   => $date_after,
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		);
	}

	// event_date_before = filterEnd → event must not start after this date.
	if ( $date_before && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_before ) ) {
		$meta_query[] = array(
			'key'     => 'event_date',
			'value'   => $date_before,
			'compare' => '<=',
			'type'    => 'DATE',
		);
	}

	if ( ! empty( $meta_query ) ) {
		$args['meta_query'] = $meta_query;
	}

	return $args;
}
add_filter( 'rest_relay_event_query', 'tharchive_filter_archive_app_rest_query', 10, 2 );

/**
 * 注册 archive app 前台资源。
 */
function tharchive_register_archive_frontend_assets() {
	$script_relative_path = 'assets/dist/archive-app.js';
	$style_relative_path  = 'assets/dist/archive-app.css';
	$script_handle        = 'tharchive-archive-app-script';
	$style_handle         = 'tharchive-archive-app-style';
	$script_abs_path      = THARCHIVE_CORE_PATH . $script_relative_path;
	$style_abs_path       = THARCHIVE_CORE_PATH . $style_relative_path;

	if ( file_exists( $script_abs_path ) ) {
		wp_register_script(
			$script_handle,
			THARCHIVE_CORE_URL . $script_relative_path,
			array(),
			tharchive_get_archive_app_asset_version( $script_relative_path ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	if ( file_exists( $style_abs_path ) ) {
		wp_register_style(
			$style_handle,
			THARCHIVE_CORE_URL . $style_relative_path,
			array(),
			tharchive_get_archive_app_asset_version( $style_relative_path )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'tharchive_register_archive_frontend_assets' );

/**
 * 为 archive app 注入脚本数据。
 */
function tharchive_enqueue_archive_app_assets() {
	$script_handle = 'tharchive-archive-app-script';
	$style_handle  = 'tharchive-archive-app-style';

	wp_enqueue_script( $script_handle );

	if ( wp_style_is( $style_handle, 'registered' ) ) {
		wp_enqueue_style( $style_handle );
	}

	wp_add_inline_script(
		$script_handle,
		'window.THARCHIVE_ARCHIVE_BOOTSTRAP = ' . wp_json_encode( tharchive_get_archive_bootstrap_data() ) . ';',
		'before'
	);
}

/**
 * 渲染 archive app 挂载点。
 *
 * Shortcode:
 * [tharchive_archive_app]
 * [tharchive_relay_index]
 *
 * @return string
 */
function tharchive_render_archive_app_shortcode() {
	$script_handle = 'tharchive-archive-app-script';

	if ( ! wp_script_is( $script_handle, 'registered' ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<div class="tharchive-archive-app-missing">Archive App 尚未构建，请先在插件目录执行 npm run build。</div>';
		}

		return '<div class="tharchive-archive-app-missing">归档功能暂未就绪，请稍后再试。</div>';
	}

	tharchive_enqueue_archive_app_assets();

	ob_start();
	?>
	<div id="tharchive-relay-index" data-tharchive-archive-app="1"></div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'tharchive_archive_app', 'tharchive_render_archive_app_shortcode' );
add_shortcode( 'tharchive_relay_index', 'tharchive_render_archive_app_shortcode' );

/**
 * 模板内直接输出 archive app。
 */
function tharchive_render_archive_app() {
	echo do_shortcode( '[tharchive_archive_app]' );
}