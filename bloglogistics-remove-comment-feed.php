<?php
/**
* Plugin Name: BlogLogistics Remove Comment Feed
* Plugin URI: https://www.bloglogistics.com
* Description: A simple plugin to remove the comments feed from WordPress sites. No settings needed, just activate and go.
* Version: 1.0.4
* Author: Roger Wheatley
 * License:         GPLv2 or later
*/

/**
 * Display a custom message instead of the RSS Feeds for comments.
 *
 * @return void
 */
function bloglogistics_snippet_disable_comments_feed() {
    wp_die(
        sprintf(
            // Translators: Placeholders for the homepage link.
            esc_html__('Comments feed not available, please visit the %1$shomepage%2$s!'),
            '<a href="' . esc_url(home_url('/')) . '">',
            '</a>'
        )
    );
}

// Replace the comments feeds with the message above.
add_action('do_feed_rss2_comments', 'bloglogistics_snippet_disable_comments_feed', 1);
add_action('do_feed_atom_comments', 'bloglogistics_snippet_disable_comments_feed', 1);

// Remove links to comments feed from the header.
remove_action('wp_head', 'feed_links_extra', 3);

// Optional: Remove the comments feed link from WP head for themes that add it outside the standard WP function
function bloglogistics_remove_comments_feed_link() {
    ob_start(function($output) {
        return preg_replace('/<link rel=[\'"]alternate[\'"] type=[\'"]application\/rss\+xml[\'"] title=[\'"].*Comments Feed[\'"] href=[\'"].*\/comments\/feed\/[\'"] \/>/', '', $output);
    });
}

add_action('wp_head', 'bloglogistics_remove_comments_feed_link', 1);
