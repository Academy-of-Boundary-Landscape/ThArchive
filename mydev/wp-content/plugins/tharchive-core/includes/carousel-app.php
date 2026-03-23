<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 获取 carousel app 资源版本。
 *
 * @param string $relative_path 资源相对路径。
 * @return string
 */
function tharchive_get_carousel_app_asset_version( $relative_path ) {
	$asset_path = THARCHIVE_CORE_PATH . ltrim( $relative_path, '/' );

	if ( file_exists( $asset_path ) ) {
		return (string) filemtime( $asset_path );
	}

	return THARCHIVE_CORE_VERSION;
}

/**
 * 注册 carousel app 前台资源。
 */
function tharchive_register_carousel_frontend_assets() {
	$script_relative_path = 'assets/dist/carousel-app.js';
	$style_relative_path  = 'assets/dist/carousel-app.css';
	$script_handle        = 'tharchive-carousel-app-script';
	$style_handle         = 'tharchive-carousel-app-style';
	$script_abs_path      = THARCHIVE_CORE_PATH . $script_relative_path;
	$style_abs_path       = THARCHIVE_CORE_PATH . $style_relative_path;

	if ( file_exists( $script_abs_path ) ) {
		wp_register_script(
			$script_handle,
			THARCHIVE_CORE_URL . $script_relative_path,
			array(),
			tharchive_get_carousel_app_asset_version( $script_relative_path ),
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
			tharchive_get_carousel_app_asset_version( $style_relative_path )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'tharchive_register_carousel_frontend_assets' );

/**
 * 解析短代码配置。
 *
 * @param array<string, string> $atts 原始短代码属性。
 * @return array<string, mixed>
 */
function tharchive_parse_carousel_shortcode_config( $atts ) {
	$defaults = array(
		'mode'       => 'recent',
		'year'       => '',
		'per_page'   => '12',
		'orderby'    => 'date',
		'order'      => 'desc',
		'title'      => '',
		'empty_text' => '',
	);

	$raw = shortcode_atts( $defaults, $atts, 'tharchive_event_carousel' );

	$mode = sanitize_key( $raw['mode'] );
	if ( ! in_array( $mode, array( 'recent', 'year' ), true ) ) {
		$mode = 'recent';
	}

	$year = absint( $raw['year'] );
	if ( 'year' === $mode && $year <= 0 ) {
		$year = (int) gmdate( 'Y' );
	}

	$per_page = absint( $raw['per_page'] );
	if ( $per_page < 1 ) {
		$per_page = 12;
	}
	if ( $per_page > 30 ) {
		$per_page = 30;
	}

	$orderby = sanitize_key( $raw['orderby'] );
	if ( ! in_array( $orderby, array( 'date', 'modified', 'title' ), true ) ) {
		$orderby = 'date';
	}

	$order = strtolower( sanitize_text_field( $raw['order'] ) );
	if ( ! in_array( $order, array( 'asc', 'desc' ), true ) ) {
		$order = 'desc';
	}

	$title = sanitize_text_field( $raw['title'] );
	if ( '' === $title ) {
		$title = 'year' === $mode ? sprintf( '%d 年活动轮播', $year ) : '近期活动轮播';
	}

	$empty_text = sanitize_text_field( $raw['empty_text'] );
	if ( '' === $empty_text ) {
		$empty_text = 'year' === $mode ? sprintf( '%d 年暂无可展示活动。', $year ) : '近期没有可展示活动。';
	}

	return array(
		'restUrl'   => esc_url_raw( rest_url() ),
		'mode'      => $mode,
		'year'      => 'year' === $mode ? $year : null,
		'perPage'   => $per_page,
		'orderby'   => $orderby,
		'order'     => $order,
		'title'     => $title,
		'emptyText' => $empty_text,
	);
}

/**
 * 短代码：输出轮播挂载点。
 *
 * [tharchive_event_carousel mode="recent|year" year="2026" per_page="12" orderby="date|modified|title" order="desc|asc" title="" empty_text=""]
 * [tharchive_relay_carousel ...]
 *
 * @param array<string, string> $atts 短代码属性。
 * @return string
 */
function tharchive_render_event_carousel_shortcode( $atts ) {
	$script_handle = 'tharchive-carousel-app-script';
	$style_handle  = 'tharchive-carousel-app-style';

	if ( ! wp_script_is( $script_handle, 'registered' ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<div class="tharchive-carousel-app-missing">Carousel App 尚未构建，请先在插件目录执行 npm run build:carousel。</div>';
		}

		return '<div class="tharchive-carousel-app-missing">轮播功能暂未就绪，请稍后再试。</div>';
	}

	$config = tharchive_parse_carousel_shortcode_config( $atts );

	wp_enqueue_script( $script_handle );

	if ( wp_style_is( $style_handle, 'registered' ) ) {
		wp_enqueue_style( $style_handle );
	}

	$mount_attrs = sprintf(
		'data-tharchive-carousel-app="1" data-config="%s"',
		esc_attr( wp_json_encode( $config ) )
	);

	ob_start();
	?>
	<div class="tharchive-carousel-embed" <?php echo $mount_attrs; ?>></div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'tharchive_event_carousel', 'tharchive_render_event_carousel_shortcode' );
add_shortcode( 'tharchive_relay_carousel', 'tharchive_render_event_carousel_shortcode' );
