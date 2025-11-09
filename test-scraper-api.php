<?php
/**
 * Test Scraper via API
 */

require_once('wp-load.php');

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

// Simulate admin user
wp_set_current_user(1);

// Test the scraper endpoint
if (function_exists('bkgt_api_scrape_teams')) {
    echo "Calling bkgt_api_scrape_teams()...\n";

    try {
        $result = bkgt_api_scrape_teams();
        echo "Result: " . json_encode($result) . "\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "bkgt_api_scrape_teams function not found\n";
}

// Check teams after
global $wpdb;
$table_name = $wpdb->prefix . 'bkgt_teams';
$teams = $wpdb->get_results("SELECT COUNT(*) as count FROM $table_name");
echo "\nTeams in database after: " . $teams[0]->count . "\n";
?>