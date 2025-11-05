<?php
// Verify Plugin Activation
define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "Verifying BKGT Data Scraping Plugin Status\n";
echo "=========================================\n\n";

$plugin_path = 'bkgt-data-scraping/bkgt-data-scraping.php';

// Check if plugin is active
$active_plugins = get_option('active_plugins');
if (in_array($plugin_path, $active_plugins)) {
    echo "✓ Plugin is active in WordPress\n";
} else {
    echo "✗ Plugin is not active\n";
}

// Check if admin menu was added
if (function_exists('add_menu_page')) {
    echo "✓ WordPress admin functions available\n";
} else {
    echo "✗ WordPress admin functions not available\n";
}

// Check if our plugin functions are available
if (function_exists('bkgt_data_scraping_init')) {
    echo "✓ Plugin initialization function available\n";
} else {
    echo "✗ Plugin initialization function not found\n";
}

// Test database connection
global $wpdb;
try {
    $test = $wpdb->get_var("SELECT 1");
    if ($test === "1") {
        echo "✓ Database connection working\n";
    } else {
        echo "✗ Database connection issue\n";
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

echo "\nVerification completed.\n";
?>