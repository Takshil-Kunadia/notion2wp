<?php
/**
 * List Block Converter.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;
use Notion2WP\Blocks\Block_Registry;

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
		// Support both grouped lists (top-level) and individual items.
		return in_array( $type, [ 'bulleted_list_item', 'numbered_list_item' ], true );
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
		$is_nested = ! empty( $context['is_nested_list'] );

		// Check if this is a grouped list.
		if ( isset( $block['is_grouped'] ) && $block['is_grouped'] ) {
			return $this->convert_grouped_list( $block, $context, $is_nested );
		}

		// Individual list item (used for nested children).
		return $this->convert_single_item( $block, $context, $is_nested );
	}

	/**
	 * Convert a grouped list (multiple list items).
	 *
	 * @param array $grouped_block Grouped list block.
	 * @param array $context Additional context.
	 * @param bool  $is_nested Whether this is a nested list.
	 * @return string Gutenberg block HTML.
	 */
	private function convert_grouped_list( $grouped_block, $context = [], $is_nested = false ) {
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

		// For nested lists, return plain HTML without Gutenberg block wrapper.
		if ( $is_nested ) {
			return $html;
		}

		return $this->wrap_gutenberg_block( $block_name, $html, $attributes );
	}

	/**
	 * Convert a single list item (for nested children).
	 *
	 * @param array $block Notion list item block.
	 * @param array $context Additional context.
	 * @param bool  $is_nested Whether this is a nested list.
	 * @return string List item HTML (without wrapping list tags).
	 */
	private function convert_single_item( $block, $context = [], $is_nested = false ) {
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

		// For nested lists, return plain HTML without Gutenberg block wrapper.
		if ( $is_nested ) {
			return $html;
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

		// Handle empty list items gracefully.
		if ( empty( trim( wp_strip_all_tags( $content ) ) ) ) {
			$content = '&nbsp;';
		}

		$html = '<li>' . $content;

		// Process nested children (e.g., nested lists, paragraphs).
		if ( ! empty( $item['children'] ) ) {
			$children = $item['children'];

			// Group consecutive list items in children.
			$grouped_children = $this->group_list_children( $children );

			// Mark children as nested to avoid Gutenberg block wrappers.
			$nested_context             = $context;
			$nested_context['is_nested_list'] = true;

			// Process grouped children.
			$registry = Block_Registry::get_instance();
			foreach ( $grouped_children as $child_block ) {
				$html .= $registry->convert_block( $child_block, $nested_context );
			}
		}

		$html .= '</li>';

		return $html;
	}

	/**
	 * Group consecutive list items in children.
	 * This ensures nested lists render as a single list, not multiple separate lists.
	 *
	 * @param array $children Child blocks.
	 * @return array Grouped children.
	 */
	private function group_list_children( $children ) {
		$grouped = [];
		$i       = 0;
		$count   = count( $children );

		while ( $i < $count ) {
			$child = $children[ $i ];
			$type  = $child['type'] ?? '';

			// Check if this is a list item.
			if ( in_array( $type, [ 'bulleted_list_item', 'numbered_list_item' ], true ) ) {
				// Collect consecutive list items of the same type.
				$list_items = [ $child ];
				$i++;

				while ( $i < $count && isset( $children[ $i ]['type'] ) && $children[ $i ]['type'] === $type ) {
					$list_items[] = $children[ $i ];
					$i++;
				}

				// Create a grouped list block.
				$grouped[] = [
					'type'       => $type,
					'list_items' => $list_items,
					'is_grouped' => true,
				];
			} else {
				// Not a list item, add as-is.
				$grouped[] = $child;
				$i++;
			}
		}

		return $grouped;
	}
}
