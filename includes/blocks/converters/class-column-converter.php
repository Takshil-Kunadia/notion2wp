<?php
/**
 * Column Block Converter.
 *
 * Handles conversion of Notion column_list and column blocks
 * to WordPress Gutenberg columns layout.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;
use Notion2WP\Blocks\Block_Types;

defined( 'ABSPATH' ) || exit;

/**
 * Converts Notion column_list blocks to Gutenberg columns blocks.
 *
 * Notion structure:
 * - column_list (parent container, empty property object)
 *   - column (child 1, optional width_ratio between 0-1)
 *     - [content blocks]
 *   - column (child 2, optional width_ratio between 0-1)
 *     - [content blocks]
 *
 * WordPress Gutenberg structure:
 * - core/columns
 *   - core/column (with optional width attribute as percentage)
 *     - [content blocks]
 *   - core/column
 *     - [content blocks]
 *
 * @see https://developers.notion.com/reference/block#column-list-and-column
 */
class Column_Converter extends Abstract_Block_Converter {

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block ) {
		return isset( $block['type'] ) && Block_Types::COLUMN_LIST === $block['type'];
	}

	/**
	 * Convert Notion column_list block to Gutenberg columns.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		$columns_html = '';

		// Process children (column blocks).
		if ( ! empty( $block['children'] ) && is_array( $block['children'] ) ) {
			foreach ( $block['children'] as $column_block ) {
				if ( isset( $column_block['type'] ) && Block_Types::COLUMN === $column_block['type'] ) {
					$columns_html .= $this->convert_single_column( $column_block, $context );
				}
			}
		}

		// Build outer columns container.
		$columns_content = '<div class="wp-block-columns">' . $columns_html . '</div>';

		return $this->wrap_gutenberg_block( 'core/columns', $columns_content );
	}

	/**
	 * Convert a single Notion column block to Gutenberg column.
	 *
	 * @param array $column_block Notion column block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg column block HTML.
	 */
	private function convert_single_column( $column_block, $context = [] ) {
		$inner_html  = '';
		$column_data = $column_block[ Block_Types::COLUMN ] ?? [];
		$attributes  = [];

		// Extract width_ratio if present (Notion uses 0-1 ratio, Gutenberg uses percentage string).
		if ( isset( $column_data['width_ratio'] ) && is_numeric( $column_data['width_ratio'] ) ) {
			$width_ratio = floatval( $column_data['width_ratio'] );
			// Convert ratio (0-1) to percentage string (e.g., "25%").
			$width_percent       = round( $width_ratio * 100, 2 );
			$attributes['width'] = $width_percent . '%';
		}

		// Process column children (actual content blocks).
		if ( ! empty( $column_block['children'] ) ) {
			$inner_html = $this->process_children( $column_block['children'], $context );
		}

		// Build style attribute for width if specified.
		$style_attr = '';
		if ( ! empty( $attributes['width'] ) ) {
			$style_attr = ' style="flex-basis:' . esc_attr( $attributes['width'] ) . '"';
		}

		// Wrap in column div.
		$column_content = '<div class="wp-block-column"' . $style_attr . '>' . $inner_html . '</div>';

		return $this->wrap_gutenberg_block( 'core/column', $column_content, $attributes );
	}
}
