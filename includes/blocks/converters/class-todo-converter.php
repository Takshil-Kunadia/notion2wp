<?php
/**
 * Todo Block Converter.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;
use Notion2WP\Blocks\Block_Registry;
use Notion2WP\Blocks\Block_Types;

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
		$type = $block['type'] ?? '';
		// Support both grouped to-do lists and individual items.
		return Block_Types::TO_DO === $type;
	}

	/**
	 * Convert Notion to_do block(s) to Gutenberg list.
	 *
	 * Handles both:
	 * - Grouped to-do lists (multiple consecutive to-do items)
	 * - Individual to-do items (for nested children)
	 *
	 * @param array $block Notion block object or grouped to-do list.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		$is_nested = ! empty( $context['is_nested_list'] );

		// Check if this is a grouped to-do list.
		if ( isset( $block['is_grouped'] ) && $block['is_grouped'] ) {
			return $this->convert_grouped_todos( $block, $context, $is_nested );
		}

		// Individual to-do item (used for nested children).
		return $this->convert_single_todo( $block, $context, $is_nested );
	}

	/**
	 * Convert a grouped to-do list (multiple to-do items).
	 *
	 * @param array $grouped_block Grouped to-do list block.
	 * @param array $context Additional context.
	 * @param bool  $is_nested Whether this is a nested list.
	 * @return string Gutenberg block HTML.
	 */
	private function convert_grouped_todos( $grouped_block, $context = [], $is_nested = false ) {
		$list_items = $grouped_block['list_items'] ?? [];

		if ( empty( $list_items ) ) {
			return '';
		}

		// Build the to-do list HTML.
		$html = '<ul>';

		foreach ( $list_items as $item ) {
			$html .= $this->convert_todo_item_content( $item, $context );
		}

		$html .= '</ul>';

		// For nested lists, return plain HTML without Gutenberg block wrapper.
		if ( $is_nested ) {
			return $html;
		}

		return $this->wrap_gutenberg_block( 'core/list', $html );
	}

	/**
	 * Convert a single to-do item (for nested children).
	 *
	 * @param array $block Notion to-do block.
	 * @param array $context Additional context.
	 * @param bool  $is_nested Whether this is a nested list.
	 * @return string To-do item HTML.
	 */
	private function convert_single_todo( $block, $context = [], $is_nested = false ) {
		$html = '<ul>';
		$html .= $this->convert_todo_item_content( $block, $context );
		$html .= '</ul>';

		// For nested lists, return plain HTML without Gutenberg block wrapper.
		if ( $is_nested ) {
			return $html;
		}

		return $this->wrap_gutenberg_block( 'core/list', $html );
	}

	/**
	 * Convert the content of a single to-do item.
	 *
	 * @param array $item Notion to-do block.
	 * @param array $context Additional context.
	 * @return string To-do item HTML.
	 */
	private function convert_todo_item_content( $item, $context = [] ) {
		$block_data = $item['to_do'] ?? [];
		$rich_text  = $block_data['rich_text'] ?? [];
		$checked    = $block_data['checked'] ?? false;
		$content    = $this->rich_text_to_html( $rich_text );

		// Handle empty to-do items gracefully.
		if ( empty( trim( wp_strip_all_tags( $content ) ) ) ) {
			$content = '&nbsp;';
		}

		$checkbox = $checked ? '☑' : '☐';
		$html     = '<li>' . $checkbox . ' ' . $content;

		// Process nested children (e.g., nested to-dos, paragraphs).
		if ( ! empty( $item['children'] ) ) {
			$children = $item['children'];

			// Group consecutive to-do items in children.
			$grouped_children = $this->group_todo_children( $children );

			// Mark children as nested to avoid Gutenberg block wrappers.
			$nested_context                    = $context;
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
	 * Group consecutive to-do items in children.
	 * This ensures nested to-do lists render as a single list, not multiple separate lists.
	 *
	 * @param array $children Child blocks.
	 * @return array Grouped children.
	 */
	private function group_todo_children( $children ) {
		$grouped = [];
		$i       = 0;
		$count   = count( $children );

		while ( $i < $count ) {
			$child = $children[ $i ];
			$type  = $child['type'] ?? '';

			// Check if this is a to-do item or list item.
			if ( in_array( $type, Block_Types::get_groupable_list_types(), true ) ) {
				// Collect consecutive items of the same type.
				$list_items = [ $child ];
				$i++;

				while ( $i < $count && isset( $children[ $i ]['type'] ) && $children[ $i ]['type'] === $type ) {
					$list_items[] = $children[ $i ];
					$i++;
				}

				// Create a grouped block.
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
