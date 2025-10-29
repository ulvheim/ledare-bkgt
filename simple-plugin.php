<?php
/**
 * Plugin Name: BKGT Document Management
 * Plugin URI: https://bkgt.se
 * Description: Secure document management system for BKGTS.
 * Version: 1.0.0
 * Author: BKGT Development Team
 * License: GPL v2 or later
 * Text Domain: bkgt-document-management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Debug: Log that plugin is loading
error_log('BKGT DM Plugin: Loading plugin file');

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
        error_log('BKGT DM Plugin: get_instance called');
        if (null === self::$instance) {
            error_log('BKGT DM Plugin: Creating new instance');
            self::$instance = new self();
        } else {
            error_log('BKGT DM Plugin: Using existing instance');
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Debug: Log constructor
        error_log('BKGT DM Plugin: Constructor called');

        // Initialize immediately
        $this->init();
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Debug: Log init
        error_log('BKGT DM Plugin: Init method called - START');

        // Load textdomain
        load_plugin_textdomain('bkgt-document-management', false, dirname(plugin_basename(__FILE__)) . '/languages/');

        error_log('BKGT DM Plugin: After textdomain load');

        // Register post types and taxonomies immediately
        error_log('BKGT DM Plugin: About to call register_post_types');
        $this->register_post_types();
        error_log('BKGT DM Plugin: About to call register_taxonomies');
        $this->register_taxonomies();

        error_log('BKGT DM Plugin: Init method called - END');

        // Add shortcodes
        add_shortcode('bkgt_documents', array($this, 'documents_shortcode'));

        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
    }

    /**
     * Register custom post types
     */
    public function register_post_types() {
        error_log('BKGT DM Plugin: Registering post types');

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
        error_log('BKGT DM Plugin: Registering taxonomies');

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
     * Add admin menu
     */
    public function add_admin_menu() {
        error_log('BKGT DM Plugin: Adding admin menu');

        add_menu_page(
            __('Documents', 'bkgt-document-management'),
            __('Documents', 'bkgt-document-management'),
            'manage_options',
            'bkgt-documents',
            array($this, 'admin_page'),
            'dashicons-media-document',
            30
        );
    }

    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('BKGT Document Management', 'bkgt-document-management'); ?></h1>
            <p><?php _e('Document management system is active.', 'bkgt-document-management'); ?></p>
        </div>
        <?php
    }

    /**
     * Documents shortcode
     */
    public function documents_shortcode($atts) {
        ob_start();
        ?>
        <div class="bkgt-documents">
            <h2><?php _e('Document Management', 'bkgt-document-management'); ?></h2>
            <p><?php _e('System is active and ready for development.', 'bkgt-document-management'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * Initialize the plugin
 */
function bkgt_document_management_init() {
    error_log('BKGT DM Plugin: Init function called');
    BKGT_Document_Management::get_instance();
}
add_action('plugins_loaded', 'bkgt_document_management_init');