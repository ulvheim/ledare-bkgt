<?php
/**
 * Team Database Diagnostic Script
 *
 * Analyzes current teams in database and validates against svenskalag.se format
 */

require_once('wp-load.php');

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

global $wpdb;

echo "=== BKGT Teams Database Diagnostic ===\n\n";

try {
    // Get all teams
    $teams = $wpdb->get_results("SELECT id, name, source_id, source_url, created_date FROM {$wpdb->prefix}bkgt_teams ORDER BY name");

    echo "Total teams in database: " . count($teams) . "\n\n";

    $valid_teams = array();
    $invalid_teams = array();

    foreach ($teams as $team) {
        $is_valid = true;
        $issues = array();

        // Check source_id format
        if (empty($team->source_id)) {
            $is_valid = false;
            $issues[] = "Missing source_id";
        } elseif (!preg_match('/^P\d{4}$/', $team->source_id)) {
            $is_valid = false;
            $issues[] = "Invalid source_id format (should be P####)";
        }

        // Check source_url
        if (empty($team->source_url)) {
            $is_valid = false;
            $issues[] = "Missing source_url";
        } elseif (stripos($team->source_url, 'svenskalag.se') === false) {
            $is_valid = false;
            $issues[] = "Source URL not from svenskalag.se";
        }

        if ($is_valid) {
            $valid_teams[] = $team;
        } else {
            $invalid_teams[] = array('team' => $team, 'issues' => $issues);
        }
    }

    echo "Valid teams (" . count($valid_teams) . "):\n";
    foreach ($valid_teams as $team) {
        echo "  ✅ {$team->name} (ID: {$team->source_id}) - {$team->source_url}\n";
    }

    echo "\nInvalid teams (" . count($invalid_teams) . "):\n";
    foreach ($invalid_teams as $team) {
        echo "  ❌ {$team['team']->name} (ID: {$team['team']->source_id})\n";
        foreach ($team['issues'] as $issue) {
            echo "    - $issue\n";
        }
    }

    // Check for duplicates
    $source_ids = array_column($valid_teams, 'source_id');
    $duplicates = array_diff_assoc($source_ids, array_unique($source_ids));

    if (!empty($duplicates)) {
        echo "\nDuplicate source_ids found:\n";
        foreach ($duplicates as $dup) {
            echo "  ⚠️  $dup\n";
        }
    }

    // Summary
    echo "\n=== Summary ===\n";
    echo "Total teams: " . count($teams) . "\n";
    echo "Valid teams: " . count($valid_teams) . "\n";
    echo "Invalid teams: " . count($invalid_teams) . "\n";
    echo "Duplicates: " . count($duplicates) . "\n";

    if (count($valid_teams) > 8) {
        echo "\n⚠️  WARNING: More than 8 valid teams found. User reported only 8 teams exist on svenskalag.se\n";
        echo "This suggests the scraper may be finding inactive/old teams or parsing incorrectly.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>