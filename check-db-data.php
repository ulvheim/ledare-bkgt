<?php
/**
 * Check manufacturers and item types in database
 */

// Load WordPress environment
require_once('wp-load.php');

echo "<h1>Check Manufacturers and Item Types</h1>\n";
echo "<pre>\n";

global $wpdb;

// Check manufacturers
echo "=== Manufacturers ===\n";
$manufacturers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkgt_manufacturers ORDER BY manufacturer_id");
foreach ($manufacturers as $manufacturer) {
    echo "ID: {$manufacturer->manufacturer_id}, Name: {$manufacturer->name}\n";
}

// Check item types
echo "\n=== Item Types ===\n";
$item_types = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkgt_item_types ORDER BY item_type_id");
foreach ($item_types as $item_type) {
    echo "ID: {$item_type->item_type_id}, Name: {$item_type->name}\n";
}

echo "\n=== Check Complete ===\n";
echo "</pre>\n";
?>