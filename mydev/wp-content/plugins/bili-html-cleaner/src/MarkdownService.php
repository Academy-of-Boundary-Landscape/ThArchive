<?php

namespace BiliHtmlCleaner;

use League\HTMLToMarkdown\HtmlConverter;
use RuntimeException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MarkdownService {
	private ?HtmlConverter $converter = null;

	public function convert( string $html ): string {
		$html = trim( $html );

		if ( '' === $html ) {
			return '';
		}

		$converter = $this->getConverter();
		$markdown  = $converter->convert( $html );

		return $this->postProcess( $markdown );
	}

	private function getConverter(): HtmlConverter {
		if ( $this->converter instanceof HtmlConverter ) {
			return $this->converter;
		}

		if ( ! class_exists( HtmlConverter::class ) ) {
			throw new RuntimeException( '缺少 HTML 转 Markdown 依赖，请先安装 vendor 依赖。' );
		}

		$this->converter = new HtmlConverter(
			array(
				'strip_tags'               => false,
				'remove_nodes'             => 'script style noscript svg',
				'hard_break'               => false,
				'use_autolinks'            => false,
				'header_style'             => 'atx',
				'bold_style'               => '**',
				'italic_style'             => '*',
				'list_item_style'          => '-',
				'preserve_comments'        => false,
				'suppress_errors'          => true,
				'strip_placeholder_links'  => true,
			)
		);

		return $this->converter;
	}

	private function postProcess( string $markdown ): string {
		$markdown = str_replace( array( "\r\n", "\r" ), "\n", $markdown );
		$markdown = preg_replace( "/[ \t]+\n/", "\n", $markdown );
		$markdown = preg_replace( "/\n{3,}/", "\n\n", $markdown );
		$markdown = preg_replace( "/[ \t]{2,}/", ' ', $markdown );
		$markdown = preg_replace( "/\n([*-])\s+\n/", "\n", $markdown );

		return trim( (string) $markdown );
	}
}
