<?php
require_once 'wp-load.php';

global $wpdb;

echo "Table prefix: " . $wpdb->prefix . "\n";
echo "Manufacturers table: " . $wpdb->prefix . "bkgt_manufacturers\n";
echo "Item types table: " . $wpdb->prefix . "bkgt_item_types\n";

// Test the database class
$db = bkgt_inventory()->db;
echo "DB manufacturers table: " . $db->get_manufacturers_table() . "\n";
echo "DB item types table: " . $db->get_item_types_table() . "\n";

// Test prepare statements
echo "\nTesting prepare statements:\n";
$query1 = $wpdb->prepare("SELECT * FROM %s ORDER BY name ASC", $db->get_manufacturers_table());
echo "Prepared manufacturers query: $query1\n";

$query2 = $wpdb->prepare("SELECT * FROM %s ORDER BY name ASC", $db->get_item_types_table());
echo "Prepared item types query: $query2\n";

// Test direct queries
echo "\nDirect query test:\n";
$manufacturers_table = $wpdb->prefix . 'bkgt_manufacturers';
$results = $wpdb->get_results("SELECT * FROM $manufacturers_table", ARRAY_A);
echo "Direct manufacturers query: " . count($results) . " results\n";

$item_types_table = $wpdb->prefix . 'bkgt_item_types';
$results = $wpdb->get_results("SELECT * FROM $item_types_table", ARRAY_A);
echo "Direct item types query: " . count($results) . " results\n";

// Test the get_all methods
echo "\nTesting BKGT_Manufacturer::get_all():\n";
try {
    $manufacturers = BKGT_Manufacturer::get_all();
    echo "Success! Found " . count($manufacturers) . " manufacturers\n";
    if (count($manufacturers) > 0) {
        echo "First manufacturer: " . $manufacturers[0]['name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nTesting BKGT_Item_Type::get_all():\n";
try {
    $item_types = BKGT_Item_Type::get_all();
    echo "Success! Found " . count($item_types) . " item types\n";
    if (count($item_types) > 0) {
        echo "First item type: " . $item_types[0]['name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>