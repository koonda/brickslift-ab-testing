<?php
/**
 * Frontend Controller for BricksLift A/B Testing.
 *
 * @package BricksLiftAB
 */

namespace BricksLiftAB\Frontend;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Frontend_Controller
 *
 * Handles enqueuing of frontend scripts and styles,
 * and makes test configuration data available to the frontend.
 */
class Frontend_Controller {

	const FRONTEND_SCRIPT_HANDLE = 'blft-frontend-main';
	const FRONTEND_STYLE_HANDLE  = 'blft-frontend-main-style';

	/**
	 * Constructor.
	 */
	public function __construct() {
	 $this->load_dependencies();
	 add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
	 // add_action( 'template_redirect', [ $this, 'check_for_conversion' ] ); // Removed: Rely on client-side JS for page_visit conversion
	 new Ajax_Handler(); // Initialize AJAX handler
	}

	/**
	 * Load required dependencies for frontend.
	 */
	private function load_dependencies() {
	 require_once BLFT_PLUGIN_DIR . 'src/Frontend/Ajax_Handler.php';
	}

	/**
	 * Enqueue scripts and styles for the frontend.
	 */
	public function enqueue_frontend_assets() {
		// Enqueue main CSS
		wp_enqueue_style(
			self::FRONTEND_STYLE_HANDLE,
			BLFT_PLUGIN_URL . 'frontend/css/blft-main.css',
			[],
			BLFT_VERSION
		);

		// Enqueue main JS
		wp_enqueue_script(
			self::FRONTEND_SCRIPT_HANDLE,
			BLFT_PLUGIN_URL . 'frontend/js/blft-main.js',
			[], // No specific JS dependencies for now
			BLFT_VERSION,
			true // Load in footer
		);

		// Localize data for the script
		// This will include active test configurations needed for variant selection.
		// For Fáze 3.2, we are simulating this in JS, but this is where it would go.
		// In later phases, we'll fetch actual running tests and their variant data.
		wp_localize_script(
			self::FRONTEND_SCRIPT_HANDLE,
			'BricksLiftAB_FrontendData',
			[
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'blft_frontend_nonce' ), // For all AJAX tracking events
				'active_tests'  => $this->get_active_tests_data_for_frontend(),
			]
		);
	}

	/**
	 * Get data for active tests to be passed to the frontend.
	 * This is a placeholder for Fáze 3.2. It will be populated with actual
	 * test data (variants, distributions, goal configs) in later phases.
	 *
	 * @return array
	 */
	private function get_active_tests_data_for_frontend() {
		$active_tests_data = [];
		$args = [
			'post_type'   => \BricksLiftAB\Core\CPT_Manager::CPT_SLUG,
			'numberposts' => -1,
			'post_status' => ['running', 'completed'], // Fetch 'running' and 'completed' tests
		];
		$running_tests = get_posts( $args );

		foreach ( $running_tests as $test ) {
			$variants_json = get_post_meta( $test->ID, '_blft_variants', true );
			$variants      = json_decode( $variants_json, true );

			if ( ! is_array( $variants ) || empty( $variants ) ) {
				continue; // Skip if no valid variants
			}

			$active_tests_data[ $test->ID ] = [
				'id'       => $test->ID,
				'status'   => $test->post_status, // Add the post status
				'variants' => $variants,
				'gdpr_settings' => [
					'consent_required'   => rest_sanitize_boolean(get_post_meta( $test->ID, '_blft_gdpr_consent_required', true )),
					'consent_mechanism'  => get_post_meta( $test->ID, '_blft_gdpr_consent_mechanism', true ) ?: 'none',
					'consent_key_name'   => get_post_meta( $test->ID, '_blft_gdpr_consent_key_name', true ),
					'consent_key_value'  => get_post_meta( $test->ID, '_blft_gdpr_consent_key_value', true ),
				],
				'run_tracking_globally' => rest_sanitize_boolean(get_post_meta( $test->ID, '_blft_run_tracking_globally', true )),
				// Add other necessary test settings here, e.g., goal_type for frontend conversion tracking
				'goal_type' => get_post_meta( $test->ID, '_blft_goal_type', true ) ?: 'page_visit',
				// Goal specific data for frontend JS to use in constructing goal_details
				'goal_pv_url' => get_post_meta( $test->ID, '_blft_goal_pv_url', true ),
				'goal_pv_url_match_type' => get_post_meta( $test->ID, '_blft_goal_pv_url_match_type', true ),
				'goal_sc_element_selector' => get_post_meta( $test->ID, '_blft_goal_sc_element_selector', true ),
				'goal_fs_form_selector' => get_post_meta( $test->ID, '_blft_goal_fs_form_selector', true ),
				// Add other goal-specific fields from CPT_Manager as needed by frontend logic

			];
		}
		return $active_tests_data;
	}

	// Removed check_for_conversion() method as client-side blft-main.js handles 'page_visit' conversions.

	/**
		* Get the current page URL.
		*
		* @return string The current URL.
		*/
	private function get_current_url() {
		// This method is no longer used by check_for_conversion but kept in case it's needed elsewhere.
		// If not, it can be removed too.
		global $wp;
		// home_url( add_query_arg( array(), $wp->request ) ) is good for permalinks
		// For simpler exact match, consider just protocol + host + request_uri
		$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
		$host     = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
		$request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
		return $protocol . $host . $request_uri;
	}
}