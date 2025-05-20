<?php
/**
 * Custom Post Type Manager for BricksLift A/B Testing.
 *
 * @package BricksLiftAB
 */

namespace BricksLiftAB\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CPT_Manager
 *
 * Handles the registration of Custom Post Types.
 */
class CPT_Manager {

	const CPT_SLUG = 'blft_test';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'init', [ $this, 'register_meta_fields' ] );
		add_action( 'init', [ $this, 'register_post_statuses' ] );
	}

	/**
	 * Register custom post types.
	 */
	public function register_post_types() {
		$labels = [
			'name'                  => _x( 'A/B Tests', 'Post Type General Name', 'brickslift-ab-testing' ),
			'singular_name'         => _x( 'A/B Test', 'Post Type Singular Name', 'brickslift-ab-testing' ),
			'menu_name'             => __( 'BricksLift A/B', 'brickslift-ab-testing' ),
			'name_admin_bar'        => __( 'A/B Test', 'brickslift-ab-testing' ),
			'archives'              => __( 'Test Archives', 'brickslift-ab-testing' ),
			'attributes'            => __( 'Test Attributes', 'brickslift-ab-testing' ),
			'parent_item_colon'     => __( 'Parent Test:', 'brickslift-ab-testing' ),
			'all_items'             => __( 'All A/B Tests', 'brickslift-ab-testing' ),
			'add_new_item'          => __( 'Add New A/B Test', 'brickslift-ab-testing' ),
			'add_new'               => __( 'Add New', 'brickslift-ab-testing' ),
			'new_item'              => __( 'New Test', 'brickslift-ab-testing' ),
			'edit_item'             => __( 'Edit Test', 'brickslift-ab-testing' ),
			'update_item'           => __( 'Update Test', 'brickslift-ab-testing' ),
			'view_item'             => __( 'View Test', 'brickslift-ab-testing' ),
			'view_items'            => __( 'View Tests', 'brickslift-ab-testing' ),
			'search_items'          => __( 'Search Test', 'brickslift-ab-testing' ),
			'not_found'             => __( 'Not found', 'brickslift-ab-testing' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'brickslift-ab-testing' ),
			'featured_image'        => __( 'Featured Image', 'brickslift-ab-testing' ),
			'set_featured_image'    => __( 'Set featured image', 'brickslift-ab-testing' ),
			'remove_featured_image' => __( 'Remove featured image', 'brickslift-ab-testing' ),
			'use_featured_image'    => __( 'Use as featured image', 'brickslift-ab-testing' ),
			'insert_into_item'      => __( 'Insert into test', 'brickslift-ab-testing' ),
			'uploaded_to_this_item' => __( 'Uploaded to this test', 'brickslift-ab-testing' ),
			'items_list'            => __( 'Tests list', 'brickslift-ab-testing' ),
			'items_list_navigation' => __( 'Tests list navigation', 'brickslift-ab-testing' ),
			'filter_items_list'     => __( 'Filter tests list', 'brickslift-ab-testing' ),
		];
		$args   = [
			'label'               => __( 'A/B Test', 'brickslift-ab-testing' ),
			'description'         => __( 'BricksLift A/B Test', 'brickslift-ab-testing' ),
			'labels'              => $labels,
			'supports'            => [ 'title' ], // As per documentation.md
			'hierarchical'        => false,
			'public'              => false, // Not publicly queryable on frontend by default
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-chart-line',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'rewrite'             => false, // No frontend rewrite rules needed
			'capability_type'     => 'post', // Consider custom capabilities later
			'show_in_rest'        => true, // For React Admin UI
			'rest_base'           => 'blft-tests',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		];
		register_post_type( self::CPT_SLUG, $args );
	}

	/**
		* Register custom post statuses.
		*/
	public function register_post_statuses() {
		register_post_status(
			'completed',
			[
				'label'                     => _x( 'Completed', 'post status', 'brickslift-ab-testing' ),
				'public'                    => true, // Allows use in query_posts
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', 'brickslift-ab-testing' ),
			]
		);
	}

	/**
		* Register meta fields for the CPT.
		*/
	public function register_meta_fields() {
		register_post_meta(
			self::CPT_SLUG,
			'_blft_status',
			[
				'type'              => 'string',
				'description'       => __( 'Status of the A/B test (draft, running, paused, completed).', 'brickslift-ab-testing' ),
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => 'draft',
				'sanitize_callback' => 'sanitize_text_field',
			]
		);

		register_post_meta(
			self::CPT_SLUG,
			'_blft_description',
			[
				'type'              => 'string',
				'description'       => __( 'Description of the A/B test.', 'brickslift-ab-testing' ),
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_textarea_field',
			]
		);

		register_post_meta(
			self::CPT_SLUG,
			'_blft_variants',
			[
				'type'              => 'string', // Stored as JSON string
				'description'       => __( 'JSON array of test variants: [{"id": "uuid", "name": "Variant Name", "distribution": 50}]', 'brickslift-ab-testing' ),
				'single'            => true,
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id'           => [ 'type' => 'string' ],
								'name'         => [ 'type' => 'string' ],
								'distribution' => [ 'type' => 'integer' ],
							],
						],
					],
				],
			]
		);

		// Goal Type Meta Fields
		register_post_meta(
			self::CPT_SLUG,
			'_blft_goal_type',
			[
				'type'              => 'string',
				'description'       => __( 'Type of conversion goal.', 'brickslift-ab-testing' ),
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'page_visit', // Default goal type
			]
		);

		// --- Meta fields specific to goal types ---
		$goal_specific_meta = [
			'_blft_goal_pv_url'               => [ 'type' => 'string', 'sanitize' => 'esc_url_raw', 'desc' => 'URL for page visit goal.' ],
			'_blft_goal_pv_url_match_type'    => [ 'type' => 'string', 'sanitize' => 'sanitize_key', 'desc' => 'Match type for page visit URL (exact, contains, starts_with, ends_with, regex).' ],
			'_blft_goal_sc_element_selector'  => [ 'type' => 'string', 'sanitize' => 'sanitize_text_field', 'desc' => 'CSS selector for element click goal.' ],
			'_blft_goal_fs_form_selector'     => [ 'type' => 'string', 'sanitize' => 'sanitize_text_field', 'desc' => 'CSS selector for form submission goal.' ],
			'_blft_goal_fs_trigger'           => [ 'type' => 'string', 'sanitize' => 'sanitize_key', 'desc' => 'Trigger for form submission (submit_event, success_class, thank_you_url).' ],
			'_blft_goal_fs_thank_you_url'     => [ 'type' => 'string', 'sanitize' => 'esc_url_raw', 'desc' => 'Thank you URL for form submission goal.' ],
			'_blft_goal_fs_success_class'     => [ 'type' => 'string', 'sanitize' => 'sanitize_text_field', 'desc' => 'Success class for form submission goal.' ],
			'_blft_goal_wc_any_product'       => [ 'type' => 'boolean', 'sanitize' => 'rest_sanitize_boolean', 'desc' => 'WC add to cart any product.', 'default' => false ],
			'_blft_goal_wc_product_id'        => [ 'type' => 'integer', 'sanitize' => 'absint', 'desc' => 'WC product ID for add to cart goal.', 'default' => 0 ],
			'_blft_goal_sd_percentage'        => [ 'type' => 'integer', 'sanitize' => 'absint', 'desc' => 'Scroll depth percentage.', 'default' => 0 ],
			'_blft_goal_top_seconds'          => [ 'type' => 'integer', 'sanitize' => 'absint', 'desc' => 'Time on page in seconds.', 'default' => 0 ],
			'_blft_goal_cje_event_name'       => [ 'type' => 'string', 'sanitize' => 'sanitize_text_field', 'desc' => 'Custom JavaScript event name.' ],
		];

		foreach ( $goal_specific_meta as $meta_key => $args ) {
			register_post_meta(
				self::CPT_SLUG,
				$meta_key,
				[
					'type'              => $args['type'],
					'description'       => __( $args['desc'], 'brickslift-ab-testing' ),
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => $args['sanitize'],
				]
			);
		}

		// Test Lifecycle Meta Fields
		$lifecycle_meta = [
			'_blft_test_duration_type' => [
				'type'    => 'string',
				'sanitize' => 'sanitize_key',
				'desc'    => 'Type of test duration (none, fixed_days, end_date).',
				'default' => 'none',
			],
			'_blft_test_duration_days' => [
				'type'    => 'integer',
				'sanitize' => 'absint',
				'desc'    => 'Number of days for fixed duration tests.',
				'default' => 7,
			],
			'_blft_test_end_date'      => [
				'type'    => 'string',
				'sanitize' => 'sanitize_text_field', // Store as YYYY-MM-DD
				'desc'    => 'Specific end date for the test.',
			],
			'_blft_test_auto_end_condition' => [
				'type'    => 'string',
				'sanitize' => 'sanitize_key',
				'desc'    => 'Condition for automatic test ending (none, min_views, min_conversions).',
				'default' => 'none',
			],
			'_blft_test_auto_end_value' => [
				'type'    => 'integer',
				'sanitize' => 'absint',
				'desc'    => 'Value for the auto-end condition (e.g., number of views/conversions).',
				'default' => 1000,
			],
		];

		foreach ( $lifecycle_meta as $meta_key => $args ) {
			register_post_meta(
				self::CPT_SLUG,
				$meta_key,
				[
					'type'              => $args['type'],
					'description'       => __( $args['desc'], 'brickslift-ab-testing' ),
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => $args['sanitize'],
					'default'           => isset( $args['default'] ) ? $args['default'] : null,
				]
			);
		}


		// GDPR and Global Tracking Meta Fields
		register_post_meta(
			self::CPT_SLUG,
			'_blft_run_tracking_globally',
			[
				'type'              => 'boolean',
				'description'       => __( 'Whether to run tracking on all pages or only where the test element is present.', 'brickslift-ab-testing' ),
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			]
		);
		register_post_meta(
			self::CPT_SLUG,
			'_blft_gdpr_consent_required',
			[
				'type'              => 'boolean',
				'description'       => __( 'Is GDPR consent required for this test?', 'brickslift-ab-testing' ),
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			]
		);
		register_post_meta(
			self::CPT_SLUG,
			'_blft_gdpr_consent_mechanism',
			[
				'type'              => 'string',
				'description'       => __( 'Mechanism for checking GDPR consent (e.g., none, cookie_key).', 'brickslift-ab-testing' ),
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_key',
				'default'           => 'none',
			]
		);
		register_post_meta(
			self::CPT_SLUG,
			'_blft_gdpr_consent_key_name',
			[
				'type'              => 'string',
				'description'       => __( 'Name of the cookie key for GDPR consent.', 'brickslift-ab-testing' ),
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
			]
		);
		register_post_meta(
			self::CPT_SLUG,
			'_blft_gdpr_consent_key_value',
			[
				'type'              => 'string',
				'description'       => __( 'Expected value of the cookie key for GDPR consent.', 'brickslift-ab-testing' ),
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
			]
		);
	}
}