<?php
/**
 * Plugin Name: BKGT Inventory System
 * Plugin URI: https://ledare.bkgt.se
 * Description: Utrustningssystem för BKGTS Ledarsystem. Hanterar utrustning, tilldelningar och lagerhållning.
 * Version: 1.0.0
 * Author: BKGTS American Football
 * Author URI: https://bkgt.se
 * Text Domain: bkgt-inventory
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: Proprietary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BKGT_INV_VERSION', '1.0.0');
define('BKGT_INV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKGT_INV_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BKGT_INV_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once BKGT_INV_PLUGIN_DIR . 'includes/class-database.php';
require_once BKGT_INV_PLUGIN_DIR . 'includes/class-manufacturer.php';
require_once BKGT_INV_PLUGIN_DIR . 'includes/class-item-type.php';
require_once BKGT_INV_PLUGIN_DIR . 'includes/class-inventory-item.php';
require_once BKGT_INV_PLUGIN_DIR . 'includes/class-assignment.php';
require_once BKGT_INV_PLUGIN_DIR . 'includes/class-history.php';
require_once BKGT_INV_PLUGIN_DIR . 'admin/class-admin.php';
require_once BKGT_INV_PLUGIN_DIR . 'admin/class-item-admin.php';

/**
 * Main Plugin Class
 */
class BKGT_Inventory {
    
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
        $this->db = new BKGT_Inventory_Database();
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
        
        // Check dependencies
        add_action('admin_notices', array($this, 'check_dependencies'));
        
        // Frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_bkgt_get_item_types', array($this, 'ajax_get_item_types'));
        add_action('wp_ajax_bkgt_generate_identifier', array($this, 'ajax_generate_identifier'));
        
