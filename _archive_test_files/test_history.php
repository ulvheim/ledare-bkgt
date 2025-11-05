<?php
require_once('wp-load.php');

echo "=== Testing History Class and Table ===\n\n";

// Check if BKGT_History class exists
if (class_exists('BKGT_History')) {
    echo "✅ BKGT_History class exists\n";
} else {
    echo "❌ BKGT_History class does NOT exist\n";
    exit(1);
}

// Check if create_history_table method exists and is callable
if (method_exists('BKGT_History', 'create_history_table')) {
    echo "✅ create_history_table method exists\n";
    
    // Try to create the table
    try {
        BKGT_History::create_history_table();
        echo "✅ create_history_table executed successfully\n";
    } catch (Exception $e) {
        echo "❌ Error creating history table: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ create_history_table method does NOT exist\n";
}

// Check if history table exists
global $wpdb;
$table_name = $wpdb->prefix . 'bkgt_inventory_history';
$exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

if ($exists) {
    echo "✅ History table exists: $table_name\n";
    
    // Check table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "Table structure:\n";
    foreach ($columns as $column) {
        echo "  - {$column->Field}: {$column->Type}\n";
    }
    
    // Count existing records
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "Records in table: $count\n";
    
} else {
    echo "❌ History table does NOT exist: $table_name\n";
}

// Test get_item_history method
if (method_exists('BKGT_History', 'get_item_history')) {
    echo "\n✅ get_item_history method exists\n";
    
    // Test with a non-existent item ID
    try {
        $history = BKGT_History::get_item_history(99999, 5);
        if (is_array($history)) {
            echo "✅ get_item_history returned array (empty for non-existent item)\n";
            echo "History entries returned: " . count($history) . "\n";
        } else {
            echo "❌ get_item_history did not return array\n";
        }
    } catch (Exception $e) {
        echo "❌ Error calling get_item_history: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ get_item_history method does NOT exist\n";
}

echo "\n=== Admin Class Test ===\n";

// Test if admin class can be loaded and render_history_meta_box works
if (class_exists('BKGT_Inventory_Admin')) {
    echo "✅ BKGT_Inventory_Admin class exists\n";
    
    if (method_exists('BKGT_Inventory_Admin', 'render_history_meta_box')) {
        echo "✅ render_history_meta_box method exists\n";
    } else {
        echo "❌ render_history_meta_box method does NOT exist\n";
    }
} else {
    echo "❌ BKGT_Inventory_Admin class does NOT exist\n";
}

echo "\n=== Summary ===\n";
echo "If all checks above are green, the history functionality should work.\n";
echo "Try accessing the inventory admin again.\n";