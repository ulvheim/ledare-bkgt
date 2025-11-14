<?php
/**
 * Plugin Name: BKGT API
 * Plugin URI: https://github.com/your-repo/bkgt-api
 * Description: Secure REST API for mobile and desktop applications to access BKGT features. Provides JWT authentication, comprehensive endpoints for teams, players, events, documents, and statistics with enterprise-grade security.
 * Version: 2.4.0
 * Author: BKGT Development Team
 * License: GPL v2 or later
 * Text Domain: bkgt-api
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Requires Plugins: bkgt-core, bkgt-data-scraping
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BKGT_API_VERSION', '2.4.0');
define('BKGT_API_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKGT_API_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BKGT_API_NAMESPACE', 'bkgt/v1');

// Include required files with error handling
$required_files = array(
    'includes/class-bkgt-permissions-database.php',
    'includes/class-bkgt-permissions.php',
    'includes/class-bkgt-permissions-endpoints.php',
    'includes/class-bkgt-permissions-helper.php',
    'includes/class-bkgt-api.php',
    'includes/class-bkgt-auth.php',
    'includes/class-bkgt-endpoints.php',
    'includes/class-bkgt-security.php',
    'includes/class-bkgt-notifications.php',
    'includes/class-bkgt-service-admin.php',
    'includes/class-bkgt-service-client.php',
    'includes/class-bkgt-updates.php',
    'admin/class-bkgt-api-admin.php'
);

foreach ($required_files as $file) {
    $file_path = BKGT_API_PLUGIN_DIR . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        error_log('BKGT API: Required file not found: ' . $file_path);
        // Don't exit - allow plugin to load partially for debugging
    }
}

/**
 * Main BKGT API Class
 */
class BKGT_API_Plugin {

    /**
     * Single instance of the plugin
     */
    private static $instance = null;

    /**
     * API handler instance
     */
    public $api;

    /**
     * Auth handler instance
     */
    public $auth;

    /**
     * Endpoints handler instance
     */
    public $endpoints;

    /**
     * Security handler instance
     */
    public $security;

    /**
     * Notifications handler instance
     */
    public $notifications;

    /**
     * Admin handler instance
     */
    public $admin;

    /**
     * Updates handler instance
     */
    public $updates;

