<?php
/**
 * Abstract Block Converter Base Class.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks;

use Notion2WP\Media\Media_Collector;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract base class for block converters.
 * Provides common functionality for all block converters.
 */
abstract class Abstract_Block_Converter implements Block_Converter_Interface {

	/**
	 * Default priority for converters.
	 *
	 * @var int
	 */
	protected $priority = 10;

	/**
	 * Get the priority of this converter.
	 *
	 * @return int
	 */
	public function get_priority() {
		return $this->priority;
	}

	/**
	 * Extract plain text from rich text array.
	 *
	 * @param array $rich_text Rich text array from Notion.
	 * @return string
	 */
	protected function extract_plain_text( $rich_text ) {
		if ( ! is_array( $rich_text ) ) {
			return '';
		}

		$text = '';
		foreach ( $rich_text as $text_item ) {
			if ( isset( $text_item['plain_text'] ) ) {
				$text .= $text_item['plain_text'];
			}
		}

		return $text;
	}

	/**
	 * Convert rich text array to HTML with formatting.
	 *
	 * @param array $rich_text Rich text array from Notion.
	 * @return string HTML string.
	 */
	protected function rich_text_to_html( $rich_text ) {
		if ( ! is_array( $rich_text ) ) {
			return '';
		}

		$html = '';

		foreach ( $rich_text as $text_item ) {
			$content = $text_item['plain_text'] ?? '';

			if ( empty( $content ) ) {
				continue;
			}

			$content      = esc_html( $content );
			$annotations  = $text_item['annotations'] ?? [];

			// Apply formatting in correct order.
			if ( ! empty( $annotations['code'] ) ) {
				$content = '<code>' . $content . '</code>';
			}
			if ( ! empty( $annotations['bold'] ) ) {
				$content = '<strong>' . $content . '</strong>';
			}
			if ( ! empty( $annotations['italic'] ) ) {
				$content = '<em>' . $content . '</em>';
			}
			if ( ! empty( $annotations['strikethrough'] ) ) {
				$content = '<s>' . $content . '</s>';
			}
			if ( ! empty( $annotations['underline'] ) ) {
				$content = '<u>' . $content . '</u>';
			}

			// Apply link.
			if ( ! empty( $text_item['href'] ) ) {
				$content = '<a href="' . esc_url( $text_item['href'] ) . '">' . $content . '</a>';
			}

			$html .= $content;
		}

		return $html;
	}

	/**
	 * Get color class for block.
	 *
	 * @param string $color Notion color value.
	 * @return string CSS class.
	 */
	protected function get_color_class( $color ) {
		if ( empty( $color ) || 'default' === $color ) {
			return '';
		}

		return 'has-' . esc_attr( $color ) . '-color';
	}

	/**
	 * Process child blocks recursively.
	 *
	 * @param array $children Child blocks array.
	 * @param array $context Context array.
	 * @return string HTML for child blocks.
	 */
	protected function process_children( $children, $context = [] ) {
		if ( empty( $children ) || ! is_array( $children ) ) {
			return '';
		}

		// Get the block registry to process children.
		$registry = Block_Registry::get_instance();
		$html     = '';

		foreach ( $children as $child_block ) {
			$html .= $registry->convert_block( $child_block, $context );
		}

		return $html;
	}

