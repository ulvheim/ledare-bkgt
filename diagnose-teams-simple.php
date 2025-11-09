<?php
/**
 * Simple Team Data Diagnostic Script for Remote Execution
 */

require_once('wp-load.php');

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

global $wpdb;

echo "=== BKGT Team Data Diagnosis ===\n\n";

try {
    // Get all teams
    $teams = $wpdb->get_results("SELECT id, name, source_id, source_url, created_date FROM {$wpdb->prefix}bkgt_teams ORDER BY name");

    echo "Total teams in database: " . count($teams) . "\n\n";

    $valid_teams = array();
    $invalid_teams = array();
    $issues_summary = array();

    foreach ($teams as $team) {
        $issues = validate_team($team);

        if (empty($issues)) {
            $valid_teams[] = $team;
        } else {
            $invalid_teams[] = array('team' => $team, 'issues' => $issues);
            foreach ($issues as $issue) {
                if (!isset($issues_summary[$issue])) {
                    $issues_summary[$issue] = 0;
                }
                $issues_summary[$issue]++;
            }
        }
    }

    echo "Valid teams (" . count($valid_teams) . "):\n";
    foreach ($valid_teams as $team) {
        echo "  ✅ {$team->name} (ID: {$team->source_id})\n";
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
        echo "\nDuplicate source_ids:\n";
        foreach ($duplicates as $dup) {
            echo "  ⚠️  $dup\n";
        }
    }

    // Check for old teams
    $current_year = (int)date('Y');
    $old_teams = array_filter($valid_teams, function($team) use ($current_year) {
        if (preg_match('/P(\d{4})/', $team->source_id, $matches)) {
            $year = (int)$matches[1];
            return $year < ($current_year - 10);
        }
        return false;
    });

    if (!empty($old_teams)) {
        echo "\nOld teams (older than 10 years):\n";
        foreach ($old_teams as $team) {
            echo "  🕰️  {$team->name} ({$team->source_id})\n";
        }
    }

    // Summary
    echo "\n=== Summary ===\n";
    echo "Total teams: " . count($teams) . "\n";
    echo "Valid teams: " . count($valid_teams) . "\n";
    echo "Invalid teams: " . count($invalid_teams) . "\n";
    echo "Duplicates: " . count($duplicates) . "\n";
    echo "Old teams: " . count($old_teams) . "\n";

    if (count($valid_teams) > 8) {
        echo "\n⚠️  WARNING: More than 8 valid teams found. User reported only 8 teams exist on svenskalag.se\n";
        echo "This suggests the scraper may be finding inactive/old teams.\n";
    }

    echo "\nIssues breakdown:\n";
    foreach ($issues_summary as $issue => $count) {
        echo "  $issue: $count teams\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

function validate_team($team) {
    $issues = array();

    // Check source_id format
    if (empty($team->source_id)) {
        $issues[] = "Missing source_id";
    } elseif (!preg_match('/^P\d{4}$/', $team->source_id)) {
        $issues[] = "Invalid source_id format (should be P####)";
    }

    // Check source_url
    if (empty($team->source_url)) {
        $issues[] = "Missing source_url";
    } elseif (stripos($team->source_url, 'svenskalag.se') === false) {
        $issues[] = "Source URL not from svenskalag.se";
    } else {
        // Check if URL contains the team code
        $expected_path = '/bkgt-' . strtolower($team->source_id);
        if (stripos($team->source_url, $expected_path) === false) {
            $issues[] = "URL doesn't contain expected team path";
        }
    }

    return $issues;
}
?>