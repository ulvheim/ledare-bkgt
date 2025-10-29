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
        // Initialize plugin
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
        add_action('wp_ajax_nopriv_bkgt_load_dms_content', array($this, 'ajax_load_dms_content'));

        // Handle document uploads
        add_action('wp_ajax_bkgt_upload_document', array($this, 'ajax_upload_document'));
        add_action('wp_ajax_nopriv_bkgt_upload_document', array($this, 'ajax_upload_document'));

        // Handle document search
        add_action('wp_ajax_bkgt_search_documents', array($this, 'ajax_search_documents'));
        add_action('wp_ajax_nopriv_bkgt_search_documents', array($this, 'ajax_search_documents'));

        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
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
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
        ));
    }

    /**
     * Get documents from database
     */
    public function get_documents($args = array()) {
        global $wpdb;

        $defaults = array(
            'category' => '',
            'limit' => 20,
            'offset' => 0,
            'search' => '',
            'user_id' => get_current_user_id(),
            'access_level' => 'read'
        );

        $args = wp_parse_args($args, $defaults);
        $table = $wpdb->prefix . 'bkgt_documents';
        $permissions_table = $wpdb->prefix . 'bkgt_document_permissions';

        // Base query
        $where = array("d.access_level = 'public'");

        // Add user-specific permissions
        if ($args['user_id']) {
            $where[] = $wpdb->prepare(
                "(d.uploaded_by = %d OR EXISTS (
                    SELECT 1 FROM {$permissions_table} p
                    WHERE p.document_id = d.id
                    AND p.user_id = %d
                    AND p.permission_type = %s
                    AND (p.expires_date IS NULL OR p.expires_date > NOW())
                ))",
                $args['user_id'], $args['user_id'], $args['access_level']
            );
        }

        // Add category filter
        if (!empty($args['category'])) {
            $where[] = $wpdb->prepare("d.category = %s", $args['category']);
        }

        // Add search filter
        if (!empty($args['search'])) {
            $where[] = $wpdb->prepare(
                "(d.title LIKE %s OR d.description LIKE %s OR d.filename LIKE %s)",
                '%' . $wpdb->esc_like($args['search']) . '%',
                '%' . $wpdb->esc_like($args['search']) . '%',
                '%' . $wpdb->esc_like($args['search']) . '%'
            );
        }

        $where_clause = implode(' AND ', $where);

        $sql = $wpdb->prepare(
            "SELECT d.* FROM {$table} d WHERE {$where_clause} ORDER BY d.upload_date DESC LIMIT %d OFFSET %d",
            $args['limit'], $args['offset']
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Save document to database
     */
    public function save_document($document_data) {
        global $wpdb;

        $table = $wpdb->prefix . 'bkgt_documents';

        $defaults = array(
            'title' => '',
            'filename' => '',
            'filepath' => '',
            'file_url' => '',
            'file_size' => 0,
            'mime_type' => '',
            'category' => '',
            'description' => '',
            'uploaded_by' => get_current_user_id(),
            'access_level' => 'private',
            'version' => 1,
            'parent_id' => 0,
            'metadata' => array()
        );

        $data = wp_parse_args($document_data, $defaults);
        $data['metadata'] = maybe_serialize($data['metadata']);

        $result = $wpdb->insert($table, $data);

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get document by ID
     */
    public function get_document($document_id, $user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $table = $wpdb->prefix . 'bkgt_documents';
        $permissions_table = $wpdb->prefix . 'bkgt_document_permissions';

        $sql = $wpdb->prepare(
            "SELECT d.* FROM {$table} d
            LEFT JOIN {$permissions_table} p ON d.id = p.document_id AND p.user_id = %d
            WHERE d.id = %d
            AND (d.access_level = 'public' OR d.uploaded_by = %d OR p.permission_type IS NOT NULL)
            AND (p.expires_date IS NULL OR p.expires_date > NOW())",
            $user_id, $document_id, $user_id
        );

        $document = $wpdb->get_row($sql);

        if ($document) {
            $document->metadata = maybe_unserialize($document->metadata);
        }

        return $document;
    }

    /**
     * Get document categories
     */
    public function get_categories() {
        global $wpdb;

        $table = $wpdb->prefix . 'bkgt_document_categories';

        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY name ASC");
    }

    /**
     * Handle file upload
     */
    public function handle_file_upload($file, $title = '', $category = '', $description = '') {
        // Validate file
        $allowed_types = array(
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        );

        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!array_key_exists($file_ext, $allowed_types)) {
            return new WP_Error('invalid_file_type', __('Invalid file type. Allowed: PDF, DOC, DOCX, XLS, XLSX, TXT, JPG, PNG.', 'bkgt-document-management'));
        }

        // Upload file
        $upload_dir = wp_upload_dir();
        $filename = wp_unique_filename($upload_dir['path'], $file['name']);
        $filepath = $upload_dir['path'] . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return new WP_Error('upload_failed', __('Failed to upload file.', 'bkgt-document-management'));
        }

        // Save to database
        $document_data = array(
            'title' => !empty($title) ? $title : pathinfo($file['name'], PATHINFO_FILENAME),
            'filename' => $filename,
            'filepath' => $filepath,
            'file_url' => $upload_dir['url'] . '/' . $filename,
            'file_size' => $file['size'],
            'mime_type' => $file['type'],
            'category' => $category,
            'description' => $description,
            'uploaded_by' => get_current_user_id(),
            'metadata' => array(
                'original_filename' => $file['name'],
                'upload_date' => current_time('mysql')
            )
        );

        $document_id = $this->save_document($document_data);

        if (!$document_id) {
            // Clean up uploaded file if database save failed
            unlink($filepath);
            return new WP_Error('db_save_failed', __('Failed to save document to database.', 'bkgt-document-management'));
        }

        return $document_id;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
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
            <p><?php _e('Document management system is active and ready for development.', 'bkgt-document-management'); ?></p>
            <div class="notice notice-info">
                <p><?php _e('Next steps:', 'bkgt-document-management'); ?></p>
                <ul>
                    <li><?php _e('Add document upload functionality', 'bkgt-document-management'); ?></li>
                    <li><?php _e('Implement access control and permissions', 'bkgt-document-management'); ?></li>
                    <li><?php _e('Add search and filtering capabilities', 'bkgt-document-management'); ?></li>
                    <li><?php _e('Implement version control for documents', 'bkgt-document-management'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Documents shortcode
     */
    public function documents_shortcode($atts) {
        // Handle tab and category filtering from URL
        $active_tab = isset($_GET['bkgt_tab']) ? sanitize_text_field($_GET['bkgt_tab']) : 'browse';
        $active_category = isset($_GET['bkgt_category']) ? sanitize_text_field($_GET['bkgt_category']) : '';

        ob_start();

        // Set default attributes
        $atts = shortcode_atts(array(
            'limit' => 10,
            'category' => $active_category,
            'show_tabs' => 'true'
        ), $atts);

        ?>
        <div class="bkgt-dms">
            <h2><?php _e('Document Management System', 'bkgt-document-management'); ?></h2>

            <?php if ($atts['show_tabs'] === 'true') : ?>
                <?php $this->display_dms_function_tabs($active_tab); ?>
            <?php endif; ?>

            <div class="bkgt-dms-content">
                <?php $this->display_tab_content($active_tab, $atts); ?>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Tab switching functionality
            $('.bkgt-dms-tab-link').on('click', function(e) {
                e.preventDefault();

                // Remove active class from all tabs
                $('.bkgt-dms-tab-link').removeClass('active');
                // Add active class to clicked tab
                $(this).addClass('active');

                // Get tab name
                var tab = $(this).data('tab');

                // Update URL without page reload
                var url = new URL(window.location);
                url.searchParams.set('bkgt_tab', tab);
                // Remove category param when switching to non-browse tabs
                if (tab !== 'browse') {
                    url.searchParams.delete('bkgt_category');
                }
                window.history.pushState({}, '', url);

                // Load tab content
                loadTabContent(tab);
            });

            function loadTabContent(tab, category = '') {
                $('.bkgt-dms-content').addClass('loading');

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'bkgt_load_dms_content',
                        tab: tab,
                        category: category,
                        limit: <?php echo intval($atts['limit']); ?>
                    },
                    success: function(response) {
                        $('.bkgt-dms-content').removeClass('loading').html(response);
                    },
                    error: function() {
                        $('.bkgt-dms-content').removeClass('loading').html('<p><?php _e('Error loading content.', 'bkgt-document-management'); ?></p>');
                    }
                });
            }

            // Handle browser back/forward buttons
            $(window).on('popstate', function() {
                var url = new URL(window.location);
                var tab = url.searchParams.get('bkgt_tab') || 'browse';
                var category = url.searchParams.get('bkgt_category') || '';

                // Update active tab
                $('.bkgt-dms-tab-link').removeClass('active');
                $('.bkgt-dms-tab-link[data-tab="' + tab + '"]').addClass('active');

                loadTabContent(tab, category);
            });

            // Handle category changes within browse tab
            $(document).on('click', '.bkgt-category-link', function(e) {
                e.preventDefault();
                var category = $(this).data('category');

                var url = new URL(window.location);
                url.searchParams.set('bkgt_tab', 'browse');
                if (category) {
                    url.searchParams.set('bkgt_category', category);
                } else {
                    url.searchParams.delete('bkgt_category');
                }
                window.history.pushState({}, '', url);

                loadTabContent('browse', category);
            });
        });
        </script>

        <style>
            .bkgt-dms { margin: 20px 0; }
            .bkgt-dms h2 { color: #333; border-bottom: 2px solid #007cba; padding-bottom: 10px; }

            /* DMS Function Tabs */
            .bkgt-dms-tabs { margin: 20px 0; }
            .bkgt-dms-tab-navigation {
                display: flex;
                border-bottom: 1px solid #ddd;
                flex-wrap: wrap;
                background: #f8f9fa;
                padding: 0 10px;
            }
            .bkgt-dms-tab-link {
                padding: 15px 25px;
                text-decoration: none;
                color: #666;
                border-bottom: 3px solid transparent;
                transition: all 0.3s ease;
                font-weight: 600;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .bkgt-dms-tab-link:hover {
                color: #007cba;
                background-color: #e9ecef;
            }
            .bkgt-dms-tab-link.active {
                color: #007cba;
                border-bottom-color: #007cba;
                background-color: #fff;
                position: relative;
            }
            .bkgt-dms-tab-link.active::after {
                content: '';
                position: absolute;
                bottom: -1px;
                left: 0;
                right: 0;
                height: 3px;
                background: #007cba;
            }

            /* DMS Content */
            .bkgt-dms-content {
                position: relative;
                min-height: 300px;
                padding: 20px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 5px;
                margin-top: 10px;
            }
            .bkgt-dms-content.loading {
                opacity: 0.6;
                pointer-events: none;
            }
            .bkgt-dms-content.loading::after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 30px;
                height: 30px;
                margin: -15px 0 0 -15px;
                border: 3px solid #007cba;
                border-top: 3px solid transparent;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

            /* Browse Section */
            .bkgt-browse-section {}
            .bkgt-category-navigation {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 20px;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 5px;
            }
            .bkgt-category-link {
                padding: 8px 16px;
                text-decoration: none;
                color: #666;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                transition: all 0.3s ease;
                font-size: 14px;
            }
            .bkgt-category-link:hover, .bkgt-category-link.active {
                color: #007cba;
                border-color: #007cba;
                background: #e3f2fd;
            }

            /* Upload Section */
            .bkgt-upload-section {}
            .bkgt-upload-form-container {
                max-width: 600px;
                margin: 0 auto;
            }
            .bkgt-upload-form-container h3 {
                color: #333;
                margin-bottom: 20px;
            }
            .bkgt-form-group {
                margin-bottom: 20px;
            }
            .bkgt-form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
                color: #333;
            }
            .bkgt-form-group input[type="text"],
            .bkgt-form-group input[type="file"],
            .bkgt-form-group select,
            .bkgt-form-group textarea {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }
            .bkgt-form-group textarea {
                resize: vertical;
                min-height: 80px;
            }
            .bkgt-form-actions {
                text-align: center;
                margin-top: 30px;
            }
            .bkgt-upload-btn {
                background: #007cba;
                color: white;
                padding: 12px 30px;
                border: none;
                border-radius: 4px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.3s ease;
            }
            .bkgt-upload-btn:hover {
                background: #005a87;
            }
            .bkgt-upload-btn:disabled {
                background: #ccc;
                cursor: not-allowed;
            }
            .bkgt-upload-progress {
                margin-top: 15px;
                text-align: center;
            }
            .bkgt-progress-bar {
                width: 0%;
                height: 4px;
                background: #007cba;
                border-radius: 2px;
                transition: width 0.3s ease;
            }
            .bkgt-success {
                color: #28a745;
                background: #d4edda;
                border: 1px solid #c3e6cb;
                padding: 10px;
                border-radius: 4px;
                margin: 10px 0;
            }
            .bkgt-error {
                color: #dc3545;
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                padding: 10px;
                border-radius: 4px;
                margin: 10px 0;
            }

            /* Search Section */
            .bkgt-search-section {}
            .bkgt-search-form {
                margin-bottom: 30px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 5px;
            }
            .bkgt-search-form h3 {
                margin-top: 0;
                color: #333;
            }
            .bkgt-search-inputs {
                display: flex;
                gap: 10px;
                align-items: center;
                flex-wrap: wrap;
            }
            .bkgt-search-inputs input,
            .bkgt-search-inputs select {
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                flex: 1;
                min-width: 200px;
            }
            .bkgt-search-btn {
                background: #007cba;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 600;
                transition: background 0.3s ease;
            }
            .bkgt-search-btn:hover {
                background: #005a87;
            }
            .bkgt-search-results {
                min-height: 200px;
            }
            .bkgt-search-placeholder {
                text-align: center;
                color: #666;
                font-style: italic;
                padding: 40px;
            }
            .bkgt-search-results-list {
                display: grid;
                gap: 15px;
            }
            .bkgt-search-result-item {
                border: 1px solid #ddd;
                padding: 15px;
                border-radius: 5px;
                background: #fff;
            }
            .bkgt-search-result-item h4 {
                margin: 0 0 10px 0;
                color: #007cba;
            }
            .bkgt-search-result-item h4 a {
                text-decoration: none;
                color: inherit;
            }
            .bkgt-search-result-item h4 a:hover {
                color: #005a87;
            }
            .bkgt-search-meta {
                font-size: 0.9em;
                color: #666;
                margin-bottom: 10px;
            }
            .bkgt-search-meta span {
                display: inline-block;
                margin-right: 15px;
            }
            .bkgt-no-results {
                text-align: center;
                padding: 40px;
                color: #666;
                font-style: italic;
            }

            /* Permissions Section */
            .bkgt-permissions-section {}
            .bkgt-permissions-info {
                display: grid;
                gap: 20px;
                grid-template-columns: 1fr;
            }
            .bkgt-notice {
                background: #e3f2fd;
                border: 1px solid #2196f3;
                border-radius: 5px;
                padding: 20px;
            }
            .bkgt-notice h4 {
                margin-top: 0;
                color: #1976d2;
            }
            .bkgt-notice ul {
                margin: 15px 0;
                padding-left: 20px;
            }
            .bkgt-notice li {
                margin-bottom: 5px;
            }
            .bkgt-permissions-actions {
                background: #fff3cd;
                border: 1px solid #ffc107;
                border-radius: 5px;
                padding: 20px;
            }
            .bkgt-permissions-actions h4 {
                margin-top: 0;
                color: #856404;
            }
            .bkgt-permissions-actions ul {
                margin: 15px 0;
                padding-left: 20px;
            }
            .bkgt-permissions-actions a {
                color: #856404;
                text-decoration: none;
                font-weight: 600;
            }
            .bkgt-permissions-actions a:hover {
                text-decoration: underline;
            }

            /* Documents List (shared) */
            .bkgt-documents-container {}
            .bkgt-documents-list { margin-top: 0; }
            .bkgt-document-item {
                border: 1px solid #ddd;
                padding: 15px;
                margin-bottom: 15px;
                border-radius: 5px;
                background: #fff;
                transition: box-shadow 0.3s ease;
            }
            .bkgt-document-item:hover {
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .bkgt-document-item h3 {
                margin-top: 0;
                color: #007cba;
                font-size: 18px;
            }
            .bkgt-document-item h3 a {
                text-decoration: none;
                color: inherit;
            }
            .bkgt-document-item h3 a:hover {
                color: #005a87;
            }
            .bkgt-document-meta {
                font-size: 0.9em;
                color: #666;
                margin: 10px 0;
            }
            .bkgt-document-meta span {
                display: inline-block;
                margin-right: 15px;
            }
            .bkgt-category-tag {
                display: inline-block;
                background: #007cba;
                color: white;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 0.8em;
                margin-right: 5px;
            }
            .bkgt-document-excerpt { margin-top: 10px; }
            .bkgt-no-documents {
                text-align: center;
                padding: 40px;
                background: #f8f9fa;
                border-radius: 5px;
                color: #666;
            }

            /* Loading animation */
            .bkgt-loading {
                text-align: center;
                padding: 40px;
                color: #666;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            /* Responsive design */
            @media (max-width: 768px) {
                .bkgt-dms-tab-navigation,
                .bkgt-category-navigation,
                .bkgt-search-inputs {
                    flex-direction: column;
                    align-items: stretch;
                }
                .bkgt-dms-tab-link,
                .bkgt-category-link {
                    text-align: center;
                }
                .bkgt-search-inputs input,
                .bkgt-search-inputs select {
                    min-width: auto;
                }
                .bkgt-permissions-info {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        <?php

        return ob_get_clean();
    }

    /**
     * Display category filter
     */
    private function display_category_filter() {
        $categories = get_terms(array(
            'taxonomy' => 'bkgt_doc_category',
            'hide_empty' => false,
        ));

        if (!empty($categories) && !is_wp_error($categories)) :
        ?>
            <div class="bkgt-category-filter">
                <form method="get" action="">
                    <label for="bkgt_category"><?php _e('Filter by Category:', 'bkgt-document-management'); ?></label>
                    <select name="bkgt_category" id="bkgt_category" onchange="this.form.submit()">
                        <option value=""><?php _e('All Categories', 'bkgt-document-management'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->slug); ?>" <?php selected(isset($_GET['bkgt_category']) ? $_GET['bkgt_category'] : '', $category->slug); ?>>
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        <?php
        endif;
    }

    /**
     * Display DMS function tabs
     */
    private function display_dms_function_tabs($active_tab = 'browse') {
        $tabs = array(
            'browse' => __('Browse Documents', 'bkgt-document-management'),
            'upload' => __('Upload Document', 'bkgt-document-management'),
            'search' => __('Search & Filter', 'bkgt-document-management'),
            'permissions' => __('Permissions', 'bkgt-document-management'),
        );

        ?>
        <div class="bkgt-dms-tabs">
            <div class="bkgt-dms-tab-navigation">
                <?php foreach ($tabs as $tab_key => $tab_label) : ?>
                    <a href="#" class="bkgt-dms-tab-link <?php echo ($active_tab === $tab_key) ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($tab_key); ?>">
                        <?php echo esc_html($tab_label); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display tab content based on active tab
     */
    private function display_tab_content($active_tab, $atts) {
        switch ($active_tab) {
            case 'browse':
                $this->display_browse_content($atts);
                break;
            case 'upload':
                $this->display_upload_content();
                break;
            case 'search':
                $this->display_search_content();
                break;
            case 'permissions':
                $this->display_permissions_content();
                break;
            default:
                $this->display_browse_content($atts);
        }
    }

    /**
     * Display browse tab content (documents with categories)
     */
    private function display_browse_content($atts) {
        // Get all categories for sub-navigation
        $categories = $this->get_categories();

        ?>
        <div class="bkgt-browse-section">
            <?php if (!empty($categories)) : ?>
                <div class="bkgt-category-navigation">
                    <a href="#" class="bkgt-category-link <?php echo empty($atts['category']) ? 'active' : ''; ?>" data-category="">
                        <?php _e('All Documents', 'bkgt-document-management'); ?>
                    </a>
                    <?php foreach ($categories as $category) : ?>
                        <a href="#" class="bkgt-category-link <?php echo ($atts['category'] === $category->slug) ? 'active' : ''; ?>" data-category="<?php echo esc_attr($category->slug); ?>">
                            <?php echo esc_html($category->name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="bkgt-documents-container">
                <?php $this->display_documents_list($atts); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display upload tab content
     */
    private function display_upload_content() {
        ?>
        <div class="bkgt-upload-section">
            <div class="bkgt-upload-form-container">
                <h3><?php _e('Upload New Document', 'bkgt-document-management'); ?></h3>
                <form id="bkgt-upload-form" enctype="multipart/form-data">
                    <?php wp_nonce_field('bkgt_upload_document', 'bkgt_upload_nonce'); ?>

                    <div class="bkgt-form-group">
                        <label for="document_title"><?php _e('Document Title', 'bkgt-document-management'); ?> *</label>
                        <input type="text" id="document_title" name="document_title" required>
                    </div>

                    <div class="bkgt-form-group">
                        <label for="document_file"><?php _e('Document File', 'bkgt-document-management'); ?> *</label>
                        <input type="file" id="document_file" name="document_file" accept=".pdf,.doc,.docx,.txt,.jpg,.png" required>
                        <small><?php _e('Allowed formats: PDF, DOC, DOCX, TXT, JPG, PNG', 'bkgt-document-management'); ?></small>
                    </div>

                    <div class="bkgt-form-group">
                        <label for="document_category"><?php _e('Category', 'bkgt-document-management'); ?></label>
                        <select id="document_category" name="document_category">
                            <option value=""><?php _e('Select Category', 'bkgt-document-management'); ?></option>
                            <?php
                            $categories = $this->get_categories();
                            if (!empty($categories)) {
                                foreach ($categories as $category) {
                                    echo '<option value="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="bkgt-form-group">
                        <label for="document_description"><?php _e('Description', 'bkgt-document-management'); ?></label>
                        <textarea id="document_description" name="document_description" rows="4"></textarea>
                    </div>

                    <div class="bkgt-form-actions">
                        <button type="submit" class="bkgt-upload-btn"><?php _e('Upload Document', 'bkgt-document-management'); ?></button>
                        <div class="bkgt-upload-progress" style="display: none;">
                            <div class="bkgt-progress-bar"></div>
                            <span class="bkgt-progress-text"><?php _e('Uploading...', 'bkgt-document-management'); ?></span>
                        </div>
                    </div>
                </form>

                <div id="bkgt-upload-result"></div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#bkgt-upload-form').on('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(this);
                formData.append('action', 'bkgt_upload_document');

                $('.bkgt-upload-progress').show();
                $('.bkgt-upload-btn').prop('disabled', true);

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function() {
                        var xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable) {
                                var percentComplete = (e.loaded / e.total) * 100;
                                $('.bkgt-progress-bar').css('width', percentComplete + '%');
                                $('.bkgt-progress-text').text(Math.round(percentComplete) + '%');
                            }
                        });
                        return xhr;
                    },
                    success: function(response) {
                        $('.bkgt-upload-progress').hide();
                        $('.bkgt-upload-btn').prop('disabled', false);
                        $('.bkgt-progress-bar').css('width', '0%');

                        if (response.success) {
                            $('#bkgt-upload-result').html('<div class="bkgt-success">' + response.data.message + '</div>');
                            $('#bkgt-upload-form')[0].reset();
                        } else {
                            $('#bkgt-upload-result').html('<div class="bkgt-error">' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        $('.bkgt-upload-progress').hide();
                        $('.bkgt-upload-btn').prop('disabled', false);
                        $('.bkgt-progress-bar').css('width', '0%');
                        $('#bkgt-upload-result').html('<div class="bkgt-error"><?php _e('Upload failed. Please try again.', 'bkgt-document-management'); ?></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Display search tab content
     */
    private function display_search_content() {
        ?>
        <div class="bkgt-search-section">
            <div class="bkgt-search-form">
                <h3><?php _e('Search Documents', 'bkgt-document-management'); ?></h3>
                <form id="bkgt-search-form">
                    <div class="bkgt-search-inputs">
                        <input type="text" id="search_query" name="search_query" placeholder="<?php _e('Search by title, content, or author...', 'bkgt-document-management'); ?>">
                        <select id="search_category" name="search_category">
                            <option value=""><?php _e('All Categories', 'bkgt-document-management'); ?></option>
                            <?php
                            $categories = $this->get_categories();
                            if (!empty($categories)) {
                                foreach ($categories as $category) {
                                    echo '<option value="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <button type="submit" class="bkgt-search-btn"><?php _e('Search', 'bkgt-document-management'); ?></button>
                    </div>
                </form>
            </div>

            <div id="bkgt-search-results" class="bkgt-search-results">
                <p class="bkgt-search-placeholder"><?php _e('Enter search terms above to find documents.', 'bkgt-document-management'); ?></p>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#bkgt-search-form').on('submit', function(e) {
                e.preventDefault();

                var query = $('#search_query').val();
                var category = $('#search_category').val();

                if (!query.trim()) {
                    alert('<?php _e('Please enter a search term.', 'bkgt-document-management'); ?>');
                    return;
                }

                $('#bkgt-search-results').html('<div class="bkgt-loading"><?php _e('Searching...', 'bkgt-document-management'); ?></div>');

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'bkgt_search_documents',
                        query: query,
                        category: category
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#bkgt-search-results').html(response.data.html);
                        } else {
                            $('#bkgt-search-results').html('<div class="bkgt-error">' + response.data.message + '</div>');
                        }
                    },
                    error: function() {
                        $('#bkgt-search-results').html('<div class="bkgt-error"><?php _e('Search failed. Please try again.', 'bkgt-document-management'); ?></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Display permissions tab content
     */
    private function display_permissions_content() {
        if (!current_user_can('manage_options')) {
            echo '<div class="bkgt-error">' . __('You do not have permission to manage document permissions.', 'bkgt-document-management') . '</div>';
            return;
        }

        ?>
        <div class="bkgt-permissions-section">
            <h3><?php _e('Document Permissions', 'bkgt-document-management'); ?></h3>
            <div class="bkgt-permissions-info">
                <div class="bkgt-notice">
                    <h4><?php _e('Current Permissions System', 'bkgt-document-management'); ?></h4>
                    <p><?php _e('Document permissions are managed through WordPress user roles and capabilities.', 'bkgt-document-management'); ?></p>
                    <ul>
                        <li><strong><?php _e('Administrators:', 'bkgt-document-management'); ?></strong> <?php _e('Full access to all documents and settings', 'bkgt-document-management'); ?></li>
                        <li><strong><?php _e('Editors:', 'bkgt-document-management'); ?></strong> <?php _e('Can create and edit documents', 'bkgt-document-management'); ?></li>
                        <li><strong><?php _e('Authors:', 'bkgt-document-management'); ?></strong> <?php _e('Can create documents', 'bkgt-document-management'); ?></li>
                        <li><strong><?php _e('Subscribers:', 'bkgt-document-management'); ?></strong> <?php _e('Read-only access to published documents', 'bkgt-document-management'); ?></li>
                    </ul>
                </div>

                <div class="bkgt-permissions-actions">
                    <h4><?php _e('Quick Actions', 'bkgt-document-management'); ?></h4>
                    <p><?php _e('Advanced permission management features will be available in future updates.', 'bkgt-document-management'); ?></p>
                    <ul>
                        <li><a href="<?php echo admin_url('users.php'); ?>"><?php _e('Manage Users', 'bkgt-document-management'); ?></a></li>
                        <li><a href="<?php echo admin_url('edit.php?post_type=bkgt_document'); ?>"><?php _e('Manage Documents', 'bkgt-document-management'); ?></a></li>
                        <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=bkgt_doc_category&post_type=bkgt_document'); ?>"><?php _e('Manage Categories', 'bkgt-document-management'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Display documents list
     */
    private function display_documents_list($atts) {
        // Get documents from database
        $args = array(
            'category' => $atts['category'],
            'limit' => intval($atts['limit']),
            'offset' => 0
        );

        $documents = $this->get_documents($args);

        if (!empty($documents)) : ?>
            <div class="bkgt-documents-list">
                <?php foreach ($documents as $document) : ?>
                    <div class="bkgt-document-item">
                        <h3><?php echo esc_html($document->title); ?></h3>
                        <div class="bkgt-document-meta">
                            <span class="bkgt-document-author">
                                <?php _e('Uploaded by:', 'bkgt-document-management'); ?>
                                <?php echo esc_html(get_userdata($document->uploaded_by)->display_name); ?>
                            </span>
                            <span class="bkgt-document-date">
                                <?php _e('Date:', 'bkgt-document-management'); ?>
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($document->upload_date))); ?>
                            </span>
                            <span class="bkgt-document-size">
                                <?php _e('Size:', 'bkgt-document-management'); ?>
                                <?php echo esc_html(size_format($document->file_size)); ?>
                            </span>
                            <?php if (!empty($document->category)) : ?>
                                <span class="bkgt-document-category">
                                    <?php _e('Category:', 'bkgt-document-management'); ?>
                                    <span class="bkgt-category-tag"><?php echo esc_html($this->get_category_name($document->category)); ?></span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($document->description)) : ?>
                            <div class="bkgt-document-description">
                                <?php echo wp_kses_post(wp_trim_words($document->description, 20)); ?>
                            </div>
                        <?php endif; ?>
                        <div class="bkgt-document-actions">
                            <a href="<?php echo esc_url($document->file_url); ?>" target="_blank" class="bkgt-download-link">
                                <?php _e('Download', 'bkgt-document-management'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="bkgt-no-documents">
                <p><?php _e('No documents found.', 'bkgt-document-management'); ?></p>
                <p><?php _e('Upload documents using the Upload tab.', 'bkgt-document-management'); ?></p>
            </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Get category name by slug
     */
    private function get_category_name($slug) {
        global $wpdb;

        $table = $wpdb->prefix . 'bkgt_document_categories';

        $category = $wpdb->get_var($wpdb->prepare(
            "SELECT name FROM {$table} WHERE slug = %s",
            $slug
        ));

        return $category ?: $slug;
    }

    /**
     * AJAX handler for loading DMS content
     */
    public function ajax_load_dms_content() {
        $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : 'browse';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;

        $atts = array(
            'category' => $category,
            'limit' => $limit,
            'show_tabs' => 'false' // Don't show tabs in AJAX response
        );

        ob_start();
        $this->display_tab_content($tab, $atts);
        $content = ob_get_clean();

        wp_send_json_success(array('html' => $content));
    }

    /**
     * AJAX handler for document upload
     */
    public function ajax_upload_document() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['bkgt_upload_nonce'], 'bkgt_upload_document')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'bkgt-document-management')));
        }

        // Check user permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to upload documents.', 'bkgt-document-management')));
        }

        // Check if file was uploaded
        if (empty($_FILES['document_file'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'bkgt-document-management')));
        }

        $title = sanitize_text_field($_POST['document_title']);
        $category = sanitize_text_field($_POST['document_category']);
        $description = sanitize_textarea_field($_POST['document_description']);

        // Use the handle_file_upload method
        $result = $this->handle_file_upload($_FILES['document_file'], $title, $category, $description);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array('message' => __('Document uploaded successfully!', 'bkgt-document-management')));
        }
    }

    /**
     * AJAX handler for document search
     */
    public function ajax_search_documents() {
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';

        if (empty($query)) {
            wp_send_json_error(array('message' => __('Search query is required.', 'bkgt-document-management')));
        }

        $args = array(
            'search' => $query,
            'category' => $category,
            'limit' => 20
        );

        $search_results = $this->get_documents($args);

        ob_start();
        if (!empty($search_results)) {
            echo '<div class="bkgt-search-results-list">';
            foreach ($search_results as $document) {
                ?>
                <div class="bkgt-search-result-item">
                    <h4><?php echo esc_html($document->title); ?></h4>
                    <div class="bkgt-search-meta">
                        <span class="bkgt-search-author">
                            <?php _e('Uploaded by:', 'bkgt-document-management'); ?>
                            <?php echo esc_html(get_userdata($document->uploaded_by)->display_name); ?>
                        </span>
                        <span class="bkgt-search-date">
                            <?php _e('Date:', 'bkgt-document-management'); ?>
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($document->upload_date))); ?>
                        </span>
                        <span class="bkgt-search-size">
                            <?php _e('Size:', 'bkgt-document-management'); ?>
                            <?php echo esc_html(size_format($document->file_size)); ?>
                        </span>
                        <?php if (!empty($document->category)) : ?>
                            <span class="bkgt-search-category">
                                <?php _e('Category:', 'bkgt-document-management'); ?>
                                <span class="bkgt-category-tag"><?php echo esc_html($this->get_category_name($document->category)); ?></span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($document->description)) : ?>
                        <div class="bkgt-search-description">
                            <?php echo wp_kses_post(wp_trim_words($document->description, 30)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="bkgt-search-actions">
                        <a href="<?php echo esc_url($document->file_url); ?>" target="_blank" class="bkgt-download-link">
                            <?php _e('Download', 'bkgt-document-management'); ?>
                        </a>
                    </div>
                </div>
                <?php
            }
            echo '</div>';
        } else {
            echo '<div class="bkgt-no-results">' . __('No documents found matching your search.', 'bkgt-document-management') . '</div>';
        }

        $html = ob_get_clean();
        wp_send_json_success(array('html' => $html));
    }
}

/**
 * Initialize the plugin
 */
function bkgt_document_management_init() {
    BKGT_Document_Management::get_instance();
}
add_action('plugins_loaded', 'bkgt_document_management_init');

/**
 * Plugin activation hook
 */
function bkgt_document_management_activate() {
    // Create database tables
    bkgt_document_management_create_tables();

    // Set default options
    add_option('bkgt_dm_version', BKGT_DM_VERSION);
}
register_activation_hook(__FILE__, 'bkgt_document_management_activate');

/**
 * Create database tables
 */
function bkgt_document_management_create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Documents table
    $table_name = $wpdb->prefix . 'bkgt_documents';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        filename varchar(255) NOT NULL,
        filepath varchar(500) NOT NULL,
        file_url varchar(500) NOT NULL,
        file_size bigint(20) NOT NULL,
        mime_type varchar(100) NOT NULL,
        category varchar(100) DEFAULT '',
        description text,
        uploaded_by bigint(20) unsigned NOT NULL,
        upload_date datetime DEFAULT CURRENT_TIMESTAMP,
        modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        access_level varchar(50) DEFAULT 'private',
        version int(11) DEFAULT 1,
        parent_id mediumint(9) DEFAULT 0,
        metadata longtext,
        PRIMARY KEY (id),
        KEY uploaded_by (uploaded_by),
        KEY category (category),
        KEY access_level (access_level)
    ) $charset_collate;";

    // Document categories table
    $categories_table = $wpdb->prefix . 'bkgt_document_categories';

    $sql2 = "CREATE TABLE $categories_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        slug varchar(100) NOT NULL,
        description text,
        parent_id mediumint(9) DEFAULT 0,
        created_by bigint(20) unsigned NOT NULL,
        created_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY parent_id (parent_id)
    ) $charset_collate;";

    // Document access permissions table
    $permissions_table = $wpdb->prefix . 'bkgt_document_permissions';

    $sql3 = "CREATE TABLE $permissions_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        document_id mediumint(9) NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        permission_type varchar(50) NOT NULL, -- 'read', 'write', 'delete', 'admin'
        granted_by bigint(20) unsigned NOT NULL,
        granted_date datetime DEFAULT CURRENT_TIMESTAMP,
        expires_date datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY document_id (document_id),
        KEY user_id (user_id),
        KEY permission_type (permission_type)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql2);
    dbDelta($sql3);

    // Insert default categories
    $default_categories = array(
        array('name' => 'Allmänna dokument', 'slug' => 'allmanna'),
        array('name' => 'Ekonomi', 'slug' => 'ekonomi'),
        array('name' => 'Spelare', 'slug' => 'spelare'),
        array('name' => 'Tränare', 'slug' => 'tranare'),
        array('name' => 'Styrelse', 'slug' => 'styrelse'),
        array('name' => 'Utrustning', 'slug' => 'utrustning'),
        array('name' => 'Kontrakt', 'slug' => 'kontrakt'),
        array('name' => 'Protokoll', 'slug' => 'protokoll')
    );

    foreach ($default_categories as $category) {
        $wpdb->insert(
            $categories_table,
            array(
                'name' => $category['name'],
                'slug' => $category['slug'],
                'created_by' => get_current_user_id() ?: 1
            ),
            array('%s', '%s', '%d')
        );
    }
}