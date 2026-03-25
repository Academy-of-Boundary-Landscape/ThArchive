<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 将 Elementor 原始 JSON 解码为数组。
 *
 * @param mixed $raw 原始 meta 值。
 * @return array
 */
function tharchive_decode_elementor_export_json( $raw ) {
	if ( is_array( $raw ) ) {
		return $raw;
	}

	if ( ! is_string( $raw ) || '' === $raw ) {
		return array();
	}

	$decoded = json_decode( $raw, true );

	return is_array( $decoded ) ? $decoded : array();
}

/**
 * 收集 Elementor 结构中的正文 section。
 *
 * @param array $elements Elementor 数据。
 * @param int   $post_id  当前文章 ID。
 * @return array<int, array<string, mixed>>
 */
function tharchive_collect_elementor_export_sections( $elements, $post_id ) {
	$sections = array();

	if ( ! is_array( $elements ) ) {
		return $sections;
	}

	foreach ( $elements as $element ) {
		if ( ! is_array( $element ) ) {
			continue;
		}

		$widget_type = '';
		if ( ! empty( $element['widgetType'] ) && is_string( $element['widgetType'] ) ) {
			$widget_type = $element['widgetType'];
		} elseif ( ! empty( $element['elType'] ) && is_string( $element['elType'] ) ) {
			$widget_type = $element['elType'];
		}

		$settings = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();
		$section  = tharchive_build_elementor_export_section( $widget_type, $settings, $post_id );

		if ( ! empty( $section ) ) {
			$sections[] = $section;
		}

		if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
			$sections = array_merge(
				$sections,
				tharchive_collect_elementor_export_sections( $element['elements'], $post_id )
			);
		}
	}

	return array_values(
		array_filter(
			$sections,
			function( $section ) {
				return ! empty( $section['body_markdown'] ) || ! empty( $section['image_urls'] );
			}
		)
	);
}

/**
 * 收集 Elementor 结构中的按钮链接。
 *
 * @param array $elements Elementor 数据。
 * @param int   $post_id  当前文章 ID。
 * @return array<int, array<string, string>>
 */
function tharchive_collect_elementor_export_button_links( $elements, $post_id ) {
	$button_links = array();

	if ( ! is_array( $elements ) ) {
		return $button_links;
	}

	foreach ( $elements as $element ) {
		if ( ! is_array( $element ) ) {
			continue;
		}

		$widget_type = '';
		if ( ! empty( $element['widgetType'] ) && is_string( $element['widgetType'] ) ) {
			$widget_type = $element['widgetType'];
		} elseif ( ! empty( $element['elType'] ) && is_string( $element['elType'] ) ) {
			$widget_type = $element['elType'];
		}

		$settings    = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();
		$button_link = tharchive_build_elementor_export_button_link( $widget_type, $settings, $post_id );

		if ( ! empty( $button_link ) ) {
			$button_links[] = $button_link;
		}

		if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
			$button_links = array_merge(
				$button_links,
				tharchive_collect_elementor_export_button_links( $element['elements'], $post_id )
			);
		}
	}

	return array_values(
		array_map(
			'unserialize',
			array_unique(
				array_map(
					'serialize',
					array_filter( $button_links )
				)
			)
		)
	);
}

/**
 * 根据 widget 类型构造按钮链接信息。
 *
 * @param string $widget_type Widget 类型。
 * @param array  $settings    Widget 配置。
 * @param int    $post_id     当前文章 ID。
 * @return array<string, string>
 */
function tharchive_build_elementor_export_button_link( $widget_type, $settings, $post_id ) {
	$widget_type = (string) $widget_type;

	if ( '' === $widget_type || empty( $settings ) || ! is_array( $settings ) ) {
		return array();
	}

	$link_data = tharchive_find_elementor_export_link_candidate( $settings );

	if ( empty( $link_data['url'] ) ) {
		return array();
	}

	$text = tharchive_find_elementor_export_button_text( $settings );

	if ( '' === $text && 'button' !== $widget_type && false === strpos( $widget_type, 'button' ) ) {
		return array();
	}

	return array(
		'widget_type' => $widget_type,
		'text'        => $text,
		'url'         => esc_url_raw( $link_data['url'] ),
		'target'      => ! empty( $link_data['is_external'] ) ? '_blank' : '',
		'nofollow'    => ! empty( $link_data['nofollow'] ) ? '1' : '0',
	);
}

/**
 * 从 widget settings 中寻找链接对象。
 *
 * @param array $settings widget 配置。
 * @return array<string, mixed>
 */
