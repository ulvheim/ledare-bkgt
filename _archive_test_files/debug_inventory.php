<?php
require_once('wp-load.php');

echo "=== Debug: Check Actual Table Contents ===\n\n";

global $wpdb;
$prefix = $wpdb->prefix;

echo "Manufacturers:\n";
$manufacturers = $wpdb->get_results("SELECT * FROM {$prefix}bkgt_manufacturers");
foreach ($manufacturers as $manuf) {
    echo "  ID: {$manuf->id}, Name: {$manuf->name}, Code: {$manuf->manufacturer_id}\n";
}

echo "\nItem Types:\n";
$item_types = $wpdb->get_results("SELECT * FROM {$prefix}bkgt_item_types");
foreach ($item_types as $type) {
    echo "  ID: {$type->id}, Name: {$type->name}, Code: {$type->item_type_id}\n";
}

echo "\nInventory Items:\n";
$items = $wpdb->get_results("SELECT * FROM {$prefix}bkgt_inventory_items LIMIT 5");
foreach ($items as $item) {
    echo "  ID: {$item->id}, Unique: {$item->unique_identifier}, Title: {$item->title}\n";
}