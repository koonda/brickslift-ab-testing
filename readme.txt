=== BricksLift A/B Testing ===
Contributors: (your WordPress.org username)
Donate link: https://example.com/
Tags: bricks, bricksbuilder, a/b testing, split testing, conversion optimization
Requires at least: 5.8
Tested up to: (latest WordPress version)
Requires PHP: 7.4
Stable tag: 0.4.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A/B testing for Bricks Builder.

== Description ==

Provides an intuitive and powerful tool for A/B testing elements, sections, and entire Bricks templates directly in the Bricks editor.

== Installation ==

1. Upload `brickslift-ab-testing` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

* A question that someone might have.
* An answer to that question.

== Screenshots ==

1. Description of the first screenshot.

== Changelog ==
= 0.3.0 =
- Implemented individual test statistics dashboard with key metrics, daily trends, and winner indication.
- Created new REST API endpoint for daily statistics (`/blft/v1/test-stats-daily/<test_id>`).
- Simplified frontend event tracking to use a single AJAX endpoint.
- Centralized cookie management in the backend AJAX handler.
- Ensured aggregated statistics table is populated exclusively by the daily cron job.
- Harmonized test status management to consistently use `_blft_status` meta field.
- Improved variant ID mapping in the Bricks Builder element.
- Mitigated database error disclosure in AJAX/API responses.
- Removed redundant `require_once` calls in favor of Composer autoloading.
- Updated inline script in `Frontend_Controller.php` to use `fetch` API (later removed this entire server-side conversion check).
- Made GDPR consent check stricter by default if test settings are missing.
- Removed server-side `check_for_conversion()` method, relying on client-side JS for 'page_visit' goals.

== 0.2.0 ==
- Updated plugin version to 0.2.0.

= 0.1.0 =
* Initial release.

== Upgrade Notice ==

= 0.1.0 =
Initial release.