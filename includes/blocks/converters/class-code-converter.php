<?php
/**
 * Code Block Converter.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;

defined( 'ABSPATH' ) || exit;

/**
 * Converts Notion code blocks to Gutenberg code blocks.
 */
class Code_Converter extends Abstract_Block_Converter {

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block ) {
		return isset( $block['type'] ) && 'code' === $block['type'];
	}

	/**
	 * Convert Notion paragraph block to Gutenberg paragraph.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		$block_data = $block['code'] ?? [];
		$code       = $this->extract_plain_text( $block_data['rich_text'] ?? [] );
		$language   = $block_data['language'] ?? 'plaintext';
		$caption    = $this->rich_text_to_html( $block_data['caption'] ?? [] );

		$html = '<pre class="wp-block-code"><code>' . esc_html( $code ) . '</code></pre>';

		$attributes = [];
		if ( ! empty( $language ) && 'plaintext' !== $language ) {
			$attributes['language'] = $language;
		}

		return $this->wrap_gutenberg_block( 'core/code', $html, $attributes );
	}
}
