<?php

namespace BiliHtmlCleaner;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HtmlExtractor {
	/**
	 * @param string $raw_html 用户粘贴的原始 HTML。
	 * @param bool   $include_images 是否保留图片。
	 * @return array{title:string,clean_html:string}
	 */
	public function extract( string $raw_html, bool $include_images = false ): array {
		$raw_html = trim( $raw_html );

		if ( '' === $raw_html ) {
			return array(
				'title'      => '',
				'clean_html' => '',
			);
		}

		$document = new DOMDocument();
		$internal = libxml_use_internal_errors( true );
		$encoding = '<?xml encoding="utf-8" ?>';

		$document->loadHTML( $encoding . $raw_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		libxml_clear_errors();
		libxml_use_internal_errors( $internal );

		$xpath = new DOMXPath( $document );
		$this->pruneDocumentBeforeExtraction( $xpath );

		$title = $this->extractTitle( $xpath );
		$root  = $this->findContentRoot( $xpath, $document );

		if ( ! $root instanceof DOMElement ) {
			return array(
				'title'      => $title,
				'clean_html' => '',
			);
		}

		$clean_document = new DOMDocument( '1.0', 'UTF-8' );
		$wrapper        = $clean_document->createElement( 'div' );
		$wrapper->setAttribute( 'class', 'bili-html-cleaner-content' );
		$clean_document->appendChild( $wrapper );

		if ( '' !== $title ) {
			$heading = $clean_document->createElement( 'h1' );
			$heading->appendChild( $clean_document->createTextNode( $title ) );
			$wrapper->appendChild( $heading );
		}

		$wrapper->appendChild( $clean_document->importNode( $root, true ) );

		$this->cleanImportedDocument( $clean_document, $include_images );

		return array(
			'title'      => $title,
			'clean_html' => $this->innerHtml( $wrapper ),
		);
	}

	private function extractTitle( DOMXPath $xpath ): string {
		$candidates = array(
			'//*[contains(concat(" ", normalize-space(@class), " "), " opus-module-title__text ")]',
			'//meta[@property="og:title"]/@content',
			'//title',
			'//h1',
		);

		foreach ( $candidates as $query ) {
			$nodes = $xpath->query( $query );

			if ( ! $nodes || 0 === $nodes->length ) {
				continue;
			}

			$value = trim( (string) $nodes->item( 0 )->nodeValue );

			if ( '' !== $value ) {
				$value = wp_strip_all_tags( $value );
				return preg_replace( '/\s*-\s*哔哩哔哩\s*$/u', '', $value );
			}
		}

		return '';
	}

	private function findContentRoot( DOMXPath $xpath, DOMDocument $document ): ?DOMElement {
		$queries = array(
			'//*[contains(concat(" ", normalize-space(@class), " "), " bili-opus-view ")]//*[contains(concat(" ", normalize-space(@class), " "), " opus-module-content ")]',
			'//*[contains(concat(" ", normalize-space(@class), " "), " opus-detail ")]//*[contains(concat(" ", normalize-space(@class), " "), " opus-module-content ")]',
			'//*[contains(concat(" ", normalize-space(@class), " "), " opus-module-content ")]',
			'//article',
			'//main',
		);

		foreach ( $queries as $query ) {
			$nodes = $xpath->query( $query );

			if ( ! $nodes || 0 === $nodes->length ) {
				continue;
			}

			foreach ( $nodes as $node ) {
				if ( $node instanceof DOMElement && $this->hasMeaningfulContent( $node ) ) {
					return $node;
				}
			}
		}

		$body = $document->getElementsByTagName( 'body' )->item( 0 );
		if ( $body instanceof DOMElement && $this->hasMeaningfulContent( $body ) ) {
			return $body;
		}

		return $document->documentElement instanceof DOMElement ? $document->documentElement : null;
	}

	private function hasMeaningfulContent( DOMElement $element ): bool {
		$text = trim( preg_replace( '/\s+/u', ' ', $element->textContent ) );

		if ( mb_strlen( $text ) >= 40 ) {
			return true;
		}

		return $element->getElementsByTagName( 'img' )->length > 0;
	}

	private function pruneDocumentBeforeExtraction( DOMXPath $xpath ): void {
		$this->removeNodesByTagName( $xpath, array( 'script', 'style', 'noscript', 'iframe', 'canvas' ) );
		$this->removeNodesByXPath(
			$xpath,
			array(
				'//*[self::header or self::footer or self::nav or self::aside]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " comment-wrap ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " bili-comment-container ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " right-sidebar-wrap ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " side-toolbar ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " opus-tabs ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " reaction-list ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " bili-popup ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " opus-module-bottom ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " opus-module-copyright ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " opus-module-author ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " opus-module-top ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " opus-more ")]',
			)
		);
	}

	/**
	 * @param DOMXPath $xpath DOM 查询对象。
	 * @param array<int, string> $tags 需要移除的标签。
	 * @return void
	 */
	private function removeNodesByTagName( DOMXPath $xpath, array $tags ): void {
		foreach ( $tags as $tag ) {
			$nodes = $xpath->query( '//' . $tag );

			if ( ! $nodes ) {
				continue;
			}

			for ( $index = $nodes->length - 1; $index >= 0; $index-- ) {
				$node = $nodes->item( $index );

				if ( $node instanceof DOMNode && $node->parentNode ) {
					$node->parentNode->removeChild( $node );
				}
			}
		}
	}

	/**
	 * @param DOMXPath $xpath DOM 查询对象。
	 * @param array<int, string> $queries 待执行的 XPath 语句。
	 * @return void
	 */
	private function removeNodesByXPath( DOMXPath $xpath, array $queries ): void {
		foreach ( $queries as $query ) {
			$nodes = $xpath->query( $query );

			if ( ! $nodes ) {
				continue;
			}

			for ( $index = $nodes->length - 1; $index >= 0; $index-- ) {
				$node = $nodes->item( $index );

				if ( $node instanceof DOMNode && $node->parentNode ) {
					$node->parentNode->removeChild( $node );
				}
			}
		}
	}

	private function cleanImportedDocument( DOMDocument $document, bool $include_images ): void {
		$xpath = new DOMXPath( $document );

		$this->removeNodesByTagName( $xpath, array( 'script', 'style', 'noscript', 'svg', 'source', 'canvas' ) );
		$this->removeNodesByXPath(
			$xpath,
			array(
				'//*[@hidden]',
				'//*[contains(@style, "display:none")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " bili-dyn-pic__mask ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " opus-module-copyright ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " opus-module-bottom ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " comment-wrap ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " right-sidebar-wrap ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " side-toolbar ")]',
				'//*[self::button]',
			)
		);

		$this->unwrapNodesByXPath(
			$xpath,
			array(
				'//*[contains(concat(" ", normalize-space(@class), " "), " bili-html-cleaner-content ")]/*[contains(concat(" ", normalize-space(@class), " "), " opus-module-content ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " b-img ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " bili-dyn-pic ")]',
				'//*[contains(concat(" ", normalize-space(@class), " "), " bili-dyn-pic__img ")]',
				'//picture',
				'//span',
			)
		);

		if ( $include_images ) {
			$this->normalizeImageNodes( $xpath );
		} else {
			$this->removeNodesByTagName( $xpath, array( 'img' ) );
			$this->unwrapNodesByXPath(
				$xpath,
				array(
					'//figure',
				)
			);
		}

		$this->normalizeLinkNodes( $xpath );
		$this->stripPresentationAttributes( $xpath );
		$this->removeEmptyNodes( $xpath );
	}

	/**
	 * @param DOMXPath $xpath DOM 查询对象。
	 * @param array<int, string> $queries 待展开节点。
	 * @return void
	 */
	private function unwrapNodesByXPath( DOMXPath $xpath, array $queries ): void {
		foreach ( $queries as $query ) {
			$nodes = $xpath->query( $query );

			if ( ! $nodes ) {
				continue;
			}

			for ( $index = $nodes->length - 1; $index >= 0; $index-- ) {
				$node = $nodes->item( $index );

				if ( ! $node instanceof DOMElement || ! $node->parentNode ) {
					continue;
				}

				while ( $node->firstChild ) {
					$node->parentNode->insertBefore( $node->firstChild, $node );
				}

				$node->parentNode->removeChild( $node );
			}
		}
	}

	private function normalizeImageNodes( DOMXPath $xpath ): void {
		$image_queries = array(
			'//img[@data-src and not(@src)]'      => 'data-src',
			'//img[@data-original and not(@src)]' => 'data-original',
		);

		foreach ( $image_queries as $query => $attribute ) {
			$images = $xpath->query( $query );

			if ( ! $images ) {
				continue;
			}

			foreach ( $images as $image ) {
				if ( $image instanceof DOMElement ) {
					$image->setAttribute( 'src', $image->getAttribute( $attribute ) );
				}
			}
		}

		$images = $xpath->query( '//img' );
		if ( ! $images ) {
			return;
		}

		foreach ( $images as $image ) {
			if ( ! $image instanceof DOMElement ) {
				continue;
			}

			$src = $this->normalizeUrl( $image->getAttribute( 'src' ) );
			if ( '' === $src ) {
				if ( $image->parentNode ) {
					$image->parentNode->removeChild( $image );
				}
				continue;
			}

			$image->setAttribute( 'src', $src );
			$image->setAttribute( 'alt', trim( $image->getAttribute( 'alt' ) ) ?: 'Bilibili image' );
			$image->removeAttribute( 'loading' );
			$image->removeAttribute( 'onload' );
			$image->removeAttribute( 'onerror' );
			$image->removeAttribute( 'data-onload' );
			$image->removeAttribute( 'data-onerror' );
			$image->removeAttribute( 'width' );
			$image->removeAttribute( 'height' );
			$image->removeAttribute( 'class' );
		}
	}

	private function normalizeLinkNodes( DOMXPath $xpath ): void {
		$links = $xpath->query( '//a' );
		if ( ! $links ) {
			return;
		}

		foreach ( $links as $link ) {
			if ( ! $link instanceof DOMElement ) {
				continue;
			}

			$href = trim( $link->getAttribute( 'href' ) );
			if ( '' === $href || 0 === strpos( $href, 'javascript:' ) ) {
				$link->removeAttribute( 'href' );
			} else {
				$link->setAttribute( 'href', $this->normalizeUrl( $href ) );
			}

			$link->removeAttribute( 'target' );
			$link->removeAttribute( 'rel' );
			$link->removeAttribute( 'class' );
			$link->removeAttribute( 'style' );
		}
	}

	private function stripPresentationAttributes( DOMXPath $xpath ): void {
		$nodes = $xpath->query( '//*[@style or @class]' );

		if ( ! $nodes ) {
			return;
		}

		foreach ( $nodes as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			$style = $node->getAttribute( 'style' );
			if ( false !== strpos( $style, 'text-align:center' ) ) {
				$node->setAttribute( 'data-align', 'center' );
			}

			$node->removeAttribute( 'style' );
			$node->removeAttribute( 'class' );
		}
	}

	private function removeEmptyNodes( DOMXPath $xpath ): void {
		$nodes = $xpath->query( '//span[not(normalize-space()) and not(*)] | //p[not(normalize-space()) and not(*)] | //div[not(normalize-space()) and not(*)] | //figure[not(normalize-space()) and not(*)] | //figcaption[not(normalize-space()) and not(*)]' );

		if ( ! $nodes ) {
			return;
		}

		for ( $index = $nodes->length - 1; $index >= 0; $index-- ) {
			$node = $nodes->item( $index );

			if ( $node instanceof DOMNode && $node->parentNode ) {
				$node->parentNode->removeChild( $node );
			}
		}
	}

	private function normalizeUrl( string $url ): string {
		$url = trim( $url );

		if ( '' === $url ) {
			return '';
		}

		if ( 0 === strpos( $url, '//' ) ) {
			return 'https:' . $url;
		}

		return $url;
	}

	private function innerHtml( DOMElement $element ): string {
		$html = '';

		foreach ( $element->childNodes as $child ) {
			$html .= $element->ownerDocument->saveHTML( $child );
		}

		return trim( $html );
	}
}