    /**
     * Get single instance of the plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->init_components();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('plugins_loaded', array($this, 'register_ajax_handlers'));
        add_action('plugins_loaded', array($this, 'load_inventory_classes'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('init', array($this, 'init_plugin'));
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize core components
        if (class_exists('BKGT_API_Core')) {
            $this->api = new BKGT_API_Core();
        }

        if (class_exists('BKGT_API_Auth')) {
            $this->auth = new BKGT_API_Auth();
        }

        if (class_exists('BKGT_API_Endpoints')) {
            $this->endpoints = new BKGT_API_Endpoints();
        }

        if (class_exists('BKGT_API_Security')) {
            $this->security = new BKGT_API_Security();
        }

        if (class_exists('BKGT_API_Notifications')) {
            $this->notifications = new BKGT_API_Notifications();
        }

        // Initialize admin interface
        if ((is_admin() || defined('DOING_AJAX')) && class_exists('BKGT_API_Admin')) {
            $this->admin = new BKGT_API_Admin();
        }

        // Initialize updates handler
        if (class_exists('BKGT_API_Updates')) {
            $this->updates = new BKGT_API_Updates();
        }
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'bkgt-api',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Register AJAX handlers
     */
    public function register_ajax_handlers() {
        // Register AJAX handlers (always available)
        add_action('wp_ajax_bkgt_api_create_key', array($this, 'ajax_create_api_key'));
        add_action('wp_ajax_bkgt_api_revoke_key', array($this, 'ajax_revoke_api_key'));
        add_action('wp_ajax_bkgt_api_get_logs', array($this, 'ajax_get_logs'));
        add_action('wp_ajax_bkgt_api_get_security_logs', array($this, 'ajax_get_security_logs'));
        add_action('wp_ajax_bkgt_api_mark_notification_read', array($this, 'ajax_mark_notification_read'));
        add_action('wp_ajax_bkgt_api_get_stats', array($this, 'ajax_get_stats'));
        
        // Enqueue admin scripts for BKGT API pages
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'bkgt-api') === false) {
            return;
        }

        wp_enqueue_style(
            'bkgt-api-admin',
            BKGT_API_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            BKGT_API_VERSION
        );

        wp_enqueue_script(
            'bkgt-api-admin',
            BKGT_API_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            BKGT_API_VERSION,
            true
        );

        wp_localize_script('bkgt-api-admin', 'bkgt_api_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkgt_api_admin_nonce'),
            'strings' => array(
                'confirm_revoke' => __('Are you sure you want to revoke this API key?', 'bkgt-api'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'bkgt-api'),
                'loading' => __('Loading...', 'bkgt-api'),
                'error' => __('An error occurred. Please try again.', 'bkgt-api'),
                'success' => __('Operation completed successfully.', 'bkgt-api'),
            ),
        ));
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Ensure inventory classes are loaded for REST API requests
        $this->load_inventory_classes();

        if ($this->endpoints) {
            $this->endpoints->register_routes();
        }
    }

    /**
     * Initialize plugin
     */
    public function init_plugin() {
        // Check dependencies
        $this->check_dependencies();

        // Initialize security measures
        if ($this->security) {
            $this->security->init();
        }

        // Initialize notifications
        if ($this->notifications) {
            $this->notifications->init();
        }
    }

    /**
     * Check plugin dependencies
     */
    private function check_dependencies() {
        $required_plugins = array(
            'bkgt-core/bkgt-core.php' => 'BKGT Core',
            'bkgt-data-scraping/bkgt-data-scraping.php' => 'BKGT Data Scraping',
            'bkgt-inventory/bkgt-inventory.php' => 'BKGT Inventory System'
        );

        $missing_plugins = array();

        foreach ($required_plugins as $plugin_file => $plugin_name) {
            if (!is_plugin_active($plugin_file)) {
                $missing_plugins[] = $plugin_name;
            }
        }

        if (!empty($missing_plugins)) {
            add_action('admin_notices', function() use ($missing_plugins) {
                $plugin_list = implode(', ', $missing_plugins);
                echo '<div class="notice notice-error"><p>';
                echo sprintf(
                    __('BKGT API requires the following plugins to be active: %s', 'bkgt-api'),
                    $plugin_list
                );
                echo '</p></div>';
            });

            // Deactivate this plugin
            deactivate_plugins(plugin_basename(__FILE__));
        } else {
            // Dependencies are met - plugin will continue loading
        }
    }

    /**
     * Load BKGT Inventory classes
     */
    public function load_inventory_classes() {
        // Check if bkgt-inventory plugin is active
        if (!is_plugin_active('bkgt-inventory/bkgt-inventory.php')) {
            return;
        }

        $inventory_files = array(
            'includes/class-database.php',
            'includes/class-history.php',
            'includes/class-assignment.php'
        );

        foreach ($inventory_files as $file) {
            $file_path = WP_PLUGIN_DIR . '/bkgt-inventory/' . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables if needed
        $this->create_tables();

        // Set default options
        $this->set_default_options();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up scheduled events
        wp_clear_scheduled_hook('bkgt_api_cleanup');

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create necessary database tables
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // API keys table
        $api_keys_table = "CREATE TABLE {$wpdb->prefix}bkgt_api_keys (
            id int(11) NOT NULL AUTO_INCREMENT,
            api_key varchar(64) NOT NULL,
            api_secret varchar(128) NOT NULL,
            name varchar(100) NOT NULL,
            permissions text,
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_used datetime DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY api_key (api_key),
            KEY created_by (created_by),
            KEY is_active (is_active)
        ) $charset_collate;";

        // API logs table
        $api_logs_table = "CREATE TABLE {$wpdb->prefix}bkgt_api_logs (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            api_key_id int(11) DEFAULT NULL,
            method varchar(10) NOT NULL,
            endpoint varchar(500) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            request_data longtext,
            response_code int(11) DEFAULT NULL,
            response_time float DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY api_key_id (api_key_id),
            KEY method (method),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Document versions table
        $document_versions_table = "CREATE TABLE {$wpdb->prefix}bkgt_document_versions (
            id int(11) NOT NULL AUTO_INCREMENT,
            document_id bigint(20) unsigned NOT NULL,
            version_number int(11) NOT NULL,
            title text NOT NULL,
            content longtext NOT NULL,
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            change_summary text,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY version_number (version_number),
            KEY created_by (created_by)
        ) $charset_collate;";

        // Document permissions table
        $document_permissions_table = "CREATE TABLE {$wpdb->prefix}bkgt_document_permissions (
            id int(11) NOT NULL AUTO_INCREMENT,
            document_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            access_type enum('read','write','manage') NOT NULL DEFAULT 'read',
            granted_by bigint(20) unsigned NOT NULL,
            granted_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY document_user (document_id, user_id),
            KEY document_id (document_id),
            KEY user_id (user_id),
            KEY access_type (access_type)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($api_keys_table);
        dbDelta($api_logs_table);
        dbDelta($document_versions_table);
        dbDelta($document_permissions_table);
    }

    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'bkgt_api_version' => BKGT_API_VERSION,
            'jwt_secret_key' => wp_generate_password(64, true, true),
            'api_rate_limit' => 100, // requests per minute
            'api_rate_limit_window' => 60, // seconds
            'jwt_expiry' => 900, // 15 minutes
            'refresh_token_expiry' => 604800, // 7 days
            'cors_allowed_origins' => array(),
            'api_debug_mode' => false,
            'api_logging_enabled' => true,
            'api_cache_enabled' => true,
            'api_cache_ttl' => 300, // 5 minutes
        );

        foreach ($defaults as $option => $value) {
            if (!get_option($option)) {
                add_option($option, $value);
            }
        }
    }

    /**
     * AJAX handler for creating API keys
     */
    public function ajax_create_api_key() {
        check_ajax_referer('bkgt_api_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
        }

        $name = sanitize_text_field($_POST['key_name']);
        $permissions = isset($_POST['key_permissions']) ? $_POST['key_permissions'] : array();

        if (empty($name)) {
            wp_send_json_error(__('Key name is required.', 'bkgt-api'));
        }

        $auth = new BKGT_API_Auth();
        $api_key = $auth->create_api_key(get_current_user_id(), $name, $permissions);

        if (!$api_key) {
            wp_send_json_error(__('Failed to create API key.', 'bkgt-api'));
        }

        do_action('bkgt_api_key_created', get_current_user_id(), array('name' => $name));

        wp_send_json_success(array(
            'message' => __('API key created successfully.', 'bkgt-api'),
            'api_key' => $api_key,
        ));
    }

    /**
     * AJAX handler for revoking API keys
     */
    public function ajax_revoke_api_key() {
        check_ajax_referer('bkgt_api_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
        }

        $key_id = intval($_POST['key_id']);

        $auth = new BKGT_API_Auth();
        $result = $auth->revoke_api_key($key_id, get_current_user_id());

        if (!$result) {
            wp_send_json_error(__('Failed to revoke API key.', 'bkgt-api'));
        }

        do_action('bkgt_api_key_revoked', get_current_user_id(), array('id' => $key_id));

        wp_send_json_success(__('API key revoked successfully.', 'bkgt-api'));
    }

    /**
     * AJAX handler for getting logs
     */
    public function ajax_get_logs() {
        check_ajax_referer('bkgt_api_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
        }

        // Implementation for filtered logs
        wp_send_json_success();
    }

    /**
     * AJAX handler for getting security logs
     */
    public function ajax_get_security_logs() {
        check_ajax_referer('bkgt_api_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
        }

        // Implementation for security logs
        wp_send_json_success();
    }

    /**
     * AJAX handler for marking notifications as read
     */
    public function ajax_mark_notification_read() {
        check_ajax_referer('bkgt_api_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
        }

        $notification_id = intval($_POST['notification_id']);

        // Implementation for marking notification as read
        wp_send_json_success();
    }

    /**
     * AJAX handler for getting stats
     */
    public function ajax_get_stats() {
        check_ajax_referer('bkgt_api_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
        }

        // Implementation for getting stats
        wp_send_json_success();
    }
}

