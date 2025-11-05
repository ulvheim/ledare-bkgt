<?php
require_once('wp-load.php');
global $wpdb;

$inventory_db = new BKGT_Inventory_Database();

echo "Checking inventory items data...\n";
$items = $wpdb->get_results("SELECT i.id, i.unique_identifier, i.title, i.manufacturer_id, i.item_type_id, m.name as manufacturer_name, t.name as item_type_name FROM {$inventory_db->get_inventory_items_table()} i LEFT JOIN {$inventory_db->get_manufacturers_table()} m ON i.manufacturer_id = m.id LEFT JOIN {$inventory_db->get_item_types_table()} t ON i.item_type_id = t.id LIMIT 5");

foreach ($items as $item) {
    echo "ID: {$item->id}, Identifier: {$item->unique_identifier}, Title: {$item->title}, Manufacturer ID: {$item->manufacturer_id}, Type ID: {$item->item_type_id}, Manufacturer: '{$item->manufacturer_name}', Type: '{$item->item_type_name}'\n";
}

echo "\nChecking manufacturers table...\n";
$manufacturers = $wpdb->get_results("SELECT * FROM {$inventory_db->get_manufacturers_table()} LIMIT 10");
foreach ($manufacturers as $m) {
    echo "ID: {$m->id}, Name: {$m->name}, Manufacturer ID: {$m->manufacturer_id}\n";
}

echo "\nChecking item types table...\n";
$types = $wpdb->get_results("SELECT * FROM {$inventory_db->get_item_types_table()} LIMIT 10");
foreach ($types as $t) {
    echo "ID: {$t->id}, Name: {$t->name}, Item Type ID: {$t->item_type_id}\n";
}
?>