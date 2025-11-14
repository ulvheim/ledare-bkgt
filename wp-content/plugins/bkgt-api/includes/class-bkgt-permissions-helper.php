<?php
/**
 * BKGT Permissions Helper
 * 
 * Utility functions and helpers for working with the permission system
 * 
 * @package BKGT_API
 * @subpackage Permissions
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Permissions_Helper {

    /**
     * Get all resources for a specific category
     *
     * @param string $category Category slug
     * 
     * @return array Resources in category
     */
    public static function get_resources_by_category($category) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bkgt_permission_resources 
                 WHERE category = %s ORDER BY display_name",
                $category
            ),
            ARRAY_A
        );
    }

    /**
     * Get user permissions for a specific category
     *
     * @param int    $user_id User ID
     * @param string $category Category slug
     * 
     * @return array Permissions for category
     */
    public static function get_user_category_permissions($user_id, $category) {
        $all_perms = BKGT_Permissions::get_user_permissions($user_id);
        $resources = self::get_resources_by_category($category);
        $result = array();

        foreach ($resources as $resource) {
            $result[$resource['slug']] = $all_perms[$resource['slug']] ?? array(
                'view' => false,
                'create' => false,
                'edit' => false,
                'delete' => false,
            );
        }

        return $result;
    }

    /**
     * Check if user can perform bulk action
     *
     * @param int    $user_id User ID
     * @param string $resource Resource slug
     * @param string $action Action type
     * 
     * @return bool
     */
    public static function user_can($user_id, $resource, $action = 'view') {
        return BKGT_Permissions::has_permission($user_id, $resource, $action);
    }

    /**
     * Get user role display name
     *
     * @param int $user_id User ID
     * 
     * @return string Role display name
     */
    public static function get_user_role_display($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return 'Unknown';
        }

        // Get first role
        if (empty($user->roles)) {
            return 'No Role';
        }

        $role = reset($user->roles);
        
        // Translate role to display name
        $display_names = array(
            'administrator' => 'Administrator',
            'coach' => 'Coach',
            'team_manager' => 'Team Manager',
            'subscriber' => 'Subscriber',
        );

        return $display_names[$role] ?? ucfirst(str_replace('_', ' ', $role));
    }

    /**
     * Grant temporary coach manager permissions
     *
     * @param int $coach_id Coach user ID
     * @param int $days Number of days override should last
     * @param string $reason Reason for granting
     * 
     * @return bool
     */
    public static function grant_temporary_manager_access($coach_id, $days = 7, $reason = '') {
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        
        // Grant inventory access
        $result1 = BKGT_Permissions::grant_user_override(
            $coach_id,
            'inventory',
            'view',
            true,
            $expires_at,
            $reason ?: "Temporary manager access for {$days} days"
        );

        // Grant equipment management
        $result2 = BKGT_Permissions::grant_user_override(
            $coach_id,
            'inventory',
            'create',
            true,
            $expires_at,
            $reason ?: "Temporary manager access for {$days} days"
        );

        $result3 = BKGT_Permissions::grant_user_override(
            $coach_id,
            'inventory',
            'edit',
            true,
            $expires_at,
            $reason ?: "Temporary manager access for {$days} days"
        );

        $result4 = BKGT_Permissions::grant_user_override(
            $coach_id,
            'inventory',
            'delete',
            true,
            $expires_at,
            $reason ?: "Temporary manager access for {$days} days"
        );

        return $result1 && $result2 && $result3 && $result4;
    }

    /**
     * Revoke all overrides for a user
     *
     * @param int $user_id User ID
     * 
     * @return int Number of overrides revoked
     */
    public static function revoke_all_overrides($user_id) {
        global $wpdb;

        $overrides = BKGT_Permissions::get_user_overrides($user_id);
        $count = 0;

        foreach ($overrides as $override) {
            if (BKGT_Permissions::revoke_user_override(
                $user_id,
                $override['resource'],
                $override['permission']
            )) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get user's active overrides (not expired)
     *
     * @param int $user_id User ID
     * 
     * @return array Active overrides
     */
    public static function get_active_overrides($user_id) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bkgt_user_permissions 
                 WHERE user_id = %d 
                 AND (expires_at IS NULL OR expires_at > NOW())
                 ORDER BY resource, permission",
                $user_id
            ),
            ARRAY_A
        );
    }

    /**
     * Get user's expired overrides
     *
     * @param int $user_id User ID
     * 
     * @return array Expired overrides
     */
    public static function get_expired_overrides($user_id) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bkgt_user_permissions 
                 WHERE user_id = %d 
                 AND expires_at IS NOT NULL 
                 AND expires_at <= NOW()
                 ORDER BY expires_at DESC",
                $user_id
            ),
            ARRAY_A
        );
    }

    /**
     * Clean up expired overrides (run on schedule)
     *
     * @return int Number of expired overrides deleted
     */
    public static function cleanup_expired_overrides() {
        global $wpdb;

        $result = $wpdb->query(
            "DELETE FROM {$wpdb->prefix}bkgt_user_permissions 
             WHERE expires_at IS NOT NULL 
             AND expires_at < NOW()"
        );

        return $result;
    }

    /**
     * Get permission summary for display
     *
     * @param int $user_id User ID
     * 
     * @return array Summary data
     */
    public static function get_permission_summary($user_id) {
        $perms = BKGT_Permissions::get_user_permissions($user_id);
        $total = 0;
        $granted = 0;

        foreach ($perms as $resource => $actions) {
            foreach ($actions as $action => $has_access) {
                $total++;
                if ($has_access) {
                    $granted++;
                }
            }
        }

        return array(
            'user_id' => $user_id,
            'total_permissions' => $total,
            'permissions_granted' => $granted,
            'percentage' => round(($granted / $total) * 100, 1),
            'active_overrides' => count(self::get_active_overrides($user_id)),
            'expired_overrides' => count(self::get_expired_overrides($user_id)),
        );
    }

    /**
     * Export permissions as CSV
     *
     * @param int $user_id User ID (null for all users)
     * 
     * @return string CSV data
     */
    public static function export_permissions_csv($user_id = null) {
        global $wpdb;

        if ($user_id) {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT u.ID, u.user_login, u.user_email, rp.resource, rp.permission, rp.granted, up.expires_at, up.reason
                     FROM {$wpdb->prefix}users u
                     WHERE u.ID = %d",
                    $user_id
                ),
                ARRAY_A
            );
        } else {
            $rows = $wpdb->get_results(
                "SELECT rp.role_slug, rp.resource, rp.permission, rp.granted, rp.created_at, rp.updated_at
                 FROM {$wpdb->prefix}bkgt_role_permissions rp
                 ORDER BY rp.role_slug, rp.resource",
                ARRAY_A
            );
        }

        if (empty($rows)) {
            return '';
        }

        // Get headers
        $headers = array_keys($rows[0]);
        
        // Build CSV
        $csv = '"' . implode('","', $headers) . '"' . "\n";
        
        foreach ($rows as $row) {
            $values = array();
            foreach ($row as $value) {
                $values[] = str_replace('"', '""', $value);
            }
            $csv .= '"' . implode('","', $values) . '"' . "\n";
        }

        return $csv;
    }

    /**
     * Check if user has any permission in a category
     *
     * @param int    $user_id User ID
     * @param string $category Category slug
     * 
     * @return bool
     */
    public static function user_has_category_access($user_id, $category) {
        $perms = self::get_user_category_permissions($user_id, $category);
        
        foreach ($perms as $resource => $actions) {
            foreach ($actions as $action => $has_access) {
                if ($has_access) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Enqueue permission data in frontend script
     *
     * @param string $var_name JavaScript variable name
     */
    public static function enqueue_user_permissions_script($var_name = 'bkgtUserPermissions') {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return;
        }

        $permissions = BKGT_Permissions::get_user_permissions($user_id);
        
        wp_add_inline_script(
            'jquery',
            "window.{$var_name} = " . wp_json_encode($permissions) . ';',
            'before'
        );
    }

    /**
     * Get permission button class based on user access
     *
     * @param int    $user_id User ID
     * @param string $resource Resource slug
     * @param string $permission Permission type
     * @param string $class_allowed CSS class if allowed
     * @param string $class_denied CSS class if denied
     * 
     * @return string CSS class
     */
    public static function get_permission_button_class($user_id, $resource, $permission, $class_allowed = '', $class_denied = 'disabled') {
        return BKGT_Permissions::has_permission($user_id, $resource, $permission) 
            ? $class_allowed 
            : $class_denied;
    }
}
