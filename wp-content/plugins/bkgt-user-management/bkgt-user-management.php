<?php
/**
 * Plugin Name: BKGT User Management
 * Plugin URI: https://ledare.bkgt.se
 * Description: Användarhantering och laghantering för BKGTS Ledarsystem. Hanterar roller, behörigheter och laguppdelningar.
 * Version: 1.0.0
 * Author: BKGTS American Football
 * Author URI: https://bkgt.se
 * Text Domain: bkgt-user-management
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
define('BKGT_UM_VERSION', '1.0.0');
define('BKGT_UM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKGT_UM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BKGT_UM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class BKGT_User_Management {
    
    /**
     * Single instance of the class
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
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once BKGT_UM_PLUGIN_DIR . 'includes/class-team.php';
        require_once BKGT_UM_PLUGIN_DIR . 'includes/class-user-team-assignment.php';
        require_once BKGT_UM_PLUGIN_DIR . 'includes/class-capabilities.php';
        require_once BKGT_UM_PLUGIN_DIR . 'admin/class-admin.php';
        require_once BKGT_UM_PLUGIN_DIR . 'admin/class-team-admin.php';
        require_once BKGT_UM_PLUGIN_DIR . 'admin/class-user-profile.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'load_textdomain'));
        add_action('init', array($this, 'register_post_types'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Register post types first
        $this->register_post_types();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Create default teams if none exist
        $this->create_default_teams();
        
        // Set plugin version
        update_option('bkgt_um_version', BKGT_UM_VERSION);
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
            'bkgt-user-management',
            false,
            dirname(BKGT_UM_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Register Team post type
        register_post_type('bkgt_team', array(
            'labels' => array(
                'name'               => __('Lag', 'bkgt-user-management'),
                'singular_name'      => __('Lag', 'bkgt-user-management'),
                'menu_name'          => __('Lag', 'bkgt-user-management'),
                'add_new'            => __('Lägg till nytt', 'bkgt-user-management'),
                'add_new_item'       => __('Lägg till nytt lag', 'bkgt-user-management'),
                'edit_item'          => __('Redigera lag', 'bkgt-user-management'),
                'new_item'           => __('Nytt lag', 'bkgt-user-management'),
                'view_item'          => __('Visa lag', 'bkgt-user-management'),
                'search_items'       => __('Sök lag', 'bkgt-user-management'),
                'not_found'          => __('Inga lag hittades', 'bkgt-user-management'),
                'not_found_in_trash' => __('Inga lag i papperskorgen', 'bkgt-user-management'),
            ),
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-groups',
            'menu_position'       => 25,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title', 'editor', 'thumbnail'),
            'has_archive'         => true,
            'rewrite'             => array('slug' => 'lag'),
            'show_in_rest'        => true,
        ));
    }
    
    /**
     * Create default teams on activation
     */
    private function create_default_teams() {
        $existing_teams = get_posts(array(
            'post_type'      => 'bkgt_team',
            'posts_per_page' => 1,
            'post_status'    => 'any',
        ));
        
        // Only create if no teams exist
        if (empty($existing_teams)) {
            $default_teams = array(
                array(
                    'title'       => 'P2013',
                    'description' => 'Pojkar födda 2013',
                ),
                array(
                    'title'       => 'P2014',
                    'description' => 'Pojkar födda 2014',
                ),
                array(
                    'title'       => 'P2015',
                    'description' => 'Pojkar födda 2015',
                ),
                array(
                    'title'       => 'P2016',
                    'description' => 'Pojkar födda 2016',
                ),
                array(
                    'title'       => 'P2017',
                    'description' => 'Pojkar födda 2017',
                ),
                array(
                    'title'       => 'P2018',
                    'description' => 'Pojkar födda 2018',
                ),
                array(
                    'title'       => 'P2019',
                    'description' => 'Pojkar födda 2019',
                ),
                array(
                    'title'       => 'P2020',
                    'description' => 'Pojkar födda 2020',
                ),
            );
            
            foreach ($default_teams as $team) {
                wp_insert_post(array(
                    'post_type'    => 'bkgt_team',
                    'post_title'   => $team['title'],
                    'post_content' => $team['description'],
                    'post_status'  => 'publish',
                ));
            }
        }
    }
}

/**
 * Initialize the plugin
 */
function bkgt_user_management() {
    return BKGT_User_Management::get_instance();
}

// Start the plugin
bkgt_user_management();

// Initialize admin classes
add_action('admin_init', function() {
    new BKGT_User_Management_Admin();
    new BKGT_Team_Admin();
    new BKGT_User_Profile();
});
