<?php
require_once('wp-load.php');

global $wpdb;

echo "=== Database Tables ===\n";

$tables = $wpdb->get_results("SHOW TABLES");
foreach ($tables as $table) {
    $table_name = current($table);
    if (strpos($table_name, 'bkgt') !== false) {
        echo "BKGT Table: $table_name\n";
    }
}

echo "\n=== Checking for inventory tables ===\n";
$inventory_tables = array(
    $wpdb->prefix . 'bkgt_inventory_items',
    $wpdb->prefix . 'bkgt_manufacturers',
    $wpdb->prefix . 'bkgt_item_types',
    $wpdb->prefix . 'bkgt_assignments',
    $wpdb->prefix . 'bkgt_locations'
);

foreach ($inventory_tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($exists) {
        echo "✅ $table exists\n";
    } else {
        echo "❌ $table does not exist\n";
    }
}
?>