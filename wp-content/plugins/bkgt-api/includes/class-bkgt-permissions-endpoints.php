<?php
/**
 * BKGT Permissions REST API Endpoints
 * 
 * Endpoints for managing permissions, roles, and user overrides
 * 
 * @package BKGT_API
 * @subpackage Permissions
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Permissions_Endpoints {

    /**
     * Register permission endpoints
     */
    public static function register_routes() {
        // Get user permissions (for frontend)
        register_rest_route('bkgt/v1', '/user/permissions', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_user_permissions'),
            'permission_callback' => '__return_true',  // Public, returns current user's permissions
        ));

        // Check specific permission
        register_rest_route('bkgt/v1', '/user/check-permission', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'check_permission'),
            'permission_callback' => '__return_true',
        ));

        // Admin: Get all role permissions
        register_rest_route('bkgt/v1', '/admin/permissions/roles', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_all_role_permissions'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
        ));

        // Admin: Update role permission
        register_rest_route('bkgt/v1', '/admin/permissions/roles/(?P<role>[a-z_]+)/(?P<resource>[a-z_]+)/(?P<permission>[a-z_]+)', array(
            'methods' => 'PUT',
            'callback' => array(__CLASS__, 'update_role_permission'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
        ));

        // Admin: Get user permission overrides
        register_rest_route('bkgt/v1', '/admin/permissions/users/(?P<user_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_user_overrides'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
        ));

        // Admin: Grant user permission override
        register_rest_route('bkgt/v1', '/admin/permissions/users/(?P<user_id>\d+)', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'grant_user_override'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
        ));

        // Admin: Revoke user permission override
        register_rest_route('bkgt/v1', '/admin/permissions/users/(?P<user_id>\d+)/(?P<resource>[a-z_]+)/(?P<permission>[a-z_]+)', array(
            'methods' => 'DELETE',
            'callback' => array(__CLASS__, 'revoke_user_override'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
        ));

        // Admin: Get audit log
        register_rest_route('bkgt/v1', '/admin/permissions/audit-log', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_audit_log'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
        ));

        // Admin: Get all permission resources
        register_rest_route('bkgt/v1', '/admin/permissions/resources', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_permission_resources'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
        ));
    }

    /**
     * Get user permissions
     *
     * @param WP_REST_Request $request
     * 
     * @return WP_REST_Response
     */
    public static function get_user_permissions($request) {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return new WP_REST_Response(
                array('message' => 'User not authenticated'),
                401
            );
        }

        $permissions = BKGT_Permissions::get_user_permissions($user_id);

        return new WP_REST_Response(
            array(
                'user_id' => $user_id,
                'permissions' => $permissions,
            ),
            200
        );
    }

    /**
     * Check specific permission
     *
     * @param WP_REST_Request $request
     * 
     * @return WP_REST_Response
     */
    public static function check_permission($request) {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return new WP_REST_Response(
                array('message' => 'User not authenticated'),
                401
            );
        }

        $params = $request->get_json_params();
        $resource = isset($params['resource']) ? sanitize_text_field($params['resource']) : '';
        $permission = isset($params['permission']) ? sanitize_text_field($params['permission']) : '';

        if (!$resource || !$permission) {
            return new WP_REST_Response(
                array('message' => 'Missing resource or permission parameter'),
                400
            );
        }

        $has_permission = BKGT_Permissions::has_permission($user_id, $resource, $permission);

        return new WP_REST_Response(
            array(
                'user_id' => $user_id,
                'resource' => $resource,
                'permission' => $permission,
                'has_permission' => $has_permission,
            ),
            200
        );
    }

    /**
     * Get all role permissions
     *
     * @return WP_REST_Response
     */
    public static function get_all_role_permissions() {
        $permissions = BKGT_Permissions::get_all_role_permissions();

        return new WP_REST_Response(
            $permissions,
            200
        );
    }

    /**
     * Update role permission
     *
     * @param WP_REST_Request $request
     * 
     * @return WP_REST_Response
     */
    public static function update_role_permission($request) {
        $role = $request->get_param('role');
        $resource = $request->get_param('resource');
        $permission = $request->get_param('permission');

        $params = $request->get_json_params();
        $granted = isset($params['granted']) ? (bool) $params['granted'] : true;

        $result = BKGT_Permissions::update_role_permission($role, $resource, $permission, $granted);

        if (!$result) {
            return new WP_REST_Response(
                array('message' => 'Failed to update permission'),
                500
            );
        }

        return new WP_REST_Response(
            array(
                'message' => 'Permission updated',
                'role' => $role,
                'resource' => $resource,
                'permission' => $permission,
                'granted' => $granted,
            ),
            200
        );
    }

    /**
     * Get user permission overrides
     *
     * @param WP_REST_Request $request
     * 
     * @return WP_REST_Response
     */
    public static function get_user_overrides($request) {
        $user_id = (int) $request->get_param('user_id');

        $overrides = BKGT_Permissions::get_user_overrides($user_id);

        return new WP_REST_Response(
            $overrides,
            200
        );
    }

    /**
     * Grant user permission override
     *
     * @param WP_REST_Request $request
     * 
     * @return WP_REST_Response
     */
    public static function grant_user_override($request) {
        $user_id = (int) $request->get_param('user_id');
        $params = $request->get_json_params();

        $resource = isset($params['resource']) ? sanitize_text_field($params['resource']) : '';
        $permission = isset($params['permission']) ? sanitize_text_field($params['permission']) : '';
        $granted = isset($params['granted']) ? (bool) $params['granted'] : true;
        $expires_at = isset($params['expires_at']) ? sanitize_text_field($params['expires_at']) : null;
        $reason = isset($params['reason']) ? sanitize_text_field($params['reason']) : '';

        if (!$resource || !$permission) {
            return new WP_REST_Response(
                array('message' => 'Missing resource or permission parameter'),
                400
            );
        }

        // Validate expires_at format if provided
        if ($expires_at) {
            $expires_time = strtotime($expires_at);
            if ($expires_time === false) {
                return new WP_REST_Response(
                    array('message' => 'Invalid expires_at format. Use YYYY-MM-DD HH:MM:SS'),
                    400
                );
            }
        }

        $result = BKGT_Permissions::grant_user_override(
            $user_id,
            $resource,
            $permission,
            $granted,
            $expires_at,
            $reason,
            get_current_user_id()
        );

        if (!$result) {
            return new WP_REST_Response(
                array('message' => 'Failed to grant override'),
                500
            );
        }

        return new WP_REST_Response(
            array(
                'message' => 'Override granted',
                'user_id' => $user_id,
                'resource' => $resource,
                'permission' => $permission,
                'granted' => $granted,
                'expires_at' => $expires_at,
                'reason' => $reason,
            ),
            200
        );
    }

    /**
     * Revoke user permission override
     *
     * @param WP_REST_Request $request
     * 
     * @return WP_REST_Response
     */
    public static function revoke_user_override($request) {
        $user_id = (int) $request->get_param('user_id');
        $resource = $request->get_param('resource');
        $permission = $request->get_param('permission');

        $result = BKGT_Permissions::revoke_user_override($user_id, $resource, $permission);

        if (!$result) {
            return new WP_REST_Response(
                array('message' => 'Failed to revoke override or override not found'),
                400
            );
        }

        return new WP_REST_Response(
            array(
                'message' => 'Override revoked',
                'user_id' => $user_id,
                'resource' => $resource,
                'permission' => $permission,
            ),
            200
        );
    }

    /**
     * Get audit log
     *
     * @param WP_REST_Request $request
     * 
     * @return WP_REST_Response
     */
    public static function get_audit_log($request) {
        $limit = min((int) $request->get_param('limit', 100), 500);
        $offset = (int) $request->get_param('offset', 0);

        $audit_log = BKGT_Permissions::get_audit_log($limit, $offset);

        return new WP_REST_Response(
            $audit_log,
            200
        );
    }

    /**
     * Get permission resources
     *
     * @return WP_REST_Response
     */
    public static function get_permission_resources() {
        global $wpdb;

        $resources = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}bkgt_permission_resources ORDER BY category, display_name",
            ARRAY_A
        );

        return new WP_REST_Response(
            $resources,
            200
        );
    }

    /**
     * Check if user is admin for permission endpoints
     *
     * @return bool|WP_Error
     */
    public static function check_admin_permission() {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'not_authenticated',
                'User is not authenticated',
                array('status' => 401)
            );
        }

        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'insufficient_permissions',
                'User does not have permission to manage permissions',
                array('status' => 403)
            );
        }

        return true;
    }
}

// Register routes on REST API init
add_action('rest_api_init', array('BKGT_Permissions_Endpoints', 'register_routes'));
