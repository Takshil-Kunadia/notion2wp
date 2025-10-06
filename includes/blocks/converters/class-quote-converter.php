<?php
/**
 * Quote Block Converter.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;

defined( 'ABSPATH' ) || exit;

/**
 * Converts Notion quote blocks to Gutenberg quotes.
 */
class Quote_Converter extends Abstract_Block_Converter {

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block ) {
		return isset( $block['type'] ) && 'quote' === $block['type'];
	}

	/**
	 * Convert Notion paragraph block to Gutenberg paragraph.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		$block_data = $block['quote'] ?? [];
		$rich_text  = $block_data['rich_text'] ?? [];
		$content    = $this->rich_text_to_html( $rich_text );

		$html = '<blockquote class="wp-block-quote"><p>' . $content . '</p>';

		if ( ! empty( $block['children'] ) ) {
			$html .= $this->process_children( $block['children'], $context );
		}

		$html .= '</blockquote>';

		return $this->wrap_gutenberg_block( 'core/quote', $html );
	}
}
