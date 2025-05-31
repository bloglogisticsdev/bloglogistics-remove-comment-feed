# bloglogistics-remove-comment-feed
=== BlogLogistics LLM Generator === Contributors: rogerwheatley
Tags: comments, feed, rss, wordpress
Requires at least: 6.8
Tested up to: 6.8.1
Stable tag: 1.0.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A simple plugin to remove the comments feed from WordPress sites. No settings needed, just activate and go.

== Description ==

Provides a simple way to remove the comments feed from a WordPress site, is there's no need to subscribe to the post commenting feed.

The plugin simply disables the comments feed.

Key Features:

Disables adding the comments feed to the site header tags.

== Installation ==

Upload the plugin files to the /wp-content/plugins/bloglogistics-remove-comment-feed directory, or install via the WordPress Plugins screen directly.
Activate the plugin through the 'Plugins' screen in WordPress.

== Usage ==

Once activated, the plugin will remove the ability to subscribe to comment feeds. The initial commit has no options, however subsequent updates may include the option to toggle the comment feed on/off.

 == Frequently Asked Questions ==

= Does this plugin have any settings? =
 No. The plugin works automatically upon activation. There is no settings page or configuration required. Setting will be added in later updates.
 
 = What exactly does this plugin do? =
 It disables and removes access to the WordPress comments RSS feed. Instead of the comments feed, users will see a message directing them to the homepage.

 = Will this affect my main post feed? =
 No. This plugin only targets the comments RSS feeds (`/comments/feed/`) and does not interfere with the main post or category feeds.

 = Is it safe to use with any theme? =
 Yes. The plugin is lightweight and only disables specific comment feed actions and filters. It should work with any well-coded theme.

 = How can I undo the changes made by this plugin? =
 Simply deactivate the plugin. All comment feed functionality and links will be restored.

 = Can I customise the message shown to visitors? =
 Yes. You can edit the `wp_die()` message inside the `bloglogistics_snippet_disable_comments_feed()` function to display a custom message.

== Changelog ==


== Upgrade Notice ==

= 1.0.4 = First public release. Disables the WP comment feed.

== License ==

This plugin is licensed under the GPLv3 or later.
