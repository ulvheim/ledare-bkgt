<?php
/**
 * BKGT API Diagnostic Script
 * Run this on the server to check plugin status and API endpoints
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "<h1>BKGT API Diagnostic</h1>";

// Check if plugins are active
echo "<h2>Plugin Status</h2>";
$plugins = array(
    'bkgt-core/bkgt-core.php' => 'BKGT Core',
    'bkgt-data-scraping/bkgt-data-scraping.php' => 'BKGT Data Scraping',
    'bkgt-inventory/bkgt-inventory.php' => 'BKGT Inventory',
    'bkgt-api/bkgt-api.php' => 'BKGT API'
);

foreach ($plugins as $file => $name) {
    $active = is_plugin_active($file);
    echo "<p><strong>$name:</strong> " . ($active ? '<span style="color:green">ACTIVE</span>' : '<span style="color:red">INACTIVE</span>') . "</p>";
}

// Check database tables
echo "<h2>Database Tables</h2>";
global $wpdb;
$tables = array(
    'bkgt_inventory_items',
    'bkgt_manufacturers',
    'bkgt_item_types',
    'bkgt_inventory_assignments',
    'bkgt_locations'
);

foreach ($tables as $table) {
    $table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    echo "<p><strong>$table:</strong> " . ($exists ? '<span style="color:green">EXISTS</span>' : '<span style="color:red">MISSING</span>') . "</p>";
}

// Check API endpoints
echo "<h2>API Endpoints</h2>";
$endpoints = array(
    'wp-json/bkgt/v1/equipment',
    'wp-json/bkgt/v1/equipment/preview-identifier',
    'wp-json/bkgt/v1/equipment/manufacturers',
    'wp-json/bkgt/v1/equipment/types'
);

foreach ($endpoints as $endpoint) {
    $url = home_url($endpoint);
    $response = wp_remote_head($url);
    $status = wp_remote_retrieve_response_code($response);
    $color = ($status == 200 || $status == 401) ? 'green' : 'red';
    echo "<p><strong>$endpoint:</strong> <span style='color:$color'>HTTP $status</span></p>";
}

// Test preview identifier endpoint
echo "<h2>Test Preview Identifier</h2>";
$url = home_url('wp-json/bkgt/v1/equipment/preview-identifier?manufacturer_id=1&item_type_id=1');
$response = wp_remote_get($url);
$status = wp_remote_retrieve_response_code($response);
$body = wp_remote_retrieve_body($response);

echo "<p><strong>URL:</strong> $url</p>";
echo "<p><strong>Status:</strong> $status</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($body) . "</pre>";

// Check if BKGT_Inventory_Item class exists
echo "<h2>Class Availability</h2>";
$classes = array(
    'BKGT_Inventory_Item',
    'BKGT_Manufacturer',
    'BKGT_Item_Type',
    'BKGT_Assignment'
);

foreach ($classes as $class) {
    $exists = class_exists($class);
    echo "<p><strong>$class:</strong> " . ($exists ? '<span style="color:green">EXISTS</span>' : '<span style="color:red">MISSING</span>') . "</p>";
}
?>