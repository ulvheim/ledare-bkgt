<?php
require_once('wp-load.php');

echo "=== BKGT Data Check ===\n\n";

global $wpdb;

// Check players table
try {
    $players_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players");
    echo "Players in database: $players_count\n";

    if ($players_count > 0) {
        $players = $wpdb->get_results("SELECT name, position FROM {$wpdb->prefix}bkgt_players LIMIT 5");
        echo "Sample players:\n";
        foreach ($players as $player) {
            echo "- {$player->name} ({$player->position})\n";
        }
    }
} catch (Exception $e) {
    echo "Error checking players: " . $e->getMessage() . "\n";
}

// Check events table
try {
    $events_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_events");
    echo "\nEvents in database: $events_count\n";

    if ($events_count > 0) {
        $events = $wpdb->get_results("SELECT title, event_date FROM {$wpdb->prefix}bkgt_events ORDER BY event_date DESC LIMIT 5");
        echo "Sample events:\n";
        foreach ($events as $event) {
            echo "- {$event->title} (" . date('Y-m-d', strtotime($event->event_date)) . ")\n";
        }
    }
} catch (Exception $e) {
    echo "Error checking events: " . $e->getMessage() . "\n";
}

// Check teams table
try {
    $teams_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");
    echo "\nTeams in database: $teams_count\n";

    if ($teams_count > 0) {
        $teams = $wpdb->get_results("SELECT name, category FROM {$wpdb->prefix}bkgt_teams LIMIT 5");
        echo "Sample teams:\n";
        foreach ($teams as $team) {
            echo "- {$team->name} ({$team->category})\n";
        }
    }
} catch (Exception $e) {
    echo "Error checking teams: " . $e->getMessage() . "\n";
}

echo "\n=== Shortcode Test ===\n";

// Test if shortcodes are registered
global $shortcode_tags;
$bkgt_shortcodes = array_filter(array_keys($shortcode_tags), function($key) {
    return strpos($key, 'bkgt_') === 0;
});

echo "BKGT shortcodes registered: " . count($bkgt_shortcodes) . "\n";
if (!empty($bkgt_shortcodes)) {
    echo "Available shortcodes:\n";
    foreach ($bkgt_shortcodes as $shortcode) {
        echo "- [$shortcode]\n";
    }
}
?>