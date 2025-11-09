<?php
/**
 * Inventory Item Management Class
 *
 * @package BKGT_Inventory
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Inventory_Item {
    
    /**
     * Generate unique identifier
     * Format: ####-####-##### (Manufacturer-ID + ItemType-ID + Sequential Number)
     */
    public static function generate_unique_identifier($manufacturer_id, $item_type_id) {
        // Get manufacturer and item type data
        $manufacturer = BKGT_Manufacturer::get($manufacturer_id);
        $item_type = BKGT_Item_Type::get($item_type_id);
        
        if (!$manufacturer || !$item_type) {
            return false;
        }
        
        // Get the next sequential number for this manufacturer + item type combination
        $sequential_number = self::get_next_sequential_number($manufacturer_id, $item_type_id);
        
        // Format: ####-####-#####
        $identifier = sprintf(
            '%04d-%04d-%05d',
            intval($manufacturer['manufacturer_id']),
            intval($item_type['item_type_id']),
            $sequential_number
        );
        
        return $identifier;
    }
    
    /**
     * Generate short form unique identifier (without leading zeros)
     * Format: ####-####-# (Manufacturer-ID + ItemType-ID + Sequential Number without leading zeros)
     */
    public static function generate_short_unique_identifier($manufacturer_id, $item_type_id) {
        // Get manufacturer and item type data
        $manufacturer = BKGT_Manufacturer::get($manufacturer_id);
        $item_type = BKGT_Item_Type::get($item_type_id);

        if (!$manufacturer || !$item_type) {
            return false;
        }

        // Get the next sequential number for this manufacturer + item type combination
        $sequential_number = self::get_next_sequential_number($manufacturer_id, $item_type_id);

        // Format: #-#-# (remove all leading zeros from all parts)
        $identifier = sprintf(
            '%d-%d-%d',
            intval($manufacturer['manufacturer_id']),
            intval($item_type['item_type_id']),
            $sequential_number
        );

        return $identifier;
    }
    
    /**
     * Get next sequential number for manufacturer + item type combination
     */
    private static function get_next_sequential_number($manufacturer_id, $item_type_id) {
        global $wpdb;

        // Find the highest sequential number for this combination in the custom database table
        $max_identifier = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(CAST(SUBSTRING_INDEX(unique_identifier, '-', -1) AS UNSIGNED)) as max_seq
             FROM {$wpdb->prefix}bkgt_inventory_items
             WHERE manufacturer_id = %d AND item_type_id = %d",
            $manufacturer_id, $item_type_id
        ));

        return ($max_identifier ?: 0) + 1;
    }
    
    /**
     * Create inventory item
     */
    public static function create($data) {
        // Validate required fields (title is not accepted - will be auto-generated from unique identifier)
        $required_fields = array('manufacturer_id', 'item_type_id');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Obligatoriskt fält saknas: %s', 'bkgt-inventory'), $field));
            }
        }

        // Generate unique identifier if not provided
        if (empty($data['unique_identifier'])) {
            $data['unique_identifier'] = self::generate_unique_identifier($data['manufacturer_id'], $data['item_type_id']);
        }

        // Check if identifier already exists
        if (self::identifier_exists($data['unique_identifier'])) {
            return new WP_Error('identifier_exists', __('Unik identifierare finns redan.', 'bkgt-inventory'));
        }

        // Title is always the unique identifier (the unique identifier IS the title)
        $data['title'] = $data['unique_identifier'];

        // Create the post
        $post_data = array(
            'post_type' => 'bkgt_inventory_item',
            'post_title' => sanitize_text_field($data['title']),
            'post_status' => 'publish',
        );
        
        if (!empty($data['description'])) {
            $post_data['post_content'] = wp_kses_post($data['description']);
        }
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Save meta data
        update_post_meta($post_id, '_bkgt_unique_identifier', $data['unique_identifier']);
        update_post_meta($post_id, '_bkgt_manufacturer_id', intval($data['manufacturer_id']));
        update_post_meta($post_id, '_bkgt_item_type_id', intval($data['item_type_id']));
        
        // Assignment data
        if (!empty($data['assignment_type'])) {
            update_post_meta($post_id, '_bkgt_assignment_type', $data['assignment_type']);
            
            if ($data['assignment_type'] === 'team' && !empty($data['assigned_team'])) {
                update_post_meta($post_id, '_bkgt_assigned_team', intval($data['assigned_team']));
            } elseif ($data['assignment_type'] === 'individual' && !empty($data['assigned_user'])) {
                update_post_meta($post_id, '_bkgt_assigned_user', intval($data['assigned_user']));
            }
        }
        
        // Storage locations (can be multiple)
        if (!empty($data['storage_locations']) && is_array($data['storage_locations'])) {
            wp_set_post_terms($post_id, $data['storage_locations'], 'bkgt_storage_location');
        }
        
        // Condition
        if (!empty($data['condition'])) {
            wp_set_post_terms($post_id, array($data['condition']), 'bkgt_condition');
        }
        
        // Custom fields
        if (!empty($data['custom_fields'])) {
            update_post_meta($post_id, '_bkgt_custom_fields', $data['custom_fields']);
        }
        
        // Metadata (JSON structure)
        if (!empty($data['metadata'])) {
            update_post_meta($post_id, '_bkgt_metadata', wp_json_encode($data['metadata']));
        }
        
        // Log creation
        BKGT_History::log($post_id, 'created', get_current_user_id(), array(
            'action' => 'created',
            'data' => $data
        ));
        
        return $post_id;
    }
    
    /**
     * Update inventory item
     */
    public static function update($post_id, $data) {
        // Update post data
        $post_data = array('ID' => $post_id);
        
        if (!empty($data['title'])) {
            $post_data['post_title'] = sanitize_text_field($data['title']);
        }
        
        if (isset($data['description'])) {
            $post_data['post_content'] = wp_kses_post($data['description']);
        }
        
        wp_update_post($post_data);
        
        // Track changes for history logging
        $changes = array();
        
        // Update assignment if changed
        $old_assignment_type = get_post_meta($post_id, '_bkgt_assignment_type', true);
        $old_assigned_team = get_post_meta($post_id, '_bkgt_assigned_team', true);
        $old_assigned_user = get_post_meta($post_id, '_bkgt_assigned_user', true);
        
        if (isset($data['assignment_type'])) {
            $new_assignment_type = $data['assignment_type'];
            
            if ($old_assignment_type !== $new_assignment_type) {
                update_post_meta($post_id, '_bkgt_assignment_type', $new_assignment_type);
                $changes['assignment_type'] = array('old' => $old_assignment_type, 'new' => $new_assignment_type);
                
                // Clear old assignments
                delete_post_meta($post_id, '_bkgt_assigned_team');
                delete_post_meta($post_id, '_bkgt_assigned_user');
                
                // Set new assignment
                if ($new_assignment_type === 'team' && !empty($data['assigned_team'])) {
                    update_post_meta($post_id, '_bkgt_assigned_team', intval($data['assigned_team']));
                    $changes['assigned_team'] = array('old' => $old_assigned_team, 'new' => $data['assigned_team']);
                } elseif ($new_assignment_type === 'individual' && !empty($data['assigned_user'])) {
                    update_post_meta($post_id, '_bkgt_assigned_user', intval($data['assigned_user']));
                    $changes['assigned_user'] = array('old' => $old_assigned_user, 'new' => $data['assigned_user']);
                }
            }
        }
        
        // Update storage locations
        if (isset($data['storage_locations']) && is_array($data['storage_locations'])) {
            $old_locations = wp_get_post_terms($post_id, 'bkgt_storage_location', array('fields' => 'ids'));
            wp_set_post_terms($post_id, $data['storage_locations'], 'bkgt_storage_location');
            
            if ($old_locations != $data['storage_locations']) {
                $changes['storage_locations'] = array('old' => $old_locations, 'new' => $data['storage_locations']);
            }
        }
        
        // Update condition
        if (!empty($data['condition'])) {
            $old_condition = wp_get_post_terms($post_id, 'bkgt_condition', array('fields' => 'slugs'));
            wp_set_post_terms($post_id, array($data['condition']), 'bkgt_condition');
            
            if ($old_condition != array($data['condition'])) {
                $changes['condition'] = array('old' => $old_condition, 'new' => $data['condition']);
            }
        }
        
        // Update custom fields
        if (isset($data['custom_fields'])) {
            $old_custom_fields = get_post_meta($post_id, '_bkgt_custom_fields', true);
            update_post_meta($post_id, '_bkgt_custom_fields', $data['custom_fields']);
            
            if ($old_custom_fields != $data['custom_fields']) {
                $changes['custom_fields'] = array('old' => $old_custom_fields, 'new' => $data['custom_fields']);
            }
        }
        
        // Update metadata
        if (isset($data['metadata'])) {
            $old_metadata = get_post_meta($post_id, '_bkgt_metadata', true);
            update_post_meta($post_id, '_bkgt_metadata', wp_json_encode($data['metadata']));
            
            if ($old_metadata != wp_json_encode($data['metadata'])) {
                $changes['metadata'] = array('old' => $old_metadata, 'new' => $data['metadata']);
            }
        }
        
        // Log changes if any
        if (!empty($changes)) {
            BKGT_History::log($post_id, 'updated', get_current_user_id(), $changes);
        }
        
        return true;
    }
    
    /**
     * Delete inventory item
     */
    public static function delete($post_id) {
        // Log deletion
        BKGT_History::log($post_id, 'deleted', get_current_user_id(), array(
            'action' => 'deleted'
        ));
        
        return wp_delete_post($post_id, true);
    }
    
    /**
     * Check if identifier exists
     */
    public static function identifier_exists($identifier) {
        $existing = get_posts(array(
            'post_type' => 'bkgt_inventory_item',
            'meta_query' => array(
                array(
                    'key' => '_bkgt_unique_identifier',
                    'value' => $identifier,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
            'fields' => 'ids'
        ));
        
        return !empty($existing);
    }
    
    /**
     * Get inventory item data
     */
    public static function get_item_data($post_id) {
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'bkgt_inventory_item') {
            return false;
        }
        
        $data = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'description' => $post->post_content,
            'unique_identifier' => get_post_meta($post->ID, '_bkgt_unique_identifier', true),
            'manufacturer_id' => get_post_meta($post->ID, '_bkgt_manufacturer_id', true),
            'item_type_id' => get_post_meta($post->ID, '_bkgt_item_type_id', true),
            'assignment_type' => get_post_meta($post->ID, '_bkgt_assignment_type', true),
            'assigned_team' => get_post_meta($post->ID, '_bkgt_assigned_team', true),
            'assigned_user' => get_post_meta($post->ID, '_bkgt_assigned_user', true),
            'storage_locations' => wp_get_post_terms($post->ID, 'bkgt_storage_location', array('fields' => 'ids')),
            'condition' => wp_get_post_terms($post->ID, 'bkgt_condition', array('fields' => 'slugs')),
            'custom_fields' => get_post_meta($post->ID, '_bkgt_custom_fields', true),
            'metadata' => json_decode(get_post_meta($post->ID, '_bkgt_metadata', true), true),
            'created_at' => $post->post_date,
            'updated_at' => $post->post_modified,
        );
        
        // Get manufacturer and item type names
        if ($data['manufacturer_id']) {
            $manufacturer = BKGT_Manufacturer::get($data['manufacturer_id']);
            $data['manufacturer_name'] = $manufacturer ? $manufacturer['name'] : '';
        }
        
        if ($data['item_type_id']) {
            $item_type = BKGT_Item_Type::get($data['item_type_id']);
            $data['item_type_name'] = $item_type ? $item_type['name'] : '';
        }
        
        // Get assigned team/user names
        if ($data['assignment_type'] === 'team' && $data['assigned_team']) {
            $team = get_post($data['assigned_team']);
            $data['assigned_team_name'] = $team ? $team->post_title : '';
        }
        
        if ($data['assignment_type'] === 'individual' && $data['assigned_user']) {
            $user = get_userdata($data['assigned_user']);
            $data['assigned_user_name'] = $user ? $user->display_name : '';
        }
        
        return $data;
    }
    
    /**
     * Get items by assignment
     */
    public static function get_items_by_assignment($assignment_type, $assignment_id = null, $args = array()) {
        $meta_query = array();
        
        if ($assignment_type === 'team') {
            $meta_query[] = array(
                'key' => '_bkgt_assignment_type',
                'value' => 'team',
                'compare' => '='
            );
            
            if ($assignment_id) {
                $meta_query[] = array(
                    'key' => '_bkgt_assigned_team',
                    'value' => $assignment_id,
                    'compare' => '='
                );
            }
        } elseif ($assignment_type === 'individual') {
            $meta_query[] = array(
                'key' => '_bkgt_assignment_type',
                'value' => 'individual',
                'compare' => '='
            );
            
            if ($assignment_id) {
                $meta_query[] = array(
                    'key' => '_bkgt_assigned_user',
                    'value' => $assignment_id,
                    'compare' => '='
                );
            }
        } elseif ($assignment_type === 'club') {
            $meta_query[] = array(
                'key' => '_bkgt_assignment_type',
                'value' => 'club',
                'compare' => '='
            );
        }
        
        $defaults = array(
            'post_type' => 'bkgt_inventory_item',
            'meta_query' => $meta_query,
            'posts_per_page' => -1,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        return get_posts($args);
    }
    
    /**
     * Get items by condition
     */
    public static function get_items_by_condition($condition, $args = array()) {
        $defaults = array(
            'post_type' => 'bkgt_inventory_item',
            'tax_query' => array(
                array(
                    'taxonomy' => 'bkgt_condition',
                    'field' => 'slug',
                    'terms' => $condition,
                ),
            ),
            'posts_per_page' => -1,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        return get_posts($args);
    }

    /**
     * Get items with pagination and filtering
     */
    public static function get_items($args = array()) {
        global $wpdb;
        global $bkgt_inventory_db;
        
        $defaults = array(
            'page' => 1,
            'per_page' => 10,
            'search' => '',
            'location_id' => null,
            'condition' => null,
            'manufacturer_id' => null,
            'item_type_id' => null,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $bkgt_inventory_db->get_inventory_items_table();
        $manufacturers_table = $bkgt_inventory_db->get_manufacturers_table();
        $item_types_table = $bkgt_inventory_db->get_item_types_table();
        $assignments_table = $bkgt_inventory_db->get_assignments_table();
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $where_clauses = array();
        $join_clauses = array();
        
        // Search
        if (!empty($args['search'])) {
            $where_clauses[] = $wpdb->prepare(
                "(i.title LIKE %s OR i.unique_identifier LIKE %s OR i.storage_location LIKE %s)",
                '%' . $wpdb->esc_like($args['search']) . '%',
                '%' . $wpdb->esc_like($args['search']) . '%',
                '%' . $wpdb->esc_like($args['search']) . '%'
            );
        }
        
        // Manufacturer filter
        if (!empty($args['manufacturer_id'])) {
            $where_clauses[] = $wpdb->prepare("i.manufacturer_id = %d", $args['manufacturer_id']);
        }
        
        // Item type filter
        if (!empty($args['item_type_id'])) {
            $where_clauses[] = $wpdb->prepare("i.item_type_id = %d", $args['item_type_id']);
        }
        
        // Condition filter
        if (!empty($args['condition'])) {
            $where_clauses[] = $wpdb->prepare("i.condition_status = %s", $args['condition']);
        }
        
        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        
        $sql = $wpdb->prepare(
            "SELECT i.*, 
                    m.name as manufacturer_name, 
                    it.name as item_type_name,
                    a.assignee_type,
                    a.assignee_id
             FROM {$table} i
             LEFT JOIN {$manufacturers_table} m ON i.manufacturer_id = m.id
             LEFT JOIN {$item_types_table} it ON i.item_type_id = it.id
             LEFT JOIN {$assignments_table} a ON i.id = a.item_id AND a.unassigned_date IS NULL
             {$where_sql}
             ORDER BY i.id DESC
             LIMIT %d OFFSET %d",
            $args['per_page'], $offset
        );
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        $items = array();
        foreach ($results as $row) {
            $items[] = array(
                'id' => (int) $row['id'],
                'unique_identifier' => $row['unique_identifier'],
                'title' => $row['title'],
                'manufacturer_id' => (int) $row['manufacturer_id'],
                'manufacturer_name' => $row['manufacturer_name'],
                'item_type_id' => (int) $row['item_type_id'],
                'item_type_name' => $row['item_type_name'],
                'storage_location' => $row['storage_location'],
                'condition_status' => $row['condition_status'],
                'notes' => $row['notes'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'assignee_type' => $row['assignee_type'],
                'assignee_id' => $row['assignee_id'] ? (int) $row['assignee_id'] : null,
            );
        }
        
        return $items;
    }

    /**
     * Get total count of items with filtering
     */
    public static function get_total_count($args = array()) {
        global $wpdb;
        global $bkgt_inventory_db;
        
        $defaults = array(
            'search' => '',
            'location_id' => null,
            'condition' => null,
            'manufacturer_id' => null,
            'item_type_id' => null,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $bkgt_inventory_db->get_inventory_items_table();
        
        $where_clauses = array();
        
        // Search
        if (!empty($args['search'])) {
            $where_clauses[] = $wpdb->prepare(
                "(title LIKE %s OR unique_identifier LIKE %s OR storage_location LIKE %s)",
                '%' . $wpdb->esc_like($args['search']) . '%',
                '%' . $wpdb->esc_like($args['search']) . '%',
                '%' . $wpdb->esc_like($args['search']) . '%'
            );
        }
        
        // Manufacturer filter
        if (!empty($args['manufacturer_id'])) {
            $where_clauses[] = $wpdb->prepare("manufacturer_id = %d", $args['manufacturer_id']);
        }
        
        // Item type filter
        if (!empty($args['item_type_id'])) {
            $where_clauses[] = $wpdb->prepare("item_type_id = %d", $args['item_type_id']);
        }
        
        // Condition filter
        if (!empty($args['condition'])) {
            $where_clauses[] = $wpdb->prepare("condition_status = %s", $args['condition']);
        }
        
        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        
        $sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
        
        return (int) $wpdb->get_var($sql);
    }

    /**
     * Get single item by ID (alias for get_item_data)
     */
    public static function get_item($item_id) {
        global $wpdb;
        global $bkgt_inventory_db;
        
        $table = $bkgt_inventory_db->get_inventory_items_table();
        $manufacturers_table = $bkgt_inventory_db->get_manufacturers_table();
        $item_types_table = $bkgt_inventory_db->get_item_types_table();
        $assignments_table = $bkgt_inventory_db->get_assignments_table();
        
        $sql = $wpdb->prepare(
            "SELECT i.*, 
                    m.name as manufacturer_name, 
                    it.name as item_type_name,
                    a.assignee_type,
                    a.assignee_id
             FROM {$table} i
             LEFT JOIN {$manufacturers_table} m ON i.manufacturer_id = m.id
             LEFT JOIN {$item_types_table} it ON i.item_type_id = it.id
             LEFT JOIN {$assignments_table} a ON i.id = a.item_id AND a.unassigned_date IS NULL
             WHERE i.id = %d",
            $item_id
        );
        
        $row = $wpdb->get_row($sql, ARRAY_A);
        
        if (!$row) {
            return false;
        }
        
        return array(
            'id' => (int) $row['id'],
            'unique_identifier' => $row['unique_identifier'],
            'title' => $row['title'],
            'size' => $row['size'],
            'manufacturer_id' => (int) $row['manufacturer_id'],
            'manufacturer_name' => $row['manufacturer_name'],
            'item_type_id' => (int) $row['item_type_id'],
            'item_type_name' => $row['item_type_name'],
            'storage_location' => $row['storage_location'],
            'condition_status' => $row['condition_status'],
            'notes' => $row['notes'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'assignee_type' => $row['assignee_type'],
            'assignee_id' => $row['assignee_id'] ? (int) $row['assignee_id'] : null,
        );
    }

    /**
     * Create item (alias for create)
     */
    public static function create_item($data) {
        global $wpdb;
        global $bkgt_inventory_db;
        
        // Validate required fields (title is not accepted - will be auto-generated from unique identifier)
        $required_fields = array('manufacturer_id', 'item_type_id');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Obligatoriskt fält saknas: %s', 'bkgt-inventory'), $field));
            }
        }
        
        // Generate unique identifier if not provided
        if (empty($data['unique_identifier'])) {
            $data['unique_identifier'] = self::generate_unique_identifier($data['manufacturer_id'], $data['item_type_id']);
        }
        
        // Check if identifier already exists
        if (self::identifier_exists($data['unique_identifier'])) {
            return new WP_Error('identifier_exists', __('Unik identifierare finns redan.', 'bkgt-inventory'));
        }
        
        // Title is always the unique identifier (the unique identifier IS the title)
        $data['title'] = $data['unique_identifier'];
        
        $table = $bkgt_inventory_db->get_inventory_items_table();
        
        $result = $wpdb->insert(
            $table,
            array(
                'unique_identifier' => $data['unique_identifier'],
                'manufacturer_id' => intval($data['manufacturer_id']),
                'item_type_id' => intval($data['item_type_id']),
                'title' => sanitize_text_field($data['title']),
                'storage_location' => sanitize_text_field($data['storage_location'] ?? ''),
                'condition_status' => $data['condition_status'] ?? 'normal',
                'notes' => sanitize_textarea_field($data['notes'] ?? ''),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Kunde inte skapa artikel.', 'bkgt-inventory'));
        }
        
        $item_id = $wpdb->insert_id;
        
        return $item_id;
    }

    /**
     * Update item (alias for update)
     */
    public static function update_item($item_id, $data) {
        global $wpdb;
        global $bkgt_inventory_db;
        
        $table = $bkgt_inventory_db->get_inventory_items_table();
        
        // Check if item exists
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE id = %d", $item_id));
        if (!$exists) {
            return new WP_Error('not_found', __('Artikel hittades inte.', 'bkgt-inventory'));
        }
        
        $update_data = array(
            'updated_at' => current_time('mysql'),
        );
        
        $update_format = array('%s');
        
        if (isset($data['manufacturer_id'])) {
            $update_data['manufacturer_id'] = intval($data['manufacturer_id']);
            $update_format[] = '%d';
        }
        
        if (isset($data['item_type_id'])) {
            $update_data['item_type_id'] = intval($data['item_type_id']);
            $update_format[] = '%d';
        }
        
        if (isset($data['unique_identifier'])) {
            $update_data['unique_identifier'] = sanitize_text_field($data['unique_identifier']);
            $update_data['title'] = sanitize_text_field($data['unique_identifier']); // Title is always the unique identifier
            $update_format[] = '%s';
            $update_format[] = '%s';
        }
        
        if (isset($data['storage_location'])) {
            $update_data['storage_location'] = sanitize_text_field($data['storage_location']);
            $update_format[] = '%s';
        }
        
        if (isset($data['size'])) {
            $update_data['size'] = sanitize_text_field($data['size']);
            $update_format[] = '%s';
        }
        
        if (isset($data['condition_status'])) {
            $update_data['condition_status'] = sanitize_text_field($data['condition_status']);
            $update_format[] = '%s';
        }
        
        if (isset($data['notes'])) {
            $update_data['notes'] = sanitize_textarea_field($data['notes']);
            $update_format[] = '%s';
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $item_id),
            $update_format,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Kunde inte uppdatera artikel.', 'bkgt-inventory'));
        }
        
        return true;
    }

    /**
     * Delete item (alias for delete)
     */
    public static function delete_item($item_id) {
        global $wpdb;
        global $bkgt_inventory_db;
        
        $table = $bkgt_inventory_db->get_inventory_items_table();
        
        $result = $wpdb->delete(
            $table,
            array('id' => $item_id),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Kunde inte ta bort artikel.', 'bkgt-inventory'));
        }
        
        return true;
    }
}
