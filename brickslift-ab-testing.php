<?php
/**
 * Plugin Name: BricksLift A/B Testing
 * Plugin URI: https://brickslift.com/
 * Description: A/B testing for Bricks Builder.
 * Version: 0.4.6
 * Author: Adam Kotala
 * Author URI: https://digistorm.cz
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

define( 'BLFT_VERSION', '0.4.6' );
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

// Temporary debugging for register_meta issues
if ( ! function_exists( '_blft_debug_log_register_meta_args_fallback' ) ) {
    /**
     * Logs arguments passed to register_meta for debugging purposes.
     *
     * @param array $args Arguments passed to register_meta.
     * @return array Unmodified $args.
     */
    function _blft_debug_log_register_meta_args_fallback( $args ) {
        if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            $object_type = print_r( $args[0] ?? 'NOT SET', true );
            $meta_key    = print_r( $args[1] ?? 'NOT SET', true );
            $type        = print_r( $args[2]['type'] ?? 'NOT SET', true );
            $default     = print_r( $args[2]['default'] ?? 'NOT SET', true );

            error_log(
                'BRICKSLIFT DEBUG register_meta_args: Object Type: ' . $object_type .
                ', Meta Key: ' . $meta_key .
                ', Type: ' . $type .
                ', Default: ' . $default
            );
        }
        return $args;
    }
    add_filter( 'register_meta_args', '_blft_debug_log_register_meta_args_fallback', 9999, 1 );
}
// End temporary debugging
/**
 * Activation hook.
 */
register_activation_hook( __FILE__, [ '\BricksLiftAB\Core\Plugin', 'activate' ] );

/**
 * Deactivation hook.
 */
register_deactivation_hook( __FILE__, [ '\BricksLiftAB\Core\Plugin', 'deactivate' ] );