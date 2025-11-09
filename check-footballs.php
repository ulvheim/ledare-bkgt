<?php
/**
 * Check Football Items in Database
 */

require_once('wp-load.php');

global $wpdb;

echo "<h1>Football Items in Database</h1>";

$footballs = $wpdb->get_results("
    SELECT id, title, manufacturer_id, item_type_id, unique_identifier, sticker_code
    FROM {$wpdb->prefix}bkgt_inventory_items
    WHERE item_type_id = 5
    ORDER BY id
");

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Title</th><th>Manufacturer ID</th><th>Item Type ID</th><th>Unique ID</th><th>Sticker Code</th></tr>";

foreach ($footballs as $football) {
    echo "<tr>";
    echo "<td>{$football->id}</td>";
    echo "<td>{$football->title}</td>";
    echo "<td>{$football->manufacturer_id}</td>";
    echo "<td>{$football->item_type_id}</td>";
    echo "<td>{$football->unique_identifier}</td>";
    echo "<td>{$football->sticker_code}</td>";
    echo "</tr>";
}

echo "</table>";

// Check manufacturers
echo "<h2>Manufacturers</h2>";
$manufacturers = $wpdb->get_results("SELECT id, manufacturer_id, name FROM {$wpdb->prefix}bkgt_manufacturers ORDER BY manufacturer_id");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Manufacturer ID</th><th>Name</th></tr>";
foreach ($manufacturers as $mfg) {
    echo "<tr><td>{$mfg->id}</td><td>{$mfg->manufacturer_id}</td><td>{$mfg->name}</td></tr>";
}
echo "</table>";

// Check item types
echo "<h2>Item Types</h2>";
$item_types = $wpdb->get_results("SELECT id, item_type_id, name FROM {$wpdb->prefix}bkgt_item_types ORDER BY item_type_id");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Item Type ID</th><th>Name</th></tr>";
foreach ($item_types as $type) {
    echo "<tr><td>{$type->id}</td><td>{$type->item_type_id}</td><td>{$type->name}</td></tr>";
}
echo "</table>";
?>