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
			'post_status'    => 'any',
		];
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
		if ( $this->is_field_present_in_schema( 'status', $schema ) ) $data['status'] = $item->post_status;
		if ( $this->is_field_present_in_schema( 'date_created', $schema ) ) $data['date_created'] = rest_get_date_with_gmt( $item->post_date_gmt, true );
		if ( $this->is_field_present_in_schema( 'modified', $schema ) ) $data['modified'] = rest_get_date_with_gmt( $item->post_modified_gmt, true );
		if ( $this->is_field_present_in_schema( 'blft_status', $schema ) ) $data['blft_status'] = get_post_meta( $item->ID, '_blft_status', true ) ?: 'draft';
		if ( $this->is_field_present_in_schema( 'description', $schema ) ) $data['description'] = get_post_meta( $item->ID, '_blft_description', true );
		if ( $this->is_field_present_in_schema( 'variants', $schema ) ) $data['variants'] = $this->get_variants_for_response( $item->ID );
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
		if ( $this->is_field_present_in_schema( 'gdpr_consent_key_name', $schema ) ) $data['gdpr_consent_key_name'] = get_post_meta( $item->ID, '_blft_gdpr_consent_key_name', true );
		if ( $this->is_field_present_in_schema( 'gdpr_consent_key_value', $schema ) ) $data['gdpr_consent_key_value'] = get_post_meta( $item->ID, '_blft_gdpr_consent_key_value', true );

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
			'post_status' => isset( $params['status'] ) ? sanitize_key( $params['status'] ) : 'draft',
		];
		if ( isset( $params['title'] ) ) {
			if ( is_string( $params['title'] ) ) $post_args['post_title'] = sanitize_text_field( $params['title'] );
			elseif ( is_array( $params['title'] ) && isset( $params['title']['raw'] ) ) $post_args['post_title'] = sanitize_text_field( $params['title']['raw'] );
		}
		if ( empty( $post_args['post_title'] ) ) $post_args['post_title'] = __( 'New A/B Test', 'brickslift-ab-testing' );
		$post_id = wp_insert_post( wp_slash( $post_args ), true );
		if ( is_wp_error( $post_id ) ) return $post_id;

		update_post_meta( $post_id, '_blft_status', isset( $params['blft_status'] ) ? sanitize_text_field( $params['blft_status'] ) : 'draft' );
		if ( isset( $params['description'] ) ) update_post_meta( $post_id, '_blft_description', sanitize_textarea_field( $params['description'] ) );
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
		if ( isset( $params['status'] ) ) $post_args['post_status'] = sanitize_key( $params['status'] );
		if ( count( $post_args ) > 1 ) {
			$updated = wp_update_post( wp_slash( $post_args ), true );
			if ( is_wp_error( $updated ) ) return $updated;
		}

		if ( isset( $params['blft_status'] ) ) update_post_meta( $id, '_blft_status', sanitize_text_field( $params['blft_status'] ) );
		if ( isset( $params['description'] ) ) update_post_meta( $id, '_blft_description', sanitize_textarea_field( $params['description'] ) );
		if ( isset( $params['variants'] ) && is_array( $params['variants'] ) ) $this->update_variants_meta( $id, $params['variants'] );
		if ( isset( $params['goal_type'] ) ) update_post_meta( $id, '_blft_goal_type', sanitize_text_field( $params['goal_type'] ) );
		$goal_specific_fields_schema = $this->get_goal_specific_schema_fields();
		foreach ( $goal_specific_fields_schema as $key => $field_args ) {
			if ( array_key_exists( $key, $params ) ) $this->update_single_goal_meta( $id, '_blft_' . $key, $params[ $key ], $field_args );
		}
		if ( array_key_exists( 'run_tracking_globally', $params ) ) update_post_meta( $id, '_blft_run_tracking_globally', rest_sanitize_boolean( $params['run_tracking_globally'] ) );
		if ( array_key_exists( 'gdpr_consent_required', $params ) ) update_post_meta( $id, '_blft_gdpr_consent_required', rest_sanitize_boolean( $params['gdpr_consent_required'] ) );
		if ( array_key_exists( 'gdpr_consent_mechanism', $params ) ) update_post_meta( $id, '_blft_gdpr_consent_mechanism', sanitize_key( $params['gdpr_consent_mechanism'] ) );
		if ( array_key_exists( 'gdpr_consent_key_name', $params ) ) update_post_meta( $id, '_blft_gdpr_consent_key_name', sanitize_text_field( $params['gdpr_consent_key_name'] ) );
		if ( array_key_exists( 'gdpr_consent_key_value', $params ) ) update_post_meta( $id, '_blft_gdpr_consent_key_value', sanitize_text_field( $params['gdpr_consent_key_value'] ) );

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
		return parent::get_collection_params();
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
				'status'       => [ 'description' => __( 'WordPress post status.', 'brickslift-ab-testing' ), 'type' => 'string', 'enum' => array_keys( get_post_stati( [ 'show_in_admin_status_list' => true ], 'objects' ) ), 'context' => [ 'view', 'edit' ] ],
				'blft_status'  => [ 'description' => __( 'Custom A/B test status (draft, running, paused, completed).', 'brickslift-ab-testing' ), 'type' => 'string', 'enum' => [ 'draft', 'running', 'paused', 'completed' ], 'default' => 'draft', 'context' => [ 'view', 'edit' ] ],
				'description'  => [ 'description' => __( 'Description of the A/B test.', 'brickslift-ab-testing' ), 'type' => 'string', 'context' => [ 'view', 'edit' ] ],
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
				'gdpr_consent_key_name' => [ 'description' => __( 'Cookie key name for GDPR consent.', 'brickslift-ab-testing' ), 'type' => 'string', 'context' => [ 'view', 'edit' ] ],
				'gdpr_consent_key_value' => [ 'description' => __( 'Cookie key value for GDPR consent.', 'brickslift-ab-testing' ), 'type' => 'string', 'context' => [ 'view', 'edit' ] ],
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