<?php
// Load WordPress
require_once('wp-load.php');

// Force recreate inventory tables
global $bkgt_inventory_db;
if ($bkgt_inventory_db) {
    echo "Recreating inventory tables...\n";
    $bkgt_inventory_db->create_tables();
    echo "Tables recreated successfully.\n";

    // Also recreate history table
    BKGT_History::create_history_table();
    echo "History table recreated successfully.\n";
} else {
    echo "BKGT Inventory Database not found.\n";
}