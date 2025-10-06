<?php
/**
 * Notion OAuth authentication.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Auth;

use Notion2WP\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Notion OAuth authentication class.
 */
class Auth {

	/**
	 * The single instance of the class.
	 *
	 * @var Auth
	 */
	protected static $instance = null;

	/**
	 * OAuth base URL.
	 */
	const OAUTH_BASE_URL = 'https://api.notion.com/v1/oauth';

	/**
	 * OAuth token endpoint.
	 */
	const TOKEN_ENDPOINT = 'https://api.notion.com/v1/oauth/token';

	/**
	 * OAuth authorize endpoint.
	 */
	const AUTHORIZE_ENDPOINT = 'https://api.notion.com/v1/oauth/authorize';

	/**
	 * Initialise.
	 */
	public static function init() {
		add_action( 'init', [ self::class, 'handle_oauth_callback' ] );
	}

	/**
	 * Generate OAuth authorization URL.
	 *
	 * @param string $client_id Client ID.
	 * @param string $redirect_uri Redirect URI.
	 * @param string $state Optional state parameter for CSRF protection.
	 * @return string
	 */
	public static function get_authorization_url( $client_id, $redirect_uri, $state = '' ) {
		$params = [
			'client_id'     => $client_id,
			'redirect_uri'  => $redirect_uri,
			'response_type' => 'code',
			'owner'         => 'user',
		];

		if ( ! empty( $state ) ) {
			$params['state'] = $state;
		}

		return self::AUTHORIZE_ENDPOINT . '?' . http_build_query( $params );
	}

