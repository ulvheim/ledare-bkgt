<?php
// Test Database Fix
define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "Testing BKGT Database Fixes\n";
echo "===========================\n\n";

global $wpdb;

// Check database version
$current_version = get_option('bkgt_db_version', '0.0.0');
echo "Current DB Version: $current_version\n\n";

// Check if tables exist
$tables = [
    'wp_bkgt_players',
    'wp_bkgt_teams',
    'wp_bkgt_events',
    'wp_bkgt_statistics',
    'wp_bkgt_sources',
    'wp_bkgt_scraping_logs'
];

echo "Table Status:\n";
foreach ($tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    echo "✓ $table: " . ($exists ? "EXISTS" : "MISSING") . "\n";
}

echo "\nTesting table constraints:\n";

// Check teams table for unique constraint
$teams_constraints = $wpdb->get_results("SHOW INDEX FROM wp_bkgt_teams WHERE Key_name = 'name_season'");
if (!empty($teams_constraints)) {
    echo "✓ Teams table has name_season unique constraint\n";
} else {
    echo "⚠ Teams table missing name_season unique constraint\n";
}

// Check for duplicate teams
$duplicate_teams = $wpdb->get_var("
    SELECT COUNT(*) as duplicates
    FROM (
        SELECT name, season, COUNT(*) as cnt
        FROM wp_bkgt_teams
        GROUP BY name, season
        HAVING cnt > 1
    ) as dupes
");

if ($duplicate_teams > 0) {
    echo "⚠ Found $duplicate_teams duplicate team entries\n";
} else {
    echo "✓ No duplicate team entries found\n";
}

// Test plugin functionality
echo "\nTesting plugin classes:\n";
if (class_exists('BKGT_Database')) {
    echo "✓ BKGT_Database class available\n";

    try {
        $db = new BKGT_Database();
        $db->upgrade_tables();
        echo "✓ Database upgrade completed successfully\n";
    } catch (Exception $e) {
        echo "✗ Database upgrade failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ BKGT_Database class not found\n";
}

echo "\nTest completed.\n";
?>