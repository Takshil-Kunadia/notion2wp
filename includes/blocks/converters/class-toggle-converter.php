<?php
/**
 * Toggle Block Converter.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;

defined( 'ABSPATH' ) || exit;

/**
 * Converts Notion toggle blocks to Gutenberg details blocks.
 */
class Toggle_Converter extends Abstract_Block_Converter {

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block ) {
		return isset( $block['type'] ) && 'toggle' === $block['type'];
	}

	/**
	 * Convert Notion paragraph block to Gutenberg paragraph.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		$block_data = $block['toggle'] ?? [];
		$rich_text  = $block_data['rich_text'] ?? [];
		$summary    = $this->rich_text_to_html( $rich_text );

		$html = '<details class="wp-block-details"><summary>' . $summary . '</summary>';

		if ( ! empty( $block['children'] ) ) {
			$html .= $this->process_children( $block['children'], $context );
		}

		$html .= '</details>';

		return $this->wrap_gutenberg_block( 'core/details', $html );
	}
}
