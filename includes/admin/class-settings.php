<?php
/**
 * Notion2WP Settings Management.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Settings management class.
 */
class Settings {

	/**
	 * Plugin settings option name.
	 */
	const SETTINGS_OPTION = 'notion2wp_settings';

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	private static $default_settings = [
		// OAuth & Connection Settings.
		'client_id'              => '',
		'client_secret'          => '',
		'access_token'           => '',
		'refresh_token'          => '',
		'bot_id'                 => '',
		'workspace_id'           => '',
		'workspace_name'         => '',
		'workspace_icon'         => '',
		'owner'                  => null,
		'duplicated_template_id' => null,
		'token_obtained_at'      => null,

		// Sync Settings.
		'sync_enabled'           => false,
		'sync_frequency'         => 'hourly', // hourly, daily, weekly.
		'auto_sync'              => false,
		'sync_on_save'           => false,
		'sync_direction'         => 'notion_to_wp', // notion_to_wp, wp_to_notion, bidirectional.

		// Content Settings.
		'default_post_type'      => 'post',
		'default_post_status'    => 'draft',
		'import_featured_images' => true,
		'import_blocks'          => true,
		'convert_notion_blocks'  => true,
		'preserve_notion_ids'    => true,

		// Field Mapping.
		'field_mappings'         => [],
		'custom_field_prefix'    => 'notion_',
		'map_properties_to_meta' => true,

		// Advanced Settings.
		'enable_debug_logging'   => false,
		'api_request_timeout'    => 30,
		'batch_size'             => 50,
		'enable_webhooks'        => false,
		'webhook_secret'         => '',

		// UI Settings.
		'show_admin_bar_menu'    => true,
		'show_dashboard_widget'  => true,
		'admin_menu_position'    => 'tools',
	];

	/**
	 * Initialize settings.
	 */
	public static function init() {
		// Register settings for sanitization.
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
	}

	/**
	 * Register settings for sanitization.
	 */
	public static function register_settings() {
		register_setting(
			'notion2wp_settings_group',
			self::SETTINGS_OPTION,
			[ __CLASS__, 'sanitize_settings' ]
		);
	}

	/**
	 * Get all plugin settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$settings = get_option( self::SETTINGS_OPTION, [] );
		return wp_parse_args( $settings, self::$default_settings );
	}

	/**
	 * Update plugin settings.
	 *
	 * @param array $new_settings Settings to update.
	 * @param bool  $merge Whether to merge with existing settings.
	 * @return bool
	 */
	public static function update_settings( $new_settings, $merge = true ) {
		if ( $merge ) {
			$current_settings = self::get_settings();
			$settings = array_merge( $current_settings, $new_settings );
		} else {
			$settings = wp_parse_args( $new_settings, self::$default_settings );
		}

		return update_option( self::SETTINGS_OPTION, $settings );
	}

	/**
	 * Get a specific setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value if key doesn't exist.
	 * @return mixed
	 */
	public static function get_setting( $key, $default = null ) {
		$settings = self::get_settings();
		
		if ( isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}

		if ( null !== $default ) {
			return $default;
		}

		return isset( self::$default_settings[ $key ] ) ? self::$default_settings[ $key ] : null;
	}

	/**
	 * Update a specific setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Setting value.
	 * @return bool
	 */
	public static function update_setting( $key, $value ) {
		$settings = self::get_settings();
		$settings[ $key ] = $value;
		return self::update_settings( $settings, false );
	}

	/**
	 * Delete a specific setting.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	public static function delete_setting( $key ) {
		$settings = self::get_settings();

		if ( isset( $settings[ $key ] ) ) {
			unset( $settings[ $key ] );
			return self::update_settings( $settings, false );
		}

		return true;
	}

	/**
	 * Reset settings to defaults.
	 *
	 * @param array $preserve_keys Keys to preserve from current settings.
	 * @return bool
	 */
	public static function reset_settings( $preserve_keys = [] ) {
		$settings = self::$default_settings;

		if ( ! empty( $preserve_keys ) ) {
			$current_settings = self::get_settings();
			foreach ( $preserve_keys as $key ) {
				if ( isset( $current_settings[ $key ] ) ) {
					$settings[ $key ] = $current_settings[ $key ];
				}
			}
		}

		return update_option( self::SETTINGS_OPTION, $settings );
	}

	/**
	 * Get OAuth related settings.
	 *
	 * @return array
	 */
	public static function get_oauth_settings() {
		$settings = self::get_settings();

		return [
			'client_id'              => $settings['client_id'],
			'client_secret'          => $settings['client_secret'],
			'access_token'           => $settings['access_token'],
			'refresh_token'          => $settings['refresh_token'],
			'bot_id'                 => $settings['bot_id'],
			'workspace_id'           => $settings['workspace_id'],
			'workspace_name'         => $settings['workspace_name'],
			'workspace_icon'         => $settings['workspace_icon'],
			'owner'                  => $settings['owner'],
			'duplicated_template_id' => $settings['duplicated_template_id'],
			'token_obtained_at'      => $settings['token_obtained_at'],
		];
	}

	/**
	 * Get sync settings.
	 *
	 * @return array
	 */
	public static function get_sync_settings() {
		$settings = self::get_settings();

		return [
			'sync_enabled'   => $settings['sync_enabled'],
			'sync_frequency' => $settings['sync_frequency'],
			'auto_sync'      => $settings['auto_sync'],
			'sync_on_save'   => $settings['sync_on_save'],
			'sync_direction' => $settings['sync_direction'],
		];
	}

	/**
	 * Get content settings.
	 *
	 * @return array
	 */
	public static function get_content_settings() {
		$settings = self::get_settings();

		return [
			'default_post_type'      => $settings['default_post_type'],
			'default_post_status'    => $settings['default_post_status'],
			'import_featured_images' => $settings['import_featured_images'],
			'import_blocks'          => $settings['import_blocks'],
			'convert_notion_blocks'  => $settings['convert_notion_blocks'],
			'preserve_notion_ids'    => $settings['preserve_notion_ids'],
		];
	}

	/**
	 * Get field mapping settings.
	 *
	 * @return array
	 */
	public static function get_field_mapping_settings() {
		$settings = self::get_settings();

		return [
			'field_mappings'         => $settings['field_mappings'],
			'custom_field_prefix'    => $settings['custom_field_prefix'],
			'map_properties_to_meta' => $settings['map_properties_to_meta'],
		];
	}
}

Settings::init();
