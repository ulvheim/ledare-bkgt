<?php
/**
 * Test plugin activation
 */

// Load WordPress environment
require_once 'wp-load.php';

// Check if user is logged in and has admin privileges
if (!current_user_can('manage_options')) {
    die('You do not have sufficient permissions to access this page.');
}

echo "<h1>Testing Plugin Activation</h1>";

try {
    // Simulate plugin activation
    echo "<h2>Testing Activation Hook...</h2>";

    // Include the plugin file
    require_once WP_PLUGIN_DIR . '/bkgt-data-scraping/bkgt-data-scraping.php';

    // Test the activation function
    if (function_exists('bkgt_data_scraping_activate')) {
        bkgt_data_scraping_activate();
        echo "<p style='color: green;'>✓ Activation hook executed successfully</p>";
    } else {
        throw new Exception('Activation function not found');
    }

    // Test the table creation function
    if (function_exists('bkgt_data_scraping_create_tables')) {
        bkgt_data_scraping_create_tables();
        echo "<p style='color: green;'>✓ Database tables created successfully</p>";
    } else {
        throw new Exception('Table creation function not found');
    }

    echo "<h2>Testing Plugin Initialization...</h2>";

    // Test the init function
    if (function_exists('bkgt_data_scraping_init')) {
        bkgt_data_scraping_init();
        echo "<p style='color: green;'>✓ Plugin initialized successfully</p>";
    } else {
        throw new Exception('Init function not found');
    }

    echo "<h2>Plugin Status: Ready for Activation</h2>";
    echo "<p style='color: green;'>✓ All activation steps completed successfully</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . esc_html($e->getMessage()) . "</p>";
    echo "<pre>" . esc_html($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><a href='" . admin_url('plugins.php') . "'>← Back to Plugins Page</a></p>";
?>