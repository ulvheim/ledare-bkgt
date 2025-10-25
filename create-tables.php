<?php
/**
 * Create Database Tables Script
 * This script manually creates the inventory database tables
 */

// Load WordPress
require_once('wp-load.php');

global $wpdb;

// Table names
$manufacturers_table = $wpdb->prefix . 'bkgt_manufacturers';
$item_types_table = $wpdb->prefix . 'bkgt_item_types';

$charset_collate = $wpdb->get_charset_collate();

echo "Creating database tables...\n";

// Drop tables if they exist
$wpdb->query("DROP TABLE IF EXISTS $manufacturers_table");
$wpdb->query("DROP TABLE IF EXISTS $item_types_table");

// Manufacturers table
$manufacturers_sql = "CREATE TABLE $manufacturers_table (
    id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    manufacturer_id varchar(4) NOT NULL UNIQUE,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY manufacturer_id (manufacturer_id)
) $charset_collate;";

$result = $wpdb->query($manufacturers_sql);
if ($result === false) {
    echo "✗ Failed to create manufacturers table: " . $wpdb->last_error . "\n";
} else {
    echo "✓ Manufacturers table created successfully\n";
}

// Item types table
$item_types_sql = "CREATE TABLE $item_types_table (
    id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    item_type_id varchar(4) NOT NULL UNIQUE,
    custom_fields longtext,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY item_type_id (item_type_id)
) $charset_collate;";

$result = $wpdb->query($item_types_sql);
if ($result === false) {
    echo "✗ Failed to create item types table: " . $wpdb->last_error . "\n";
} else {
    echo "✓ Item types table created successfully\n";
}

// Get the inventory plugin instance
$inventory = BKGT_Inventory::get_instance();

// Create default data using the public method
$inventory->update_default_data();

echo "Default data created successfully!\n";
?>