<?php
require_once('wp-load.php');

echo "=== Creating Sample Data ===\n\n";

// Include the plugin functions
require_once('wp-content/plugins/bkgt-data-scraping/bkgt-data-scraping.php');

echo "Calling bkgt_create_sample_data()...\n";
try {
    bkgt_create_sample_data();
    echo "SUCCESS: Sample data created!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Verifying Data ===\n";

global $wpdb;

// Check players
$players_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players");
echo "Players: $players_count\n";

// Check events
$events_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_events");
echo "Events: $events_count\n";

// Check teams
$teams_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");
echo "Teams: $teams_count\n";

echo "\nDone!\n";
?>