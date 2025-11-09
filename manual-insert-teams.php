<?php
/**
 * Manually Insert the 8 Teams from svenskalag.se
 */

require_once('wp-load.php');

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

global $wpdb;

echo "=== Manually Inserting 8 Teams from svenskalag.se ===\n\n";

try {
    $table_name = $wpdb->prefix . 'bkgt_teams';

    // Clear existing teams
    $wpdb->query("TRUNCATE TABLE $table_name");
    echo "Cleared existing teams\n";

    // The 8 teams found on svenskalag.se
    $teams = array(
        array('name' => 'P2013', 'url' => 'https://www.svenskalag.se/bkgt-p2013'),
        array('name' => 'P2014', 'url' => 'https://www.svenskalag.se/bkgt-p2014'),
        array('name' => 'P2015', 'url' => 'https://www.svenskalag.se/bkgt-p2015'),
        array('name' => 'P2016', 'url' => 'https://www.svenskalag.se/bkgt-p2016'),
        array('name' => 'P2017', 'url' => 'https://www.svenskalag.se/bkgt-p2017'),
        array('name' => 'P2018', 'url' => 'https://www.svenskalag.se/bkgt-p2018'),
        array('name' => 'P2019', 'url' => 'https://www.svenskalag.se/bkgt-p2019'),
        array('name' => 'P2020', 'url' => 'https://www.svenskalag.se/bkgt-p2020'),
    );

    $inserted = 0;
    foreach ($teams as $team) {
        $result = $wpdb->insert($table_name, array(
            'name' => $team['name'],
            'svenskalag_id' => $team['name'], // Use name as ID for now
            'category' => 'Barn',
            'status' => 'active',
            'season' => date('Y'),
            'created_date' => current_time('mysql'),
            'updated_date' => current_time('mysql')
        ));

        if ($result) {
            $inserted++;
            echo "✅ Inserted {$team['name']}\n";
        } else {
            echo "❌ Failed to insert {$team['name']}: " . $wpdb->last_error . "\n";
        }
    }

    echo "\nInserted $inserted teams\n";

    // Verify
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "Total teams in database: $count\n";

    if ($count == 8) {
        echo "\n✅ SUCCESS: Exactly 8 teams now in database, matching svenskalag.se\n";
        echo "The team data issue has been resolved!\n";
    } else {
        echo "\n⚠️  WARNING: Expected 8 teams, but have $count\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>