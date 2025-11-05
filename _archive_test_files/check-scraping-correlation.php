<?php
define('WP_USE_THEMES', false);
require_once('wp-load.php');

global $wpdb;

// Get successful team scraping logs
$logs = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bkgt_scraping_logs WHERE scrape_type = "teams" AND status = "completed" ORDER BY id DESC');

echo 'Successful team scraping logs:' . "\n";
foreach($logs as $log) {
    echo 'Log ID: ' . $log->id . ' - Started: ' . $log->started_at . ' - Completed: ' . $log->completed_at . ' - Records added: ' . $log->records_added . "\n";
}

// Get teams created around the scraping time
echo "\nTeams created after 2025-10-29 20:00:00:\n";
$teams = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bkgt_teams WHERE created_at > "2025-10-29 20:00:00" ORDER BY created_at DESC');

foreach($teams as $team) {
    echo 'Team ID: ' . $team->id . ' - Name: ' . $team->name . ' - Created: ' . $team->created_at . "\n";
}

// Check if there are any teams with source_id set (indicating they were scraped)
echo "\nTeams with source_id (scraped teams):\n";
$scraped_teams = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bkgt_teams WHERE source_id IS NOT NULL');

if (empty($scraped_teams)) {
    echo "No teams have source_id set - none were actually scraped from external source\n";
} else {
    foreach($scraped_teams as $team) {
        echo 'Team ID: ' . $team->id . ' - Name: ' . $team->name . ' - Source ID: ' . $team->source_id . "\n";
    }
}
?>