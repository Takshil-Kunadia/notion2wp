<?php
/**
 * Plugin Name
 *
 * @package           Notion2WP
 * @author            Takshil Kunadia
 * @copyright         2025 Takshil Kunadia
 * @license           GPL2
 *
 * @wordpress-plugin
 * Plugin Name:       Notion2WP
 * Plugin URI:        https://notion2wp.framer.website/
 * Description:       Publish Notion databases and pages to WordPress posts seamlessly.
 * Version:           1.0.1
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Takshil Kunadia
 * Author URI:        https://takshil.dev
 * Text Domain:       notion2wp
 * License:           GPL2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

defined( 'ABSPATH' ) || exit;

define( 'NOTION2WP_VERSION', '1.0.1' );

// Define plugin constants.
if ( ! defined( 'NOTION2WP_PLUGIN_FILE' ) ) {
	define( 'NOTION2WP_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'NOTION2WP_PLUGIN_DIR' ) ) {
	define( 'NOTION2WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'NOTION2WP_PLUGIN_URL' ) ) {
	define( 'NOTION2WP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'NOTION2WP_ABSPATH' ) ) {
	define( 'NOTION2WP_ABSPATH', __DIR__ . '/' );
}

require_once __DIR__ . '/vendor/autoload.php';

Notion2WP\WP_Notion::init();
