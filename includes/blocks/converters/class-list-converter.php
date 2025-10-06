<?php
/**
 * List Block Converter.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;

defined( 'ABSPATH' ) || exit;

/**
 * Converts Notion list item blocks to Gutenberg lists.
 */
class List_Converter extends Abstract_Block_Converter {

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block ) {
		$type = $block['type'] ?? '';
		return in_array( $type, [ 'bulleted_list_item', 'numbered_list_item' ], true );
	}

	/**
	 * Convert Notion list item block to Gutenberg list.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		$type       = $block['type'] ?? '';
		$block_data = $block[ $type ] ?? [];
		$rich_text  = $block_data['rich_text'] ?? [];
		$color      = $block_data['color'] ?? 'default';

		$content = $this->rich_text_to_html( $rich_text );

		$list_type   = 'bulleted_list_item' === $type ? 'ul' : 'ol';
		$block_name  = 'bulleted_list_item' === $type ? 'core/list' : 'core/list';
		
		$html = '<' . $list_type . '><li>' . $content;

		// Process nested children.
		if ( ! empty( $block['children'] ) ) {
			$html .= $this->process_children( $block['children'], $context );
		}

		$html .= '</li></' . $list_type . '>';

		$attributes = [
			'ordered' => 'numbered_list_item' === $type,
		];

		if ( 'default' !== $color ) {
			$attributes['className'] = $this->get_color_class( $color );
		}

		return $this->wrap_gutenberg_block( $block_name, $html, $attributes );
	}
}
