<?php
/**
 * Fix First Football Item Unique Identifier
 * Correct the manufacturer assignment for the first football
 */

require_once('wp-load.php');
global $wpdb;

echo "FIXING FIRST FOOTBALL UNIQUE IDENTIFIER\n";
echo "=======================================\n\n";

echo "CURRENT EQUIPMENT ITEMS:\n";
echo "------------------------\n";

$items = $wpdb->get_results(
    "SELECT i.id, i.title, i.sticker_code, i.unique_identifier, i.manufacturer_id, i.item_type_id,
            m.name as manufacturer_name, it.name as item_type_name
     FROM {$wpdb->prefix}bkgt_inventory_items i
     LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
     LEFT JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
     ORDER BY i.id"
);

foreach ($items as $item) {
    echo "ID {$item->id}: {$item->title} (Code: {$item->sticker_code})\n";
    echo "  Current: {$item->manufacturer_name} (ID: {$item->manufacturer_id}) - {$item->item_type_name} (ID: {$item->item_type_id})\n";
    echo "  Unique ID: {$item->unique_identifier}\n\n";
}

echo "CORRECTING FIRST FOOTBALL ITEM:\n";
echo "--------------------------------\n";

// Find the item with sticker_code = '5-5-1'
$item_to_fix = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}bkgt_inventory_items WHERE sticker_code = %s",
    '5-5-1'
));

if (!$item_to_fix) {
    echo "❌ Item with sticker code 5-5-1 not found!\n";
    exit;
}

echo "Found item: {$item_to_fix->title} (ID: {$item_to_fix->id})\n";
echo "Current manufacturer ID: {$item_to_fix->manufacturer_id}\n";

// Change manufacturer from 1 (BKGT) to 5 (Wilson)
$new_manufacturer_id = 5;
$new_item_type_id = 5; // Footballs

// Generate new unique identifier
if (!class_exists('BKGT_Inventory_Item')) {
    echo "❌ BKGT_Inventory_Item class not found!\n";
    exit;
}

$new_unique_identifier = BKGT_Inventory_Item::generate_unique_identifier($new_manufacturer_id, $new_item_type_id);

if (!$new_unique_identifier) {
    echo "❌ Failed to generate new unique identifier!\n";
    exit;
}

echo "New manufacturer ID: {$new_manufacturer_id} (Wilson)\n";
echo "New unique identifier: {$new_unique_identifier}\n";

// Update the item
$result = $wpdb->update(
    $wpdb->prefix . 'bkgt_inventory_items',
    array(
        'manufacturer_id' => $new_manufacturer_id,
        'unique_identifier' => $new_unique_identifier,
        'updated_at' => current_time('mysql')
    ),
    array('id' => $item_to_fix->id),
    array('%d', '%s', '%s'),
    array('%d')
);

if ($result === false) {
    echo "❌ Failed to update item: " . $wpdb->last_error . "\n";
} else {
    echo "✅ Successfully updated item!\n";
}

echo "\nVERIFICATION - UPDATED ITEMS:\n";
echo "------------------------------\n";

$updated_items = $wpdb->get_results(
    "SELECT i.id, i.title, i.sticker_code, i.unique_identifier, i.manufacturer_id, i.item_type_id,
            m.name as manufacturer_name, it.name as item_type_name
     FROM {$wpdb->prefix}bkgt_inventory_items i
     LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
     LEFT JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
     ORDER BY i.id"
);

foreach ($updated_items as $item) {
    echo "ID {$item->id}: {$item->title} (Code: {$item->sticker_code})\n";
    echo "  Manufacturer: {$item->manufacturer_name} (ID: {$item->manufacturer_id})\n";
    echo "  Item Type: {$item->item_type_name} (ID: {$item->item_type_id})\n";
    echo "  Unique ID: {$item->unique_identifier}\n\n";
}

echo "EXPECTED RESULTS:\n";
echo "-----------------\n";
echo "Football 5-5-1: 0005-0005-00001 (Wilson manufacturer, Footballs, first item)\n";
echo "Football 5-6-1: 0006-0005-00001 (No-name manufacturer, Footballs, first item)\n";
?>