/**
 * Initialize the plugin
 */
function bkgt_api() {
    return BKGT_API_Plugin::get_instance();
}

// Register activation/deactivation hooks
register_activation_hook(__FILE__, 'bkgt_api_activate');
register_deactivation_hook(__FILE__, 'bkgt_api_deactivate');

/**
 * Plugin activation callback
 */
function bkgt_api_activate() {
    // Create permission tables
    if (class_exists('BKGT_Permissions_Database')) {
        BKGT_Permissions_Database::create_tables();
    }

    $plugin = bkgt_api();
    if (method_exists($plugin, 'activate')) {
        $plugin->activate();
    }
}

/**
 * Plugin deactivation callback
 */
function bkgt_api_deactivate() {
    $plugin = bkgt_api();
    if (method_exists($plugin, 'deactivate')) {
        $plugin->deactivate();
    }
}

// Start the plugin
bkgt_api();

// Global AJAX handler functions
function bkgt_api_ajax_create_key() {
    check_ajax_referer('bkgt_api_admin_nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
    }

    $name = sanitize_text_field($_POST['key_name']);
    $permissions = isset($_POST['key_permissions']) ? $_POST['key_permissions'] : array();

    if (empty($name)) {
        wp_send_json_error(__('Key name is required.', 'bkgt-api'));
    }

    $auth = new BKGT_API_Auth();
    $api_key = $auth->create_api_key(get_current_user_id(), $name, $permissions);

    if (!$api_key) {
        wp_send_json_error(__('Failed to create API key.', 'bkgt-api'));
    }

    do_action('bkgt_api_key_created', get_current_user_id(), array('name' => $name));

    wp_send_json_success(array(
        'message' => __('API key created successfully.', 'bkgt-api'),
        'api_key' => $api_key,
    ));
}

