<?php
/**
 * Team Management Class
 *
 * @package BKGT_User_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Team {
    
    /**
     * Get all teams
     */
    public static function get_all_teams($args = array()) {
        $defaults = array(
            'post_type'      => 'bkgt_team',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        );
        
        $args = wp_parse_args($args, $defaults);
        $teams = get_posts($args);
        
        return $teams;
    }
    
    /**
     * Get team by ID
     */
    public static function get_team($team_id) {
        $team = get_post($team_id);
        
        if (!$team || $team->post_type !== 'bkgt_team') {
            return false;
        }
        
        return $team;
    }
    
    /**
     * Get team members
     */
    public static function get_team_members($team_id, $role = '') {
        $args = array(
            'meta_query' => array(
                array(
                    'key'     => 'bkgt_assigned_teams',
                    'value'   => sprintf(':"%d";', $team_id),
                    'compare' => 'LIKE',
                ),
            ),
        );
        
        // Filter by role if specified
        if (!empty($role)) {
            $args['role__in'] = array($role);
        }
        
        $user_query = new WP_User_Query($args);
        return $user_query->get_results();
    }
    
    /**
     * Get team coaches (Tränare)
     */
    public static function get_team_coaches($team_id) {
        return self::get_team_members($team_id, 'tranare');
    }
    
    /**
     * Get team managers (Lagledare)
     */
    public static function get_team_managers($team_id) {
        return self::get_team_members($team_id, 'lagledare');
    }
    
    /**
     * Get team staff (Coaches + Managers)
     */
    public static function get_team_staff($team_id) {
        $coaches = self::get_team_coaches($team_id);
        $managers = self::get_team_managers($team_id);
        
        return array_merge($coaches, $managers);
    }
    
    /**
     * Get team metadata
     */
    public static function get_team_meta($team_id, $key, $single = true) {
        return get_post_meta($team_id, $key, $single);
    }
    
    /**
     * Update team metadata
     */
    public static function update_team_meta($team_id, $key, $value) {
        return update_post_meta($team_id, $key, $value);
    }
    
    /**
     * Get team statistics
     */
    public static function get_team_stats($team_id) {
        $stats = array(
            'members_count'  => count(self::get_team_members($team_id)),
            'coaches_count'  => count(self::get_team_coaches($team_id)),
            'managers_count' => count(self::get_team_managers($team_id)),
        );
        
        return $stats;
    }
    
    /**
     * Check if user has access to team
     */
    public static function user_can_access_team($user_id, $team_id) {
        // Admins and Board Members can access all teams
        if (user_can($user_id, 'manage_options') || user_can($user_id, 'manage_all_teams')) {
            return true;
        }
        
        // Check if user is assigned to this team
        $user_teams = get_user_meta($user_id, 'bkgt_assigned_teams', true);
        if (!is_array($user_teams)) {
            return false;
        }
        
        return in_array($team_id, $user_teams);
    }
    
    /**
     * Get teams for dropdown
     */
    public static function get_teams_for_select() {
        $teams = self::get_all_teams();
        $options = array();
        
        foreach ($teams as $team) {
            $options[$team->ID] = $team->post_title;
        }
        
        return $options;
    }
}
