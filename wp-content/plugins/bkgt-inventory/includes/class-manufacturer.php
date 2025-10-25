<?php
/**
 * Manufacturer Management Class
 *
 * @package BKGT_Inventory
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Manufacturer {
    
    /**
     * Database instance
     */
    private static $db;
    
    /**
     * Initialize database connection
     */
    private static function init_db() {
        if (!self::$db) {
            self::$db = bkgt_inventory()->db;
        }
    }
    
    /**
     * Create a new manufacturer
     */
    public static function create($name, $manufacturer_id) {
        self::init_db();
        global $wpdb;
        
        // Validate manufacturer ID format (4 digits)
        if (!preg_match('/^\d{4}$/', $manufacturer_id)) {
            return new WP_Error('invalid_manufacturer_id', __('Tillverkare-ID måste vara 4 siffror.', 'bkgt-inventory'));
        }
        
        // Check if manufacturer ID already exists
        if (self::exists($manufacturer_id)) {
            return new WP_Error('manufacturer_id_exists', __('Tillverkare-ID finns redan.', 'bkgt-inventory'));
        }
        
        $result = $wpdb->insert(
            self::$db->get_manufacturers_table(),
            array(
                'name' => sanitize_text_field($name),
                'manufacturer_id' => $manufacturer_id,
            ),
            array('%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Kunde inte skapa tillverkare.', 'bkgt-inventory'));
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get manufacturer by ID
     */
    public static function get($id) {
        self::init_db();
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM %s WHERE id = %d",
                self::$db->get_manufacturers_table(),
                $id
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get manufacturer by manufacturer ID
     */
    public static function get_by_manufacturer_id($manufacturer_id) {
        self::init_db();
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM %s WHERE manufacturer_id = %s",
                self::$db->get_manufacturers_table(),
                $manufacturer_id
            ),
            ARRAY_A
        );
    }
    
    /**
     * Get all manufacturers
     */
    public static function get_all() {
        self::init_db();
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %s ORDER BY name ASC",
                self::$db->get_manufacturers_table()
            ),
            ARRAY_A
        );
    }
    
    /**
     * Update manufacturer
     */
    public static function update($id, $data) {
        self::init_db();
        global $wpdb;
        
        $update_data = array();
        $update_format = array();
        
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
            $update_format[] = '%s';
        }
        
        if (isset($data['manufacturer_id'])) {
            // Validate manufacturer ID format
            if (!preg_match('/^\d{4}$/', $data['manufacturer_id'])) {
                return new WP_Error('invalid_manufacturer_id', __('Tillverkare-ID måste vara 4 siffror.', 'bkgt-inventory'));
            }
            
            // Check if manufacturer ID already exists (excluding current)
            $existing = self::get_by_manufacturer_id($data['manufacturer_id']);
            if ($existing && $existing['id'] != $id) {
                return new WP_Error('manufacturer_id_exists', __('Tillverkare-ID finns redan.', 'bkgt-inventory'));
            }
            
            $update_data['manufacturer_id'] = $data['manufacturer_id'];
            $update_format[] = '%s';
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', __('Inga data att uppdatera.', 'bkgt-inventory'));
        }
        
        $result = $wpdb->update(
            self::$db->get_manufacturers_table(),
            $update_data,
            array('id' => $id),
            $update_format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Kunde inte uppdatera tillverkare.', 'bkgt-inventory'));
        }
        
        return true;
    }
    
    /**
     * Delete manufacturer
     */
    public static function delete($id) {
        self::init_db();
        global $wpdb;
        
        // Check if manufacturer is used in any inventory items
        $inventory_items = get_posts(array(
            'post_type' => 'bkgt_inventory_item',
            'meta_query' => array(
                array(
                    'key' => '_bkgt_manufacturer_id',
                    'value' => $id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (!empty($inventory_items)) {
            return new WP_Error('manufacturer_in_use', __('Tillverkare används av utrustningsartiklar och kan inte tas bort.', 'bkgt-inventory'));
        }
        
        $result = $wpdb->delete(
            self::$db->get_manufacturers_table(),
            array('id' => $id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Kunde inte ta bort tillverkare.', 'bkgt-inventory'));
        }
        
        return true;
    }
    
    /**
     * Check if manufacturer exists by manufacturer ID
     */
    public static function exists($manufacturer_id) {
        self::init_db();
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM " . self::$db->get_manufacturers_table() . " WHERE manufacturer_id = %s",
                $manufacturer_id
            )
        );
        
        return $count > 0;
    }
    
    /**
     * Get manufacturers for dropdown
     */
    public static function get_for_select() {
        $manufacturers = self::get_all();
        $options = array();
        
        foreach ($manufacturers as $manufacturer) {
            $options[$manufacturer['id']] = $manufacturer['name'] . ' (' . $manufacturer['manufacturer_id'] . ')';
        }
        
        return $options;
    }
}
