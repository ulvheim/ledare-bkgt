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
     * Inventory items table name
     */
    private $inventory_items_table;
    
    /**
     * Assignments table name
     */
    private $assignments_table;
    
    /**
     * Locations table name
     */
    private $locations_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        $this->manufacturers_table = $wpdb->prefix . 'bkgt_manufacturers';
        $this->item_types_table = $wpdb->prefix . 'bkgt_item_types';
        $this->inventory_items_table = $wpdb->prefix . 'bkgt_inventory_items';
        $this->assignments_table = $wpdb->prefix . 'bkgt_inventory_assignments';
        $this->locations_table = $wpdb->prefix . 'bkgt_locations';
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
            manufacturer_id int(11) NOT NULL UNIQUE,
            contact_info text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY manufacturer_id (manufacturer_id)
        ) $charset_collate;";
        
        // Item types table
        $item_types_sql = "CREATE TABLE {$this->item_types_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            item_type_id int(11) NOT NULL UNIQUE,
            description text,
            custom_fields longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY item_type_id (item_type_id)
        ) $charset_collate;";
        
        // Inventory items table
        $inventory_items_sql = "CREATE TABLE {$this->inventory_items_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            unique_identifier varchar(20) NOT NULL UNIQUE,
            manufacturer_id int(11) NOT NULL,
            item_type_id int(11) NOT NULL,
            title varchar(255) NOT NULL,
            size varchar(100),
            storage_location varchar(255),
            condition_status enum('normal','needs_repair','repaired','reported_lost','scrapped') DEFAULT 'normal',
            condition_date datetime,
            condition_reason text,
            metadata longtext,
            sticker_code varchar(50),
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_identifier (unique_identifier),
            KEY manufacturer_id (manufacturer_id),
            KEY item_type_id (item_type_id)
        ) $charset_collate;";
        
        // Assignments table
        $assignments_sql = "CREATE TABLE {$this->assignments_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            item_id bigint(20) NOT NULL,
            assignee_id bigint(20) DEFAULT NULL,
            assignee_name varchar(255) DEFAULT NULL,
            assignment_date datetime DEFAULT CURRENT_TIMESTAMP,
            due_date date DEFAULT NULL,
            return_date date DEFAULT NULL,
            location_id int(11) DEFAULT NULL,
            notes text,
            PRIMARY KEY (id),
            KEY item_id (item_id),
            KEY assignee_id (assignee_id),
            KEY assignment_date (assignment_date),
            KEY due_date (due_date)
        ) $charset_collate;";
        
        // Locations table
        $locations_sql = "CREATE TABLE {$this->locations_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL UNIQUE,
            parent_id int(11) DEFAULT NULL,
            location_type enum('storage','repair','locker','warehouse','other') DEFAULT 'storage',
            address text,
            contact_person varchar(255),
            contact_phone varchar(50),
            contact_email varchar(255),
            capacity int(11) DEFAULT NULL,
            access_restrictions text,
            notes text,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY parent_id (parent_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($manufacturers_sql);
        dbDelta($item_types_sql);
        dbDelta($inventory_items_sql);
        dbDelta($assignments_sql);
        dbDelta($locations_sql);
        
        // Update database version
        update_option('bkgt_inventory_db_version', '1.2.0');
    }
    
    /**
     * Drop database tables
     */
    public function drop_tables() {
        global $wpdb;
        
        $wpdb->query("DROP TABLE IF EXISTS {$this->assignments_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->inventory_items_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->item_types_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->manufacturers_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->locations_table}");
        
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
     * Get inventory items table name
     */
    public function get_inventory_items_table() {
        return $this->inventory_items_table;
    }
    
    /**
     * Get assignments table name
     */
    public function get_assignments_table() {
        return $this->assignments_table;
    }
    
    /**
     * Get locations table name
     */
    public function get_locations_table() {
        return $this->locations_table;
    }
    
    /**
     * Check if tables exist
     */
    public function tables_exist() {
        global $wpdb;
        
        $manufacturers_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->manufacturers_table}'") === $this->manufacturers_table;
        $item_types_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->item_types_table}'") === $this->item_types_table;
        $inventory_items_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->inventory_items_table}'") === $this->inventory_items_table;
        $assignments_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->assignments_table}'") === $this->assignments_table;
        $locations_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->locations_table}'") === $this->locations_table;
        
        return $manufacturers_exists && $item_types_exists && $inventory_items_exists && $assignments_exists && $locations_exists;
    }
}
