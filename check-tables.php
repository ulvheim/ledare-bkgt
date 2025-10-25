<?php
require_once 'wp-load.php';

global $wpdb;

echo "Checking BKGT tables...\n";

// Check manufacturers table
$manufacturers_table = $wpdb->prefix . 'bkgt_manufacturers';
$result = $wpdb->get_var("SHOW TABLES LIKE '$manufacturers_table'");
echo "Manufacturers table exists: " . ($result ? 'YES' : 'NO') . " ($manufacturers_table)\n";

// Check item types table
$item_types_table = $wpdb->prefix . 'bkgt_item_types';
$result = $wpdb->get_var("SHOW TABLES LIKE '$item_types_table'");
echo "Item types table exists: " . ($result ? 'YES' : 'NO') . " ($item_types_table)\n";

// Try to query manufacturers
try {
    $manufacturers = $wpdb->get_results("SELECT COUNT(*) as count FROM $manufacturers_table", ARRAY_A);
    echo "Manufacturers count: " . ($manufacturers[0]['count'] ?? 'ERROR') . "\n";
} catch (Exception $e) {
    echo "Error querying manufacturers: " . $e->getMessage() . "\n";
}

// Try to query item types
try {
    $item_types = $wpdb->get_results("SELECT COUNT(*) as count FROM $item_types_table", ARRAY_A);
    echo "Item types count: " . ($item_types[0]['count'] ?? 'ERROR') . "\n";
} catch (Exception $e) {
    echo "Error querying item types: " . $e->getMessage() . "\n";
}
?>