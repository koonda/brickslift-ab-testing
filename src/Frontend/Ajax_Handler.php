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
			$error_message = __( 'Failed to record event.', 'brickslift-ab-testing' );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// Optionally log the detailed error for debugging purposes
				// error_log( 'BricksLift A/B DB Error: ' . $wpdb->last_error );
				// If WP_DEBUG_DISPLAY is also on, you might choose to send it, but generally avoid for production.
				// For this AJAX handler, let's stick to a generic message for the client.
			}
			wp_send_json_error( [ 'message' => $error_message ], 500 );
		} else {
			// Event successfully inserted into blft_tracking table.
			// Now handle cookie logic based on event type.

			$cookie_name   = 'blft_shown_test_' . $test_id;
			$cookie_path   = COOKIEPATH ?: '/';
			$cookie_domain = COOKIE_DOMAIN ?: '';
			$secure        = is_ssl();
			$httponly      = true;
			$cookie_set    = false;

			if ( 'view' === $event_type ) {
				// Store the displayed variant in a cookie for stickiness
				$cookie_value  = $variant_id;
				$cookie_expire = time() + ( 30 * DAY_IN_SECONDS ); // 30 days
				$cookie_set = setcookie( $cookie_name, $cookie_value, $cookie_expire, $cookie_path, $cookie_domain, $secure, $httponly );
			} elseif ( 'conversion' === $event_type ) {
				// Clear the cookie for this test to allow re-evaluation or prevent multiple conversions for the same view cycle if needed.
				$cookie_set = setcookie( $cookie_name, '', time() - 3600, $cookie_path, $cookie_domain, $secure, $httponly );
			}

			wp_send_json_success( [
				'message'    => __( 'Event tracked successfully.', 'brickslift-ab-testing' ),
				'cookie_set' => $cookie_set, // Include cookie status for debugging if needed
			] );
		}
	}
}