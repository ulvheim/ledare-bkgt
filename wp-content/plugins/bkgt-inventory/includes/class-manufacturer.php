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
            global $bkgt_inventory_db;
            self::$db = $bkgt_inventory_db;
        }
    }
    
    /**
     * Create a new manufacturer
     */
    public static function create($data) {
        self::init_db();
        global $wpdb;
        
        // Validate required fields
        if (empty($data['name']) || empty($data['code'])) {
            return new WP_Error('missing_data', __('Namn och kod krävs.', 'bkgt-inventory'));
        }
        
        // Validate manufacturer ID format (numerical only)
        if (!preg_match('/^\d+$/', $data['code']) || intval($data['code']) <= 0) {
            return new WP_Error('invalid_code', __('Kod måste vara ett positivt heltal.', 'bkgt-inventory'));
        }
        
        // Check if manufacturer code already exists
        if (self::exists(intval($data['code']))) {
            return new WP_Error('code_exists', __('Tillverkarkod finns redan.', 'bkgt-inventory'));
        }
        
        $result = $wpdb->insert(
            self::$db->get_manufacturers_table(),
            array(
                'name' => sanitize_text_field($data['name']),
                'manufacturer_id' => intval($data['code']),
                'contact_info' => sanitize_textarea_field($data['contact_info'] ?? ''),
            ),
            array('%s', '%d', '%s')
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
        
        $table = self::$db->get_manufacturers_table();
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d",
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
        
        $manufacturers = $wpdb->get_results(
            "SELECT m.*, COUNT(i.id) as item_count 
             FROM " . self::$db->get_manufacturers_table() . " m 
             LEFT JOIN " . self::$db->get_inventory_items_table() . " i ON m.id = i.manufacturer_id 
             GROUP BY m.id 
             ORDER BY m.name ASC",
            ARRAY_A
        );
        
        return $manufacturers;
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
        
        if (isset($data['code'])) {
            // Validate manufacturer code format
            if (!preg_match('/^[A-Z0-9]{4}$/', strtoupper($data['code']))) {
                return new WP_Error('invalid_code', __('Kod måste vara 4 tecken lång och innehålla endast bokstäver och siffror.', 'bkgt-inventory'));
            }
            
            // Check if manufacturer code already exists (excluding current)
            $existing = self::get_by_manufacturer_id(strtoupper($data['code']));
            if ($existing && $existing['id'] != $id) {
                return new WP_Error('code_exists', __('Tillverkarkod finns redan.', 'bkgt-inventory'));
            }
            
            $update_data['manufacturer_id'] = strtoupper($data['code']);
            $update_format[] = '%s';
        }
        
        if (isset($data['contact_info'])) {
            $update_data['contact_info'] = sanitize_textarea_field($data['contact_info']);
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
    public static function exists($manufacturer_code) {
        self::init_db();
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM " . self::$db->get_manufacturers_table() . " WHERE manufacturer_id = %s",
                strtoupper($manufacturer_code)
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
    
    /**
     * Get next available manufacturer code
     */
    public static function get_next_manufacturer_code() {
        self::init_db();
        global $wpdb;
        
        $table = self::$db->get_manufacturers_table();
        
        // Get all existing manufacturer codes
        $existing_codes = $wpdb->get_col("SELECT manufacturer_id FROM {$table} ORDER BY manufacturer_id DESC");
        
        if (empty($existing_codes)) {
            return '0001';
        }
        
        // Find the highest numeric code
        $max_code = 0;
        foreach ($existing_codes as $code) {
            // Remove any non-numeric characters and convert to int
            $numeric_code = intval(preg_replace('/[^0-9]/', '', $code));
            $max_code = max($max_code, $numeric_code);
        }
        
        // Increment and format as 4-digit code
        $next_code = $max_code + 1;
        return str_pad($next_code, 4, '0', STR_PAD_LEFT);
    }
}
