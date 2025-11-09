<?php
/**
 * Check All Teams Data
 */

require_once('wp-load.php');

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

global $wpdb;

echo "=== All BKGT Teams Data ===\n\n";

try {
    $table_name = $wpdb->prefix . 'bkgt_teams';

    // Get all teams
    $teams = $wpdb->get_results("SELECT * FROM $table_name ORDER BY name");

    echo "All teams in database (" . count($teams) . "):\n";
    foreach ($teams as $team) {
        echo "  ID: {$team->id}, Name: {$team->name}, Svenskalag_ID: " . ($team->svenskalag_id ?: 'NULL') . ", Status: {$team->status}, Category: {$team->category}\n";
    }

    echo "\n=== Analysis ===\n";

    // Count by category
    $categories = array();
    foreach ($teams as $team) {
        $cat = $team->category ?: 'Unknown';
        if (!isset($categories[$cat])) {
            $categories[$cat] = 0;
        }
        $categories[$cat]++;
    }

    echo "Teams by category:\n";
    foreach ($categories as $cat => $count) {
        echo "  $cat: $count teams\n";
    }

    // Check for P#### pattern
    $p_teams = array_filter($teams, function($team) {
        return preg_match('/^P\d{4}$/', $team->name);
    });

    echo "\nTeams matching P#### pattern: " . count($p_teams) . "\n";
    if (count($p_teams) > 0) {
        $years = array_map(function($team) {
            return substr($team->name, 1);
        }, $p_teams);
        sort($years);
        echo "Years found: " . implode(', ', $years) . "\n";
    }

    // Check current year
    $current_year = (int)date('Y');
    echo "\nCurrent year: $current_year\n";

    if (count($p_teams) > 8) {
        echo "\n⚠️  WARNING: " . count($p_teams) . " teams found, but user reported only 8 teams exist on svenskalag.se\n";
        echo "This suggests the scraper is finding old/inactive teams.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>