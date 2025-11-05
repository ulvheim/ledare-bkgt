<?php
define('WP_USE_THEMES', false);
require_once('wp-load.php');

global $wpdb;

// Check team ID 1
$result = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'bkgt_teams WHERE id = 1');
if($result) {
    echo 'Team ID 1 exists: ' . $result->name . "\n";
} else {
    echo "Team ID 1 does not exist\n";
}

// Check recent logs
$logs = $wpdb->get_results('SELECT action, status, message FROM ' . $wpdb->prefix . 'bkgt_scraping_logs ORDER BY id DESC LIMIT 10');
echo "\nRecent logs:\n";
if (empty($logs)) {
    echo "No logs found\n";
} else {
    foreach($logs as $log) {
        echo $log->action . ' - ' . $log->status . ': ' . substr($log->message, 0, 100) . "...\n";
    }
}
?>