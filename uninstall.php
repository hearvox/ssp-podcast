<?php
/**
 * Fired when the plugin is uninstalled.
 *
 *
 * @link    http://hearingvoices.com/tools/
 * @since   0.1.0
 *
 * @package    SSP Podcast
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Removes plugin option from database.
 *
 * @since   0.1.0
 */
if ( function_exists( 'delete_option' ) ) {
    delete_option( 'ssppod' );
}
