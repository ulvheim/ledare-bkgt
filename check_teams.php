<?php
require_once 'wp-load.php';

global $wpdb;

$teams = $wpdb->get_results("SELECT id, name, source_id, category, source_url FROM {$wpdb->prefix}bkgt_teams ORDER BY name");

echo "Current teams in database (" . count($teams) . " total):\n\n";

foreach ($teams as $team) {
    echo "{$team->id}: {$team->name} ({$team->source_id}) - {$team->category} - {$team->source_url}\n";
}

echo "\nTeam count by category:\n";
$categories = $wpdb->get_results("SELECT category, COUNT(*) as count FROM {$wpdb->prefix}bkgt_teams GROUP BY category ORDER BY category");
foreach ($categories as $cat) {
    echo "{$cat->category}: {$cat->count}\n";
}