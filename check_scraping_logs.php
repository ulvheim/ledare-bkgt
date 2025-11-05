<?php
require_once 'wp-load.php';

global $wpdb;

// Check scraping logs for teams
$team_logs = $wpdb->get_results("
    SELECT scrape_type, status, records_processed, records_added, started_at, source_url
    FROM {$wpdb->prefix}bkgt_scraping_logs
    WHERE scrape_type = 'teams'
    ORDER BY started_at DESC
    LIMIT 10
");

echo "Recent team scraping logs:\n\n";

if (empty($team_logs)) {
    echo "No team scraping logs found.\n";
} else {
    foreach ($team_logs as $log) {
        echo "Date: {$log->started_at}\n";
        echo "Status: {$log->status}\n";
        echo "Records processed: {$log->records_processed}\n";
        echo "Records added: {$log->records_added}\n";
        echo "Source: {$log->source_url}\n";
        echo "---\n";
    }
}

// Check current team count
$team_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");
echo "\nCurrent team count: $team_count\n";

// Check for duplicates
$duplicates = $wpdb->get_var("
    SELECT COUNT(*) FROM (
        SELECT source_id, COUNT(*) as count
        FROM {$wpdb->prefix}bkgt_teams
        WHERE source_id IS NOT NULL AND source_id != ''
        GROUP BY source_id
        HAVING count > 1
    ) as dup
");

echo "Number of duplicate source_ids: $duplicates\n";