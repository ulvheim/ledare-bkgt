<?php
require_once('wp-load.php');
global $wpdb;

$inventory_db = new BKGT_Inventory_Database();

echo "Manufacturers:\n";
$m = $wpdb->get_results("SELECT id, name, manufacturer_id FROM " . $inventory_db->get_manufacturers_table());
foreach ($m as $manuf) {
    echo $manuf->id . ": " . $manuf->name . " (" . $manuf->manufacturer_id . ")\n";
}

echo "\nItem Types:\n";
$t = $wpdb->get_results("SELECT id, name, item_type_id FROM " . $inventory_db->get_item_types_table());
foreach ($t as $type) {
    echo $type->id . ": " . $type->name . " (" . $type->item_type_id . ")\n";
}

echo "\nSample inventory items:\n";
$items = $wpdb->get_results("SELECT id, unique_identifier, manufacturer_id, item_type_id FROM " . $inventory_db->get_inventory_items_table() . " LIMIT 5");
foreach ($items as $item) {
    echo $item->unique_identifier . ": manuf=" . $item->manufacturer_id . ", type=" . $item->item_type_id . "\n";
}
?>