function tharchive_find_elementor_export_link_candidate( $settings ) {
	$candidates = array(
		'link',
		'button_link',
		'url',
		'source_link',
	);

	foreach ( $candidates as $key ) {
		if ( empty( $settings[ $key ] ) ) {
			continue;
		}

		$value = $settings[ $key ];

		if ( is_array( $value ) && ! empty( $value['url'] ) && is_string( $value['url'] ) ) {
			return $value;
		}

		if ( is_string( $value ) && '' !== trim( $value ) ) {
			return array(
				'url' => $value,
			);
		}
	}

	foreach ( $settings as $value ) {
		if ( ! is_array( $value ) ) {
			continue;
		}

		$result = tharchive_find_elementor_export_link_candidate( $value );

		if ( ! empty( $result['url'] ) ) {
			return $result;
		}
	}

	return array();
}

/**
 * 从 widget settings 中寻找按钮文字。
 *
 * @param array $settings widget 配置。
 * @return string
 */
function tharchive_find_elementor_export_button_text( $settings ) {
	$text_keys = array(
		'text',
		'button_text',
		'title',
		'label',
	);

	foreach ( $text_keys as $key ) {
		if ( empty( $settings[ $key ] ) || ! is_string( $settings[ $key ] ) ) {
			continue;
		}

		$text = trim( wp_strip_all_tags( $settings[ $key ] ) );

		if ( '' !== $text ) {
			return $text;
		}
	}

	return '';
}

/**
 * 根据 widget 类型构造导出 section。
 *
 * @param string $widget_type Widget 类型。
 * @param array  $settings    Widget 配置。
 * @param int    $post_id     当前文章 ID。
 * @return array<string, mixed>
 */
function tharchive_build_elementor_export_section( $widget_type, $settings, $post_id ) {
	$body_markdown = '';
	$image_urls    = array();
	$title         = '';
	$widget_type   = (string) $widget_type;

	switch ( $widget_type ) {
		case 'heading':
			$title         = isset( $settings['title'] ) ? wp_strip_all_tags( (string) $settings['title'] ) : '';
			$body_markdown = tharchive_normalize_elementor_export_markdown( $title );
			break;

		case 'text-editor':
		case 'html':
		case 'fswp-text-unfold':
			$body_markdown = tharchive_convert_elementor_export_settings_to_markdown( $settings );
			break;

		case 'image':
			$image_urls = tharchive_collect_elementor_export_image_urls_from_settings( $settings, $post_id );
			break;

		case 'button':
			return array();

		default:
			$body_markdown = tharchive_convert_elementor_export_settings_to_markdown( $settings );
			$image_urls    = tharchive_collect_elementor_export_image_urls_from_settings( $settings, $post_id );
			break;
	}

	return array(
		'widget_type'   => $widget_type,
		'title'         => $title,
		'body_markdown' => $body_markdown,
		'image_urls'    => $image_urls,
	);
}

/**
 * 将 widget settings 转成 Markdown 正文。
 *
 * @param array $settings 配置项。
 * @return string
 */
function tharchive_convert_elementor_export_settings_to_markdown( $settings ) {
	$html_fragments = tharchive_collect_elementor_export_html_fragments( $settings );

	if ( empty( $html_fragments ) ) {
		return '';
	}

	$blocks = array();

	foreach ( $html_fragments as $fragment ) {
		$markdown = tharchive_convert_elementor_export_html_fragment( $fragment );

		if ( '' === $markdown ) {
			continue;
		}

		$blocks[] = $markdown;
	}

	return trim( implode( "\n\n", array_unique( $blocks ) ) );
}

/**
 * 从 settings 中提取可能含正文的 HTML 片段。
 *
 * @param array $settings widget 配置。
 * @return array<int, string>
 */
function tharchive_collect_elementor_export_html_fragments( $settings ) {
	$fragments    = array();
	$allowed_keys = array( 'editor', 'text', 'content', 'html', 'description', 'title' );

	$walker = function( $value, $key = '' ) use ( &$walker, &$fragments, $allowed_keys ) {
		if ( is_string( $value ) ) {
			$trimmed = trim( $value );

			if ( '' === $trimmed ) {
				return;
			}

			if ( in_array( (string) $key, $allowed_keys, true ) || false !== strpos( $trimmed, '<' ) || preg_match( '/[\p{Han}A-Za-z0-9]{12,}/u', $trimmed ) ) {
				$fragments[] = $trimmed;
			}

			return;
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $child_key => $child_value ) {
				$walker( $child_value, is_string( $child_key ) ? $child_key : '' );
			}
		}
	};

	$walker( $settings );

	return array_values( array_unique( $fragments ) );
}

/**
 * 从 settings 中提取图片 URL。
 *
 * @param array $settings widget 配置。
 * @param int   $post_id  当前文章 ID。
 * @return array<int, string>
 */
