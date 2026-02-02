<?php
/**
 * Block Type Constants.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Constants for Notion block types.
 */
class Block_Types {

	/**
	 * Content block types.
	 */
	const PARAGRAPH = 'paragraph';
	const HEADING_1 = 'heading_1';
	const HEADING_2 = 'heading_2';
	const HEADING_3 = 'heading_3';
	const QUOTE     = 'quote';
	const CODE      = 'code';
	const CALLOUT   = 'callout';
	const TOGGLE    = 'toggle';

	/**
	 * Media block types.
	 */
	const IMAGE    = 'image';
	const VIDEO    = 'video';
	const AUDIO    = 'audio';
	const FILE     = 'file';
	const BOOKMARK = 'bookmark';
	const EMBED    = 'embed';

	/**
	 * Layout block types.
	 */
	const COLUMN_LIST = 'column_list';
	const COLUMN      = 'column';

	/**
	 * Other block types.
	 */
	const DIVIDER   = 'divider';
	const TABLE     = 'table';
	const TABLE_ROW = 'table_row';

	/**
	 * List block types.
	 */
	const BULLETED_LIST_ITEM = 'bulleted_list_item';
	const NUMBERED_LIST_ITEM = 'numbered_list_item';
	const TO_DO              = 'to_do';

	/**
	 * Get all groupable list item types.
	 *
	 * @return array Array of groupable list item types.
	 */
	public static function get_groupable_list_types() {
		return [
			self::BULLETED_LIST_ITEM,
			self::NUMBERED_LIST_ITEM,
			self::TO_DO,
		];
	}

	/**
	 * Get all list item types (including to-do).
	 *
	 * @return array Array of list item types.
	 */
	public static function get_list_types() {
		return [
			self::BULLETED_LIST_ITEM,
			self::NUMBERED_LIST_ITEM,
		];
	}
}
