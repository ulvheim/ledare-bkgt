<?php
/**
 * Item Type Management Class
 *
 * @package BKGT_Inventory
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Item_Type {
    
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
     * Create a new item type
     */
    public static function create($data) {
        self::init_db();
        global $wpdb;
        
        // Validate required fields
        if (empty($data['name']) || empty($data['code'])) {
            return new WP_Error('missing_data', __('Namn och kod krävs.', 'bkgt-inventory'));
        }
        
        // Validate item type code format (4 characters)
        if (!preg_match('/^[A-Z0-9]{4}$/', strtoupper($data['code']))) {
            return new WP_Error('invalid_code', __('Kod måste vara 4 tecken lång och innehålla endast bokstäver och siffror.', 'bkgt-inventory'));
        }
        
        // Check if item type code already exists
        if (self::exists(strtoupper($data['code']))) {
            return new WP_Error('code_exists', __('Artikelkod finns redan.', 'bkgt-inventory'));
        }
        
        $result = $wpdb->insert(
            self::$db->get_item_types_table(),
            array(
                'name' => sanitize_text_field($data['name']),
                'item_type_id' => strtoupper($data['code']),
                'description' => sanitize_textarea_field($data['description'] ?? ''),
                'custom_fields' => json_encode($data['custom_fields'] ?? array()),
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Kunde inte skapa artikeltyp.', 'bkgt-inventory'));
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get item type by ID
     */
    public static function get($id) {
        self::init_db();
        global $wpdb;
        
        $table = self::$db->get_item_types_table();
        $item_type = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        
        if ($item_type) {
            $item_type['custom_fields'] = !empty($item_type['custom_fields']) ? json_decode($item_type['custom_fields'], true) : array();
        }
        
        return $item_type;
    }
    
    /**
     * Get item type by item type ID
     */
    public static function get_by_item_type_id($item_type_id) {
        self::init_db();
        global $wpdb;
        
        $item_type = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM %s WHERE item_type_id = %s",
                self::$db->get_item_types_table(),
                $item_type_id
            ),
            ARRAY_A
        );
        
        if ($item_type) {
            $item_type['custom_fields'] = !empty($item_type['custom_fields']) ? json_decode($item_type['custom_fields'], true) : array();
        }
        
        return $item_type;
    }
    
    /**
     * Get all item types
     */
    public static function get_all() {
        self::init_db();
        global $wpdb;
        
        $item_types = $wpdb->get_results(
            "SELECT it.*, COUNT(i.id) as item_count 
             FROM " . self::$db->get_item_types_table() . " it 
             LEFT JOIN " . self::$db->get_inventory_items_table() . " i ON it.id = i.item_type_id 
             GROUP BY it.id 
             ORDER BY it.name ASC",
            ARRAY_A
        );
        
        // Decode custom fields
        foreach ($item_types as &$item_type) {
            $item_type['custom_fields'] = !empty($item_type['custom_fields']) ? json_decode($item_type['custom_fields'], true) : array();
        }
        
        return $item_types;
    }
    
    /**
     * Update item type
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
            // Validate item type code format
            if (!preg_match('/^[A-Z0-9]{4}$/', strtoupper($data['code']))) {
                return new WP_Error('invalid_code', __('Kod måste vara 4 tecken lång och innehålla endast bokstäver och siffror.', 'bkgt-inventory'));
            }
            
            // Check if item type code already exists (excluding current)
            $existing = self::get_by_item_type_id(strtoupper($data['code']));
            if ($existing && $existing['id'] != $id) {
                return new WP_Error('code_exists', __('Artikelkod finns redan.', 'bkgt-inventory'));
            }
            
            $update_data['item_type_id'] = strtoupper($data['code']);
            $update_format[] = '%s';
        }
        
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
            $update_format[] = '%s';
        }
        
        if (isset($data['custom_fields'])) {
            $update_data['custom_fields'] = json_encode($data['custom_fields']);
            $update_format[] = '%s';
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', __('Inga data att uppdatera.', 'bkgt-inventory'));
        }
        
        $result = $wpdb->update(
            self::$db->get_item_types_table(),
            $update_data,
            array('id' => $id),
            $update_format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Kunde inte uppdatera artikeltyp.', 'bkgt-inventory'));
        }
        
        return true;
    }
    
    /**
     * Delete item type
     */
    public static function delete($id) {
        self::init_db();
        global $wpdb;
        
        // Check if item type is used in any inventory items
        $inventory_items = get_posts(array(
            'post_type' => 'bkgt_inventory_item',
            'meta_query' => array(
                array(
                    'key' => '_bkgt_item_type_id',
                    'value' => $id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (!empty($inventory_items)) {
            return new WP_Error('item_type_in_use', __('Artikeltyp används av utrustningsartiklar och kan inte tas bort.', 'bkgt-inventory'));
        }
        
        $result = $wpdb->delete(
            self::$db->get_item_types_table(),
            array('id' => $id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Kunde inte ta bort artikeltyp.', 'bkgt-inventory'));
        }
        
        return true;
    }
    
    /**
     * Check if item type exists by item type ID
     */
    public static function exists($item_type_code) {
        self::init_db();
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM " . self::$db->get_item_types_table() . " WHERE item_type_id = %s",
                strtoupper($item_type_code)
            )
        );
        
        return $count > 0;
    }
    
    /**
     * Get item types for dropdown
     */
    public static function get_for_select() {
        $item_types = self::get_all();
        $options = array();
        
        foreach ($item_types as $item_type) {
            $options[$item_type['id']] = $item_type['name'] . ' (' . $item_type['item_type_id'] . ')';
        }
        
        return $options;
    }
    
    /**
     * Add custom field to item type
     */
    public static function add_custom_field($item_type_id, $field_name, $field_type = 'text', $field_options = array()) {
        $item_type = self::get($item_type_id);
        
        if (!$item_type) {
            return new WP_Error('item_type_not_found', __('Artikeltyp hittades inte.', 'bkgt-inventory'));
        }
        
        $custom_fields = $item_type['custom_fields'] ?: array();
        
        // Check if field already exists
        if (isset($custom_fields[$field_name])) {
            return new WP_Error('field_exists', __('Fältet finns redan.', 'bkgt-inventory'));
        }
        
        $custom_fields[$field_name] = array(
            'type' => $field_type,
            'options' => $field_options,
        );
        
        return self::update($item_type_id, array('custom_fields' => $custom_fields));
    }
    
    /**
     * Remove custom field from item type
     */
    public static function remove_custom_field($item_type_id, $field_name) {
        $item_type = self::get($item_type_id);
        
        if (!$item_type) {
            return new WP_Error('item_type_not_found', __('Artikeltyp hittades inte.', 'bkgt-inventory'));
        }
        
        $custom_fields = $item_type['custom_fields'] ?: array();
        
        if (!isset($custom_fields[$field_name])) {
            return new WP_Error('field_not_found', __('Fältet hittades inte.', 'bkgt-inventory'));
        }
        
        unset($custom_fields[$field_name]);
        
        return self::update($item_type_id, array('custom_fields' => $custom_fields));
    }
    
    /**
     * Get next available item type code
     */
    public static function get_next_item_type_code() {
        self::init_db();
        global $wpdb;
        
        $table = self::$db->get_item_types_table();
        
        // Get all existing item type codes
        $existing_codes = $wpdb->get_col("SELECT item_type_id FROM {$table} ORDER BY item_type_id DESC");
        
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
