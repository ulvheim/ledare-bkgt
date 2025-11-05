<?php
/**
 * BKGT Permission - Unified Access Control and Authorization
 * 
 * Provides consistent permission checking and role-based access control across all BKGT plugins.
 * Centralizes all capability checks and team-based access logic.
 * 
 * @package BKGT
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BKGT_Permission Class
 * 
 * Centralizes all permission and authorization functionality
 */
class BKGT_Permission {
    
    /**
     * BKGT Role Definitions
     */
    const ROLE_ADMIN        = 'bkgt_admin';        // Styrelsemedlem - Full access
    const ROLE_COACH        = 'bkgt_coach';        // Tränare - Team-specific access
    const ROLE_TEAM_MANAGER = 'bkgt_team_manager'; // Lagledare - Limited team access
    
    /**
     * Initialize permissions system
     * 
     * Adds all BKGT capabilities to roles on first run
     */
    public static function init() {
        self::register_capabilities();
    }
    
    /**
     * Register all BKGT capabilities
     * 
     * These are used by all plugins for permission checking
     */
    public static function register_capabilities() {
        $capabilities = array(
            // Inventory
            'bkgt_view_inventory'        => 'View inventory items',
            'bkgt_edit_inventory'        => 'Edit inventory items',
            'bkgt_delete_inventory'      => 'Delete inventory items',
            'bkgt_view_inventory_history' => 'View inventory history',
            
            // Documents
            'bkgt_view_documents'        => 'View documents',
            'bkgt_upload_documents'      => 'Upload documents',
            'bkgt_edit_documents'        => 'Edit documents',
            'bkgt_delete_documents'      => 'Delete documents',
            'bkgt_view_document_history' => 'View document history',
            
            // Teams & Players
            'bkgt_view_teams'            => 'View teams',
            'bkgt_edit_teams'            => 'Edit teams',
            'bkgt_view_players'          => 'View players',
            'bkgt_edit_players'          => 'Edit players',
            'bkgt_view_performance'      => 'View performance data (coaches only)',
            
            // Events
            'bkgt_view_events'           => 'View events',
            'bkgt_create_events'         => 'Create events',
            'bkgt_edit_events'           => 'Edit events',
            'bkgt_delete_events'         => 'Delete events',
            
            // Communication
            'bkgt_send_messages'         => 'Send messages',
            'bkgt_view_messages'         => 'View messages',
            
            // Offboarding
            'bkgt_manage_offboarding'    => 'Manage offboarding processes',
            
            // Admin
            'bkgt_manage_settings'       => 'Manage BKGT settings',
            'bkgt_view_logs'             => 'View system logs',
        );
        
        // Add capabilities to roles
        self::setup_roles( $capabilities );
    }
    
    /**
     * Setup BKGT roles with appropriate capabilities
     * 
     * @param array $capabilities List of capabilities
     */
    private static function setup_roles( $capabilities ) {
        global $wp_roles;
        
        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }
        
        // Admin (Styrelsemedlem) - Full access to everything
        $admin_role = $wp_roles->get_role( 'bkgt_admin' );
        if ( $admin_role ) {
            foreach ( $capabilities as $capability => $label ) {
                $admin_role->add_cap( $capability );
            }
        }
        
        // Coach (Tränare) - Team-specific access
        $coach_role = $wp_roles->get_role( 'bkgt_coach' );
        if ( $coach_role ) {
            $coach_capabilities = array(
                'bkgt_view_inventory',
                'bkgt_edit_inventory',
                'bkgt_view_inventory_history',
                'bkgt_view_documents',
                'bkgt_upload_documents',
                'bkgt_view_teams',
                'bkgt_view_players',
                'bkgt_view_performance', // Coaches CAN see performance data
                'bkgt_view_events',
                'bkgt_create_events',
                'bkgt_edit_events',
                'bkgt_send_messages',
                'bkgt_view_messages',
            );
            
            foreach ( $coach_capabilities as $capability ) {
                if ( isset( $capabilities[ $capability ] ) ) {
                    $coach_role->add_cap( $capability );
                }
            }
        }
        
