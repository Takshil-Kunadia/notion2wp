<?php
/**
 * Notion Importer Controller.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Importer;

use Notion2WP\Adapter\Notion_Client;
use Notion2WP\Admin\Settings;
use Notion2WP\Blocks\Block_Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Importer Controller class for managing import operations.
 */
class Importer_Controller {

	/**
	 * Notion API client.
	 *
	 * @var Notion_Client
	 */
	private $notion_client;

	/**
	 * Constructor.
	 *
	 * @param Notion_Client $notion_client Notion API client instance.
	 */
	public function __construct( $notion_client = null ) {
		$this->notion_client = $notion_client ?? new Notion_Client();
	}

	/**
	 * Get a list of importable items (pages and databases).
	 *
	 * @return array|\WP_Error
	 */
	public function get_importable_items() {
		$items = $this->notion_client->list_all_pages_and_databases();

		if ( is_wp_error( $items ) ) {
			return $items;
		}

		// Format items for frontend consumption.
		$formatted_items = [];

		foreach ( $items as $item ) {
			$formatted_item = $this->format_item_for_display( $item );
			if ( $formatted_item ) {
				$formatted_items[] = $formatted_item;
			}
		}

		return $formatted_items;
	}

	/**
	 * Format an item (page or database) for display.
	 *
	 * @param array $item Notion item (page or database).
	 * @return array|null
	 */
	private function format_item_for_display( $item ) {
		if ( empty( $item['object'] ) ) {
			return null;
		}

		$formatted = [
			'id'               => $item['id'] ?? '',
			'type'             => $item['object'] ?? '',
			'title'            => $this->extract_title( $item ),
			'url'              => $item['url'] ?? '',
			'created_time'     => $item['created_time'] ?? '',
			'last_edited_time' => $item['last_edited_time'] ?? '',
			'archived'         => $item['archived'] ?? false,
		];

		// Add database-specific fields.
		if ( 'database' === $item['object'] ) {
			$formatted['description'] = $this->extract_description( $item );
			$formatted['properties']  = $this->get_database_property_names( $item );
		}

		// Add parent information.
		if ( ! empty( $item['parent'] ) ) {
			$formatted['parent'] = [
				'type' => $item['parent']['type'] ?? '',
				'id'   => $this->get_parent_id( $item['parent'] ),
			];
		}

		return $formatted;
	}

	/**
	 * Extract title from Notion item.
	 *
	 * @param array $item Notion item.
	 * @return string
	 */
	private function extract_title( $item ) {
		// For pages.
		if ( 'page' === $item['object'] && ! empty( $item['properties'] ) ) {
			foreach ( $item['properties'] as $property ) {
				if ( isset( $property['type'] ) && 'title' === $property['type'] ) {
					return $this->extract_rich_text( $property['title'] ?? [] );
				}
			}
		}

		// For databases.
		if ( 'database' === $item['object'] && ! empty( $item['title'] ) ) {
			return $this->extract_rich_text( $item['title'] );
		}

		return __( '(Untitled)', 'notion2wp' );
	}

	/**
	 * Extract description from database.
	 *
	 * @param array $item Database item.
	 * @return string
	 */
	private function extract_description( $item ) {
		if ( ! empty( $item['description'] ) ) {
			return $this->extract_rich_text( $item['description'] );
		}
		return '';
	}

	/**
	 * Extract plain text from rich text array.
	 *
	 * @param array $rich_text Rich text array from Notion.
	 * @return string
	 */
	private function extract_rich_text( $rich_text ) {
		if ( ! is_array( $rich_text ) ) {
			return '';
		}

		$text = '';
		foreach ( $rich_text as $text_item ) {
			if ( isset( $text_item['plain_text'] ) ) {
				$text .= $text_item['plain_text'];
			}
		}

		return $text;
	}

	/**
	 * Get database property names.
	 *
	 * @param array $database Database item.
	 * @return array
	 */
	private function get_database_property_names( $database ) {
		$properties = [];

		if ( ! empty( $database['properties'] ) ) {
			foreach ( $database['properties'] as $name => $property ) {
				$properties[] = [
					'name' => $name,
					'type' => $property['type'] ?? '',
				];
			}
		}

		return $properties;
	}

	/**
	 * Get parent ID from parent object.
	 *
	 * @param array $parent Parent object.
	 * @return string
	 */
	private function get_parent_id( $parent ) {
		$type = $parent['type'] ?? '';

		switch ( $type ) {
			case 'database_id':
				return $parent['database_id'] ?? '';
			case 'page_id':
				return $parent['page_id'] ?? '';
			case 'workspace':
				return 'workspace';
			default:
				return '';
		}
	}