function bkgt_api_ajax_revoke_key() {
    check_ajax_referer('bkgt_api_admin_nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
    }

    $key_id = intval($_POST['key_id']);

    $auth = new BKGT_API_Auth();
    $result = $auth->revoke_api_key($key_id, get_current_user_id());

    if (!$result) {
        wp_send_json_error(__('Failed to revoke API key.', 'bkgt-api'));
    }

    do_action('bkgt_api_key_revoked', get_current_user_id(), array('id' => $key_id));

    wp_send_json_success(__('API key revoked successfully.', 'bkgt-api'));
}

function bkgt_api_ajax_get_logs() {
    check_ajax_referer('bkgt_api_admin_nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
    }

    // Implementation for filtered logs
    wp_send_json_success();
}

function bkgt_api_ajax_get_security_logs() {
    check_ajax_referer('bkgt_api_admin_nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
    }

    // Implementation for security logs
    wp_send_json_success();
}

function bkgt_api_ajax_mark_notification_read() {
    check_ajax_referer('bkgt_api_admin_nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
    }

    $notification_id = intval($_POST['notification_id']);

    // Implementation for marking notification as read
    wp_send_json_success();
}

function bkgt_api_ajax_get_stats() {
    check_ajax_referer('bkgt_api_admin_nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
    }

    // Implementation for getting stats
    wp_send_json_success();
}

function bkgt_api_enqueue_admin_scripts($hook) {
    // Load on all admin pages for now to ensure scripts are available
    // TODO: Restrict to specific pages once working
    // if (strpos($hook, 'bkgt-api') === false) {
    //     return;
    // }

    wp_enqueue_style(
        'bkgt-api-admin',
        BKGT_API_PLUGIN_URL . 'admin/css/admin.css',
        array(),
        BKGT_API_VERSION
    );

    wp_enqueue_script(
        'bkgt-api-admin',
        BKGT_API_PLUGIN_URL . 'admin/js/admin.js',
        array('jquery'),
        BKGT_API_VERSION,
        true
    );

    wp_localize_script('bkgt-api-admin', 'bkgt_api_admin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bkgt_api_admin_nonce'),
        'strings' => array(
            'confirm_revoke' => __('Are you sure you want to revoke this API key?', 'bkgt-api'),
            'confirm_delete' => __('Are you sure you want to delete this item?', 'bkgt-api'),
            'loading' => __('Loading...', 'bkgt-api'),
            'error' => __('An error occurred. Please try again.', 'bkgt-api'),
            'success' => __('Operation completed successfully.', 'bkgt-api'),
        ),
    ));
}

// Register AJAX handlers immediately
add_action('wp_ajax_bkgt_api_create_key', 'bkgt_api_ajax_create_key');
add_action('wp_ajax_bkgt_api_revoke_key', 'bkgt_api_ajax_revoke_key');
add_action('wp_ajax_bkgt_api_get_logs', 'bkgt_api_ajax_get_logs');
add_action('wp_ajax_bkgt_api_get_security_logs', 'bkgt_api_ajax_get_security_logs');
add_action('wp_ajax_bkgt_api_mark_notification_read', 'bkgt_api_ajax_mark_notification_read');
add_action('wp_ajax_bkgt_api_get_stats', 'bkgt_api_ajax_get_stats');

// Enqueue admin scripts
add_action('admin_enqueue_scripts', 'bkgt_api_enqueue_admin_scripts');