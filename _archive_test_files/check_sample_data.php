<?php
require_once('wp-load.php');

echo "=== Checking for Sample/Placeholder Data ===\n\n";

global $wpdb;

// Check inventory items - use direct table name
$inventory_table = $wpdb->prefix . 'bkgt_inventory_items';
$inventory_count = $wpdb->get_var("SELECT COUNT(*) FROM $inventory_table");
echo "Inventory items in database: $inventory_count\n";

if ($inventory_count == 0) {
    echo "⚠️  No real inventory data found - sample data will be displayed\n";
} else {
    echo "✅ Real inventory data exists\n";
}

// Check teams
$teams_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bkgt_team'");
echo "Teams in database: $teams_count\n";

if ($teams_count == 0) {
    echo "⚠️  No real team data found\n";
} else {
    echo "✅ Real team data exists\n";
}

// Check players
$players_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bkgt_player'");
echo "Players in database: $players_count\n";

if ($players_count == 0) {
    echo "⚠️  No real player data found\n";
} else {
    echo "✅ Real player data exists\n";
}

// Check documents
$documents_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bkgt_document'");
echo "Documents in database: $documents_count\n";

if ($documents_count == 0) {
    echo "⚠️  No real document data found\n";
} else {
    echo "✅ Real document data exists\n";
}

// Check offboarding processes
$offboarding_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bkgt_offboarding'");
echo "Offboarding processes in database: $offboarding_count\n";

if ($offboarding_count == 0) {
    echo "⚠️  No real offboarding data found\n";
} else {
    echo "✅ Real offboarding data exists\n";
}

echo "\n=== Sample Data Status ===\n";
$issues_found = 0;

if ($inventory_count == 0) {
    echo "❌ INVENTORY: Using sample data instead of real equipment\n";
    $issues_found++;
}

if ($teams_count == 0) {
    echo "❌ TEAMS: No teams configured\n";
    $issues_found++;
}

if ($players_count == 0) {
    echo "❌ PLAYERS: No player data available\n";
    $issues_found++;
}

if ($documents_count == 0) {
    echo "❌ DOCUMENTS: No documents uploaded\n";
    $issues_found++;
}

if ($offboarding_count == 0) {
    echo "❌ OFFBOARDING: No processes configured\n";
    $issues_found++;
}

if ($issues_found == 0) {
    echo "✅ All modules have real data - no placeholder content!\n";
} else {
    echo "\n⚠️  $issues_found modules still using sample/placeholder data\n";
}
?>