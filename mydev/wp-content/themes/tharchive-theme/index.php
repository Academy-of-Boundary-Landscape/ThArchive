<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<section class="tharchive-panel">
	<span class="tharchive-eyebrow"><?php esc_html_e( 'Archive Front', 'tharchive-theme' ); ?></span>
	<h1 class="tharchive-section-title"><?php bloginfo( 'name' ); ?></h1>
	<?php if ( get_bloginfo( 'description' ) ) : ?>
		<p><?php bloginfo( 'description' ); ?></p>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<div class="tharchive-card-grid">
			<?php while ( have_posts() ) : the_post(); ?>
				<?php $post_type_object = get_post_type_object( get_post_type() ); ?>
				<article <?php post_class( 'tharchive-card' ); ?>>
					<h2 class="tharchive-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<div class="tharchive-card__meta"><?php echo esc_html( $post_type_object ? $post_type_object->labels->singular_name : '' ); ?></div>
					<div class="tharchive-card__excerpt"><?php the_excerpt(); ?></div>
				</article>
			<?php endwhile; ?>
		</div>
	<?php else : ?>
		<div class="tharchive-empty-state">
			<p><?php esc_html_e( '当前还没有可展示的内容。', 'tharchive-theme' ); ?></p>
		</div>
	<?php endif; ?>
</section>

<?php
get_footer();