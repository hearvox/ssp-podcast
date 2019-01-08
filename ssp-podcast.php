<?php
/*
Plugin Name:       SSP Podcast
Plugin URI:        https://storytelling.stanford.edu/category/shows/
Description:       Pull podcast episode data from an external feed (e.g., SoundCloud) then publish it in your own RSS feed.
Version:           0.1.3
Author:            Stanford Storytelling Project / Barrett Golding
Author URI:        https://storytelling.stanford.edu/
License:           GPL-2.0+
License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain:       ssppod
Domain Path:       /languages/
Plugin Prefix:     ssppod
*/

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

/* ------------------------------------------------------------------------ *
 * Plugin init and uninstall text change
 * ------------------------------------------------------------------------ */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( defined( 'SSPPOD_VERSION' ) ) {
    return;
}

/* ------------------------------------------------------------------------ *
 * Constants: plugin version, name, and the path and URL to directory.
 *
 * SSPPOD_BASENAME ssp-podcast-master/ssp-podcast.php
 * SSPPOD_DIR      /path/to/wp-content/plugins/ssp-podcast-master/
 * SSPPOD_URL      https://example.com/wp-content/plugins/ssp-podcast-master/
 * ------------------------------------------------------------------------ */
define( 'SSPPOD_VERSION', '0.1.3' );
define( 'SSPPOD_BASENAME', plugin_basename( __FILE__ ) );
define( 'SSPPOD_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'SSPPOD_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );

/**
 * Adds "Settings" link on plugin page (next to "Activate").
 */
//
function ssppod_plugin_settings_link( $links ) {
  $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=ssppod' ) ) . '">' . __( 'Settings', 'ssppod' ) . '</a>';
  array_unshift( $links, $settings_link );
  return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ssppod_plugin_settings_link' );

/**
 * Redirect to Settings screen upon plugin activation.
 *
 * @param  string $plugin Plugin basename (e.g., "my-plugin/my-plugin.php")
 * @return void
 */
function ssppod_activation_redirect( $plugin ) {
    if ( $plugin === SSPPOD_BASENAME ) {
        $redirect_uri = add_query_arg(
            array(
                'page' => 'ssppod' ),
                admin_url( 'options-general.php' )
            );
        wp_safe_redirect( $redirect_uri );
        exit;
    }
}
add_action( 'activated_plugin', 'ssppod_activation_redirect' );

/**
 * Load the plugin text domain for translation.
 *
 * @since   0.1.0
 */
function ssppod_load_textdomain() {
    load_plugin_textdomain( 'ssppod', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'ssppod_load_textdomain' );

/**
 * Sets default settings option upon activation, if options doesn't exist.
 *
 * @uses ssppod_get_options()   Safely get site option, check plugin version.
 */
function ssppod_activate() {
    ssppod_get_options();
}
register_activation_hook( __FILE__, 'ssppod_activate' );

/**
 * The code that runs during plugin deactivation (not currently used).
 */
/*
function ssppod_deactivate() {
}
register_deactivation_hook( __FILE__, 'ssppod_deactivate' );
*/

/* ------------------------------------------------------------------------ *
 * Required Plugin Files
 * ------------------------------------------------------------------------ */
include_once( dirname( __FILE__ ) . '/includes/admin-options.php' );
include_once( dirname( __FILE__ ) . '/includes/functions.php' );

