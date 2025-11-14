<?php
/**
 * Simple test to verify auto-generated inventory item
 */

// Load WordPress environment
require_once('wp-load.php');

// Get the latest created item
$item_id = 24;
$item = BKGT_Inventory_Item::get($item_id);

if ($item) {
    echo "SUCCESS: Item created with auto-generated fields\n";
    echo "Unique Identifier: " . ($item['unique_identifier'] ?? 'NOT SET') . "\n";
    echo "Sticker Code: " . ($item['sticker_code'] ?? 'NOT SET') . "\n";
    echo "Title: " . ($item['title'] ?? 'NOT SET') . "\n";

    // Verify sticker code matches unique identifier
    if (!empty($item['unique_identifier']) && !empty($item['sticker_code'])) {
        $expected_sticker = BKGT_Inventory_Item::generate_sticker_code($item['unique_identifier']);
        if ($item['sticker_code'] === $expected_sticker) {
            echo "VERIFICATION: Sticker code correctly derived from unique identifier\n";
        } else {
            echo "ERROR: Sticker code mismatch!\n";
        }
    }
} else {
    echo "ERROR: Could not retrieve item\n";
}
?>