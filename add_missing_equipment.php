<?php
/**
 * Add Missing Equipment Items
 * Adds the two football items that should exist
 */

require_once('wp-load.php');
global $wpdb;

echo "ADDING MISSING EQUIPMENT ITEMS\n";
echo "==============================\n\n";

// Check if required classes exist
if (!class_exists('BKGT_Inventory_Item')) {
    echo "❌ ERROR: BKGT_Inventory_Item class not found. Make sure bkgt-inventory plugin is active.\n";
    exit;
}

// Check what manufacturers and item types exist
echo "CHECKING MANUFACTURERS AND ITEM TYPES:\n";
$manufacturers = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}bkgt_manufacturers ORDER BY name");
$item_types = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}bkgt_item_types ORDER BY name");

echo "Manufacturers:\n";
foreach ($manufacturers as $m) {
    echo "  ID {$m->id}: {$m->name}\n";
}

echo "\nItem Types:\n";
foreach ($item_types as $it) {
    echo "  ID {$it->id}: {$it->name}\n";
}

// Find Football item type
$football_type = null;
foreach ($item_types as $it) {
    if (stripos($it->name, 'football') !== false || stripos($it->name, 'fotboll') !== false) {
        $football_type = $it;
        break;
    }
}

if (!$football_type) {
    echo "\n❌ ERROR: No Football item type found. Please create it first.\n";
    exit;
}

echo "\n✅ Found Football item type: {$football_type->name} (ID: {$football_type->id})\n";

// Use first manufacturer if available
$manufacturer = !empty($manufacturers) ? $manufacturers[0] : null;
if (!$manufacturer) {
    echo "❌ ERROR: No manufacturers found. Please create one first.\n";
    exit;
}

echo "✅ Using manufacturer: {$manufacturer->name} (ID: {$manufacturer->id})\n\n";

// Items to add
$items_to_add = [
    [
        'sticker_code' => '5-5-1',
        'title' => 'Football 5-5-1',
        'manufacturer_id' => $manufacturer->id,
        'item_type_id' => $football_type->id,
        'storage_location' => 'Main Storage',
        'condition_status' => 'normal'
    ],
    [
        'sticker_code' => '5-6-1',
        'title' => 'Football 5-6-1',
        'manufacturer_id' => $manufacturer->id,
        'item_type_id' => $football_type->id,
        'storage_location' => 'Main Storage',
        'condition_status' => 'normal'
    ]
];

$added_count = 0;
foreach ($items_to_add as $item_data) {
    // Check if item already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}bkgt_inventory_items WHERE sticker_code = %s",
        $item_data['sticker_code']
    ));

    if ($existing) {
        echo "⚠️  Item with code {$item_data['sticker_code']} already exists (ID: $existing)\n";
        continue;
    }

    // Generate unique identifier
    $unique_identifier = BKGT_Inventory_Item::generate_unique_identifier(
        $item_data['manufacturer_id'],
        $item_data['item_type_id']
    );

    if (!$unique_identifier) {
        echo "❌ Failed to generate unique identifier for {$item_data['sticker_code']}\n";
        continue;
    }

    // Insert the item
    $result = $wpdb->insert(
        $wpdb->prefix . 'bkgt_inventory_items',
        array(
            'unique_identifier' => $unique_identifier,
            'manufacturer_id' => $item_data['manufacturer_id'],
            'item_type_id' => $item_data['item_type_id'],
            'title' => $item_data['title'],
            'storage_location' => $item_data['storage_location'],
            'sticker_code' => $item_data['sticker_code'],
            'condition_status' => $item_data['condition_status'],
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array('%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    if ($result) {
        $new_id = $wpdb->insert_id;
        echo "✅ Added item: {$item_data['title']} (ID: $new_id, Code: {$item_data['sticker_code']})\n";
        $added_count++;
    } else {
        echo "❌ Failed to add item: {$item_data['title']} - " . $wpdb->last_error . "\n";
    }
}

echo "\nSUMMARY:\n";
echo "--------\n";
echo "Items attempted: " . count($items_to_add) . "\n";
echo "Items added: $added_count\n";
echo "Items skipped (already exist): " . (count($items_to_add) - $added_count) . "\n";

if ($added_count > 0) {
    echo "\n✅ Equipment items have been added to the database!\n";
    echo "Run verify_equipment_data.php to confirm they are present.\n";
} else {
    echo "\nℹ️  No new items were added (they may already exist).\n";
}
?>