<?php
require_once 'wp-load.php';

global $wpdb;

// Function to check if a team is real (has proper svenskalag.se source data)
function is_real_team($team_name, $source_id, $source_url) {
    // Only consider teams real if they have:
    // 1. A proper source_id (P2013 format)
    // 2. A svenskalag.se source_url
    if (empty($source_id) || empty($source_url)) {
        return false;
    }

    // Source ID should be in P2013 format
    if (!preg_match('/^P\d{4}$/', $source_id)) {
        return false;
    }

    // Source URL should be from svenskalag.se
    if (stripos($source_url, 'svenskalag.se') === false) {
        return false;
    }

    return true;
}

// Find and remove fake teams
$all_teams = $wpdb->get_results("SELECT id, name, source_id, source_url FROM {$wpdb->prefix}bkgt_teams");

$fake_teams = array();
$real_teams = array();

foreach ($all_teams as $team) {
    if (!is_real_team($team->name, $team->source_id, $team->source_url)) {
        $fake_teams[] = $team;
    } else {
        $real_teams[] = $team;
    }
}

echo "Found " . count($fake_teams) . " fake/demo teams to remove:\n\n";

foreach ($fake_teams as $team) {
    echo "Removing fake team: {$team->name} (ID: {$team->id}, Source: {$team->source_id})\n";
    $wpdb->delete($wpdb->prefix . 'bkgt_teams', array('id' => $team->id));
}

echo "\nRemoved " . count($fake_teams) . " fake teams.\n";

// Now handle duplicates among remaining real teams
$duplicates = $wpdb->get_results("
    SELECT source_id, COUNT(*) as count, GROUP_CONCAT(id) as ids
    FROM {$wpdb->prefix}bkgt_teams
    WHERE source_id IS NOT NULL AND source_id != ''
    GROUP BY source_id
    HAVING count > 1
");

echo "\nFound " . count($duplicates) . " duplicate source_ids among real teams:\n\n";

$total_deleted = 0;
foreach ($duplicates as $dup) {
    $ids = explode(',', $dup->ids);
    // Keep the first ID, delete the rest
    $keep_id = array_shift($ids);
    $delete_ids = implode(',', $ids);

    echo "Source ID {$dup->source_id}: keeping team ID $keep_id, deleting IDs $delete_ids\n";

    if (!empty($delete_ids)) {
        $wpdb->query("DELETE FROM {$wpdb->prefix}bkgt_teams WHERE id IN ($delete_ids)");
        $total_deleted += count($ids);
    }
}

echo "\nDeleted $total_deleted duplicate teams.\n";

// Show final team count
$final_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");
echo "Final team count: $final_count\n";

// Show remaining teams
$remaining_teams = $wpdb->get_results("SELECT id, name, source_id, category FROM {$wpdb->prefix}bkgt_teams ORDER BY name");
echo "\nRemaining teams:\n";
foreach ($remaining_teams as $team) {
    echo "{$team->id}: {$team->name} ({$team->source_id}) - {$team->category}\n";
}