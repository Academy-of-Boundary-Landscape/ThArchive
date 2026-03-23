<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 判断是否为前台投稿创建的活动。
 *
 * @param int $post_id 文章 ID。
 * @return bool
 */
function tharchive_is_front_submission_event( $post_id ) {
	return 'front_submission' === get_post_meta( $post_id, '_tharchive_submission_channel', true );
}

/**
 * 获取待审核前台投稿数量。
 *
 * @return int
 */
function tharchive_get_pending_front_submission_count() {
	$query = new WP_Query(
		array(
			'post_type'              => 'relay_event',
			'post_status'            => 'pending',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				array(
					'key'   => '_tharchive_submission_channel',
					'value' => 'front_submission',
				),
			),
		)
	);

	return (int) $query->found_posts;
}

/**
 * 增加后台“待审核投稿”入口。
 */
function tharchive_register_pending_review_submenu() {
	$count = tharchive_get_pending_front_submission_count();
	$title = '待审核投稿';

	if ( $count > 0 ) {
		$title .= ' <span class="awaiting-mod count-' . $count . '"><span class="pending-count">' . $count . '</span></span>';
	}

	add_submenu_page(
		'edit.php?post_type=relay_event',
		'待审核投稿',
		$title,
		'edit_posts',
		'edit.php?post_type=relay_event&post_status=pending&tharchive_submission_channel=front_submission'
	);
}
add_action( 'admin_menu', 'tharchive_register_pending_review_submenu' );

/**
 * 调整活动归档子菜单顺序，让待审核投稿更靠前。
 *
 * @return void
 */
function tharchive_reorder_relay_event_submenu() {
	global $submenu;

	$menu_key = 'edit.php?post_type=relay_event';

	if ( empty( $submenu[ $menu_key ] ) || ! is_array( $submenu[ $menu_key ] ) ) {
		return;
	}

	$pending_item = null;
	$other_items  = array();

	foreach ( $submenu[ $menu_key ] as $item ) {
		if ( ! isset( $item[2] ) ) {
			$other_items[] = $item;
			continue;
		}

		if ( false !== strpos( $item[2], 'tharchive_submission_channel=front_submission' ) ) {
			$pending_item = $item;
			continue;
		}

		$other_items[] = $item;
	}

	if ( null === $pending_item ) {
		return;
	}

	$reordered = array();
	$inserted  = false;

	foreach ( $other_items as $item ) {
		$reordered[] = $item;

		if ( ! $inserted && isset( $item[2] ) && 'edit.php?post_type=relay_event' === $item[2] ) {
			$reordered[] = $pending_item;
			$inserted    = true;
		}
	}

	if ( ! $inserted ) {
		array_unshift( $reordered, $pending_item );
	}

	$submenu[ $menu_key ] = $reordered;
}
add_action( 'admin_menu', 'tharchive_reorder_relay_event_submenu', 99 );

/**
 * 让列表页支持按投稿来源筛选。
 *
 * @param array $query_vars 查询变量。
 * @return array
 */
function tharchive_register_submission_channel_query_var( $query_vars ) {
	$query_vars[] = 'tharchive_submission_channel';

	return $query_vars;
}
add_filter( 'query_vars', 'tharchive_register_submission_channel_query_var' );

/**
 * 将待审核投稿筛选映射到后台查询。
 *
 * @param WP_Query $query 查询对象。
 * @return void
 */
function tharchive_filter_admin_submission_queue( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	global $pagenow;

	if ( 'edit.php' !== $pagenow ) {
		return;
	}

	if ( 'relay_event' !== $query->get( 'post_type' ) ) {
		return;
	}

	$submission_channel = $query->get( 'tharchive_submission_channel' );
	if ( 'front_submission' !== $submission_channel ) {
		return;
	}

	$query->set(
		'meta_query',
		array(
			array(
				'key'   => '_tharchive_submission_channel',
				'value' => 'front_submission',
			),
		)
	);
}
add_action( 'pre_get_posts', 'tharchive_filter_admin_submission_queue' );

/**
 * 在文章状态旁显示“前台投稿待审核”。
 *
 * @param string[] $states 现有状态文案。
 * @param WP_Post  $post   当前文章。
 * @return string[]
 */
function tharchive_display_front_submission_state( $states, $post ) {
	if ( 'relay_event' !== $post->post_type || 'pending' !== $post->post_status ) {
		return $states;
	}

	if ( ! tharchive_is_front_submission_event( $post->ID ) ) {
		return $states;
	}

	$states['tharchive_front_submission'] = '前台投稿待审核';

	return $states;
}
add_filter( 'display_post_states', 'tharchive_display_front_submission_state', 10, 2 );

/**
 * 扩充后台列表列。
 *
 * @param array $columns 现有列。
 * @return array
 */
function tharchive_add_submission_review_column( $columns ) {
	$ordered = array();

	if ( isset( $columns['cb'] ) ) {
		$ordered['cb'] = $columns['cb'];
		unset( $columns['cb'] );
	}

	if ( isset( $columns['title'] ) ) {
		$ordered['title'] = $columns['title'];
		unset( $columns['title'] );
	}

	$ordered['tharchive_review_status'] = '审核状态';

	foreach ( $columns as $key => $label ) {
		$ordered[ $key ] = $label;
	}

	return $ordered;
}
add_filter( 'manage_relay_event_posts_columns', 'tharchive_add_submission_review_column' );

