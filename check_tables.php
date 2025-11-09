<?php
require_once('wp-load.php');
global $wpdb;

echo "MANUFACTURERS:\n";
$results = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bkgt_manufacturers');
foreach($results as $row) {
    echo "ID: {$row->id}, Name: {$row->name}, Manufacturer_ID: {$row->manufacturer_id}\n";
}

echo "\nITEM TYPES:\n";
$results = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bkgt_item_types');
foreach($results as $row) {
    echo "ID: {$row->id}, Name: {$row->name}, Item_Type_ID: {$row->item_type_id}\n";
}

echo "\nINVENTORY ITEMS:\n";
$results = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bkgt_inventory_items');
foreach($results as $row) {
    echo "ID: {$row->id}, Manuf: {$row->manufacturer_id}, Type: {$row->item_type_id}, Seq: {$row->sequential_number}, Identifier: {$row->unique_identifier}\n";
}
?>