<?php
require_once('wp-load.php');

echo "Checking plugin activation...\n";
$active_plugins = get_option('active_plugins', array());
echo "Currently active plugins:\n";
foreach ($active_plugins as $plugin) {
    echo "- $plugin\n";
}

if (in_array('bkgt-inventory/bkgt-inventory.php', $active_plugins)) {
    echo "Plugin is already ACTIVE\n";
} else {
    echo "Plugin is NOT ACTIVE - activating it now...\n";
    
    // Add to active plugins
    $active_plugins[] = 'bkgt-inventory/bkgt-inventory.php';
    update_option('active_plugins', $active_plugins);
    
    echo "Plugin activated!\n";
}

// Verify it's active
$active_plugins = get_option('active_plugins', array());
if (in_array('bkgt-inventory/bkgt-inventory.php', $active_plugins)) {
    echo "✅ SUCCESS: Plugin is now active\n";
} else {
    echo "❌ FAILED: Plugin activation failed\n";
}
    echo "✅ SUCCESS: Plugin is now active\n";
} else {
    echo "❌ FAILED: Plugin activation failed\n";
}
?>
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