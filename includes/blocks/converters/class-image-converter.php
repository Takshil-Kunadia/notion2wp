<?php
/**
 * Image Block Converter.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;

defined( 'ABSPATH' ) || exit;

/**
 * Converts Notion image blocks to Gutenberg images.
 */
class Image_Converter extends Abstract_Block_Converter {

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block ) {
		return isset( $block['type'] ) && 'image' === $block['type'];
	}

	/**
	 * Convert Notion paragraph block to Gutenberg paragraph.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		$block_data = $block['image'] ?? [];
		$type       = $block_data['type'] ?? '';
		$url        = '';

		if ( 'external' === $type && ! empty( $block_data['external']['url'] ) ) {
			$url = $block_data['external']['url'];
		} elseif ( 'file' === $type && ! empty( $block_data['file']['url'] ) ) {
			$url = $block_data['file']['url'];
		}

		if ( empty( $url ) ) {
			return '';
		}

		$caption = $this->rich_text_to_html( $block_data['caption'] ?? [] );
		$alt     = $this->extract_plain_text( $block_data['caption'] ?? [] );

		$html = '<figure class="wp-block-image">';
		$html .= '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '"/>';
		
		if ( ! empty( $caption ) ) {
			$html .= '<figcaption class="wp-element-caption">' . $caption . '</figcaption>';
		}
		
		$html .= '</figure>';

		$attributes = [
			'url' => $url,
			'alt' => $alt,
		];

		return $this->wrap_gutenberg_block( 'core/image', $html, $attributes );
	}
}
