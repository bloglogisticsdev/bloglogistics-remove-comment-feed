<?php
/**
 * Uninstall cleanup for BlogLogistics Remove Comment Feed.
 *
 * @package BlogLogistics_Remove_Comment_Feed
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'bloglogistics_rcf_options' );
