<?php
/**
 * BKGT Permissions Database Setup
 * 
 * Creates and manages the permission system database tables
 * 
 * @package BKGT_API
 * @subpackage Permissions
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Permissions_Database {

    /**
     * Create permission tables
     */
    public static function create_tables() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Role permissions table
        $sql_role_perms = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bkgt_role_permissions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            role_slug varchar(64) NOT NULL,
            resource varchar(128) NOT NULL,
            permission varchar(64) NOT NULL,
            granted tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_role_perm (role_slug, resource, permission),
            KEY idx_role (role_slug),
            KEY idx_resource (resource)
        ) $charset_collate;";

        dbDelta($sql_role_perms);

        // User permission overrides table
        $sql_user_perms = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bkgt_user_permissions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            resource varchar(128) NOT NULL,
            permission varchar(64) NOT NULL,
            granted tinyint(1) NOT NULL DEFAULT 1,
            expires_at datetime NULL,
            reason text,
            granted_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_perm (user_id, resource, permission),
            KEY idx_user (user_id),
            KEY idx_resource (resource),
            KEY idx_expires (expires_at),
            CONSTRAINT fk_user_id FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";

        dbDelta($sql_user_perms);

        // Permission resources table
        $sql_resources = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bkgt_permission_resources (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            resource_slug varchar(128) NOT NULL,
            display_name varchar(255),
            description text,
            category varchar(64),
            required_for_frontend tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY resource_slug (resource_slug),
            KEY idx_category (category)
        ) $charset_collate;";

        dbDelta($sql_resources);

        // Permissions table (actions)
        $sql_perms = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bkgt_permissions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            permission_slug varchar(64) NOT NULL,
            display_name varchar(255),
            description text,
            category varchar(64),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY permission_slug (permission_slug),
            KEY idx_category (category)
        ) $charset_collate;";

        dbDelta($sql_perms);

        // Permission audit log table
        $sql_audit = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bkgt_permission_audit_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            action varchar(64) NOT NULL,
            user_id bigint(20) NULL,
            resource varchar(128),
            permission varchar(64),
            granted tinyint(1),
            reason text,
            changed_by bigint(20),
            changed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user (user_id),
            KEY idx_changed_by (changed_by),
            KEY idx_changed_at (changed_at),
            KEY idx_resource (resource)
        ) $charset_collate;";

        dbDelta($sql_audit);

        // Initialize permission definitions
        self::initialize_permission_definitions();

        // Initialize resource definitions
        self::initialize_resource_definitions();

        // Initialize role permissions
        self::initialize_role_permissions();
    }

    /**
     * Initialize permission definitions
     */
    private static function initialize_permission_definitions() {
        global $wpdb;

        $permissions = array(
            array('view', 'View', 'View/read resources', 'read'),
            array('create', 'Create', 'Create new resources', 'write'),
            array('edit', 'Edit', 'Modify existing resources', 'write'),
            array('delete', 'Delete', 'Delete resources', 'delete'),
            array('manage', 'Manage', 'Full management access', 'admin'),
        );

        foreach ($permissions as $perm) {
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT IGNORE INTO {$wpdb->prefix}bkgt_permissions (permission_slug, display_name, description, category) 
                     VALUES (%s, %s, %s, %s)",
                    $perm[0], $perm[1], $perm[2], $perm[3]
                )
            );
        }
    }

    /**
     * Initialize resource definitions
     */
    private static function initialize_resource_definitions() {
        global $wpdb;

        $resources = array(
            array('inventory', 'Inventory', 'Equipment and inventory management', 'inventory', 1),
            array('equipment', 'Equipment', 'Equipment management', 'inventory', 1),
            array('manufacturers', 'Manufacturers', 'Manufacturer management', 'inventory', 0),
            array('item_types', 'Item Types', 'Item type management', 'inventory', 0),
            array('assignments', 'Assignments', 'Equipment assignments', 'inventory', 1),
            array('locations', 'Locations', 'Location management', 'inventory', 0),
            array('teams', 'Teams', 'Team management', 'teams', 1),
            array('players', 'Players', 'Player management', 'teams', 1),
            array('events', 'Events', 'Event management', 'teams', 1),
            array('documents', 'Documents', 'Document management', 'documents', 1),
            array('admin_settings', 'Admin Settings', 'Administrative settings', 'admin', 0),
            array('api_keys', 'API Keys', 'API key management', 'admin', 0),
        );

        foreach ($resources as $resource) {
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT IGNORE INTO {$wpdb->prefix}bkgt_permission_resources 
                     (resource_slug, display_name, description, category, required_for_frontend) 
                     VALUES (%s, %s, %s, %s, %d)",
                    $resource[0], $resource[1], $resource[2], $resource[3], $resource[4]
                )
            );
        }
    }

    /**
     * Initialize default role permissions
     * 
     * Sets up default permissions for Coach, Team Manager, and Admin roles
     */
    private static function initialize_role_permissions() {
        global $wpdb;

        // Skip if already initialized
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_role_permissions"
        );
        if ($count > 0) {
            return;
        }

        // Define role permission matrix
        $role_permissions = array(
            // COACH ROLE
            'coach' => array(
                // Inventory (limited to assigned items via API filtering)
                'inventory' => array(
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ),
                'equipment' => array(
                    'view' => false,
                    'create' => false,
                    'edit' => false,
                    'delete' => false,
                ),
                // Teams (own team only)
                'teams' => array(
                    'view' => true,
                    'create' => false,
                    'edit' => false,  // Can edit own team metadata
                    'delete' => false,
                ),
                // Players (own team only)
                'players' => array(
                    'view' => true,
                    'create' => true,
                    'edit' => true,
                    'delete' => false,
                ),
                // Documents
                'documents' => array(
                    'view' => true,
                    'create' => true,
                    'edit' => true,
                    'delete' => false,
                ),
                // Events
                'events' => array(
                    'view' => true,
                    'create' => true,
                    'edit' => true,
                    'delete' => false,
                ),
            ),

            // TEAM_MANAGER ROLE
            'team_manager' => array(
                // Inventory (full access)
                'inventory' => array(
                    'view' => true,
                    'create' => true,
                    'edit' => true,
                    'delete' => true,
                ),
                'equipment' => array(
                    'view' => true,
                    'create' => true,
                    'edit' => true,
                    'delete' => true,
                ),
                'manufacturers' => array(
                    'view' => true,
                    'create' => true,
                    'edit' => true,
                    'delete' => true,
                ),
                'item_types' => array(
                    'view' => true,
                    'create' => true,
                    'edit' => true,
                    'delete' => true,
                ),
                'assignments' => array(
                    'view' => true,
                    'create' => true,
                    'edit' => true,
                    'delete' => true,
                ),
                'locations' => array(
                    'view' => true,
                    'create' => true,
                    'edit' => true,
                    'delete' => true,
                ),
                // Teams
                'teams' => array(
                    'view' => true,
                    'create' => true,
                    'edit' => true,
                    'delete' => true,
                ),
                // Players
                'players' => array(
                    'view' => true,
                    'create' => true,
                    'edit' => true,
                    'delete' => true,
                ),
                // Documents
                'documents' => array(
                    'view' => true,
                    'create' => true,
                    'edit' => true,
                    'delete' => true,
                ),
                // Events
                'events' => array(
                    'view' => true,
                    'create' => true,
                    'edit' => true,
                    'delete' => true,
                ),
            ),
        );

        // Insert permissions
        foreach ($role_permissions as $role => $resources) {
            foreach ($resources as $resource => $permissions) {
                foreach ($permissions as $permission => $granted) {
                    $wpdb->insert(
                        "{$wpdb->prefix}bkgt_role_permissions",
                        array(
                            'role_slug' => $role,
                            'resource' => $resource,
                            'permission' => $permission,
                            'granted' => (int) $granted,
                        )
                    );
                }
            }
        }
    }

    /**
     * Check if tables exist
     *
     * @return bool
     */
    public static function tables_exist() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bkgt_role_permissions';
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }

    /**
     * Drop tables (for plugin uninstall)
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = array(
            "{$wpdb->prefix}bkgt_permission_audit_log",
            "{$wpdb->prefix}bkgt_permissions",
            "{$wpdb->prefix}bkgt_permission_resources",
            "{$wpdb->prefix}bkgt_user_permissions",
            "{$wpdb->prefix}bkgt_role_permissions",
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
}
