<?php
/**
 * AJAX Handler for Frontend Events for BricksLift A/B Testing.
 *
 * @package BricksLiftAB
 */

namespace BricksLiftAB\Frontend;

use BricksLiftAB\Core\DB_Manager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Ajax_Handler
 *
 * Handles AJAX requests from the frontend for event tracking.
 */
class Ajax_Handler {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_blft_track_event', [ $this, 'track_event' ] );
		add_action( 'wp_ajax_nopriv_blft_track_event', [ $this, 'track_event' ] ); // For non-logged-in users

		add_action( 'wp_ajax_blft_track_view', [ $this, 'handle_track_view' ] );
		add_action( 'wp_ajax_nopriv_blft_track_view', [ $this, 'handle_track_view' ] );

		add_action( 'wp_ajax_blft_track_conversion', [ $this, 'handle_track_conversion' ] );
		add_action( 'wp_ajax_nopriv_blft_track_conversion', [ $this, 'handle_track_conversion' ] );
	}

	/**
	 * Handle event tracking AJAX request.
	 */
	public function track_event() {
		// Verify nonce
		check_ajax_referer( 'blft_frontend_nonce', 'nonce' );

		$test_id      = isset( $_POST['test_id'] ) ? absint( $_POST['test_id'] ) : 0;
		$variant_id   = isset( $_POST['variant_id'] ) ? sanitize_text_field( wp_unslash( $_POST['variant_id'] ) ) : '';
		$event_type   = isset( $_POST['event_type'] ) ? sanitize_key( $_POST['event_type'] ) : ''; // 'view' or 'conversion'
		$visitor_hash = isset( $_POST['visitor_hash'] ) ? sanitize_text_field( wp_unslash( $_POST['visitor_hash'] ) ) : '';
		$page_url     = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';
		$goal_type    = isset( $_POST['goal_type'] ) ? sanitize_text_field( wp_unslash( $_POST['goal_type'] ) ) : '';
		$goal_details = isset( $_POST['goal_details'] ) ? wp_kses_post( wp_unslash( $_POST['goal_details'] ) ) : ''; // JSON string, wp_kses_post for broader sanitization, then validate if it's valid JSON.

		if ( ! $test_id || empty( $variant_id ) || empty( $event_type ) || empty( $visitor_hash ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing required base tracking data.', 'brickslift-ab-testing' ) ], 400 );
			return;
		}

		// Additional validation for conversion events
		if ( 'conversion' === $event_type ) {
			if ( empty( $goal_type ) ) {
				wp_send_json_error( [ 'message' => __( 'Missing goal_type for conversion event.', 'brickslift-ab-testing' ) ], 400 );
				return;
			}
			// Validate goal_details as JSON
			if ( ! empty( $goal_details ) ) {
				json_decode( $goal_details );
				if ( json_last_error() !== JSON_ERROR_NONE ) {
					wp_send_json_error( [ 'message' => __( 'Invalid goal_details format. Expected JSON.', 'brickslift-ab-testing' ) ], 400 );
					return;
				}
			}
		}

		// Basic validation for event type
		if ( ! in_array( $event_type, [ 'view', 'conversion' ], true ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid event type.', 'brickslift-ab-testing' ) ], 400 );
			return;
		}
		
		// @TODO: Add GDPR check here before inserting if Fáze 4.2 logic is integrated directly or via a helper.
		// For now, we assume consent is handled by the JS calling this.

		global $wpdb;
		$table_name = $wpdb->prefix . 'blft_tracking';

		$data_to_insert = [
			'test_id'         => $test_id,
			'variant_id'      => $variant_id,
			'visitor_hash'    => $visitor_hash,
			'event_type'      => $event_type,
			'event_timestamp' => current_time( 'mysql', 1 ), // GMT timestamp
			'page_url'        => $page_url,
		];

		$data_format = [
			'%d', // test_id
			'%s', // variant_id
			'%s', // visitor_hash
			'%s', // event_type
			'%s', // event_timestamp
			'%s', // page_url
		];

		if ( 'conversion' === $event_type ) {
			$data_to_insert['goal_type']    = $goal_type;
			$data_to_insert['goal_details'] = $goal_details; // Already sanitized, potentially JSON string
			$data_format[]                  = '%s'; // goal_type
			$data_format[]                  = '%s'; // goal_details
		}

		$inserted = $wpdb->insert(
			$table_name,
			$data_to_insert,
			$data_format
		);

		if ( false === $inserted ) {
			wp_send_json_error( [ 'message' => __( 'Failed to record event.', 'brickslift-ab-testing' ), 'db_error' => $wpdb->last_error ], 500 );
		} else {
			wp_send_json_success( [ 'message' => __( 'Event tracked successfully.', 'brickslift-ab-testing' ) ] );
		}
	}

	/**
	 * Handle A/B test variant view tracking AJAX request.
	 *
	 * Records the view and sets a cookie for the displayed variant.
	 */
	public function handle_track_view() {
		// Verify nonce
		check_ajax_referer( 'blft_track_view_nonce', 'nonce' );

		$test_id    = isset( $_POST['test_id'] ) ? absint( $_POST['test_id'] ) : 0;
		$variant_id = isset( $_POST['variant_id'] ) ? sanitize_text_field( wp_unslash( $_POST['variant_id'] ) ) : '';

		if ( ! $test_id || empty( $variant_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing test_id or variant_id.', 'brickslift-ab-testing' ) ], 400 );
			return;
		}

		// Record the view in the database
		$recorded = DB_Manager::record_variant_view( $test_id, $variant_id );

		if ( ! $recorded ) {
			wp_send_json_error( [ 'message' => __( 'Failed to record variant view.', 'brickslift-ab-testing' ) ], 500 );
			return;
		}

		// Store the displayed variant in a cookie
		$cookie_name     = 'blft_shown_test_' . $test_id;
		$cookie_value    = $variant_id;
		$cookie_expire   = time() + ( 30 * DAY_IN_SECONDS ); // 30 days
		$cookie_path     = COOKIEPATH ?: '/';
		$cookie_domain   = COOKIE_DOMAIN ?: '';
		$secure          = is_ssl();
		$httponly        = true;

		// Set the cookie
		// Note: `setcookie` should be called before any output. WordPress AJAX handlers buffer output, so this is generally fine.
		$cookie_set = setcookie( $cookie_name, $cookie_value, $cookie_expire, $cookie_path, $cookie_domain, $secure, $httponly );

		if ( ! $cookie_set ) {
			// Optionally log this, but don't fail the request just because cookie setting failed.
			// The view is already recorded.
			// error_log("BricksLift A/B Test: Failed to set cookie {$cookie_name}");
		}

		wp_send_json_success(
			[
				'message'     => __( 'Variant view tracked successfully.', 'brickslift-ab-testing' ),
				'test_id'     => $test_id,
				'variant_id'  => $variant_id,
				'cookie_set'  => $cookie_set, // For debugging/confirmation if needed
			]
		);
	}

	/**
	 * Handle A/B test variant conversion tracking AJAX request.
	 *
	 * Records the conversion and clears the tracking cookie for the test.
	 */
	public function handle_track_conversion() {
		// Verify nonce
		check_ajax_referer( 'blft_track_conversion_nonce', 'nonce' );

		$test_id    = isset( $_POST['test_id'] ) ? absint( $_POST['test_id'] ) : 0;
		$variant_id = isset( $_POST['variant_id'] ) ? sanitize_text_field( wp_unslash( $_POST['variant_id'] ) ) : '';

		if ( ! $test_id || empty( $variant_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing test_id or variant_id for conversion tracking.', 'brickslift-ab-testing' ) ], 400 );
			return;
		}

		// Record the conversion in the database
		$recorded = DB_Manager::record_variant_conversion( $test_id, $variant_id );

		if ( ! $recorded ) {
			wp_send_json_error( [ 'message' => __( 'Failed to record variant conversion.', 'brickslift-ab-testing' ) ], 500 );
			return;
		}

		// Clear the cookie for this test to prevent multiple conversions for the same view cycle
		$cookie_name   = 'blft_shown_test_' . $test_id;
		$cookie_path   = COOKIEPATH ?: '/';
		$cookie_domain = COOKIE_DOMAIN ?: '';
		
		// To clear a cookie, set its expiration date to the past.
		// WordPress doesn't have a dedicated function to clear cookies directly via setcookie parameters in the same way it sets them,
		// but setting to a past time and empty value is the standard PHP way.
		// The `unset( $_COOKIE[ $cookie_name ] )` only removes it from the $_COOKIE superglobal for the current request,
		// it doesn't instruct the browser to remove it.
		setcookie( $cookie_name, '', time() - 3600, $cookie_path, $cookie_domain );


		wp_send_json_success(
			[
				'message'    => __( 'Variant conversion tracked successfully.', 'brickslift-ab-testing' ),
				'test_id'    => $test_id,
				'variant_id' => $variant_id,
			]
		);
	}
}