	/**
	 * Exchange authorization code for access token.
	 *
	 * @param string $client_id Client ID.
	 * @param string $client_secret Client secret.
	 * @param string $code Authorization code.
	 * @param string $redirect_uri Redirect URI.
	 * @return array|WP_Error
	 */
	public static function get_access_token( $client_id, $client_secret, $code, $redirect_uri ) {
		$body = [
			'grant_type'   => 'authorization_code',
			'code'         => $code,
			'redirect_uri' => $redirect_uri,
		];

		$credentials = base64_encode( $client_id . ':' . $client_secret );

		$response = wp_safe_remote_post(
			self::TOKEN_ENDPOINT,
			[
				'body'    => wp_json_encode( $body ),
				'timeout' => 30, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
				'headers' => [
					'Accept'        => 'application/json',
					'Content-Type'  => 'application/json',
					'Authorization' => 'Basic ' . $credentials,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );

		if ( $code >= 400 ) {
			$error_data = json_decode( $body, true );
			return new \WP_Error(
				'notion2wp_oauth_error',
				isset( $error_data['error'] ) ? $error_data['error'] : 'OAuth error',
				[
					'status'   => $code,
					'response' => $error_data,
				]
			);
		}

		return json_decode( $body, true );
	}

	/**
	 * Refresh access token.
	 *
	 * @param string $client_id Client ID.
	 * @param string $client_secret Client secret.
	 * @param string $refresh_token Refresh token.
	 * @return array|WP_Error
	 */
	public static function refresh_access_token( $client_id, $client_secret, $refresh_token ) {
		$body = [
			'grant_type'    => 'refresh_token',
			'refresh_token' => $refresh_token,
		];

		$credentials = base64_encode( $client_id . ':' . $client_secret );

		$response = wp_safe_remote_post(
			self::TOKEN_ENDPOINT,
			[
				'body'    => wp_json_encode( $body ),
				'timeout' => 30, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
				'headers' => [
					'Accept'        => 'application/json',
					'Content-Type'  => 'application/json',
					'Authorization' => 'Basic ' . $credentials,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );

		if ( $code >= 400 ) {
			$error_data = json_decode( $body, true );
			return new \WP_Error(
				'notion2wp_oauth_refresh_error',
				isset( $error_data['error'] ) ? $error_data['error'] : 'Token refresh error',
				[
					'status'   => $code,
					'response' => $error_data,
				]
			);
		}

		$token_data = json_decode( $body, true );

		// Update stored settings with new token.
		$settings                      = Settings::get_settings();
		$settings['access_token']      = $token_data['access_token'];
		$settings['refresh_token']     = $token_data['refresh_token'];
		$settings['bot_id']            = $token_data['bot_id'];
		$settings['workspace_id']      = $token_data['workspace_id'];
		$settings['workspace_name']    = $token_data['workspace_name'];
		$settings['workspace_icon']    = $token_data['workspace_icon'];
		$settings['owner']             = $token_data['owner'];

		Settings::update_settings( $settings );

		return $token_data;
	}

	/**
	 * Start OAuth flow with provided credentials.
	 *
	 * @param string $client_id     The Notion client ID.
	 * @param string $client_secret The Notion client secret.
	 * @return array|WP_Error Authorization URL or error.
	 */
	public static function start_oauth_flow( $client_id, $client_secret ) {
		// Validate credentials.
		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return new \WP_Error( 'invalid_credentials', __( 'Client ID and Client Secret are required.', 'notion2wp' ) );
		}

		// Store credentials temporarily.
		$settings                  = Settings::get_settings();
		$settings['client_id']     = $client_id;
		$settings['client_secret'] = $client_secret;
		Settings::update_settings( $settings );

		// Generate OAuth URL.
		$redirect_uri = self::get_plugin_redirect_uri();
		$state        = wp_create_nonce( 'notion2wp_oauth_state' );
		
		// Store state for verification.
		set_transient( 'notion2wp_oauth_state_' . get_current_user_id(), $state, 600 );

		$auth_url = self::get_authorization_url( $client_id, $redirect_uri, $state );

		return [
			'auth_url'     => $auth_url,
			'redirect_uri' => $redirect_uri,
			'state'        => $state,
		];
	}

	/**
	 * Handle OAuth callback.
	 */
	public static function handle_oauth_callback() {
		if ( ! isset( $_GET['notion2wp_oauth_callback'] ) || ! isset( $_GET['code'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$error = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Check for OAuth errors.
		if ( ! empty( $error ) ) {
			$error_description = isset( $_GET['error_description'] ) ? sanitize_text_field( wp_unslash( $_GET['error_description'] ) ) : $error; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			wp_safe_redirect(
				add_query_arg(
					[
						'page'        => 'notion2wp-settings',
						'oauth_error' => urlencode( $error_description ),
					],
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		// Verify state parameter to prevent CSRF attacks.
		$stored_state = get_transient( 'notion2wp_oauth_state_' . get_current_user_id() );
		if ( empty( $state ) || $state !== $stored_state ) {
			wp_safe_redirect(
				add_query_arg(
					[
						'page'        => 'notion2wp-settings',
						'oauth_error' => urlencode( 'Invalid state parameter. Please try again.' ),
					],
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		// Clean up the state transient.
		delete_transient( 'notion2wp_oauth_state_' . get_current_user_id() );

		$settings = Settings::get_settings();

		if ( empty( $settings['client_id'] ) || empty( $settings['client_secret'] ) ) {
			wp_die( esc_html__( 'Notion2WP client credentials not configured.', 'notion2wp' ) );
		}

		$redirect_uri = self::get_plugin_redirect_uri();

		$token_response = self::get_access_token(
			$settings['client_id'],
			$settings['client_secret'],
			$code,
			$redirect_uri
		);

		if ( is_wp_error( $token_response ) ) {
			wp_safe_redirect(
				add_query_arg(
					[
						'page'        => 'notion2wp-settings',
						'oauth_error' => urlencode( $token_response->get_error_message() ),
					],
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		// Store all the OAuth response data.
		$settings['access_token']           = $token_response['access_token'];
		$settings['refresh_token']          = $token_response['refresh_token'];
		$settings['bot_id']                 = $token_response['bot_id'];
		$settings['workspace_id']           = $token_response['workspace_id'];
		$settings['workspace_name']         = $token_response['workspace_name'] ?? '';
		$settings['workspace_icon']         = $token_response['workspace_icon'] ?? '';
		$settings['owner']                  = $token_response['owner'];
		$settings['duplicated_template_id'] = $token_response['duplicated_template_id'] ?? null;
		$settings['token_obtained_at']      = time();

		Settings::update_settings( $settings );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'          => 'notion2wp-settings',
					'oauth_success' => '1',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Check if we have a valid access token.
	 *
	 * @return bool
	 */
	public static function has_valid_token() {
		$settings = Settings::get_settings();
		return ! empty( $settings['access_token'] ) && ! empty( $settings['bot_id'] );
	}

	/**
	 * Get current access token.
	 *
	 * @return string|null
	 */
	public static function get_access_token_value() {
		$settings = Settings::get_settings();
		return $settings['access_token'] ?? null;
	}

	/**
	 * Get bot ID.
	 *
	 * @return string|null
	 */
	public static function get_bot_id() {
		$settings = Settings::get_settings();
		return $settings['bot_id'] ?? null;
	}

	/**
	 * Get workspace information.
	 *
	 * @return array
	 */
	public static function get_workspace_info() {
		$settings = Settings::get_settings();
		
		return [
			'workspace_id'   => $settings['workspace_id'] ?? '',
			'workspace_name' => $settings['workspace_name'] ?? '',
			'workspace_icon' => $settings['workspace_icon'] ?? '',
			'owner'          => $settings['owner'] ?? null,
		];
	}

	/**
	 * Revoke the current access token.
	 *
	 * @return bool
	 */
	public static function revoke_token() {
		$settings = Settings::get_settings();

		// Clear all OAuth-related settings.
		unset( $settings['access_token'] );
		unset( $settings['refresh_token'] );
		unset( $settings['bot_id'] );
		unset( $settings['workspace_id'] );
		unset( $settings['workspace_name'] );
		unset( $settings['workspace_icon'] );
		unset( $settings['owner'] );
		unset( $settings['duplicated_template_id'] );
		unset( $settings['token_obtained_at'] );

		Settings::update_settings( $settings );

		return true;
	}

	/**
	 * Get plugin redirect URI.
	 *
	 * @return string
	 */
	private static function get_plugin_redirect_uri() {
		return admin_url( 'admin.php?notion2wp_oauth_callback=1' );
	}

	/**
	 * Validate and refresh token if needed.
	 *
	 * @return bool
	 */
	public static function validate_token() {
		$settings = Settings::get_settings();

		if ( empty( $settings['access_token'] ) || empty( $settings['refresh_token'] ) ) {
			return false;
		}

		// Notion's access tokens don't expire, but refresh tokens can be used to get updated workspace info.
		// For now, just return true if we have both tokens.
		return true;
	}

	/**
	 * Get OAuth connection status.
	 *
	 * @return array
	 */
	public static function get_connection_status() {
		$settings = Settings::get_settings();

		$status = [
			'connected'       => false,
			'workspace_name'  => '',
			'workspace_icon'  => '',
			'bot_id'          => '',
			'connection_date' => null,
		];

		if ( ! empty( $settings['access_token'] ) && ! empty( $settings['bot_id'] ) ) {
			$status['connected']       = true;
			$status['workspace_name']  = $settings['workspace_name'] ?? '';
			$status['workspace_icon']  = $settings['workspace_icon'] ?? '';
			$status['bot_id']          = $settings['bot_id'];
			$status['connection_date'] = ! empty( $settings['token_obtained_at'] ) ?
				date_i18n( get_option( 'date_format' ), $settings['token_obtained_at'] ) : null;
		}

		return $status;
	}
}

Auth::init();
