=== BlogLogistics Remove Comment Feed ===
Contributors: bloglogistics
Tags: comments, feeds, rss, atom, privacy
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.3
Stable tag: 1.3.1
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

== BlogLogistics Service Usage Notice ==

This plugin is licensed under GPL-3.0-or-later.

This plugin is provided by BlogLogistics as part of an active hosting, maintenance, or site-management service, unless a separate service arrangement has been granted. If the website is moved to another provider, continued BlogLogistics service use, support, updates, configuration assistance, or replacement work may require a separate agreement.

This notice does not restrict any rights granted under the GPL-3.0-or-later licence.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install the ZIP through WordPress.
2. Activate the plugin.
3. Go to BlogLogistics > Remove Comment Feed.
4. Confirm that comment feeds are disabled and customize the blocked-feed message if needed.

== Frequently Asked Questions ==

= Does this disable normal WordPress feeds? =
No. This plugin blocks comment feeds only. Normal post, category, tag, and author feeds remain available.

= What happens when someone visits a comment feed URL directly? =
They see the blocked-feed message configured under BlogLogistics > Remove Comment Feed.

= Can I customize the blocked-feed message? =
Yes. Go to BlogLogistics > Remove Comment Feed and edit the message shown when someone opens a blocked comment feed URL.

= Does this plugin create public pages or replacement links? =
No. It removes comment feed links and blocks comment feed URLs. It does not create public pages or add replacement links.

= Does this plugin use the BlogLogistics update system? =
Yes. Updates are served through the BlogLogistics manifest-based update system.

= Does this plugin continue to be covered by BlogLogistics service terms if the website moves to another provider? =

This plugin is licensed under GPL-3.0-or-later. BlogLogistics service use, support, updates, configuration assistance, or replacement work may require an active BlogLogistics hosting, maintenance, or site-management service, or a separate agreement. This notice does not restrict any rights granted under the GPL-3.0-or-later licence.

== Changelog ==

= 1.3.0 =
* Refactor the main plugin file into a bootstrap loader.
* Move the main plugin class into the includes directory.
* Add translation support and bundled language files.
* Add language files for English Australia, English Great Britain, French, German, Spanish, Norwegian Bokmål, Swedish, and Japanese.
* Add Domain Path metadata for bundled language files.
* Add uninstall cleanup for this plugin’s saved settings.
* Preserve update metadata, including icons, banners, Installation, FAQ, Author, and changelog support.

= 1.2.5 =
* Add Installation and FAQ metadata to the plugin details modal.
* Add linked BlogLogistics author metadata to the generated update manifest.

= 1.2.4 =
* Add BlogLogistics plugin banner assets.
* Add banner metadata to the generated update manifest.

= 1.2.3 =
* Add BlogLogistics plugin icon assets.
* Add icon metadata to the generated update manifest.

= 1.2.2 =
* Move settings from Settings > Remove Comment Feed to BlogLogistics > Remove Comment Feed.
* Add the shared BlogLogistics wp-admin parent menu with the RSS-style icon.

= 1.2.1 =
* Add automated release ZIP and update manifest workflow.

= 1.2.0 =
* Modernize plugin for the BlogLogistics plugin system.
* Add the BlogLogistics manifest-based updater.
* Add automated GitHub Actions release ZIP and manifest upload workflow.
* Add a clear wp-admin settings page under BlogLogistics > Remove Comment Feed.
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
