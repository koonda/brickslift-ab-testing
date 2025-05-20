<?php
/**
 * REST API Test Stats Daily Endpoint for BricksLift A/B Testing.
 *
 * @package BricksLiftAB
 */

namespace BricksLiftAB\API\Endpoints;

use WP_Error;
use WP_REST_Controller; // Added this line
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use BricksLiftAB\Core\DB_Manager;
use BricksLiftAB\Core\CPT_Manager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Test_Stats_Daily_Endpoint
 *
 * Handles the /test-stats-daily endpoint.
 */
class Test_Stats_Daily_Endpoint extends WP_REST_Controller {

	/**
	 * The namespace for the REST API.
	 *
	 * @var string
	 */
	protected $namespace = 'blft/v1';

	/**
	 * The base for this endpoint.
	 *
	 * @var string
	 */
	protected $rest_base = 'test-stats-daily';

	/**
	 * DB_Manager instance.
	 *
	 * @var DB_Manager
	 */
	protected $db_manager;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(); // Call parent constructor
		$this->namespace = 'blft/v1'; // Ensure namespace is set before parent constructor or route registration
		$this->rest_base = 'test-stats-daily'; // Ensure rest_base is set
		$this->db_manager = new DB_Manager();
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<test_id>[\d]+)', // Route includes test_id
			[
				'args'   => [
					'test_id' => [
						'description'       => esc_html__( 'Unique identifier for the A/B test.', 'brickslift-ab-testing' ),
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => function( $param, $request, $key ) {
							return is_numeric( $param ) && $param > 0;
						},
					],
				],
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ], // Changed from get_items
					'permission_callback' => [ $this, 'get_item_permissions_check' ], // Changed from get_items_permissions_check
					'args'                => $this->get_collection_params(), // Re-use for query params like date range
				],
			]
		);
	}

	/**
	 * Checks if a given request has access to get the item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		// Use 'manage_options' for now, consider a custom capability later e.g., 'view_blft_stats'.
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You do not have permission to view A/B test statistics.', 'brickslift-ab-testing' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}
		// Check if the test_id corresponds to a valid blft_test CPT.
		$test_id = (int) $request['test_id'];
		$post = get_post( $test_id );
		if ( ! $post || CPT_Manager::CPT_SLUG !== $post->post_type ) {
			return new WP_Error( 'rest_post_invalid_id', __( 'Invalid Test ID provided.', 'brickslift-ab-testing' ), [ 'status' => 404 ] );
		}
		return true;
	}

	/**
	 * Retrieves the item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$test_id    = (int) $request['test_id'];
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );

		// Validate date formats if provided
		if ( $start_date && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'Invalid start_date format. Use YYYY-MM-DD.', 'brickslift-ab-testing' ), [ 'status' => 400 ] );
		}
		if ( $end_date && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'Invalid end_date format. Use YYYY-MM-DD.', 'brickslift-ab-testing' ), [ 'status' => 400 ] );
		}

		global $wpdb;
		$table_stats_aggregated = $wpdb->prefix . 'blft_stats_aggregated';

		$query = $wpdb->prepare(
			"SELECT stat_date, variant_id, impressions_count, conversions_count
			 FROM {$table_stats_aggregated}
			 WHERE test_id = %d",
			$test_id
		);

		if ( $start_date ) {
			$query .= $wpdb->prepare( " AND stat_date >= %s", $start_date );
		}
		if ( $end_date ) {
			$query .= $wpdb->prepare( " AND stat_date <= %s", $end_date );
		}
		$query .= " ORDER BY stat_date ASC, variant_id ASC";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$daily_stats = $wpdb->get_results( $query );

		if ( $wpdb->last_error ) {
			// Log the detailed error for debugging if WP_DEBUG is on
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// error_log( 'BricksLift A/B Test Stats Daily API DB Error: ' . $wpdb->last_error );
			}
			// Return a generic error to the client
			return new WP_Error(
				'rest_db_error',
				__( 'An error occurred while fetching daily test statistics.', 'brickslift-ab-testing' ),
				[ 'status' => 500 ]
			);
		}

		// Get variant names
		$variant_names_map = [];
		$variants_json     = get_post_meta( $test_id, '_blft_variants', true );
		if ( ! empty( $variants_json ) ) {
			$variants_array = json_decode( $variants_json, true );
			if ( is_array( $variants_array ) ) {
				foreach ( $variants_array as $variant_detail ) {
					if ( isset( $variant_detail['id'] ) && isset( $variant_detail['name'] ) ) {
						$variant_names_map[ $variant_detail['id'] ] = $variant_detail['name'];
					}
				}
			}
		}

		// Group stats by date
		$response_data = [];
		$grouped_stats = [];
		foreach ( $daily_stats as $stat_row ) {
			$date_key = $stat_row->stat_date;
			if ( ! isset( $grouped_stats[ $date_key ] ) ) {
				$grouped_stats[ $date_key ] = [
					'date'     => $date_key,
					'variants' => [],
				];
			}
			$variant_id_str = (string) $stat_row->variant_id;
			$grouped_stats[ $date_key ]['variants'][] = [
				'variant_id'      => $variant_id_str,
				'variant_name'    => isset( $variant_names_map[ $variant_id_str ] ) ? $variant_names_map[ $variant_id_str ] : __( 'Unknown Variant', 'brickslift-ab-testing' ),
				'impressions'     => (int) $stat_row->impressions_count,
				'conversions'     => (int) $stat_row->conversions_count,
			];
		}
		// Ensure the final response is an array of date objects, even if only one date.
		$response_data = array_values( $grouped_stats );

		return new WP_REST_Response( $response_data, 200 );
	}

	/**
	 * Retrieves the query params for the collections.
	 *
	 * @return array Query parameters for the collection.
	 */
	public function get_collection_params() {
		return [
			'context'    => $this->get_context_param( [ 'default' => 'view' ] ),
			'start_date' => [
				'description'       => esc_html__( 'Start date for the statistics (YYYY-MM-DD).', 'brickslift-ab-testing' ),
				'type'              => 'string',
				'format'            => 'date',
				'sanitize_callback' => 'sanitize_text_field', // Basic sanitization
				'validate_callback' => 'rest_validate_request_arg',
			],
			'end_date'   => [
				'description'       => esc_html__( 'End date for the statistics (YYYY-MM-DD).', 'brickslift-ab-testing' ),
				'type'              => 'string',
				'format'            => 'date',
				'sanitize_callback' => 'sanitize_text_field', // Basic sanitization
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}
}