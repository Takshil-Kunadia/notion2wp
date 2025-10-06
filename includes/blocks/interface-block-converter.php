<?php
/**
 * Block Converter Interface.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for block converters.
 * Each Notion block type should implement this interface.
 */
interface Block_Converter_Interface {

	/**
	 * Check if this converter supports the given block type.
	 *
	 * @param array $block Notion block object.
	 * @return bool
	 */
	public function supports( $block );

	/**
	 * Convert Notion block to WordPress Gutenberg block HTML.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context (parent blocks, settings, etc.).
	 * @return string Gutenberg block HTML.
	 */
	public function convert( $block, $context = [] );

	/**
	 * Get the priority of this converter (higher = earlier execution).
	 * Useful when multiple converters might match the same block.
	 *
	 * @return int
	 */
	public function get_priority();
}
