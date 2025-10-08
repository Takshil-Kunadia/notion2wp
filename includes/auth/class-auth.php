<?php
/**
 * Notion authentication for Internal integrations.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Auth;

use Notion2WP\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Notion authentication class.
 */
class Auth {

	/**
	 * The single instance of the class.
	 *
	 * @var Auth
	 */
	protected static $instance = null;

	/**
	 * Notion API base URL.
	 */
	const API_BASE_URL = 'https://api.notion.com/v1';

	/**
	 * Notion API version.
	 */
	const API_VERSION = '2025-09-03';

	/**
	 * Save integration token.
	 *
	 * @param string $integration_token The Notion integration token.
	 * @return array|WP_Error Success data or error.
	 */
	public static function save_integration_token( $integration_token ) {
		if ( empty( $integration_token ) ) {
			return new \WP_Error(
				'invalid_token',
				__( 'Integration token is required.', 'notion2wp' )
			);
		}

		$test_result = self::verify_token( $integration_token );

		if ( is_wp_error( $test_result ) ) {
			return $test_result;
		}

		// Store the token and workspace info.
		$settings = Settings::get_settings();
		$settings['integration_token'] = $integration_token;
		$settings['bot_id']            = $test_result['bot_id'] ?? '';
		$settings['token_obtained_at'] = time();

		Settings::update_settings( $settings );

		return [
			'success' => true,
			'bot_id'  => $test_result['bot_id'] ?? '',
		];
	}

	/**
	 * Verify integration token by making a test API request.
	 *
	 * @param string $integration_token The integration token to verify.
	 * @return array|WP_Error Bot info or error.
	 */
	public static function verify_token( $integration_token ) {
		// Test the token by fetching the bot info.
		$response = wp_safe_remote_get(
			self::API_BASE_URL . '/users/me',
			[
				'timeout' => 15, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
				'headers' => [
					'Authorization'  => 'Bearer ' . $integration_token,
					'Notion-Version' => self::API_VERSION,
					'Content-Type'   => 'application/json',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'notion2wp_api_error',
				$response->get_error_message()
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code >= 400 ) {
			return new \WP_Error(
				'notion2wp_invalid_token',
				isset( $data['message'] ) ? $data['message'] : __( 'Invalid integration token.', 'notion2wp' ),
				[
					'status'   => $code,
					'response' => $data,
				]
			);
		}

		// Extract bot information.
		$bot_info = [
			'bot_id' => $data['id'] ?? '',
			'bot'    => $data['bot'] ?? [],
		];

		return $bot_info;
	}

	/**
	 * Check if we have a valid integration token.
	 *
	 * @return bool
	 */
	public static function has_valid_token() {
		$settings = Settings::get_settings();
		return ! empty( $settings['integration_token'] );
	}

	/**
	 * Get current integration token.
	 *
	 * @return string|null
	 */
	public static function get_integration_token() {
		$settings = Settings::get_settings();
		return $settings['integration_token'] ?? null;
	}

	/**
	 * Revoke the current integration token.
	 *
	 * @return bool
	 */
	public static function revoke_token() {
		$settings = Settings::get_settings();

		// Clear all integration-related settings.
		unset( $settings['integration_token'] );
		unset( $settings['bot_id'] );
		unset( $settings['token_obtained_at'] );

		Settings::update_settings( $settings, false );

		return true;
	}

	/**
	 * Get connection status.
	 *
	 * @return array
	 */
	public static function get_connection_status() {
		$settings = Settings::get_settings();

		$status = [
			'connected'       => false,
			'connection_date' => null,
		];

		if ( ! empty( $settings['integration_token'] ) ) {
			$status['connected']       = true;
			$status['connection_date'] = ! empty( $settings['token_obtained_at'] ) ?
				date_i18n( get_option( 'date_format' ), $settings['token_obtained_at'] ) : null;
		}

		return $status;
	}
}
