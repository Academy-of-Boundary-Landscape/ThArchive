<?php

namespace BiliHtmlCleaner;

use RuntimeException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminPage {
	private HtmlExtractor $extractor;

	private MarkdownService $markdown_service;

	private PromptBuilder $prompt_builder;

	public function __construct( HtmlExtractor $extractor, MarkdownService $markdown_service, PromptBuilder $prompt_builder ) {
		$this->extractor        = $extractor;
		$this->markdown_service = $markdown_service;
		$this->prompt_builder   = $prompt_builder;
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'registerMenu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
	}

	public function registerMenu(): void {
		add_management_page(
			'Bilibili HTML Cleaner',
			'Bilibili HTML Cleaner',
			'manage_options',
			'bili-html-cleaner',
			array( $this, 'renderPage' )
		);
	}

	public function enqueueAssets( string $hook_suffix ): void {
		if ( 'tools_page_bili-html-cleaner' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'bili-html-cleaner-admin',
			BILI_HTML_CLEANER_URL . 'assets/admin.css',
			array(),
			BILI_HTML_CLEANER_VERSION
		);

		wp_enqueue_script(
			'bili-html-cleaner-admin',
			BILI_HTML_CLEANER_URL . 'assets/admin.js',
			array(),
			BILI_HTML_CLEANER_VERSION,
			true
		);
	}

	public function renderPage(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '你没有权限使用这个工具。' );
		}

		$state = $this->getDefaultState();

		if ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			check_admin_referer( 'bili_html_cleaner_submit' );
			$state = $this->handleSubmission();
		}

		$this->renderTemplate( $state );
	}

	/**
	 * @return array<string, string>
	 */
	private function handleSubmission(): array {
		$raw_html       = isset( $_POST['raw_html'] ) ? wp_unslash( $_POST['raw_html'] ) : '';
		$raw_html       = is_string( $raw_html ) ? trim( $raw_html ) : '';
		$include_images = isset( $_POST['include_images'] ) && '1' === (string) wp_unslash( $_POST['include_images'] );

		$state = array(
			'raw_html'       => $raw_html,
			'clean_html'     => '',
			'markdown'       => '',
			'prompt'         => '',
			'include_images' => $include_images ? '1' : '0',
			'notice'         => '',
			'notice_type'    => 'success',
		);

		if ( '' === $raw_html ) {
			$state['notice']      = '请先粘贴原始 HTML。';
			$state['notice_type'] = 'error';
			return $state;
		}

		try {
			$extracted            = $this->extractor->extract( $raw_html, $include_images );
			$state['clean_html']  = $extracted['clean_html'];
			$state['markdown']    = $this->markdown_service->convert( $state['clean_html'] );
			$state['prompt']      = $this->prompt_builder->build( $state['markdown'] );
			$state['notice']      = '' !== $state['markdown'] ? '转换完成。' : '没有提取到可用正文，请检查 HTML 是否完整。';
			$state['notice_type'] = '' !== $state['markdown'] ? 'success' : 'error';
		} catch ( RuntimeException $exception ) {
			$state['notice']      = $exception->getMessage();
			$state['notice_type'] = 'error';
		} catch ( \Throwable $throwable ) {
			$state['notice']      = '处理失败，请检查 HTML 内容或服务器环境。';
			$state['notice_type'] = 'error';
		}

		return $state;
	}

	/**
	 * @return array<string, string>
	 */
	private function getDefaultState(): array {
		return array(
			'raw_html'       => '',
			'clean_html'     => '',
			'markdown'       => '',
			'prompt'         => '',
			'include_images' => '0',
			'notice'         => '',
			'notice_type'    => 'success',
		);
	}

	/**
	 * @param array<string, string> $state 页面状态。
	 * @return void
	 */
	private function renderTemplate( array $state ): void {
		?>
		<div class="wrap bili-html-cleaner">
			<h1>Bilibili HTML Cleaner</h1>
			<p>把 Bilibili 专栏或 Opus 页面 HTML 粘贴到下面，工具会尝试提取正文、转成 Markdown，并生成可复制到大模型网页中的 Prompt。</p>

			<?php if ( '' !== $state['notice'] ) : ?>
				<div class="notice notice-<?php echo esc_attr( $state['notice_type'] ); ?> is-dismissible">
					<p><?php echo esc_html( $state['notice'] ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'bili_html_cleaner_submit' ); ?>

				<div class="bili-html-cleaner__grid">
					<section class="bili-html-cleaner__panel bili-html-cleaner__panel--full">
						<h2>原始 HTML</h2>
						<textarea name="raw_html" rows="16" class="large-text code" placeholder="请粘贴完整 HTML"><?php echo esc_textarea( $state['raw_html'] ); ?></textarea>
						<p class="description">建议直接粘贴页面源代码或复制到的完整 HTML 片段。</p>
					</section>

					<section class="bili-html-cleaner__panel bili-html-cleaner__panel--full">
						<label>
							<input type="checkbox" name="include_images" value="1" <?php checked( '1', $state['include_images'] ); ?>>
							保留图片（可选，默认关闭）
						</label>
						<p class="description">默认只保留文本和链接，减少后续渲染与处理开销。</p>
						<?php submit_button( '清洗并转换', 'primary', 'submit', false ); ?>
					</section>

					<section class="bili-html-cleaner__panel">
						<div class="bili-html-cleaner__panel-head">
							<h2>Markdown 输出</h2>
							<button type="button" class="button" data-copy-target="bili-html-cleaner-markdown">复制 Markdown</button>
						</div>
						<textarea id="bili-html-cleaner-markdown" readonly rows="16" class="large-text code"><?php echo esc_textarea( $state['markdown'] ); ?></textarea>
					</section>

					<section class="bili-html-cleaner__panel">
						<div class="bili-html-cleaner__panel-head">
							<h2>Prompt 输出</h2>
							<button type="button" class="button" data-copy-target="bili-html-cleaner-prompt">复制 Prompt</button>
						</div>
						<textarea id="bili-html-cleaner-prompt" readonly rows="16" class="large-text code"><?php echo esc_textarea( $state['prompt'] ); ?></textarea>
					</section>

					<section class="bili-html-cleaner__panel bili-html-cleaner__panel--full">
						<details>
							<summary>查看清洗后的 HTML</summary>
							<textarea readonly rows="12" class="large-text code"><?php echo esc_textarea( $state['clean_html'] ); ?></textarea>
						</details>
					</section>
				</div>
			</form>
		</div>
		<?php
	}
}
