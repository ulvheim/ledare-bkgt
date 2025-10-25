<?php
/**
 * User Capabilities Management
 *
 * @package BKGT_User_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Capabilities {
    
    /**
     * Check if user can view performance/evaluation data
     */
    public static function can_view_performance_data($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'view_performance_data') || 
               user_can($user_id, 'manage_options');
    }
    
    /**
     * Check if user can manage inventory
     */
    public static function can_manage_inventory($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'manage_inventory') || 
               user_can($user_id, 'manage_options');
    }
    
    /**
     * Check if user can manage documents
     */
    public static function can_manage_documents($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'manage_documents') || 
               user_can($user_id, 'manage_options');
    }
    
    /**
     * Check if user can view team data
     */
    public static function can_view_team_data($user_id = null, $team_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Admins and board members can view all teams
        if (user_can($user_id, 'manage_all_teams') || user_can($user_id, 'manage_options')) {
            return true;
        }
        
        // Check team-specific access
        if ($team_id) {
            return BKGT_Team::user_can_access_team($user_id, $team_id);
        }
        
        // General team data view permission
        return user_can($user_id, 'view_team_data');
    }
    
    /**
     * Check if user can manage all teams (Board members only)
     */
    public static function can_manage_all_teams($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'manage_all_teams') || 
               user_can($user_id, 'manage_options');
    }
    
    /**
     * Get user's role label in Swedish
     */
    public static function get_user_role_label($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return '';
        }
        
        $roles = $user->roles;
        $role = reset($roles);
        
        $role_labels = array(
            'administrator'   => __('Administratör', 'bkgt-user-management'),
            'styrelsemedlem'  => __('Styrelsemedlem', 'bkgt-user-management'),
            'tranare'         => __('Tränare', 'bkgt-user-management'),
            'lagledare'       => __('Lagledare', 'bkgt-user-management'),
        );
        
        return isset($role_labels[$role]) ? $role_labels[$role] : ucfirst($role);
    }
    
    /**
     * Get capabilities for a role
     */
    public static function get_role_capabilities($role_slug) {
        $capabilities = array(
            'styrelsemedlem' => array(
                'read'                   => true,
                'edit_posts'             => true,
                'delete_posts'           => true,
                'publish_posts'          => true,
                'upload_files'           => true,
                'edit_others_posts'      => true,
                'delete_others_posts'    => true,
                'manage_categories'      => true,
                'view_performance_data'  => true,
                'manage_inventory'       => true,
                'manage_documents'       => true,
                'manage_all_teams'       => true,
            ),
            'tranare' => array(
                'read'                   => true,
                'edit_posts'             => true,
                'delete_posts'           => true,
                'publish_posts'          => true,
                'upload_files'           => true,
                'view_performance_data'  => true,
                'manage_inventory'       => true,
                'manage_documents'       => true,
                'view_team_data'         => true,
            ),
            'lagledare' => array(
                'read'                   => true,
                'edit_posts'             => true,
                'upload_files'           => true,
                'manage_inventory'       => true,
                'manage_documents'       => true,
                'view_team_data'         => true,
            ),
        );
        
        return isset($capabilities[$role_slug]) ? $capabilities[$role_slug] : array();
    }
}
