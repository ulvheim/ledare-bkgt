<?php
/**
 * BKGT Core Plugin
 * 
 * Plugin Name: BKGT Core
 * Plugin URI: https://ledare.bkgt.se
 * Description: Core functionality, logging, validation, and permissions for BKGT system
 * Version: 1.0.0
 * Author: BKGT Amerikansk Fotboll
 * Author URI: https://bkgt.se
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bkgt
 * Domain Path: /languages
 * 
 * @package BKGT
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Define BKGT Core constants
 */
define( 'BKGT_CORE_VERSION', '1.0.0' );
define( 'BKGT_CORE_FILE', __FILE__ );
define( 'BKGT_CORE_DIR', plugin_dir_path( BKGT_CORE_FILE ) );
define( 'BKGT_CORE_URL', plugin_dir_url( BKGT_CORE_FILE ) );

/**
 * Main BKGT_Core class
 */
class BKGT_Core {
    
    /**
     * REST API instance
     */
    public $rest_api;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
        $this->init_rest_api();
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // UI helper functions
        require_once BKGT_CORE_DIR . 'includes/functions-ui-helpers.php';
        
        // Core utility classes
        require_once BKGT_CORE_DIR . 'includes/class-logger.php';
        require_once BKGT_CORE_DIR . 'includes/class-exceptions.php';
        require_once BKGT_CORE_DIR . 'includes/class-error-recovery.php';
        require_once BKGT_CORE_DIR . 'includes/class-graceful-degradation.php';
        require_once BKGT_CORE_DIR . 'includes/class-validator.php';
        require_once BKGT_CORE_DIR . 'includes/class-sanitizer.php';
        require_once BKGT_CORE_DIR . 'includes/class-permission.php';
        require_once BKGT_CORE_DIR . 'includes/class-database.php';
        require_once BKGT_CORE_DIR . 'includes/class-rest-api.php';
        
        // Form system
        require_once BKGT_CORE_DIR . 'includes/class-form-handler.php';
        require_once BKGT_CORE_DIR . 'includes/class-form-builder.php';
        
        // Button system
        require_once BKGT_CORE_DIR . 'includes/class-button-builder.php';
        
        // Admin functionality
        if ( is_admin() ) {
            require_once BKGT_CORE_DIR . 'admin/class-admin.php';
            require_once BKGT_CORE_DIR . 'admin/class-admin-error-dashboard.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Load plugin text domain for translations
        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
        
        // Initialize core systems
        add_action( 'wp_loaded', array( $this, 'init_systems' ) );
        
        // Enqueue modal assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_modal_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_modal_assets' ) );
        
        // Admin notices
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        
        // Deactivation cleanup
        register_deactivation_hook( BKGT_CORE_FILE, array( $this, 'deactivate' ) );
    }
    
    /**
     * Initialize REST API
     */
    private function init_rest_api() {
        $this->rest_api = new BKGT_REST_API();
    }
    
    /**
     * Load plugin text domain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain( 'bkgt', false, dirname( plugin_basename( BKGT_CORE_FILE ) ) . '/languages/' );
    }
    
    /**
     * Initialize core systems
     */
    public function init_systems() {
        // Logger is already initialized via action hook in class-logger.php
        // Validator is a utility class, no initialization needed
        // Permissions are already initialized via action hook in class-permission.php
        
        do_action( 'bkgt_core_loaded' );
    }
    
