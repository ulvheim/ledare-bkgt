<?php
// Debug Teams Table
define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "Debugging Teams Table\n";
echo "=====================\n\n";

global $wpdb;

$teams = $wpdb->get_results('SELECT id, name, season FROM wp_bkgt_teams');
echo "Teams in database:\n";
foreach($teams as $team) {
    echo $team->id . ': ' . $team->name . ' (' . $team->season . ')' . "\n";
}

echo "\nChecking for duplicates:\n";
$duplicates = $wpdb->get_results("
    SELECT name, season, COUNT(*) as cnt
    FROM wp_bkgt_teams
    GROUP BY name, season
    HAVING cnt > 1
");

if (empty($duplicates)) {
    echo "No duplicates found\n";
} else {
    foreach($duplicates as $dup) {
        echo "Duplicate: {$dup->name} ({$dup->season}) - {$dup->cnt} entries\n";
    }
}

echo "\nChecking constraints:\n";
$constraints = $wpdb->get_results("SHOW INDEX FROM wp_bkgt_teams WHERE Key_name = 'name_season'");
if (empty($constraints)) {
    echo "No name_season constraint found\n";
} else {
    echo "name_season constraint exists\n";
}

echo "\nDebug completed.\n";
?>