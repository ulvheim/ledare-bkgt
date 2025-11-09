<?php
/**
 * Clean Up Teams Data - Remove old and invalid teams
 */

require_once('wp-load.php');

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

global $wpdb;

echo "=== BKGT Teams Cleanup ===\n\n";

try {
    $table_name = $wpdb->prefix . 'bkgt_teams';

    $before_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "Teams before cleanup: $before_count\n\n";

    // Get current year
    $current_year = (int)date('Y');
    echo "Current year: $current_year\n\n";

    // Remove old P#### teams (older than current year)
    $old_p_teams = $wpdb->get_results($wpdb->prepare("
        SELECT id, name FROM $table_name
        WHERE name REGEXP '^P[0-9]{4}$'
        AND CAST(SUBSTRING(name, 2) AS UNSIGNED) < %d
    ", $current_year));

    if (!empty($old_p_teams)) {
        echo "Removing old P#### teams:\n";
        foreach ($old_p_teams as $team) {
            echo "  🗑️  {$team->name} (too old)\n";
            $wpdb->delete($table_name, array('id' => $team->id));
        }
        echo "\n";
    }

    // Remove non-P#### teams that don't have svenskalag_id (not from scraper)
    $non_p_teams = $wpdb->get_results("
        SELECT id, name, svenskalag_id FROM $table_name
        WHERE name NOT REGEXP '^P[0-9]{4}$'
        AND (svenskalag_id IS NULL OR svenskalag_id = '')
    ");

    if (!empty($non_p_teams)) {
        echo "Removing non-P#### teams without svenskalag_id (not from scraper):\n";
        foreach ($non_p_teams as $team) {
            echo "  🗑️  {$team->name} (not from svenskalag.se)\n";
            $wpdb->delete($table_name, array('id' => $team->id));
        }
        echo "\n";
    }

    $after_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    echo "Cleanup completed!\n";
    echo "Removed old P#### teams: " . count($old_p_teams) . "\n";
    echo "Removed non-scraped teams: " . count($non_p_teams) . "\n";
    echo "Teams after cleanup: $after_count\n\n";

    // Show remaining teams
    if ($after_count > 0) {
        $remaining = $wpdb->get_results("SELECT id, name, svenskalag_id FROM $table_name ORDER BY name");
        echo "Remaining teams:\n";
        foreach ($remaining as $team) {
            echo "  ✅ {$team->name} (ID: {$team->svenskalag_id})\n";
        }
    }

    if ($after_count > 8) {
        echo "\n⚠️  WARNING: Still more than 8 teams remaining. Manual review may be needed.\n";
    } elseif ($after_count == 8) {
        echo "\n✅ Perfect! Exactly 8 teams remaining, matching svenskalag.se\n";
    } else {
        echo "\n⚠️  WARNING: Only $after_count teams remaining. Expected 8.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>