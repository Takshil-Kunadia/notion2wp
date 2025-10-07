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

		// Add custom capability.
		add_action( 'admin_init', [ __CLASS__, 'add_notion_capability' ] );

		// Include required files.
		require_once NOTION2WP_ABSPATH . 'includes/admin/class-admin.php';
		require_once NOTION2WP_ABSPATH . 'includes/admin/class-rest-api.php';
		require_once NOTION2WP_ABSPATH . 'includes/admin/class-settings.php';
		require_once NOTION2WP_ABSPATH . 'includes/auth/class-auth.php';
		require_once NOTION2WP_ABSPATH . 'includes/api-client/class-notion-client.php';
		require_once NOTION2WP_ABSPATH . 'includes/importer/class-importer-controller.php';
		require_once NOTION2WP_ABSPATH . 'includes/importer/class-page-property-handler.php';
	}

	/**
	 * Add custom capability.
	 */
	public static function add_notion_capability() {
		// Add custom capability for managing Notion connections.
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( 'manage_notion2wp' );
		}
	}

	/**
	 * Check if user has permission to manage Notion2WP.
	 *
	 * @return bool
	 */
	public static function current_user_can_manage() {
		return current_user_can( 'manage_notion2wp' ) || current_user_can( 'manage_options' );
	}
}
