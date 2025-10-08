<?php
/**
 * Notion2WP REST API endpoints.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Admin;

defined( 'ABSPATH' ) || exit;

use Notion2WP\Admin\Settings;
use Notion2WP\Auth\Auth;
use Notion2WP\Importer\Importer_Controller;
use WP_REST_Response;
use WP_REST_Server;
use WP_REST_Request;

/**
 * REST API class for handling AJAX requests and API endpoints.
 */
class Rest_API {

	/**
	 * API namespace.
	 */
	const NAMESPACE = 'notion2wp/v1';

	/**
	 * Initialize REST API endpoints.
	 */
	public static function init() {
		add_action( 'rest_api_init', [ self::class, 'register_routes' ] );
	}

	/**
	 * Register REST API routes.
	 */
	public static function register_routes() {
		// Auth endpoints.
		register_rest_route(
			self::NAMESPACE,
			'/auth/status',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'get_auth_status' ],
				'permission_callback' => [ self::class, 'check_permissions' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/auth/connect',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ self::class, 'connect_integration' ],
				'permission_callback' => [ self::class, 'check_permissions' ],
				'args'                => [
					'integration_token' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/auth/disconnect',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ self::class, 'disconnect_oauth' ],
				'permission_callback' => [ self::class, 'check_permissions' ],
			]
		);

		// Settings endpoints.
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'get_settings' ],
				'permission_callback' => [ self::class, 'check_permissions' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/settings',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ self::class, 'update_settings' ],
				'permission_callback' => [ self::class, 'check_permissions' ],
				'args'                => self::get_settings_schema(),
			]
		);

		// Import endpoints.
		register_rest_route(
			self::NAMESPACE,
			'/import/items',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'get_importable_items' ],
				'permission_callback' => [ self::class, 'check_permissions' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/import/pages',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ self::class, 'import_pages' ],
				'permission_callback' => [ self::class, 'check_permissions' ],
				'args'                => [
					'page_ids' => [
						'required'          => true,
						'type'              => 'array',
						'items'             => [
							'type' => 'string',
						],
						'sanitize_callback' => function( $page_ids ) {
							return array_map( 'sanitize_text_field', $page_ids );
						},
					],
				],
			]
		);
	}

	/**
	 * Check user permissions.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public static function check_permissions( $request ) {
		return current_user_can( 'manage_notion2wp' ) && current_user_can( 'manage_options' );
	}

	/**
	 * Get authentication status.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function get_auth_status( $request ) {
		// Delegate to Auth class.
		$status = Auth::get_connection_status();

		return new WP_REST_Response( $status, 200 );
	}

	/**
	 * Connect to Notion using integration token.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function connect_integration( $request ) {
		$integration_token = $request->get_param( 'integration_token' );

		// Delegate to Auth class to save and verify token.
		$result = Auth::save_integration_token( $integration_token );

		// Handle result.
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				[ 'message' => $result->get_error_message() ],
				400
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Successfully connected to Notion!', 'notion2wp' ),
			],
			200
		);
	}

	/**
	 * Disconnect OAuth.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function disconnect_oauth( $request ) {
		// Delegate to Auth class.
		$result = Auth::revoke_token();

		if ( $result ) {
			return new WP_REST_Response(
				[ 'message' => __( 'Successfully disconnected from Notion.', 'notion2wp' ) ],
				200
			);
		}

		return new WP_REST_Response(
			[ 'message' => __( 'Failed to disconnect from Notion.', 'notion2wp' ) ],
			500
		);
	}

	/**
	 * Get settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function get_settings( $request ) {
		$settings = Settings::get_settings();

		// Remove sensitive data from response.
		unset( $settings['client_secret'] );
		unset( $settings['access_token'] );
		unset( $settings['refresh_token'] );

		return new WP_REST_Response( $settings, 200 );
	}

	/**
	 * Update settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function update_settings( $request ) {
		$current_settings = Settings::get_settings();
		$new_settings     = $request->get_json_params();

		// Merge with current settings, preserving auth data.
		$auth_keys = [
			'integration_token',
			'bot_id',
			'owner',
			'duplicated_template_id',
			'token_obtained_at',
		];

		foreach ( $auth_keys as $key ) {
			if ( isset( $current_settings[ $key ] ) ) {
				$new_settings[ $key ] = $current_settings[ $key ];
			}
		}

		$updated = Settings::update_settings( $new_settings, false );

		if ( $updated ) {
			return new WP_REST_Response(
				[ 'message' => __( 'Settings updated successfully.', 'notion2wp' ) ],
				200
			);
		}

		return new WP_REST_Response(
			[ 'message' => __( 'Failed to update settings.', 'notion2wp' ) ],
			500
		);
	}

	/**
	 * Get importable items (pages and databases).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function get_importable_items( $request ) {
		$importer = new Importer_Controller();
		$items    = $importer->get_importable_items();

		if ( is_wp_error( $items ) ) {
			return new WP_REST_Response(
				[ 'message' => $items->get_error_message() ],
				400
			);
		}

		return new WP_REST_Response(
			[
				'items' => $items,
				'total' => count( $items ),
			],
			200
		);
	}

	/**
	 * Import selected pages.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function import_pages( $request ) {
		$page_ids = $request->get_param( 'page_ids' );

		$importer = new Importer_Controller();
		$results  = $importer->import_pages( $page_ids );

		if ( is_wp_error( $results ) ) {
			return new WP_REST_Response(
				[ 'message' => $results['errors'] ],
				400
			);
		}

		return new WP_REST_Response(
			[
				'success' => $results['success'],
				'errors'  => $results['errors'],
				'message' => sprintf(
					/* translators: %1$d: number of successful imports, %2$d: number of failed imports */
					__( 'Import complete. Success: %1$d, Failed: %2$d', 'notion2wp' ),
					count( $results['success'] ),
					count( $results['errors'] )
				),
			],
			200
		);
	}

	/**
	 * Get settings schema for validation.
	 *
	 * @return array
	 */
	private static function get_settings_schema() {
		return [
			'sync_enabled'        => [
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			'sync_frequency'      => [
				'type'              => 'string',
				'enum'              => [ 'hourly', 'daily', 'weekly' ],
				'sanitize_callback' => 'sanitize_text_field',
			],
			'auto_sync'           => [
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			'sync_on_save'        => [
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			'sync_direction'      => [
				'type'              => 'string',
				'enum'              => [ 'notion_to_wp', 'wp_to_notion', 'bidirectional' ],
				'sanitize_callback' => 'sanitize_text_field',
			],
			'default_post_type'   => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
			'default_post_status' => [
				'type'              => 'string',
				'enum'              => [ 'draft', 'publish', 'private' ],
				'sanitize_callback' => 'sanitize_text_field',
			],
			'default_author_id'   => [
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			],
			'preserve_formatting' => [
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			'convert_blocks'      => [
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			'import_images'       => [
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			'field_mappings'      => [
				'type' => 'object',
			],
			'database_mappings'   => [
				'type' => 'object',
			],
		];
	}
}
