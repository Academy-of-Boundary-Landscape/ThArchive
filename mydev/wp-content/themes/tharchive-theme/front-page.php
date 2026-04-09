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
	<!-- 探照灯暗幕：通过 JS 用 transform 定位，避免重绘 -->
	<div class="front-page-curtain" aria-hidden="true"></div>
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
		/*
		 * 探照灯暗幕定位
		 * 通过 transform 移动 .front-page-curtain 元素，将其圆形开口对准鼠标位置。
		 * transform 变更由浏览器 compositor 线程处理，不触发任何 repaint 或 layout，
		 * 彻底替代了原本每帧重新光栅化 mask-image + conic-gradient 的方案。
		 */
		(function() {
			const curtain = document.querySelector('.front-page-curtain');
			if (!curtain) return;

			let rafPending = false;
			let pendingX = 0, pendingY = 0;
			const HIDDEN = 'translate(calc(-50% - 9999px), -50%)';

			document.addEventListener('mousemove', function(e) {
				pendingX = e.clientX;
				pendingY = e.clientY;
				if (rafPending) return;
				rafPending = true;
				requestAnimationFrame(function() {
					// 元素中心默认在视口中心（top:50% left:50%），
					// 将开口偏移至鼠标坐标：dx = mouseX - viewportCenterX
					const dx = pendingX - window.innerWidth / 2;
					const dy = pendingY - window.innerHeight / 2;
					curtain.style.transform = 'translate(calc(-50% + ' + dx + 'px), calc(-50% + ' + dy + 'px))';
					rafPending = false;
				});
			});

			document.addEventListener('mouseleave', function() {
				curtain.style.transform = HIDDEN;
			});
		})();
	</script>

<?php
get_footer();
