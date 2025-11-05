<?php
require_once('wp-load.php');
global $wpdb;

echo "=== BKGT Teams Table Schema ===\n";
$teams_schema = $wpdb->get_results("DESCRIBE {$wpdb->prefix}bkgt_teams");
foreach ($teams_schema as $column) {
    echo "{$column->Field}: {$column->Type} ({$column->Null}) Default: {$column->Default}\n";
}

echo "\n=== BKGT Players Table Schema ===\n";
$players_schema = $wpdb->get_results("DESCRIBE {$wpdb->prefix}bkgt_players");
foreach ($players_schema as $column) {
    echo "{$column->Field}: {$column->Type} ({$column->Null}) Default: {$column->Default}\n";
}
?>