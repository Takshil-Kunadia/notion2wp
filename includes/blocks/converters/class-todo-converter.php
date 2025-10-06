<?php
/**
 * Todo Block Converter.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;

defined( 'ABSPATH' ) || exit;

/**
 * Converts Notion to_do blocks to Gutenberg list items with checkboxes.
 */
class Todo_Converter extends Abstract_Block_Converter {

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block ) {
		return isset( $block['type'] ) && 'to_do' === $block['type'];
	}

	/**
	 * Convert Notion paragraph block to Gutenberg paragraph.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		$block_data = $block['to_do'] ?? [];
		$rich_text  = $block_data['rich_text'] ?? [];
		$checked    = $block_data['checked'] ?? false;
		$content    = $this->rich_text_to_html( $rich_text );

		$checkbox = $checked ? '☑' : '☐';
		$html     = '<ul><li>' . $checkbox . ' ' . $content;

		if ( ! empty( $block['children'] ) ) {
			$html .= $this->process_children( $block['children'], $context );
		}

		$html .= '</li></ul>';

		return $this->wrap_gutenberg_block( 'core/list', $html );
	}
}
