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
		// Support both individual list items and grouped lists.
		return in_array( $type, [ 'bulleted_list_item', 'numbered_list_item' ], true ) &&
			( isset( $block['is_grouped'] ) && $block['is_grouped'] );
	}

	/**
	 * Convert Notion list item block(s) to Gutenberg list.
	 *
	 * Handles both:
	 * - Grouped lists (multiple consecutive list items)
	 * - Individual list items (for nested children)
	 *
	 * @param array $block Notion block object or grouped list.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		// Check if this is a grouped list.
		if ( isset( $block['is_grouped'] ) && $block['is_grouped'] ) {
			return $this->convert_grouped_list( $block, $context );
		}

		// Individual list item (used for nested children).
		return $this->convert_single_item( $block, $context );
	}

	/**
	 * Convert a grouped list (multiple list items).
	 *
	 * @param array $grouped_block Grouped list block.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	private function convert_grouped_list( $grouped_block, $context = [] ) {
		$type       = $grouped_block['type'] ?? '';
		$list_items = $grouped_block['list_items'] ?? [];

		if ( empty( $list_items ) ) {
			return '';
		}

		$is_ordered = 'numbered_list_item' === $type;
		$list_tag   = $is_ordered ? 'ol' : 'ul';
		$block_name = 'core/list';

		// Build the list HTML.
		$html = '<' . $list_tag . '>';

		foreach ( $list_items as $item ) {
			$html .= $this->convert_list_item_content( $item, $context );
		}

		$html .= '</' . $list_tag . '>';

		// Get color from first item (if any have color, use it for the whole list).
		$color = '';
		foreach ( $list_items as $item ) {
			$item_type = $item['type'] ?? '';
			$item_data = $item[ $item_type ] ?? [];
			$item_color = $item_data['color'] ?? 'default';
			if ( 'default' !== $item_color ) {
				$color = $item_color;
				break;
			}
		}

		// Prepare attributes.
		$attributes = [
			'ordered' => $is_ordered,
		];

		if ( 'default' !== $color && ! empty( $color ) ) {
			$attributes['className'] = $this->get_color_class( $color );
		}

		return $this->wrap_gutenberg_block( $block_name, $html, $attributes );
	}

	/**
	 * Convert a single list item (for nested children).
	 *
	 * @param array $block Notion list item block.
	 * @param array $context Additional context.
	 * @return string List item HTML (without wrapping list tags).
	 */
	private function convert_single_item( $block, $context = [] ) {
		$type      = $block['type'] ?? '';
		$is_ordered = 'numbered_list_item' === $type;
		$list_tag  = $is_ordered ? 'ol' : 'ul';

		$html = '<' . $list_tag . '>';
		$html .= $this->convert_list_item_content( $block, $context );
		$html .= '</' . $list_tag . '>';

		$block_data = $block[ $type ] ?? [];
		$color      = $block_data['color'] ?? 'default';

		$attributes = [
			'ordered' => $is_ordered,
		];

		if ( 'default' !== $color ) {
			$attributes['className'] = $this->get_color_class( $color );
		}

		return $this->wrap_gutenberg_block( 'core/list', $html, $attributes );
	}

	/**
	 * Convert the content of a single list item.
	 *
	 * @param array $item Notion list item block.
	 * @param array $context Additional context.
	 * @return string List item HTML.
	 */
	private function convert_list_item_content( $item, $context = [] ) {
		$type       = $item['type'] ?? '';
		$block_data = $item[ $type ] ?? [];
		$rich_text  = $block_data['rich_text'] ?? [];

		$content = $this->rich_text_to_html( $rich_text );

		$html = '<li>' . $content;

		// Process nested children (e.g., nested lists, paragraphs).
		if ( ! empty( $item['children'] ) ) {
			$html .= $this->process_children( $item['children'], $context );
		}

		$html .= '</li>';

		return $html;
	}
}
