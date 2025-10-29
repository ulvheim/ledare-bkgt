<?php
require_once('wp-load.php');

echo "=== Database Contents Check ===\n\n";

global $wpdb;

// Check if tables exist
$tables = array('bkgt_players', 'bkgt_events', 'bkgt_teams');
foreach ($tables as $table) {
    $full_table = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'") === $full_table;
    echo "Table $table: " . ($exists ? "EXISTS" : "MISSING") . "\n";

    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
        echo "  Records: $count\n";

        if ($count > 0 && $table === 'bkgt_players') {
            $players = $wpdb->get_results("SELECT name, position FROM $full_table LIMIT 3");
            foreach ($players as $player) {
                echo "    - {$player->name} ({$player->position})\n";
            }
        }
    }
}

echo "\n=== Checking Sample Data Creation ===\n";

// Try to manually insert a test player
$result = $wpdb->insert(
    $wpdb->prefix . 'bkgt_players',
    array(
        'name' => 'Test Player',
        'position' => 'Test Position',
        'number' => 99,
        'status' => 'active'
    )
);

if ($result) {
    echo "Manual insert successful\n";
    $new_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players");
    echo "New player count: $new_count\n";
} else {
    echo "Manual insert failed: " . $wpdb->last_error . "\n";
}
?>