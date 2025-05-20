<?php
/**
 * Main REST API Controller for BricksLift A/B Testing.
 *
 * @package BricksLiftAB
 */

namespace BricksLiftAB\API;

use BricksLiftAB\API\Endpoints\Tests_Endpoint;
use BricksLiftAB\API\Endpoints\Stats_Endpoint;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class REST_Controller
 *
 * Initializes and manages all REST API endpoints for the plugin.
 */
class REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Register all REST API routes.
	 */
	public function register_rest_routes() {
		$controllers = $this->get_controllers();
		foreach ( $controllers as $controller ) {
			if ( method_exists( $controller, 'register_routes' ) ) {
				$controller->register_routes();
			}
		}
	}

	/**
	 * Get all available REST API endpoint controllers.
	 *
	 * @return array Array of controller instances.
	 */
	protected function get_controllers() {
		return [
			new Tests_Endpoint(),
			// Add other endpoint controllers here as they are created
			new Stats_Endpoint(),
		];
	}
}