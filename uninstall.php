<?php
/**
 * Uninstall Sync Content From Notion.
 *
 * Fired when the plugin is uninstalled.
 *
 * @package Sync Content From Notion
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Clean up plugin data on uninstall.
 *
 * This function removes:
 * - Plugin options
 * - Custom capabilities from all roles
 */
function sync_content_from_notion_uninstall() {
	// Remove plugin options.
	delete_option( 'sync_content_from_notion_settings' );
	delete_option( 'sync_content_from_notion_allowed_roles' );

	// Remove custom capability from all roles.
	$roles = wp_roles();
	if ( $roles && isset( $roles->roles ) ) {
		foreach ( array_keys( $roles->roles ) as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->remove_cap( 'manage_sync_content_from_notion' );
			}
		}
	}

	// Clear any cached data.
	wp_cache_flush();
}

// Run uninstall cleanup.
sync_content_from_notion_uninstall();
