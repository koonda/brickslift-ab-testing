<?php
/**
 * REST API Stats Endpoint for BricksLift A/B Testing.
 *
 * @package BricksLiftAB
 */

namespace BricksLiftAB\API\Endpoints;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use BricksLiftAB\Core\DB_Manager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Stats_Endpoint
 *
 * Handles the /stats endpoint.
 */
class Stats_Endpoint {

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
	protected $rest_base = 'stats';

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
		$this->db_manager = new DB_Manager();
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $this->get_collection_params(),
				],
			]
		);
	}

	/**
	 * Checks if a given request has access to get items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		// Use 'manage_options' for now, consider a custom capability later e.g., 'view_blft_stats'.
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You do not have permission to view A/B test statistics.', 'brickslift-ab-testing' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}
		return true;
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$test_id = $request->get_param( 'test_id' );
		$period  = $request->get_param( 'period' );

		if ( empty( $test_id ) || ! is_numeric( $test_id ) ) {
			return new WP_Error(
				'rest_invalid_param',
				esc_html__( 'Invalid or missing test_id.', 'brickslift-ab-testing' ),
				[ 'status' => 400, 'params' => [ 'test_id' ] ]
			);
		}
		$test_id = (int) $test_id;

		$supported_periods = [ 'last_7_days', 'last_30_days', 'current_month', 'all_time' ];
		if ( empty( $period ) ) {
			$period = 'last_7_days';
		} elseif ( ! in_array( $period, $supported_periods, true ) ) {
			return new WP_Error(
				'rest_invalid_param',
				esc_html__( 'Invalid period specified.', 'brickslift-ab-testing' ),
				[ 'status' => 400, 'params' => [ 'period' ] ]
			);
		}

		list( $start_date, $end_date ) = $this->calculate_date_range( $period );

		$stats_data = $this->db_manager->get_aggregated_stats( $test_id, $start_date, $end_date );

		if ( is_wp_error( $stats_data ) ) {
			// Log the detailed error for debugging if WP_DEBUG is on
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// error_log( 'BricksLift A/B Stats API DB Error: ' . $stats_data->get_error_message() );
			}
			// Return a generic error to the client
			return new WP_Error(
				'rest_db_error',
				__( 'An error occurred while fetching statistics.', 'brickslift-ab-testing' ),
				[ 'status' => 500 ]
			);
		}
		
		// Get variant names efficiently
		$variant_names_map = [];
		$test_post = get_post( $test_id );

		if ( $test_post && $test_post->post_type === \BricksLiftAB\Core\CPT_Manager::CPT_SLUG ) {
			$variants_json = get_post_meta( $test_id, '_blft_variants', true );
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
		}

		$formatted_data = [];
		foreach ( $stats_data as $stat_item ) {
			// The variant_id from stats_data is a string, ensure consistent comparison or casting if needed.
			// In blft_stats_aggregated, variant_id is VARCHAR, matching the string ID from _blft_variants.
			$variant_id_str = (string) $stat_item->variant_id;
			$variant_name = isset( $variant_names_map[ $variant_id_str ] ) ? $variant_names_map[ $variant_id_str ] : __( 'Unknown Variant', 'brickslift-ab-testing' ) . ' (' . $variant_id_str . ')';

			$formatted_data[] = [
				// Ensure variant_id in response is consistent with what frontend expects (string or int)
				// The schema for Tests_Endpoint returns variant ID as string.
				// blft_stats_aggregated stores variant_id as VARCHAR.
				// $stat_item->variant_id is likely already a string from DB.
				'variant_id'      => $variant_id_str,
				'variant_name'    => $variant_name,
				'views'           => (int) $stat_item->total_views,
				'conversions'     => (int) $stat_item->total_conversions,
			];
		}

		$response_data = [
			'test_id' => $test_id,
			'period'  => $period,
			'data'    => $formatted_data,
		];

		return new WP_REST_Response( $response_data, 200 );
	}

	/**
	 * Calculates the start and end dates based on the period.
	 *
	 * @param string $period The period string.
	 * @return array [start_date, end_date]
	 */
	protected function calculate_date_range( $period ) {
		$start_date = null;
		$end_date   = current_time( 'Y-m-d' ); // Today

		switch ( $period ) {
			case 'last_7_days':
				$start_date = gmdate( 'Y-m-d', strtotime( '-6 days', strtotime( $end_date ) ) );
				break;
			case 'last_30_days':
				$start_date = gmdate( 'Y-m-d', strtotime( '-29 days', strtotime( $end_date ) ) );
				break;
			case 'current_month':
				$start_date = current_time( 'Y-m-01' );
				break;
			case 'all_time':
			default:
				$start_date = null; // No start date restriction
				$end_date   = null; // No end date restriction
				break;
		}
		return [ $start_date, $end_date ];
	}

	/**
	 * Retrieves the query params for the collections.
	 *
	 * @return array Query parameters for the collection.
	 */
	public function get_collection_params() {
		return [
			'test_id' => [
				'description'       => esc_html__( 'The ID of the A/B test.', 'brickslift-ab-testing' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'period'  => [
				'description'       => esc_html__( 'Defines the time range for the statistics.', 'brickslift-ab-testing' ),
				'type'              => 'string',
				'default'           => 'last_7_days',
				'enum'              => [ 'last_7_days', 'last_30_days', 'current_month', 'all_time' ],
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}
}