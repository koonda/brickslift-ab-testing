<?php
/**
 * Admin Assets Manager for BricksLift A/B Testing.
 *
 * @package BricksLiftAB
 */

namespace BricksLiftAB\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Assets_Manager
 *
 * Handles enqueuing of admin scripts and styles.
 */
class Assets_Manager {

	const ADMIN_SCRIPT_HANDLE = 'blft-admin-app';

	private $target_hook_suffix;

	/**
	 * Constructor.
	 *
	 * @param string $dashboard_hook_suffix The correct hook suffix for the dashboard page.
	 */
	public function __construct( string $dashboard_hook_suffix ) {
		$this->target_hook_suffix = $dashboard_hook_suffix;

		error_log('[BricksLift A/B Debug] Assets_Manager constructed. Target hook suffix received: ' . $this->target_hook_suffix);

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Enqueue scripts and styles for the admin area.
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		// Only load on our specific admin page.
		// The React app is now a submenu page.
		// Parent CPT slug: 'blft_test'
		// Submenu page slug: 'brickslift-ab-dashboard'
		// Expected hook: 'blft_test_page_brickslift-ab-dashboard'
		// WordPress also generates a hook for the CPT's main listing page: 'edit.php?post_type=blft_test' -> 'edit-blft_test'
		// And for the CPT's add new page: 'post-new.php?post_type=blft_test' -> 'post-new-blft_test'
		// We only want to load assets on our custom dashboard page.

		// Log the values for debugging
		error_log('[BricksLift A/B Debug] In enqueue_admin_assets. Current hook_suffix: ' . $hook_suffix);
		error_log('[BricksLift A/B Debug] Target hook_suffix from constructor: ' . $this->target_hook_suffix);
		error_log('[BricksLift A/B Debug] Comparison result (target === current): ' . ($this->target_hook_suffix === $hook_suffix ? 'true' : 'false'));

		if ( $this->target_hook_suffix !== $hook_suffix ) {
			error_log('[BricksLift A/B Debug] Hooks do not match. Assets not enqueued. Current: \'' . $hook_suffix . '\', Target: \'' . $this->target_hook_suffix . '\'');
			return;
		}
		error_log('[BricksLift A/B Debug] Hooks matched! Proceeding to enqueue assets. Current: \'' . $hook_suffix . '\', Target: \'' . $this->target_hook_suffix . '\'');

		$script_asset_path = BLFT_PLUGIN_DIR . 'admin-ui/build/index.asset.php';
		$script_url        = BLFT_PLUGIN_URL . 'admin-ui/build/index.js';
		$style_url         = BLFT_PLUGIN_URL . 'admin-ui/build/index.css';

		// Restore file_exists check
		if ( ! file_exists( $script_asset_path ) ) {
			// translators: %s: path to the asset file.
			$error_message = sprintf( __( 'BricksLift A/B Testing: Admin asset file not found: %s. Path checked: %s. Please ensure assets are built and deployed correctly.', 'brickslift-ab-testing' ), basename($script_asset_path), $script_asset_path );
			error_log('[BricksLift A/B Debug] FATAL: ' . $error_message);
			wp_die( esc_html( $error_message ) );
			return; // Should be redundant due to wp_die
		}
		error_log('[BricksLift A/B Debug] file_exists check for ' . $script_asset_path . ' PASSED.');

		$script_asset = require $script_asset_path;
		error_log('[BricksLift A/B Debug] Script asset path loaded: ' . $script_asset_path);

		// Removed wp_die for further debugging. Allowing enqueue functions to run.

		// Use dependencies from asset file, but filter out 'react' and 'react-jsx-runtime'
		$original_dependencies = $script_asset['dependencies'];
		$filtered_dependencies = array_filter($original_dependencies, function($dep) {
			return $dep !== 'react' && $dep !== 'react-jsx-runtime';
		});
		// Ensure 'wp-element' is present if React-related dependencies were filtered, as it should provide the React abstraction.
		if (in_array('react', $original_dependencies, true) || in_array('react-jsx-runtime', $original_dependencies, true)) {
			if (!in_array('wp-element', $filtered_dependencies, true)) {
				$filtered_dependencies[] = 'wp-element'; // Add wp-element if not already there
			}
		}
		$filtered_dependencies = array_unique($filtered_dependencies); // Remove duplicates if any

		error_log('[BricksLift A/B Debug] Original dependencies: ' . implode(', ', $original_dependencies));
		error_log('[BricksLift A/B Debug] Using filtered dependencies: ' . implode(', ', $filtered_dependencies));

		wp_enqueue_script(
			self::ADMIN_SCRIPT_HANDLE,
			$script_url,
			$filtered_dependencies, // Use filtered dependencies
			$script_asset['version'],
			true // Load in footer
		);
		error_log('[BricksLift A/B Debug] wp_enqueue_script called for ' . self::ADMIN_SCRIPT_HANDLE . ' with ' . count($filtered_dependencies) . ' filtered dependencies.');

		wp_enqueue_style(
			self::ADMIN_SCRIPT_HANDLE . '-style', // Use a related handle for the style
			$style_url,
			[], // No specific style dependencies for now - assuming index.css has its imports or is self-contained
			$script_asset['version'] // Use the same version for cache busting
		);
		error_log('[BricksLift A/B Debug] wp_enqueue_style called for ' . self::ADMIN_SCRIPT_HANDLE . '-style');

		// Pass data to script if needed, e.g., REST API nonce, base URL.
		wp_localize_script(
			self::ADMIN_SCRIPT_HANDLE,
			'BricksLiftAB_AdminData',
			[
				'rest_url' => esc_url_raw( rest_url() ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				// Add other data as needed
			]
		);
		error_log('[BricksLift A/B Debug] wp_localize_script called for BricksLiftAB_AdminData.');
	}
}