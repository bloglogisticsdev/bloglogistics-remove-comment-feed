=== BlogLogistics Remove Comment Feed ===
Contributors: bloglogistics
Tags: comments, feeds, rss, atom, privacy
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.3
Stable tag: 1.2.0
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Removes comment feed links and blocks direct access to WordPress comment feed URLs while leaving normal post feeds available.

== Description ==

BlogLogistics Remove Comment Feed disables WordPress comment RSS/Atom feeds without disabling normal post feeds.

The plugin removes comment feed links from the site HTML head, blocks direct access to comment feed URLs such as `/comments/feed/`, and lets administrators customize the message shown to visitors who manually open a blocked comment feed URL.

The plugin does not create public pages and does not add replacement links to the site.

== Features ==

* Disable comment RSS/Atom feeds.
* Remove comment feed links from the site HTML head.
* Leave normal post, category, tag, and author feeds available.
* Customize the message shown when someone visits a blocked comment feed URL.
* Reset the blocked-feed message to the default.
* Uses the BlogLogistics manifest update system.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install the ZIP through WordPress.
2. Activate the plugin.
3. Go to Settings > Remove Comment Feed.
4. Confirm that comment feeds are disabled and customize the blocked-feed message if needed.

== Changelog ==

= 1.2.0 =
* Modernize plugin for the BlogLogistics plugin system.
* Add the BlogLogistics manifest-based updater.
* Add automated GitHub Actions release ZIP and manifest upload workflow.
* Add a clear wp-admin settings page under Settings > Remove Comment Feed.
* Add a customizable message for blocked comment feed URLs.
* Add a reset button for restoring the default blocked-feed message.
* Remove comment feed links from wp_head without disabling normal post feeds.
* Block direct comment feed requests while leaving normal feeds available.
* Update requirements to WordPress 7.0 and PHP 8.3.
* Standardize license metadata to GPL-3.0-or-later.

= 1.1.0 =
* Added a settings page.
* Improved feed-link removal.
* Refactored to object-oriented code.
* Improved escaping and security checks.

= 1.0.0 =
* Initial release.
