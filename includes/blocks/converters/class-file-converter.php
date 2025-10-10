<?php
/**
 * File Block Converter.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;

defined( 'ABSPATH' ) || exit;

/**
 * Converts Notion file blocks to Gutenberg file blocks.
 */
class File_Converter extends Abstract_Block_Converter {

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block ) {
		return isset( $block['type'] ) && 'file' === $block['type'];
	}

	/**
	 * Convert Notion file block to Gutenberg file block.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		$block_data = $block['file'] ?? [];
		$type       = $block_data['type'] ?? '';
		$url        = '';
		$name       = $block_data['name'] ?? 'file';

		if ( 'external' === $type && ! empty( $block_data['external']['url'] ) ) {
			$url = $block_data['external']['url'];
		} elseif ( 'file' === $type && ! empty( $block_data['file']['url'] ) ) {
			$url = $block_data['file']['url'];
		}

		if ( empty( $url ) ) {
			return '';
		}

		$html = '<div class="wp-block-file">';
		$html .= sprintf(
			'<a href="%s">%s</a>',
			$url,
			esc_html( $name )
		);
		$html .= sprintf(
			'<a href="%s" class="wp-block-file__button wp-element-button" download>%s</a>',
			rawurlencode( $url ),
			__( 'Download', 'notion2wp' )
		);
		$html .= '</div>';

		return $this->wrap_gutenberg_block( 'core/file', $html, [ 'href' => rawurlencode( $url ) ] );
	}
}
