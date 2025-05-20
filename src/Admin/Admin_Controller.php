<?php
/**
 * Admin Controller for BricksLift A/B Testing.
 *
 * @package BricksLiftAB
 */

namespace BricksLiftAB\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin_Controller
 *
 * Handles the admin menu, pages, and overall admin area setup.
 */
class Admin_Controller {

	/**
	 * Assets_Manager instance.
	 *
	 * @var Assets_Manager
	 */
	public $assets_manager;

	/**
	 * The hook suffix for the main admin page.
	 *
	 * @var string|false
	 */
	private $main_page_hook_suffix = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->assets_manager = new Assets_Manager();
		add_action( 'admin_menu', [ $this, 'register_admin_pages' ] );
	}

	/**
	 * Register admin menu pages.
	 */
	public function register_admin_pages() {
		// The CPT 'blft_test' (registered with 'show_in_menu' => true) creates the top-level menu.
		// We add our React app as a submenu to it.
		$this->main_page_hook_suffix = add_submenu_page(
			'edit.php?post_type=' . \BricksLiftAB\Core\CPT_Manager::CPT_SLUG, // Parent slug
			__( 'A/B Test Dashboard', 'brickslift-ab-testing' ),             // Page title (for browser tab)
			__( 'Dashboard', 'brickslift-ab-testing' ),                       // Menu title (what appears in submenu)
			'manage_options',                                                 // Capability
			'brickslift-ab-dashboard',                                        // Menu slug (must be unique for this submenu page)
			[ $this, 'render_admin_page' ]                                    // Function to render the page
		);

		// If you want the "Dashboard" to be the first submenu item, you might need to add it with a low position,
		// or adjust other submenu items that WordPress automatically adds for the CPT (like "All A/B Tests", "Add New").
		// For now, this will add "Dashboard" as one of the submenu items.

		// Note: The CPT 'blft_test' will automatically have "All A/B Tests" and "Add New" submenus
		// under its main "BricksLift A/B" menu item.
	}

	/**
	 * Render the main admin page.
	 * This page will host the React application.
	 */
	public function render_admin_page() {
		?>
		<div class="wrap">
			<div id="blft-admin-root">
				<h2><?php esc_html_e( 'Loading BricksLift A/B Testing App...', 'brickslift-ab-testing' ); ?></h2>
				<p><?php esc_html_e( 'Please ensure JavaScript is enabled.', 'brickslift-ab-testing' ); ?></p>
			</div>
			<script type="text/javascript">
				console.log('Admin_Controller: blft-admin-root div has been rendered. Initial innerHTML length:', document.getElementById('blft-admin-root') ? document.getElementById('blft-admin-root').innerHTML.length : 'not found');
				document.addEventListener('DOMContentLoaded', function() {
					var adminRoot = document.getElementById('blft-admin-root');
					if (adminRoot) {
						console.log('Admin_Controller: DOMContentLoaded - blft-admin-root found by inline script. Current innerHTML length:', adminRoot.innerHTML.length);
						// adminRoot.innerHTML = '<h1>Inline Script Replaced Content!</h1>'; // Uncomment for aggressive test
						
						// Check after a short delay if React has replaced the content
						setTimeout(function() {
							var adminRootAfterTimeout = document.getElementById('blft-admin-root');
							if (adminRootAfterTimeout) {
								console.log('Admin_Controller: setTimeout(500ms) - blft-admin-root found. Current innerHTML length:', adminRootAfterTimeout.innerHTML.length);
								if (adminRootAfterTimeout.innerHTML.includes('Loading BricksLift A/B Testing App...')) {
									console.warn('Admin_Controller: setTimeout(500ms) - React app does NOT seem to have rendered into blft-admin-root.');
								} else {
									console.log('Admin_Controller: setTimeout(500ms) - React app SEEMS to have rendered into blft-admin-root (loading message gone).');
								}
							} else {
								console.error('Admin_Controller: setTimeout(500ms) - blft-admin-root NOT found.');
							}
						}, 500); // 500ms delay

					} else {
						console.error('Admin_Controller: DOMContentLoaded - blft-admin-root NOT found by inline script.');
					}
				});
			</script>
		</div>
		<?php
	}

	/**
	 * Get the main page hook suffix.
	 *
	 * @return string|false
	 */
	public function get_main_page_hook_suffix() {
		return $this->main_page_hook_suffix;
	}
}