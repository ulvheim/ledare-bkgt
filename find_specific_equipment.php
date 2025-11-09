<?php
require_once('wp-load.php');
global $wpdb;

echo "SEARCHING FOR SPECIFIC EQUIPMENT ITEMS:\n";
echo "=======================================\n\n";

// Search for items with specific sticker codes
$target_codes = ['5-5-1', '5-6-1'];

foreach ($target_codes as $code) {
    echo "Searching for sticker code: $code\n";

    $item = $wpdb->get_row($wpdb->prepare(
        "SELECT i.id, i.unique_identifier, i.title, i.sticker_code, i.storage_location,
                m.name as manufacturer_name, it.name as item_type_name, i.condition_status,
                i.created_at, i.updated_at
         FROM {$wpdb->prefix}bkgt_inventory_items i
         LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
         LEFT JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
         WHERE i.sticker_code = %s",
        $code
    ));

    if ($item) {
        echo "✅ FOUND!\n";
        echo "  ID: {$item->id}\n";
        echo "  Unique Identifier: {$item->unique_identifier}\n";
        echo "  Title: {$item->title}\n";
        echo "  Sticker Code: {$item->sticker_code}\n";
        echo "  Manufacturer: {$item->manufacturer_name}\n";
        echo "  Type: {$item->item_type_name}\n";
        echo "  Storage: {$item->storage_location}\n";
        echo "  Condition: {$item->condition_status}\n";
        echo "  Created: {$item->created_at}\n";
        echo "  Updated: {$item->updated_at}\n";
    } else {
        echo "❌ NOT FOUND\n";
    }
    echo "---\n";
}

// Also check all items to see what's actually in the database
echo "\nALL ITEMS IN DATABASE:\n";
echo "=====================\n";

$all_items = $wpdb->get_results(
    "SELECT i.id, i.unique_identifier, i.title, i.sticker_code,
            m.name as manufacturer_name, it.name as item_type_name
     FROM {$wpdb->prefix}bkgt_inventory_items i
     LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
     LEFT JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
     ORDER BY i.id"
);

if (empty($all_items)) {
    echo "No items found in database.\n";
} else {
    foreach ($all_items as $item) {
        echo "ID {$item->id}: {$item->title} (Code: {$item->sticker_code}) - {$item->manufacturer_name} {$item->item_type_name}\n";
    }
    echo "\nTotal items: " . count($all_items) . "\n";
}

// Check manufacturers and item types
echo "\nMANUFACTURERS:\n";
echo "=============\n";
$manufacturers = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}bkgt_manufacturers ORDER BY id");
foreach ($manufacturers as $m) {
    echo "ID {$m->id}: {$m->name}\n";
}

echo "\nITEM TYPES:\n";
echo "===========\n";
$item_types = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}bkgt_item_types ORDER BY id");
foreach ($item_types as $it) {
    echo "ID {$it->id}: {$it->name}\n";
}
?>