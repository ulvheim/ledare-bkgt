<?php
/**
 * Plugin Name: BKGT Document Management
 * Plugin URI: https://bkgt.se
 * Description: Secure document management system with version control and access permissions for BKGTS.
 * Version: 1.0.0
 * Author: BKGT Development Team
 * License: GPL v2 or later
 * Text Domain: bkgt-document-management
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BKGT_DOC_VERSION', '1.0.0');
define('BKGT_DOC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKGT_DOC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BKGT_DOC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once BKGT_DOC_PLUGIN_DIR . 'includes/class-database.php';
require_once BKGT_DOC_PLUGIN_DIR . 'includes/class-document.php';
require_once BKGT_DOC_PLUGIN_DIR . 'includes/class-category.php';
require_once BKGT_DOC_PLUGIN_DIR . 'includes/class-version.php';
require_once BKGT_DOC_PLUGIN_DIR . 'includes/class-access.php';
require_once BKGT_DOC_PLUGIN_DIR . 'admin/class-admin.php';

/**
 * Main Plugin Class
 */
class BKGT_Document_Management {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Database handler
     */
    public $db;

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
        $this->db = new BKGT_Document_Database();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'load_textdomain'));
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));

        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

        // AJAX handlers
        add_action('wp_ajax_bkgt_download_document', array($this, 'ajax_download_document'));
        add_action('wp_ajax_bkgt_download_version', array($this, 'ajax_download_version'));
        add_action('wp_ajax_bkgt_share_document', array($this, 'ajax_share_document'));
        
        // Shortcodes
        add_shortcode('bkgt_documents', array($this, 'shortcode_documents'));
        add_shortcode('bkgt_documents_admin', array($this, 'shortcode_documents_admin'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->db->create_tables();

        // Register post types first
        $this->register_post_types();
        $this->register_taxonomies();

        // Create upload directory
        $this->create_upload_directory();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Create default data
        $this->create_default_data();

        // Set plugin version
        update_option('bkgt_doc_version', BKGT_DOC_VERSION);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('BKGT Dokument', 'bkgt-document-management'),
            __('Dokument', 'bkgt-document-management'),
            'manage_options',
            'bkgt-documents',
            array($this, 'admin_page'),
            'dashicons-media-document',
            26
        );
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('BKGT Dokumenthantering', 'bkgt-document-management'); ?></h1>
            <p><?php _e('Hantera klubbens dokument här.', 'bkgt-document-management'); ?></p>
            <div class="bkgt-documents-admin">
                <p><?php _e('Admin interface kommer här.', 'bkgt-document-management'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'bkgt-document-management',
            false,
            dirname(BKGT_DOC_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Document post type
        register_post_type('bkgt_document', array(
            'labels' => array(
                'name'               => __('Dokument', 'bkgt-document-management'),
                'singular_name'      => __('Dokument', 'bkgt-document-management'),
                'menu_name'          => __('Dokument', 'bkgt-document-management'),
                'add_new'            => __('Lägg till nytt', 'bkgt-document-management'),
                'add_new_item'       => __('Lägg till nytt dokument', 'bkgt-document-management'),
                'edit_item'          => __('Redigera dokument', 'bkgt-document-management'),
                'new_item'           => __('Nytt dokument', 'bkgt-document-management'),
                'view_item'          => __('Visa dokument', 'bkgt-document-management'),
                'search_items'       => __('Sök dokument', 'bkgt-document-management'),
                'not_found'          => __('Inga dokument hittades', 'bkgt-document-management'),
                'not_found_in_trash' => __('Inga dokument i papperskorgen', 'bkgt-document-management'),
            ),
            'public'              => true,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-media-document',
            'menu_position'       => 25,
            'capability_type'     => 'post',
            'capabilities'        => array(
                'edit_post'          => 'manage_documents',
                'read_post'          => 'read_document',
                'delete_post'        => 'delete_document',
                'edit_posts'         => 'manage_documents',
                'edit_others_posts'  => 'manage_documents',
                'publish_posts'      => 'manage_documents',
                'read_private_posts' => 'read_document',
            ),
            'has_archive'         => false,
            'hierarchical'        => false,
            'supports'            => array('title', 'editor', 'author', 'thumbnail'),
            'show_in_rest'        => false,
        ));
    }

    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Document category taxonomy
        register_taxonomy('bkgt_doc_category', 'bkgt_document', array(
            'labels' => array(
                'name'              => __('Dokumentkategorier', 'bkgt-document-management'),
                'singular_name'     => __('Dokumentkategori', 'bkgt-document-management'),
                'search_items'      => __('Sök kategorier', 'bkgt-document-management'),
                'all_items'         => __('Alla kategorier', 'bkgt-document-management'),
                'edit_item'         => __('Redigera kategori', 'bkgt-document-management'),
                'update_item'       => __('Uppdatera kategori', 'bkgt-document-management'),
                'add_new_item'      => __('Lägg till ny kategori', 'bkgt-document-management'),
                'new_item_name'     => __('Ny kategori', 'bkgt-document-management'),
                'menu_name'         => __('Kategorier', 'bkgt-document-management'),
            ),
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => false,
            'capabilities'      => array(
                'manage_terms' => 'manage_options',
                'edit_terms'   => 'manage_options',
                'delete_terms' => 'manage_options',
                'assign_terms' => 'manage_documents',
            ),
        ));

        // Add default categories
        if (!term_exists('mötesprotokoll', 'bkgt_doc_category')) {
            wp_insert_term('Mötesprotokoll', 'bkgt_doc_category', array('slug' => 'mötesprotokoll'));
        }
        if (!term_exists('kontrakt', 'bkgt_doc_category')) {
            wp_insert_term('Kontrakt', 'bkgt_doc_category', array('slug' => 'kontrakt'));
        }
        if (!term_exists('policy', 'bkgt_doc_category')) {
            wp_insert_term('Policy', 'bkgt_doc_category', array('slug' => 'policy'));
        }
        if (!term_exists('ekonomi', 'bkgt_doc_category')) {
            wp_insert_term('Ekonomi', 'bkgt_doc_category', array('slug' => 'ekonomi'));
        }
        if (!term_exists('utbildning', 'bkgt_doc_category')) {
            wp_insert_term('Utbildning', 'bkgt_doc_category', array('slug' => 'utbildning'));
        }
    }

    /**
     * Create upload directory
     */
    private function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $bkgt_upload_dir = $upload_dir['basedir'] . '/bkgt-documents';

        if (!file_exists($bkgt_upload_dir)) {
            wp_mkdir_p($bkgt_upload_dir);

            // Create .htaccess for security
            $htaccess_content = "Order Deny,Allow\nDeny from all\n";
            file_put_contents($bkgt_upload_dir . '/.htaccess', $htaccess_content);

            // Create index.php for security
            file_put_contents($bkgt_upload_dir . '/index.php', '<?php // Silence is golden');
        }
    }

    /**
     * Create default data
     */
    private function create_default_data() {
        // Add default capabilities
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_documents');
            $role->add_cap('read_document');
            $role->add_cap('delete_document');
        }

        // Add capabilities for custom roles
        $custom_roles = array('styrelsemedlem', 'tränare', 'lagledare');
        foreach ($custom_roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                $role->add_cap('read_document');
                if ($role_name === 'styrelsemedlem') {
                    $role->add_cap('manage_documents');
                    $role->add_cap('delete_document');
                }
            }
        }
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!is_singular('bkgt_document') && !is_post_type_archive('bkgt_document')) {
            return;
        }

        wp_enqueue_style(
            'bkgt-document-frontend',
            BKGT_DOC_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            BKGT_DOC_VERSION
        );

        wp_enqueue_script(
            'bkgt-document-frontend',
            BKGT_DOC_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            BKGT_DOC_VERSION,
            true
        );

        wp_localize_script('bkgt-document-frontend', 'bkgtDocument', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkgt-document-nonce'),
            'strings' => array(
                'confirmDelete' => __('Är du säker på att du vill radera detta dokument?', 'bkgt-document-management'),
                'uploadError' => __('Ett fel uppstod vid uppladdning.', 'bkgt-document-management'),
            ),
        ));
    }

    /**
     * AJAX: Download document
     */
    public function ajax_download_document() {
        check_ajax_referer('download_document_' . $_GET['document_id'], 'nonce');

        $document_id = intval($_GET['document_id']);

        if (!BKGT_Document_Access::user_has_access($document_id)) {
            wp_die(__('Du har inte behörighet att ladda ner detta dokument.', 'bkgt-document-management'));
        }

        $document = new BKGT_Document($document_id);
        $file_path = $document->get_file_path();

        if (!$file_path || !file_exists($file_path)) {
            wp_die(__('Filen hittades inte.', 'bkgt-document-management'));
        }

        // Log download
        BKGT_Document::log_download($document_id);

        // Send file
        header('Content-Type: ' . $document->get_mime_type());
        header('Content-Disposition: attachment; filename="' . $document->get_file_name() . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($file_path);
        exit;
    }

    /**
     * AJAX: Download version
     */
    public function ajax_download_version() {
        check_ajax_referer('download_version_' . $_GET['version_id'], 'nonce');

        $version_id = intval($_GET['version_id']);
        $version = new BKGT_Document_Version($version_id);

        if (!$version->data) {
            wp_die(__('Version hittades inte.', 'bkgt-document-management'));
        }

        $document_id = $version->get_document_id();

        if (!BKGT_Document_Access::user_has_access($document_id)) {
            wp_die(__('Du har inte behörighet att ladda ner denna version.', 'bkgt-document-management'));
        }

        $file_path = $version->get_file_path();

        if (!$file_path || !file_exists($file_path)) {
            wp_die(__('Filen hittades inte.', 'bkgt-document-management'));
        }

        // Log download
        BKGT_Document::log_download($document_id);

        // Send file
        header('Content-Type: ' . $version->get_mime_type());
        header('Content-Disposition: attachment; filename="' . $version->get_file_name() . '"');
        header('Content-Length: ' . $version->get_file_size());
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($file_path);
        exit;
    }

    /**
     * AJAX: Share document
     */
    public function ajax_share_document() {
        check_ajax_referer('share_document_' . $_GET['document_id'], 'nonce');

        $document_id = intval($_GET['document_id']);

        if (!BKGT_Document_Access::user_has_access($document_id, null, BKGT_Document_Access::ACCESS_MANAGE)) {
            wp_die(__('Du har inte behörighet att dela detta dokument.', 'bkgt-document-management'));
        }

        $document = get_post($document_id);
        if (!$document) {
            wp_die(__('Dokument hittades inte.', 'bkgt-document-management'));
        }

        // Generate shareable link (this would typically create a temporary access token)
        $share_url = get_permalink($document_id);

        wp_redirect($share_url);
        exit;
    }
    
    /**
     * Shortcode for documents display
     */
    public function shortcode_documents($atts) {
        // Check user permissions
        if (!is_user_logged_in()) {
            return '<p>' . __('Du måste vara inloggad för att se denna sida.', 'bkgt-document-management') . '</p>';
        }
        
        // Get current user role
        $user = wp_get_current_user();
        $user_roles = $user->roles;
        
        ob_start();
        ?>
        <div class="bkgt-documents-container">
            <h2><?php _e('Dokumenthantering', 'bkgt-document-management'); ?></h2>
            <p><?php _e('Här kan du hantera klubbens dokument.', 'bkgt-document-management'); ?></p>
            
            <?php if (in_array('administrator', $user_roles) || in_array('styrelsemedlem', $user_roles)): ?>
                <a href="<?php echo get_permalink(19); ?>" class="btn btn-primary">
                    <?php _e('Hantera Dokument', 'bkgt-document-management'); ?>
                </a>
            <?php endif; ?>
            
            <!-- Placeholder for documents list -->
            <div class="documents-list">
                <p><?php _e('Dokumentlista kommer här.', 'bkgt-document-management'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode for documents admin interface
     */
    public function shortcode_documents_admin($atts) {
        // Check user permissions - only admins
        if (!current_user_can('manage_options')) {
            return '<p>' . __('Du har inte behörighet att komma åt denna sida.', 'bkgt-document-management') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="bkgt-documents-admin">
            <h2><?php _e('Hantera Dokument', 'bkgt-document-management'); ?></h2>
            
            <!-- Upload Document Form -->
            <div class="admin-section">
                <h3><?php _e('Ladda upp nytt dokument', 'bkgt-document-management'); ?></h3>
                <form id="upload-document-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="document-title"><?php _e('Titel', 'bkgt-document-management'); ?></label>
                        <input type="text" id="document-title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="document-file"><?php _e('Fil', 'bkgt-document-management'); ?></label>
                        <input type="file" id="document-file" name="document_file" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
                    </div>
                    <div class="form-group">
                        <label for="document-category"><?php _e('Kategori', 'bkgt-document-management'); ?></label>
                        <select id="document-category" name="category">
                            <option value="allmanna"><?php _e('Allmänna dokument', 'bkgt-document-management'); ?></option>
                            <option value="ekonomi"><?php _e('Ekonomi', 'bkgt-document-management'); ?></option>
                            <option value="spelare"><?php _e('Spelare', 'bkgt-document-management'); ?></option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary"><?php _e('Ladda upp', 'bkgt-document-management'); ?></button>
                </form>
            </div>
            
            <!-- Documents List -->
            <div class="admin-section">
                <h3><?php _e('Dokumentlista', 'bkgt-document-management'); ?></h3>
                <div id="documents-table">
                    <p><?php _e('Laddar...', 'bkgt-document-management'); ?></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * Initialize the plugin
 */
function bkgt_document_management() {
    return BKGT_Document_Management::get_instance();
}

// Start the plugin
bkgt_document_management();

// Initialize admin classes
if (is_admin()) {
    new BKGT_Document_Admin();
}