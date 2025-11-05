<?php
/**
 * Test script to trigger BKGT scraper
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "BKGT Scraper Test\n";
echo "=================\n\n";

// Check if plugin is active
$plugin_path = 'bkgt-data-scraping/bkgt-data-scraping.php';
$active_plugins = get_option('active_plugins');

if (!in_array($plugin_path, $active_plugins)) {
    echo "❌ BKGT Data Scraping plugin is not active\n";
    exit(1);
}

echo "✓ Plugin is active\n";

// Initialize database and scraper
try {
    if (!class_exists('BKGT_Database')) {
        echo "❌ BKGT_Database class not found\n";
        exit(1);
    }

    if (!class_exists('BKGT_Scraper')) {
        echo "❌ BKGT_Scraper class not found\n";
        exit(1);
    }

    $db = new BKGT_Database();
    $scraper = new BKGT_Scraper($db);

    echo "✓ Classes initialized successfully\n";

    // Enable scraping for testing
    update_option('bkgt_scraping_enabled', 'yes');
    echo "✓ Scraping enabled for testing\n";

    // Check current data
    global $wpdb;
    $teams_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");
    $players_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players");

    echo "Current data:\n";
    echo "- Teams: $teams_count\n";
    echo "- Players: $players_count\n\n";

    // Test scraping using the daily scraping method
    echo "Testing scraping (teams, players, events)...\n";
    $scraper->run_daily_scraping();
    echo "✓ Scraping completed\n";

    // Check updated data
    $teams_count_after = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");
    $players_count_after = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players");

    echo "\nUpdated data:\n";
    echo "- Teams: $teams_count_after (+" . ($teams_count_after - $teams_count) . ")\n";
    echo "- Players: $players_count_after (+" . ($players_count_after - $players_count) . ")\n";

    // Check scraping logs
    $logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkgt_scraping_logs ORDER BY created_at DESC LIMIT 5");
    if (!empty($logs)) {
        echo "\nRecent scraping logs:\n";
        foreach ($logs as $log) {
            echo "- " . date('Y-m-d H:i:s', strtotime($log->created_at)) . ": {$log->action} - {$log->status}\n";
            if ($log->message) {
                echo "  Message: {$log->message}\n";
            }
        }
    }

    echo "\n✅ Scraping test completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>