<?php
define('WP_USE_THEMES', false);
require_once('wp-load.php');

global $wpdb;
$teams = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bkgt_teams ORDER BY id');

echo 'Teams found: ' . count($teams) . "\n\n";
foreach($teams as $team) {
    echo 'ID: ' . $team->id . "\n";
    echo 'Name: ' . $team->name . "\n";
    echo 'Source ID: ' . ($team->source_id ?? 'NULL') . "\n";
    echo 'Created: ' . ($team->created_at ?? 'NULL') . "\n";
    echo "---\n";
}
?>