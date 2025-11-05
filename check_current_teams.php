<?php
require_once 'wp-load.php';

global $wpdb;

// Get all teams
$teams = $wpdb->get_results("SELECT id, name, source_id, category, source_url FROM {$wpdb->prefix}bkgt_teams ORDER BY name");

echo "Current teams in database (" . count($teams) . " total):\n\n";

foreach ($teams as $team) {
    $source_info = !empty($team->source_id) ? " (Source: {$team->source_id})" : " (No source ID)";
    echo "{$team->id}: {$team->name}{$source_info} - {$team->category}\n";
    if (!empty($team->source_url)) {
        echo "    URL: {$team->source_url}\n";
    }
    echo "\n";
}

// Check for potential fake teams
echo "Potential fake/synthetic teams detected:\n";
$fake_count = 0;
foreach ($teams as $team) {
    $is_fake = false;
    $reason = "";

    if (empty($team->source_id) || empty($team->source_url)) {
        $is_fake = true;
        $reason = "Missing source_id or source_url";
    } elseif (!preg_match('/^P\d{4}$/', $team->source_id)) {
        $is_fake = true;
        $reason = "Invalid source_id format (should be P2013, etc.)";
    } elseif (stripos($team->source_url, 'svenskalag.se') === false) {
        $is_fake = true;
        $reason = "Source URL not from svenskalag.se";
    }

    if ($is_fake) {
        echo "- {$team->name} (ID: {$team->id}): $reason\n";
        $fake_count++;
    }
}

if ($fake_count === 0) {
    echo "No fake teams detected.\n";
} else {
    echo "\nTotal fake teams: $fake_count\n";
    echo "Run cleanup_teams.php to remove them.\n";
}