<?php
// Activate BKGT Data Scraping Plugin
define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "Activating BKGT Data Scraping Plugin...\n";
echo "======================================\n\n";

$plugin_path = 'bkgt-data-scraping/bkgt-data-scraping.php';

// Check if plugin is already active
if (is_plugin_active($plugin_path)) {
    echo "✓ Plugin is already active!\n";
    exit(0);
}

// Attempt to activate the plugin
$result = activate_plugin($plugin_path);

if (is_wp_error($result)) {
    echo "✗ Plugin activation failed:\n";
    echo "Error: " . $result->get_error_message() . "\n";
    exit(1);
}

if (is_plugin_active($plugin_path)) {
    echo "✓ Plugin activated successfully!\n";

    // Test that the plugin is working
    echo "\nTesting plugin functionality...\n";

    // Check if database tables were created
    global $wpdb;
    $tables = [
        $wpdb->prefix . 'bkgt_players',
        $wpdb->prefix . 'bkgt_teams',
        $wpdb->prefix . 'bkgt_events',
        $wpdb->prefix . 'bkgt_scraping_log'
    ];

    foreach ($tables as $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if ($exists) {
            echo "✓ Table $table exists\n";
        } else {
            echo "✗ Table $table not found\n";
        }
    }

    echo "\nPlugin activation completed successfully!\n";
} else {
    echo "✗ Plugin activation failed (unknown reason)\n";
    exit(1);
}
?>