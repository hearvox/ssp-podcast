<?php
/**
 * Admin Settings Page (Dashboard> Settings> Postscript)
 *
 * @link    http://hearingvoices.com/tools/
 * @since   0.1.0
 *
 * @package    SSP Podcast
 * @subpackage ssp-podcast/includes
 */

/* ------------------------------------------------------------------------ *
 * Wordpress Settings API
 * ------------------------------------------------------------------------ */

/**
 * Adds submenu item to Settings dashboard menu.
 *
 * @since   0.1.0
 *
 * Sets Settings page screen ID: 'settings_page_postscript'.
 */
function ssppod_settings_menu() {
    $ssppod_options_page = add_options_page(
        __('SSP Podcast', 'ssppod' ),
        __( 'SSP Podcast', 'ssppod' ),
        'manage_options',
        'ssppod',
        'ssppod_settings_display'
    );

    // Adds contextual Help tab on Settings page.
    add_action( "load-$ssppod_options_page", 'ssppod_help_tab');
}
add_action('admin_menu', 'ssppod_settings_menu');

/**
 * Adds tabs, sidebar, and content to contextual Help tab on Settings page.
 *
 * Sets Settings page screen ID: 'settings_page_postscript'.
 * @since   0.1.0
 */
function ssppod_help_tab() {
    $current_screen = get_current_screen();

    // Default tab.
    $current_screen->add_help_tab(
        array(
            'id'        => 'settings',
            'title'     => __( 'Settings', 'ssppod' ),
            'content'   =>
                '<p>' . __( 'This plugin pulls data from Stanford Storytelling Project\'s SoundCloud feed, then published it into an feed at SSP, which must be on same server as this site. (Author: Barrett Golding &lt;bg@hearingvoices.com&gt;.) The settings URLs and path are:', 'ssppod' ) . '</p>' .
                    '<ul>' .
                        '<li>SC: https://feeds.soundcloud.com/users/soundcloud:users:19701305/sounds.rss</li>' .
                        '<li>SSP: https://web.stanford.edu/group/storytelling/rss.xml</li>' .
                        '<li>Path: /afs/ir/group/storytelling/WWW/rss.xml</li>' .
                        '<li>Tags: https://storytelling.stanford.edu/wp-content/plugins/ssp-podcast/xml/podcast-tags.xml</li>' .
                    '</ul>',
        )
    );



    // Sidebar.
    $current_screen->set_help_sidebar(
        '<p><strong>' . __( 'Links:', 'ssppod' ) . '</strong></p>' .
        '<p><a href="https://storytelling.stanford.edu/category/shows/state-of-the-human/">'     . __( 'SSP SOTH',     'postscript' ) . '</a></p>' .
        '<p><a href="https://soundcloud.com/stateofthehuman" target="_blank">' . __( 'SOTH@SoundCloud', 'ssppod' ) . '</a></p>' .
        '<p><a href="https://github.com/hearvox/ssp-podcast" target="_blank">' . __( 'GitHub repo', 'ssppod' ) . '</a></p>'
    );
}

/**
 * Renders settings menu page.
 *
 * @since   0.1.0
 */
function ssppod_settings_display() {
    ?>
    <div class="wrap">
        <h1>SSP Podcast</h1>
        <section style="padding-bottom: 2rem;">
            <header>
                <h2>Update podcast</h2>
            </header>
            <p>Use this button to pull items from external feed then publish them in your feed.</p>
            <!-- Update feed form -->
            <form method="post">
                <input type="submit" name="submit_soth" id="submit-soth" class="button button-primary" value="Update: State of the Human">
                <?php wp_nonce_field( 'ssppod_soth', 'ssppod_update_soth' ); ?>
            </form>
            <?php
            if ( isset( $_POST['submit_soth'] ) ) {
                if ( wp_verify_nonce( $_POST['ssppod_update_soth'], 'ssppod_soth' ) ) {
                    ssppod_upodate_feed_xml();
                } else {
                echo 'Error updating feed: notify admin.';
                }
            }
            // print_r( $_POST );
            ?>
        </section>
        <hr>
        <!-- Settings option (array) form -->
        <form method="post" action="options.php">
            <?php settings_fields( 'ssppod' ); ?>
            <?php do_settings_sections( 'ssppod' ); ?>
            <?php submit_button(); ?>
        </form>
    </div><!-- .wrap -->
    <?php
}

