<?php
/**
 * Check the auto-generated inventory item
 */

// Load WordPress environment
require_once('wp-load.php');

echo "<h1>Check Auto-Generated Inventory Item</h1>\n";
echo "<pre>\n";

// Get the latest created item (ID 24 based on test output)
$item_id = 24;
$item = BKGT_Inventory_Item::get($item_id);

if ($item) {
    echo "Item ID: $item_id\n";
    echo "Unique Identifier: " . ($item['unique_identifier'] ?? 'NOT SET') . "\n";
    echo "Sticker Code: " . ($item['sticker_code'] ?? 'NOT SET') . "\n";
    echo "Manufacturer ID: " . ($item['manufacturer_id'] ?? 'NOT SET') . "\n";
    echo "Item Type ID: " . ($item['item_type_id'] ?? 'NOT SET') . "\n";
    echo "Title: " . ($item['title'] ?? 'NOT SET') . "\n";
    echo "Storage Location: " . ($item['storage_location'] ?? 'NOT SET') . "\n";
    echo "Notes: " . ($item['notes'] ?? 'NOT SET') . "\n";

    // Verify sticker code matches unique identifier
    if (!empty($item['unique_identifier']) && !empty($item['sticker_code'])) {
        $expected_sticker = BKGT_Inventory_Item::generate_sticker_code($item['unique_identifier']);
        if ($item['sticker_code'] === $expected_sticker) {
            echo "\n✅ SUCCESS: Sticker code correctly derived from unique identifier\n";
        } else {
            echo "\n❌ ERROR: Sticker code mismatch!\n";
            echo "Expected: $expected_sticker\n";
            echo "Actual: {$item['sticker_code']}\n";
        }
    }
} else {
    echo "ERROR: Could not retrieve item with ID $item_id\n";
}

echo "\n=== Check Complete ===\n";
echo "</pre>\n";
?>