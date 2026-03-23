<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$post_id         = get_the_ID();
	$excerpt         = trim( (string) get_the_excerpt() );
	$event_date      = tharchive_get_event_date_label( $post_id );
	$content_html    = tharchive_render_markdown( get_post_field( 'post_content', $post_id ) );
	$source_raw_text = trim( (string) tharchive_get_event_meta( $post_id, 'source_raw_text', '' ) );
	$hero_image      = get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'tharchive-hero__image' ) );
	$meta_cards      = tharchive_render_event_meta_cards( $post_id );
	$gallery_html    = tharchive_render_event_gallery( $post_id );
	$action_html     = tharchive_render_event_actions( $post_id );
	?>

	<article <?php post_class( 'tharchive-relay-event' ); ?>>
		<section class="tharchive-panel tharchive-hero">
			<div class="tharchive-hero__layout">
				<div class="tharchive-hero__content">
					<span class="tharchive-eyebrow"><?php esc_html_e( 'Relay Event', 'tharchive-theme' ); ?></span>
					<h1 class="tharchive-hero__title"><?php the_title(); ?></h1>

					<?php if ( $excerpt ) : ?>
						<p class="tharchive-hero__excerpt"><?php echo esc_html( $excerpt ); ?></p>
					<?php endif; ?>

					<div class="tharchive-hero__meta">
						<?php if ( $event_date ) : ?>
							<div class="tharchive-meta-block">
								<span class="tharchive-meta-block__label"><?php esc_html_e( '活动日期', 'tharchive-theme' ); ?></span>
								<strong class="tharchive-meta-block__value"><?php echo esc_html( $event_date ); ?></strong>
							</div>
						<?php endif; ?>

						<?php echo wp_kses_post( tharchive_render_event_term_group( $post_id, 'touhou_character', '东方角色', false ) ); ?>
						<?php echo wp_kses_post( tharchive_render_event_term_group( $post_id, 'organizer', '主办方', false ) ); ?>
					</div>

					<?php echo wp_kses_post( $action_html ); ?>
				</div>

				<?php if ( $hero_image ) : ?>
					<div class="tharchive-hero__media">
						<?php echo $hero_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php endif; ?>
			</div>
		</section>

		<?php if ( $meta_cards ) : ?>
			<section class="tharchive-panel tharchive-relay-event__section">
				<h2 class="tharchive-section-title"><?php esc_html_e( '活动信息', 'tharchive-theme' ); ?></h2>
				<?php echo wp_kses_post( $meta_cards ); ?>
			</section>
		<?php endif; ?>

		<?php if ( $content_html ) : ?>
			<section class="tharchive-panel tharchive-relay-event__section">
				<h2 class="tharchive-section-title"><?php esc_html_e( '活动说明', 'tharchive-theme' ); ?></h2>
				<div class="tharchive-prose">
					<?php echo $content_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $gallery_html ) : ?>
			<section class="tharchive-panel tharchive-relay-event__section">
				<h2 class="tharchive-section-title"><?php esc_html_e( '活动图集', 'tharchive-theme' ); ?></h2>
				<?php echo wp_kses_post( $gallery_html ); ?>
			</section>
		<?php endif; ?>

		<?php if ( $source_raw_text ) : ?>
			<section class="tharchive-panel tharchive-relay-event__section">
				<h2 class="tharchive-section-title"><?php esc_html_e( '原始文本摘录', 'tharchive-theme' ); ?></h2>
				<details class="tharchive-details">
					<summary><?php esc_html_e( '展开查看原始文本', 'tharchive-theme' ); ?></summary>
					<div class="tharchive-details__body">
						<?php echo nl2br( esc_html( $source_raw_text ) ); ?>
					</div>
				</details>
			</section>
		<?php endif; ?>
	</article>
	<?php
endwhile;

get_footer();
