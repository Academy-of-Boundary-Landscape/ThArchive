<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$relay_list_url = tharchive_theme_get_relay_list_url();
$recent_relay_url = tharchive_theme_get_recent_relay_url();
$submit_url     = tharchive_theme_get_submit_url();
$about_url      = tharchive_theme_get_about_url();
$site_title     = '东方同人接力归档';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="astronomy-background">
	<div class="stars-layer-1"></div>
	<div class="stars-layer-2"></div>
	<div class="meteor-shower">
		<div class="meteor"></div>
		<div class="meteor"></div>
		<div class="meteor"></div>
		<div class="meteor"></div>
		<div class="meteor"></div>
		<div class="meteor"></div>
		<div class="meteor"></div>
		<div class="meteor"></div>
		<div class="meteor"></div>
		<div class="meteor"></div>
	</div>
</div>


<div class="tharchive-site-shell">
	<div class="tharchive-site-header-wrap">
		<header class="tharchive-site-header">
			<div class="tharchive-header-left">
				<a class="tharchive-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<span class="tharchive-brand__kicker"><?php esc_html_e( 'Touhou Relay Archive', 'tharchive-theme' ); ?></span>
					<span class="tharchive-brand__title"><?php echo esc_html( $site_title ); ?></span>
				</a>
			</div>

			<nav class="tharchive-site-nav" aria-label="<?php esc_attr_e( 'Primary', 'tharchive-theme' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( '首页', 'tharchive-theme' ); ?></a>
				<a href="<?php echo esc_url( $relay_list_url ); ?>"><?php esc_html_e( '接力列表', 'tharchive-theme' ); ?></a>
				<a href="<?php echo esc_url( $recent_relay_url ); ?>"><?php esc_html_e( '近期接力', 'tharchive-theme' ); ?></a>
				<a href="<?php echo esc_url( $submit_url ); ?>"><?php esc_html_e( '提交接力信息', 'tharchive-theme' ); ?></a>
				<a href="<?php echo esc_url( $about_url ); ?>"><?php esc_html_e( '关于', 'tharchive-theme' ); ?></a>
			</nav>

			<div class="tharchive-header-status" aria-hidden="true">
				<span class="tharchive-header-status__dot"></span>
				<span class="tharchive-header-status__text"><?php esc_html_e( 'V1.0: ASTROLABE', 'tharchive-theme' ); ?></span>
			</div>
		</header>
	</div>

	<main class="tharchive-site-main">
