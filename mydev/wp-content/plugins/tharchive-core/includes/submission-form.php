<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 获取投稿页返回地址
 *
 * @return string
 */
function tharchive_get_submission_return_url() {
	global $post;

	if ( $post instanceof WP_Post ) {
		$link = get_permalink( $post );
		if ( $link ) {
			return $link;
		}
	}

	return home_url( '/' );
}

/**
 * 拉取某个 taxonomy 的术语名称列表，给前端做自动补全/建议值
 *
 * @param string $taxonomy taxonomy 名称
 * @param int    $limit    数量上限
 * @return array
 */
function tharchive_get_taxonomy_term_name_suggestions( $taxonomy, $limit = 100 ) {
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'number'     => $limit,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
		return array();
	}

	$names = array();

	foreach ( $terms as $term ) {
		if ( ! empty( $term->name ) ) {
			$names[] = $term->name;
		}
	}

	return array_values( array_unique( $names ) );
}

/**
 * 组织前端启动配置
 *
 * @return array
 */
function tharchive_get_submission_bootstrap_data() {
	return array(
		'submitUrl' => admin_url( 'admin-post.php' ),
		'action'    => 'tharchive_submit_event',
		'nonce'     => wp_create_nonce( 'tharchive_front_submit_event' ),
		'returnUrl' => tharchive_get_submission_return_url(),
		'defaults'  => array(),
		'suggestions' => array(
			'characters'  => tharchive_get_taxonomy_term_name_suggestions( 'touhou_character', 200 ),
			'organizers'  => tharchive_get_taxonomy_term_name_suggestions( 'organizer', 200 ),
		),
		'upload' => array(
			'acceptedImageTypes' => array( 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ),
			'maxGalleryFiles'    => 20,
		),
		'labels' => array(
			'submitButton'   => '提交活动信息',
			'submittingText' => '正在提交……',
		),
	);
}

/**
 * 注册前台投稿页所需的资源
 */
function tharchive_register_submission_frontend_assets() {
	$script_handle = 'tharchive-submission-app';
	$style_handle  = 'tharchive-submission-app-style';

	$script_rel_path = 'assets/dist/submission-app.js';
	$style_rel_path  = 'assets/dist/submission-app.css';

	$script_abs_path = THARCHIVE_CORE_PATH . $script_rel_path;
	$style_abs_path  = THARCHIVE_CORE_PATH . $style_rel_path;

	$script_url = THARCHIVE_CORE_URL . $script_rel_path;
	$style_url  = THARCHIVE_CORE_URL . $style_rel_path;

	if ( file_exists( $script_abs_path ) ) {
		wp_register_script(
			$script_handle,
			$script_url,
			array(),
			(string) filemtime( $script_abs_path ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	if ( file_exists( $style_abs_path ) ) {
		wp_register_style(
			$style_handle,
			$style_url,
			array(),
			(string) filemtime( $style_abs_path )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'tharchive_register_submission_frontend_assets' );

/**
 * 短代码：输出 Vue 挂载点
 *
 * 用法：
 * [tharchive_event_submission_form]
 *
 * @return string
 */
function tharchive_render_event_submission_app_shortcode() {
	$script_handle = 'tharchive-submission-app';
	$style_handle  = 'tharchive-submission-app-style';

	if ( ! wp_script_is( $script_handle, 'registered' ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<div>THArchive 投稿前端资源未找到。请先构建 Vue 产物到 assets/dist/ 目录。</div>';
		}
		return '<div>投稿功能暂未就绪，请稍后再试。</div>';
	}

	wp_enqueue_script( $script_handle );

	if ( wp_style_is( $style_handle, 'registered' ) ) {
		wp_enqueue_style( $style_handle );
	}

	$bootstrap = tharchive_get_submission_bootstrap_data();

	wp_add_inline_script(
		$script_handle,
		'window.THARCHIVE_SUBMISSION_BOOTSTRAP = ' . wp_json_encode( $bootstrap ) . ';',
		'before'
	);

	ob_start();
	?>
	<div class="tharchive-submission-page">
		<div id="tharchive-submission-app"></div>
		<noscript>该投稿页面需要启用 JavaScript。</noscript>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'tharchive_event_submission_form', 'tharchive_render_event_submission_app_shortcode' );