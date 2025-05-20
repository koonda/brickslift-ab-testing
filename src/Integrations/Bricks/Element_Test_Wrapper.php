<?php
/**
 * Bricks Element: A/B Test Wrapper
 *
 * @package BricksLiftAB
 */

namespace BricksLiftAB\Integrations\Bricks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Bricks\Element' ) ) {
	return; // Bricks Element class not found
}

/**
 * Class Element_Test_Wrapper
 */
class Element_Test_Wrapper extends \Bricks\Element {
	/**
	 * Element name.
	 *
	 * @var string
	 */
	public $name = 'blft-test-wrapper';

	/**
	 * Element label.
	 *
	 * @var string
	 */
	// public $label; // Set in get_label() method instead

	/**
	 * Element category.
	 *
	 * @var string
	 */
	public $category = 'layout'; // Or a custom category, e.g., 'brickslift'

	/**
	 * Element icon.
	 *
	 * @var string
	 */
	public $icon = 'ti-layout-column2'; // Example icon, consider 'dashicons-forms' or a custom SVG

	/**
	 * Controls group.
	 *
	 * @var string
	 */
	public $controls_group = 'general';

	/**
	 * Whether the element is nestable.
	 *
	 * @var bool
	 */
	public $nestable = true; // Use $nestable as per Bricks documentation

	/**
	 * Constructor.
	 */
	// public function __construct( $element = null ) { // Label is set via get_label()
	//  parent::__construct( $element );
	// }

	/**
	 * Get the localized element label.
	 *
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'A/B Test Wrapper (BL)', 'brickslift-ab-testing' );
	}

	/**
	 * Set element controls.
	 */
	public function set_controls() {
		$this->controls['info'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'A/B Test Setup', 'brickslift-ab-testing' ),
			'type'    => 'info',
			'content' => esc_html__( 'Select an A/B test to run within this wrapper. Each direct child element/section inside this wrapper will be treated as a variant.', 'brickslift-ab-testing' ),
		];

		$this->controls['selected_test_id'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Select A/B Test', 'brickslift-ab-testing' ),
			'type'        => 'select',
			'options'     => $this->get_ab_tests_options(),
			'placeholder' => esc_html__( 'Select a test', 'brickslift-ab-testing' ),
			'description' => esc_html__( 'Choose the A/B test you want to associate with this content area.', 'brickslift-ab-testing' ),
		];

		// Note: Variant content is managed by nesting elements directly under this wrapper in Bricks editor.
		// The number of direct children should correspond to the number of variants in the selected test.
		// We will add JavaScript to handle showing/hiding these children based on the selected variant.
	}

	/**
	 * Get A/B tests for select control options.
	 *
	 * @return array
	 */
	private function get_ab_tests_options() {
		$options = [];
		$tests   = get_posts(
			[
				'post_type'   => \BricksLiftAB\Core\CPT_Manager::CPT_SLUG,
				'numberposts' => -1,
				'post_status' => ['publish', 'draft', 'running', 'paused'], // Include relevant statuses
				'orderby'     => 'title',
				'order'       => 'ASC',
			]
		);

		if ( $tests ) {
			foreach ( $tests as $test ) {
				// translators: %1$s: Test Title, %2$d: Test ID.
				$options[ $test->ID ] = sprintf( esc_html__( '%1$s (ID: %2$d)', 'brickslift-ab-testing' ), esc_html( $test->post_title ), $test->ID );
			}
		}
		return $options;
	}

	/**
	 * Render element HTML.
	 *
	 * @param array $settings Element settings.
	 * @param int   $element_id Element ID.
	 */
	public function render( $settings = [], $element_id = 0 ) {
		$selected_test_id = isset( $settings['selected_test_id'] ) ? absint( $settings['selected_test_id'] ) : 0;

		if ( ! $selected_test_id ) {
			if ( bricks_is_builder_main() ) {
				echo '<div class="blft-placeholder">' . esc_html__( 'Please select an A/B Test in the element settings.', 'brickslift-ab-testing' ) . '</div>';
			}
			return;
		}

		// The main wrapper for the A/B test area
		echo "<div {$this->render_attributes( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'_root',
			[
				'class' => [ 'blft-test-container', "blft-test-{$selected_test_id}" ],
				'data-blft-test-id' => $selected_test_id,
			]
		)}>";

		// Render child elements (variants)
		// Each direct child will be a variant container.
		// Frontend JS will handle showing the correct one.
		if ( ! empty( $this->children ) ) {
			foreach ( $this->children as $index => $child_element ) {
				// Assign a variant ID (e.g., based on order or a predefined ID from test settings later)
				// For now, using index as a simple identifier. This needs to map to actual variant IDs from the test.
				// This part will be refined when linking to actual variant data from the CPT.
				$variant_identifier = 'variant-' . $index; // Placeholder

				echo "<div class=\"blft-variant-wrapper blft-variant-hidden\" data-blft-variant-identifier=\"{$variant_identifier}\">"; // All hidden by default
				$child_element->render();
				echo '</div>';
			}
		} elseif ( bricks_is_builder_main() ) {
			echo '<div class="blft-placeholder">' . esc_html__( 'Add child elements to serve as variants for this test.', 'brickslift-ab-testing' ) . '</div>';
		}

		echo '</div>'; // Close .blft-test-container
	}
}