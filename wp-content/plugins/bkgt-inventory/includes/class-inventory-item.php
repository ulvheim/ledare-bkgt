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
            '%s-%s-%05d',
            $manufacturer['manufacturer_id'],
            $item_type['item_type_id'],
            $sequential_number
        );
        
        return $identifier;
    }
    
    /**
     * Get next sequential number for manufacturer + item type combination
     */
    private static function get_next_sequential_number($manufacturer_id, $item_type_id) {
        global $wpdb;
        
        // Find the highest sequential number for this combination
        $existing_items = get_posts(array(
            'post_type' => 'bkgt_inventory_item',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_bkgt_manufacturer_id',
                    'value' => $manufacturer_id,
                    'compare' => '='
                ),
                array(
                    'key' => '_bkgt_item_type_id',
                    'value' => $item_type_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        $max_sequential = 0;
        
        foreach ($existing_items as $item_id) {
            $identifier = get_post_meta($item_id, '_bkgt_unique_identifier', true);
            
            if ($identifier) {
                // Extract sequential number from identifier (last 5 digits)
                $parts = explode('-', $identifier);
                if (count($parts) === 3) {
                    $sequential = intval($parts[2]);
                    $max_sequential = max($max_sequential, $sequential);
                }
            }
        }
        
        return $max_sequential + 1;
    }
    
    /**
     * Create inventory item
     */
    public static function create($data) {
        // Validate required fields
        $required_fields = array('manufacturer_id', 'item_type_id', 'title');
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
}
