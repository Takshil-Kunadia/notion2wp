<?php
/**
 * Video Block Converter.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;

defined( 'ABSPATH' ) || exit;

/**
 * Converts Notion video blocks to Gutenberg video/embed blocks.
 */
class Video_Converter extends Abstract_Block_Converter {

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block ) {
		return isset( $block['type'] ) && 'video' === $block['type'];
	}

	/**
	 * Convert Notion paragraph block to Gutenberg paragraph.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		$block_data = $block['video'] ?? [];
		$type       = $block_data['type'] ?? '';
		$url        = '';

		if ( 'external' === $type && ! empty( $block_data['external']['url'] ) ) {
			$url = $block_data['external']['url'];
		} elseif ( 'file' === $type && ! empty( $block_data['file']['url'] ) ) {
			$url = $block_data['file']['url'];
		}

		if ( empty( $url ) ) {
			return '';
		}

		// Check if it's a YouTube/Vimeo URL - use embed.
		if ( preg_match( '/(youtube\.com|youtu\.be|vimeo\.com)/i', $url ) ) {
			$html = '<figure class="wp-block-embed is-type-video"><div class="wp-block-embed__wrapper">';
			$html .= esc_url( $url );
			$html .= '</div></figure>';
			return $this->wrap_gutenberg_block( 'core/embed', $html, [ 'url' => $url ] );
		}

		// Regular video file.
		$html = '<figure class="wp-block-video"><video controls src="' . esc_url( $url ) . '"></video></figure>';
		return $this->wrap_gutenberg_block( 'core/video', $html, [ 'src' => $url ] );
	}
}
