<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<section class="tharchive-panel">
	<span class="tharchive-eyebrow"><?php esc_html_e( 'Relay Archive', 'tharchive-theme' ); ?></span>
	<h1 class="tharchive-section-title"><?php post_type_archive_title(); ?></h1>
	<p><?php esc_html_e( '集中展示 relay_event 的标题、摘要、日期和关键归档信息。', 'tharchive-theme' ); ?></p>

	<?php if ( have_posts() ) : ?>
		<div class="tharchive-card-grid">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php
				$post_id     = get_the_ID();
				$date_label  = tharchive_get_event_date_label( $post_id );
				$organizers  = wp_list_pluck( tharchive_get_event_terms( $post_id, 'organizer' ), 'name' );
				$characters  = wp_list_pluck( tharchive_get_event_terms( $post_id, 'touhou_character' ), 'name' );
				$meta_chunks = array_filter(
					array(
						$date_label,
						! empty( $organizers ) ? '主办方：' . implode( ' / ', $organizers ) : '',
						! empty( $characters ) ? '角色：' . implode( ' / ', $characters ) : '',
					)
				);
				?>
				<article <?php post_class( 'tharchive-card' ); ?>>
					<?php if ( has_post_thumbnail() ) : ?>
						<p><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium_large' ); ?></a></p>
					<?php endif; ?>
					<h2 class="tharchive-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php if ( ! empty( $meta_chunks ) ) : ?>
						<div class="tharchive-card__meta"><?php echo esc_html( implode( ' · ', $meta_chunks ) ); ?></div>
					<?php endif; ?>
					<?php if ( has_excerpt() ) : ?>
						<p class="tharchive-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
					<p><a href="<?php the_permalink(); ?>"><?php esc_html_e( '查看活动详情', 'tharchive-theme' ); ?></a></p>
				</article>
			<?php endwhile; ?>
		</div>

		<?php
		the_posts_pagination(
			array(
				'mid_size'  => 1,
				'prev_text' => '上一页',
				'next_text' => '下一页',
				'before_page_number' => '<span class="screen-reader-text">第 </span>',
			)
		);
		?>
	<?php else : ?>
		<div class="tharchive-empty-state">
			<p><?php esc_html_e( '还没有已发布的活动。', 'tharchive-theme' ); ?></p>
		</div>
	<?php endif; ?>
</section>

<?php
get_footer();