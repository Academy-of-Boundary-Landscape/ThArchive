<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$relay_list_url = tharchive_theme_get_relay_list_url();
$recent_relay_url = tharchive_theme_get_recent_relay_url();
$submit_url     = tharchive_theme_get_submit_url();
$about_url      = tharchive_theme_get_about_url();
$site_title     = '东方同人接力归档';
$site_url       = 'https://yuriko.cn/';
?>
	</main>

	<div class="tharchive-site-footer-wrap">
		<footer class="tharchive-site-footer">
			<section class="tharchive-footer-block tharchive-footer-brand">
				<h2 class="tharchive-footer-title"><?php echo esc_html( $site_title ); ?></h2>
				<p class="tharchive-footer-desc"><?php bloginfo( 'description' ); ?></p>
				<p class="tharchive-footer-desc">
					<a class="tharchive-footer-link" href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_url ); ?></a>
				</p>
			</section>

			<nav class="tharchive-footer-block tharchive-footer-nav" aria-label="<?php esc_attr_e( 'Footer Links', 'tharchive-theme' ); ?>">
				<h2 class="tharchive-footer-title"><?php esc_html_e( '导航', 'tharchive-theme' ); ?></h2>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( '首页', 'tharchive-theme' ); ?></a>
				<a href="<?php echo esc_url( $relay_list_url ); ?>"><?php esc_html_e( '接力列表', 'tharchive-theme' ); ?></a>
				<a href="<?php echo esc_url( $recent_relay_url ); ?>"><?php esc_html_e( '近期接力', 'tharchive-theme' ); ?></a>
				<a href="<?php echo esc_url( $submit_url ); ?>"><?php esc_html_e( '投稿接力', 'tharchive-theme' ); ?></a>
				<a href="<?php echo esc_url( $about_url ); ?>"><?php esc_html_e( '关于', 'tharchive-theme' ); ?></a>
			</nav>

			<section class="tharchive-footer-block tharchive-footer-meta">
				<h2 class="tharchive-footer-title"><?php esc_html_e( '站点信息', 'tharchive-theme' ); ?></h2>
				<p><?php esc_html_e( '东方 Project 同人接力活动归档。', 'tharchive-theme' ); ?></p>
				<p><?php esc_html_e( '线上地址：yuriko.cn', 'tharchive-theme' ); ?></p>
				<p><?php esc_html_e( '内容版权归原作者所有。', 'tharchive-theme' ); ?></p>
			</section>

			<div class="tharchive-footer-copyright">
				<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $site_title ); ?></span>
				<span><?php esc_html_e( '东方同人接力活动归档站。', 'tharchive-theme' ); ?></span>
			</div>
		</footer>
	</div>
</div>

<?php wp_footer(); ?>
</body>
</html>