	/**
	 * Import selected Notion pages.
	 *
	 * @param array $page_ids Array of Notion page IDs to import.
	 * @return array Import results with success/error status for each page.
	 */
	public function import_pages( $page_ids ) {
		if ( ! is_array( $page_ids ) || empty( $page_ids ) ) {
			return new \WP_Error( 'invalid_input', __( 'No pages selected for import.', 'notion2wp' ) );
		}

		$results = [
			'success' => [],
			'errors'  => [],
		];

		foreach ( $page_ids as $page_id ) {
			$result = $this->import_single_page( $page_id );

			if ( is_wp_error( $result ) ) {
				$results['errors'][] = [
					'page_id' => $page_id,
					'message' => $result->get_error_message(),
				];
			} else {
				$results['success'][] = [
					'page_id' => $page_id,
					'post_id' => $result,
				];
			}
		}

		return $results;
	}

	/**
	 * Import a single Notion page.
	 *
	 * @param string $page_id Notion page ID.
	 * @return int|\WP_Error WordPress post ID on success, WP_Error on failure.
	 */
	private function import_single_page( $page_id ) {
		// Fetch page metadata.
		$page = $this->notion_client->get_page( $page_id );

		if ( is_wp_error( $page ) ) {
			return $page;
		}

		// Fetch page content (blocks).
		$blocks = $this->notion_client->get_all_block_children( $page_id );

		if ( is_wp_error( $blocks ) ) {
			return $blocks;
		}

		// Convert to WordPress post format.
		$post_data = $this->convert_to_wordpress_post( $page, $blocks );

		// Create/update WordPress post.
		$post_id = $this->create_or_update_post( $post_data, $page_id );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Store mapping between Notion page and WordPress post.
		$this->store_notion_mapping( $page_id, $post_id );

		return $post_id;
	}

	/**
	 * Convert Notion page and blocks to WordPress post data.
	 *
	 * @param array $page Notion page object.
	 * @param array $blocks Notion blocks array.
	 * @return array WordPress post data.
	 */
	private function convert_to_wordpress_post( $page, $blocks ) {
		$title   = $this->extract_title( $page );
		$content = $this->blocks_to_html( $blocks );

		// Get settings for defaults.
		$settings = Settings::get_settings();

		$post_data = [
			'post_title'   => sanitize_text_field( $title ),
			'post_content' => wp_kses_post( $content ),
			'post_status'  => $settings['default_post_status'] ?? 'draft',
			'post_type'    => $settings['default_post_type'] ?? 'post',
			'post_author'  => $settings['default_author_id'] ?? get_current_user_id(),
		];

		// Add created/modified dates if available.
		if ( ! empty( $page['created_time'] ) ) {
			$post_data['post_date'] = gmdate( 'Y-m-d H:i:s', strtotime( $page['created_time'] ) );
		}

		if ( ! empty( $page['last_edited_time'] ) ) {
			$post_data['post_modified'] = gmdate( 'Y-m-d H:i:s', strtotime( $page['last_edited_time'] ) );
		}

		return $post_data;
	}

	/**
	 * Convert Notion blocks to HTML using the block registry.
	 *
	 * @param array $blocks Notion blocks array.
	 * @return string HTML content.
	 */
	private function blocks_to_html( $blocks ) {
		// Load block system.
		require_once NOTION2WP_ABSPATH . 'includes/blocks/interface-block-converter.php';
		require_once NOTION2WP_ABSPATH . 'includes/blocks/class-abstract-block-converter.php';
		require_once NOTION2WP_ABSPATH . 'includes/blocks/class-block-registry.php';

		// Get the block registry instance.
		$registry = Block_Registry::get_instance();

		// Convert all blocks.
		return $registry->convert_blocks( $blocks );
	}

	/**
	 * Create or update WordPress post.
	 *
	 * @param array  $post_data WordPress post data.
	 * @param string $page_id Notion page ID.
	 * @return int|\WP_Error Post ID on success, WP_Error on failure.
	 */
	private function create_or_update_post( $post_data, $page_id ) {
		// Check if post already exists.
		$existing_post_id = $this->get_post_by_notion_id( $page_id );

		if ( $existing_post_id ) {
			$post_data['ID'] = $existing_post_id;
			$result          = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		return $result;
	}

	/**
	 * Store mapping between Notion page and WordPress post.
	 *
	 * @param string $page_id Notion page ID.
	 * @param int    $post_id WordPress post ID.
	 * @return bool
	 */
	private function store_notion_mapping( $page_id, $post_id ) {
		update_post_meta( $post_id, '_notion_page_id', sanitize_text_field( $page_id ) );
		update_post_meta( $post_id, '_notion_synced_at', current_time( 'mysql' ) );

		return true;
	}

	/**
	 * Get WordPress post ID by Notion page ID.
	 *
	 * @param string $page_id Notion page ID.
	 * @return int|null Post ID or null if not found.
	 */
	private function get_post_by_notion_id( $page_id ) {
		$args = [
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				[
					'key'   => '_notion_page_id',
					'value' => $page_id,
				],
			],
			'fields'         => 'ids',
		];

		$posts = get_posts( $args );

		return ! empty( $posts ) ? $posts[0] : null;
	}
}
