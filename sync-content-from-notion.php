<?php
/**
 * Plugin Name
 *
 * @package           Sync Content From Notion
 * @author            Takshil Kunadia
 * @copyright         2025 Takshil Kunadia
 * @license           GPL2
 *
 * @wordpress-plugin
 * Plugin Name:       Sync Content From Notion
 * Plugin URI:        https://notion2wp.framer.website/
 * Description:       Publish Notion databases and pages to WordPress posts seamlessly.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Takshil Kunadia
 * Author URI:        https://takshil.dev
 * Text Domain:       sync-content-from-notion
 * License:           GPL2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

defined( 'ABSPATH' ) || exit;

define( 'SYNC_CONTENT_FROM_NOTION_VERSION', '1.0.0' );

// Define plugin constants.
if ( ! defined( 'SYNC_CONTENT_FROM_NOTION_PLUGIN_FILE' ) ) {
	define( 'SYNC_CONTENT_FROM_NOTION_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'SYNC_CONTENT_FROM_NOTION_PLUGIN_DIR' ) ) {
	define( 'SYNC_CONTENT_FROM_NOTION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SYNC_CONTENT_FROM_NOTION_PLUGIN_URL' ) ) {
	define( 'SYNC_CONTENT_FROM_NOTION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'SYNC_CONTENT_FROM_NOTION_ABSPATH' ) ) {
	define( 'SYNC_CONTENT_FROM_NOTION_ABSPATH', __DIR__ . '/' );
}

require_once __DIR__ . '/vendor/autoload.php';

SyncContentFromNotion\WP_Notion::init();
