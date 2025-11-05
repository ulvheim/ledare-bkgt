<?php
require_once('wp-load.php');
global $wpdb;

echo "=== Removing Fake Inventory Data ===\n\n";

$inventory_db = new BKGT_Inventory_Database();

// First, verify we have real data in custom post types
$real_items = get_posts(array(
    'post_type' => 'bkgt_inventory_item',
    'post_status' => 'publish',
    'numberposts' => -1
));

echo "Real inventory items (custom post types): " . count($real_items) . "\n";
foreach ($real_items as $item) {
    $unique_id = get_post_meta($item->ID, '_bkgt_unique_id', true);
    echo "  - {$item->post_title} (ID: $unique_id)\n";
}

echo "\nFake data in database tables:\n";

// Check each table
$tables_to_clear = array(
    'inventory_items' => $inventory_db->get_inventory_items_table(),
    'manufacturers' => $inventory_db->get_manufacturers_table(),
    'item_types' => $inventory_db->get_item_types_table(),
    'assignments' => $inventory_db->get_assignments_table(),
    'locations' => $inventory_db->get_locations_table()
);

foreach ($tables_to_clear as $name => $table) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    echo "  $name: $count records\n";
}

echo "\n=== Clearing Fake Data ===\n";

// Clear the tables (in correct order due to foreign keys)
$clear_order = array('assignments', 'inventory_items', 'item_types', 'manufacturers', 'locations');

foreach ($clear_order as $table_name) {
    $table = $tables_to_clear[$table_name];
    $result = $wpdb->query("DELETE FROM $table");

    if ($result !== false) {
        echo "✅ Cleared $table_name table ($result records deleted)\n";
    } else {
        echo "❌ Failed to clear $table_name table: " . $wpdb->last_error . "\n";
    }
}

echo "\n=== Verification ===\n";

// Verify tables are empty
foreach ($tables_to_clear as $name => $table) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    echo "  $name: $count records remaining\n";
}

// Test that shortcode still works
echo "\n=== Testing Shortcode ===\n";
if (function_exists('bkgt_inventory_shortcode')) {
    $output = bkgt_inventory_shortcode(array());

    if (strpos($output, '0005-0005-00001') !== false) {
        echo "✅ Shortcode still shows real data\n";
    } else {
        echo "❌ Shortcode no longer shows real data\n";
    }

    if (strpos($output, 'HELM-001') !== false) {
        echo "❌ Shortcode still shows fake data\n";
    } else {
        echo "✅ Shortcode no longer shows fake data\n";
    }
} else {
    echo "❌ Shortcode function not found\n";
}

echo "\n=== Done ===\n";
?>