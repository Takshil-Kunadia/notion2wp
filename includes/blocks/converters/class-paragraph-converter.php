<?php
/**
 * Paragraph Block Converter.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;

defined( 'ABSPATH' ) || exit;

/**
 * Converts Notion paragraph blocks to Gutenberg paragraphs.
 */
class Paragraph_Converter extends Abstract_Block_Converter {

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block ) {
		return isset( $block['type'] ) && 'paragraph' === $block['type'];
	}

	/**
	 * Convert Notion paragraph block to Gutenberg paragraph.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		$block_data = $block['paragraph'] ?? [];
		$rich_text  = $block_data['rich_text'] ?? [];
		$color      = $block_data['color'] ?? 'default';

		$content = $this->rich_text_to_html( $rich_text );

		// Handle empty paragraphs.
		if ( empty( trim( wp_strip_all_tags( $content ) ) ) ) {
			$content = '&nbsp;';
		}

		$html = '<p>' . $content . '</p>';

		// Process children if present.
		if ( ! empty( $block['children'] ) ) {
			$html .= $this->process_children( $block['children'], $context );
		}

		$attributes = [];
		if ( 'default' !== $color ) {
			$attributes['className'] = $this->get_color_class( $color );
		}

		return $this->wrap_gutenberg_block( 'core/paragraph', $html, $attributes );
	}
}
