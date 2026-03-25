<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 注册 relay_event JSON 导入入口。
 *
 * @return void
 */
function tharchive_register_import_submenu() {
	add_submenu_page(
		'edit.php?post_type=relay_event',
		'导入 relay_event JSON',
		'导入 JSON',
		'manage_options',
		'tharchive-relay-import',
		'tharchive_render_import_page'
	);

	$count = tharchive_count_posts_missing_required_fields();
	$title = '待补关键字段';

	if ( $count > 0 ) {
		$title .= ' <span class="awaiting-mod count-' . $count . '"><span class="pending-count">' . $count . '</span></span>';
	}

	add_submenu_page(
		'edit.php?post_type=relay_event',
		'待补关键字段',
		$title,
		'edit_posts',
		'edit.php?post_type=relay_event&tharchive_missing_required_fields=1'
	);
}
add_action( 'admin_menu', 'tharchive_register_import_submenu' );

/**
 * 注册后台筛选 query var。
 *
 * @param array $query_vars 原始变量。
 * @return array
 */
function tharchive_register_missing_required_fields_query_var( $query_vars ) {
	$query_vars[] = 'tharchive_missing_required_fields';

	return $query_vars;
}
add_filter( 'query_vars', 'tharchive_register_missing_required_fields_query_var' );

/**
 * 调整子菜单顺序。
 *
 * @return void
 */
function tharchive_reorder_import_submenus() {
	global $submenu;

	$menu_key = 'edit.php?post_type=relay_event';

	if ( empty( $submenu[ $menu_key ] ) || ! is_array( $submenu[ $menu_key ] ) ) {
		return;
	}

	$priority_slugs = array(
		'edit.php?post_type=relay_event',
		'edit.php?post_type=relay_event&post_status=pending&tharchive_submission_channel=front_submission',
		'edit.php?post_type=relay_event&tharchive_missing_required_fields=1',
		'tharchive-elementor-export',
		'tharchive-relay-import',
	);

	$indexed = array();
	$others  = array();

	foreach ( $submenu[ $menu_key ] as $item ) {
		$slug = isset( $item[2] ) ? $item[2] : '';
		if ( in_array( $slug, $priority_slugs, true ) ) {
			$indexed[ $slug ] = $item;
			continue;
		}

		$others[] = $item;
	}

	$reordered = array();

	foreach ( $priority_slugs as $slug ) {
		if ( isset( $indexed[ $slug ] ) ) {
			$reordered[] = $indexed[ $slug ];
		}
	}

	$submenu[ $menu_key ] = array_merge( $reordered, $others );
}
add_action( 'admin_menu', 'tharchive_reorder_import_submenus', 110 );

/**
 * 获取 relay_event 缺失的关键字段。
 *
 * @param int $post_id 文章 ID。
 * @return array<int, string>
 */
function tharchive_get_relay_event_missing_required_fields( $post_id ) {
	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post || 'relay_event' !== $post->post_type ) {
		return array();
	}

	$missing = array();

	if ( '' === trim( $post->post_title ) ) {
		$missing[] = 'title';
	}

	if ( '' === trim( $post->post_excerpt ) ) {
		$missing[] = 'excerpt';
	}

	if ( '' === trim( wp_strip_all_tags( (string) $post->post_content ) ) ) {
		$missing[] = 'content';
	}

	$characters = get_the_terms( $post, 'touhou_character' );
	if ( empty( $characters ) || is_wp_error( $characters ) ) {
		$missing[] = 'character';
	}

	$organizers = get_the_terms( $post, 'organizer' );
	if ( empty( $organizers ) || is_wp_error( $organizers ) ) {
		$missing[] = 'organizer';
	}

	if ( '' === trim( (string) get_post_meta( $post_id, 'event_date', true ) ) ) {
		$missing[] = 'event_date';
	}

	if ( ! has_post_thumbnail( $post_id ) ) {
		$missing[] = 'cover_image';
	}

	return $missing;
}

/**
 * 更新 relay_event 缺失字段缓存。
 *
 * @param int $post_id 文章 ID。
 * @return array<int, string>
 */
function tharchive_update_relay_event_missing_required_fields_meta( $post_id ) {
	$missing = tharchive_get_relay_event_missing_required_fields( $post_id );
	update_post_meta( $post_id, '_tharchive_missing_required_fields', $missing );
	update_post_meta( $post_id, '_tharchive_missing_required_fields_count', count( $missing ) );

	return $missing;
}

/**
 * 在保存 relay_event 后刷新缺失字段缓存。
 *
 * @param int     $post_id 文章 ID。
 * @param WP_Post $post    文章对象。
 * @return void
 */
