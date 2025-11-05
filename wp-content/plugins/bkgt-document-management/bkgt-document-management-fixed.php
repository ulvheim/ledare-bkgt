<?php
/**
 * Plugin Name: BKGT Document Management
 * Plugin URI: https://bkgt.se
 * Description: Secure document management system for BKGTS.
 * Version: 1.0.0
 * Author: BKGT Amerikansk Fotboll
 * License: GPL v2 or later
 * Text Domain: bkgt-document-management
 * Requires Plugins: bkgt-core
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('BKGT_DM_VERSION', '1.0.0');
define('BKGT_DM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKGT_DM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check WordPress version compatibility
if (version_compare(get_bloginfo('version'), '5.0', '<')) {
    return;
}

/**
 * Main Plugin Class
 */
class BKGT_Document_Management {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
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
        $this->init();
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load textdomain
        add_action('init', array($this, 'load_textdomain'));

        // Register post types and taxonomies
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));

        // Add shortcodes
        add_shortcode('bkgt_documents', array($this, 'documents_shortcode'));

        // AJAX handlers
        add_action('wp_ajax_bkgt_load_dms_content', array($this, 'ajax_load_dms_content'));
        add_action('wp_ajax_bkgt_upload_document', array($this, 'ajax_upload_document'));
        add_action('wp_ajax_bkgt_search_documents', array($this, 'ajax_search_documents'));
        add_action('wp_ajax_bkgt_download_document', array($this, 'ajax_download_document'));
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('bkgt-document-management', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Register custom post types
     */
    public function register_post_types() {
        register_post_type('bkgt_document', array(
            'labels' => array(
                'name' => __('Documents', 'bkgt-document-management'),
                'singular_name' => __('Document', 'bkgt-document-management'),
                'add_new' => __('Add New', 'bkgt-document-management'),
                'add_new_item' => __('Add New Document', 'bkgt-document-management'),
                'edit_item' => __('Edit Document', 'bkgt-document-management'),
                'new_item' => __('New Document', 'bkgt-document-management'),
                'view_item' => __('View Document', 'bkgt-document-management'),
                'search_items' => __('Search Documents', 'bkgt-document-management'),
                'not_found' => __('No documents found', 'bkgt-document-management'),
                'not_found_in_trash' => __('No documents found in trash', 'bkgt-document-management'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'author'),
            'show_in_rest' => false,
        ));
    }

    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        register_taxonomy('bkgt_doc_category', 'bkgt_document', array(
            'labels' => array(
                'name' => __('Categories', 'bkgt-document-management'),
                'singular_name' => __('Category', 'bkgt-document-management'),
            ),
            'hierarchical' => true,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => false,
        ));
    }

    /**
     * Documents shortcode - stub for now
     */
    public function documents_shortcode($atts) {
        return '<p>Document Management System</p>';
    }

    /**
     * AJAX handlers - stubs
     */
    public function ajax_load_dms_content() {
        wp_send_json_success();
    }

    public function ajax_upload_document() {
        wp_send_json_success();
    }

    public function ajax_search_documents() {
        wp_send_json_success();
    }

    public function ajax_download_document() {
        wp_send_json_success();
    }
}

/**
 * Initialize the plugin
 */
function bkgt_document_management_init() {
    // Load core classes
    require_once BKGT_DM_PLUGIN_DIR . 'includes/class-access.php';
    require_once BKGT_DM_PLUGIN_DIR . 'includes/class-category.php';
    require_once BKGT_DM_PLUGIN_DIR . 'includes/class-database.php';
    require_once BKGT_DM_PLUGIN_DIR . 'includes/class-document.php';
    require_once BKGT_DM_PLUGIN_DIR . 'includes/class-version.php';

    // Load Phase 3 advanced features
    require_once BKGT_DM_PLUGIN_DIR . 'includes/class-template-system.php';
    require_once BKGT_DM_PLUGIN_DIR . 'includes/class-export-system.php';
    require_once BKGT_DM_PLUGIN_DIR . 'includes/class-version-control.php';

    // Load admin classes - Note: BKGT_Document_Admin handles all menu registration
    // Do NOT register admin_menu in BKGT_Document_Management to avoid duplicate menus
    if (is_admin()) {
        require_once BKGT_DM_PLUGIN_DIR . 'admin/class-admin.php';
        new BKGT_Document_Admin();
    }

    BKGT_Document_Management::get_instance();
}

/**
 * Plugin activation
 */
function bkgt_document_management_activate() {
    // Check if BKGT Core plugin is active
    if (!function_exists('bkgt_log')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('BKGT Core plugin is required for BKGT Document Management to work. Please activate BKGT Core first.');
    }
    
    bkgt_log('info', 'BKGT Document Management plugin activated', array(
        'version' => BKGT_DM_VERSION,
    ));
}

/**
 * Plugin deactivation
 */
function bkgt_document_management_deactivate() {
    if (function_exists('bkgt_log')) {
        bkgt_log('info', 'BKGT Document Management plugin deactivated');
    }
}

register_activation_hook(__FILE__, 'bkgt_document_management_activate');
register_deactivation_hook(__FILE__, 'bkgt_document_management_deactivate');

add_action('plugins_loaded', 'bkgt_document_management_init');