    /**
     * Enqueue modal and form assets
     */
    public function enqueue_modal_assets() {
        // Enqueue CSS variables (foundation for all components)
        wp_enqueue_style(
            'bkgt-variables',
            BKGT_CORE_URL . 'assets/bkgt-variables.css',
            array(),
            BKGT_CORE_VERSION
        );
        
        // Enqueue button CSS
        wp_enqueue_style(
            'bkgt-buttons',
            BKGT_CORE_URL . 'assets/bkgt-buttons.css',
            array( 'bkgt-variables' ),
            BKGT_CORE_VERSION
        );
        
        // Enqueue modal CSS
        wp_enqueue_style(
            'bkgt-modal',
            BKGT_CORE_URL . 'assets/bkgt-modal.css',
            array( 'bkgt-variables' ),
            BKGT_CORE_VERSION
        );
        
        // Enqueue form CSS
        wp_enqueue_style(
            'bkgt-form',
            BKGT_CORE_URL . 'assets/bkgt-form.css',
            array( 'bkgt-variables', 'bkgt-buttons' ),
            BKGT_CORE_VERSION
        );
        
        // Enqueue form validation CSS
        wp_enqueue_style(
            'bkgt-form-validation',
            BKGT_CORE_URL . 'assets/css/form-validation.css',
            array( 'bkgt-variables' ),
            BKGT_CORE_VERSION
        );
        
        // Enqueue button JavaScript
        wp_enqueue_script(
            'bkgt-buttons',
            BKGT_CORE_URL . 'assets/bkgt-buttons.js',
            array(),
            BKGT_CORE_VERSION,
            true // Load in footer
        );
        
        // Enqueue frontend logger (must load before modal and form)
        wp_enqueue_script(
            'bkgt-logger',
            BKGT_CORE_URL . 'assets/bkgt-logger.js',
            array(),
            BKGT_CORE_VERSION,
            true // Load in footer
        );
        
        // Enqueue modal JavaScript (requires bkgt_log to be available)
        wp_enqueue_script(
            'bkgt-modal',
            BKGT_CORE_URL . 'assets/bkgt-modal.js',
            array( 'bkgt-logger' ),
            BKGT_CORE_VERSION,
            true // Load in footer
        );
        
        // Enqueue form JavaScript (depends on bkgt-modal and bkgt-buttons)
        wp_enqueue_script(
            'bkgt-form',
            BKGT_CORE_URL . 'assets/bkgt-form.js',
            array( 'bkgt-modal', 'bkgt-buttons' ),
            BKGT_CORE_VERSION,
            true // Load in footer
        );
        
        // Enqueue form validation JavaScript
        wp_enqueue_script(
            'bkgt-form-validation',
            BKGT_CORE_URL . 'assets/js/bkgt-form-validation.js',
            array( 'bkgt-logger' ),
            BKGT_CORE_VERSION,
            true // Load in footer
        );
        
        // Pass necessary data to JavaScript
        wp_localize_script(
            'bkgt-form',
            'bkgtFormConfig',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'bkgt-nonce' ),
                'isAdmin' => is_admin(),
                'strings' => array(
                    'submit' => __( 'Skicka', 'bkgt' ),
                    'cancel' => __( 'Avbryt', 'bkgt' ),
                    'loading' => __( 'Skickar...', 'bkgt' ),
                    'error' => __( 'Ett fel uppstod', 'bkgt' ),
                    'success' => __( 'Framgångsrikt skickat!', 'bkgt' ),
                    'required' => __( 'Detta fält är obligatoriskt', 'bkgt' ),
                    'invalidEmail' => __( 'Ogiltig e-postadress', 'bkgt' ),
                    'invalidPhone' => __( 'Ogiltigt telefonnummer', 'bkgt' ),
                    'invalidUrl' => __( 'Ogiltig URL', 'bkgt' ),
                    'invalidDate' => __( 'Ogiltigt datumformat', 'bkgt' ),
                )
            )
        );
        
        bkgt_log( 'info', 'Modal, form, and button assets enqueued' );
    }
    
    /**
     * Display admin notices
     */
    public function admin_notices() {
        // Check if other required BKGT plugins are active
        $required_plugins = array(
            'bkgt-user-management/bkgt-user-management.php' => 'BKGT User Management',
        );
        
        foreach ( $required_plugins as $plugin_file => $plugin_name ) {
            if ( ! is_plugin_active( $plugin_file ) ) {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p>';
                echo wp_kses_post( sprintf(
                    __( '<strong>BKGT Core:</strong> Required plugin "%s" is not active. BKGT may not function correctly.', 'bkgt' ),
                    $plugin_name
                ) );
                echo '</p>';
                echo '</div>';
            }
        }
    }
    
    /**
     * Cleanup on deactivation
     */
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook( 'bkgt_cleanup_logs' );
    }
}

