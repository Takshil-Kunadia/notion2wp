<?php
/**
 * Notion2WP plugin initialization.
 *
 * @package Notion2WP
 */

namespace Notion2WP;

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
		require_once NOTION2WP_ABSPATH . 'includes/auth/class-auth.php';
		require_once NOTION2WP_ABSPATH . 'includes/api-client/class-notion-client.php';
		require_once NOTION2WP_ABSPATH . 'includes/importer/class-importer-controller.php';
		require_once NOTION2WP_ABSPATH . 'includes/importer/class-page-property-handler.php';
	}
}
