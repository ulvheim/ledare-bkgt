<?php
require_once('wp-load.php');

echo "=== Table Structure Check ===\n\n";

global $wpdb;

$tables = array('bkgt_players', 'bkgt_events', 'bkgt_teams');

foreach ($tables as $table) {
    echo "Table: $table\n";
    $full_table = $wpdb->prefix . $table;

    $columns = $wpdb->get_results("DESCRIBE $full_table");
    if ($columns) {
        foreach ($columns as $col) {
            echo "  {$col->Field}: {$col->Type}\n";
        }
    } else {
        echo "  No columns found or table doesn't exist\n";
    }
    echo "\n";
}

// Check if we can insert with correct columns
echo "=== Testing Insert ===\n";

$test_data = array(
    'name' => 'Test Player',
    'position' => 'Quarterback',
    'number' => 12,
    'status' => 'active'
);

$result = $wpdb->insert($wpdb->prefix . 'bkgt_players', $test_data);

if ($result) {
    echo "Insert successful!\n";
} else {
    echo "Insert failed: " . $wpdb->last_error . "\n";
    echo "Last query: " . $wpdb->last_query . "\n";
}
?>