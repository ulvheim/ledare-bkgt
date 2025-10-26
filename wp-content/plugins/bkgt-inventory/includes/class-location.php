<?php
/**
 * Location Management Class
 *
 * @package BKGT_Inventory
 * @since 1.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Location {
    
    /**
     * Location types
     */
    const TYPE_STORAGE = 'storage';
    const TYPE_REPAIR = 'repair';
    const TYPE_LOCKER = 'locker';
    const TYPE_WAREHOUSE = 'warehouse';
    const TYPE_OTHER = 'other';
    
    /**
     * Get all locations
     */
    public static function get_all_locations($include_inactive = false) {
        global $wpdb;
        
        $db = BKGT_Inventory_Database::get_instance();
        $locations_table = $db->get_locations_table();
        
        $where = $include_inactive ? '' : 'WHERE is_active = 1';
        
        $sql = "SELECT * FROM {$locations_table} {$where} ORDER BY name ASC";
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        if (!$results) {
            return array();
        }
        
        // Build hierarchical structure
        return self::build_hierarchy($results);
    }
    
    /**
     * Get location by ID
     */
    public static function get_location($location_id) {
        global $wpdb;
        
        $db = BKGT_Inventory_Database::get_instance();
        $locations_table = $db->get_locations_table();
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$locations_table} WHERE id = %d",
            $location_id
        );
        
        $result = $wpdb->get_row($sql, ARRAY_A);
        
        if (!$result) {
            return false;
        }
        
        // Get parent location if exists
        if ($result['parent_id']) {
            $result['parent'] = self::get_location($result['parent_id']);
        }
        
        // Get child locations
        $result['children'] = self::get_child_locations($location_id);
        
        return $result;
    }
    
    /**
     * Get child locations
     */
    public static function get_child_locations($parent_id) {
        global $wpdb;
        
        $db = BKGT_Inventory_Database::get_instance();
        $locations_table = $db->get_locations_table();
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$locations_table} WHERE parent_id = %d AND is_active = 1 ORDER BY name ASC",
            $parent_id
        );
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Create new location
     */
    public static function create_location($data) {
        global $wpdb;
        
        $db = BKGT_Inventory_Database::get_instance();
        $locations_table = $db->get_locations_table();
        
        // Validate required fields
        if (empty($data['name'])) {
            return new WP_Error('missing_name', __('Platsnamn är obligatoriskt.', 'bkgt-inventory'));
        }
        
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = sanitize_title($data['name']);
        }
        
        // Ensure slug is unique
        $original_slug = $data['slug'];
        $counter = 1;
        while (self::slug_exists($data['slug'])) {
            $data['slug'] = $original_slug . '-' . $counter;
            $counter++;
        }
        
        // Set defaults
        $defaults = array(
            'parent_id' => null,
            'location_type' => self::TYPE_STORAGE,
            'address' => '',
            'contact_person' => '',
            'contact_phone' => '',
            'contact_email' => '',
            'capacity' => null,
            'access_restrictions' => '',
            'notes' => '',
            'is_active' => 1
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Validate parent exists if provided
        if ($data['parent_id'] && !self::location_exists($data['parent_id'])) {
            return new WP_Error('invalid_parent', __('Föräldraplats finns inte.', 'bkgt-inventory'));
        }
        
        // Validate email if provided
        if (!empty($data['contact_email']) && !is_email($data['contact_email'])) {
            return new WP_Error('invalid_email', __('Ogiltig e-postadress.', 'bkgt-inventory'));
        }
        
        // Insert location
        $result = $wpdb->insert(
            $locations_table,
            array(
                'name' => sanitize_text_field($data['name']),
                'slug' => sanitize_title($data['slug']),
                'parent_id' => $data['parent_id'] ? intval($data['parent_id']) : null,
                'location_type' => $data['location_type'],
                'address' => sanitize_textarea_field($data['address']),
                'contact_person' => sanitize_text_field($data['contact_person']),
                'contact_phone' => sanitize_text_field($data['contact_phone']),
                'contact_email' => sanitize_email($data['contact_email']),
                'capacity' => $data['capacity'] ? intval($data['capacity']) : null,
                'access_restrictions' => sanitize_textarea_field($data['access_restrictions']),
                'notes' => sanitize_textarea_field($data['notes']),
                'is_active' => intval($data['is_active'])
            ),
            array('%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Kunde inte skapa plats.', 'bkgt-inventory'));
        }
        
        $location_id = $wpdb->insert_id;
        
        // Clear any relevant caches
        wp_cache_flush();
        
        return $location_id;
    }
    
    /**
     * Update location
     */
    public static function update_location($location_id, $data) {
        global $wpdb;
        
        $db = BKGT_Inventory_Database::get_instance();
        $locations_table = $db->get_locations_table();
        
        // Check if location exists
        if (!self::location_exists($location_id)) {
            return new WP_Error('location_not_found', __('Plats hittades inte.', 'bkgt-inventory'));
        }
        
        // Validate required fields
        if (empty($data['name'])) {
            return new WP_Error('missing_name', __('Platsnamn är obligatoriskt.', 'bkgt-inventory'));
        }
        
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = sanitize_title($data['name']);
        }
        
        // Ensure slug is unique (excluding current location)
        $original_slug = $data['slug'];
        $counter = 1;
        while (self::slug_exists($data['slug'], $location_id)) {
            $data['slug'] = $original_slug . '-' . $counter;
            $counter++;
        }
        
        // Validate parent exists if provided and not self-referencing
        if ($data['parent_id']) {
            if ($data['parent_id'] == $location_id) {
                return new WP_Error('invalid_parent', __('En plats kan inte vara sin egen förälder.', 'bkgt-inventory'));
            }
            if (!self::location_exists($data['parent_id'])) {
                return new WP_Error('invalid_parent', __('Föräldraplats finns inte.', 'bkgt-inventory'));
            }
        }
        
        // Validate email if provided
        if (!empty($data['contact_email']) && !is_email($data['contact_email'])) {
            return new WP_Error('invalid_email', __('Ogiltig e-postadress.', 'bkgt-inventory'));
        }
        
        // Update location
        $result = $wpdb->update(
            $locations_table,
            array(
                'name' => sanitize_text_field($data['name']),
                'slug' => sanitize_title($data['slug']),
                'parent_id' => $data['parent_id'] ? intval($data['parent_id']) : null,
                'location_type' => $data['location_type'],
                'address' => sanitize_textarea_field($data['address']),
                'contact_person' => sanitize_text_field($data['contact_person']),
                'contact_phone' => sanitize_text_field($data['contact_phone']),
                'contact_email' => sanitize_email($data['contact_email']),
                'capacity' => $data['capacity'] ? intval($data['capacity']) : null,
                'access_restrictions' => sanitize_textarea_field($data['access_restrictions']),
                'notes' => sanitize_textarea_field($data['notes']),
                'is_active' => intval($data['is_active'])
            ),
            array('id' => $location_id),
            array('%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Kunde inte uppdatera plats.', 'bkgt-inventory'));
        }
        
        // Clear any relevant caches
        wp_cache_flush();
        
        return true;
    }
    
    /**
     * Delete location
     */
    public static function delete_location($location_id) {
        global $wpdb;
        
        $db = BKGT_Inventory_Database::get_instance();
        $locations_table = $db->get_locations_table();
        
        // Check if location exists
        if (!self::location_exists($location_id)) {
            return new WP_Error('location_not_found', __('Plats hittades inte.', 'bkgt-inventory'));
        }
        
        // Check if location has children
        $children = self::get_child_locations($location_id);
        if (!empty($children)) {
            return new WP_Error('has_children', __('Kan inte ta bort plats med underplatser. Flytta eller ta bort underplatser först.', 'bkgt-inventory'));
        }
        
        // Check if location has assigned items
        $assigned_items = self::get_location_item_count($location_id);
        if ($assigned_items > 0) {
            return new WP_Error('has_items', __('Kan inte ta bort plats med tilldelade artiklar. Flytta artiklar först.', 'bkgt-inventory'));
        }
        
        // Soft delete by setting inactive
        $result = $wpdb->update(
            $locations_table,
            array('is_active' => 0),
            array('id' => $location_id),
            array('%d'),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Kunde inte ta bort plats.', 'bkgt-inventory'));
        }
        
        // Clear any relevant caches
        wp_cache_flush();
        
        return true;
    }
    
    /**
     * Get location item count
     */
    public static function get_location_item_count($location_id) {
        global $wpdb;
        
        $db = BKGT_Inventory_Database::get_instance();
        $assignments_table = $db->get_assignments_table();
        
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$assignments_table} 
             WHERE assignee_type = 'location' AND assignee_id = %d AND unassigned_date IS NULL",
            $location_id
        );
        
        return (int) $wpdb->get_var($sql);
    }
    
    /**
     * Get location statistics
     */
    public static function get_location_stats($location_id) {
        global $wpdb;
        
        $db = BKGT_Inventory_Database::get_instance();
        $assignments_table = $db->get_assignments_table();
        $inventory_items_table = $db->get_inventory_items_table();
        
        $stats = array(
            'total_items' => 0,
            'items_by_condition' => array(),
            'recent_assignments' => array()
        );
        
        // Get total items
        $stats['total_items'] = self::get_location_item_count($location_id);
        
        // Get items by condition
        $sql = $wpdb->prepare(
            "SELECT i.condition_status, COUNT(*) as count 
             FROM {$assignments_table} a 
             JOIN {$inventory_items_table} i ON a.item_id = i.id 
             WHERE a.assignee_type = 'location' AND a.assignee_id = %d AND a.unassigned_date IS NULL 
             GROUP BY i.condition_status",
            $location_id
        );
        
        $condition_results = $wpdb->get_results($sql, ARRAY_A);
        foreach ($condition_results as $row) {
            $stats['items_by_condition'][$row['condition_status']] = (int) $row['count'];
        }
        
        // Get recent assignments (last 10)
        $sql = $wpdb->prepare(
            "SELECT a.*, i.title, i.unique_identifier 
             FROM {$assignments_table} a 
             JOIN {$inventory_items_table} i ON a.item_id = i.id 
             WHERE a.assignee_type = 'location' AND a.assignee_id = %d 
             ORDER BY a.assigned_date DESC LIMIT 10",
            $location_id
        );
        
        $stats['recent_assignments'] = $wpdb->get_results($sql, ARRAY_A);
        
        return $stats;
    }
    
    /**
     * Check if location exists
     */
    public static function location_exists($location_id) {
        global $wpdb;
        
        $db = BKGT_Inventory_Database::get_instance();
        $locations_table = $db->get_locations_table();
        
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$locations_table} WHERE id = %d",
            $location_id
        );
        
        return (int) $wpdb->get_var($sql) > 0;
    }
    
    /**
     * Check if slug exists
     */
    public static function slug_exists($slug, $exclude_id = null) {
        global $wpdb;
        
        $db = BKGT_Inventory_Database::get_instance();
        $locations_table = $db->get_locations_table();
        
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$locations_table} WHERE slug = %s",
            $slug
        );
        
        if ($exclude_id) {
            $sql = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$locations_table} WHERE slug = %s AND id != %d",
                $slug, $exclude_id
            );
        }
        
        return (int) $wpdb->get_var($sql) > 0;
    }
    
    /**
     * Build hierarchical structure from flat array
     */
    private static function build_hierarchy($locations, $parent_id = null) {
        $hierarchy = array();
        
        foreach ($locations as $location) {
            if ($location['parent_id'] == $parent_id) {
                $children = self::build_hierarchy($locations, $location['id']);
                if (!empty($children)) {
                    $location['children'] = $children;
                }
                $hierarchy[] = $location;
            }
        }
        
        return $hierarchy;
    }
    
    /**
     * Get location types
     */
    public static function get_location_types() {
        return array(
            self::TYPE_STORAGE => __('Lager', 'bkgt-inventory'),
            self::TYPE_REPAIR => __('Reparation', 'bkgt-inventory'),
            self::TYPE_LOCKER => __('Skåp', 'bkgt-inventory'),
            self::TYPE_WAREHOUSE => __('Lagerlokal', 'bkgt-inventory'),
            self::TYPE_OTHER => __('Övrigt', 'bkgt-inventory')
        );
    }
    
    /**
     * Migrate existing taxonomy terms to locations table
     */
    public static function migrate_taxonomy_terms() {
        $terms = get_terms(array(
            'taxonomy' => 'bkgt_storage_location',
            'hide_empty' => false
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return;
        }
        
        foreach ($terms as $term) {
            // Check if location already exists
            if (self::slug_exists($term->slug)) {
                continue;
            }
            
            // Create location from term
            $location_data = array(
                'name' => $term->name,
                'slug' => $term->slug,
                'location_type' => self::TYPE_STORAGE,
                'notes' => __('Migrerad från taxonomi', 'bkgt-inventory')
            );
            
            self::create_location($location_data);
        }
    }
}