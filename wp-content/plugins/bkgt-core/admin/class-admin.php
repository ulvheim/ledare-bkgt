<?php
/**
 * BKGT Core Admin Class
 *
 * Handles admin interface setup and initialization for BKGT Core plugin
 *
 * @package BKGT_Core
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main admin class for BKGT Core
 */
class BKGT_Core_Admin {
    
    /**
     * Initialize admin functionality
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Register admin menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 10 );
        
        // Enqueue admin scripts and styles
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        
        // Initialize error dashboard
        add_action( 'plugins_loaded', array( $this, 'init_error_dashboard' ) );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add main BKGT menu
        add_menu_page(
            __( 'BKGT System', 'bkgt-core' ),
            __( 'BKGT', 'bkgt-core' ),
            'manage_options',
            'bkgt-dashboard',
            array( $this, 'render_dashboard' ),
            'dashicons-list-view',
            30
        );
        
        // Add dashboard submenu as default page
        add_submenu_page(
            'bkgt-dashboard',
            __( 'Dashboard', 'bkgt-core' ),
            __( 'Dashboard', 'bkgt-core' ),
            'manage_options',
            'bkgt-dashboard',
            array( $this, 'render_dashboard' )
        );
    }
    
    /**
     * Render main dashboard page
     */
    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'BKGT System Dashboard', 'bkgt-core' ); ?></h1>
            
            <div class="bkgt-admin-container">
                <div class="bkgt-admin-card">
                    <h2><?php esc_html_e( 'System Status', 'bkgt-core' ); ?></h2>
                    <p><?php esc_html_e( 'BKGT Core plugin is active and operational.', 'bkgt-core' ); ?></p>
                </div>
                
                <div class="bkgt-admin-card">
                    <h2><?php esc_html_e( 'System Information', 'bkgt-core' ); ?></h2>
                    <ul>
                        <li><?php echo sprintf( esc_html__( 'BKGT Core Version: %s', 'bkgt-core' ), esc_html( BKGT_CORE_VERSION ) ); ?></li>
                        <li><?php echo sprintf( esc_html__( 'WordPress Version: %s', 'bkgt-core' ), esc_html( get_bloginfo( 'version' ) ) ); ?></li>
                        <li><?php echo sprintf( esc_html__( 'PHP Version: %s', 'bkgt-core' ), esc_html( phpversion() ) ); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'bkgt' ) === false ) {
            return;
        }
        
        // Enqueue admin styles
        wp_enqueue_style(
            'bkgt-core-admin',
            BKGT_CORE_URL . 'admin/css/admin.css',
            array(),
            BKGT_CORE_VERSION
        );
        
        // Enqueue admin scripts
        wp_enqueue_script(
            'bkgt-core-admin',
            BKGT_CORE_URL . 'admin/js/admin.js',
            array( 'jquery' ),
            BKGT_CORE_VERSION,
            true
        );
    }
    
    /**
     * Initialize error dashboard
     */
    public function init_error_dashboard() {
        if ( class_exists( 'BKGT_Admin_Error_Dashboard' ) ) {
            BKGT_Admin_Error_Dashboard::init();
        }
    }
}

// Initialize admin only in admin context
if ( is_admin() ) {
    new BKGT_Core_Admin();
}