        // Team Manager (Lagledare) - Limited team access
        $tm_role = $wp_roles->get_role( 'bkgt_team_manager' );
        if ( $tm_role ) {
            $tm_capabilities = array(
                'bkgt_view_inventory',
                'bkgt_view_inventory_history',
                'bkgt_view_documents',
                'bkgt_upload_documents',
                'bkgt_view_teams',
                'bkgt_view_players',
                'bkgt_view_events',
                'bkgt_send_messages',
                'bkgt_view_messages',
                // Team Managers CANNOT see performance data
            );
            
            foreach ( $tm_capabilities as $capability ) {
                if ( isset( $capabilities[ $capability ] ) ) {
                    $tm_role->add_cap( $capability );
                }
            }
        }
    }
    
    /**
     * Check if current user can view inventory
     * 
     * @return bool True if user can view inventory
     */
    public static function can_view_inventory() {
        return current_user_can( 'bkgt_view_inventory' );
    }
    
    /**
     * Check if current user can edit inventory
     * 
     * @return bool True if user can edit inventory
     */
    public static function can_edit_inventory() {
        return current_user_can( 'bkgt_edit_inventory' );
    }
    
    /**
     * Check if current user can view documents
     * 
     * @return bool True if user can view documents
     */
    public static function can_view_documents() {
        return current_user_can( 'bkgt_view_documents' );
    }
    
    /**
     * Check if current user can upload documents
     * 
     * @return bool True if user can upload documents
     */
    public static function can_upload_documents() {
        return current_user_can( 'bkgt_upload_documents' );
    }
    
    /**
     * Check if current user can view performance data
     * 
     * Only coaches and admins can view performance data
     * Team managers cannot
     * 
     * @return bool True if user can view performance data
     */
    public static function can_view_performance_data() {
        return current_user_can( 'bkgt_view_performance' );
    }
    
    /**
     * Check if current user can access a specific team
     * 
     * Admins can access all teams
     * Coaches and Team Managers can only access their assigned teams
     * 
     * @param int $team_id Team ID to check access for
     * 
     * @return bool True if user can access this team
     */
    public static function can_access_team( $team_id ) {
        $user = wp_get_current_user();
        
        if ( empty( $user->ID ) ) {
            return false;
        }
        
        // Admins can access all teams
        if ( current_user_can( 'manage_options' ) || in_array( self::ROLE_ADMIN, (array) $user->roles, true ) ) {
            return true;
        }
        
        // Check if user is assigned to this team
        $user_teams = get_user_meta( $user->ID, 'bkgt_assigned_teams', true );
        
        if ( empty( $user_teams ) ) {
            return false;
        }
        
        return in_array( $team_id, (array) $user_teams, true );
    }
    
    /**
     * Get teams assigned to current user
     * 
     * @return array Array of team IDs
     */
    public static function get_user_teams() {
        $user = wp_get_current_user();
        
        if ( empty( $user->ID ) ) {
            return array();
        }
        
        // Admins get all teams
        if ( current_user_can( 'manage_options' ) || in_array( self::ROLE_ADMIN, (array) $user->roles, true ) ) {
            $teams = get_posts( array(
                'post_type' => 'bkgt_team',
                'numberposts' => -1,
                'fields' => 'ids',
            ) );
            return $teams;
        }
        
        // Others get their assigned teams
        $user_teams = get_user_meta( $user->ID, 'bkgt_assigned_teams', true );
        return ! empty( $user_teams ) ? (array) $user_teams : array();
    }
    
    /**
     * Check if user can manage settings (admin only)
     * 
     * @return bool True if user can manage settings
     */
    public static function can_manage_settings() {
        return current_user_can( 'bkgt_manage_settings' );
    }
    
    /**
     * Check if user can view system logs (admin only)
     * 
     * @return bool True if user can view logs
     */
    public static function can_view_logs() {
        return current_user_can( 'bkgt_view_logs' );
    }
    
    /**
     * Check if user has a specific BKGT role
     * 
     * @param string $role Role to check (use class constants like ROLE_ADMIN)
     * 
     * @return bool True if user has the role
     */
    public static function has_role( $role ) {
        $user = wp_get_current_user();
        return in_array( $role, (array) $user->roles, true );
    }
    
    /**
     * Check if user is a coach
     * 
     * @return bool True if user is a coach
     */
    public static function is_coach() {
        return self::has_role( self::ROLE_COACH );
    }
    
    /**
     * Check if user is a team manager
     * 
     * @return bool True if user is a team manager
     */
    public static function is_team_manager() {
        return self::has_role( self::ROLE_TEAM_MANAGER );
    }
    
    /**
     * Check if user is an admin
     * 
     * @return bool True if user is a BKGT admin
     */
    public static function is_admin() {
        return self::has_role( self::ROLE_ADMIN ) || current_user_can( 'manage_options' );
    }
    
    /**
     * Require specific permission or die
     * 
     * Used at the beginning of admin pages or AJAX handlers
     * 
     * @param string $capability Capability required
     * 
     * @return void Dies if user doesn't have capability
     */
    public static function require_capability( $capability ) {
        if ( ! current_user_can( $capability ) ) {
            BKGT_Logger::warning( "Permission denied for capability: {$capability}", array(
                'user_id' => get_current_user_id(),
                'capability' => $capability,
            ) );
            wp_die( esc_html__( 'Du har inte behörighet att utföra denna åtgärd', 'bkgt' ) );
        }
    }
    
    /**
     * Require team access or die
     * 
     * @param int $team_id Team ID to require access for
     * 
     * @return void Dies if user can't access team
     */
    public static function require_team_access( $team_id ) {
        if ( ! self::can_access_team( $team_id ) ) {
            BKGT_Logger::warning( "Team access denied", array(
                'user_id' => get_current_user_id(),
                'team_id' => $team_id,
            ) );
            wp_die( esc_html__( 'Du har inte behörighet att komma åt detta lag', 'bkgt' ) );
        }
    }
    
    /**
     * Require admin or die
     * 
     * @return void Dies if user is not admin
     */
    public static function require_admin() {
        self::require_capability( 'bkgt_manage_settings' );
    }
}

// Initialize permissions on WordPress load
add_action( 'wp_loaded', array( 'BKGT_Permission', 'init' ) );