function tharchive_refresh_missing_fields_on_save( $post_id, $post ) {
	if ( ! $post instanceof WP_Post || 'relay_event' !== $post->post_type ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	tharchive_update_relay_event_missing_required_fields_meta( $post_id );
}
add_action( 'save_post_relay_event', 'tharchive_refresh_missing_fields_on_save', 20, 2 );

/**
 * 查询缺失关键字段的 relay_event 数量。
 *
 * @return int
 */
function tharchive_count_posts_missing_required_fields() {
	$query = new WP_Query(
		array(
			'post_type'              => 'relay_event',
			'post_status'            => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				array(
					'key'     => '_tharchive_missing_required_fields_count',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
		)
	);

	return (int) $query->found_posts;
}

/**
 * 将缺失字段筛选映射到后台列表查询。
 *
 * @param WP_Query $query 查询对象。
 * @return void
 */
function tharchive_filter_admin_missing_required_fields_queue( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	global $pagenow;

	if ( 'edit.php' !== $pagenow || 'relay_event' !== $query->get( 'post_type' ) ) {
		return;
	}

	if ( '1' !== (string) $query->get( 'tharchive_missing_required_fields' ) ) {
		return;
	}

	$query->set(
		'meta_query',
		array(
			array(
				'key'     => '_tharchive_missing_required_fields_count',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'NUMERIC',
			),
		)
	);
}
add_action( 'pre_get_posts', 'tharchive_filter_admin_missing_required_fields_queue' );

/**
 * 为 relay_event 列表增加“关键字段状态”筛选控件。
 *
 * @param string $post_type 当前文章类型。
 * @param string $which     位置。
 * @return void
 */
function tharchive_render_missing_required_fields_filter( $post_type, $which ) {
	if ( 'relay_event' !== $post_type || 'top' !== $which ) {
		return;
	}

	$current = isset( $_GET['tharchive_missing_required_fields'] ) ? sanitize_text_field( wp_unslash( $_GET['tharchive_missing_required_fields'] ) ) : '';
	?>
	<label for="filter-by-tharchive-missing-fields" class="screen-reader-text">按关键字段状态筛选</label>
	<select name="tharchive_missing_required_fields" id="filter-by-tharchive-missing-fields">
		<option value="">全部关键字段状态</option>
		<option value="1" <?php selected( $current, '1' ); ?>>只看缺主要信息</option>
	</select>
	<?php
}
add_action( 'restrict_manage_posts', 'tharchive_render_missing_required_fields_filter', 20, 2 );

/**
 * 为 relay_event 列表增加上方视图链接。
 *
 * @param array $views 原始视图链接。
 * @return array
 */
function tharchive_add_missing_required_fields_view_link( $views ) {
	global $typenow;

	if ( 'relay_event' !== $typenow ) {
		return $views;
	}

	$count      = tharchive_count_posts_missing_required_fields();
	$is_current = isset( $_GET['tharchive_missing_required_fields'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['tharchive_missing_required_fields'] ) );

	$url = add_query_arg(
		array(
			'post_type'                         => 'relay_event',
			'tharchive_missing_required_fields' => '1',
		),
		admin_url( 'edit.php' )
	);

	$label = '缺主要信息';
	if ( $count > 0 ) {
		$label .= ' <span class="count">(' . intval( $count ) . ')</span>';
	}

	$views['tharchive_missing_required_fields'] = sprintf(
		'<a href="%1$s"%2$s>%3$s</a>',
		esc_url( $url ),
		$is_current ? ' class="current" aria-current="page"' : '',
		$label
	);

	return $views;
}
add_filter( 'views_edit-relay_event', 'tharchive_add_missing_required_fields_view_link' );

/**
 * 为状态列增加缺失关键信息提示。
 *
 * @param string[] $states 状态标签。
 * @param WP_Post  $post   当前文章。
 * @return string[]
 */
function tharchive_display_missing_required_fields_state( $states, $post ) {
	if ( 'relay_event' !== $post->post_type ) {
		return $states;
	}

	$count = (int) get_post_meta( $post->ID, '_tharchive_missing_required_fields_count', true );
	if ( $count > 0 ) {
		$states['tharchive_missing_required_fields'] = '缺少关键字段';
	}

	return $states;
}
add_filter( 'display_post_states', 'tharchive_display_missing_required_fields_state', 20, 2 );

/**
 * 为活动列表增加缺失字段列。
 *
 * @param array $columns 原始列。
 * @return array
 */
function tharchive_add_missing_required_fields_column( $columns ) {
	$ordered = array();

	foreach ( $columns as $key => $label ) {
		$ordered[ $key ] = $label;

		if ( 'tharchive_review_status' === $key ) {
			$ordered['tharchive_missing_fields'] = '待补字段';
		}
	}

	if ( ! isset( $ordered['tharchive_missing_fields'] ) ) {
		$ordered['tharchive_missing_fields'] = '待补字段';
	}

	return $ordered;
}
add_filter( 'manage_relay_event_posts_columns', 'tharchive_add_missing_required_fields_column', 20 );

/**
 * 渲染缺失字段列。
 *
 * @param string $column  列名。
 * @param int    $post_id 文章 ID。
 * @return void
 */
function tharchive_render_missing_required_fields_column( $column, $post_id ) {
	if ( 'tharchive_missing_fields' !== $column ) {
		return;
	}

	$missing = get_post_meta( $post_id, '_tharchive_missing_required_fields', true );
	$missing = is_array( $missing ) ? array_values( array_filter( $missing ) ) : array();

	if ( empty( $missing ) ) {
		echo '<span style="color:#2271b1;">已齐全</span>';
		return;
	}

	$labels = array(
		'title'       => '标题',
		'excerpt'     => '简介',
		'content'     => '说明',
		'character'   => '角色',
		'organizer'   => '主办方',
		'event_date'  => '日期',
		'cover_image' => '封面图',
	);

	$display = array();
	foreach ( $missing as $key ) {
		$display[] = isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
	}

	echo '<span style="color:#b32d2e;">' . esc_html( implode( ' / ', $display ) ) . '</span>';
}
add_action( 'manage_relay_event_posts_custom_column', 'tharchive_render_missing_required_fields_column', 10, 2 );

/**
 * 在编辑页增加缺失字段提示侧栏。
 */
function tharchive_add_missing_fields_meta_box() {
	add_meta_box(
		'tharchive_missing_fields',
		'关键字段补全',
		'tharchive_render_missing_fields_meta_box',
		'relay_event',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'tharchive_add_missing_fields_meta_box' );

/**
 * 渲染缺失字段提示侧栏。
 *
 * @param WP_Post $post 当前文章。
 * @return void
 */
function tharchive_render_missing_fields_meta_box( $post ) {
	$missing = tharchive_get_relay_event_missing_required_fields( $post->ID );

	if ( empty( $missing ) ) {
		echo '<p>当前这篇活动的关键字段已经补齐。</p>';
		return;
	}

	$labels = array(
		'title'       => '标题',
		'excerpt'     => '一句话简介',
		'content'     => '活动说明正文',
		'character'   => '东方角色',
		'organizer'   => '主办方',
		'event_date'  => '活动日期',
		'cover_image' => '活动封面图',
	);

	echo '<p>这篇活动还缺少以下关键字段，建议优先补齐后再发布或继续整理：</p><ul style="margin-left:18px;list-style:disc;">';

	foreach ( $missing as $key ) {
		echo '<li>' . esc_html( isset( $labels[ $key ] ) ? $labels[ $key ] : $key ) . '</li>';
	}

	echo '</ul>';
}

/**
 * 从导入记录中读取值。
 *
 * @param array  $record JSON 记录。
 * @param string $key    字段名。
 * @return string
 */
function tharchive_get_import_record_string( $record, $key ) {
	if ( ! isset( $record[ $key ] ) ) {
		return '';
	}

	return trim( (string) $record[ $key ] );
}

/**
 * 获取或创建 taxonomy term ID。
 *
 * @param string $term_name 词条名。
 * @param string $taxonomy  taxonomy。
 * @return int
 */
function tharchive_get_or_create_import_term_id( $term_name, $taxonomy ) {
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
 * 从 URL 或附件 ID 设置封面图。
 *
 * @param int    $post_id     文章 ID。
 * @param string $cover_value 封面值。
 * @return int
 */
function tharchive_import_cover_image( $post_id, $cover_value ) {
	$cover_value = trim( $cover_value );

	if ( '' === $cover_value ) {
		return 0;
	}

	if ( ctype_digit( $cover_value ) ) {
		$attachment_id = absint( $cover_value );
		if ( $attachment_id > 0 ) {
			set_post_thumbnail( $post_id, $attachment_id );
			return $attachment_id;
		}
	}

	if ( ! filter_var( $cover_value, FILTER_VALIDATE_URL ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$temp_file = download_url( $cover_value );
	if ( is_wp_error( $temp_file ) ) {
		return 0;
	}

	$file_array = array(
		'name'     => wp_basename( wp_parse_url( $cover_value, PHP_URL_PATH ) ?: 'cover-image' ),
		'tmp_name' => $temp_file,
	);

	$attachment_id = media_handle_sideload( $file_array, $post_id );

	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $temp_file );
		return 0;
	}

	set_post_thumbnail( $post_id, $attachment_id );

	return (int) $attachment_id;
}

/**
 * 导入单条 JSON 记录。
 *
 * @param array  $record      JSON 记录。
 * @param string $post_status 导入后的文章状态。
 * @return array<string, mixed>
 */
function tharchive_import_single_relay_event_record( $record, $post_status ) {
	$title                = tharchive_get_import_record_string( $record, 'title' );
	$excerpt              = tharchive_get_import_record_string( $record, 'excerpt' );
	$content              = tharchive_get_import_record_string( $record, 'content' );
	$character            = tharchive_get_import_record_string( $record, 'character' );
	$organizer            = tharchive_get_import_record_string( $record, 'organizer' );
	$event_date           = tharchive_get_import_record_string( $record, 'event_date' );
	$cover_image          = tharchive_get_import_record_string( $record, 'cover_image' );
	$bilibili_summary_url = tharchive_get_import_record_string( $record, 'bilibili_summary_url' );

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'relay_event',
			'post_status'  => $post_status,
			'post_title'   => $title,
			'post_excerpt' => $excerpt,
			'post_content' => $content,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return array(
			'success' => false,
			'error'   => $post_id->get_error_message(),
			'title'   => $title,
		);
	}

	if ( '' !== $character ) {
		$character_id = tharchive_get_or_create_import_term_id( $character, 'touhou_character' );
		if ( $character_id > 0 ) {
			wp_set_post_terms( $post_id, array( $character_id ), 'touhou_character', false );
		}
	}

	if ( '' !== $organizer ) {
		$organizer_id = tharchive_get_or_create_import_term_id( $organizer, 'organizer' );
		if ( $organizer_id > 0 ) {
			wp_set_post_terms( $post_id, array( $organizer_id ), 'organizer', false );
		}
	}

	if ( '' !== $event_date ) {
		update_post_meta( $post_id, 'event_date', sanitize_text_field( $event_date ) );
		if ( preg_match( '/^(\d{4})-\d{2}-\d{2}$/', $event_date, $matches ) ) {
			update_post_meta( $post_id, 'event_year', absint( $matches[1] ) );
		}
	}

	if ( '' !== $bilibili_summary_url ) {
		update_post_meta( $post_id, 'bilibili_summary_url', esc_url_raw( $bilibili_summary_url ) );
	}

	$attachment_id = tharchive_import_cover_image( $post_id, $cover_image );

	update_post_meta( $post_id, '_tharchive_import_source', 'json_import' );
	update_post_meta( $post_id, '_tharchive_imported_at', current_time( 'mysql' ) );
	update_post_meta( $post_id, '_tharchive_import_cover_value', $cover_image );
	update_post_meta( $post_id, '_tharchive_import_attachment_id', $attachment_id );

	$missing = tharchive_update_relay_event_missing_required_fields_meta( $post_id );

	return array(
		'success'        => true,
		'post_id'        => $post_id,
		'title'          => $title,
		'missing_fields' => $missing,
	);
}

/**
 * 解析上传或粘贴的 JSON。
 *
 * @return array<int, array<string, mixed>>
 */
function tharchive_parse_import_payload() {
	$raw_json = '';

	if ( ! empty( $_FILES['tharchive_import_file']['tmp_name'] ) && UPLOAD_ERR_OK === (int) $_FILES['tharchive_import_file']['error'] ) {
		$raw_json = file_get_contents( $_FILES['tharchive_import_file']['tmp_name'] );
	}

	if ( '' === trim( $raw_json ) && isset( $_POST['tharchive_import_json'] ) ) {
		$raw_json = wp_unslash( $_POST['tharchive_import_json'] );
	}

	$data = json_decode( (string) $raw_json, true );

	if ( ! is_array( $data ) ) {
		return array();
	}

	if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
		$data = $data['items'];
	}

	return array_values(
		array_filter(
			$data,
			function( $item ) {
				return is_array( $item );
			}
		)
	);
}

/**
 * 渲染导入工具页。
 *
 * @return void
 */
function tharchive_render_import_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '你没有权限使用这个导入工具。' );
	}
	?>
	<div class="wrap">
		<h1>导入 relay_event JSON</h1>
		<p>这个工具使用宽松模式导入活动。缺失字段不会阻止创建，但会在后台标记为“待补关键字段”，方便后续集中补录。</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<?php wp_nonce_field( 'tharchive_import_relay_events' ); ?>
			<input type="hidden" name="action" value="tharchive_import_relay_events">

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="tharchive-import-status">导入后的文章状态</label></th>
						<td>
							<select id="tharchive-import-status" name="tharchive_import_post_status">
								<option value="draft">draft（推荐，先补字段）</option>
								<option value="publish">publish</option>
								<option value="pending">pending</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tharchive-import-file">上传 JSON 文件</label></th>
						<td>
							<input id="tharchive-import-file" type="file" name="tharchive_import_file" accept=".json,application/json">
							<p class="description">支持直接上传 JSON 文件；如果同时填写下面的文本框，会优先使用上传文件。</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tharchive-import-json">或粘贴 JSON</label></th>
						<td>
							<textarea id="tharchive-import-json" name="tharchive_import_json" rows="18" class="large-text code" placeholder='[{"title":"活动标题","excerpt":"一句话简介","content":"正文","character":"角色","organizer":"","event_date":"2026-03-25","cover_image":"","bilibili_summary_url":""}]'></textarea>
							<p class="description">建议字段使用：title / excerpt / content / character / organizer / event_date / cover_image。可选补充：bilibili_summary_url。</p>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary">开始导入</button>
			</p>
		</form>
	</div>
	<?php
}

