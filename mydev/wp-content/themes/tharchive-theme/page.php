<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}
get_header();
?>

<?php
while ( have_posts() ) :
	the_post();
	$panel_classes = array( 'tharchive-panel' );
	$content       = get_post_field( 'post_content', get_the_ID() );

	if ( has_shortcode( $content, 'tharchive_event_carousel' ) || has_shortcode( $content, 'tharchive_relay_carousel' ) ) {
		$panel_classes[] = 'tharchive-panel--carousel-host';
	}
?>
<section class="<?php echo esc_attr( implode( ' ', $panel_classes ) ); ?>">
<article <?php post_class(); ?>>
<h1 class="tharchive-section-title"><?php the_title(); ?></h1>
<div class="tharchive-content">
<?php the_content(); ?>
</div>
</article>
</section>
<?php endwhile; ?>

<?php get_footer(); ?>
