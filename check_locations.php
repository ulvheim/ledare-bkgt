<?php
require_once('wp-load.php');

echo "Checking locations...\n";

global $wpdb;

// Check if the locations table exists
$locations_table = $wpdb->prefix . 'bkgt_locations';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$locations_table'") === $locations_table;

echo "Locations table exists: " . ($table_exists ? 'YES' : 'NO') . "\n";

if ($table_exists) {
    // Check how many locations exist
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $locations_table");
    echo "Number of locations: $count\n";

    if ($count > 0) {
        // Show some locations
        $locations = $wpdb->get_results("SELECT id, name, is_active FROM $locations_table LIMIT 10");
        echo "\nFirst 10 locations:\n";
        foreach ($locations as $location) {
            echo "- ID: {$location->id}, Name: {$location->name}, Active: " . ($location->is_active ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "No locations found in the table.\n";
    }
} else {
    echo "Locations table does not exist.\n";
}

echo "\nDone.\n";
?>