/**
 * 渲染后台列表中的审核状态列。
 *
 * @param string $column  列 key。
 * @param int    $post_id 文章 ID。
 * @return void
 */
function tharchive_render_submission_review_column( $column, $post_id ) {
	if ( 'tharchive_review_status' !== $column ) {
		return;
	}

	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post ) {
		echo '—';
		return;
	}

	if ( tharchive_is_front_submission_event( $post_id ) ) {
		echo '<strong>前台投稿</strong><br>';

		if ( 'pending' === $post->post_status ) {
			echo '<span style="color:#b32d2e;">等待审核</span><br>';
			$review_url = wp_nonce_url(
				admin_url( 'admin-post.php?action=tharchive_publish_submission&post_id=' . $post_id ),
				'tharchive_publish_submission_' . $post_id
			);
			echo '<a href="' . esc_url( $review_url ) . '">一键通过并发布</a>';
			return;
		}

		echo '<span style="color:#2271b1;">已处理</span>';
		return;
	}

	echo '后台创建';
}
add_action( 'manage_relay_event_posts_custom_column', 'tharchive_render_submission_review_column', 10, 2 );

/**
 * 在编辑页添加投稿审核侧栏。
 */
function tharchive_add_submission_review_meta_box() {
	add_meta_box(
		'tharchive_submission_review',
		'投稿审核',
		'tharchive_render_submission_review_meta_box',
		'relay_event',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'tharchive_add_submission_review_meta_box' );

/**
 * 渲染投稿审核侧栏。
 *
 * @param WP_Post $post 当前文章。
 * @return void
 */
function tharchive_render_submission_review_meta_box( $post ) {
	if ( ! tharchive_is_front_submission_event( $post->ID ) ) {
		echo '<p>这篇活动不是前台投稿创建的，无需走投稿审核流程。</p>';
		return;
	}

	$created_at = (string) get_post_meta( $post->ID, '_tharchive_submission_created_at', true );

	echo '<p><strong>来源：</strong>前台投稿表单</p>';

	if ( '' !== $created_at ) {
		echo '<p><strong>提交时间：</strong>' . esc_html( $created_at ) . '</p>';
	}

	if ( 'pending' === $post->post_status ) {
		$publish_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=tharchive_publish_submission&post_id=' . $post->ID ),
			'tharchive_publish_submission_' . $post->ID
		);

		echo '<p>建议流程：先补齐分类、标签、时间和归档链接，再点击下面按钮直接发布。</p>';
		echo '<p><a class="button button-primary button-large" href="' . esc_url( $publish_url ) . '">通过审核并发布</a></p>';
		echo '<p><a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=relay_event&post_status=pending&tharchive_submission_channel=front_submission' ) ) . '">返回待审核列表</a></p>';
		return;
	}

	echo '<p>当前状态：已不在待审核队列中。</p>';
}

/**
 * 编辑页顶部提示当前审核步骤。
 *
 * @return void
 */
function tharchive_render_submission_review_admin_notice() {
	$screen = get_current_screen();

	if ( ! $screen || 'relay_event' !== $screen->post_type || 'post' !== $screen->base ) {
		return;
	}

	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	if ( $post_id <= 0 || ! tharchive_is_front_submission_event( $post_id ) ) {
		return;
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || 'pending' !== $post->post_status ) {
		return;
	}

	echo '<div class="notice notice-warning"><p><strong>这是一篇前台投稿，当前仍处于待审核状态。</strong> 建议你先检查封面、时间、分类和链接，再使用右侧“投稿审核”里的“一键通过并发布”。</p></div>';
}
add_action( 'admin_notices', 'tharchive_render_submission_review_admin_notice' );

/**
 * 处理一键通过并发布。
 *
 * @return void
 */
function tharchive_publish_submission_admin_action() {
	$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

	if ( $post_id <= 0 ) {
		wp_die( '缺少文章 ID。' );
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( '你没有权限审核这篇投稿。' );
	}

	check_admin_referer( 'tharchive_publish_submission_' . $post_id );

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || 'relay_event' !== $post->post_type ) {
		wp_die( '未找到对应活动。' );
	}

	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'publish',
		)
	);

	wp_safe_redirect(
		add_query_arg(
			array(
				'post'                    => $post_id,
				'action'                  => 'edit',
				'tharchive_review_result' => 'published',
			),
			admin_url( 'post.php' )
		)
	);
	exit;
}
add_action( 'admin_post_tharchive_publish_submission', 'tharchive_publish_submission_admin_action' );

/**
 * 发布成功后的后台提示。
 *
 * @return void
 */
function tharchive_render_submission_review_result_notice() {
	if ( ! is_admin() ) {
		return;
	}

	if ( ! isset( $_GET['tharchive_review_result'] ) ) {
		return;
	}

	if ( 'published' !== sanitize_key( wp_unslash( $_GET['tharchive_review_result'] ) ) ) {
		return;
	}

	echo '<div class="notice notice-success is-dismissible"><p>这篇前台投稿已通过审核并发布。</p></div>';
}
add_action( 'admin_notices', 'tharchive_render_submission_review_result_notice' );