/**
 * 处理批量导入动作。
 *
 * @return void
 */
function tharchive_handle_relay_event_import() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '你没有权限执行这个导入操作。' );
	}

	check_admin_referer( 'tharchive_import_relay_events' );

	$post_status = isset( $_POST['tharchive_import_post_status'] ) ? sanitize_key( wp_unslash( $_POST['tharchive_import_post_status'] ) ) : 'draft';
	if ( ! in_array( $post_status, array( 'draft', 'publish', 'pending' ), true ) ) {
		$post_status = 'draft';
	}

	$records = tharchive_parse_import_payload();

	if ( empty( $records ) ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type'                 => 'relay_event',
					'page'                      => 'tharchive-relay-import',
					'tharchive_import_result'   => 'empty',
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	$imported      = 0;
	$with_missing  = 0;
	$errors        = 0;

	foreach ( $records as $record ) {
		$result = tharchive_import_single_relay_event_record( $record, $post_status );

		if ( empty( $result['success'] ) ) {
			++$errors;
			continue;
		}

		++$imported;

		if ( ! empty( $result['missing_fields'] ) ) {
			++$with_missing;
		}
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'post_type'                   => 'relay_event',
				'page'                        => 'tharchive-relay-import',
				'tharchive_import_result'     => 'done',
				'tharchive_imported'          => $imported,
				'tharchive_imported_missing'  => $with_missing,
				'tharchive_import_errors'     => $errors,
			),
			admin_url( 'edit.php' )
		)
	);
	exit;
}
add_action( 'admin_post_tharchive_import_relay_events', 'tharchive_handle_relay_event_import' );