	/**
	 * Wrap content in Gutenberg block comment format.
	 *
	 * @param string $block_name Gutenberg block name (e.g., 'core/paragraph').
	 * @param string $content Block content.
	 * @param array  $attributes Block attributes.
	 * @return string
	 */
	protected function wrap_gutenberg_block( $block_name, $content, $attributes = [] ) {
		$attrs = '';
		if ( ! empty( $attributes ) ) {
			$attrs = ' ' . wp_json_encode( $attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		}

		return sprintf(
			"<!-- wp:%s%s -->\n%s\n<!-- /wp:%s -->\n",
			$block_name,
			$attrs,
			$content,
			$block_name
		);
	}

	/**
	 * Create a simple HTML block for unsupported or complex content.
	 *
	 * @param string $html HTML content.
	 * @return string
	 */
	protected function wrap_html_block( $html ) {
		return $this->wrap_gutenberg_block( 'core/html', $html );
	}

	/**
	 * Check if a string is a media placeholder token.
	 *
	 * @param string $url The string to check.
	 * @return bool
	 */
	protected function is_media_placeholder( $url ) {
		return 0 === strpos( $url, '{{notion_media:' );
	}

	/**
	 * Escape a URL for use in HTML attributes, passing placeholder tokens through.
	 *
	 * Placeholder tokens ({{notion_media:...}}) are not valid URLs and would be
	 * stripped by esc_url(). They are resolved to real URLs after post creation.
	 *
	 * @param string $url The URL or placeholder token.
	 * @return string The escaped URL or raw placeholder token.
	 */
	protected function escape_media_url( $url ) {
		return $this->is_media_placeholder( $url ) ? $url : esc_url( $url );
	}

	/**
	 * Resolve a media URL for download.
	 *
	 * Uses the presence of expiry_time from the Notion API response as the
	 * authoritative signal that a URL is temporary and must be downloaded.
	 * Falls back to type "file" check if expiry_time is absent (defensive).
	 *
	 * @param string      $url         The media URL.
	 * @param string      $file_type   The Notion file type ('external', 'file', 'file_upload').
	 * @param string      $block_type  The block type constant (e.g., Block_Types::IMAGE).
	 * @param string|null $expiry_time The expiry_time from the Notion file object, or null.
	 * @return string The original URL or a placeholder token.
	 */
	protected function resolve_media_url( $url, $file_type, $block_type, $expiry_time = null ) {
		// Primary signal: expiry_time means the URL is signed and will expire.
		// Fallback: type "file" always has expiry_time in practice, but handle the defensive case where it might be absent.
		$is_expiring = ( null !== $expiry_time ) || ( 'file' === $file_type );

		if ( ! $is_expiring ) {
			return $url;
		}

		/**
		 * Filters whether a specific media URL should be downloaded.
		 *
		 * @since 1.1.0
		 *
		 * @param bool   $should_download Whether to download this URL.
		 * @param string $url             The media URL.
		 * @param string $file_type       The Notion file type.
		 * @param string $block_type      The block type.
		 */
		if ( ! apply_filters( 'notion2wp_should_download_media', true, $url, $file_type, $block_type ) ) {
			return $url;
		}

		return Media_Collector::get_instance()->register( $url, $block_type );
	}

	/**
	 * Map Notion color to your preffered color in WordPress.
	 *
	 * @param string $color Notion color value.
	 * @return string Translated color value.
	 */
	protected function map_color( $color ): string {
		$default_colours = [
			'default'           => '#E6E6E4',
			'blue'              => '#0B6E99',
			'blue_background'   => '#CCE4F9',
			'brown'             => '#684B3F',
			'brown_background'  => '#E8D5CC',
			'gray'              => '#8F8E8A',
			'gray_background'   => '#D7D7D5',
			'green'             => '#A7F3D0',
			'green_background'  => '#A7F3D0',
			'orange'            => '#D9730D',
			'orange_background' => '#FDDFCC',
			'pink'              => '#B2297B',
			'pink_background'   => '#F8CCE6',
			'purple'            => '#6940A5',
			'purple_background' => '#E1D3F8',
			'red'               => '#E14646',
			'red_background'    => '#FFCCD1',
			'yellow'            => '#DFAC03',
			'yellow_background' => '#FBEECC',
		];

		/**
		 * Allow filtering of color mappings.
		 * You can also add new colors here if Notion adds more in the future.
		 *
		 * @param array $colors Associative array of Notion colors to WordPress colors.
		 */
		$colors = apply_filters( 'notion2wp_color_mappings', $default_colours );

		return $colors[ $color ] ?? $color;
	}
}
