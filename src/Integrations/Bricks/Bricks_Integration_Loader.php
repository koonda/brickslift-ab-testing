<?php
/**
 * Bricks Integration Loader for BricksLift A/B Testing.
 *
 * @package BricksLiftAB
 */

namespace BricksLiftAB\Integrations\Bricks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Bricks_Integration_Loader
 *
 * Initializes the Bricks Builder integration.
 */
class Bricks_Integration_Loader {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Check if Bricks theme or plugin is active
		if ( ! $this->is_bricks_active() ) {
			return;
		}
		$this->load_dependencies();
		$this->add_hooks();
	}

	/**
	 * Check if Bricks is active.
	 *
	 * @return bool
	 */
	private function is_bricks_active() {
		// Check for Bricks theme (template) or Bricks plugin
		return defined( 'BRICKS_VERSION' ) || ( function_exists( 'bricks_is_active_theme' ) && bricks_is_active_theme() );
	}


	/**
	 * Load required dependencies for Bricks integration.
	 */
	private function load_dependencies() {
		require_once BLFT_PLUGIN_DIR . 'src/Integrations/Bricks/Element_Test_Wrapper.php';
	}

	/**
	 * Add WordPress and Bricks hooks.
	 */
	private function add_hooks() {
		// Hook to register custom elements in Bricks
		// Use the recommended Bricks hook for registering elements.
		add_action( 'bricks/elements/register_elements', [ $this, 'register_bricks_elements' ] );
	}

	/**
	 * Register custom Bricks elements.
	 *
	 * This function is hooked to 'init'. It checks if Bricks functions are available
	 * before attempting to register elements.
	 */
	public function register_bricks_elements() {
		if ( ! class_exists( '\Bricks\Elements' ) ) {
			// Bricks Elements class not found, perhaps Bricks is not fully loaded or active.
			return;
		}
		\Bricks\Elements::register_element( BLFT_PLUGIN_DIR . 'src/Integrations/Bricks/Element_Test_Wrapper.php' );
	}
}