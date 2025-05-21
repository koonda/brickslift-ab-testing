<?php
/**
 * Plugin Name: BricksLift A/B Testing
 * Plugin URI: https://brickslift.com/
 * Description: A/B testing for Bricks Builder.
 * Version: 0.5.3
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

define( 'BLFT_VERSION', '0.5.3' );
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
// Temporary debug action to inspect $wp_scripts
add_action( 'admin_print_footer_scripts', function() {
    global $wp_scripts;
    if ( isset( $wp_scripts->registered['blft-admin-app'] ) ) {
        error_log( '[BricksLift A/B Debug] $wp_scripts->registered[\'blft-admin-app\']: ' . print_r( $wp_scripts->registered['blft-admin-app'], true ) );
        if ( isset( $wp_scripts->registered['blft-admin-app']->extra['data'] ) ) {
            error_log( '[BricksLift A/B Debug] Localized data for blft-admin-app: ' . $wp_scripts->registered['blft-admin-app']->extra['data'] );
        } else {
            error_log( '[BricksLift A/B Debug] NO Localized data found for blft-admin-app in $wp_scripts.' );
        }
    } else {
        error_log( '[BricksLift A/B Debug] blft-admin-app NOT FOUND in $wp_scripts->registered at admin_print_footer_scripts.' );
    }
    // Optional: error_log( '[BricksLift A/B Debug] $wp_scripts->queue: ' . print_r( $wp_scripts->queue, true ) );
}, 9999 ); // High priority to run late
// --- Start of New Debug Snippets for admin_footer ---

// Check if admin_footer action starts
add_action( 'admin_footer', function() {
    error_log( '[BricksLift A/B Debug] admin_footer action STARTED (priority 1 hook).' );

    // Check if wp_print_footer_scripts is hooked to admin_footer
    global $wp_filter;
    $hook_name = 'admin_footer';
    $priority_to_check = 20; // Default priority for wp_print_footer_scripts
    $function_to_check = 'wp_print_footer_scripts';
    $is_hooked = false;

    if ( isset( $wp_filter[ $hook_name ] ) && isset( $wp_filter[ $hook_name ]->callbacks[ $priority_to_check ] ) ) {
        foreach ( $wp_filter[ $hook_name ]->callbacks[ $priority_to_check ] as $callback ) {
            if ( isset( $callback['function'] ) && $callback['function'] === $function_to_check ) {
                $is_hooked = true;
                break;
            }
        }
    }
    if ( $is_hooked ) {
        error_log( '[BricksLift A/B Debug] wp_print_footer_scripts IS HOOKED to admin_footer at priority ' . $priority_to_check . '.' );
    } else {
        error_log( '[BricksLift A/B Debug] wp_print_footer_scripts IS NOT HOOKED to admin_footer at default priority ' . $priority_to_check . '. Checking other priorities...' );
        $found_at_other_priority = false;
        if (isset($wp_filter[$hook_name])) {
            foreach ($wp_filter[$hook_name]->callbacks as $priority => $callbacks_at_priority) {
                foreach ($callbacks_at_priority as $callback_details) {
                    if (isset($callback_details['function']) && $callback_details['function'] === $function_to_check) {
                        error_log('[BricksLift A/B Debug] Found wp_print_footer_scripts hooked at priority: ' . $priority);
                        $found_at_other_priority = true;
                        break 2; // Break both loops
                    }
                }
            }
        }
        if (!$found_at_other_priority) {
             error_log('[BricksLift A/B Debug] wp_print_footer_scripts NOT FOUND on admin_footer at any priority.');
        }
    }

}, 1 ); // Run very early

// Check just before wp_print_footer_scripts (default priority 20)
add_action( 'admin_footer', function() {
    error_log( '[BricksLift A/B Debug] admin_footer hook: Just BEFORE wp_print_footer_scripts should run (priority 19 check).' );
}, 19 );

// Check just after wp_print_footer_scripts (default priority 20)
add_action( 'admin_footer', function() {
    error_log( '[BricksLift A/B Debug] admin_footer hook: Just AFTER wp_print_footer_scripts should have run (priority 21 check).' );
}, 21 );

// Check if admin_footer action ends (very late)
add_action( 'admin_footer', function() {
    error_log( '[BricksLift A/B Debug] admin_footer action ENDED (priority 99999 hook).' );
}, 99999 ); // Run very late

// --- End of New Debug Snippets for admin_footer ---
// --- Start of Fix: Manually call wp_print_footer_scripts ---

// Manually call wp_print_footer_scripts because it seems to be unhooked
add_action( 'admin_footer', function() {
    error_log( '[BricksLift A/B Debug] Manually calling wp_print_footer_scripts().' );
    wp_print_footer_scripts();
    error_log( '[BricksLift A/B Debug] Manual call to wp_print_footer_scripts() finished.' );
// --- Add this line ---
    ob_end_flush();
    // --- End of added line ---
}, 99 ); // Use a high priority to run after most other hooks, but before our very late debug log (99999)
// Note: The existing debug hooks (priority 1, 19, 21, 9999, 99999) should remain for now to confirm execution flow.

// --- End of Fix: Manually call wp_print_footer_scripts ---
// --- Start of New Debug Snippets for Output Buffering ---

// Check output buffering state very early in admin_footer
add_action( 'admin_footer', function() {
    error_log( '[BricksLift A/B Debug] OB Check (Priority 5): Level=' . ob_get_level() . ', Length=' . ob_get_length() );
}, 5 );

// Check output buffering state just before our manual wp_print_footer_scripts call (priority 99)
add_action( 'admin_footer', function() {
    error_log( '[BricksLift A/B Debug] OB Check (Priority 98): Level=' . ob_get_level() . ', Length=' . ob_get_length() );
}, 98 ); // Priority 98 is just before our manual call at 99

// Check output buffering state just after our manual wp_print_footer_scripts call (priority 99)
add_action( 'admin_footer', function() {
    error_log( '[BricksLift A/B Debug] OB Check (Priority 100): Level=' . ob_get_level() . ', Length=' . ob_get_length() );
}, 100 ); // Priority 100 is just after our manual call at 99

// Check output buffering state very late in admin_footer
add_action( 'admin_footer', function() {
    error_log( '[BricksLift A/B Debug] OB Check (Priority 99998): Level=' . ob_get_level() . ', Length=' . ob_get_length() );
}, 99998 ); // Priority 99998 is just before our very late ENDED log at 99999

// --- End of New Debug Snippets for Output Buffering ---