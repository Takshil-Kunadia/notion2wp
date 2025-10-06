<?php
/**
 * Notion API Client.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Adapter;

use Notion2WP\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Notion API Client class for interacting with Notion API.
 */
class Notion_Client {

	/**
	 * Notion API base URL.
	 */
	const API_BASE_URL = 'https://api.notion.com/v1';

	/**
	 * Notion API version.
	 */
	const API_VERSION = '2025-09-03';

	/**
	 * Access token.
	 *
	 * @var string
	 */
	private $access_token;

	/**
	 * Constructor.
	 *
	 * @param string $access_token Optional access token. If not provided, will get from settings.
	 */
	public function __construct( $access_token = null ) {
		if ( $access_token ) {
			$this->access_token = $access_token;
		} else {
			$settings           = Settings::get_settings();
			$this->access_token = $settings['access_token'] ?? '';
		}
	}

	/**
	 * Make API request to Notion.
	 *
	 * @param string $endpoint API endpoint.
	 * @param string $method HTTP method (GET, POST, PATCH, DELETE).
	 * @param array  $body Request body for POST/PATCH requests.
	 * @return array|\WP_Error
	 */
	private function make_request( $endpoint, $method = 'GET', $body = null ) {
		if ( empty( $this->access_token ) ) {
			return new \WP_Error( 'no_token', __( 'No access token available. Please authenticate with Notion first.', 'notion2wp' ) );
		}

		$url = self::API_BASE_URL . $endpoint;

		$args = [
			'method'  => $method,
			'timeout' => 30, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			'headers' => [
				'Authorization'  => 'Bearer ' . $this->access_token,
				'Notion-Version' => self::API_VERSION,
				'Content-Type'   => 'application/json',
			],
		];

		if ( $body && ( 'POST' === $method || 'PATCH' === $method ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( $status_code >= 400 ) {
			$error_message = $data['message'] ?? __( 'Unknown error occurred.', 'notion2wp' );
			return new \WP_Error( 'notion_api_error', $error_message, [ 'status' => $status_code ] );
		}

		return $data;
	}

	/**
	 * Search for pages and databases.
	 *
	 * @param array  $filter Optional filter criteria.
	 * @param array  $sort Optional sort criteria.
	 * @param int    $page_size Number of results per page (max 100).
	 * @param string $start_cursor Pagination cursor.
	 * @return array|\WP_Error
	 */
	public function search( $filter = [], $sort = [], $page_size = 100, $start_cursor = null ) {
		$body = [
			'page_size' => min( $page_size, 100 ),
		];

		if ( ! empty( $filter ) ) {
			$body['filter'] = $filter;
		}

		if ( ! empty( $sort ) ) {
			$body['sort'] = $sort;
		}

		if ( $start_cursor ) {
			$body['start_cursor'] = $start_cursor;
		}

		return $this->make_request( '/search', 'POST', $body );
	}

	/**
	 * Retrieve a page by ID.
	 *
	 * @param string $page_id Page ID.
	 * @return array|\WP_Error
	 */
	public function get_page( $page_id ) {
		return $this->make_request( '/pages/' . $page_id );
	}

	/**
	 * Retrieve a database by ID.
	 *
	 * @param string $database_id Database ID.
	 * @return array|\WP_Error
	 */
	public function get_database( $database_id ) {
		return $this->make_request( '/databases/' . $database_id );
	}

	/**
	 * Query a database.
	 *
	 * @param string $database_id Database ID.
	 * @param array  $filter Optional filter criteria.
	 * @param array  $sorts Optional sort criteria.
	 * @param int    $page_size Number of results per page (max 100).
	 * @param string $start_cursor Pagination cursor.
	 * @return array|\WP_Error
	 */
	public function query_database( $database_id, $filter = [], $sorts = [], $page_size = 100, $start_cursor = null ) {
		$body = [
			'page_size' => min( $page_size, 100 ),
		];

		if ( ! empty( $filter ) ) {
			$body['filter'] = $filter;
		}

		if ( ! empty( $sorts ) ) {
			$body['sorts'] = $sorts;
		}

		if ( $start_cursor ) {
			$body['start_cursor'] = $start_cursor;
		}

		return $this->make_request( '/databases/' . $database_id . '/query', 'POST', $body );
	}

	/**
	 * Retrieve block children (page content).
	 *
	 * @param string $block_id Block or page ID.
	 * @param int    $page_size Number of results per page (max 100).
	 * @param string $start_cursor Pagination cursor.
	 * @return array|\WP_Error
	 */
	public function get_block_children( $block_id, $page_size = 100, $start_cursor = null ) {
		$endpoint = '/blocks/' . $block_id . '/children';

		$query_params = [
			'page_size' => min( $page_size, 100 ),
		];

		if ( $start_cursor ) {
			$query_params['start_cursor'] = $start_cursor;
		}

		$endpoint .= '?' . http_build_query( $query_params );

		return $this->make_request( $endpoint );
	}

	/**
	 * Recursively fetch all block children.
	 *
	 * @param string $block_id Block or page ID.
	 * @return array|\WP_Error Array of blocks or WP_Error.
	 */
	public function get_all_block_children( $block_id ) {
		$all_blocks   = [];
		$start_cursor = null;

		do {
			$response = $this->get_block_children( $block_id, 100, $start_cursor );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$blocks = $response['results'] ?? [];

			// Recursively fetch nested blocks.
			foreach ( $blocks as &$block ) {
				if ( ! empty( $block['has_children'] ) ) {
					$children = $this->get_all_block_children( $block['id'] );
					if ( ! is_wp_error( $children ) ) {
						$block['children'] = $children;
					}
				}
			}

			$all_blocks   = array_merge( $all_blocks, $blocks );
			$start_cursor = $response['next_cursor'] ?? null;

		} while ( ! empty( $response['has_more'] ) );

		return $all_blocks;
	}

	/**
	 * List comments on a block or page.
	 *
	 * @param string $block_id Block or page ID.
	 * @param int    $page_size Number of results per page (max 100).
	 * @param string $start_cursor Pagination cursor.
	 * @return array|\WP_Error
	 */
	public function get_comments( $block_id, $page_size = 100, $start_cursor = null ) {
		$endpoint = '/comments';

		$query_params = [
			'block_id'  => $block_id,
			'page_size' => min( $page_size, 100 ),
		];

		if ( $start_cursor ) {
			$query_params['start_cursor'] = $start_cursor;
		}

		$endpoint .= '?' . http_build_query( $query_params );

		return $this->make_request( $endpoint );
	}

	/**
	 * Get all pages and databases accessible to the integration.
	 *
	 * @return array|\WP_Error
	 */
	public function list_all_pages_and_databases() {
		$all_items    = [];
		$start_cursor = null;

		do {
			$response = $this->search( [], [], 100, $start_cursor );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$items        = $response['results'] ?? [];
			$all_items    = array_merge( $all_items, $items );
			$start_cursor = $response['next_cursor'] ?? null;

		} while ( ! empty( $response['has_more'] ) );

		return $all_items;
	}

	/**
	 * Get pages from a specific database.
	 *
	 * @param string $database_id Database ID.
	 * @return array|\WP_Error
	 */
	public function get_database_pages( $database_id ) {
		$all_pages    = [];
		$start_cursor = null;

		do {
			$response = $this->query_database( $database_id, [], [], 100, $start_cursor );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$pages        = $response['results'] ?? [];
			$all_pages    = array_merge( $all_pages, $pages );
			$start_cursor = $response['next_cursor'] ?? null;

		} while ( ! empty( $response['has_more'] ) );

		return $all_pages;
	}
}
