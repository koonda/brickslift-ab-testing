<?php
/**
 * Database Manager for BricksLift A/B Testing.
 *
 * @package BricksLiftAB
 */

namespace BricksLiftAB\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DB_Manager
 *
 * Handles creation and management of custom database tables.
 */
class DB_Manager {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Initialization for DB Manager, if any, can go here.
		// Table creation is typically hooked to plugin activation.
	}

	/**
	 * Create custom database tables.
	 *
	 * This method should be called on plugin activation.
	 */
	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Table: blft_tracking
		$table_name_tracking = $wpdb->prefix . 'blft_tracking';
		$sql_tracking        = "CREATE TABLE $table_name_tracking (
		          id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		          test_id BIGINT(20) UNSIGNED NOT NULL,
		          variant_id VARCHAR(255) NOT NULL,
		          visitor_hash VARCHAR(64) NOT NULL,
		          event_type VARCHAR(50) NOT NULL,
		          event_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		          page_url TEXT,
		          goal_type VARCHAR(100) DEFAULT NULL,
		          goal_details TEXT DEFAULT NULL,
		          processed TINYINT(1) NOT NULL DEFAULT 0,
		          PRIMARY KEY  (id),
		          KEY idx_test_variant (test_id, variant_id),
		          KEY idx_visitor_event (visitor_hash, event_type),
		          KEY idx_goal_type (goal_type),
		          KEY idx_event_timestamp_processed (event_timestamp, processed)
		      ) $charset_collate;";
		dbDelta( $sql_tracking );

		// Table: blft_stats_aggregated
		$table_name_stats_aggregated = $wpdb->prefix . 'blft_stats_aggregated';
		$sql_stats_aggregated        = "CREATE TABLE $table_name_stats_aggregated (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			test_id BIGINT(20) UNSIGNED NOT NULL,
			variant_id VARCHAR(255) NOT NULL,
			stat_date DATE NOT NULL,
			impressions_count INT(11) UNSIGNED NOT NULL DEFAULT 0,
			conversions_count INT(11) UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY idx_test_variant_date (test_id, variant_id, stat_date)
		) $charset_collate;";
		dbDelta( $sql_stats_aggregated );
	}

	/**
	 * Remove custom database tables.
	 *
	 * This method could be called on plugin uninstallation.
	 */
	public static function remove_tables() {
		global $wpdb;
		$table_name_tracking         = $wpdb->prefix . 'blft_tracking';
		$table_name_stats_aggregated = $wpdb->prefix . 'blft_stats_aggregated';

		// $wpdb->query( "DROP TABLE IF EXISTS $table_name_tracking" );
		// $wpdb->query( "DROP TABLE IF EXISTS $table_name_stats_aggregated" );
		// For safety, actual drop queries are commented out.
		// Implement carefully in uninstall.php.
	}

	/**
		* Records a view for a specific variant of a test.
		*
		* Increments the impression count for the given test_id and variant_id
		* for the current date. If no record exists for the current date,
		* it creates a new one.
		*
		* @param int    $test_id    The ID of the A/B test.
		* @param string $variant_id The ID of the variant.
		* @return bool True on success, false on failure.
		*/
	public static function record_variant_view( $test_id, $variant_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'blft_stats_aggregated';
		$current_date = current_time( 'Y-m-d' );

		// Sanitize inputs
		$test_id    = absint( $test_id );
		$variant_id = sanitize_text_field( $variant_id );

		if ( empty( $test_id ) || empty( $variant_id ) ) {
			return false;
		}

		// Check if a record for this test, variant, and date already exists.
		$existing_record = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, impressions_count FROM $table_name WHERE test_id = %d AND variant_id = %s AND stat_date = %s",
				$test_id,
				$variant_id,
				$current_date
			)
		);

		if ( $existing_record ) {
			// Record exists, increment impressions_count.
			$result = $wpdb->update(
				$table_name,
				array( 'impressions_count' => $existing_record->impressions_count + 1 ),
				array( 'id' => $existing_record->id ),
				array( '%d' ), // format for impressions_count
				array( '%d' )  // format for id
			);
		} else {
			// No record for today, insert a new one.
			$result = $wpdb->insert(
				$table_name,
				array(
					'test_id'           => $test_id,
					'variant_id'        => $variant_id,
					'stat_date'         => $current_date,
					'impressions_count' => 1,
					'conversions_count' => 0, // Initialize conversions to 0
				),
				array(
					'%d', // test_id
					'%s', // variant_id
					'%s', // stat_date
					'%d', // impressions_count
					'%d', // conversions_count
				)
			);
		}

		return false !== $result;
	}

	/**
		* Records a conversion for a specific variant of a test.
		*
		* Increments the conversion count for the given test_id and variant_id
		* for the current date. If no record exists for the current date,
		* it creates a new one (though ideally, a view should have created it first).
		*
		* @param int    $test_id    The ID of the A/B test.
		* @param string $variant_id The ID of the variant.
		* @return bool True on success, false on failure.
		*/
	public static function record_variant_conversion( $test_id, $variant_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'blft_stats_aggregated';
		$current_date = current_time( 'Y-m-d' );

		// Sanitize inputs
		$test_id    = absint( $test_id );
		$variant_id = sanitize_text_field( $variant_id );

		if ( empty( $test_id ) || empty( $variant_id ) ) {
			return false;
		}

		// Check if a record for this test, variant, and date already exists.
		$existing_record = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, conversions_count FROM $table_name WHERE test_id = %d AND variant_id = %s AND stat_date = %s",
				$test_id,
				$variant_id,
				$current_date
			)
		);

		if ( $existing_record ) {
			// Record exists, increment conversions_count.
			$result = $wpdb->update(
				$table_name,
				array( 'conversions_count' => $existing_record->conversions_count + 1 ),
				array( 'id' => $existing_record->id ),
				array( '%d' ), // format for conversions_count
				array( '%d' )  // format for id
			);
		} else {
			// No record for today, insert a new one.
			// This case should ideally be rare for conversions if views are tracked properly.
			$result = $wpdb->insert(
				$table_name,
				array(
					'test_id'           => $test_id,
					'variant_id'        => $variant_id,
					'stat_date'         => $current_date,
					'impressions_count' => 0, // Assuming view was already recorded or not relevant here
					'conversions_count' => 1,
				),
				array(
					'%d', // test_id
					'%s', // variant_id
					'%s', // stat_date
					'%d', // impressions_count
					'%d', // conversions_count
				)
			);
		}
		return false !== $result;
	}

	/**
	 * Aggregates daily tracking data from blft_tracking into blft_stats_aggregated.
	 *
	 * This function is intended to be called by a daily cron job.
	 * It processes records from the PREVIOUS DAY.
	 */
	public function aggregate_daily_tracking_data() {
		global $wpdb;
		$table_tracking = $wpdb->prefix . 'blft_tracking';
		$table_stats_aggregated = $wpdb->prefix . 'blft_stats_aggregated';

		// Determine the date range for "yesterday"
		// Using WordPress current_time to respect WP timezone settings.
		$yesterday_date = date( 'Y-m-d', strtotime( '-1 day', current_time( 'timestamp' ) ) );
		$start_datetime = $yesterday_date . ' 00:00:00';
		$end_datetime   = $yesterday_date . ' 23:59:59';

		// Fetch unprocessed events from yesterday
		$raw_events = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT test_id, variant_id, event_type, COUNT(*) as event_count
				 FROM $table_tracking
				 WHERE processed = 0 AND event_timestamp BETWEEN %s AND %s
				 GROUP BY test_id, variant_id, event_type",
				$start_datetime,
				$end_datetime
			)
		);

		if ( empty( $raw_events ) ) {
			// error_log( "BricksLift A/B Testing: No new events to aggregate for $yesterday_date." );
			return; // No data to process
		}

		// Aggregate data
		$aggregated_data = [];
		foreach ( $raw_events as $event ) {
			$key = $event->test_id . '_' . $event->variant_id;
			if ( ! isset( $aggregated_data[ $key ] ) ) {
				$aggregated_data[ $key ] = [
					'test_id'           => $event->test_id,
					'variant_id'        => $event->variant_id,
					'impressions_count' => 0,
					'conversions_count' => 0,
				];
			}
			if ( 'view' === $event->event_type ) {
				$aggregated_data[ $key ]['impressions_count'] += $event->event_count;
			} elseif ( 'conversion' === $event->event_type ) {
				$aggregated_data[ $key ]['conversions_count'] += $event->event_count;
			}
		}

		// Store aggregated data
		foreach ( $aggregated_data as $data ) {
			$test_id           = absint( $data['test_id'] );
			$variant_id        = sanitize_text_field( $data['variant_id'] );
			$impressions_count = absint( $data['impressions_count'] );
			$conversions_count = absint( $data['conversions_count'] );

			// Check if a record for this test, variant, and date already exists.
			$existing_record = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, impressions_count, conversions_count FROM $table_stats_aggregated WHERE test_id = %d AND variant_id = %s AND stat_date = %s",
					$test_id,
					$variant_id,
					$yesterday_date
				)
			);

			if ( $existing_record ) {
				// Record exists, UPDATE by adding the new counts
				$new_impressions = $existing_record->impressions_count + $impressions_count;
				$new_conversions = $existing_record->conversions_count + $conversions_count;
				$wpdb->update(
					$table_stats_aggregated,
					[
						'impressions_count' => $new_impressions,
						'conversions_count' => $new_conversions,
					],
					[ 'id' => $existing_record->id ],
					[ '%d', '%d' ], // format for data
					[ '%d' ]        // format for where
				);
			} else {
				// No record for this combination for yesterday, INSERT a new one.
				$wpdb->insert(
					$table_stats_aggregated,
					[
						'test_id'           => $test_id,
						'variant_id'        => $variant_id,
						'stat_date'         => $yesterday_date,
						'impressions_count' => $impressions_count,
						'conversions_count' => $conversions_count,
					],
					[ '%d', '%s', '%s', '%d', '%d' ]
				);
			}
		}

		// Mark processed rows in blft_tracking
		// This is a critical step to prevent reprocessing.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $table_tracking
				 SET processed = 1
				 WHERE processed = 0 AND event_timestamp BETWEEN %s AND %s",
				$start_datetime,
				$end_datetime
			)
		);
		// error_log( "BricksLift A/B Testing: Successfully aggregated stats for $yesterday_date. Processed events: " . count($raw_events) );
	}