function tharchive_collect_elementor_export_image_urls_from_settings( $settings, $post_id ) {
	$image_urls = array();

	$walker = function( $value ) use ( &$walker, &$image_urls, $post_id ) {
		if ( is_array( $value ) ) {
			if ( ! empty( $value['url'] ) && is_string( $value['url'] ) ) {
				$image_urls[] = esc_url_raw( $value['url'] );
			}

			if ( ! empty( $value['id'] ) ) {
				$attachment_url = wp_get_attachment_image_url( absint( $value['id'] ), 'full' );

				if ( $attachment_url ) {
					$image_urls[] = $attachment_url;
				}
			}

			foreach ( $value as $child ) {
				$walker( $child );
			}
		}
	};

	$walker( $settings );

	return array_values(
		array_filter(
			array_unique( $image_urls ),
			function( $url ) use ( $post_id ) {
				return is_string( $url ) && '' !== $url && $post_id >= 0;
			}
		)
	);
}

/**
 * 选择最像正文主体的 Markdown 块。
 *
 * @param array   $sections 已抽出的 section。
 * @param WP_Post $post     当前文章。
 * @return string
 */
function tharchive_choose_elementor_export_main_body( $sections, $post ) {
	$best_block  = '';
	$best_length = 0;

	foreach ( $sections as $section ) {
		$body = isset( $section['body_markdown'] ) ? trim( (string) $section['body_markdown'] ) : '';

		if ( '' === $body ) {
			continue;
		}

		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $body ) : strlen( $body );

		if ( $length > $best_length ) {
			$best_length = $length;
			$best_block  = $body;
		}
	}

	if ( '' !== $best_block ) {
		return $best_block;
	}

	$fallback = trim( wp_strip_all_tags( (string) $post->post_content ) );

	return tharchive_normalize_elementor_export_markdown( $fallback );
}

/**
 * 将 HTML 片段转成 Markdown 风格文本。
 *
 * @param string $html HTML 片段。
 * @return string
 */
