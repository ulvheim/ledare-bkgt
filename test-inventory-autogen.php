<?php
/**
 * Test script to verify inventory system auto-generation functionality
 * Run this via web browser or command line to test the implementation
 */

// Load WordPress environment
require_once('wp-load.php');

echo "<h1>Inventory System Auto-Generation Test</h1>\n";
echo "<pre>\n";

// Test 1: Verify generate_unique_identifier function
echo "=== Test 1: Unique Identifier Generation ===\n";
$test_cases = [
    ['manufacturer_id' => 1, 'item_type_id' => 1],
    ['manufacturer_id' => 5, 'item_type_id' => 5],
    ['manufacturer_id' => 2, 'item_type_id' => 3],
    ['manufacturer_id' => 0, 'item_type_id' => 0],
];

foreach ($test_cases as $test) {
    $uid = BKGT_Inventory_Item::generate_unique_identifier($test['manufacturer_id'], $test['item_type_id']);
    echo "Manufacturer {$test['manufacturer_id']}, Type {$test['item_type_id']}: $uid\n";
}

// Test 2: Verify generate_sticker_code function
echo "\n=== Test 2: Sticker Code Generation ===\n";
foreach ($test_cases as $test) {
    $uid = BKGT_Inventory_Item::generate_unique_identifier($test['manufacturer_id'], $test['item_type_id']);
    $sticker = BKGT_Inventory_Item::generate_sticker_code($uid);
    echo "UID: $uid -> Sticker: $sticker\n";
}

// Test 3: Verify create_item auto-generates fields
echo "\n=== Test 3: Create Item Auto-Generation ===\n";
$test_data = [
    'manufacturer_id' => 1,
    'item_type_id' => 1,
    'storage_location' => 'Test Location',
    'notes' => 'Test item for auto-generation'
];

echo "Creating test item with data:\n";
print_r($test_data);

$result = BKGT_Inventory_Item::create_item($test_data);

if (is_wp_error($result)) {
    echo "ERROR: " . $result->get_error_message() . "\n";
} else {
    echo "SUCCESS: Item created with ID: $result\n";

    // Retrieve the created item to verify auto-generated fields
    $item = BKGT_Inventory_Item::get($result);
    if ($item) {
        echo "Retrieved item data:\n";
        echo "  Unique Identifier: " . ($item['unique_identifier'] ?? 'NOT SET') . "\n";
        echo "  Sticker Code: " . ($item['sticker_code'] ?? 'NOT SET') . "\n";
        echo "  Manufacturer ID: " . ($item['manufacturer_id'] ?? 'NOT SET') . "\n";
        echo "  Item Type ID: " . ($item['item_type_id'] ?? 'NOT SET') . "\n";
    } else {
        echo "ERROR: Could not retrieve created item\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "</pre>\n";
?>