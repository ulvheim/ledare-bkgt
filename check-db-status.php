<?php
require_once 'wp-load.php';

global $wpdb;

// Check manufacturers table
$manufacturers_table = $wpdb->prefix . 'bkgt_manufacturers';
$manufacturers = $wpdb->get_results("SELECT * FROM $manufacturers_table", ARRAY_A);

echo "Manufacturers table:\n";
echo "Count: " . count($manufacturers) . "\n";
if (empty($manufacturers)) {
    echo "No manufacturers found!\n";
} else {
    foreach ($manufacturers as $manufacturer) {
        echo "- ID: {$manufacturer['id']}, Name: {$manufacturer['name']}\n";
    }
}

// Check item types table
$item_types_table = $wpdb->prefix . 'bkgt_item_types';
$item_types = $wpdb->get_results("SELECT * FROM $item_types_table", ARRAY_A);

echo "\nItem Types table:\n";
echo "Count: " . count($item_types) . "\n";
if (empty($item_types)) {
    echo "No item types found!\n";
} else {
    foreach ($item_types as $item_type) {
        echo "- ID: {$item_type['id']}, Name: {$item_type['name']}\n";
    }
}

// Check if plugin is active
$active_plugins = get_option('active_plugins');
if (in_array('bkgt-inventory/bkgt-inventory.php', $active_plugins)) {
    echo "\n✓ BKGT Inventory plugin is active\n";
} else {
    echo "\n✗ BKGT Inventory plugin is NOT active\n";
    echo "Active plugins: " . implode(', ', $active_plugins) . "\n";
}
?>