function tharchive_convert_elementor_export_html_fragment( $html ) {
	$html = trim( (string) $html );

	if ( '' === $html ) {
		return '';
	}

	if ( ! class_exists( 'DOMDocument' ) ) {
		return tharchive_normalize_elementor_export_markdown( wp_strip_all_tags( $html ) );
	}

	$document = new DOMDocument();
	$wrapped  = '<!DOCTYPE html><html><body>' . $html . '</body></html>';

	libxml_use_internal_errors( true );
	$loaded = $document->loadHTML( '<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();

	if ( ! $loaded ) {
		return tharchive_normalize_elementor_export_markdown( wp_strip_all_tags( $html ) );
	}

	$xpath = new DOMXPath( $document );

	foreach ( $xpath->query( '//style|//script|//svg|//button|//noscript' ) as $node ) {
		$node->parentNode->removeChild( $node );
	}

	$body = $document->getElementsByTagName( 'body' )->item( 0 );

	if ( ! $body ) {
		return tharchive_normalize_elementor_export_markdown( wp_strip_all_tags( $html ) );
	}

	$chunks = array();

	foreach ( $body->childNodes as $child ) {
		$chunk = tharchive_convert_elementor_export_dom_node( $child );

		if ( '' !== $chunk ) {
			$chunks[] = $chunk;
		}
	}

	return tharchive_normalize_elementor_export_markdown( implode( "\n\n", $chunks ) );
}

/**
 * 将 DOM 节点转成 Markdown 风格文本。
 *
 * @param DOMNode $node DOM 节点。
 * @return string
 */
function tharchive_convert_elementor_export_dom_node( $node ) {
	if ( XML_TEXT_NODE === $node->nodeType ) {
		return preg_replace( '/\s+/u', ' ', trim( $node->textContent ) );
	}

	if ( XML_ELEMENT_NODE !== $node->nodeType ) {
		return '';
	}

	$tag = strtolower( $node->nodeName );

	if ( in_array( $tag, array( 'style', 'script', 'svg', 'button', 'noscript' ), true ) ) {
		return '';
	}

	if ( 'br' === $tag ) {
		return "\n";
	}

	if ( 'table' === $tag ) {
		return tharchive_convert_elementor_export_table_to_markdown( $node );
	}

	if ( preg_match( '/^h([1-6])$/', $tag, $matches ) ) {
		$level = max( 1, min( 6, (int) $matches[1] ) );
		$text  = trim( tharchive_convert_elementor_export_child_nodes( $node ) );

		return '' !== $text ? str_repeat( '#', $level ) . ' ' . $text : '';
	}

	if ( 'ul' === $tag || 'ol' === $tag ) {
		$lines = array();
		$index = 1;

		foreach ( $node->childNodes as $child ) {
			if ( XML_ELEMENT_NODE !== $child->nodeType || 'li' !== strtolower( $child->nodeName ) ) {
				continue;
			}

			$text = trim( tharchive_convert_elementor_export_child_nodes( $child ) );
			if ( '' === $text ) {
				continue;
			}

			$prefix  = 'ol' === $tag ? $index . '. ' : '- ';
			$lines[] = $prefix . $text;
			++$index;
		}

		return implode( "\n", $lines );
	}

	if ( 'img' === $tag ) {
		$src = $node->attributes && $node->attributes->getNamedItem( 'src' ) ? $node->attributes->getNamedItem( 'src' )->nodeValue : '';
		$alt = $node->attributes && $node->attributes->getNamedItem( 'alt' ) ? $node->attributes->getNamedItem( 'alt' )->nodeValue : '';

		return $src ? '![' . trim( (string) $alt ) . '](' . esc_url_raw( $src ) . ')' : '';
	}

	if ( 'a' === $tag ) {
		$text = trim( tharchive_convert_elementor_export_child_nodes( $node ) );
		$href = $node->attributes && $node->attributes->getNamedItem( 'href' ) ? $node->attributes->getNamedItem( 'href' )->nodeValue : '';

		if ( '' === $href ) {
			return $text;
		}

		return '' !== $text ? '[' . $text . '](' . esc_url_raw( $href ) . ')' : esc_url_raw( $href );
	}

	$text = trim( tharchive_convert_elementor_export_child_nodes( $node ) );

	if ( in_array( $tag, array( 'p', 'div', 'section', 'article', 'blockquote' ), true ) ) {
		return $text;
	}

	return $text;
}

/**
 * 将 DOM 节点的子节点拼成文本。
 *
 * @param DOMNode $node DOM 节点。
 * @return string
 */
function tharchive_convert_elementor_export_child_nodes( $node ) {
	$chunks = array();

	foreach ( $node->childNodes as $child ) {
		$chunk = tharchive_convert_elementor_export_dom_node( $child );

		if ( '' !== $chunk ) {
			$chunks[] = $chunk;
		}
	}

	return implode( '', $chunks );
}

/**
 * 将 HTML table 转成 Markdown 表格。
 *
 * @param DOMNode $table table 节点。
 * @return string
 */
function tharchive_convert_elementor_export_table_to_markdown( $table ) {
	$rows = array();

	foreach ( $table->childNodes as $child ) {
		$tag = XML_ELEMENT_NODE === $child->nodeType ? strtolower( $child->nodeName ) : '';

		if ( in_array( $tag, array( 'thead', 'tbody', 'tfoot' ), true ) ) {
			foreach ( $child->childNodes as $row_node ) {
				if ( XML_ELEMENT_NODE === $row_node->nodeType && 'tr' === strtolower( $row_node->nodeName ) ) {
					$rows[] = tharchive_convert_elementor_export_table_row( $row_node );
				}
			}
			continue;
		}

		if ( 'tr' === $tag ) {
			$rows[] = tharchive_convert_elementor_export_table_row( $child );
		}
	}

	$rows = array_values(
		array_filter(
			$rows,
			function( $row ) {
				return ! empty( $row );
			}
		)
	);

	if ( empty( $rows ) ) {
		return '';
	}

	$header = array_shift( $rows );
	$lines  = array(
		'| ' . implode( ' | ', $header ) . ' |',
		'| ' . implode( ' | ', array_fill( 0, count( $header ), '---' ) ) . ' |',
	);

	foreach ( $rows as $row ) {
		$row = array_pad( $row, count( $header ), '' );
		$lines[] = '| ' . implode( ' | ', array_slice( $row, 0, count( $header ) ) ) . ' |';
	}

	return implode( "\n", $lines );
}

/**
 * 将 HTML table 行转成单行数组。
 *
 * @param DOMNode $row tr 节点。
 * @return array<int, string>
 */
function tharchive_convert_elementor_export_table_row( $row ) {
	$cells = array();

	foreach ( $row->childNodes as $cell ) {
		if ( XML_ELEMENT_NODE !== $cell->nodeType ) {
			continue;
		}

		$tag = strtolower( $cell->nodeName );

		if ( ! in_array( $tag, array( 'th', 'td' ), true ) ) {
			continue;
		}

		$text    = trim( tharchive_convert_elementor_export_child_nodes( $cell ) );
		$cells[] = str_replace( '|', '\|', $text );
	}

	return $cells;
}

/**
 * 规范化 Markdown 风格文本。
 *
 * @param string $text 原始文本。
 * @return string
 */
function tharchive_normalize_elementor_export_markdown( $text ) {
	$text = str_replace( array( "\r\n", "\r" ), "\n", (string) $text );
	$text = preg_replace( "/\n{3,}/", "\n\n", $text );
	$text = preg_replace( "/[ \t]+\n/", "\n", $text );

	return trim( (string) $text );
}
