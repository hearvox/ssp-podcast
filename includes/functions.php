<?php
/**
 * General functions for reading and writing plugin settings.
 *
 * @link    http://hearingvoices.com/tools/
 * @since   0.1.0
 *
 * @package    SSP Podcast
 * @subpackage ssp-podcast/includes
 */

/* ------------------------------------------------------------------------ *
 * Functions to get/set options array.
 * ------------------------------------------------------------------------ */

/**
 * Retrieves an option, and array of plugin settings, from database.
 *
 * Option functions based on Jetpack Stats:
 * @link https://github.com/Automattic/jetpack/blob/master/modules/stats.php
 *
 * @since   0.1.0
 *
 * @uses    ssppod_upgrade_options()
 * @return  array   $options    Array of plugin settings
 */
function ssppod_get_options() {
    $options = get_option( 'ssppod' );

    // Set version if not the latest.
    if ( ! isset( $options['version'] ) || $options['version'] < SSPPOD_VERSION ) {
        $options = ssppod_upgrade_options( $options );
    }

    return $options;
}

/**
 * Makes array of plugin settings, merging default and new values.
 *
 * @since   0.1.0
 *
 * @uses    ssppod_set_options()
 * @param   array   $options        Array of plugin settings
 * @return  array   $new_options    Merged array of plugin settings
 */
function ssppod_upgrade_options( $options ) {
    $defaults = array(
        'feed_pull_url'  => '',
        'feed_push_url'  => '',
        'feed_push_path' => '',
        'feed_tags_url'  => SSPPOD_URL . 'xml/podcast-tags.xml',
    );

    if ( is_array( $options ) && ! empty( $options ) ) {
        $new_options = array_merge( $defaults, $options );
    } else {
        $new_options = $defaults;
    }

    $new_options['version'] = SSPPOD_VERSION;

    ssppod_set_options( $new_options );

    return $new_options;
}

/**
 * Sets an option in database (an array of plugin settings).
 *
 * Note: update_option() adds option if it doesn't exist.
 *
 * @since   0.1.0
 *
 * @param   array   $option     Array of plugin settings
 */
function ssppod_set_options( $options ) {
    $options_clean = ssppod_sanitize_data( $options );
    update_option( 'ssppod', $options_clean );
}

/* ------------------------------------------------------------------------ *
 * Functions to get/set a specific options array item.
 * ------------------------------------------------------------------------ */

/**
 * Retrieves a specific setting (an array item) from an option (an array).
 *
 * @since   0.1.0
 *
 * @uses    ssppod_get_options()
 * @param   array|string    $option     Array item key
 * @return  array           $option_key Array item value
 */
function ssppod_get_option( $option_key = NULL ) {
    $options = ssppod_get_options();

    // Returns valid inner array key ($options[$option_key]).
    if ( isset( $options ) && $option_key != NULL && isset( $options[ $option_key ] ) ) {
            return $options[ $option_key ];
    } else { // Inner array key not valid.
    return NULL;
    }
}

/**
 * Sets a specified setting (array item) in the option (array of plugin settings).
 *
 * @since   0.1.0
 *
 * @uses    ssppod_set_options()
 *
 * @param   string  $option     Array item key of specified setting
 * @param   string  $value      Array item value of specified setting
 * @return  array   $options    Array of plugin settings
 */
function ssppod_set_option( $option, $value ) {
    $options = ssppod_get_options();

    $options[$option] = $value;

    ssppod_set_options( $options );
}

/**
 * Sanitizes values in an one- and multi- dimensional arrays.
 *
 * Used by post meta-box form before writing post-meta to database
 * and by Settings API before writing option to database.
 *
 * @link https://tommcfarlin.com/input-sanitization-with-the-wordpress-settings-api/
 *
 * @since    0.4.0
 *
 * @param    array    $input        The address input.
 * @return   array    $input_clean  The sanitized input.
 */
function ssppod_sanitize_data( $data = array() ) {
    // Initialize a new array to hold the sanitized values.
    $data_clean = array();

    // Check for non-empty array.
    if ( ! is_array( $data ) || ! count( $data )) {
        return array();
    }

    // Traverse the array and sanitize each value.
    foreach ( $data as $key => $value) {
        // For one-dimensional array.
        if ( ! is_array( $value ) && ! is_object( $value ) ) {
            // Remove blank lines and whitespaces.
            $value = preg_replace( '/^\h*\v+/m', '', trim( $value ) );
            $value = str_replace( ' ', '', $value );
            $data_clean[ $key ] = sanitize_text_field( $value );
        }

        // For multidimensional array.
        if ( is_array( $value ) ) {
            $data_clean[ $key ] = ssppod_sanitize_data( $value );
        }
    }

    return $data_clean;
}

/**
 * Sanitizes values in an one-dimensional array.
 * (Used by post meta-box form before writing post-meta to database.)
 *
 * @link https://tommcfarlin.com/input-sanitization-with-the-wordpress-settings-api/
 *
 * @since    0.4.0
 *
 * @param    array    $input        The address input.
 * @return   array    $input_clean  The sanitized input.
 */
function ssppod_sanitize_array( $input ) {
    // Initialize a new array to hold the sanitized values.
    $input_clean = array();

    // Traverse the array and sanitize each value.
    foreach ( $input as $key => $val ) {
        $input_clean[ $key ] = sanitize_text_field( $val );
    }

    return $input_clean;
}

function ssppod_remove_empty_lines( $string ) {
    return preg_replace( "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $string );
    // preg_replace( '/^\h*\v+/m', '', $string );
}

function ssppod_object_to_array($d) {
        if (is_object($d))
            $d = get_object_vars($d);

        return is_array($d) ? array_map(__FUNCTION__, $d) : $d;
}


/* ------------------------------------------------------------------------ *
 * Functions to check URLs.
 * ------------------------------------------------------------------------ */
/**
 * Checks if URL exists. (Not used yet.)
 * @todo Add status code as tax-meta upon settings wp_insert_term.
 *
 * @since   0.1.0
 *
 * @param  $url         URL to be checked.
 * @return int|string   URL Sstatus repsonse code number, or WP error on failure.
 */
function ssppod_url_exists( $url = '' ) {
    // Make absolute URLs for WP core scripts (from their registered relative 'src' URLs)
    if ( substr( $url, 0, 13 ) === '/wp-includes/' || substr( $url, 0, 10 ) === '/wp-admin/' ) {
        $url = get_bloginfo( 'wpurl' ) . $url;
    }

    // Make protocol-relative URLs absolute  (i.e., from "//example.com" to "https://example.com" )
    if ( substr( $url, 0, 2 ) === '//' ) {
        $url = 'https:' . $url;
    }

    if ( has_filter( 'ssppod_url_exists' ) ) {
        $url = apply_filters( 'ssppod_url_exists', $url );
    }

    // Sanitize
    $url = esc_url_raw( $url );

    // Get URL header
    $response = wp_remote_head( $url );
    if ( is_wp_error( $response ) ) {
        return 'Error: ' . is_wp_error( $response );
    }

    // Request success, return header response code
    return wp_remote_retrieve_response_code( $response );
}
