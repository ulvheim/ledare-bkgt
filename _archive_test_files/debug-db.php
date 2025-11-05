<?php
// Debug script to check database tables and data
require_once('wp-load.php');

global $wpdb;

echo "Checking database tables...\n\n";

// Check if tables exist
$manufacturers_table = $wpdb->prefix . 'bkgt_manufacturers';
$item_types_table = $wpdb->prefix . 'bkgt_item_types';

echo "Manufacturers table: $manufacturers_table\n";
echo "Item types table: $item_types_table\n\n";

$manufacturers = $wpdb->get_results("SELECT * FROM $manufacturers_table", ARRAY_A);
$item_types = $wpdb->get_results("SELECT * FROM $item_types_table", ARRAY_A);

echo "Manufacturers found: " . count($manufacturers) . "\n";
if (count($manufacturers) > 0) {
    foreach ($manufacturers as $m) {
        echo "  - ID: {$m['id']}, Name: {$m['name']}\n";
    }
} else {
    echo "  No manufacturers found!\n";
}

echo "\nItem types found: " . count($item_types) . "\n";
if (count($item_types) > 0) {
    foreach ($item_types as $it) {
        echo "  - ID: {$it['id']}, Name: {$it['name']}\n";
    }
} else {
    echo "  No item types found!\n";
}
?>