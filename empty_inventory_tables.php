<?php
/**
 * Script to empty all inventory database tables
 * This will remove all data from inventory-related tables to start fresh
 */

require_once('wp-load.php');
global $wpdb;

echo "=== EMPTYING INVENTORY DATABASE TABLES ===\n\n";

// Tables to empty
$tables = array(
    'bkgt_inventory_assignments',
    'bkgt_inventory_items',
    'bkgt_item_types',
    'bkgt_manufacturers'
);

// First, create a simple backup by showing current counts
echo "CURRENT DATA COUNTS:\n";
foreach ($tables as $table) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}{$table}");
    echo "{$table}: {$count} records\n";
}

echo "\n⚠️  WARNING: This will permanently delete all inventory data!\n";
echo "Type 'YES' to confirm: ";

$confirmation = trim(fgets(STDIN));
if ($confirmation !== 'YES') {
    echo "Operation cancelled.\n";
    exit;
}

echo "\nStarting to empty tables...\n";

// Empty tables in correct order (respecting foreign key constraints)
// First disable foreign key checks temporarily
$wpdb->query("SET FOREIGN_KEY_CHECKS = 0");

$tables_order = array(
    'bkgt_inventory_assignments',  // Child table first
    'bkgt_inventory_items',        // Parent table
    'bkgt_item_types',
    'bkgt_manufacturers'
);

foreach ($tables_order as $table) {
    $full_table_name = $wpdb->prefix . $table;
    echo "Emptying {$full_table_name}... ";

    $result = $wpdb->query("TRUNCATE TABLE {$full_table_name}");

    if ($result !== false) {
        echo "✅ Done\n";
    } else {
        echo "❌ Failed: " . $wpdb->last_error . "\n";
    }
}

// Re-enable foreign key checks
$wpdb->query("SET FOREIGN_KEY_CHECKS = 1");

echo "\n=== VERIFICATION ===\n";
echo "NEW DATA COUNTS:\n";
foreach ($tables as $table) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}{$table}");
    echo "{$table}: {$count} records\n";
}

echo "\n✅ All inventory tables have been emptied successfully!\n";
echo "You can now start fresh with your inventory system.\n";
?>