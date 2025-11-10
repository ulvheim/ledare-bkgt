<?php
require_once '../../../../wp-load.php';

if (!defined('ABSPATH')) {
    die('WordPress not found' . PHP_EOL);
}

echo "Testing equipment identifier resolution...\n";

// Test with a database ID
$item_id = 1; // Assuming there's at least one item
echo "Testing with database ID: $item_id\n";
$resolved = BKGT_Inventory_Item::resolve_item_identifier($item_id);
echo "Resolved to: " . ($resolved ?: 'false') . "\n";

// Test with a unique identifier (if we can find one)
global $wpdb;
$table = $wpdb->prefix . 'bkgt_inventory_items';
$unique_id = $wpdb->get_var("SELECT unique_identifier FROM $table LIMIT 1");
if ($unique_id) {
    echo "Testing with unique identifier: $unique_id\n";
    $resolved = BKGT_Inventory_Item::resolve_item_identifier($unique_id);
    echo "Resolved to: " . ($resolved ?: 'false') . "\n";
} else {
    echo "No unique identifiers found in database\n";
}

echo "Test completed.\n";
?>