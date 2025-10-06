<?php
/**
 * Heading Block Converter.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;

defined( 'ABSPATH' ) || exit;

/**
 * Converts Notion heading blocks to Gutenberg headings.
 */
class Heading_Converter extends Abstract_Block_Converter {

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block ) {
		$type = $block['type'] ?? '';
		return in_array( $type, [ 'heading_1', 'heading_2', 'heading_3' ], true );
	}

	/**
	 * Convert Notion heading block to Gutenberg heading.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		$type       = $block['type'] ?? '';
		$level      = (int) substr( $type, -1 ); // Extract level from heading_1, heading_2, heading_3.
		$block_data = $block[ $type ] ?? [];
		$rich_text  = $block_data['rich_text'] ?? [];
		$color      = $block_data['color'] ?? 'default';
		$toggleable = $block_data['is_toggleable'] ?? false;

		$content = $this->rich_text_to_html( $rich_text );

		$html = '<h' . $level . '>' . $content . '</h' . $level . '>';

		// Process children if toggleable.
		if ( $toggleable && ! empty( $block['children'] ) ) {
			$html .= $this->process_children( $block['children'], $context );
		}

		$attributes = [
			'level' => $level,
		];

		if ( 'default' !== $color ) {
			$attributes['className'] = $this->get_color_class( $color );
		}

		return $this->wrap_gutenberg_block( 'core/heading', $html, $attributes );
	}
}
