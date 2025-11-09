<?php
/**
 * SWE3 Plugin Activation Script
 * Run this script to activate the SWE3 scraper plugin
 */

// Bootstrap WordPress
require_once dirname(__FILE__) . '/wp-load.php';

if (!function_exists('wp_get_current_user')) {
    die('WordPress not loaded properly');
}

echo "Activating BKGT SWE3 Scraper Plugin...\n";

// Check if plugin exists
$plugin_file = 'bkgt-swe3-scraper/bkgt-swe3-scraper.php';
$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;

if (!file_exists($plugin_path)) {
    die("Plugin file not found: $plugin_path\n");
}

echo "Plugin file found: $plugin_path\n";

// Get current active plugins
$active_plugins = get_option('active_plugins', array());

if (!is_array($active_plugins)) {
    $active_plugins = array();
}

// Check if already active
if (in_array($plugin_file, $active_plugins)) {
    echo "Plugin is already active.\n";
} else {
    // Add to active plugins
    $active_plugins[] = $plugin_file;
    update_option('active_plugins', $active_plugins);
    echo "Plugin activated successfully.\n";
}

// Run activation hook
if (file_exists($plugin_path)) {
    include_once $plugin_path;

    // Run activation function if it exists
    $activation_function = 'bkgt_swe3_scraper_activation';
    if (function_exists($activation_function)) {
        call_user_func($activation_function);
        echo "Plugin activation hook executed.\n";
    } else {
        echo "No activation hook found.\n";
    }
}

// Test plugin loading
if (function_exists('bkgt_swe3_scraper')) {
    $plugin_instance = bkgt_swe3_scraper();
    echo "Plugin loaded successfully.\n";

    // Test database table creation
    global $wpdb;
    $table_name = $wpdb->prefix . 'bkgt_swe3_documents';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
        echo "Database table created successfully.\n";
    } else {
        echo "Database table not found.\n";
    }
} else {
    echo "Plugin failed to load.\n";
}

echo "\nActivation complete!\n";
echo "You can now access the plugin at: WordPress Admin > Tools > SWE3 Scraper\n";