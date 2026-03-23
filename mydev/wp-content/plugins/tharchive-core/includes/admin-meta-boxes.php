<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 注册活动编辑页的 Meta Box
 */
function tharchive_add_event_meta_boxes() {
	add_meta_box(
		'tharchive_event_details',
		'活动附加信息',
		'tharchive_render_event_meta_box',
		'relay_event',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'tharchive_add_event_meta_boxes' );

/**
 * 仅在 relay_event 编辑页加载媒体库脚本和样式
 *
 * @param string $hook_suffix 当前后台页面钩子
 */
function tharchive_enqueue_event_admin_media_assets( $hook_suffix ) {
	$screen = get_current_screen();

	if ( ! $screen || 'relay_event' !== $screen->post_type ) {
		return;
	}

	if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_style(
		'tharchive-event-admin-media',
		THARCHIVE_CORE_URL . 'assets/admin-event-media.css',
		array(),
		THARCHIVE_CORE_VERSION
	);

	wp_enqueue_script(
		'tharchive-event-admin-media',
		THARCHIVE_CORE_URL . 'assets/admin-event-media.js',
		array( 'jquery' ),
		THARCHIVE_CORE_VERSION,
		true
	);

	wp_localize_script(
		'tharchive-event-admin-media',
		'tharchiveEventAdminMedia',
		array(
			'frameTitle'  => '选择活动图集',
			'frameButton' => '使用这些图片',
			'emptyText'   => '暂未选择图集图片。这里建议放宣传图、海报或活动相关配图。',
		)
	);
}
add_action( 'admin_enqueue_scripts', 'tharchive_enqueue_event_admin_media_assets' );

/**
 * 渲染单行输入框
 */
function tharchive_render_text_input_row( $args ) {
	$id          = $args['id'];
	$label       = $args['label'];
	$value       = $args['value'];
	$type        = isset( $args['type'] ) ? $args['type'] : 'text';
	$placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
	$description = isset( $args['description'] ) ? $args['description'] : '';

	?>
	<tr>
		<th scope="row">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		</th>
		<td>
			<input
				type="<?php echo esc_attr( $type ); ?>"
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( $id ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				class="regular-text"
				<?php echo 'number' === $type ? 'min="0" step="1"' : ''; ?>
			/>
			<?php if ( ! empty( $description ) ) : ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * 渲染多行文本框
 */
function tharchive_render_textarea_row( $args ) {
	$id          = $args['id'];
	$label       = $args['label'];
	$value       = $args['value'];
	$rows        = isset( $args['rows'] ) ? absint( $args['rows'] ) : 4;
	$placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
	$description = isset( $args['description'] ) ? $args['description'] : '';

	?>
	<tr>
		<th scope="row">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		</th>
		<td>
			<textarea
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( $id ); ?>"
				rows="<?php echo esc_attr( $rows ); ?>"
				class="large-text"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
			><?php echo esc_textarea( $value ); ?></textarea>
			<?php if ( ! empty( $description ) ) : ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * 渲染活动图集媒体选择器
 *
 * @param int[] $image_ids 图集 attachment ID 列表
 */
function tharchive_render_gallery_picker_row( $image_ids ) {
	$image_ids = array_values(
		array_filter(
			array_map( 'absint', (array) $image_ids )
		)
	);
	?>
	<tr>
		<th scope="row">
			<label for="gallery_images">活动图集</label>
		</th>
		<td>
			<div class="tharchive-gallery-picker" data-tharchive-gallery-picker="1">
				<input
					type="hidden"
					id="gallery_images"
					name="gallery_images"
					value="<?php echo esc_attr( implode( ',', $image_ids ) ); ?>"
					data-tharchive-gallery-input="1"
				/>

				<div class="tharchive-gallery-picker__actions">
					<button type="button" class="button button-secondary" data-tharchive-gallery-open="1">从媒体库选择图片</button>
					<button
						type="button"
						class="button-link-delete<?php echo empty( $image_ids ) ? ' hidden' : ''; ?>"
						data-tharchive-gallery-clear="1"
					>
						清空图集
					</button>
				</div>

				<p class="description">
					支持多选图片，顺序会按这里显示的顺序保存。封面图仍然请使用右侧“特色图片”单独设置。
				</p>

				<ul class="tharchive-gallery-picker__list" data-tharchive-gallery-list="1">
					<?php foreach ( $image_ids as $image_id ) : ?>
						<?php
						$thumbnail = wp_get_attachment_image_url( $image_id, 'thumbnail' );
						$full       = wp_get_attachment_image_url( $image_id, 'medium_large' );
						$title      = get_the_title( $image_id );

						if ( ! $thumbnail ) {
							continue;
						}
						?>
						<li
							class="tharchive-gallery-picker__item"
							data-attachment-id="<?php echo esc_attr( $image_id ); ?>"
							data-thumbnail-url="<?php echo esc_url( $thumbnail ); ?>"
							data-full-url="<?php echo esc_url( $full ? $full : $thumbnail ); ?>"
							data-title="<?php echo esc_attr( $title ? $title : '未命名图片' ); ?>"
						>
							<div class="tharchive-gallery-picker__thumb">
								<img src="<?php echo esc_url( $thumbnail ); ?>" alt="" />
							</div>
							<div class="tharchive-gallery-picker__meta">
								<strong><?php echo esc_html( $title ? $title : '未命名图片' ); ?></strong>
								<span>ID: <?php echo esc_html( (string) $image_id ); ?></span>
							</div>
							<button type="button" class="button-link-delete" data-tharchive-gallery-remove="1">移除</button>
						</li>
					<?php endforeach; ?>
				</ul>

				<p
					class="description tharchive-gallery-picker__empty<?php echo empty( $image_ids ) ? '' : ' hidden'; ?>"
					data-tharchive-gallery-empty="1"
				>
					暂未选择图集图片。这里建议放宣传图、海报或活动相关配图。
				</p>
			</div>
		</td>
	</tr>
	<?php
}

/**
 * 渲染活动 Meta Box
 *
 * @param WP_Post $post 当前文章对象
 */
function tharchive_render_event_meta_box( $post ) {
	wp_nonce_field( 'tharchive_save_event_meta', 'tharchive_event_meta_nonce' );

	$event_year               = tharchive_get_event_meta( $post->ID, 'event_year', '' );
	$event_date               = tharchive_get_event_meta( $post->ID, 'event_date', '' );
	$event_date_end           = tharchive_get_event_meta( $post->ID, 'event_date_end', '' );
	$organizer_contact        = tharchive_get_event_meta( $post->ID, 'organizer_contact', '' );
	$registration_info        = tharchive_get_event_meta( $post->ID, 'registration_info', '' );
	$deadline_info            = tharchive_get_event_meta( $post->ID, 'deadline_info', '' );
	$publish_platform_info    = tharchive_get_event_meta( $post->ID, 'publish_platform_info', '' );
	$rules_markdown           = tharchive_get_event_meta( $post->ID, 'rules_markdown', '' );
	$bilibili_summary_url     = tharchive_get_event_meta( $post->ID, 'bilibili_summary_url', '' );
	$archive_site_url         = tharchive_get_event_meta( $post->ID, 'archive_site_url', '' );
	$extra_archive_links      = tharchive_get_event_meta( $post->ID, 'extra_archive_links', '' );
	$archive_summary_markdown = tharchive_get_event_meta( $post->ID, 'archive_summary_markdown', '' );
	$participant_count        = tharchive_get_event_meta( $post->ID, 'participant_count', '' );
	$source_summary_url       = tharchive_get_event_meta( $post->ID, 'source_summary_url', '' );
	$source_raw_text          = tharchive_get_event_meta( $post->ID, 'source_raw_text', '' );
	$other_notes              = tharchive_get_event_meta( $post->ID, 'other_notes', '' );
	$gallery_images           = tharchive_get_event_meta( $post->ID, 'gallery_images', array() );

	if ( ! is_array( $gallery_images ) ) {
		$gallery_images = array();
	}
	?>

	<div class="tharchive-meta-box">
		<p>
			这里填写活动的附加结构化信息。活动标题、活动说明、简介、封面图请继续使用 WordPress 自带区域。
		</p>
		<p>
			字段顺序已按“当前主要使用”与“备用字段”重新整理。靠后的项目主要用于后台补充、兼容旧方案或后续扩展。
		</p>

		<h3>一、当前主要使用字段</h3>
		<table class="form-table" role="presentation">
			<tbody>
				<?php
				tharchive_render_text_input_row(
					array(
						'id'          => 'event_year',
						'label'       => '活动年份',
						'value'       => $event_year,
						'type'        => 'number',
						'placeholder' => '例如 2026',
						'description' => '建议填写四位年份；通常也可由活动日期自动推导。',
					)
				);

				tharchive_render_text_input_row(
					array(
						'id'          => 'event_date',
						'label'       => '活动日期',
						'value'       => $event_date,
						'type'        => 'date',
						'description' => '当前前台录入和前台展示都会直接使用这个字段。',
					)
				);

				tharchive_render_text_input_row(
					array(
						'id'          => 'bilibili_summary_url',
						'label'       => '主归档 / 总结链接',
						'value'       => $bilibili_summary_url,
						'type'        => 'url',
						'placeholder' => 'https://www.bilibili.com/opus/... 或其它主归档链接',
						'description' => '当前单页按钮会直接读取这个字段。',
					)
				);

				tharchive_render_text_input_row(
					array(
						'id'          => 'archive_site_url',
						'label'       => '独立归档站链接',
						'value'       => $archive_site_url,
						'type'        => 'url',
						'placeholder' => 'https://yuriko.cn/relay/活动归档页',
						'description' => '当前单页按钮会直接读取这个字段。',
					)
				);

				tharchive_render_textarea_row(
					array(
						'id'          => 'source_raw_text',
						'label'       => '原始文本摘录',
						'value'       => $source_raw_text,
						'rows'        => 8,
						'placeholder' => '可直接粘贴总结专栏中的原始文字，供后续整理或解析使用。',
						'description' => '当前单页可选显示，后续也适合用于自动整理。',
					)
				);

				tharchive_render_textarea_row(
					array(
						'id'          => 'other_notes',
						'label'       => '其它备注',
						'value'       => $other_notes,
						'rows'        => 4,
						'placeholder' => '记录审核备注、补充说明或特殊情况。',
					)
				);
				?>
			</tbody>
		</table>

		<hr />

		<h3>二、备用字段（后台补充 / 历史兼容）</h3>
		<p class="description">
			以下字段当前不会全部在前台投稿和前台展示中使用，主要用于后台补充、旧数据兼容或以后扩展。
		</p>

		<h4>时间补充</h4>
		<table class="form-table" role="presentation">
			<tbody>
				<?php
				tharchive_render_text_input_row(
					array(
						'id'          => 'event_date_end',
						'label'       => '结束日期',
						'value'       => $event_date_end,
						'type'        => 'date',
						'description' => '如果活动跨天或有延续可填写；当前属于补充信息。',
					)
				);
				?>
			</tbody>
		</table>

		<h4>主办与参与说明</h4>
		<table class="form-table" role="presentation">
			<tbody>
				<?php
				tharchive_render_textarea_row(
					array(
						'id'          => 'organizer_contact',
						'label'       => '主办方联系方式',
						'value'       => $organizer_contact,
						'rows'        => 3,
						'placeholder' => "例如：QQ 群 123456789\n主催 B 站：xxx",
					)
				);

				tharchive_render_textarea_row(
					array(
						'id'          => 'registration_info',
						'label'       => '报名方式说明',
						'value'       => $registration_info,
						'rows'        => 4,
						'placeholder' => "例如：QQ 群报名，进群后联系主催登记\n或：私聊主催报名",
					)
				);

				tharchive_render_textarea_row(
					array(
						'id'          => 'deadline_info',
						'label'       => '截止方式说明',
						'value'       => $deadline_info,
						'rows'        => 3,
						'placeholder' => "例如：人满即止\n或：2026-03-20 20:00 截止",
					)
				);

				tharchive_render_textarea_row(
					array(
						'id'          => 'publish_platform_info',
						'label'       => '发布平台说明',
						'value'       => $publish_platform_info,
						'rows'        => 3,
						'placeholder' => "例如：主要在 Bilibili 发布，X / 贴吧同步宣传",
					)
				);

				tharchive_render_textarea_row(
					array(
						'id'          => 'rules_markdown',
						'label'       => '规则 / 补充说明（Markdown）',
						'value'       => $rules_markdown,
						'rows'        => 8,
						'placeholder' => "例如：\n- 每位参与者占一个时间段\n- 发布时请 at 前后棒\n- 禁止 AI 生成整稿投稿",
					)
				);
				?>
			</tbody>
		</table>

		<h4>扩展归档信息</h4>
		<table class="form-table" role="presentation">
			<tbody>
				<?php
				tharchive_render_textarea_row(
					array(
						'id'          => 'extra_archive_links',
						'label'       => '额外归档链接',
						'value'       => $extra_archive_links,
						'rows'        => 4,
						'placeholder' => "每行一个链接，可附说明\n例如：\nhttps://yuriko.cn/relay/活动归档页\nhttps://www.bilibili.com/opus/123456789",
						'description' => '建议每行一个链接，后续脚本也更容易处理。',
					)
				);

				tharchive_render_textarea_row(
					array(
						'id'          => 'archive_summary_markdown',
						'label'       => '归档总结说明（Markdown）',
						'value'       => $archive_summary_markdown,
						'rows'        => 8,
						'placeholder' => "例如：\n本次共 24 棒，另有 3 个特典棒。\n\n感谢各位老师参与……",
					)
				);

				tharchive_render_text_input_row(
					array(
						'id'          => 'participant_count',
						'label'       => '参与人数 / 棒位说明',
						'value'       => $participant_count,
						'type'        => 'text',
						'placeholder' => '例如：24 棒 + 3 特典 / 共 25 位创作者',
						'description' => '这里故意做成自由文本，不强制整数。',
					)
				);
				?>
			</tbody>
		</table>

		<h4>原始来源扩展</h4>
		<table class="form-table" role="presentation">
			<tbody>
				<?php
				tharchive_render_text_input_row(
					array(
						'id'          => 'source_summary_url',
						'label'       => '原始总结链接',
						'value'       => $source_summary_url,
						'type'        => 'url',
						'placeholder' => 'https://www.bilibili.com/opus/...',
						'description' => '便于以后做脚本 / 大模型自动导入时回溯来源。',
					)
				);
				?>
			</tbody>
		</table>

		<hr />

		<h3>三、活动图集</h3>
		<p class="description">
			这里已经改成媒体库多选器，适合管理员直接补图；前台仍然读取同一个 <code>gallery_images</code> 字段。
		</p>
		<table class="form-table" role="presentation">
			<tbody>
				<?php
				tharchive_render_gallery_picker_row( $gallery_images );
				?>
			</tbody>
		</table>
	</div>
	<?php
}
