<?php
/**
 * Assignment Management Class
 *
 * @package BKGT_Inventory
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Assignment {
    
    /**
     * Assignment types
     */
    const TYPE_CLUB = 'club';
    const TYPE_TEAM = 'team';
    const TYPE_INDIVIDUAL = 'individual';
    
    /**
     * Assign item to club
     */
    public static function assign_to_club($item_id) {
        return self::update_assignment($item_id, self::TYPE_CLUB);
    }
    
    /**
     * Assign item to team
     */
    public static function assign_to_team($item_id, $team_id) {
        // Check if User Management plugin is available
        if (!class_exists('BKGT_Team')) {
            return new WP_Error('plugin_not_available', __('Användarhantering plugin krävs.', 'bkgt-inventory'));
        }
        
        // Verify team exists
        $team = BKGT_Team::get_team($team_id);
        if (!$team) {
            return new WP_Error('team_not_found', __('Lag hittades inte.', 'bkgt-inventory'));
        }
        
        return self::update_assignment($item_id, self::TYPE_TEAM, $team_id);
    }
    
    /**
     * Assign item to individual
     */
    public static function assign_to_individual($item_id, $user_id) {
        // Verify user exists
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', __('Användare hittades inte.', 'bkgt-inventory'));
        }
        
        return self::update_assignment($item_id, self::TYPE_INDIVIDUAL, $user_id);
    }
    
    /**
     * Update item assignment
     */
    private static function update_assignment($item_id, $assignment_type, $assignment_id = null) {
        // Verify item exists
        $item = get_post($item_id);
        if (!$item || $item->post_type !== 'bkgt_inventory_item') {
            return new WP_Error('item_not_found', __('Utrustningsartikel hittades inte.', 'bkgt-inventory'));
        }
        
        // Get current assignment
        $current_assignment_type = get_post_meta($item_id, '_bkgt_assignment_type', true);
        $current_assignment_id = null;
        
        if ($current_assignment_type === self::TYPE_TEAM) {
            $current_assignment_id = get_post_meta($item_id, '_bkgt_assigned_team', true);
        } elseif ($current_assignment_type === self::TYPE_INDIVIDUAL) {
            $current_assignment_id = get_post_meta($item_id, '_bkgt_assigned_user', true);
        }
        
        // Update assignment type
        update_post_meta($item_id, '_bkgt_assignment_type', $assignment_type);
        
        // Clear old assignment data
        delete_post_meta($item_id, '_bkgt_assigned_team');
        delete_post_meta($item_id, '_bkgt_assigned_user');
        
        // Set new assignment data
        if ($assignment_type === self::TYPE_TEAM && $assignment_id) {
            update_post_meta($item_id, '_bkgt_assigned_team', $assignment_id);
        } elseif ($assignment_type === self::TYPE_INDIVIDUAL && $assignment_id) {
            update_post_meta($item_id, '_bkgt_assigned_user', $assignment_id);
        }
        
        // Log the assignment change
        BKGT_History::log($item_id, 'assignment_changed', get_current_user_id(), array(
            'old_assignment_type' => $current_assignment_type,
            'old_assignment_id' => $current_assignment_id,
            'new_assignment_type' => $assignment_type,
            'new_assignment_id' => $assignment_id,
        ));
        
        return true;
    }
    
    /**
     * Get item assignment
     */
    public static function get_assignment($item_id) {
        $assignment_type = get_post_meta($item_id, '_bkgt_assignment_type', true);
        
        $assignment = array(
            'type' => $assignment_type,
            'id' => null,
            'name' => '',
        );
        
        if ($assignment_type === self::TYPE_TEAM) {
            $team_id = get_post_meta($item_id, '_bkgt_assigned_team', true);
            $team = get_post($team_id);
            
            $assignment['id'] = $team_id;
            $assignment['name'] = $team ? $team->post_title : '';
        } elseif ($assignment_type === self::TYPE_INDIVIDUAL) {
            $user_id = get_post_meta($item_id, '_bkgt_assigned_user', true);
            $user = get_userdata($user_id);
            
            $assignment['id'] = $user_id;
            $assignment['name'] = $user ? $user->display_name : '';
        } elseif ($assignment_type === self::TYPE_CLUB) {
            $assignment['name'] = __('Klubben', 'bkgt-inventory');
        }
        
        return $assignment;
    }
    
    /**
     * Check if user can access item
     */
    public static function user_can_access_item($user_id, $item_id) {
        // Admins can access everything
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        $assignment = self::get_assignment($item_id);
        
        // Club items are accessible to all authenticated users
        if ($assignment['type'] === self::TYPE_CLUB) {
            return is_user_logged_in();
        }
        
        // Team items require team access
        if ($assignment['type'] === self::TYPE_TEAM) {
            if (class_exists('BKGT_Team')) {
                return BKGT_Team::user_can_access_team($user_id, $assignment['id']);
            }
            return false;
        }
        
        // Individual items require personal access or team admin access
        if ($assignment['type'] === self::TYPE_INDIVIDUAL) {
            // Owner can access their own items
            if ($assignment['id'] == $user_id) {
                return true;
            }
            
            // Check if user is a coach/manager of the owner's team
            if (class_exists('BKGT_User_Team_Assignment') && class_exists('BKGT_Team')) {
                $owner_teams = BKGT_User_Team_Assignment::get_user_teams($assignment['id']);
                foreach ($owner_teams as $team_id) {
                    if (BKGT_Team::user_can_access_team($user_id, $team_id)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get items assigned to user
     */
    public static function get_user_items($user_id, $args = array()) {
        $meta_query_items = array(
            'relation' => 'OR',
            // Items assigned to club
            array(
                'key' => '_bkgt_assignment_type',
                'value' => self::TYPE_CLUB,
                'compare' => '='
            ),
        );
        
        // Add team items only if User Management plugin is available
        if (class_exists('BKGT_User_Team_Assignment')) {
            $user_teams = BKGT_User_Team_Assignment::get_user_teams($user_id);
            if (!empty($user_teams)) {
                $meta_query_items[] = array(
                    'relation' => 'AND',
                    array(
                        'key' => '_bkgt_assignment_type',
                        'value' => self::TYPE_TEAM,
                        'compare' => '='
                    ),
                    array(
                        'key' => '_bkgt_assigned_team',
                        'value' => $user_teams,
                        'compare' => 'IN'
                    )
                );
            }
        }
        
        // Items assigned directly to user
        $meta_query_items[] = array(
            'relation' => 'AND',
            array(
                'key' => '_bkgt_assignment_type',
                'value' => self::TYPE_INDIVIDUAL,
                'compare' => '='
            ),
            array(
                'key' => '_bkgt_assigned_user',
                'value' => $user_id,
                'compare' => '='
            )
        );
        
        $defaults = array(
            'post_type' => 'bkgt_inventory_item',
            'meta_query' => $meta_query_items,
            'posts_per_page' => -1,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        return get_posts($args);
    }
    
    /**
     * Transfer item from one assignment to another
     */
    public static function transfer_item($item_id, $new_assignment_type, $new_assignment_id = null) {
        $current_assignment = self::get_assignment($item_id);
        
        // Don't transfer if assignment hasn't changed
        if ($current_assignment['type'] === $new_assignment_type && $current_assignment['id'] == $new_assignment_id) {
            return true;
        }
        
        return self::update_assignment($item_id, $new_assignment_type, $new_assignment_id);
    }
    
    /**
     * Bulk assign items
     */
    public static function bulk_assign($item_ids, $assignment_type, $assignment_id = null) {
        $results = array();
        
        foreach ($item_ids as $item_id) {
            $result = self::update_assignment($item_id, $assignment_type, $assignment_id);
            $results[$item_id] = $result;
        }
        
        return $results;
    }
}
