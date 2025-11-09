<?php
/**
 * Equipment Data Verification Script
 * Checks for specific football equipment items that should exist
 */

require_once('wp-load.php');
global $wpdb;

echo "EQUIPMENT DATA VERIFICATION\n";
echo "===========================\n\n";

// Expected items
$expected_items = [
    '5-5-1' => 'Football item with code 5-5-1',
    '5-6-1' => 'Football item with code 5-6-1'
];

echo "CHECKING FOR EXPECTED ITEMS:\n";
echo "----------------------------\n";

$found_count = 0;
foreach ($expected_items as $code => $description) {
    echo "Looking for: $description (Code: $code)\n";

    $item = $wpdb->get_row($wpdb->prepare(
        "SELECT i.*, m.name as manufacturer_name, it.name as item_type_name
         FROM {$wpdb->prefix}bkgt_inventory_items i
         LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
         LEFT JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
         WHERE i.sticker_code = %s",
        $code
    ));

    if ($item) {
        echo "✅ FOUND!\n";
        echo "   ID: {$item->id}\n";
        echo "   Title: {$item->title}\n";
        echo "   Unique Identifier: {$item->unique_identifier}\n";
        echo "   Manufacturer: {$item->manufacturer_name} (ID: {$item->manufacturer_id})\n";
        echo "   Item Type: {$item->item_type_name} (ID: {$item->item_type_id})\n";
        echo "   Storage Location: {$item->storage_location}\n";
        echo "   Condition: {$item->condition_status}\n";
        echo "   Created: {$item->created_at}\n";
        $found_count++;
    } else {
        echo "❌ NOT FOUND\n";
    }
    echo "\n";
}

echo "SUMMARY:\n";
echo "--------\n";
echo "Expected items: " . count($expected_items) . "\n";
echo "Found items: $found_count\n";
echo "Missing items: " . (count($expected_items) - $found_count) . "\n\n";

if ($found_count < count($expected_items)) {
    echo "❌ SOME ITEMS ARE MISSING!\n\n";

    // Check what items actually exist
    echo "ALL EXISTING ITEMS:\n";
    echo "------------------\n";

    $all_items = $wpdb->get_results(
        "SELECT i.id, i.title, i.sticker_code, i.unique_identifier,
                m.name as manufacturer_name, it.name as item_type_name
         FROM {$wpdb->prefix}bkgt_inventory_items i
         LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
         LEFT JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
         ORDER BY i.id"
    );

    if (empty($all_items)) {
        echo "No items found in database at all!\n\n";
        echo "🔧 TROUBLESHOOTING:\n";
        echo "1. Check if bkgt-inventory plugin is active\n";
        echo "2. Check if database tables exist\n";
        echo "3. Check if data was accidentally deleted\n";
    } else {
        echo "Found " . count($all_items) . " items in database:\n";
        foreach ($all_items as $item) {
            echo "- ID {$item->id}: {$item->title} (Code: {$item->sticker_code}, Type: {$item->item_type_name})\n";
        }
    }
} else {
    echo "✅ ALL EXPECTED ITEMS FOUND!\n";
}

// Check database table structure
echo "\nDATABASE STRUCTURE CHECK:\n";
echo "-------------------------\n";

$tables = ['bkgt_inventory_items', 'bkgt_manufacturers', 'bkgt_item_types'];
foreach ($tables as $table) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}$table");
    echo "$table: $count records\n";
}

echo "\nINSTRUCTIONS:\n";
echo "-------------\n";
if ($found_count < count($expected_items)) {
    echo "If items are missing, you can:\n";
    echo "1. Check WordPress admin for the bkgt-inventory interface\n";
    echo "2. Add the missing items manually through the admin interface\n";
    echo "3. Check if there are database backups to restore from\n";
    echo "4. Run this script again after adding the items\n";
} else {
    echo "Items are present and accounted for!\n";
    echo "The API endpoints should now work correctly.\n";
}
?>