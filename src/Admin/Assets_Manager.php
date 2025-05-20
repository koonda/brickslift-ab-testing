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

	/**
	 * Constructor.
	 */
	public function __construct() {
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
		$target_hook = \BricksLiftAB\Core\CPT_Manager::CPT_SLUG . '_page_brickslift-ab-dashboard';
		if ( $target_hook !== $hook_suffix ) {
			return;
		}

		$script_asset_path = BLFT_PLUGIN_DIR . 'admin-ui/build/index.asset.php';
		$script_url        = BLFT_PLUGIN_URL . 'admin-ui/build/index.js';
		$style_url         = BLFT_PLUGIN_URL . 'admin-ui/build/index.css';

		if ( ! file_exists( $script_asset_path ) ) {
			// translators: %s: path to the asset file.
			wp_die( esc_html( sprintf( __( 'BricksLift A/B Testing: You need to build the admin assets. Run "npm install && npm run build" in the %s directory.', 'brickslift-ab-testing' ), 'admin-ui' ) ) );
			return;
		}

		$script_asset = require $script_asset_path;

		wp_enqueue_script(
			self::ADMIN_SCRIPT_HANDLE,
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true // Load in footer
		);

		wp_enqueue_style(
			self::ADMIN_SCRIPT_HANDLE . '-style', // Use a related handle for the style
			$style_url,
			[], // No specific style dependencies for now
			$script_asset['version'] // Use the same version for cache busting
		);

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
	}
}