/**
 * 显示导入结果提示。
 *
 * @return void
 */
function tharchive_render_import_result_notice() {
	if ( ! is_admin() || empty( $_GET['page'] ) || 'tharchive-relay-import' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
		return;
	}

	if ( empty( $_GET['tharchive_import_result'] ) ) {
		return;
	}

	$result = sanitize_key( wp_unslash( $_GET['tharchive_import_result'] ) );

	if ( 'empty' === $result ) {
		echo '<div class="notice notice-warning"><p>没有解析到可导入的 JSON 记录。</p></div>';
		return;
	}

	if ( 'done' !== $result ) {
		return;
	}

	$imported     = isset( $_GET['tharchive_imported'] ) ? absint( $_GET['tharchive_imported'] ) : 0;
	$with_missing = isset( $_GET['tharchive_imported_missing'] ) ? absint( $_GET['tharchive_imported_missing'] ) : 0;
	$errors       = isset( $_GET['tharchive_import_errors'] ) ? absint( $_GET['tharchive_import_errors'] ) : 0;

	echo '<div class="notice notice-success is-dismissible"><p>导入完成：成功创建 ' . esc_html( (string) $imported ) . ' 条活动，其中 ' . esc_html( (string) $with_missing ) . ' 条仍缺少关键字段，失败 ' . esc_html( (string) $errors ) . ' 条。你可以从左侧“待补关键字段”继续整理。</p></div>';
}
add_action( 'admin_notices', 'tharchive_render_import_result_notice' );
