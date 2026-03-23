<?php
/**
 * Front Page Template
 *
 * @package tharchive-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$relay_list_url   = tharchive_theme_get_relay_list_url();
$submit_url       = tharchive_theme_get_submit_url();
$recent_relay_url = tharchive_theme_get_recent_relay_url();
$about_url        = tharchive_theme_get_about_url();

get_header();
?>

	<section id="primary" class="site-main front-page-main">
	<section class="front-hero">
		<div class="container front-hero__inner">
			<div class="front-hero__content">
				<p class="front-hero__eyebrow">THArchive - Version 1.0 "Astrolabe"</p>
				<h1 class="front-hero__title">东方同人接力归档站</h1>
				<p class="front-hero__desc">
					一个用于整理、展示与归档东方Project 同人接力活动的网站。
					你可以在这里查看往期活动、关注近期接力、并登记你的活动信息。
				</p>

				<div class="front-hero__actions">
					<a class="button front-button" href="<?php echo esc_url( $relay_list_url ); ?>">
						往期接力列表大全
					</a>

					<a class="button front-button" href="<?php echo esc_url( $submit_url ); ?>">
						投稿接力活动
					</a>

					<a class="button front-button" href="<?php echo esc_url( $recent_relay_url ); ?>">
						近期接力
					</a>

					<a class="button front-button" href="<?php echo esc_url( $about_url ); ?>">
						关于和更新日志
					</a>
				</div>

				<div class="front-hero__carousel">
					<?php echo do_shortcode( '[tharchive_event_carousel mode="recent" per_page="7" orderby="date" order="desc" title="近期活动"]' ); ?>
				</div>
			</div>
		</div>
	</section>
	</section>

	<script>
		// 为主页星盘背景更新鼠标探照灯视口坐标
		document.addEventListener('mousemove', function(e) {
			const frontPageMain = document.querySelector('.front-page-main');
			if (frontPageMain) {
				// 改为了配合 position: fixed 定位直接使用客户端的视窗坐标 clientX/clientY
				frontPageMain.style.setProperty('--mouse-x', `${e.clientX}px`);
				frontPageMain.style.setProperty('--mouse-y', `${e.clientY}px`);
			}
		});
	</script>

<?php
get_footer();
