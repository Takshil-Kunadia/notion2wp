<?php
/**
 * Notion2WP plugin initialization.
 *
 * @package Notion2WP
 */

namespace Notion2WP;

use Notion2WP\Admin\Capabilities;
use Notion2WP\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class to handle the plugin initialization
 */
class WP_Notion {

	/**
	 * Runs the initialization.
	 */
	public static function init() {
		// Include required files.
		require_once NOTION2WP_ABSPATH . 'includes/admin/class-admin.php';
		require_once NOTION2WP_ABSPATH . 'includes/admin/class-rest-api.php';
		require_once NOTION2WP_ABSPATH . 'includes/admin/class-settings.php';
		require_once NOTION2WP_ABSPATH . 'includes/admin/class-capabilities.php';
		require_once NOTION2WP_ABSPATH . 'includes/auth/class-auth.php';
		require_once NOTION2WP_ABSPATH . 'includes/api-client/class-notion-client.php';
		require_once NOTION2WP_ABSPATH . 'includes/media/class-media-collector.php';
		require_once NOTION2WP_ABSPATH . 'includes/media/class-media-handler.php';
		require_once NOTION2WP_ABSPATH . 'includes/importer/class-importer-controller.php';
		require_once NOTION2WP_ABSPATH . 'includes/importer/class-page-property-handler.php';

		// Register activation and deactivation hooks.
		register_activation_hook( NOTION2WP_PLUGIN_FILE, [ __CLASS__, 'activate' ] );
		register_deactivation_hook( NOTION2WP_PLUGIN_FILE, [ __CLASS__, 'deactivate' ] );
	}

	/**
	 * Plugin activation hook.
	 *
	 * Called when the plugin is activated.
	 */
	public static function activate() {
		// Add custom capabilities to roles.
		Capabilities::add_capabilities();
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * Called when the plugin is deactivated.
	 */
	public static function deactivate() {
		// Reset all settings options.
		Settings::reset_options();

		// Remove custom capabilities from all roles.
		Capabilities::remove_capabilities();
	}
}
