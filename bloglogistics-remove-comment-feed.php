<?php
/**
 * Plugin Name: BlogLogistics Comment Feed Control
 * Plugin URI: https://www.bloglogistics.com
 * Description: Control the display and availability of comments RSS feeds on your WordPress website.
 * Version: 1.1.0
 * Author: Roger Wheatley
 * License: GPLv2 or later
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class BlogLogistics_Comment_Feed_Control
 *
 * Manages the disabling and enabling of WordPress comment RSS feeds.
 */
class BlogLogistics_Comment_Feed_Control {

    /**
     * Constructor.
     *
     * Initializes the plugin by setting up hooks.
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

        if ( $this->is_comments_feed_disabled() ) {
            add_action( 'do_feed_rss2_comments', array( $this, 'disable_comments_feed_display' ), 1 );
            add_action( 'do_feed_atom_comments', array( $this, 'disable_comments_feed_display' ), 1 );
            add_action( 'wp_head', array( $this, 'remove_comments_feed_links' ), 3 ); // Lower priority to ensure it runs after other plugins/themes.
            add_action( 'template_redirect', array( $this, 'buffer_and_remove_feed_link_from_output' ) );
        }
    }

    /**
     * Check if the comments feed is set to be disabled.
     *
     * @return bool True if comments feed should be disabled, false otherwise.
     */
    private function is_comments_feed_disabled() {
        return (bool) get_option( 'bloglogistics_disable_comments_feed', true ); // Default to true for backward compatibility.
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting(
            'bloglogistics_comment_feed_options', // Option group
            'bloglogistics_disable_comments_feed', // Option name
            array(
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => true,
                'show_in_rest'      => false,
            )
        );

        add_settings_section(
            'bloglogistics_comment_feed_section', // ID
            esc_html__( 'Comment Feed Settings', 'bloglogistics-comment-feed' ), // Title
            null, // Callback
            'bloglogistics_comment_feed' // Page
        );

        add_settings_field(
            'bloglogistics_disable_comments_feed_field', // ID
            esc_html__( 'Disable Comment RSS Feed', 'bloglogistics-comment-feed' ), // Title
            array( $this, 'disable_comments_feed_callback' ), // Callback
            'bloglogistics_comment_feed', // Page
            'bloglogistics_comment_feed_section' // Section
        );
    }

    /**
     * Callback for the "Disable Comment RSS Feed" setting field.
     */
    public function disable_comments_feed_callback() {
        $disabled = $this->is_comments_feed_disabled();
        ?>
        <label for="bloglogistics_disable_comments_feed">
            <input type="checkbox" id="bloglogistics_disable_comments_feed" name="bloglogistics_disable_comments_feed" value="1" <?php checked( $disabled, true ); ?> />
            <?php esc_html_e( 'Check this box to disable all comments RSS feeds and their links.', 'bloglogistics-comment-feed' ); ?>
        </label>
        <?php
    }

    /**
     * Add admin menu page.
     */
    public function add_admin_menu() {
        add_options_page(
            esc_html__( 'Comment Feed Control', 'bloglogistics-comment-feed' ), // Page title
            esc_html__( 'Comment Feeds', 'bloglogistics-comment-feed' ), // Menu title
            'manage_options', // Capability required to access
            'bloglogistics_comment_feed', // Menu slug
            array( $this, 'settings_page_content' ) // Callback function to render the page
        );
    }

    /**
     * Render the settings page content.
     */
    public function settings_page_content() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'BlogLogistics Comment Feed Control', 'bloglogistics-comment-feed' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'bloglogistics_comment_feed_options' );
                do_settings_sections( 'bloglogistics_comment_feed' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Display a custom message instead of the RSS Feeds for comments.
     *
     * @return void
     */
    public function disable_comments_feed_display() {
        wp_die(
            sprintf(
                /* translators: %1$s: opening anchor tag, %2$s: closing anchor tag */
                esc_html__( 'Comments feed not available, please visit the %1$shomepage%2$s!', 'bloglogistics-comment-feed' ),
                '<a href="' . esc_url( home_url( '/' ) ) . '">',
                '</a>'
            ),
            esc_html__( 'Comments Feed Disabled', 'bloglogistics-comment-feed' ),
            array( 'response' => 200 ) // Send 200 OK status to avoid issues with crawlers.
        );
    }

    /**
     * Remove links to comments feed from the header.
     *
     * This targets the standard WordPress `feed_links_extra` output.
     */
    public function remove_comments_feed_links() {
        remove_action( 'wp_head', 'feed_links_extra', 3 );
        // Also remove if theme adds it via `feed_links` (less common for comment feeds).
        remove_action( 'wp_head', 'feed_links', 2 );
    }

    /**
     * Buffers output to remove comments feed link from WP head for themes that add it
     * outside the standard WP function or after initial `wp_head` actions.
     */
    public function buffer_and_remove_feed_link_from_output() {
        ob_start( array( $this, 'filter_output_for_feed_link' ) );
    }

    /**
     * Callback for `ob_start` to filter the output and remove comment feed links.
     *
     * @param string $output The output buffer content.
     * @return string The filtered output.
     */
    public function filter_output_for_feed_link( $output ) {
        // Regex to match various comment feed link formats.
        $regex = '/<link\s+rel=[\'"]alternate[\'"]\s+type=[\'"]application\/rss\+xml[\'"]\s+title=[\'"][^\'"]*(Comments|Comment)\s+Feed[\'"]\s+href=[\'"][^\'"]*\/comments\/feed\/[\'"]\s*\/?>/i';
        return preg_replace( $regex, '', $output );
    }
}

// Instantiate the plugin class.
new BlogLogistics_Comment_Feed_Control();