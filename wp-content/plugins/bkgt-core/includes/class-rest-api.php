<?php
/**
 * BKGT REST API Class
 *
 * Handles REST API endpoints for BKGT system
 *
 * @package BKGT_Core
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BKGT_REST_API class
 */
class BKGT_REST_API {

    /**
     * Namespace for REST API endpoints
     */
    const API_NAMESPACE = 'bkgt/v1';

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // User permissions endpoint
        register_rest_route( self::API_NAMESPACE, '/user/permissions', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_user_permissions' ),
            'permission_callback' => array( $this, 'check_user_permissions' ),
            'args'                => array(),
        ) );

        // Admin users endpoint
        register_rest_route( self::API_NAMESPACE, '/admin/users', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_admin_users' ),
            'permission_callback' => array( $this, 'check_admin_permissions' ),
            'args'                => array(
                'page'     => array(
                    'default'           => 1,
                    'sanitize_callback' => 'absint',
                ),
                'per_page' => array(
                    'default'           => 20,
                    'sanitize_callback' => 'absint',
                ),
                'search'   => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'role'     => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );

        // Update user role endpoint
        register_rest_route( self::API_NAMESPACE, '/admin/users/(?P<user_id>\d+)/role', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'update_user_role' ),
            'permission_callback' => array( $this, 'check_admin_permissions' ),
            'args'                => array(
                'user_id' => array(
                    'required'          => true,
                    'sanitize_callback' => 'absint',
                ),
                'role'    => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );
    }

    /**
     * Get current user permissions
     *
     * @return WP_REST_Response
     */
    public function get_user_permissions() {
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return new WP_Error( 'not_logged_in', 'User not logged in', array( 'status' => 401 ) );
        }

        $user = get_user_by( 'id', $user_id );
        $permissions = array();

        if ( $user ) {
            // Get user roles
            $roles = $user->roles;

            // Get capabilities for each role
            foreach ( $roles as $role_key ) {
                $role = get_role( $role_key );
                if ( $role ) {
                    $permissions = array_merge( $permissions, array_keys( $role->capabilities ) );
                }
            }

            // Remove duplicates
            $permissions = array_unique( $permissions );
        }

        return new WP_REST_Response( array(
            'user_id'     => $user_id,
            'roles'       => $roles,
            'permissions' => $permissions,
        ), 200 );
    }

    /**
     * Get users for admin management
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_admin_users( $request ) {
        $page     = $request->get_param( 'page' );
        $per_page = $request->get_param( 'per_page' );
        $search   = $request->get_param( 'search' );
        $role     = $request->get_param( 'role' );

        $args = array(
            'number'  => $per_page,
            'offset'  => ( $page - 1 ) * $per_page,
            'orderby' => 'display_name',
            'order'   => 'ASC',
        );

        // Add search if provided
        if ( ! empty( $search ) ) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
        }

        // Add role filter if provided
        if ( ! empty( $role ) ) {
            $args['role'] = $role;
        }

        $user_query = new WP_User_Query( $args );
        $users = $user_query->get_results();
        $total_users = $user_query->get_total();

        $user_data = array();

        foreach ( $users as $user ) {
            $user_data[] = array(
                'id'           => $user->ID,
                'username'     => $user->user_login,
                'email'        => $user->user_email,
                'display_name' => $user->display_name,
                'roles'        => $user->roles,
                'registered'   => $user->user_registered,
                'last_login'   => get_user_meta( $user->ID, 'last_login', true ),
            );
        }

        return new WP_REST_Response( array(
            'users'       => $user_data,
            'total'       => $total_users,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_users / $per_page ),
        ), 200 );
    }

    /**
     * Update user role
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function update_user_role( $request ) {
        $user_id = $request->get_param( 'user_id' );
        $role    = $request->get_param( 'role' );

        // Validate user exists
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return new WP_Error( 'user_not_found', 'User not found', array( 'status' => 404 ) );
        }

        // Prevent users from modifying their own roles or other admins
        $current_user_id = get_current_user_id();
        if ( $user_id === $current_user_id ) {
            return new WP_Error( 'cannot_modify_self', 'Cannot modify your own role', array( 'status' => 403 ) );
        }

        // Check if current user has admin role
        if ( ! current_user_can( 'bkgt_admin' ) && ! current_user_can( 'administrator' ) ) {
            return new WP_Error( 'insufficient_permissions', 'Admin permissions required', array( 'status' => 403 ) );
        }

        // Validate role
        $allowed_roles = array_keys( BKGT_Permission::get_all_roles() );
        if ( ! in_array( $role, $allowed_roles ) && ! in_array( $role, array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ) ) ) {
            return new WP_Error( 'invalid_role', 'Invalid role specified', array( 'status' => 400 ) );
        }

        // Remove all existing roles and add the new one
        $user->set_role( $role );

        // Log the role change
        BKGT_Permission::log_permission_change( $current_user_id, $user_id, 'role_change', array(
            'old_roles' => $user->roles,
            'new_role'  => $role,
        ) );

        return new WP_REST_Response( array(
            'success' => true,
            'user_id' => $user_id,
            'role'    => $role,
            'message' => 'User role updated successfully',
        ), 200 );
    }

    /**
     * Check if user can access their own permissions
     *
     * @return bool
     */
    public function check_user_permissions() {
        return is_user_logged_in();
    }

    /**
     * Check if user has admin permissions
     *
     * @return bool
     */
    public function check_admin_permissions() {
        return current_user_can( 'bkgt_admin' ) || current_user_can( 'administrator' );
    }
}