<?php
/**
 * Database Management Class
 *
 * @package BKGT_Inventory
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Inventory_Database {
    
    /**
     * Manufacturers table name
     */
    private $manufacturers_table;
    
    /**
     * Item types table name
     */
    private $item_types_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        $this->manufacturers_table = $wpdb->prefix . 'bkgt_manufacturers';
        $this->item_types_table = $wpdb->prefix . 'bkgt_item_types';
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Manufacturers table
        $manufacturers_sql = "CREATE TABLE {$this->manufacturers_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            manufacturer_id varchar(4) NOT NULL UNIQUE,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY manufacturer_id (manufacturer_id)
        ) $charset_collate;";
        
        // Item types table
        $item_types_sql = "CREATE TABLE {$this->item_types_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            item_type_id varchar(4) NOT NULL UNIQUE,
            custom_fields longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY item_type_id (item_type_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($manufacturers_sql);
        dbDelta($item_types_sql);
        
        // Update database version
        update_option('bkgt_inventory_db_version', '1.0.0');
    }
    
    /**
     * Drop database tables
     */
    public function drop_tables() {
        global $wpdb;
        
        $wpdb->query("DROP TABLE IF EXISTS {$this->manufacturers_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->item_types_table}");
        
        delete_option('bkgt_inventory_db_version');
    }
    
    /**
     * Get manufacturers table name
     */
    public function get_manufacturers_table() {
        return $this->manufacturers_table;
    }
    
    /**
     * Get item types table name
     */
    public function get_item_types_table() {
        return $this->item_types_table;
    }
    
    /**
     * Check if tables exist
     */
    public function tables_exist() {
        global $wpdb;
        
        $manufacturers_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->manufacturers_table}'") === $this->manufacturers_table;
        $item_types_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->item_types_table}'") === $this->item_types_table;
        
        return $manufacturers_exists && $item_types_exists;
    }
}
