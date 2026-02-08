<?php
/**
 * Plugin Uninstall Handler
 *
 * Removes all persistent data created by the Coming Soon by Mudrava plugin
 * when it is deleted through the WordPress admin interface.
 *
 * @package Mudrava\ComingSoon
 * @since   1.0.0
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/* Remove plugin settings. */
delete_option('mudrava_coming_soon_options');

/* Remove the Coming Soon page and its ID reference. */
$page_id = get_option('mudrava_coming_soon_page_id');

if ($page_id) {
    wp_delete_post((int) $page_id, true);
    delete_option('mudrava_coming_soon_page_id');
}

/* Remove GitHub updater transient. */
delete_transient('mudrava_coming_soon_github_latest');
