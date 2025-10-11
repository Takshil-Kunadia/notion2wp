<?php
/**
 * Notion2WP Admin functionality.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Admin;

defined( 'ABSPATH' ) || exit;

use Notion2WP\Auth\Auth;

/**
 * Admin class for handling WordPress admin interface.
 */
class Admin {

	/**
	 * Admin page slug.
	 */
	const PAGE_SLUG = 'notion2wp-settings';

	/**
	 * Initialize admin functionality.
	 */
	public static function init() {
		add_action( 'admin_menu', [ self::class, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_admin_scripts' ] );

		// Initialize REST API endpoints.
		Rest_API::init();
	}

	/**
	 * Add admin menu pages.
	 */
	public static function add_admin_menu() {
		add_menu_page(
			__( 'Notion2WP', 'notion2wp' ),
			__( 'Notion2WP', 'notion2wp' ),
			'manage_notion2wp',
			self::PAGE_SLUG,
			[ self::class, 'render_admin_page' ],
			'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M3.8 3.6c.6.5.8.5 2 .4l10.9-.7c.2 0 0-.2 0-.3L15.1 1.7c-.3-.3-.8-.6-1.7-.5L2.6 2c-.4 0-.5.2-.3.4zm.7 2.5v11.5c0 .6.3.8 1 .8l12-.7c.7 0 .8-.5.8-1V5.3c0-.5-.2-.8-.6-.7L5.1 5.3c-.5 0-.6.3-.6.8zm11.8.6c.1.3 0 .7-.3.7l-.6.1v8.5c-.5.3-1 .4-1.3.4-.6 0-.8-.2-1.2-.8L8.2 9.6v5.7l1.2.3s0 .7-1 .7l-2.7.2c-.1-.2 0-.5.3-.6l.7-.2V8.2l-1-.1c-.1-.3.1-.8.7-.9l2.9-.2 4.2 6.4V7.8l-1-.1c-.1-.4.2-.7.6-.8zM1.7.9l11-.8c1.3-.1 1.7 0 2.5.6l3.5 2.5c.6.4.8.5.8 1v13.5c0 .8-.3 1.3-1.4 1.4L5.3 19.9c-.8 0-1.2-.1-1.6-.6L1.1 16C.7 15.3.5 14.9.5 14.3V2.3c0-.7.3-1.3 1.2-1.4z" fill="#a7aaad"/></svg>' ),
			30
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public static function enqueue_admin_scripts( $hook ) {
		// Enqueue WordPress dependencies for React/Gutenberg components.
		$dependencies = [
			'wp-element',
			'wp-components',
			'wp-i18n',
			'wp-api-fetch',
			'wp-data',
			'wp-notices',
			'wp-hooks',
			'wp-dataviews',
		];

		// Build script.
		$script_asset_file = NOTION2WP_PLUGIN_DIR . 'dist/index.asset.php';
		$script_asset      = file_exists( $script_asset_file ) ? require $script_asset_file : [
			'dependencies' => $dependencies,
			'version'      => filemtime( NOTION2WP_PLUGIN_DIR . 'dist/index.js' ), // phpcs:ignore WordPressVIPMinimum.Files.IncludingNonPHPFile.IncludingNonPHPFile
		];

		wp_enqueue_script(
			'notion2wp-admin',
			NOTION2WP_PLUGIN_URL . 'dist/index.js',
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_enqueue_style(
			'notion2wp-admin',
			NOTION2WP_PLUGIN_URL . 'dist/index.css',
			[ 'wp-components' ],
			$script_asset['version']
		);

		// Localize script with data.
		wp_localize_script(
			'notion2wp-admin',
			'notion2wpAdmin',
			[
				'apiUrl'   => home_url( '/wp-json/notion2wp/v1/' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'adminUrl' => admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
				'siteLogo' => get_site_icon_url(),
			]
		);
	}

	/**
	 * Render the main admin page with tabbed interface.
	 */
	public static function render_admin_page() {
		?>
		<div class="wrap">
			<div id="notion2wp-admin-root"></div>
		</div>
		<?php
	}
}

// Initialize admin functionality.
Admin::init();
