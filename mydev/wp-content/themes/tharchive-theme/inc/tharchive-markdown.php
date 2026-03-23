<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 渲染 Markdown 或回退处理普通正文。
 *
 * @param string $text 原始正文。
 * @return string
 */
function tharchive_render_markdown( $text ) {
	$text = is_string( $text ) ? trim( $text ) : '';

	if ( '' === $text ) {
		return '';
	}

	if ( false !== strpos( $text, '<!-- wp:' ) || preg_match( '/<\/?[a-z][^>]*>/i', $text ) ) {
		return apply_filters( 'the_content', $text );
	}

	if ( class_exists( 'Parsedown' ) ) {
		$parser = new Parsedown();
		$html   = $parser->text( $text );

		return wp_kses( $html, tharchive_get_markdown_allowed_html() );
	}

	$blocks       = preg_split( "/\n{2,}/", str_replace( array( "\r\n", "\r" ), "\n", $text ) );
	$placeholders = array();
	$html_parts   = array();

	foreach ( $blocks as $block ) {
		$block = trim( $block );

		if ( '' === $block ) {
			continue;
		}

		if ( preg_match( '/^```([a-zA-Z0-9_-]+)?\n([\s\S]+)\n```$/', $block, $matches ) ) {
			$language = ! empty( $matches[1] ) ? sanitize_html_class( $matches[1] ) : '';
			$code     = esc_html( $matches[2] );
			$class    = $language ? ' class="language-' . esc_attr( $language ) . '"' : '';
			$html_parts[] = '<pre><code' . $class . '>' . $code . '</code></pre>';
			continue;
		}

		$lines = preg_split( "/\n/", $block );

		if ( ! empty( $lines ) && count( $lines ) === count( preg_grep( '/^>\s?/', $lines ) ) ) {
			$quote_lines = array_map(
				static function( $line ) {
					return preg_replace( '/^>\s?/', '', $line );
				},
				$lines
			);

			$html_parts[] = '<blockquote>' . tharchive_render_markdown( implode( "\n\n", $quote_lines ) ) . '</blockquote>';
			continue;
		}

		if ( ! empty( $lines ) && count( $lines ) === count( preg_grep( '/^(?:[-*+]\s+|\d+\.\s+)/', $lines ) ) ) {
			$is_ordered = 1 === preg_match( '/^\d+\.\s+/', $lines[0] );
			$tag        = $is_ordered ? 'ol' : 'ul';
			$items      = array();

			foreach ( $lines as $line ) {
				$item = preg_replace( '/^(?:[-*+]\s+|\d+\.\s+)/', '', $line );
				$items[] = '<li>' . tharchive_markdown_render_inline( $item, $placeholders ) . '</li>';
			}

			$html_parts[] = '<' . $tag . '>' . implode( '', $items ) . '</' . $tag . '>';
			continue;
		}

		if ( preg_match( '/^(#{1,6})\s+(.+)$/s', $block, $matches ) ) {
			$level = min( 6, strlen( $matches[1] ) );
			$title = tharchive_markdown_render_inline( trim( $matches[2] ), $placeholders );
			$html_parts[] = sprintf( '<h%d>%s</h%d>', $level, $title, $level );
			continue;
		}

		$paragraph = implode( "<br>\n", array_map(
			static function( $line ) use ( &$placeholders ) {
				return tharchive_markdown_render_inline( $line, $placeholders );
			},
			$lines
		) );

		$html_parts[] = '<p>' . $paragraph . '</p>';
	}

	$html = implode( "\n", $html_parts );

	return wp_kses( $html, tharchive_get_markdown_allowed_html() );
}

/**
 * 渲染 Markdown 行内语法。
 *
 * @param string $text 行内文本。
 * @param array  $placeholders 占位符缓存。
 * @return string
 */
function tharchive_markdown_render_inline( $text, array &$placeholders ) {
	$text = (string) $text;

	$text = preg_replace_callback(
		'/`([^`]+)`/',
		static function( $matches ) use ( &$placeholders ) {
			$key = '__THARCHIVE_CODE_' . count( $placeholders ) . '__';
			$placeholders[ $key ] = '<code>' . esc_html( $matches[1] ) . '</code>';

			return $key;
		},
		$text
	);

	$text = esc_html( $text );

	$text = preg_replace_callback(
		'/\[([^\]]+)\]\(([^)\s]+)(?:\s+"([^"]+)")?\)/',
		static function( $matches ) {
			$label = esc_html( $matches[1] );
			$url   = esc_url( $matches[2] );
			$title = ! empty( $matches[3] ) ? ' title="' . esc_attr( $matches[3] ) . '"' : '';

			if ( '' === $url ) {
				return $label;
			}

			return '<a href="' . $url . '"' . $title . ' target="_blank" rel="noreferrer noopener">' . $label . '</a>';
		},
		$text
	);

	$replacements = array(
		'/\*\*(.+?)\*\*/s' => '<strong>$1</strong>',
		'/__(.+?)__/s'        => '<strong>$1</strong>',
		'/\*(.+?)\*/s'       => '<em>$1</em>',
		'/_([^_]+)_/s'        => '<em>$1</em>',
		'/~~(.+?)~~/s'        => '<del>$1</del>',
	);

	foreach ( $replacements as $pattern => $replacement ) {
		$text = preg_replace( $pattern, $replacement, $text );
	}

	if ( ! empty( $placeholders ) ) {
		$text = strtr( $text, $placeholders );
	}

	return $text;
}

/**
 * 获取 Markdown 输出允许的 HTML 标签。
 *
 * @return array
 */
function tharchive_get_markdown_allowed_html() {
	$allowed = wp_kses_allowed_html( 'post' );

	$allowed['code'] = array(
		'class' => true,
	);

	$allowed['pre'] = array(
		'class' => true,
	);

	return $allowed;
}