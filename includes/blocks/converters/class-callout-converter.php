<?php
/**
 * Callout Block Converter.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;

defined( 'ABSPATH' ) || exit;

/**
 * Converts Notion callout blocks to Gutenberg group/notice blocks.
 */
class Callout_Converter extends Abstract_Block_Converter {

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block ) {
		return isset( $block['type'] ) && 'callout' === $block['type'];
	}

	/**
	 * Convert Notion paragraph block to Gutenberg paragraph.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		$block_data = $block['callout'] ?? [];
		$rich_text  = $block_data['rich_text'] ?? [];
		$icon       = $block_data['icon'] ?? [];
		$color      = $block_data['color'] ?? 'default';

		$icon_html = '';
		if ( isset( $icon['emoji'] ) ) {
			$icon_html = esc_html( $icon['emoji'] );
		}

		$content = $icon_html . $this->rich_text_to_html( $rich_text );

		// TODO: Support attributes like backgroundColor, textColor, etc.

		// Using core/group as closest equivalent.
		$html = '<div class="wp-block-group notion-callout">';
		$html .= '<!-- wp:paragraph --><p>';
		$html .= $content;

		if ( ! empty( $block['children'] ) ) {
			$html .= $this->process_children( $block['children'], $context );
		}

		$html .= '</p><!-- /wp:paragraph -->';
		$html .= '</div>';

		return $this->wrap_gutenberg_block( 'core/group', $html );
	}
}