/**
 * Outputs textarea displaying feed XML.
 *
 * @since   0.1.0
 */
function ssppod_upodate_feed_xml() {
    $xml = $date_pub = $date_build = $item_count = $feed = $items = '';
    $options = ssppod_get_options(); // Options array: 'ssppod'.

    // URLs in options (empty string if not an URL).
    $feed_pull_url  = esc_url_raw( $options['feed_pull_url'] );
    $feed_push_url  = esc_url_raw( $options['feed_push_url'] );
    $feed_push_path = esc_url_raw( $options['feed_push_path'] );
    $feed_tags_url  = esc_url_raw( $options['feed_tags_url'] );

    // Get external feed, if option value is an URL.
    if ( $feed_pull_url && $feed_tags_url ) {
        $xml = simplexml_load_file( $feed_pull_url );
        // Insert external feed data into XML template.
        if ( isset( $xml->channel->item ) ) {
            // Get external feed data
            $date_pub   = ( isset( $xml->channel->pubDate ) ) ? $xml->channel->pubDate[0] : '';
            $date_build = ( isset( $xml->channel->lastBuildDate ) ) ? $xml->channel->lastBuildDate[0] : '';
            $item_count = count( $xml->channel->item );

            // Get XML template.
            $feed = file_get_contents( $feed_tags_url );
            $feed = str_replace( '<!-- pubDate -->', $date_pub, $feed); // Insert pub date.
            $feed = str_replace( '<!-- lastBuildDate -->', $date_build, $feed); // Insert last build date.

            // Get items as XMl.
            foreach ( $xml->channel->item as $item ) {
                $items .= $item->asXML();
            }

            $items = str_replace( ' (full episode)</title>', '</title>', $items); // Clean item titles.
            $feed  = str_replace( '<!-- items -->', $items, $feed); // Insert RSS items.
        }
    }

    // Write to file (combines: open, write, close).
    $write = file_put_contents( $feed_push_path, $feed);



    // Write resulting XML into textarea.
    $feed_new = file_get_contents( $feed_push_path );
    ?>
    <p><?php _e( 'Feed date:', 'ssppod' ); ?> <?php echo $date_pub; ?><br>
    <?php _e( 'Number of episodes:', 'ssppod' ); ?> <?php echo $item_count; ?><br>
    <?php _e( 'Bytes written:', 'ssppod' ); ?> <?php echo $write; ?></p>
    <p><label for="ssppod-feed-xml"><strong>Updated feed XML</strong></label><br>
    <textarea disabled="true" id="ssppod-feed-xml" name="ssppod-feed-xml" rows="12" cols="80" style="max-width: 90%;"><?php echo htmlentities( $feed_new ); ?></textarea></p><br>
    <?php
    // echo '<pre>'; print_r( $options ); echo '</pre>';
}

/* ------------------------------------------------------------------------ *
 * Setting Registrations
 * ------------------------------------------------------------------------ */

/**
 * Creates settings fields via WordPress Settings API.
 *
 * @since   0.1.0
 */
function ssppod_options_init() {

    // Array to pass to $callback functions as add_settings_field() $args (last param).
    $options = ssppod_get_options(); // Options array: 'ssppod'.

    add_settings_section(
        'ssppod_soth_settings_section',
        __( 'Settings: State of the Human', 'ssppod' ),
        'ssppod_section_callback',
        'ssppod'
    );

    add_settings_field(
        'ssppod_feed_pull_url',
        __( 'External feed URL', 'ssppod' ),
        'ssppod_feed_pull_url_callback',
        'ssppod',
        'ssppod_soth_settings_section',
        $args = array(
        	'label_for' => 'ssppod-url-feed-pull',
        	'value'     => ( isset( $options['feed_pull_url'] ) ) ? $options['feed_pull_url'] : ''
        )
    );

    add_settings_field(
        'ssppod_feed_push_url',
        __( 'Your feed URL', 'ssppod' ),
        'ssppod_feed_push_url_callback',
        'ssppod',
        'ssppod_soth_settings_section',
        $args = array(
        	'label_for' => 'ssppod-feed-push-url',
        	'value'     => ( isset( $options['feed_push_url'] ) ) ? $options['feed_push_url'] : ''
        )
    );

    add_settings_field(
        'ssppod_feed_push_path',
        __( 'Path to your feed', 'ssppod' ),
        'ssppod_feed_push_path_callback',
        'ssppod',
        'ssppod_soth_settings_section',
        $args = array(
            'label_for' => 'ssppod-feed-push-path',
            'value'     => ( isset( $options['feed_push_path'] ) ) ? $options['feed_push_path'] : ''
        )
    );

    add_settings_field(
        'ssppod_feed_tags_url',
        __( 'Feed tags URL', 'ssppod' ),
        'ssppod_feed_tags_url_callback',
        'ssppod',
        'ssppod_soth_settings_section',
        $args = array(
        	'label_for' => 'ssppod-feed-tags-url',
        	'value'     => ( isset( $options['feed_tags_url'] ) ) ? $options['feed_tags_url'] : ''
        )
    );

    register_setting(
        'ssppod',
        'ssppod',
        'ssppod_sanitize_data'
    );

}
add_action('admin_init', 'ssppod_options_init');

