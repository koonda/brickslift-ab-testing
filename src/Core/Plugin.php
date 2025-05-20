<?php
/**
 * Main Plugin Class for BricksLift A/B Testing.
 *
 * @package BricksLiftAB
 */

namespace BricksLiftAB\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 *
 * Initializes the plugin and its components.
 */
class Plugin {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * CPT_Manager instance.
	 *
	 * @var CPT_Manager
	 */
	public $cpt_manager;

	/**
	 * Admin_Controller instance.
	 *
	 * @var \BricksLiftAB\Admin\Admin_Controller
	 */
	public $admin_controller;

	/**
	 * REST_Controller instance.
	 *
	 * @var \BricksLiftAB\API\REST_Controller
	 */
	public $rest_controller;

	/**
	 * Bricks_Integration_Loader instance.
	 *
	 * @var \BricksLiftAB\Integrations\Bricks\Bricks_Integration_Loader
	 */
	public $bricks_integration_loader;

	/**
	 * Frontend_Controller instance.
	 *
	 * @var \BricksLiftAB\Frontend\Frontend_Controller
	 */
	public $frontend_controller;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_textdomain();
		$this->load_dependencies();
		$this->init_components();
		$this->add_hooks();
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'brickslift-ab-testing',
			false,
			dirname( plugin_basename( BLFT_PLUGIN_FILE ) ) . '/languages/'
		);
	}

	/**
	 * Get the singleton instance of this class.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Load required dependencies.
	 */
	private function load_dependencies() {
		// Autoloader is handled by Composer, so explicit require_once calls for classes
		// within the BricksLiftAB namespace are generally not needed if files are correctly named and located.
		// For example, `new CPT_Manager()` will be autoloaded from `src/Core/CPT_Manager.php`.
		// If there were any non-namespaced procedural files or third-party libraries not managed by Composer,
		// they would be required here.
	}

	/**
		* Initialize plugin components.
		*/
	private function init_components() {
		$this->cpt_manager = new CPT_Manager();
		$this->rest_controller = new \BricksLiftAB\API\REST_Controller();

		if ( is_admin() ) {
			$this->admin_controller = new \BricksLiftAB\Admin\Admin_Controller();
		}
		$this->bricks_integration_loader = new \BricksLiftAB\Integrations\Bricks\Bricks_Integration_Loader();
		if ( ! is_admin() ) { // Only load frontend controller on the frontend
			$this->frontend_controller = new \BricksLiftAB\Frontend\Frontend_Controller();
		}
	}

	/**
	 * Add WordPress hooks.
	 */
	private function add_hooks() {
		// Actions and filters will be added here.
		// For CPT registration, it's typically done on 'init'.
		// The CPT_Manager will handle its own hook registration internally.
		add_action( 'blft_aggregate_daily_stats_hook', [ $this, 'perform_daily_stats_aggregation' ] );
	}

	/**
	 * Plugin activation hook.
	 */
	public static function activate() {
		// Create custom database tables.
		DB_Manager::create_tables();

		// Schedule daily stats aggregation cron job.
		if ( ! wp_next_scheduled( 'blft_aggregate_daily_stats_hook' ) ) {
			// Schedule to run daily, around 2 AM server time.
			// 'gmt_offset' can be used if WP's timezone is set, otherwise it's UTC.
			// For simplicity, using a fixed time. Consider wp_timezone()->getOffset() for more precision.
			$scheduled_time = strtotime( 'tomorrow 2:00am' ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			wp_schedule_event( $scheduled_time, 'daily', 'blft_aggregate_daily_stats_hook' );
		}

		// Activation tasks, e.g., flushing rewrite rules if CPTs are registered.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation hook.
	 */
	public static function deactivate() {
		// Clear scheduled cron job.
		wp_clear_scheduled_hook( 'blft_aggregate_daily_stats_hook' );

		// Deactivation tasks.
		flush_rewrite_rules();
	}

	/**
	 * Perform daily statistics aggregation.
	 * This is the callback for the WP Cron job.
	 */
	public function perform_daily_stats_aggregation() {
		// Ensure DB_Manager is available.
		// It's typically loaded in load_dependencies and might be instantiated if needed,
		// but for a cron context, we might need to ensure it's ready or get a static instance.
		// For now, assuming DB_Manager has static methods or can be instantiated.
		// If DB_Manager methods are not static, we'd need an instance.
		// Let's assume we'll add a static method to DB_Manager or get an instance.
		
		// Log that the cron job has started (optional, good for debugging)
		// error_log('BricksLift A/B Testing: Daily stats aggregation cron job started.');

		$db_manager = new DB_Manager(); // Assuming DB_Manager can be instantiated directly.
		$db_manager->aggregate_daily_tracking_data();

		// Log completion (optional)
		// error_log('BricksLift A/B Testing: Daily stats aggregation cron job finished.');

		$this->check_and_terminate_tests( $db_manager );
	}

	/**
		* Checks active A/B tests and terminates them if conditions are met.
		*
		* @param DB_Manager $db_manager Instance of DB_Manager.
		*/
	public function check_and_terminate_tests( DB_Manager $db_manager ) {
		// error_log('BricksLift A/B Testing: Starting check_and_terminate_tests.');

		$active_tests_query = new \WP_Query(
			[
				'post_type'      => CPT_Manager::CPT_SLUG,
				'posts_per_page' => -1, // Get all active tests.
				'meta_query'     => [
					[
						'key'     => '_blft_status',
						'value'   => 'running',
						'compare' => '=',
					],
				],
				'fields'         => 'ids',      // Only get post IDs for efficiency.
			]
		);

		if ( ! $active_tests_query->have_posts() ) {
			// error_log('BricksLift A/B Testing: No active tests found to check for termination.');
			return;
		}

		$test_ids = $active_tests_query->posts;

		foreach ( $test_ids as $test_id ) {
			$should_terminate = false;
			$test_post        = get_post( $test_id );
			$test_name        = $test_post->post_title;

			// Retrieve lifecycle meta fields
			$duration_type      = get_post_meta( $test_id, '_blft_test_duration_type', true );
			$duration_days      = get_post_meta( $test_id, '_blft_test_duration_days', true );
			$end_date_str       = get_post_meta( $test_id, '_blft_test_end_date', true );
			$auto_end_condition = get_post_meta( $test_id, '_blft_test_auto_end_condition', true );
			$auto_end_value     = get_post_meta( $test_id, '_blft_test_auto_end_value', true );

			// 1. Duration Check
			if ( 'fixed_days' === $duration_type && ! empty( $duration_days ) ) {
				$publish_date_gmt = strtotime( $test_post->post_date_gmt );
				$days_passed      = ( current_time( 'timestamp', true ) - $publish_date_gmt ) / DAY_IN_SECONDS;
				if ( $days_passed >= (int) $duration_days ) {
					$should_terminate = true;
					// error_log("BricksLift A/B Testing: Test ID {$test_id} marked for termination due to fixed_days.");
				}
			} elseif ( 'end_date' === $duration_type && ! empty( $end_date_str ) ) {
				$end_timestamp = strtotime( $end_date_str . ' 23:59:59' ); // Consider end of day
				if ( current_time( 'timestamp', true ) >= $end_timestamp ) {
					$should_terminate = true;
					// error_log("BricksLift A/B Testing: Test ID {$test_id} marked for termination due to end_date.");
				}
			}

			// 2. Auto-End Condition Check (only if not already marked by duration)
			if ( ! $should_terminate && 'none' !== $auto_end_condition && ! empty( $auto_end_value ) ) {
				$variants_json = get_post_meta( $test_id, '_blft_variants', true );
				$variants      = json_decode( $variants_json, true );

				if ( is_array( $variants ) ) {
					foreach ( $variants as $variant ) {
						if ( ! isset( $variant['id'] ) ) {
							continue;
						}
						$variant_id = $variant['id'];

						if ( 'min_conversions' === $auto_end_condition ) {
							$total_conversions = $db_manager->get_total_conversions_for_variant( $test_id, $variant_id );
							if ( $total_conversions >= (int) $auto_end_value ) {
								$should_terminate = true;
								// error_log("BricksLift A/B Testing: Test ID {$test_id}, Variant {$variant_id} marked for termination due to min_conversions ({$total_conversions} >= {$auto_end_value}).");
								break;
							}
						} elseif ( 'min_views' === $auto_end_condition ) {
							$total_views = $db_manager->get_total_views_for_variant( $test_id, $variant_id );
							if ( $total_views >= (int) $auto_end_value ) {
								$should_terminate = true;
								// error_log("BricksLift A/B Testing: Test ID {$test_id}, Variant {$variant_id} marked for termination due to min_views ({$total_views} >= {$auto_end_value}).");
								break;
							}
						}
					}
				}
			}


			if ( $should_terminate ) {
				// Update post status to 'completed'
				// Update the custom status meta field to 'completed'
				update_post_meta( $test_id, '_blft_status', 'completed' );

				// Optionally, if you also want to change the WordPress post status, you can do it here.
				// For example, to move it to a 'completed' CPT status if registered, or 'draft' etc.
				// wp_update_post(
				// 	[
				// 		'ID'          => $test_id,
				// 		'post_status' => 'completed', // This would be a custom registered post status
				// 	]
				// );
				// error_log("BricksLift A/B Testing: Test ID {$test_id} status updated to completed.");

				// Send admin notification
				$admin_email = get_option( 'admin_email' );
				$subject     = sprintf( __( 'A/B Test Completed: %s', 'brickslift-ab-testing' ), $test_name );
				$body        = sprintf(
					__( "The A/B test '%s' (ID: %d) has automatically completed based on its defined end conditions. You can view the results and consider implementing the winning variant.", 'brickslift-ab-testing' ),
					$test_name,
					$test_id
				);
				wp_mail( $admin_email, $subject, $body );
				// error_log("BricksLift A/B Testing: Admin notification sent for test ID {$test_id}.");
			}
		}
		// error_log('BricksLift A/B Testing: Finished check_and_terminate_tests.');
	}
}