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
		$type = $block['type'] ?? '';
		// Support both individual to-do items and grouped to-do lists.
		return 'to_do' === $type &&
			( isset( $block['is_grouped'] ) && $block['is_grouped'] && $type === 'to_do' );
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
		// Check if this is a grouped to-do list.
		if ( isset( $block['is_grouped'] ) && $block['is_grouped'] ) {
			return $this->convert_grouped_todos( $block, $context );
		}

		// Individual to-do item (used for nested children).
		return $this->convert_single_todo( $block, $context );
	}

	/**
	 * Convert a grouped to-do list (multiple to-do items).
	 *
	 * @param array $grouped_block Grouped to-do list block.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	private function convert_grouped_todos( $grouped_block, $context = [] ) {
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

		return $this->wrap_gutenberg_block( 'core/list', $html );
	}

	/**
	 * Convert a single to-do item (for nested children).
	 *
	 * @param array $block Notion to-do block.
	 * @param array $context Additional context.
	 * @return string To-do item HTML.
	 */
	private function convert_single_todo( $block, $context = [] ) {
		$html = '<ul>';
		$html .= $this->convert_todo_item_content( $block, $context );
		$html .= '</ul>';

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

		$checkbox = $checked ? '☑' : '☐';
		$html     = '<li>' . $checkbox . ' ' . $content;

		// Process nested children (e.g., nested to-dos, paragraphs).
		if ( ! empty( $item['children'] ) ) {
			$html .= $this->process_children( $item['children'], $context );
		}

		$html .= '</li>';

		return $html;
	}
}
