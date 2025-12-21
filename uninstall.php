<?php
/**
 * Uninstall Script
 * 
 * Fired when the plugin is uninstalled.
 * Cleans up all plugin data from the database and file system.
 * 
 * @package WP_News_Audio_Pro
 * @since 1.0.0
 */

// Exit if accessed directly or not uninstalling
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Delete all plugin options
delete_option('wnap_settings');
delete_option('wnap_license');
delete_option('wnap_version');

// Delete transients
delete_transient('wnap_license_check');

// Delete all post meta
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_wnap_%'");

// Delete audio files
$upload_dir = wp_upload_dir();
$audio_dir = $upload_dir['basedir'] . '/news-audio-pro/';

if (is_dir($audio_dir)) {
    // Recursively delete directory and contents
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($audio_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }
    
    rmdir($audio_dir);
}

// Clear scheduled cron jobs
wp_clear_scheduled_hook('wnap_cleanup_old_audio');
wp_clear_scheduled_hook('wnap_license_check');

// Clear any cached data
wp_cache_flush();
