<?php
/**
 * BKGT Permissions System
 * 
 * Manages role-based and user-specific permissions across the BKGT ecosystem.
 * Provides granular control over resource access with support for overrides.
 * 
 * @package BKGT_API
 * @subpackage Permissions
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Permissions {

    /**
     * Default cache time (1 hour)
     */
    const CACHE_DURATION = 3600;

    /**
     * Permission cache
     */
    private static $permission_cache = array();

    /**
     * Resource definitions
     */
    private static $resources = null;

    /**
     * Initialize permissions on plugin load
     */
    public static function init() {
        add_action('wp_ajax_bkgt_get_permissions', array(__CLASS__, 'ajax_get_user_permissions'));
        add_action('wp_ajax_bkgt_check_permission', array(__CLASS__, 'ajax_check_permission'));
    }

    /**
     * Check if a user has permission for a specific resource/action
     *
     * @param int    $user_id     User ID
     * @param string $resource    Resource identifier (e.g., 'inventory', 'teams')
     * @param string $permission  Permission type (e.g., 'view', 'edit', 'delete')
     * 
     * @return bool True if user has permission
     */
    public static function has_permission($user_id, $resource, $permission) {
        // Admins always have access
        if (self::is_admin_user($user_id)) {
            return true;
        }

        // Check cache first
        $cache_key = "perm_{$user_id}_{$resource}_{$permission}";
        if (isset(self::$permission_cache[$cache_key])) {
            return self::$permission_cache[$cache_key];
        }

        // 1. Check user-specific overrides first (highest priority)
        $user_override = self::get_user_permission_override($user_id, $resource, $permission);
        if ($user_override !== null) {
            // Check expiry
            if (!empty($user_override['expires_at'])) {
                if (strtotime($user_override['expires_at']) < time()) {
                    // Override expired, fall through to role-based
                } else {
                    $result = (bool) $user_override['granted'];
                    self::$permission_cache[$cache_key] = $result;
                    return $result;
                }
            } else {
                $result = (bool) $user_override['granted'];
                self::$permission_cache[$cache_key] = $result;
                return $result;
            }
        }

        // 2. Check role-based permissions
        $user = get_user_by('id', $user_id);
        if (!$user) {
            self::$permission_cache[$cache_key] = false;
            return false;
        }

        foreach ($user->roles as $role) {
            $role_permission = self::get_role_permission($role, $resource, $permission);
            if ($role_permission !== null) {
                $result = (bool) $role_permission['granted'];
                self::$permission_cache[$cache_key] = $result;
                return $result;
            }
        }

        // 3. Default deny (secure by default)
        self::$permission_cache[$cache_key] = false;
        return false;
    }

    /**
     * Get all permissions for a user (for frontend/UI rendering)
     *
     * @param int $user_id User ID
     * 
     * @return array Array of resource => [view, create, edit, delete] permissions
     */
    public static function get_user_permissions($user_id) {
        $permissions = array();
        $resources = self::get_all_resources();

        foreach ($resources as $resource) {
            $permissions[$resource['slug']] = array(
                'view'   => self::has_permission($user_id, $resource['slug'], 'view'),
                'create' => self::has_permission($user_id, $resource['slug'], 'create'),
                'edit'   => self::has_permission($user_id, $resource['slug'], 'edit'),
                'delete' => self::has_permission($user_id, $resource['slug'], 'delete'),
            );
        }

        return $permissions;
    }

    /**
     * Check if user is admin
     *
     * @param int $user_id User ID
     * 
     * @return bool
     */
    private static function is_admin_user($user_id) {
        $user = get_user_by('id', $user_id);
        return $user && in_array('administrator', $user->roles, true);
    }

    /**
     * Get user-specific permission override
     *
     * @param int    $user_id     User ID
     * @param string $resource    Resource slug
     * @param string $permission  Permission type
     * 
     * @return array|null Override data or null
     */
    private static function get_user_permission_override($user_id, $resource, $permission) {
        global $wpdb;

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bkgt_user_permissions 
                 WHERE user_id = %d AND resource = %s AND permission = %s",
                $user_id,
                $resource,
                $permission
            ),
            ARRAY_A
        );

        return $result;
    }

    /**
     * Get role-based permission
     *
     * @param string $role        Role slug
     * @param string $resource    Resource slug
     * @param string $permission  Permission type
     * 
     * @return array|null Permission data or null
     */
    private static function get_role_permission($role, $resource, $permission) {
        global $wpdb;

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bkgt_role_permissions 
                 WHERE role_slug = %s AND resource = %s AND permission = %s",
                $role,
                $resource,
                $permission
            ),
            ARRAY_A
        );

        return $result;
    }

    /**
     * Get all permission resources
     *
     * @return array Array of resources
     */
    private static function get_all_resources() {
        if (self::$resources !== null) {
            return self::$resources;
        }

        global $wpdb;

        self::$resources = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}bkgt_permission_resources ORDER BY category, display_name",
            ARRAY_A
        );

        return self::$resources;
    }

    /**
     * Grant user permission override
     *
     * @param int    $user_id     User ID
     * @param string $resource    Resource slug
     * @param string $permission  Permission type
     * @param bool   $granted     Grant or revoke
     * @param string $expires_at  Optional expiry date (YYYY-MM-DD HH:MM:SS)
     * @param string $reason      Optional reason for the override
     * @param int    $granted_by  User ID of admin who granted it
     * 
     * @return bool|int Insert/update result
     */
    public static function grant_user_override($user_id, $resource, $permission, $granted = true, $expires_at = null, $reason = '', $granted_by = null) {
        global $wpdb;

        $granted_by = $granted_by ?? get_current_user_id();

        // Check if override already exists
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}bkgt_user_permissions 
                 WHERE user_id = %d AND resource = %s AND permission = %s",
                $user_id,
                $resource,
                $permission
            )
        );

        $data = array(
            'user_id' => $user_id,
            'resource' => $resource,
            'permission' => $permission,
            'granted' => (int) $granted,
            'expires_at' => $expires_at,
            'reason' => $reason,
            'granted_by' => $granted_by,
            'updated_at' => current_time('mysql'),
        );

        if ($existing) {
            $result = $wpdb->update(
                "{$wpdb->prefix}bkgt_user_permissions",
                $data,
                array('id' => $existing->id)
            );
        } else {
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert(
                "{$wpdb->prefix}bkgt_user_permissions",
                $data
            );
        }

        // Clear cache
        self::clear_user_cache($user_id);

        // Log the change
        self::log_permission_change('user_override', $user_id, $resource, $permission, $granted, $reason, $granted_by);

        return $result;
    }

    /**
     * Revoke user permission override
     *
     * @param int    $user_id     User ID
     * @param string $resource    Resource slug
     * @param string $permission  Permission type
     * 
     * @return bool Success
     */
    public static function revoke_user_override($user_id, $resource, $permission) {
        global $wpdb;

        $result = $wpdb->delete(
            "{$wpdb->prefix}bkgt_user_permissions",
            array(
                'user_id' => $user_id,
                'resource' => $resource,
                'permission' => $permission,
            )
        );

        // Clear cache
        self::clear_user_cache($user_id);

        return (bool) $result;
    }

    /**
     * Update role permission
     *
     * @param string $role        Role slug
     * @param string $resource    Resource slug
     * @param string $permission  Permission type
     * @param bool   $granted     Grant or revoke
     * 
     * @return bool|int
     */
    public static function update_role_permission($role, $resource, $permission, $granted = true) {
        global $wpdb;

        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}bkgt_role_permissions 
                 WHERE role_slug = %s AND resource = %s AND permission = %s",
                $role,
                $resource,
                $permission
            )
        );

        $data = array(
            'role_slug' => $role,
            'resource' => $resource,
            'permission' => $permission,
            'granted' => (int) $granted,
            'updated_at' => current_time('mysql'),
        );

        if ($existing) {
            $result = $wpdb->update(
                "{$wpdb->prefix}bkgt_role_permissions",
                $data,
                array('id' => $existing->id)
            );
        } else {
            $data['created_at'] = current_time('mysql');
            $result = $wpdb->insert(
                "{$wpdb->prefix}bkgt_role_permissions",
                $data
            );
        }

        // Clear all cache
        self::clear_all_cache();

        // Log the change
        self::log_permission_change('role_permission', null, $resource, $permission, $granted, "Role: {$role}");

        return $result;
    }

    /**
     * Get all role permissions
     *
     * @return array Role permissions structure
     */
    public static function get_all_role_permissions() {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT role_slug, resource, permission, granted FROM {$wpdb->prefix}bkgt_role_permissions ORDER BY role_slug, resource, permission",
            ARRAY_A
        );

        $permissions = array();
        foreach ($rows as $row) {
            if (!isset($permissions[$row['role_slug']])) {
                $permissions[$row['role_slug']] = array();
            }
            if (!isset($permissions[$row['role_slug']][$row['resource']])) {
                $permissions[$row['role_slug']][$row['resource']] = array();
            }
            $permissions[$row['role_slug']][$row['resource']][$row['permission']] = (bool) $row['granted'];
        }

        return $permissions;
    }

    /**
     * Get user permission overrides
     *
     * @param int $user_id User ID
     * 
     * @return array User overrides
     */
    public static function get_user_overrides($user_id) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bkgt_user_permissions WHERE user_id = %d ORDER BY resource, permission",
                $user_id
            ),
            ARRAY_A
        );
    }

    /**
     * Log permission changes for audit trail
     *
     * @param string $action     Action type (role_permission, user_override)
     * @param int    $user_id    User ID (for user overrides)
     * @param string $resource   Resource slug
     * @param string $permission Permission type
     * @param bool   $granted    Grant or revoke
     * @param string $reason     Reason for change
     * @param int    $changed_by User ID who made the change
     * 
     * @return bool
     */
    private static function log_permission_change($action, $user_id, $resource, $permission, $granted, $reason = '', $changed_by = null) {
        global $wpdb;

        $changed_by = $changed_by ?? get_current_user_id();

        return $wpdb->insert(
            "{$wpdb->prefix}bkgt_permission_audit_log",
            array(
                'action' => $action,
                'user_id' => $user_id,
                'resource' => $resource,
                'permission' => $permission,
                'granted' => (int) $granted,
                'reason' => $reason,
                'changed_by' => $changed_by,
                'changed_at' => current_time('mysql'),
            )
        );
    }

    /**
     * Get audit log
     *
     * @param int $limit Number of entries to retrieve
     * @param int $offset Offset for pagination
     * 
     * @return array Audit log entries
     */
    public static function get_audit_log($limit = 100, $offset = 0) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bkgt_permission_audit_log 
                 ORDER BY changed_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            ),
            ARRAY_A
        );
    }

    /**
     * Clear cache for a specific user
     *
     * @param int $user_id User ID
     */
    public static function clear_user_cache($user_id) {
        // Clear from static cache
        foreach (array_keys(self::$permission_cache) as $key) {
            if (strpos($key, "perm_{$user_id}_") === 0) {
                unset(self::$permission_cache[$key]);
            }
        }

        // Clear from transients
        delete_transient("bkgt_perms_{$user_id}");
    }

    /**
     * Clear all permission cache
     */
    public static function clear_all_cache() {
        self::$permission_cache = array();
        self::$resources = null;

        // Could also clear all bkgt_perms_* transients if using persistent cache
    }

    /**
     * AJAX: Get user permissions
     */
    public static function ajax_get_user_permissions() {
        check_ajax_referer('bkgt_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Not authenticated');
        }

        $permissions = self::get_user_permissions($user_id);
        wp_send_json_success($permissions);
    }

    /**
     * AJAX: Check specific permission
     */
    public static function ajax_check_permission() {
        check_ajax_referer('bkgt_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Not authenticated');
        }

        $resource = isset($_POST['resource']) ? sanitize_text_field($_POST['resource']) : '';
        $permission = isset($_POST['permission']) ? sanitize_text_field($_POST['permission']) : '';

        if (!$resource || !$permission) {
            wp_send_json_error('Missing resource or permission');
        }

        $has_perm = self::has_permission($user_id, $resource, $permission);
        wp_send_json_success(array('has_permission' => $has_perm));
    }

    /**
     * Create REST endpoint permission callback
     *
     * @param string $resource   Resource slug
     * @param string $permission Permission type
     * 
     * @return callable Callback function for permission_callback
     */
    public static function rest_permission_callback($resource, $permission = 'view') {
        return function() use ($resource, $permission) {
            $user_id = get_current_user_id();
            if (!$user_id) {
                return new WP_Error('not_authenticated', 'User is not authenticated', array('status' => 401));
            }

            if (!self::has_permission($user_id, $resource, $permission)) {
                return new WP_Error('insufficient_permissions', 'User does not have permission for this resource', array('status' => 403));
            }

            return true;
        };
    }
}

// Initialize on plugin load
if (class_exists('BKGT_Permissions')) {
    BKGT_Permissions::init();
}
