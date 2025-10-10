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
	 * Page property handler.
	 *
	 * @var Page_Property_Handler
	 */
	private $property_handler;

	/**
	 * Constructor.
	 *
	 * @param Notion_Client $notion_client Notion API client instance.
	 */
	public function __construct( $notion_client = null ) {
		$this->notion_client    = $notion_client ?? new Notion_Client();
		$this->property_handler = new Page_Property_Handler();
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
			'title'            => $this->property_handler->extract_title( $item ),
			'url'              => $item['url'] ?? '',
			'created_time'     => $item['created_time'] ?? '',
			'last_edited_time' => $item['last_edited_time'] ?? '',
			'archived'         => $item['archived'] ?? false,
			'media'            => Page_Property_Handler::extract_file_url( $item['cover'] ) ?? null,
		];

		// Add database-specific fields.
		if ( 'database' === $item['object'] ) {
			$formatted['description'] = $this->property_handler->extract_description( $item );
			$formatted['properties']  = $this->property_handler->get_database_properties( $item );
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
	 * Import selected Notion items (pages and/or databases).
	 *
	 * @param array $items Array of items with 'id' and 'type' keys.
	 * @return array Import results with success/error status for each item.
	 */
	public function import_items( $items ) {
		if ( ! is_array( $items ) || empty( $items ) ) {
			return new \WP_Error( 'invalid_input', __( 'No items selected for import.', 'notion2wp' ) );
		}

		$results = [
			'success' => [],
			'errors'  => [],
		];

		foreach ( $items as $item ) {
			$item_id   = $item['id'] ?? '';
			$item_type = $item['type'] ?? '';

			if ( empty( $item_id ) || empty( $item_type ) ) {
				$results['errors'][] = [
					'page_id' => $item_id,
					'message' => __( 'Invalid item: missing ID or type.', 'notion2wp' ),
				];
				continue;
			}

			// Handle based on type provided from frontend.
			if ( 'database' === $item_type ) {
				$result = $this->import_database( $item_id );
			} elseif ( 'data_source' === $item_type ) {
				$result = $this->import_data_source( $item_id );
			} else {
				$result = $this->import_page( $item_id );
			}

			if ( is_wp_error( $result ) ) {
				$results['errors'][] = [
					'page_id' => $item_id,
					'message' => $result->get_error_message(),
				];
			} elseif ( is_array( $result ) ) {
				// For databases, $result is an array of imported pages.
				foreach ( $result as $page_result ) {
					if ( is_wp_error( $page_result ) ) {
						$results['errors'][] = [
							'page_id' => $page_result->get_error_data( 'page_id' ) ?? $item_id,
							'message' => $page_result->get_error_message(),
						];
					} else {
						$results['success'][] = $page_result;
					}
				}
			} else {
				$results['success'][] = [
					'page_id' => $item_id,
					'post_id' => $result,
				];
			}
		}

		return $results;
	}

	/**
	 * Import a Notion database by querying all its pages.
	 *
	 * @param string $database_id Notion database ID.
	 * @return array Array of import results for each page in the database.
	 */
	private function import_database( $database_id ) {
		// Query the database for all pages.
		$pages = $this->notion_client->query_database( $database_id );

		if ( is_wp_error( $pages ) ) {
			return $pages;
		}

		if ( empty( $pages ) || ! isset( $pages['results'] ) ) {
			return new \WP_Error(
				'empty_database',
				sprintf(
					/* translators: %s: Database ID */
					__( 'No pages found in database %s or database is not shared with the integration.', 'notion2wp' ),
					$database_id
				)
			);
		}

		$results = [];

		// Import each page from the database.
		foreach ( $pages['results'] as $page ) {
			$page_id = $page['id'] ?? '';

			if ( empty( $page_id ) ) {
				continue;
			}

			$result = $this->import_page( $page_id );

			if ( is_wp_error( $result ) ) {
				$result->add_data( $page_id, 'page_id' );
				$results[] = $result;
			} else {
				$results[] = [
					'page_id' => $page_id,
					'post_id' => $result,
				];
			}
		}

		return $results;
	}

	/**
	 * Import all pages from a Notion data source (workspace).
	 *
	 * @param string $data_source_id Notion data source ID (usually 'workspace').
	 * @return array Array of import results for each page in the data source.
	 */
	private function import_data_source( $data_source_id ) {
		// Query the datasource for all pages.
		$pages = $this->notion_client->query_datasource( $data_source_id );

		if ( is_wp_error( $pages ) ) {
			return $pages;
		}

		if ( empty( $pages ) || ! isset( $pages['results'] ) ) {
			return new \WP_Error(
				'empty_datasource',
				sprintf(
					/* translators: %s: Data source ID */
					__( 'No pages found in data source %s or data source is not shared with the integration.', 'notion2wp' ),
					$data_source_id
				)
			);
		}

		$results = [];

		// Import each page from the data source.
		foreach ( $pages['results'] as $page ) {
			$page_id = $page['id'] ?? '';

			if ( empty( $page_id ) ) {
				continue;
			}

			$result = $this->import_page( $page_id );

			if ( is_wp_error( $result ) ) {
				$result->add_data( $page_id, 'page_id' );
				$results[] = $result;
			} else {
				$results[] = [
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
	private function import_page( $page_id ) {
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

		// Handle cover image as featured image.
		if ( ! empty( $page['cover'] ) ) {
			$this->property_handler->handle_cover_image( $post_id, $page['cover'] );
		}

		// Handle page icon.
		if ( ! empty( $page['icon'] ) ) {
			$this->property_handler->handle_icon( $post_id, $page['icon'] );
		}

		// Extract and store page metadata.
		$metadata = $this->property_handler->extract_metadata( $page );
		$this->property_handler->store_metadata( $post_id, $metadata );

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
		$title   = $this->property_handler->extract_title( $page );
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
