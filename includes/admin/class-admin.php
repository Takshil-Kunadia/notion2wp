<?php
/**
 * Sync Content From Notion Admin functionality.
 *
 * @package Sync Content From Notion
 */

namespace SyncContentFromNotion\Admin;

defined( 'ABSPATH' ) || exit;

use SyncContentFromNotion\Auth\Auth;

/**
 * Admin class for handling WordPress admin interface.
 */
class Admin {

	/**
	 * Admin page slug.
	 */
	const PAGE_SLUG = 'sync-content-from-notion-settings';

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
		// Load SVG icon from file.
		$icon_path = SYNC_CONTENT_FROM_NOTION_PLUGIN_DIR . 'src/assets/sync-content-from-notion-logo.svg';
		$icon_svg  = file_exists( $icon_path ) ? file_get_contents( $icon_path ) : '';

		add_menu_page(
			__( 'Sync Content From Notion', 'sync-content-from-notion' ),
			__( 'Sync Content From Notion', 'sync-content-from-notion' ),
			Capabilities::CAPABILITY,
			self::PAGE_SLUG,
			[ self::class, 'render_admin_page' ],
			'data:image/svg+xml;base64,' . base64_encode( $icon_svg ),
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
		$script_asset_file = SYNC_CONTENT_FROM_NOTION_PLUGIN_DIR . 'dist/index.asset.php';
		$script_asset      = file_exists( $script_asset_file ) ? require $script_asset_file : [
			'dependencies' => $dependencies,
			'version'      => filemtime( SYNC_CONTENT_FROM_NOTION_PLUGIN_DIR . 'dist/index.js' ), // phpcs:ignore WordPressVIPMinimum.Files.IncludingNonPHPFile.IncludingNonPHPFile
		];

		wp_enqueue_script(
			'sync-content-from-notion-admin',
			SYNC_CONTENT_FROM_NOTION_PLUGIN_URL . 'dist/index.js',
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_enqueue_style(
			'sync-content-from-notion-admin',
			SYNC_CONTENT_FROM_NOTION_PLUGIN_URL . 'dist/index.css',
			[ 'wp-components' ],
			$script_asset['version']
		);

		// Localize script with data.
		wp_localize_script(
			'sync-content-from-notion-admin',
			'syncContentFromNotionAdmin',
			[
				'apiUrl'   => home_url( '/wp-json/sync-content-from-notion/v1/' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'adminUrl' => admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
				'isAdmin'  => current_user_can( 'manage_options' ),
			]
		);
	}

	/**
	 * Render the main admin page with tabbed interface.
	 */
	public static function render_admin_page() {
		?>
		<div class="wrap">
			<div id="sync-content-from-notion-admin-root"></div>
		</div>
		<?php
	}
}

// Initialize admin functionality.
Admin::init();
