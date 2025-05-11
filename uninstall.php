<?php
/**
 * Uninstall script for QuizCourse One-Click Importer
 *
 * This file runs when the plugin is uninstalled via the WordPress admin panel.
 * It cleans up all plugin data from the database and removes any uploaded files.
 *
 * @package QuizCourse_Importer
 */

// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define plugin constants if not already defined
if (!defined('QCI_PLUGIN_DIR')) {
    define('QCI_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// Get the upload directory for our plugin
$upload_dir = wp_upload_dir();
$qci_upload_dir = $upload_dir['basedir'] . '/quizcourse-importer';

/**
 * Remove plugin options
 */
function qci_remove_options() {
    // Delete all options with qci_ prefix
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'qci_%'");

    // Delete specific options
    delete_option('quizcourse_importer_version');
    delete_option('quizcourse_importer_settings');
}

/**
 * Remove plugin logs
 */
function qci_remove_logs() {
    global $wpdb;
    
    // Check if log table exists
    $table_name = $wpdb->prefix . 'quizcourse_importer_logs';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}

/**
 * Remove uploaded files
 */
function qci_remove_files() {
    global $qci_upload_dir;
    
    // If upload directory exists
    if (is_dir($qci_upload_dir)) {
        // Get all files in directory
        $files = glob($qci_upload_dir . '/*');
        
        // Loop through files and delete them
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        
        // Remove directory
        @rmdir($qci_upload_dir);
    }
    
    // Also remove temp files
    $temp_dir = get_temp_dir() . 'quizcourse-importer';
    if (is_dir($temp_dir)) {
        // Get all files in temp directory
        $temp_files = glob($temp_dir . '/*');
        
        // Loop through files and delete them
        foreach ($temp_files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        
        // Remove temp directory
        @rmdir($temp_dir);
    }
}

/**
 * Remove transients
 */
function qci_remove_transients() {
    global $wpdb;
    
    // Delete all transients with qci_ prefix
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_qci_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_qci_%'");
}

/**
 * Remove user meta
 */
function qci_remove_user_meta() {
    global $wpdb;
    
    // Delete user meta with qci_ prefix
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'qci_%'");
}

/**
 * Check if this is a complete uninstall - user selected "Delete data" option
 */
function qci_is_complete_uninstall() {
    // Check for the delete_data option or implement your own logic
    return get_option('qci_delete_data_on_uninstall', false);
}

// Only perform complete cleanup if user has opted in
if (qci_is_complete_uninstall()) {
    // Run all cleanup functions
    qci_remove_options();
    qci_remove_logs();
    qci_remove_files();
    qci_remove_transients();
    qci_remove_user_meta();
    
    // Optional: Also remove any custom database tables if created
    // Note: If your plugin creates custom tables, they should be removed here
    /*
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}quizcourse_importer_custom_table");
    */
} else {
    // If not complete uninstall, just remove basic options and transients
    qci_remove_transients();
    
    // Remove version option
    delete_option('quizcourse_importer_version');
}

// Clear any cached data
wp_cache_flush();