/*
ssppod_get_options() returns:
Array
(
    [feed_pull_url] => {URL}
    [feed_push_url] => {URL}
    [feed_push_path] => {file path}
    [feed_tags_url] => {URL}
    [version] => {vers#}
)
*/

/* ------------------------------------------------------------------------ *
 * Section Callbacks
 * ------------------------------------------------------------------------ */

/**
 * Outputs text for the top of the Settings screen.
 *
 * @since   0.1.0
 */
function ssppod_section_callback() {
    ?>
    <p>SSP Podcast <?php _e('pulls podcast episode data from an external feed (e.g., SoundCloud) then publishes thsoe items in your own podcast feed', 'ssppod' ); ?> (<?php _e('version', 'ssppod' ); ?> <?php echo SSPPOD_VERSION; ?>).</p>
    <?php
}

/* ------------------------------------------------------------------------ *
 * Field Callbacks (Get/Set Admin Option Array)
 * ------------------------------------------------------------------------ */

/**
 * Outputs URL form field to set external feed URL.
 *
 * @since   0.1.0
 */
function ssppod_feed_pull_url_callback( $args ) {
    ?>
    <input type="url" required id="ssppod-feed-pull-url" name="ssppod[feed_pull_url]" size="82" value="<?php if ( isset ( $args['value'] ) ) { echo esc_url( $args['value'] ); } ?>" pattern="https?://.+" title="Please specify https:// or http://." />
    <p class="description"><?php _e( 'Pull RSS episode items from this URL (e.g., a SoundCloud feed).', 'ssppod' ); ?></p>
    <?php
}

/**
 * Outputs URL form field to sets site feed URL.
 *
 * @since   0.1.0
 */
function ssppod_feed_push_url_callback( $args ) {
    ?>
    <input type="url" required id="ssppod-feed-push-url" name="ssppod[feed_push_url]" size="82" value="<?php if ( isset ( $args['value'] ) ) { echo esc_url( $args['value'] ); } ?>" pattern="https?://.+" title="Please specify https:// or http://." />
    <p class="description"><?php _e( 'Write RSS items to this feed file: <strong>Must</strong> be on same server as this WordPress site.', 'ssppod' ); ?><p>
    <?php
}

/**
 * Outputs text form field to set site feed path.
 *
 * @since   0.1.0
 */
function ssppod_feed_push_path_callback( $args ) {
    ?>
    <input type="text" required id="ssppod-feed-push-path" name="ssppod[feed_push_path]" size="82" value="<?php if ( isset ( $args['value'] ) ) { echo esc_attr( $args['value'] ); } ?>" />
    <p class="description"><?php _e( 'File path to above feed: <strong>Must</strong> be on same server as this WordPress site', 'ssppod' ); ?><p>
    <p class="description"><?php _e( 'Path to WordPress:', 'ssppod' ); ?> <?php echo ABSPATH; ?>
    <?php
}

/**
 * Outputs URL form field to set file with XML template tags.
 *
 * @since   0.1.0
 */
function ssppod_feed_tags_url_callback( $args ) {
    ?>
    <input type="url" required id="ssppod-feed-tags-url" name="ssppod[feed_tags_url]" size="82" value="<?php if ( isset ( $args['value'] ) ) { echo esc_url( $args['value'] ); } ?>" pattern="https?://.+" title="Please specify https:// or http://." />
    <p class="description"><?php _e( 'XML template file with tags that go above and below episode items', 'ssppod' ); ?> (<a href="<?php echo SSPPOD_URL; ?>includes/template-tags.xml"><?php _e( 'default file', 'ssppod' ); ?></a>).</p>
    <?php
}
