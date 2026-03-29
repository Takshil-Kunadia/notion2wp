<?php
/**
 * Audio Block Converter.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;
use Notion2WP\Blocks\Block_Types;

defined( 'ABSPATH' ) || exit;

/**
 * Converts Notion audio blocks to Gutenberg audio blocks.
 */
class Audio_Converter extends Abstract_Block_Converter {

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block ) {
		return isset( $block['type'] ) && Block_Types::AUDIO === $block['type'];
	}

	/**
	 * Convert Notion paragraph block to Gutenberg paragraph.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		$block_data  = $block[ Block_Types::AUDIO ] ?? [];
		$type        = $block_data['type'] ?? '';
		$url         = '';
		$expiry_time = null;

		if ( 'external' === $type && ! empty( $block_data['external']['url'] ) ) {
			$url = $block_data['external']['url'];
		} elseif ( 'file' === $type && ! empty( $block_data['file']['url'] ) ) {
			$url         = $block_data['file']['url'];
			$expiry_time = $block_data['file']['expiry_time'] ?? null;
		}

		if ( empty( $url ) ) {
			return '';
		}

		$url = $this->resolve_media_url( $url, $type, Block_Types::AUDIO, $expiry_time );

		$html = '<figure class="wp-block-audio"><audio controls src="' . $this->escape_media_url( $url ) . '"></audio></figure>';

		return $this->wrap_gutenberg_block( 'core/audio', $html, [ 'src' => $url ] );
	}
}
