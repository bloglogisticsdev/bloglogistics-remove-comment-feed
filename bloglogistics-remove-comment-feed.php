<?php
/**
 * Plugin Name:       BlogLogistics Remove Comment Feed
 * Plugin URI:        https://github.com/bloglogisticsdev/bloglogistics-remove-comment-feed
 * Description:       Removes comment feed links and blocks direct access to WordPress comment feed URLs while leaving normal post feeds available.
 * Version:           1.3.1
 * Requires at least: 7.0
 * Requires PHP:      8.3
 * Author:            BlogLogistics
 * Author URI:        https://www.bloglogistics.com/
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI:        https://github.com/bloglogisticsdev/bloglogistics-remove-comment-feed
 * Text Domain:       bloglogistics-remove-comment-feed
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BLOGLOGISTICS_RCF_VERSION', '1.3.1' );
define( 'BLOGLOGISTICS_RCF_SLUG', 'bloglogistics-remove-comment-feed' );
define( 'BLOGLOGISTICS_RCF_FILE', __FILE__ );
define( 'BLOGLOGISTICS_RCF_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLOGLOGISTICS_RCF_REPO_URL', 'https://github.com/bloglogisticsdev/bloglogistics-remove-comment-feed/' );
define( 'BLOGLOGISTICS_RCF_UPDATE_MANIFEST_URL', 'https://updates.bloglogistics.com/plugins/bloglogistics-remove-comment-feed.json' );

/**
 * Load bundled translation files.
 */
function bloglogistics_rcf_load_textdomain(): void {
	load_plugin_textdomain(
		'bloglogistics-remove-comment-feed',
		false,
		dirname( plugin_basename( BLOGLOGISTICS_RCF_FILE ) ) . '/languages'
	);
}
add_action( 'plugins_loaded', 'bloglogistics_rcf_load_textdomain' );

/**
 * Load Plugin Update Checker and configure the BlogLogistics manifest updater.
 */
function bloglogistics_rcf_load_updater(): void {
	$bloglogistics_rcf_puc = BLOGLOGISTICS_RCF_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

	if ( ! file_exists( $bloglogistics_rcf_puc ) ) {
		return;
	}

	if ( ! class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory', false ) ) {
		require_once $bloglogistics_rcf_puc;
	}

	require_once BLOGLOGISTICS_RCF_DIR . 'includes/class-bloglogistics-remove-comment-feed-updater.php';

	if ( class_exists( 'BlogLogistics_Remove_Comment_Feed_Updater', false ) ) {
		BlogLogistics_Remove_Comment_Feed_Updater::init(
			array(
				'repo_url'    => BLOGLOGISTICS_RCF_UPDATE_MANIFEST_URL,
				'plugin_file' => BLOGLOGISTICS_RCF_FILE,
				'slug'        => BLOGLOGISTICS_RCF_SLUG,
			)
		);
	}
}
bloglogistics_rcf_load_updater();

/**
 * Load and start the plugin.
 */
function bloglogistics_rcf_bootstrap(): void {
	require_once BLOGLOGISTICS_RCF_DIR . 'includes/class-bloglogistics-remove-comment-feed.php';

	if ( class_exists( 'BlogLogistics_Remove_Comment_Feed', false ) ) {
		new BlogLogistics_Remove_Comment_Feed();
	}
}
add_action( 'plugins_loaded', 'bloglogistics_rcf_bootstrap', 20 );
