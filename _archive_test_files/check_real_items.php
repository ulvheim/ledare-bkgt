<?php
require_once('wp-load.php');
global $wpdb;

$inventory_db = new BKGT_Inventory_Database();

$items = $wpdb->get_results("SELECT id, unique_identifier, title, manufacturer_id, item_type_id FROM " . $inventory_db->get_inventory_items_table());

echo "Total items in database: " . count($items) . "\n\n";

foreach ($items as $item) {
    echo $item->unique_identifier . " - " . $item->title . "\n";
}

echo "\nChecking if the real item exists...\n";
$real_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $inventory_db->get_inventory_items_table() . " WHERE unique_identifier = %s", "0005-0005-00001"));

if ($real_item) {
    echo "Found real item: " . $real_item->unique_identifier . " - " . $real_item->title . "\n";
} else {
    echo "Real item not found in database\n";
}
?>