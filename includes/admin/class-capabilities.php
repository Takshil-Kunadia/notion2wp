<?php
/**
 * Notion2WP Capabilities Management.
 *
 * Handles custom capabilities for the plugin.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Capabilities class for managing plugin permissions.
 */
class Capabilities {

	/**
	 * Custom capability name.
	 */
	const CAPABILITY = 'manage_notion2wp';

	/**
	 * Option name for storing allowed roles.
	 */
	const ROLES_OPTION = 'notion2wp_allowed_roles';

	/**
	 * Add custom capability to default roles.
	 *
	 * Called on plugin activation.
	 */
	public static function add_capabilities() {
		// Get allowed roles from options or use defaults.
		$allowed_roles = get_option( self::ROLES_OPTION, [ 'administrator' ] );

		// Always include administrator.
		if ( ! in_array( 'administrator', $allowed_roles, true ) ) {
			$allowed_roles[] = 'administrator';
		}

		// Add capability to allowed roles.
		foreach ( $allowed_roles as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->add_cap( self::CAPABILITY );
			}
		}
	}

	/**
	 * Remove custom capability from all roles.
	 *
	 * Called on plugin deactivation.
	 */
	public static function remove_capabilities() {
		$wp_roles = wp_roles();

		foreach ( $wp_roles->roles as $role_name => $role_info ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->remove_cap( self::CAPABILITY );
			}
		}

		delete_option( self::ROLES_OPTION );
	}

	/**
	 * Update capabilities for roles.
	 *
	 * @param array $new_roles Array of role names that should have the capability.
	 * @return bool True on success, false on failure.
	 */
	public static function update_role_capabilities( $new_roles ) {
		// Validate input.
		if ( ! is_array( $new_roles ) ) {
			return false;
		}

		// Always include administrator.
		if ( ! in_array( 'administrator', $new_roles, true ) ) {
			$new_roles[] = 'administrator';
		}

		/**
		 * Filters the roles before updating capabilities.
		 *
		 * @since 1.0.0
		 *
		 * @param array $new_roles Array of role names that should have the capability.
		 */
		$new_roles = apply_filters( 'notion2wp_update_role_capabilities', $new_roles );

		// Get current roles with capability.
		$current_roles = get_option( self::ROLES_OPTION, [ 'administrator' ] );

		// Remove capability from roles that should no longer have it.
		$roles_to_remove = array_diff( $current_roles, $new_roles );
		foreach ( $roles_to_remove as $role_name ) {
			// Don't remove from administrator.
			if ( 'administrator' === $role_name ) {
				continue;
			}

			$role = get_role( $role_name );
			if ( $role ) {
				$role->remove_cap( self::CAPABILITY );
			}
		}

		// Add capability to new roles.
		$roles_to_add = array_diff( $new_roles, $current_roles );
		foreach ( $roles_to_add as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->add_cap( self::CAPABILITY );
			}
		}

		// Update stored roles.
		return update_option( self::ROLES_OPTION, $new_roles );
	}

	/**
	 * Get all available WordPress roles.
	 *
	 * @return array Array of roles with name and display_name.
	 */
	public static function get_available_roles() {
		$wp_roles = wp_roles();

		$roles = [];
		foreach ( $wp_roles->roles as $role_name => $role_info ) {
			$roles[] = [
				'name'         => $role_name,
				'display_name' => translate_user_role( $role_info['name'] ),
			];
		}

		return $roles;
	}

	/**
	 * Get roles that currently have the capability.
	 *
	 * @return array Array of role names.
	 */
	public static function get_roles_with_capability() {
		return get_option( self::ROLES_OPTION, [ 'administrator' ] );
	}

	/**
	 * Check if current user can manage Notion2WP.
	 *
	 * @return bool True if user has capability, false otherwise.
	 */
	public static function current_user_can_manage() {
		return current_user_can( self::CAPABILITY );
	}
}
