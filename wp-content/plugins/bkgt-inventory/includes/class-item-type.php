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
    public static function create($name, $item_type_id, $custom_fields = array()) {
        self::init_db();
        global $wpdb;
        
        // Validate item type ID format (4 digits)
        if (!preg_match('/^\d{4}$/', $item_type_id)) {
            return new WP_Error('invalid_item_type_id', __('Artikeltyp-ID måste vara 4 siffror.', 'bkgt-inventory'));
        }
        
        // Check if item type ID already exists
        if (self::exists($item_type_id)) {
            return new WP_Error('item_type_id_exists', __('Artikeltyp-ID finns redan.', 'bkgt-inventory'));
        }
        
        $result = $wpdb->insert(
            self::$db->get_item_types_table(),
            array(
                'name' => sanitize_text_field($name),
                'item_type_id' => $item_type_id,
                'custom_fields' => json_encode($custom_fields),
            ),
            array('%s', '%s', '%s')
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
        
        $item_type = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM %s WHERE id = %d",
                self::$db->get_item_types_table(),
                $id
            ),
            ARRAY_A
        );
        
        if ($item_type) {
            $item_type['custom_fields'] = json_decode($item_type['custom_fields'], true);
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
            $item_type['custom_fields'] = json_decode($item_type['custom_fields'], true);
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
            "SELECT * FROM " . self::$db->get_item_types_table() . " ORDER BY name ASC",
            ARRAY_A
        );
        
        // Decode custom fields
        foreach ($item_types as &$item_type) {
            $item_type['custom_fields'] = json_decode($item_type['custom_fields'], true);
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
        
        if (isset($data['item_type_id'])) {
            // Validate item type ID format
            if (!preg_match('/^\d{4}$/', $data['item_type_id'])) {
                return new WP_Error('invalid_item_type_id', __('Artikeltyp-ID måste vara 4 siffror.', 'bkgt-inventory'));
            }
            
            // Check if item type ID already exists (excluding current)
            $existing = self::get_by_item_type_id($data['item_type_id']);
            if ($existing && $existing['id'] != $id) {
                return new WP_Error('item_type_id_exists', __('Artikeltyp-ID finns redan.', 'bkgt-inventory'));
            }
            
            $update_data['item_type_id'] = $data['item_type_id'];
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
    public static function exists($item_type_id) {
        self::init_db();
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM " . self::$db->get_item_types_table() . " WHERE item_type_id = %s",
                $item_type_id
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
}
