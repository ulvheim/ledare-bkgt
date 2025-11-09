<?php
/**
 * Comprehensive Team Data Management Script
 *
 * Diagnoses team data issues and provides cleanup/repair functionality
 */

require_once('wp-load.php');

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

// Check if user has admin privileges
if (!current_user_can('manage_options')) {
    die('Admin privileges required');
}

global $wpdb;

$action = isset($_GET['action']) ? $_GET['action'] : 'diagnose';

echo "=== BKGT Team Data Management ===\n\n";

try {
    switch ($action) {
        case 'diagnose':
            diagnose_teams();
            break;

        case 'cleanup':
            cleanup_teams();
            break;

        case 'clear_repopulate':
            clear_and_repopulate_teams();
            break;

        default:
            echo "Available actions:\n";
            echo "  ?action=diagnose - Analyze current team data\n";
            echo "  ?action=cleanup - Remove invalid teams\n";
            echo "  ?action=clear_repopulate - Clear all and repopulate from svenskalag.se\n";
            break;
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

function diagnose_teams() {
    global $wpdb;

    echo "=== Team Data Diagnosis ===\n\n";

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

function cleanup_teams() {
    global $wpdb;

    echo "=== Team Data Cleanup ===\n\n";

    $before_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");
    echo "Teams before cleanup: $before_count\n";

    // Remove invalid teams
    $teams = $wpdb->get_results("SELECT id, name, source_id, source_url FROM {$wpdb->prefix}bkgt_teams");

    $removed_invalid = 0;
    foreach ($teams as $team) {
        $issues = validate_team($team);
        if (!empty($issues)) {
            $wpdb->delete($wpdb->prefix . 'bkgt_teams', array('id' => $team->id));
            $removed_invalid++;
        }
    }

    // Remove duplicates
    $duplicates = $wpdb->get_results("
        SELECT source_id, COUNT(*) as count, GROUP_CONCAT(id) as ids
        FROM {$wpdb->prefix}bkgt_teams
        WHERE source_id IS NOT NULL AND source_id != ''
        GROUP BY source_id
        HAVING count > 1
    ");

    $removed_duplicates = 0;
    foreach ($duplicates as $dup) {
        $ids = explode(',', $dup->ids);
        // Keep the first ID, delete the rest
        $keep_id = array_shift($ids);
        $delete_ids = implode(',', $ids);

        if (!empty($delete_ids)) {
            $wpdb->query("DELETE FROM {$wpdb->prefix}bkgt_teams WHERE id IN ($delete_ids)");
            $removed_duplicates += count($ids);
        }
    }

    // Remove old teams
    $current_year = (int)date('Y');
    $old_teams_result = $wpdb->query($wpdb->prepare("
        DELETE FROM {$wpdb->prefix}bkgt_teams
        WHERE source_id REGEXP '^P[0-9]{4}$'
        AND CAST(SUBSTRING(source_id, 2) AS UNSIGNED) < %d
    ", $current_year - 10));

    $removed_old = $old_teams_result ? $wpdb->rows_affected : 0;

    $after_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");

    echo "Cleanup completed!\n";
    echo "Removed invalid: $removed_invalid\n";
    echo "Removed duplicates: $removed_duplicates\n";
    echo "Removed old: $removed_old\n";
    echo "Teams after cleanup: $after_count\n";

    if ($after_count > 8) {
        echo "\n⚠️  WARNING: Still more than 8 teams remaining. Manual review may be needed.\n";
    }
}

function clear_and_repopulate_teams() {
    global $wpdb;

    echo "=== Clear and Repopulate Teams ===\n\n";

    // Clear all teams
    $before_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");
    echo "Teams before clear: $before_count\n";

    $result = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bkgt_teams");
    if ($result === false) {
        throw new Exception("Failed to clear teams table: " . $wpdb->last_error);
    }

    // Load scraper
    if (!class_exists('BKGT_Scraper')) {
        $scraper_files = array(
            WP_PLUGIN_DIR . '/bkgt-data-scraping/includes/class-bkgt-scraper.php',
            WP_PLUGIN_DIR . '/bkgt-core/includes/class-bkgt-scraper.php',
            get_template_directory() . '/includes/class-bkgt-scraper.php'
        );

        $loaded = false;
        foreach ($scraper_files as $file) {
            if (file_exists($file)) {
                require_once($file);
                $loaded = true;
                break;
            }
        }

        if (!$loaded) {
            throw new Exception("BKGT_Scraper class not found");
        }
    }

    // Scrape teams
    $scraper = new BKGT_Scraper();
    $scraped_count = $scraper->scrape_teams();

    echo "Scraped $scraped_count teams\n";

    // Run cleanup
    cleanup_teams();
}
?>