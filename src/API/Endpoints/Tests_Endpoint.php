<?php
/**
 * Tests REST API Endpoint for BricksLift A/B Testing.
 *
 * @package BricksLiftAB
 */

namespace BricksLiftAB\API\Endpoints;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Query;
use BricksLiftAB\Core\CPT_Manager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tests_Endpoint
 *
 * Manages REST API endpoints for A/B tests.
 */
class Tests_Endpoint extends WP_REST_Controller {

	/**
	 * Namespace for the REST API.
	 * @var string
	 */
	protected $namespace = 'blft/v1';

	/**
	 * Route base for tests.
	 * @var string
	 */
	protected $rest_base = 'tests';

	/**
	 * Register the routes for the objects of the controller.
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
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				'args'   => [
					'id' => [
						'description' => __( 'Unique identifier for the test.', 'brickslift-ab-testing' ),
						'type'        => 'integer',
						'required'    => true,
					],
				],
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
					'args'                => [
						'context' => $this->get_context_param( [ 'default' => 'view' ] ),
					],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/status',
			[
				'args'   => [
					'id' => [
						'description' => __( 'Unique identifier for the test.', 'brickslift-ab-testing' ),
						'type'        => 'integer',
						'required'    => true,
					],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE, // Using CREATABLE for POST
					'callback'            => [ $this, 'manage_test_status' ],
					'permission_callback' => [ $this, 'manage_test_status_permissions_check' ],
					'args'                => [
						'action' => [
							'description' => __( 'Action to perform on the test status.', 'brickslift-ab-testing' ),
							'type'        => 'string',
							'required'    => true,
							'enum'        => [ 'start', 'pause', 'stop', 'archive', 'declare_winner' ],
						],
						'winner_variant_id' => [
							'description' => __( 'ID of the winning variant (required if action is declare_winner).', 'brickslift-ab-testing' ),
							'type'        => 'string',
							'required'    => false, // Will be validated in callback
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/duplicate',
			[
				'args'   => [
					'id' => [
						'description' => __( 'Unique identifier for the test to duplicate.', 'brickslift-ab-testing' ),
						'type'        => 'integer',
						'required'    => true,
					],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE, // Using CREATABLE for POST
					'callback'            => [ $this, 'duplicate_test' ],
					'permission_callback' => [ $this, 'duplicate_test_permissions_check' ],
				],
			]
		);
	}

	public function get_items_permissions_check( $request ) {
		$post_type_obj = get_post_type_object( CPT_Manager::CPT_SLUG );
		if ( ! current_user_can( $post_type_obj->cap->edit_posts ) ) {
			return new \WP_Error( 'rest_forbidden', esc_html__( 'Sorry, you are not allowed to access these items.', 'brickslift-ab-testing' ), [ 'status' => rest_authorization_required_code() ] );
		}
		return true;
	}

	public function get_item_permissions_check( $request ) {
		$post_id = (int) $request['id'];
		$post    = get_post( $post_id );
		if ( ! $post || CPT_Manager::CPT_SLUG !== $post->post_type ) {
			return new \WP_Error( 'rest_post_invalid_id', __( 'Invalid post ID.', 'brickslift-ab-testing' ), [ 'status' => 404 ] );
		}
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return new \WP_Error( 'rest_forbidden', esc_html__( 'Sorry, you are not allowed to view this item.', 'brickslift-ab-testing' ), [ 'status' => rest_authorization_required_code() ] );
		}
		return true;
	}

	public function create_item_permissions_check( $request ) {
		$post_type_obj = get_post_type_object( CPT_Manager::CPT_SLUG );
		if ( ! current_user_can( $post_type_obj->cap->create_posts ) ) {
			return new \WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to create items in this post type.', 'brickslift-ab-testing' ), [ 'status' => rest_authorization_required_code() ] );
		}
		return true;
	}

	public function update_item_permissions_check( $request ) {
		$post_id = (int) $request['id'];
		$post    = get_post( $post_id );
		if ( ! $post || CPT_Manager::CPT_SLUG !== $post->post_type ) {
			return new \WP_Error( 'rest_post_invalid_id', __( 'Invalid post ID.', 'brickslift-ab-testing' ), [ 'status' => 400 ] );
		}
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return new \WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to update this post.', 'brickslift-ab-testing' ), [ 'status' => rest_authorization_required_code() ] );
		}
		return true;
	}

	public function get_items( $request ) {
		$args = [
			'post_type'      => CPT_Manager::CPT_SLUG,
			'posts_per_page' => $request['per_page'],
			'paged'          => $request['page'],
			'post_status'    => 'any', // We fetch all WP statuses and filter by our custom status meta if needed
		];

		// Handle status filtering
		if ( ! empty( $request['status'] ) ) {
			$args['meta_query'][] = [
				'key'   => '_blft_status',
				'value' => sanitize_text_field( $request['status'] ),
			];
		}

		// Handle sorting
		$orderby = $request->get_param( 'orderby' );
		$order   = $request->get_param( 'order' );

		if ( ! empty( $orderby ) ) {
			switch ( $orderby ) {
				case 'title':
					$args['orderby'] = 'title';
					break;
				case 'start_date':
					$args['meta_key'] = '_blft_start_date';
					$args['orderby']  = 'meta_value';
					break;
					// Add more cases if other orderby fields are supported
			}
			if ( ! empty( $order ) && in_array( strtolower( $order ), [ 'asc', 'desc' ], true ) ) {
				$args['order'] = strtoupper( $order );
			}
		}

		$query = new WP_Query( $args );
		$posts = $query->get_posts();
		$data  = [];
		if ( empty( $posts ) ) {
			return new \WP_REST_Response( [], 200 );
		}
		foreach ( $posts as $post ) {
			$response = $this->prepare_item_for_response( $post, $request );
			$data[]   = $this->prepare_response_for_collection( $response );
		}
		$total_posts = $query->found_posts;
		$max_pages   = $query->max_num_pages;
		$response = new \WP_REST_Response( $data, 200 );
		$response->header( 'X-WP-Total', (int) $total_posts );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );
		return $response;
	}

	public function prepare_item_for_response( $item, $request ) {
		$data   = [];
		$schema = $this->get_item_schema();
		if ( ! $item instanceof \WP_Post ) {
			return new \WP_Error( 'rest_item_invalid', __( 'Invalid item for response.', 'brickslift-ab-testing' ) );
		}

		if ( $this->is_field_present_in_schema( 'id', $schema ) ) $data['id'] = $item->ID;
		if ( $this->is_field_present_in_schema( 'title', $schema ) ) $data['title'] = [ 'raw' => $item->post_title, 'rendered' => get_the_title( $item->ID ) ];
		// The main 'status' for the API response should be our custom A/B test status.
		if ( $this->is_field_present_in_schema( 'status', $schema ) ) $data['status'] = get_post_meta( $item->ID, '_blft_status', true ) ?: 'draft';
		if ( $this->is_field_present_in_schema( 'wordpress_status', $schema ) ) $data['wordpress_status'] = $item->post_status; // Keep original WP status if needed under a different key

		if ( $this->is_field_present_in_schema( 'start_date', $schema ) ) $data['start_date'] = get_post_meta( $item->ID, '_blft_start_date', true ) ?: '';
		if ( $this->is_field_present_in_schema( 'end_date', $schema ) ) $data['end_date'] = get_post_meta( $item->ID, '_blft_test_end_date', true ) ?: '';
		if ( $this->is_field_present_in_schema( 'hypothesis', $schema ) ) $data['hypothesis'] = get_post_meta( $item->ID, '_blft_hypothesis', true ) ?: '';
		if ( $this->is_field_present_in_schema( 'winner_variant_id', $schema ) ) $data['winner_variant_id'] = get_post_meta( $item->ID, '_blft_winner_variant_id', true ) ?: '';

		$variants_raw = get_post_meta( $item->ID, '_blft_variants', true );
		$variants_array = json_decode( $variants_raw, true );
		if ( $this->is_field_present_in_schema( 'variations_count', $schema ) ) $data['variations_count'] = is_array( $variants_array ) ? count( $variants_array ) : 0;
		
		// Note: 'variants' field itself is handled by get_variants_for_response if needed by schema, but count is separate.
		// For consistency, ensure 'variants' data is also populated if schema requests it.
		if ( $this->is_field_present_in_schema( 'variants', $schema ) ) $data['variants'] = $this->get_variants_for_response( $item->ID );


		if ( $this->is_field_present_in_schema( 'date_created', $schema ) ) $data['date_created'] = rest_get_date_with_gmt( $item->post_date_gmt, true );
		if ( $this->is_field_present_in_schema( 'modified', $schema ) ) $data['modified'] = rest_get_date_with_gmt( $item->post_modified_gmt, true );
		// if ( $this->is_field_present_in_schema( 'blft_status', $schema ) ) $data['blft_status'] = get_post_meta( $item->ID, '_blft_status', true ) ?: 'draft'; // This is now 'status'
		if ( $this->is_field_present_in_schema( 'description', $schema ) ) $data['description'] = get_post_meta( $item->ID, '_blft_description', true ) ?: '';
		if ( $this->is_field_present_in_schema( 'goal_type', $schema ) ) $data['goal_type'] = get_post_meta( $item->ID, '_blft_goal_type', true ) ?: 'page_visit';

		$goal_specific_fields_schema = $this->get_goal_specific_schema_fields();
		foreach ( $goal_specific_fields_schema as $key => $field_args ) {
			if ( $this->is_field_present_in_schema( $key, $schema ) ) {
				$meta_value = get_post_meta( $item->ID, '_blft_' . $key, true );
				if ( $field_args['type'] === 'boolean' ) $data[ $key ] = rest_sanitize_boolean( $meta_value );
				elseif ( $field_args['type'] === 'integer' ) $data[ $key ] = $meta_value !== '' ? (int) $meta_value : null;
				else $data[ $key ] = $meta_value;
			}
		}

		if ( $this->is_field_present_in_schema( 'run_tracking_globally', $schema ) ) $data['run_tracking_globally'] = rest_sanitize_boolean(get_post_meta( $item->ID, '_blft_run_tracking_globally', true ));
		if ( $this->is_field_present_in_schema( 'gdpr_consent_required', $schema ) ) $data['gdpr_consent_required'] = rest_sanitize_boolean(get_post_meta( $item->ID, '_blft_gdpr_consent_required', true ));
		if ( $this->is_field_present_in_schema( 'gdpr_consent_mechanism', $schema ) ) $data['gdpr_consent_mechanism'] = get_post_meta( $item->ID, '_blft_gdpr_consent_mechanism', true ) ?: 'none';
		if ( $this->is_field_present_in_schema( 'gdpr_consent_key_name', $schema ) ) $data['gdpr_consent_key_name'] = get_post_meta( $item->ID, '_blft_gdpr_consent_key_name', true ) ?: '';
		if ( $this->is_field_present_in_schema( 'gdpr_consent_key_value', $schema ) ) $data['gdpr_consent_key_value'] = get_post_meta( $item->ID, '_blft_gdpr_consent_key_value', true ) ?: '';

		// Lifecycle fields
		if ( $this->is_field_present_in_schema( 'test_duration_type', $schema ) ) $data['test_duration_type'] = get_post_meta( $item->ID, '_blft_test_duration_type', true ) ?: 'none';
		if ( $this->is_field_present_in_schema( 'test_duration_days', $schema ) ) {
			$duration_days = get_post_meta( $item->ID, '_blft_test_duration_days', true );
			$data['test_duration_days'] = $duration_days !== '' ? (int) $duration_days : 7; // Default 7 if not set or empty
		}
		if ( $this->is_field_present_in_schema( 'test_auto_end_condition', $schema ) ) $data['test_auto_end_condition'] = get_post_meta( $item->ID, '_blft_test_auto_end_condition', true ) ?: 'none';
		if ( $this->is_field_present_in_schema( 'test_auto_end_value', $schema ) ) {
			$auto_end_value = get_post_meta( $item->ID, '_blft_test_auto_end_value', true );
			$data['test_auto_end_value'] = $auto_end_value !== '' ? (int) $auto_end_value : 1000; // Default 1000 if not set or empty
		}


		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );
		$response = new \WP_REST_Response( $data );
		$response->add_links( $this->prepare_links( $item ) );
		return $response;
	}

	protected function is_field_present_in_schema( $field_name, $schema ) {
		return isset( $schema['properties'][ $field_name ] );
	}

	public function get_item( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );
		if ( ! $post || CPT_Manager::CPT_SLUG !== $post->post_type ) {
			return new \WP_Error( 'rest_post_invalid_id', __( 'Invalid post ID.', 'brickslift-ab-testing' ), [ 'status' => 404 ] );
		}
		$response = $this->prepare_item_for_response( $post, $request );
		return rest_ensure_response( $response );
	}

	public function create_item( $request ) {
		$params = $request->get_params();
		$post_args = [
			'post_type'   => CPT_Manager::CPT_SLUG,
			'post_status' => 'draft', // New tests are WP drafts by default
		];
		if ( isset( $params['title'] ) ) {
			if ( is_string( $params['title'] ) ) $post_args['post_title'] = sanitize_text_field( $params['title'] );
			elseif ( is_array( $params['title'] ) && isset( $params['title']['raw'] ) ) $post_args['post_title'] = sanitize_text_field( $params['title']['raw'] );
		}
		if ( empty( $post_args['post_title'] ) ) $post_args['post_title'] = __( 'New A/B Test', 'brickslift-ab-testing' );
		$post_id = wp_insert_post( wp_slash( $post_args ), true );
		if ( is_wp_error( $post_id ) ) return $post_id;

		update_post_meta( $post_id, '_blft_status', isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : 'draft' ); // Changed from blft_status to status
		if ( isset( $params['description'] ) ) update_post_meta( $post_id, '_blft_description', sanitize_textarea_field( $params['description'] ) );
		
		// Update new fields
		if ( isset( $params['start_date'] ) ) update_post_meta( $post_id, '_blft_start_date', sanitize_text_field( $params['start_date'] ) );
		if ( isset( $params['hypothesis'] ) ) update_post_meta( $post_id, '_blft_hypothesis', sanitize_textarea_field( $params['hypothesis'] ) );
		if ( isset( $params['winner_variant_id'] ) ) update_post_meta( $post_id, '_blft_winner_variant_id', sanitize_text_field( $params['winner_variant_id'] ) );
		// end_date is _blft_test_end_date, which might be handled by lifecycle meta if it's part of a different schema group.
		// For now, assuming it's directly updatable if sent.
		if ( isset( $params['end_date'] ) ) update_post_meta( $post_id, '_blft_test_end_date', sanitize_text_field( $params['end_date'] ) );


		$this->update_variants_meta( $post_id, isset( $params['variants'] ) && is_array( $params['variants'] ) ? $params['variants'] : [] );
		update_post_meta( $post_id, '_blft_goal_type', isset( $params['goal_type'] ) ? sanitize_text_field( $params['goal_type'] ) : 'page_visit' );
		$goal_specific_fields_schema = $this->get_goal_specific_schema_fields();
		foreach ( $goal_specific_fields_schema as $key => $field_args ) {
			if ( isset( $params[ $key ] ) ) $this->update_single_goal_meta( $post_id, '_blft_' . $key, $params[ $key ], $field_args );
		}
		if ( isset( $params['run_tracking_globally'] ) ) update_post_meta( $post_id, '_blft_run_tracking_globally', rest_sanitize_boolean( $params['run_tracking_globally'] ) );
		if ( isset( $params['gdpr_consent_required'] ) ) update_post_meta( $post_id, '_blft_gdpr_consent_required', rest_sanitize_boolean( $params['gdpr_consent_required'] ) );
		if ( isset( $params['gdpr_consent_mechanism'] ) ) update_post_meta( $post_id, '_blft_gdpr_consent_mechanism', sanitize_key( $params['gdpr_consent_mechanism'] ) );
		if ( isset( $params['gdpr_consent_key_name'] ) ) update_post_meta( $post_id, '_blft_gdpr_consent_key_name', sanitize_text_field( $params['gdpr_consent_key_name'] ) );
		if ( isset( $params['gdpr_consent_key_value'] ) ) update_post_meta( $post_id, '_blft_gdpr_consent_key_value', sanitize_text_field( $params['gdpr_consent_key_value'] ) );

		// Lifecycle fields
		if ( isset( $params['test_duration_type'] ) ) update_post_meta( $post_id, '_blft_test_duration_type', sanitize_key( $params['test_duration_type'] ) );
		if ( isset( $params['test_duration_days'] ) ) update_post_meta( $post_id, '_blft_test_duration_days', absint( $params['test_duration_days'] ) );
		if ( isset( $params['test_auto_end_condition'] ) ) update_post_meta( $post_id, '_blft_test_auto_end_condition', sanitize_key( $params['test_auto_end_condition'] ) );
		if ( isset( $params['test_auto_end_value'] ) ) update_post_meta( $post_id, '_blft_test_auto_end_value', absint( $params['test_auto_end_value'] ) );

		$post = get_post( $post_id );
		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $post_id ) ) );
		return $response;
	}

	public function update_item( $request ) {
		$id     = (int) $request['id'];
		$params = $request->get_params();
		$post   = get_post( $id );
		if ( ! $post || CPT_Manager::CPT_SLUG !== $post->post_type ) {
			return new \WP_Error( 'rest_post_invalid_id', __( 'Invalid post ID.', 'brickslift-ab-testing' ), [ 'status' => 400 ] );
		}
		$post_args = [ 'ID' => $id ];
		if ( isset( $params['title'] ) ) {
			if ( is_string( $params['title'] ) ) $post_args['post_title'] = sanitize_text_field( $params['title'] );
			elseif ( is_array( $params['title'] ) && isset( $params['title']['raw'] ) ) $post_args['post_title'] = sanitize_text_field( $params['title']['raw'] );
		}
		// Do not update post_status based on custom 'status' field. WordPress status should be managed separately if needed.
		// if ( isset( $params['wordpress_status'] ) ) $post_args['post_status'] = sanitize_key( $params['wordpress_status'] );
		if ( count( $post_args ) > 1 ) {
			$updated = wp_update_post( wp_slash( $post_args ), true );
			if ( is_wp_error( $updated ) ) return $updated;
		}

		if ( array_key_exists( 'status', $params ) ) update_post_meta( $id, '_blft_status', sanitize_text_field( $params['status'] ) );
		if ( array_key_exists( 'description', $params ) ) update_post_meta( $id, '_blft_description', sanitize_textarea_field( $params['description'] ) );

		// Update new fields
		if ( array_key_exists( 'start_date', $params ) ) update_post_meta( $id, '_blft_start_date', sanitize_text_field( $params['start_date'] ) );
		if ( array_key_exists( 'end_date', $params ) ) update_post_meta( $id, '_blft_test_end_date', sanitize_text_field( $params['end_date'] ) );
		if ( array_key_exists( 'hypothesis', $params ) ) update_post_meta( $id, '_blft_hypothesis', sanitize_textarea_field( $params['hypothesis'] ) );
		if ( array_key_exists( 'winner_variant_id', $params ) ) update_post_meta( $id, '_blft_winner_variant_id', sanitize_text_field( $params['winner_variant_id'] ) );

		if ( isset( $params['variants'] ) && is_array( $params['variants'] ) ) {
			$this->update_variants_meta( $id, $params['variants'] );
		}
		if ( array_key_exists( 'goal_type', $params ) ) update_post_meta( $id, '_blft_goal_type', sanitize_text_field( $params['goal_type'] ) );
		
		$goal_specific_fields_schema = $this->get_goal_specific_schema_fields();
		foreach ( $goal_specific_fields_schema as $key => $field_args ) {
			if ( array_key_exists( $key, $params ) ) {
				$this->update_single_goal_meta( $id, '_blft_' . $key, $params[ $key ], $field_args );
			}
		}
		if ( array_key_exists( 'run_tracking_globally', $params ) ) update_post_meta( $id, '_blft_run_tracking_globally', rest_sanitize_boolean( $params['run_tracking_globally'] ) );
		if ( array_key_exists( 'gdpr_consent_required', $params ) ) update_post_meta( $id, '_blft_gdpr_consent_required', rest_sanitize_boolean( $params['gdpr_consent_required'] ) );
		if ( array_key_exists( 'gdpr_consent_mechanism', $params ) ) update_post_meta( $id, '_blft_gdpr_consent_mechanism', sanitize_key( $params['gdpr_consent_mechanism'] ) );
		if ( array_key_exists( 'gdpr_consent_key_name', $params ) ) update_post_meta( $id, '_blft_gdpr_consent_key_name', sanitize_text_field( $params['gdpr_consent_key_name'] ) );
		if ( array_key_exists( 'gdpr_consent_key_value', $params ) ) update_post_meta( $id, '_blft_gdpr_consent_key_value', sanitize_text_field( $params['gdpr_consent_key_value'] ) );

		// Lifecycle fields
		if ( array_key_exists( 'test_duration_type', $params ) ) update_post_meta( $id, '_blft_test_duration_type', sanitize_key( $params['test_duration_type'] ) );
		if ( array_key_exists( 'test_duration_days', $params ) ) update_post_meta( $id, '_blft_test_duration_days', absint( $params['test_duration_days'] ) );
		if ( array_key_exists( 'test_auto_end_condition', $params ) ) update_post_meta( $id, '_blft_test_auto_end_condition', sanitize_key( $params['test_auto_end_condition'] ) );
		if ( array_key_exists( 'test_auto_end_value', $params ) ) update_post_meta( $id, '_blft_test_auto_end_value', absint( $params['test_auto_end_value'] ) );
		
		$post = get_post( $id );
		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $post, $request );
		return rest_ensure_response( $response );
	}

	protected function update_single_goal_meta( $post_id, $meta_key, $value, $field_args ) {
		$sanitized_value = null;
		if ( $field_args['type'] === 'boolean' ) $sanitized_value = rest_sanitize_boolean( $value );
		elseif ( $field_args['type'] === 'integer' ) $sanitized_value = ( $value === '' || is_null( $value ) ) ? null : absint( $value );
		elseif ( isset($field_args['format']) && $field_args['format'] === 'url' ) $sanitized_value = esc_url_raw( $value );
		else $sanitized_value = sanitize_text_field( $value );
		if ( is_null( $sanitized_value ) && $field_args['type'] !== 'boolean' ) delete_post_meta( $post_id, $meta_key );
		else update_post_meta( $post_id, $meta_key, $sanitized_value );
	}

	protected function update_variants_meta( $post_id, $variants ) {
		$sanitized_variants = [];
		if ( is_array( $variants ) ) {
			foreach ( $variants as $variant ) {
				$sanitized_variant = [];
				$sanitized_variant['id'] = isset( $variant['id'] ) && !empty(trim($variant['id'])) ? sanitize_text_field( $variant['id'] ) : wp_generate_uuid4();
				$sanitized_variant['name'] = isset( $variant['name'] ) ? sanitize_text_field( $variant['name'] ) : __( 'Variant', 'brickslift-ab-testing' );
				$sanitized_variant['distribution'] = isset( $variant['distribution'] ) ? absint( $variant['distribution'] ) : 0;
				$sanitized_variants[] = $sanitized_variant;
			}
		}
		if (empty($sanitized_variants)) {
			$sanitized_variants[] = ['id' => wp_generate_uuid4(), 'name' => __('Variant A', 'brickslift-ab-testing'), 'distribution' => 100];
		}
		update_post_meta( $post_id, '_blft_variants', wp_json_encode( $sanitized_variants ) );
	}

	protected function get_variants_for_response( $post_id ) {
		$variants_json = get_post_meta( $post_id, '_blft_variants', true );
		if ( empty( $variants_json ) ) return [];
		$variants = json_decode( $variants_json, true );
		return is_array( $variants ) ? $variants : [];
	}

	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['status'] = [
			'description'       => __( 'Filter by test status.', 'brickslift-ab-testing' ),
			'type'              => 'string',
			'enum'              => [ 'draft', 'running', 'paused', 'completed', 'archived' ], // Added 'archived'
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		];

		$params['orderby'] = [
			'description'       => __( 'Sort collection by object attribute.', 'brickslift-ab-testing' ),
			'type'              => 'string',
			'default'           => 'date',
			'enum'              => [
				'date', // Default WP orderby
				'id',
				'include',
				'title',
				'slug',
				'modified',
				'start_date', // Custom
			],
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		];

		$params['order'] = [
			'description'       => __( 'Order sort attribute ascending or descending.', 'brickslift-ab-testing' ),
			'type'              => 'string',
			'default'           => 'desc',
			'enum'              => [ 'asc', 'desc' ],
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		];

		return $params;
	}

	protected function prepare_links( $post ) {
		$links = [
			'self' => [ 'href' => rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $post->ID ) ) ],
			'collection' => [ 'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) ],
		];
		return $links;
	}

	protected function get_goal_specific_schema_fields() {
		return [
			'goal_pv_url'            => [ 'type' => 'string', 'format' => 'url', 'description' => __( 'URL for page visit goal.', 'brickslift-ab-testing' ) ],
			'goal_pv_url_match_type' => [ 'type' => 'string', 'enum' => ['exact', 'contains', 'starts_with', 'ends_with', 'regex'], 'default' => 'exact', 'description' => __( 'Match type for page visit URL.', 'brickslift-ab-testing' ) ],
			'goal_sc_element_selector' => [ 'type' => 'string', 'description' => __( 'CSS selector for element click goal.', 'brickslift-ab-testing' ) ],
			'goal_fs_form_selector'  => [ 'type' => 'string', 'description' => __( 'CSS selector for form submission goal.', 'brickslift-ab-testing' ) ],
			'goal_fs_trigger'        => [ 'type' => 'string', 'enum' => ['submit_event', 'success_class', 'thank_you_url'], 'default' => 'submit_event', 'description' => __( 'Trigger for form submission.', 'brickslift-ab-testing' ) ],
			'goal_fs_thank_you_url'  => [ 'type' => 'string', 'format' => 'url', 'description' => __( 'Thank you URL for form submission goal.', 'brickslift-ab-testing' ) ],
			'goal_fs_success_class'  => [ 'type' => 'string', 'description' => __( 'Success class for form submission goal.', 'brickslift-ab-testing' ) ],
			'goal_wc_any_product'    => [ 'type' => 'boolean', 'default' => false, 'description' => __( 'Track add to cart for any WooCommerce product.', 'brickslift-ab-testing' ) ],
			'goal_wc_product_id'     => [ 'type' => 'integer', 'description' => __( 'Specific WooCommerce product ID for add to cart goal.', 'brickslift-ab-testing' ) ],
			'goal_sd_percentage'     => [ 'type' => 'integer', 'minimum' => 0, 'maximum' => 100, 'description' => __( 'Scroll depth percentage.', 'brickslift-ab-testing' ) ],
			'goal_top_seconds'       => [ 'type' => 'integer', 'minimum' => 0, 'description' => __( 'Time on page in seconds.', 'brickslift-ab-testing' ) ],
			'goal_cje_event_name'    => [ 'type' => 'string', 'description' => __( 'Custom JavaScript event name.', 'brickslift-ab-testing' ) ],
		];
	}

	/**
	 * Manages the status of a test (start, pause, stop, archive, declare_winner).
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function manage_test_status( $request ) {
		$test_id = (int) $request['id'];
		$action  = sanitize_text_field( $request['action'] );

		$post = get_post( $test_id );
		if ( ! $post || CPT_Manager::CPT_SLUG !== $post->post_type ) {
			return new \WP_Error( 'rest_post_invalid_id', __( 'Invalid test ID.', 'brickslift-ab-testing' ), [ 'status' => 404 ] );
		}

		$new_status = '';
		$message    = '';

		switch ( $action ) {
			case 'start':
				$new_status = 'running';
				$message    = __( 'Test started successfully.', 'brickslift-ab-testing' );
				break;
			case 'pause':
				$new_status = 'paused';
				$message    = __( 'Test paused successfully.', 'brickslift-ab-testing' );
				break;
			case 'stop':
				$new_status = 'completed'; // Or 'stopped' if that's preferred and registered
				$message    = __( 'Test stopped successfully.', 'brickslift-ab-testing' );
				break;
			case 'archive':
				$new_status = 'archived';
				$message    = __( 'Test archived successfully.', 'brickslift-ab-testing' );
				// Potentially update WordPress post status to 'archive' if it's a registered WP status.
				// For now, we only update the meta field.
				// If 'archived' is a custom WP post status, ensure it's registered in CPT_Manager.
				break;
			case 'declare_winner':
				$winner_variant_id = sanitize_text_field( $request['winner_variant_id'] );
				if ( empty( $winner_variant_id ) ) {
					return new \WP_Error( 'rest_missing_winner_variant_id', __( 'Winner variant ID is required to declare a winner.', 'brickslift-ab-testing' ), [ 'status' => 400 ] );
				}
				// TODO: Validate if winner_variant_id actually exists in the test's variants.
				update_post_meta( $test_id, '_blft_winner_variant_id', $winner_variant_id );
				$new_status = 'completed';
				$message    = __( 'Winner declared successfully.', 'brickslift-ab-testing' );
				break;
			default:
				return new \WP_Error( 'rest_invalid_action', __( 'Invalid action specified.', 'brickslift-ab-testing' ), [ 'status' => 400 ] );
		}

		if ( ! empty( $new_status ) ) {
			update_post_meta( $test_id, '_blft_status', $new_status );
		}

		// If 'archived' status should also change the WP post status
		if ($action === 'archive') {
			// Check if 'archived' is a registered post status
			$post_statuses = get_post_stati();
			if (isset($post_statuses['archived'])) {
				wp_update_post(['ID' => $test_id, 'post_status' => 'archived']);
			}
			// Otherwise, it's just a meta status.
		}


		$response_data = [
			'message'     => $message,
			'status'      => $new_status ?: get_post_meta( $test_id, '_blft_status', true ),
			'test_id'     => $test_id,
		];
		if ($action === 'declare_winner') {
			$response_data['winner_variant_id'] = $winner_variant_id;
		}

		return new \WP_REST_Response( $response_data, 200 );
	}

	/**
	 * Permission check for managing test status.
	 */
	public function manage_test_status_permissions_check( $request ) {
		$post_id = (int) $request['id'];
		$post    = get_post( $post_id );
		if ( ! $post || CPT_Manager::CPT_SLUG !== $post->post_type ) {
			return new \WP_Error( 'rest_post_invalid_id', __( 'Invalid test ID.', 'brickslift-ab-testing' ), [ 'status' => 404 ] );
		}
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return new \WP_Error( 'rest_forbidden', esc_html__( 'Sorry, you are not allowed to manage the status of this test.', 'brickslift-ab-testing' ), [ 'status' => rest_authorization_required_code() ] );
		}
		return true;
	}

	/**
	 * Duplicates a test.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function duplicate_test( $request ) {
		$original_test_id = (int) $request['id'];
		$original_post    = get_post( $original_test_id );

		if ( ! $original_post || CPT_Manager::CPT_SLUG !== $original_post->post_type ) {
			return new \WP_Error( 'rest_post_invalid_id', __( 'Invalid original test ID.', 'brickslift-ab-testing' ), [ 'status' => 404 ] );
		}

		$new_post_args = [
			'post_title'   => $original_post->post_title . ' - ' . __( 'Copy', 'brickslift-ab-testing' ),
			'post_content' => $original_post->post_content,
			'post_status'  => 'draft', // New duplicated tests are WP drafts
			'post_type'    => CPT_Manager::CPT_SLUG,
		];

		$new_test_id = wp_insert_post( wp_slash( $new_post_args ), true );

		if ( is_wp_error( $new_test_id ) ) {
			return $new_test_id;
		}

		// Copy meta fields
		$meta_keys_to_copy = [
			'_blft_description',
			'_blft_hypothesis',
			'_blft_variants', // Variants will be copied as is, including their IDs. New unique IDs for variants might be desired.
			'_blft_goal_type',
			'_blft_goal_pv_url',
			'_blft_goal_pv_url_match_type',
			'_blft_goal_sc_element_selector',
			'_blft_goal_fs_form_selector',
			'_blft_goal_fs_trigger',
			'_blft_goal_fs_thank_you_url',
			'_blft_goal_fs_success_class',
			'_blft_goal_wc_any_product',
			'_blft_goal_wc_product_id',
			'_blft_goal_sd_percentage',
			'_blft_goal_top_seconds',
			'_blft_goal_cje_event_name',
			'_blft_run_tracking_globally',
			'_blft_gdpr_consent_required',
			'_blft_gdpr_consent_mechanism',
			'_blft_gdpr_consent_key_name',
			'_blft_gdpr_consent_key_value',
			'_blft_test_duration_type',
			'_blft_test_duration_days',
			'_blft_test_auto_end_condition',
			'_blft_test_auto_end_value',
			// Do NOT copy: _blft_status (set below), _blft_start_date, _blft_test_end_date, _blft_winner_variant_id
		];

		foreach ( $meta_keys_to_copy as $meta_key ) {
			$meta_value = get_post_meta( $original_test_id, $meta_key, true );
			if ( $meta_value !== '' ) { // Check if meta_value is not empty string to avoid saving empty meta.
				// For variants, ensure they are decoded and re-encoded if any processing is needed.
				// For now, direct copy.
				update_post_meta( $new_test_id, $meta_key, $meta_value );
			}
		}
		
		// Set default status for the new test
		update_post_meta( $new_test_id, '_blft_status', 'draft' );

		// Clear instance-specific data
		delete_post_meta( $new_test_id, '_blft_start_date' );
		delete_post_meta( $new_test_id, '_blft_test_end_date' );
		delete_post_meta( $new_test_id, '_blft_winner_variant_id' );


		$new_post_object = get_post( $new_test_id );
		$response        = $this->prepare_item_for_response( $new_post_object, $request );
		$response        = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $new_test_id ) ) );

		return $response;
	}

	/**
	 * Permission check for duplicating a test.
	 */
	public function duplicate_test_permissions_check( $request ) {
		// Check if user can create new posts of this type
		$post_type_obj = get_post_type_object( CPT_Manager::CPT_SLUG );
		if ( ! current_user_can( $post_type_obj->cap->create_posts ) ) {
			return new \WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to create (duplicate) tests.', 'brickslift-ab-testing' ), [ 'status' => rest_authorization_required_code() ] );
		}
		// Also check if user can read the original post they are trying to duplicate
		$original_post_id = (int) $request['id'];
		$original_post    = get_post( $original_post_id );
		if ( ! $original_post || CPT_Manager::CPT_SLUG !== $original_post->post_type ) {
			return new \WP_Error( 'rest_post_invalid_id', __( 'Invalid original test ID.', 'brickslift-ab-testing' ), [ 'status' => 404 ] );
		}
		// No specific read check here as create_posts implies ability to see content to duplicate.
		// If more granular control is needed, add a 'read_post' check for $original_post_id.
		return true;
	}


	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}
		$schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => CPT_Manager::CPT_SLUG,
			'type'       => 'object',
			'properties' => [
				'id'           => [ 'description' => __( 'Unique identifier for the test.', 'brickslift-ab-testing' ), 'type' => 'integer', 'context' => [ 'view', 'edit', 'embed' ], 'readonly' => true ],
				'title'        => [ 'description' => __( 'Title of the test.', 'brickslift-ab-testing' ), 'type' => 'object', 'context' => [ 'view', 'edit', 'embed' ],
					'properties'  => [
						'raw'      => [ 'description' => __( 'Title for the object, as it exists in the database.', 'brickslift-ab-testing' ), 'type' => 'string', 'context' => [ 'view', 'edit' ] ],
						'rendered' => [ 'description' => __( 'HTML-rendered title for the object.', 'brickslift-ab-testing' ), 'type' => 'string', 'context' => [ 'view', 'edit', 'embed' ], 'readonly' => true ],
					],
					'required'    => [ 'raw' ],
				],
				'status'       => [ 'description' => __( 'Custom A/B test status (draft, running, paused, completed, archived).', 'brickslift-ab-testing' ), 'type' => 'string', 'enum' => [ 'draft', 'running', 'paused', 'completed', 'archived' ], 'default' => 'draft', 'context' => [ 'view', 'edit' ] ],
				'wordpress_status' => [ 'description' => __( 'WordPress post status (e.g., publish, draft, trash).', 'brickslift-ab-testing' ), 'type' => 'string', 'enum' => array_keys( get_post_stati( [ 'show_in_admin_status_list' => true ], 'objects' ) ), 'context' => [ 'view', 'edit' ], 'readonly' => true ],
				'start_date' => [ 'description' => __( 'Start date of the test (YYYY-MM-DD).', 'brickslift-ab-testing' ), 'type' => 'string', 'format' => 'date', 'context' => [ 'view', 'edit' ] ],
				'end_date' => [ 'description' => __( 'End date of the test (YYYY-MM-DD).', 'brickslift-ab-testing' ), 'type' => 'string', 'format' => 'date', 'context' => [ 'view', 'edit' ] ],
				'hypothesis' => [ 'description' => __( 'Hypothesis for the A/B test.', 'brickslift-ab-testing' ), 'type' => 'string', 'context' => [ 'view', 'edit' ] ],
				'winner_variant_id' => [ 'description' => __( 'ID of the winning variant.', 'brickslift-ab-testing' ), 'type' => 'string', 'context' => [ 'view', 'edit' ] ],
				'variations_count' => [ 'description' => __( 'Number of variations in the test.', 'brickslift-ab-testing' ), 'type' => 'integer', 'context' => [ 'view', 'edit' ], 'readonly' => true ],
				'description'  => [ 'description' => __( 'Description of the A/B test.', 'brickslift-ab-testing' ), 'type' => 'string', 'context' => [ 'view', 'edit' ], 'default' => '' ],
				'variants'     => [ 'description' => __( 'Variants for the A/B test.', 'brickslift-ab-testing' ), 'type' => 'array', 'context' => [ 'view', 'edit' ],
					'items'       => [ 'type' => 'object',
						'properties' => [
							'id'           => [ 'type' => 'string', 'description' => __( 'Unique ID for the variant (e.g., UUID).', 'brickslift-ab-testing' ), 'context' => [ 'view', 'edit' ] ],
							'name'         => [ 'type' => 'string', 'description' => __( 'Name of the variant.', 'brickslift-ab-testing' ), 'context' => [ 'view', 'edit' ] ],
							'distribution' => [ 'type' => 'integer', 'description' => __( 'Traffic distribution percentage for the variant.', 'brickslift-ab-testing' ), 'context' => [ 'view', 'edit' ], 'minimum' => 0, 'maximum' => 100 ],
						],
						'required'   => [ 'id', 'name', 'distribution' ],
					],
				],
				'goal_type' => [ 'description' => __( 'Type of conversion goal.', 'brickslift-ab-testing' ), 'type' => 'string', 'enum' => ['page_visit', 'selector_click', 'form_submission', 'wc_add_to_cart', 'scroll_depth', 'time_on_page', 'custom_js_event'], 'default' => 'page_visit', 'context' => [ 'view', 'edit' ] ],
				'run_tracking_globally' => [ 'description' => __( 'Run tracking on all pages.', 'brickslift-ab-testing' ), 'type' => 'boolean', 'default' => false, 'context' => [ 'view', 'edit' ] ],
				'gdpr_consent_required' => [ 'description' => __( 'Is GDPR consent required?', 'brickslift-ab-testing' ), 'type' => 'boolean', 'default' => false, 'context' => [ 'view', 'edit' ] ],
				'gdpr_consent_mechanism' => [ 'description' => __( 'GDPR consent mechanism.', 'brickslift-ab-testing' ), 'type' => 'string', 'enum' => ['none', 'cookie_key'], 'default' => 'none', 'context' => [ 'view', 'edit' ] ],
				'gdpr_consent_key_name' => [ 'description' => __( 'Cookie key name for GDPR consent.', 'brickslift-ab-testing' ), 'type' => 'string', 'context' => [ 'view', 'edit' ], 'default' => '' ],
				'gdpr_consent_key_value' => [ 'description' => __( 'Cookie key value for GDPR consent.', 'brickslift-ab-testing' ), 'type' => 'string', 'context' => [ 'view', 'edit' ], 'default' => '' ],
				// Lifecycle fields
				'test_duration_type' => [ 'description' => __('Type of test duration (none, fixed_days, end_date).', 'brickslift-ab-testing'), 'type' => 'string', 'enum' => ['none', 'fixed_days', 'end_date'], 'default' => 'none', 'context' => ['view', 'edit'] ],
				'test_duration_days' => [ 'description' => __('Number of days for fixed duration tests.', 'brickslift-ab-testing'), 'type' => 'integer', 'default' => 7, 'context' => ['view', 'edit'] ],
				'test_auto_end_condition' => [ 'description' => __('Condition for automatic test ending (none, min_views, min_conversions).', 'brickslift-ab-testing'), 'type' => 'string', 'enum' => ['none', 'min_views', 'min_conversions'], 'default' => 'none', 'context' => ['view', 'edit'] ],
				'test_auto_end_value' => [ 'description' => __('Value for the auto-end condition (e.g., number of views/conversions).', 'brickslift-ab-testing'), 'type' => 'integer', 'default' => 1000, 'context' => ['view', 'edit'] ],
			],
		];

		$goal_specific_fields_schema = $this->get_goal_specific_schema_fields();
		foreach ($goal_specific_fields_schema as $key => $field_schema_args) {
			$schema['properties'][$key] = [
				'description' => $field_schema_args['description'],
				'type'        => $field_schema_args['type'],
				'context'     => [ 'view', 'edit' ],
			];
			if (isset($field_schema_args['format'])) $schema['properties'][$key]['format'] = $field_schema_args['format'];
			if (isset($field_schema_args['enum'])) $schema['properties'][$key]['enum'] = $field_schema_args['enum'];
			if (isset($field_schema_args['default'])) $schema['properties'][$key]['default'] = $field_schema_args['default'];
			if (isset($field_schema_args['minimum'])) $schema['properties'][$key]['minimum'] = $field_schema_args['minimum'];
			if (isset($field_schema_args['maximum'])) $schema['properties'][$key]['maximum'] = $field_schema_args['maximum'];
		}

		$schema['properties']['date_created'] = [ 'description' => __( "The date the test was created, in GMT.", 'brickslift-ab-testing' ), 'type' => 'string', 'format' => 'date-time', 'context' => [ 'view', 'edit' ], 'readonly' => true ];
		$schema['properties']['modified'] = [ 'description' => __( "The date the test was last modified, in GMT.", 'brickslift-ab-testing' ), 'type' => 'string', 'format' => 'date-time', 'context' => [ 'view', 'edit' ], 'readonly' => true ];

		$this->schema = $schema;
		return $this->add_additional_fields_schema( $this->schema );
	}
}