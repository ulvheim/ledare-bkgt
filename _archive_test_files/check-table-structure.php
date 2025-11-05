<?php
define('WP_USE_THEMES', false);
require_once('wp-load.php');

global $wpdb;

// Check scraping logs table structure
$columns = $wpdb->get_results('DESCRIBE ' . $wpdb->prefix . 'bkgt_scraping_logs');
echo 'Scraping logs table columns:' . "\n";
foreach($columns as $col) {
    echo '- ' . $col->Field . ' (' . $col->Type . ')' . "\n";
}

// Check if there are any logs
$count = $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'bkgt_scraping_logs');
echo "\nTotal logs: $count\n";

// Check team scraping logs
$team_logs = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bkgt_scraping_logs WHERE scrape_type = "teams" ORDER BY id DESC LIMIT 5');
echo "\nTeam scraping logs:\n";
foreach($team_logs as $log) {
    echo 'ID: ' . $log->id . ' - Status: ' . $log->status . ' - Records: ' . $log->records_added . ' added, ' . $log->records_processed . ' processed - URL: ' . $log->source_url . "\n";
}
?>