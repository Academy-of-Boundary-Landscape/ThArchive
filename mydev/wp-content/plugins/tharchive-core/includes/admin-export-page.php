<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 注册 Elementor 旧文导出入口。
 *
 * @return void
 */
function tharchive_register_elementor_export_submenu() {
	add_submenu_page(
		'edit.php?post_type=relay_event',
		'导出 Elementor 旧文',
		'导出旧文 JSON',
		'manage_options',
		'tharchive-elementor-export',
		'tharchive_render_elementor_export_page'
	);
}
add_action( 'admin_menu', 'tharchive_register_elementor_export_submenu' );

/**
 * 调整活动归档子菜单顺序，让导出工具靠前。
 *
 * @return void
 */
function tharchive_reorder_elementor_export_submenu() {
	global $submenu;

	$menu_key = 'edit.php?post_type=relay_event';

	if ( empty( $submenu[ $menu_key ] ) || ! is_array( $submenu[ $menu_key ] ) ) {
		return;
	}

	$export_item  = null;
	$pending_item = null;
	$other_items  = array();

	foreach ( $submenu[ $menu_key ] as $item ) {
		if ( ! isset( $item[2] ) ) {
			$other_items[] = $item;
			continue;
		}

		if ( 'tharchive-elementor-export' === $item[2] ) {
			$export_item = $item;
			continue;
		}

		if ( false !== strpos( $item[2], 'tharchive_submission_channel=front_submission' ) ) {
			$pending_item = $item;
			continue;
		}

		$other_items[] = $item;
	}

	if ( null === $export_item ) {
		return;
	}

	$reordered = array();
	$inserted  = false;

	foreach ( $other_items as $item ) {
		$reordered[] = $item;

		if ( ! $inserted && isset( $item[2] ) && 'edit.php?post_type=relay_event' === $item[2] ) {
			if ( null !== $pending_item ) {
				$reordered[] = $pending_item;
			}

			$reordered[] = $export_item;
			$inserted    = true;
		}
	}

	if ( ! $inserted ) {
		if ( null !== $pending_item ) {
			$reordered[] = $pending_item;
		}

		$reordered[] = $export_item;
	}

	$submenu[ $menu_key ] = $reordered;
}
add_action( 'admin_menu', 'tharchive_reorder_elementor_export_submenu', 100 );

/**
 * 构造当前筛选的导出下载链接。
 *
 * @param array $filters 筛选项。
 * @return string
 */
function tharchive_get_elementor_export_download_url( $filters ) {
	return wp_nonce_url(
		add_query_arg(
			array(
				'action'                => 'tharchive_export_elementor_posts',
				'post_types'            => $filters['post_types'],
				'post_statuses'         => $filters['post_statuses'],
				'limit'                 => $filters['limit'],
				'offset'                => $filters['offset'],
				'include_raw_elementor' => $filters['include_raw_elementor'],
			),
			admin_url( 'admin-post.php' )
		),
		'tharchive_export_elementor_posts'
	);
}

/**
 * 渲染文章预览表格。
 *
 * @param WP_Post[] $posts 当前批次文章。
 * @return void
 */
function tharchive_render_elementor_export_post_preview( $posts ) {
	if ( empty( $posts ) ) {
		echo '<p>当前批次没有命中任何 Elementor 文章。</p>';
		return;
	}
	?>
	<table class="widefat striped">
		<thead>
			<tr>
				<th>ID</th>
				<th>标题</th>
				<th>类型</th>
				<th>状态</th>
				<th>日期</th>
				<th>链接</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $posts as $post ) : ?>
				<tr>
					<td><?php echo esc_html( (string) $post->ID ); ?></td>
					<td>
						<strong><?php echo esc_html( get_the_title( $post ) ?: '(无标题)' ); ?></strong>
						<?php if ( ! empty( $post->post_excerpt ) ) : ?>
							<div style="color:#646970;margin-top:4px;"><?php echo esc_html( wp_html_excerpt( $post->post_excerpt, 90, '…' ) ); ?></div>
						<?php endif; ?>
					</td>
					<td><code><?php echo esc_html( $post->post_type ); ?></code></td>
					<td><code><?php echo esc_html( $post->post_status ); ?></code></td>
					<td><?php echo esc_html( mysql2date( 'Y-m-d H:i', $post->post_date ) ); ?></td>
					<td><a href="<?php echo esc_url( get_permalink( $post ) ); ?>" target="_blank" rel="noreferrer noopener">查看原文</a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * 渲染 JSON 预览。
 *
 * @param array $items 导出记录。
 * @return void
 */
function tharchive_render_elementor_export_json_preview( $items ) {
	if ( empty( $items ) ) {
		echo '<p>当前批次没有可预览的 JSON。</p>';
		return;
	}

	$preview_items = array_slice( $items, 0, 2 );
	$preview_json  = wp_json_encode(
		array(
			'meta'  => array(
				'preview_count' => count( $preview_items ),
			),
			'items' => $preview_items,
		),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	);
	?>
	<p style="margin-top:0;color:#50575e;">这里只预览前 2 条，方便确认字段结构；完整数据请点上面的下载按钮。</p>
	<textarea readonly="readonly" style="width:100%;min-height:320px;font-family:SFMono-Regular,Consolas,Monaco,monospace;font-size:12px;line-height:1.5;"><?php echo esc_textarea( $preview_json ); ?></textarea>
	<?php
}

/**
 * 渲染 Elementor 旧文导出页面。
 *
 * @return void
 */
