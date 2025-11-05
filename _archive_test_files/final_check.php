<?php
require_once('wp-load.php');

echo "=== Current Data Check ===\n\n";

global $wpdb;

// Check players
$players = $wpdb->get_results("SELECT first_name, last_name, position, jersey_number FROM {$wpdb->prefix}bkgt_players");
echo "Players (" . count($players) . "):\n";
foreach ($players as $p) {
    echo "- {$p->first_name} {$p->last_name} (#{$p->jersey_number}) - {$p->position}\n";
}

// Check events
$events = $wpdb->get_results("SELECT title, event_type, event_date FROM {$wpdb->prefix}bkgt_events ORDER BY event_date");
echo "\nEvents (" . count($events) . "):\n";
foreach ($events as $e) {
    echo "- {$e->title} ({$e->event_type}) - " . date('Y-m-d H:i', strtotime($e->event_date)) . "\n";
}

// Check teams
$teams = $wpdb->get_results("SELECT name, category FROM {$wpdb->prefix}bkgt_teams");
echo "\nTeams (" . count($teams) . "):\n";
foreach ($teams as $t) {
    echo "- {$t->name} ({$t->category})\n";
}
?>