        // Shortcodes
        add_shortcode('bkgt_inventory', array($this, 'shortcode_inventory'));
    }
    
    /**
     * Check plugin dependencies
     */
    public function check_dependencies() {
        if (!class_exists('BKGT_Team') || !class_exists('BKGT_User_Team_Assignment')) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>BKGT Inventory:</strong> ';
            echo __('Funktioner relaterade till lag kräver att BKGT User Management plugin är aktiverad.', 'bkgt-inventory');
            echo '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('BKGT Utrustning', 'bkgt-inventory'),
            __('Utrustning', 'bkgt-inventory'),
            'manage_options',
            'bkgt-inventory',
            array($this, 'admin_page'),
            'dashicons-archive',
            25
        );
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('BKGT Utrustningssystem', 'bkgt-inventory'); ?></h1>
            <p><?php _e('Hantera klubbens utrustning här.', 'bkgt-inventory'); ?></p>
            <div class="bkgt-inventory-admin">
                <p><?php _e('Admin interface kommer här.', 'bkgt-inventory'); ?></p>
            </div>
        </div>
        <?php
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
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Create default data
        $this->create_default_data();
        
        // Set plugin version
        update_option('bkgt_inv_version', BKGT_INV_VERSION);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'bkgt-inventory',
            false,
            dirname(BKGT_INV_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Inventory Item post type
        register_post_type('bkgt_inventory_item', array(
            'labels' => array(
                'name'               => __('Utrustning', 'bkgt-inventory'),
                'singular_name'      => __('Utrustningsartikel', 'bkgt-inventory'),
                'menu_name'          => __('Utrustning', 'bkgt-inventory'),
                'add_new'            => __('Lägg till ny', 'bkgt-inventory'),
                'add_new_item'       => __('Lägg till ny utrustningsartikel', 'bkgt-inventory'),
                'edit_item'          => __('Redigera utrustningsartikel', 'bkgt-inventory'),
                'new_item'           => __('Ny utrustningsartikel', 'bkgt-inventory'),
                'view_item'          => __('Visa utrustningsartikel', 'bkgt-inventory'),
                'search_items'       => __('Sök utrustning', 'bkgt-inventory'),
                'not_found'          => __('Ingen utrustning hittades', 'bkgt-inventory'),
                'not_found_in_trash' => __('Ingen utrustning i papperskorgen', 'bkgt-inventory'),
            ),
            'public'              => true,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-archive',
            'menu_position'       => 26,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title', 'thumbnail'),
            'has_archive'         => false,
            'rewrite'             => false,
            'show_in_rest'        => false,
        ));
    }
    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Condition taxonomy
        register_taxonomy('bkgt_condition', 'bkgt_inventory_item', array(
            'labels' => array(
                'name'              => __('Skick', 'bkgt-inventory'),
                'singular_name'     => __('Skick', 'bkgt-inventory'),
                'search_items'      => __('Sök skick', 'bkgt-inventory'),
                'all_items'         => __('Alla skick', 'bkgt-inventory'),
                'edit_item'         => __('Redigera skick', 'bkgt-inventory'),
                'update_item'       => __('Uppdatera skick', 'bkgt-inventory'),
                'add_new_item'      => __('Lägg till nytt skick', 'bkgt-inventory'),
                'new_item_name'     => __('Nytt skick namn', 'bkgt-inventory'),
                'menu_name'         => __('Skick', 'bkgt-inventory'),
            ),
            'hierarchical'      => false,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => false,
        ));
        
        // Storage Location taxonomy
        register_taxonomy('bkgt_storage_location', 'bkgt_inventory_item', array(
            'labels' => array(
                'name'              => __('Lagringsplats', 'bkgt-inventory'),
                'singular_name'     => __('Lagringsplats', 'bkgt-inventory'),
                'search_items'      => __('Sök lagringsplats', 'bkgt-inventory'),
                'all_items'         => __('Alla lagringsplatser', 'bkgt-inventory'),
                'edit_item'         => __('Redigera lagringsplats', 'bkgt-inventory'),
                'update_item'       => __('Uppdatera lagringsplats', 'bkgt-inventory'),
                'add_new_item'      => __('Lägg till ny lagringsplats', 'bkgt-inventory'),
                'new_item_name'     => __('Ny lagringsplats namn', 'bkgt-inventory'),
                'menu_name'         => __('Lagringsplatser', 'bkgt-inventory'),
            ),
            'hierarchical'      => false,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => false,
        ));
    }
    
    /**
     * Create default data
     */
    private function create_default_data() {
        // Create default conditions
        $conditions = array(
            'normal' => __('Normal', 'bkgt-inventory'),
            'behöver-reparation' => __('Behöver reparation', 'bkgt-inventory'),
            'reparerad' => __('Reparerad', 'bkgt-inventory'),
            'förlustanmäld' => __('Förlustanmäld', 'bkgt-inventory'),
            'skrotad' => __('Skrotad', 'bkgt-inventory'),
        );
        
        foreach ($conditions as $slug => $name) {
            if (!term_exists($slug, 'bkgt_condition')) {
                wp_insert_term($name, 'bkgt_condition', array('slug' => $slug));
            }
        }
        
        // Create default storage locations
        $locations = array(
            'klubbförråd' => __('Klubbförråd', 'bkgt-inventory'),
            'containern-tyresövallen' => __('Containern, Tyresövallen', 'bkgt-inventory'),
        );
        
        foreach ($locations as $slug => $name) {
            if (!term_exists($slug, 'bkgt_storage_location')) {
                wp_insert_term($name, 'bkgt_storage_location', array('slug' => $slug));
            }
        }
        
        // Create default manufacturers
        $default_manufacturers = array(
            array('name' => 'Schutt', 'id' => '0001'),
            array('name' => 'Riddell', 'id' => '0002'),
            array('name' => 'Xenith', 'id' => '0003'),
            array('name' => 'Under Armour', 'id' => '0004'),
            array('name' => 'Nike', 'id' => '0005'),
        );
        
        foreach ($default_manufacturers as $manufacturer) {
            BKGT_Manufacturer::create($manufacturer['name'], $manufacturer['id']);
        }
        
        // Create default item types
        $default_item_types = array(
            array('name' => 'Hjälm', 'id' => '0001'),
            array('name' => 'Axelskydd', 'id' => '0002'),
            array('name' => 'Armskydd', 'id' => '0003'),
            array('name' => 'Lårskydd', 'id' => '0004'),
            array('name' => 'Knäskydd', 'id' => '0005'),
            array('name' => 'Skor', 'id' => '0006'),
            array('name' => 'Tröja', 'id' => '0007'),
            array('name' => 'Byxor', 'id' => '0008'),
        );
        
        foreach ($default_item_types as $item_type) {
            BKGT_Item_Type::create($item_type['name'], $item_type['id']);
        }
    }
    
    /**
     * AJAX: Get item types for manufacturer
     */
    public function ajax_get_item_types() {
        check_ajax_referer('bkgt-inventory-nonce', 'nonce');
        
        $manufacturer_id = intval($_POST['manufacturer_id']);
        $item_types = BKGT_Item_Type::get_all();
        
        wp_send_json_success($item_types);
    }
    
    /**
     * AJAX: Generate unique identifier
     */
    public function ajax_generate_identifier() {
        check_ajax_referer('bkgt-inventory-nonce', 'nonce');
        
        $manufacturer_id = intval($_POST['manufacturer_id']);
        $item_type_id = intval($_POST['item_type_id']);
        
        $identifier = BKGT_Inventory_Item::generate_unique_identifier($manufacturer_id, $item_type_id);
        
        wp_send_json_success(array('identifier' => $identifier));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!is_singular('bkgt_inventory_item') && !is_post_type_archive('bkgt_inventory_item')) {
            return;
        }
        
        wp_enqueue_style(
            'bkgt-inventory-frontend',
            BKGT_INV_PLUGIN_URL . 'assets/frontend.css',
            array(),
            BKGT_INV_VERSION
        );
    }
    
    /**
     * Shortcode for inventory display
     */
    public function shortcode_inventory($atts) {
        // Check user permissions
        if (!is_user_logged_in()) {
            return '<p>' . __('Du måste vara inloggad för att se denna sida.', 'bkgt-inventory') . '</p>';
        }
        
        // Get current user role
        $user = wp_get_current_user();
        $user_roles = $user->roles;
        
        // Basic inventory list
        ob_start();
        ?>
        <div class="bkgt-inventory-container">
            <h2><?php _e('Utrustningsinventarie', 'bkgt-inventory'); ?></h2>
            <p><?php _e('Här kan du hantera klubbens utrustning.', 'bkgt-inventory'); ?></p>
            
            <?php if (in_array('administrator', $user_roles) || in_array('styrelsemedlem', $user_roles)): ?>
                <a href="<?php echo admin_url('admin.php?page=bkgt-inventory'); ?>" class="btn btn-primary">
                    <?php _e('Hantera Inventarie', 'bkgt-inventory'); ?>
                </a>
            <?php endif; ?>
            
            <!-- Placeholder for inventory list -->
            <div class="inventory-list">
                <p><?php _e('Inventarielista kommer här.', 'bkgt-inventory'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * Initialize the plugin
 */
function bkgt_inventory() {
    return BKGT_Inventory::get_instance();
}

// Start the plugin
bkgt_inventory();

// Initialize admin classes
add_action('admin_init', function() {
    new BKGT_Inventory_Admin();
    new BKGT_Item_Admin();
});
