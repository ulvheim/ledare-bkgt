<?php
require_once('wp-load.php');

echo "Creating inventory tables...\n";

// Include the inventory database class and create tables
if (function_exists('bkgt_inventory_create_tables')) {
    bkgt_inventory_create_tables();
    echo "✅ Inventory tables created successfully\n";
} else {
    echo "❌ bkgt_inventory_create_tables function not found\n";
}
?>