/**
 * Initialize the plugin
 */
function bkgt_core_init() {
    new BKGT_Core();
}

// Load on plugins_loaded hook
add_action( 'plugins_loaded', 'bkgt_core_init', 0 );

/**
 * Helper function to access the logger
 * 
 * @param string $level   Log level
 * @param string $message Log message
 * @param array  $context Context data
 * 
 * @return bool Whether log was written
 */
function bkgt_log( $level = 'info', $message = '', $context = array() ) {
    return BKGT_Logger::log( $level, $message, $context );
}

/**
 * Helper function to validate data
 * 
 * @param string $rule  Validation rule name
 * @param mixed  $value Value to validate
 * @param mixed  ...$args Additional rule arguments
 * 
     * @return bool|string True if valid, error message if not
 */
function bkgt_validate( $rule, $value, ...$args ) {
    if ( method_exists( 'BKGT_Validator', $rule ) ) {
        return call_user_func_array( array( 'BKGT_Validator', $rule ), array_merge( array( $value ), $args ) );
    }
    return false;
}

/**
 * Helper function to check permissions
 * 
 * @param string $permission Permission to check
 * @param mixed  ...$args    Additional arguments
 * 
 * @return bool Whether user has permission
 */
function bkgt_can( $permission, ...$args ) {
    $method = 'can_' . $permission;
    
    if ( method_exists( 'BKGT_Permission', $method ) ) {
        return call_user_func_array( array( 'BKGT_Permission', $method ), $args );
    }
    
    // Fallback to direct capability check
    return current_user_can( $permission );
}

/**
 * Helper function to access the database service
 * 
 * Usage:
 *   bkgt_db()->get_posts( $post_type );
 *   bkgt_db()->create_post( $post_type, $data );
 *   bkgt_db()->query( $sql );
 * 
 * @return BKGT_Database Database service instance
 */
function bkgt_db() {
    static $db = null;
    if ( null === $db ) {
        $db = new BKGT_Database();
    }
    return $db;
}

/**
 * Helper function to create a modal JavaScript object
 * 
 * Usage:
 *   $modal = bkgt_modal( array(
 *       'id' => 'my-modal',
 *       'title' => 'My Modal Title',
 *       'size' => 'medium'
 *   ) );
 *   $modal->open( '<p>Modal content here</p>' );
 * 
 * @param array $options Modal options
 * 
 * @return string JavaScript code to initialize modal
 */
function bkgt_modal( $options = array() ) {
    $defaults = array(
        'id' => 'bkgt-modal-' . rand( 1000, 9999 ),
        'title' => '',
        'size' => 'medium',
        'closeButton' => true,
        'overlay' => true,
    );
    
    $options = wp_parse_args( $options, $defaults );
    
    // Build JavaScript
    $js = "var " . sanitize_key( $options['id'] ) . " = new BKGTModal(" . wp_json_encode( $options ) . ");";
    
    return $js;
}

/**
 * Helper function to create a button
 * 
 * Usage:
 *   bkgt_button( 'Click Me' )
 *       ->primary()
 *       ->large()
 *       ->id( 'my-button' )
 *       ->render();
 * 
 * Or:
 *   echo bkgt_button( 'Delete' )->danger()->delete_action();
 * 
 * @param string $text Button text
 * 
 * @return BKGT_Button_Builder
 */
function bkgt_button( $text = 'Button' ) {
    return new BKGT_Button_Builder( $text );
}