/**
	 * Retrieves aggregated statistics for a specific A/B test and date range.
	 *
	 * @param int    $test_id    The ID of the A/B test.
	 * @param string|null $start_date The start date for the statistics (Y-m-d format). Null for no start restriction.
	 * @param string|null $end_date   The end date for the statistics (Y-m-d format). Null for no end restriction.
	 * @return array|WP_Error Array of statistics objects on success, WP_Error on failure.
	 */
	public function get_aggregated_stats( $test_id, $start_date = null, $end_date = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'blft_stats_aggregated';

		$test_id = absint( $test_id );
		if ( ! $test_id ) {
			return new \WP_Error( 'invalid_test_id', __( 'Invalid Test ID provided.', 'brickslift-ab-testing' ) );
		}

		$query = "SELECT variant_id, SUM(impressions_count) as total_views, SUM(conversions_count) as total_conversions
				  FROM {$table_name}
				  WHERE test_id = %d";

		$params = [ $test_id ];

		if ( $start_date ) {
			// Validate date format
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
				return new \WP_Error( 'invalid_start_date', __( 'Invalid start date format. Please use YYYY-MM-DD.', 'brickslift-ab-testing' ) );
			}
			$query .= " AND stat_date >= %s";
			$params[] = $start_date;
		}

		if ( $end_date ) {
			// Validate date format
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
				return new \WP_Error( 'invalid_end_date', __( 'Invalid end date format. Please use YYYY-MM-DD.', 'brickslift-ab-testing' ) );
			}
			$query .= " AND stat_date <= %s";
			$params[] = $end_date;
		}

		$query .= " GROUP BY variant_id ORDER BY variant_id ASC";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

		if ( $wpdb->last_error ) {
			return new \WP_Error( 'db_query_error', $wpdb->last_error, [ 'status' => 500 ] );
		}

		return $results;
	}

	/**
		* Retrieves the total number of conversions for a specific variant of a test.
		*
		* @param int    $test_id    The ID of the A/B test.
		* @param string $variant_id The ID of the variant.
		* @return int The total number of conversions.
		*/
	public function get_total_conversions_for_variant( $test_id, $variant_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'blft_stats_aggregated';

		$test_id    = absint( $test_id );
		$variant_id = sanitize_text_field( $variant_id );

		if ( ! $test_id || ! $variant_id ) {
			return 0;
		}

		$total_conversions = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(conversions_count)
				 FROM {$table_name}
				 WHERE test_id = %d AND variant_id = %s",
				$test_id,
				$variant_id
			)
		);

		return (int) $total_conversions;
	}

	/**
		* Retrieves the total number of views (impressions) for a specific variant of a test.
		*
		* @param int    $test_id    The ID of the A/B test.
		* @param string $variant_id The ID of the variant.
		* @return int The total number of views.
		*/
	public function get_total_views_for_variant( $test_id, $variant_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'blft_stats_aggregated';

		$test_id    = absint( $test_id );
		$variant_id = sanitize_text_field( $variant_id );

		if ( ! $test_id || ! $variant_id ) {
			return 0;
		}

		$total_views = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(impressions_count)
				 FROM {$table_name}
				 WHERE test_id = %d AND variant_id = %s",
				$test_id,
				$variant_id
			)
		);

		return (int) $total_views;
	}
} // Closes the DB_Manager class