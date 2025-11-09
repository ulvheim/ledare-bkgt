<?php
require_once('wp-load.php');
global $wpdb;

echo "INVENTORY ITEMS FROM DATABASE:\n";
echo "===============================\n\n";

$items = $wpdb->get_results(
    "SELECT i.id, i.unique_identifier, i.title, m.name as manufacturer_name, it.name as item_type_name, i.condition_status
     FROM {$wpdb->prefix}bkgt_inventory_items i
     LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
     LEFT JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
     ORDER BY i.id DESC LIMIT 20"
);

if (empty($items)) {
    echo "No inventory items found.\n";
} else {
    foreach ($items as $item) {
        echo "ID: {$item->id}\n";
        echo "Identifier: {$item->unique_identifier}\n";
        echo "Title: {$item->title}\n";
        echo "Manufacturer: {$item->manufacturer_name}\n";
        echo "Type: {$item->item_type_name}\n";
        echo "Status: {$item->condition_status}\n";
        echo "---\n";
    }

    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_inventory_items");
    echo "\nTotal items: $total\n";
}
?>