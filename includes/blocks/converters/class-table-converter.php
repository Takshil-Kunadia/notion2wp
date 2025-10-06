<?php
/**
 * Table Block Converter.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;

defined( 'ABSPATH' ) || exit;

/**
 * Converts Notion table blocks to Gutenberg tables.
 */
class Table_Converter extends Abstract_Block_Converter {

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block ) {
		$type = $block['type'] ?? '';
		return in_array( $type, [ 'table', 'table_row' ], true );
	}

	/**
	 * Convert Notion paragraph block to Gutenberg paragraph.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		if ( 'table_row' === $block['type'] ) {
			return $this->convert_table_row( $block );
		}

		// Table block - children will be table_row blocks.
		$block_data = $block['table'] ?? [];
		$has_header = $block_data['has_column_header'] ?? false;

		$html = '<figure class="wp-block-table"><table>';

		if ( ! empty( $block['children'] ) ) {
			$rows = $block['children'];
			if ( $has_header && ! empty( $rows ) ) {
				$html .= '<thead>' . $this->convert_table_row( $rows[0], true ) . '</thead>';
				$rows  = array_slice( $rows, 1 );
			}

			if ( ! empty( $rows ) ) {
				$html .= '<tbody>';
				foreach ( $rows as $row ) {
					$html .= $this->convert_table_row( $row );
				}
				$html .= '</tbody>';
			}
		}

		$html .= '</table></figure>';

		return $this->wrap_gutenberg_block( 'core/table', $html );
	}

	/**
	 * Convert table row.
	 *
	 * @param array $row Table row block.
	 * @param bool  $is_header Whether this is a header row.
	 * @return string
	 */
	private function convert_table_row( $row, $is_header = false ) {
		$row_data = $row['table_row'] ?? [];
		$cells    = $row_data['cells'] ?? [];
		$tag      = $is_header ? 'th' : 'td';

		$html = '<tr>';
		foreach ( $cells as $cell ) {
			$content = $this->rich_text_to_html( $cell );
			$html   .= '<' . $tag . '>' . $content . '</' . $tag . '>';
		}
		$html .= '</tr>';

		return $html;
	}
}
