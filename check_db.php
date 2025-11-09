<?php
require_once('wp-load.php');

global $wpdb;

// Check if manufacturer 1 exists
$manufacturer = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'bkgt_manufacturers WHERE id = 1');
echo 'Manufacturer 1: ' . ($manufacturer ? 'EXISTS' : 'NOT FOUND') . PHP_EOL;

// Check if item type 1 exists
$item_type = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'bkgt_item_types WHERE id = 1');
echo 'Item Type 1: ' . ($item_type ? 'EXISTS' : 'NOT FOUND') . PHP_EOL;

// Check if classes exist
echo 'BKGT_Manufacturer class: ' . (class_exists('BKGT_Manufacturer') ? 'EXISTS' : 'NOT FOUND') . PHP_EOL;
echo 'BKGT_Item_Type class: ' . (class_exists('BKGT_Item_Type') ? 'EXISTS' : 'NOT FOUND') . PHP_EOL;

// Try to generate identifier
if (class_exists('BKGT_Inventory_Item')) {
    $identifier = BKGT_Inventory_Item::generate_unique_identifier(1, 1);
    echo 'Generated identifier: ' . ($identifier ?: 'FAILED') . PHP_EOL;
} else {
    echo 'BKGT_Inventory_Item class not found' . PHP_EOL;
}