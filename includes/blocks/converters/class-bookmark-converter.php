<?php
/**
 * Bookmark Block Converter.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;

defined( 'ABSPATH' ) || exit;

/**
 * Converts Notion bookmark blocks to Gutenberg embeds.
 */
class Bookmark_Converter extends Abstract_Block_Converter {

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block ) {
		return isset( $block['type'] ) && 'bookmark' === $block['type'];
	}

	/**
	 * Convert Notion paragraph block to Gutenberg paragraph.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		$block_data = $block['bookmark'] ?? [];
		$url        = $block_data['url'] ?? '';
		$caption    = $this->rich_text_to_html( $block_data['caption'] ?? [] );

		if ( empty( $url ) ) {
			return '';
		}

		$html = '<figure class="wp-block-embed"><div class="wp-block-embed__wrapper">';
		$html .= esc_url( $url );
		$html .= '</div>';

		if ( ! empty( $caption ) ) {
			$html .= '<figcaption class="wp-element-caption">' . $caption . '</figcaption>';
		}

		$html .= '</figure>';

		return $this->wrap_gutenberg_block( 'core/embed', $html, [ 'url' => $url ] );
	}
}
