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
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'get_auth_status' ],
				'permission_callback' => [ self::class, 'check_permissions' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/auth/connect',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ self::class, 'start_oauth_flow' ],
				'permission_callback' => [ self::class, 'check_permissions' ],
				'args'                => [
					'client_id'     => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'client_secret' => [
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
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => [ self::class, 'disconnect_oauth' ],
				'permission_callback' => [ self::class, 'check_permissions' ],
			]
		);

		// Settings endpoints.
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'get_settings' ],
				'permission_callback' => [ self::class, 'check_permissions' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/settings',
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ self::class, 'update_settings' ],
				'permission_callback' => [ self::class, 'check_permissions' ],
				'args'                => self::get_settings_schema(),
			]
		);
	}

	/**
	 * Check user permissions.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool
	 */
	public static function check_permissions( $request ) {
		return current_user_can( 'manage_notion2wp' ) && current_user_can( 'manage_options' );
	}

	/**
	 * Get authentication status.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function get_auth_status( $request ) {
		// Delegate to Auth class.
		$status = Auth::get_connection_status();

		return new \WP_REST_Response( $status, 200 );
	}

	/**
	 * Start OAuth flow.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function start_oauth_flow( $request ) {
		$client_id     = $request->get_param( 'client_id' );
		$client_secret = $request->get_param( 'client_secret' );

		// Delegate to Auth class.
		$result = Auth::start_oauth_flow( $client_id, $client_secret );

		// Handle result.
		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response(
				[ 'message' => $result->get_error_message() ],
				400
			);
		}

		return new \WP_REST_Response(
			[
				'auth_url' => $result['auth_url'],
				'message'  => __( 'Redirect to Notion for authorization.', 'notion2wp' ),
			],
			200
		);
	}

	/**
	 * Disconnect OAuth.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function disconnect_oauth( $request ) {
		// Delegate to Auth class.
		$result = Auth::revoke_token();

		if ( $result ) {
			return new \WP_REST_Response(
				[ 'message' => __( 'Successfully disconnected from Notion.', 'notion2wp' ) ],
				200
			);
		}

		return new \WP_REST_Response(
			[ 'message' => __( 'Failed to disconnect from Notion.', 'notion2wp' ) ],
			500
		);
	}

	/**
	 * Get settings.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function get_settings( $request ) {
		$settings = Settings::get_settings();

		// Remove sensitive data from response.
		unset( $settings['client_secret'] );
		unset( $settings['access_token'] );
		unset( $settings['refresh_token'] );

		return new \WP_REST_Response( $settings, 200 );
	}

	/**
	 * Update settings.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function update_settings( $request ) {
		$current_settings = Settings::get_settings();
		$new_settings     = $request->get_json_params();

		// Merge with current settings, preserving auth data.
		$auth_keys = [
			'client_secret',
			'access_token', 
			'refresh_token',
			'bot_id',
			'workspace_id',
			'workspace_name',
			'workspace_icon',
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
			return new \WP_REST_Response(
				[ 'message' => __( 'Settings updated successfully.', 'notion2wp' ) ],
				200
			);
		}

		return new \WP_REST_Response(
			[ 'message' => __( 'Failed to update settings.', 'notion2wp' ) ],
			500
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
