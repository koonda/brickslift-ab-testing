<?php
/**
 * Plugin Name: BricksLift A/B Testing
 * Plugin URI: https://brickslift.com/
 * Description: A/B testing for Bricks Builder.
 * Version: 0.3.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: brickslift-ab-testing
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Bricks: 1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'BLFT_VERSION', '0.3.0' );
define( 'BLFT_PLUGIN_FILE', __FILE__ );
define( 'BLFT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLFT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Ensure Composer autoloader is loaded.
if ( file_exists( BLFT_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once BLFT_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * The main function to load the plugin.
 *
 * @return void
 */
function blft_run_plugin() {
	\BricksLiftAB\Core\Plugin::get_instance();
}
add_action( 'plugins_loaded', 'blft_run_plugin' );

/**
 * Activation hook.
 */
register_activation_hook( __FILE__, [ '\BricksLiftAB\Core\Plugin', 'activate' ] );

/**
 * Deactivation hook.
 */
register_deactivation_hook( __FILE__, [ '\BricksLiftAB\Core\Plugin', 'deactivate' ] );