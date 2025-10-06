<?php
/**
 * Unsupported Block Converter (Fallback).
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;

defined( 'ABSPATH' ) || exit;

/**
 * Fallback converter for unsupported Notion blocks.
 */
class Unsupported_Converter extends Abstract_Block_Converter {

	/**
	 * Priority (lowest - this is the fallback).
	 *
	 * @var int
	 */
	protected $priority = 1;

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block ) {
		// This converter supports all blocks (acts as fallback).
		return true;
	}

	/**
	 * Convert Notion paragraph block to Gutenberg paragraph.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] ) {
		$type = $block['type'] ?? 'unknown';
		
		// Create a comment indicating the unsupported block type.
		return '<!-- Unsupported Notion block type: ' . esc_html( $type ) . ' -->' . "\n";
	}
}
