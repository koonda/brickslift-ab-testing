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
	 * The hook suffix for the dashboard admin page.
	 *
	 * @var string|false
	 */
	private $dashboard_hook_suffix = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Note: $this->dashboard_hook_suffix is not available here yet,
		// as admin_menu runs later. We'll pass it when Assets_Manager needs it,
		// or adjust Assets_Manager to receive it later.
		// For now, we'll pass it during admin_enqueue_scripts.
		// Let's defer the direct passing to Assets_Manager constructor for now
		// and ensure it's available for the enqueue_scripts action.
		// The task asks to pass it to constructor, so we'll need to instantiate Assets_Manager
		// after the hook_suffix is set, or pass it via a setter.
		// Let's try instantiating it in register_admin_pages or pass it via a method.
		// The simplest approach given the current structure is to pass it after it's set.
		// However, the request is to pass it to the constructor.
		// This implies Assets_Manager might need to be instantiated later, or its dependency injected.

		// Let's adjust to instantiate Assets_Manager after the hook is available.
		// This means moving the instantiation or having a setter.
		// The prompt implies passing to constructor. So, we'll need to ensure
		// the hook suffix is available *before* Assets_Manager is newed up.
		// This is tricky as __construct runs before admin_menu.

		// Re-thinking: The prompt says "When instantiating the Assets_Manager (likely in the Admin_Controller's constructor or an init method)".
		// If we instantiate in constructor, we don't have the hook suffix yet.
		// Let's assume Assets_Manager will be instantiated *after* `register_admin_pages` has run,
		// or we'll pass it via a setter.
		// For now, I will prepare the property and the assignment.
		// The actual passing to Assets_Manager constructor will be handled by ensuring
		// Assets_Manager is instantiated *after* this hook is set.
		// Let's modify the constructor to instantiate Assets_Manager *conditionally* or pass it later.
		// The most direct interpretation of the prompt is to pass it to the constructor.
		// This means we must ensure `dashboard_hook_suffix` is set *before* `new Assets_Manager()`.
		// This is not possible if `Assets_Manager` is created in `Admin_Controller::__construct`
		// and `dashboard_hook_suffix` is set in `register_admin_pages` (called by `admin_menu` hook).

		// Let's follow the prompt's structure:
		// 1. Add property: $dashboard_hook_suffix
		// 2. Capture in add_admin_pages
		// 3. Pass to Assets_Manager constructor.

		// This means we need to instantiate Assets_Manager *after* the hook is captured.
		// A common pattern is to initialize components on a WordPress hook like 'plugins_loaded' or 'init'.
		// Or, instantiate Assets_Manager inside `register_admin_pages` after the hook is obtained.

		// Let's modify the constructor to pass it, assuming Assets_Manager will be initialized
		// at a point where $this->dashboard_hook_suffix is available.
		// The prompt says "When instantiating the Assets_Manager (likely in the Admin_Controller's constructor or an init method)"
		// This implies we might need to adjust *where* Assets_Manager is instantiated.

		// Let's assume for now that Assets_Manager will be instantiated *after* the hook is set.
		// The current code `new Assets_Manager()` is in the constructor.
		// This is a conflict.
		// I will add the property and capture the hook.
		// Then, I will modify the constructor to pass it, and it will be the user's responsibility
		// to ensure Assets_Manager is instantiated at the right time, or I will adjust it if the next step allows.

		// Let's make a minimal change to the constructor for now, and then adjust where Assets_Manager is created.
		// The prompt is quite specific: "When instantiating the Assets_Manager ... pass this correct $this->dashboard_hook_suffix".
		// This means the instantiation line `new Assets_Manager()` needs to change.

		// I will add the property, capture the hook, and then modify the `new Assets_Manager()` line.
		// The hook is set in `register_admin_pages`. So `Assets_Manager` must be created *after* that.
		// A simple way is to create it within `register_admin_pages` or on a later hook.
		// Let's create it in `register_admin_pages` after the hook is obtained.

		add_action( 'admin_menu', [ $this, 'register_admin_pages' ] );
		// $this->assets_manager will be initialized in register_admin_pages
	}

	/**
	 * Register admin menu pages.
	 */
	public function register_admin_pages() {
		// The CPT 'blft_test' (registered with 'show_in_menu' => true) creates the top-level menu.
		// We add our React app as a submenu to it.
		$this->dashboard_hook_suffix = add_submenu_page(
			'edit.php?post_type=' . \BricksLiftAB\Core\CPT_Manager::CPT_SLUG, // Parent slug
			__( 'A/B Test Dashboard', 'brickslift-ab-testing' ),             // Page title (for browser tab)
			__( 'Dashboard', 'brickslift-ab-testing' ),                       // Menu title (what appears in submenu)
			'manage_options',                                                 // Capability
			'brickslift-ab-dashboard',                                        // Menu slug (must be unique for this submenu page)
			[ $this, 'render_admin_page' ]                                    // Function to render the page
		);

		// Instantiate Assets_Manager here, now that we have the hook suffix.
		if ( $this->dashboard_hook_suffix ) {
			$this->assets_manager = new Assets_Manager( $this->dashboard_hook_suffix );
		} else {
			// Fallback or error handling if hook suffix is not generated
			// This case should ideally not happen with add_submenu_page
			$this->assets_manager = new Assets_Manager( '' ); // Or handle error
			error_log('BricksLift A/B Testing: Failed to get dashboard_hook_suffix.');
		}


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
	 * Get the dashboard page hook suffix.
	 *
	 * @return string|false
	 */
	public function get_dashboard_hook_suffix() {
		return $this->dashboard_hook_suffix;
	}
}