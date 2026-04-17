<?php
/**
 * Uninstall Notion2WP.
 *
 * Fired when the plugin is uninstalled.
 *
 * @package Notion2WP
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Clean up plugin data on uninstall.
 *
 * This function removes:
 * - Plugin options
 * - Custom capabilities from all roles
 */
function notion2wp_uninstall() {
	// Remove plugin options.
	delete_option( 'notion2wp_settings' );
	delete_option( 'notion2wp_allowed_roles' );
	delete_option( 'notion2wp_integration_token' );

	// Remove custom capability from all roles.
	$roles = wp_roles();
	if ( $roles && isset( $roles->roles ) ) {
		foreach ( array_keys( $roles->roles ) as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->remove_cap( 'manage_notion2wp' );
			}
		}
	}

	// Clear any cached data.
	wp_cache_flush();
}

// Run uninstall cleanup.
notion2wp_uninstall();
