<?php
/**
 * Simple test script to verify create_item method works without title
 */

require_once('wp-load.php');

// Test data - no title provided
$test_data = array(
    'manufacturer_id' => 1,
    'item_type_id' => 1,
    'storage_location' => 'Test Location',
    'condition_status' => 'normal'
);

echo "Testing create_item method directly...\n";
echo "Test data (no title provided):\n";
echo json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

// Test the create_item method directly
$result = BKGT_Inventory_Item::create_item($test_data);

if (is_wp_error($result)) {
    echo "❌ ERROR: " . $result->get_error_message() . "\n";
    echo "Error Code: " . $result->get_error_code() . "\n";
} else {
    echo "✅ SUCCESS: Item created with ID: $result\n";

    // Get the created item to check the title
    $item = BKGT_Inventory_Item::get_item($result);
    if ($item) {
        echo "Generated Title: '" . $item['title'] . "'\n";
        echo "Unique Identifier: '" . $item['unique_identifier'] . "'\n";
        if ($item['title'] === $item['unique_identifier']) {
            echo "✅ Title correctly auto-generated from unique identifier!\n";
        } else {
            echo "❌ Title was not auto-generated correctly\n";
        }
    } else {
        echo "❌ Could not retrieve created item\n";
    }
}
?>