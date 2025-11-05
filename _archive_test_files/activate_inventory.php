<?php
require_once('wp-load.php');

echo "Checking plugin activation...\n";
$active_plugins = get_option('active_plugins', array());
if (in_array('bkgt-inventory/bkgt-inventory.php', $active_plugins)) {
    echo "Plugin is ACTIVE\n";
} else {
    echo "Plugin is NOT ACTIVE\n";
    echo "Activating plugin...\n";
    
    // Add to active plugins
    $active_plugins[] = 'bkgt-inventory/bkgt-inventory.php';
    update_option('active_plugins', $active_plugins);
    
    // Run activation hook
    if (function_exists('bkgt_inventory_activate')) {
        bkgt_inventory_activate();
        echo "Plugin activated and tables created\n";
    } else {
        echo "Activation function not found\n";
    }
}

echo "\nChecking database tables...\n";
global $wpdb;
$tables = array(
    'wp_bkgt_manufacturers',
    'wp_bkgt_item_types', 
    'wp_bkgt_inventory_items',
    'wp_bkgt_assignments',
    'wp_bkgt_locations'
);

foreach ($tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    echo "$table: " . ($exists ? "EXISTS" : "MISSING") . "\n";
}

echo "\nChecking inventory items count...\n";
$count = $wpdb->get_var("SELECT COUNT(*) FROM wp_bkgt_inventory_items");
echo "Items in database: $count\n";
?>