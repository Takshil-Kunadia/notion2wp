<?php
/**
 * Plugin Name: Notion2WP
 * Description: Publish content directly from Notion to WordPress.
 * Version: 0.1.0
 * Author: Takshil Kunadia
 */

defined( 'ABSPATH' ) || exit;

define( 'TK_NOTION_TO_WP', '0.0.0' );

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
