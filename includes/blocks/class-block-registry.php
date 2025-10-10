<?php
/**
 * Block Registry - Manages block converters.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Block Registry class.
 * Registers and manages all block converters.
 */
class Block_Registry {

	/**
	 * Singleton instance.
	 *
	 * @var Block_Registry
	 */
	private static $instance = null;

	/**
	 * Registered converters.
	 *
	 * @var Block_Converter_Interface[]
	 */
	private $converters = [];

	/**
	 * Get singleton instance.
	 *
	 * @return Block_Registry
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to enforce singleton.
	 */
	private function __construct() {
		$this->register_default_converters();
	}

	/**
	 * Register a block converter.
	 *
	 * @param Block_Converter_Interface $converter Block converter instance.
	 */
	public function register( Block_Converter_Interface $converter ) {
		$this->converters[] = $converter;

		// Sort by priority (highest first).
		usort(
			$this->converters,
			function ( $a, $b ) {
				return $b->get_priority() - $a->get_priority();
			}
		);
	}

	/**
	 * Register default block converters.
	 */
	private function register_default_converters() {
		// Load converter classes.
		require_once __DIR__ . '/converters/class-paragraph-converter.php';
		require_once __DIR__ . '/converters/class-heading-converter.php';
		require_once __DIR__ . '/converters/class-list-converter.php';
		require_once __DIR__ . '/converters/class-quote-converter.php';
		require_once __DIR__ . '/converters/class-code-converter.php';
		require_once __DIR__ . '/converters/class-image-converter.php';
		require_once __DIR__ . '/converters/class-divider-converter.php';
		require_once __DIR__ . '/converters/class-callout-converter.php';
		require_once __DIR__ . '/converters/class-toggle-converter.php';
		require_once __DIR__ . '/converters/class-todo-converter.php';
		require_once __DIR__ . '/converters/class-table-converter.php';
		require_once __DIR__ . '/converters/class-bookmark-converter.php';
		require_once __DIR__ . '/converters/class-embed-converter.php';
		require_once __DIR__ . '/converters/class-file-converter.php';
		require_once __DIR__ . '/converters/class-video-converter.php';
		require_once __DIR__ . '/converters/class-audio-converter.php';
		require_once __DIR__ . '/converters/class-unsupported-converter.php';

		// Register converters.
		$this->register( new Converters\Paragraph_Converter() );
		$this->register( new Converters\Heading_Converter() );
		$this->register( new Converters\List_Converter() );
		$this->register( new Converters\Quote_Converter() );
		$this->register( new Converters\Code_Converter() );
		$this->register( new Converters\Image_Converter() );
		$this->register( new Converters\Divider_Converter() );
		$this->register( new Converters\Callout_Converter() );
		$this->register( new Converters\Toggle_Converter() );
		$this->register( new Converters\Todo_Converter() );
		$this->register( new Converters\Table_Converter() );
		$this->register( new Converters\Bookmark_Converter() );
		$this->register( new Converters\Embed_Converter() );
		$this->register( new Converters\File_Converter() );
		$this->register( new Converters\Video_Converter() );
		$this->register( new Converters\Audio_Converter() );
		$this->register( new Converters\Unsupported_Converter() );
	}

	/**
	 * Convert a Notion block to Gutenberg format.
	 *
	 * @param array $block Notion block object.
	 * @param array $context Additional context.
	 * @return string Gutenberg block HTML.
	 */
	public function convert_block( $block, $context = [] ) {
		foreach ( $this->converters as $converter ) {
			if ( $converter->supports( $block ) ) {
				return $converter->convert( $block, $context );
			}
		}

		// Fallback: return HTML comment.
		$type = $block['type'] ?? 'unknown';
		return '<!-- Unsupported Notion block type: ' . esc_html( $type ) . ' -->' . "\n";
	}

	/**
	 * Convert an array of blocks.
	 *
	 * @param array $blocks Array of Notion blocks.
	 * @param array $context Additional context.
	 * @return string Combined Gutenberg HTML.
	 */
	public function convert_blocks( $blocks, $context = [] ) {
		$html           = '';
		$grouped_blocks = $this->group_associated_items( $blocks );

		foreach ( $grouped_blocks as $block ) {
			$html .= $this->convert_block( $block, $context );
		}

		return $html;
	}

	/**
	 * Group consecutive associated items together.
	 *
	 * Groups consecutive list items (bulleted, numbered, to-do) of the same type
	 * so they render as a single list in Gutenberg instead of multiple separate lists.
	 *
	 * @param array $blocks Array of Notion blocks.
	 * @return array Grouped blocks.
	 */
	private function group_associated_items( $blocks ) {
		$grouped = [];
		$i       = 0;
		$count   = count( $blocks );

		while ( $i < $count ) {
			$block = $blocks[ $i ];
			$type  = $block['type'] ?? '';

			// Check if this is a groupable list item.
			if ( in_array( $type, [ 'bulleted_list_item', 'numbered_list_item', 'to_do' ], true ) ) {
				// Collect consecutive list items of the same type.
				$list_items = [ $block ];
				$i++;

				while ( $i < $count && isset( $blocks[ $i ]['type'] ) && $blocks[ $i ]['type'] === $type ) {
					$list_items[] = $blocks[ $i ];
					$i++;
				}

				// Create a grouped list block.
				$grouped[] = [
					'type'       => $type,
					'list_items' => $list_items,
					'is_grouped' => true,
				];
			} else {
				// Not a list item, add as-is.
				$grouped[] = $block;
				$i++;
			}
		}

		return $grouped;
	}
}
