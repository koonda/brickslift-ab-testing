<?php
/**
 * Uninstall BricksLift A/B Testing
 *
 * @package BricksLiftAB
 */

// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// @TODO: Add uninstallation logic here:
// - Delete custom tables
// - Delete options
// - Delete CPT data