function tharchive_render_elementor_export_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '你没有权限使用这个导出工具。' );
	}

	$filters          = tharchive_sanitize_elementor_export_request( $_GET );
	$total_candidates = tharchive_count_elementor_export_posts( $filters );
	$post_types       = tharchive_get_elementor_export_post_type_choices();
	$post_statuses    = get_post_stati(
		array(
			'internal' => false,
		),
		'objects'
	);
	$download_url     = tharchive_get_elementor_export_download_url( $filters );
	$preview_posts    = tharchive_get_elementor_export_posts( $filters );
	$preview_items    = tharchive_prepare_elementor_export_records( $preview_posts, ! empty( $filters['include_raw_elementor'] ) );
	?>
	<div class="wrap">
		<h1>导出 Elementor 旧文 JSON</h1>
		<p>这个工具只读读取 WordPress 文章和 Elementor 元数据，不会写入数据库。适合先把旧页面导出成结构化 JSON，再交给后续的 LLM 清洗与字段抽取流程。</p>

		<div style="margin:16px 0;padding:12px 16px;border:1px solid #ccd0d4;background:#fff;">
			<p style="margin:0 0 6px;"><strong>当前筛选命中：</strong><?php echo esc_html( (string) $total_candidates ); ?> 篇 Elementor 文章</p>
			<p style="margin:0;color:#50575e;">建议先按 20-50 篇一批导出，确认 JSON 结构符合预期后，再继续下一批。</p>
		</div>

		<form method="get" action="">
			<input type="hidden" name="post_type" value="relay_event">
			<input type="hidden" name="page" value="tharchive-elementor-export">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">文章类型</th>
						<td>
							<?php foreach ( $post_types as $post_type_key => $post_type_obj ) : ?>
								<label style="display:inline-block;min-width:140px;margin:0 16px 8px 0;">
									<input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $post_type_key ); ?>" <?php checked( in_array( $post_type_key, $filters['post_types'], true ) ); ?>>
									<?php echo esc_html( $post_type_obj->labels->singular_name ); ?> <code><?php echo esc_html( $post_type_key ); ?></code>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">文章状态</th>
						<td>
							<?php foreach ( $post_statuses as $status_key => $status_obj ) : ?>
								<label style="display:inline-block;min-width:120px;margin:0 16px 8px 0;">
									<input type="checkbox" name="post_statuses[]" value="<?php echo esc_attr( $status_key ); ?>" <?php checked( in_array( $status_key, $filters['post_statuses'], true ) ); ?>>
									<?php echo esc_html( $status_obj->label ); ?> <code><?php echo esc_html( $status_key ); ?></code>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tharchive-export-limit">每批导出数量</label></th>
						<td>
							<input id="tharchive-export-limit" type="number" name="limit" min="1" max="500" value="<?php echo esc_attr( (string) $filters['limit'] ); ?>" class="small-text">
							<p class="description">建议 20-50。最大 500。</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tharchive-export-offset">偏移量</label></th>
						<td>
							<input id="tharchive-export-offset" type="number" name="offset" min="0" value="<?php echo esc_attr( (string) $filters['offset'] ); ?>" class="small-text">
							<p class="description">例如上一批导出了 50 篇，这里就填 50。</p>
						</td>
					</tr>
					<tr>
						<th scope="row">附带原始 Elementor JSON</th>
						<td>
							<label>
								<input type="checkbox" name="include_raw_elementor" value="1" <?php checked( ! empty( $filters['include_raw_elementor'] ) ); ?>>
								同时导出 <code>_elementor_data</code> 和 <code>_elementor_page_settings</code>
							</label>
							<p class="description">默认关闭，避免导出文件过大。只有在你确实需要回查原始组件树时再开启。</p>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit">
				<button type="submit" class="button">更新命中范围</button>
				<a class="button button-primary" href="<?php echo esc_url( $download_url ); ?>">下载这一批 JSON</a>
			</p>
		</form>

		<hr style="margin:24px 0;">

		<h2>文章预览</h2>
		<?php tharchive_render_elementor_export_post_preview( $preview_posts ); ?>

		<hr style="margin:24px 0;">

		<h2>JSON 预览</h2>
		<?php tharchive_render_elementor_export_json_preview( $preview_items ); ?>
	</div>
	<?php
}

/**
 * 处理后台导出请求。
 *
 * @return void
 */
function tharchive_handle_elementor_export_download() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '你没有权限导出这些内容。' );
	}

	check_admin_referer( 'tharchive_export_elementor_posts' );

	$filters = tharchive_sanitize_elementor_export_request( $_GET );
	$posts   = tharchive_get_elementor_export_posts( $filters );
	$items   = tharchive_prepare_elementor_export_records( $posts, ! empty( $filters['include_raw_elementor'] ) );

	$payload = array(
		'meta'  => array(
			'site_url'            => home_url( '/' ),
			'exported_at'         => current_time( 'mysql' ),
			'exporter'            => 'tharchive-core',
			'format_version'      => 1,
			'filters'             => $filters,
			'exported_count'      => count( $items ),
			'has_more_candidates' => tharchive_count_elementor_export_posts( $filters ) > ( $filters['offset'] + count( $items ) ),
		),
		'items' => $items,
	);

	$filename = sprintf(
		'tharchive-elementor-export-%s-%d-%d.json',
		wp_date( 'Ymd-His' ),
		(int) $filters['offset'],
		(int) count( $items )
	);

	nocache_headers();
	header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
	header( 'Content-Disposition: attachment; filename=' . $filename );

	echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	exit;
}
add_action( 'admin_post_tharchive_export_elementor_posts', 'tharchive_handle_elementor_export_download' );
