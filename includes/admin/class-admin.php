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
		// Load SVG icon from file.
		$icon_path = NOTION2WP_PLUGIN_DIR . 'src/assets/notion-icon.svg';
		$icon_svg  = file_exists( $icon_path ) ? file_get_contents( $icon_path ) : '';

		add_menu_page(
			__( 'Notion2WP', 'notion2wp' ),
			__( 'Notion2WP', 'notion2wp' ),
			'manage_notion2wp',
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
