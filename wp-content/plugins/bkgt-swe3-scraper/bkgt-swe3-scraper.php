<?php
/**
 * Plugin Name: BKGT SWE3 Document Scraper
 * Plugin URI: https://github.com/bkgt/ledare-bkgt
 * Description: Automated scraping and curation of official SWE3 documents from the Swedish American Football Federation website
 * Version: 1.0.0
 * Author: BKGT Development Team
 * License: GPL v2 or later
 * Text Domain: bkgt-swe3-scraper
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BKGT_SWE3_VERSION', '1.0.0');
define('BKGT_SWE3_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKGT_SWE3_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BKGT_SWE3_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include core classes
require_once BKGT_SWE3_PLUGIN_DIR . 'includes/class-bkgt-swe3-scraper.php';
require_once BKGT_SWE3_PLUGIN_DIR . 'includes/class-bkgt-swe3-parser.php';
require_once BKGT_SWE3_PLUGIN_DIR . 'includes/class-bkgt-swe3-scheduler.php';
require_once BKGT_SWE3_PLUGIN_DIR . 'includes/class-bkgt-swe3-dms-integration.php';
require_once BKGT_SWE3_PLUGIN_DIR . 'includes/rest-api-endpoint.php';
require_once BKGT_SWE3_PLUGIN_DIR . 'includes/ajax-upload-endpoint.php';

// Include admin classes
// if (is_admin()) {
//     require_once BKGT_SWE3_PLUGIN_DIR . 'admin/class-bkgt-swe3-admin.php';
// }

/**
 * Main plugin class
 */
class BKGT_SWE3_Scraper_Plugin {

    /**
     * Single instance of the plugin
     */
    private static $instance = null;

    /**
     * Core scraper instance
     */
    public $scraper;

    /**
     * Parser instance
     */
    public $parser;

    /**
     * Scheduler instance
     */
    public $scheduler;

    /**
     * DMS integration instance
     */
    public $dms_integration;

    /**
     * Admin instance
     */
    public $admin;

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
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        try {
            $this->scraper = new BKGT_SWE3_Scraper();
            error_log('BKGT SWE3: Scraper initialized');
        } catch (Exception $e) {
            error_log('BKGT SWE3: Failed to initialize scraper: ' . $e->getMessage());
            return;
        }

        try {
            $this->parser = new BKGT_SWE3_Parser();
            error_log('BKGT SWE3: Parser initialized');
        } catch (Exception $e) {
            error_log('BKGT SWE3: Failed to initialize parser: ' . $e->getMessage());
            return;
        }

        try {
            $this->scheduler = new BKGT_SWE3_Scheduler();
            error_log('BKGT SWE3: Scheduler initialized');
        } catch (Exception $e) {
            error_log('BKGT SWE3: Failed to initialize scheduler: ' . $e->getMessage());
            return;
        }

        try {
            $this->dms_integration = new BKGT_SWE3_DMS_Integration();
            error_log('BKGT SWE3: DMS integration initialized');
        } catch (Exception $e) {
            error_log('BKGT SWE3: Failed to initialize DMS integration: ' . $e->getMessage());
            return;
        }

        if (is_admin()) {
            // try {
            //     $this->admin = new BKGT_SWE3_Admin();
            //     error_log('BKGT SWE3: Admin initialized');
            // } catch (Exception $e) {
            //     error_log('BKGT SWE3: Failed to initialize admin: ' . $e->getMessage());
            // }
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_database_tables();

        // Schedule cron job
        $this->scheduler->schedule_daily_scrape();

        // Set default options
        add_option('bkgt_swe3_last_scrape', 'never');
        add_option('bkgt_swe3_scrape_enabled', 'yes');
        add_option('bkgt_swe3_log_level', 'info');
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled cron job
        $this->scheduler->unschedule_daily_scrape();

        // Note: We don't delete database tables on deactivation
        // to preserve document history
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'bkgt-swe3-scraper',
            false,
            dirname(BKGT_SWE3_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize scheduler hooks
        $this->scheduler->init_hooks();

        // Plugin initialization code here
    }

    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'bkgt_swe3_documents';

        $sql = "CREATE TABLE $table_name (
            id INT AUTO_INCREMENT PRIMARY KEY,
            swe3_id VARCHAR(100) UNIQUE,
            title VARCHAR(255) NOT NULL,
            document_type VARCHAR(50),
            swe3_url VARCHAR(500),
            local_path VARCHAR(500),
            file_hash VARCHAR(64),
            version VARCHAR(20),
            publication_date DATE,
            scraped_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            dms_document_id INT,
            status ENUM('active', 'archived', 'error') DEFAULT 'active',
            last_checked DATETIME,
            error_message TEXT,
            INDEX idx_swe3_id (swe3_id),
            INDEX idx_status (status),
            INDEX idx_last_checked (last_checked)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Store database version
        add_option('bkgt_swe3_db_version', '1.0.0');
    }
}

// Initialize the plugin
function bkgt_swe3_scraper() {
    return BKGT_SWE3_Scraper_Plugin::get_instance();
}

// Start the plugin
bkgt_swe3_scraper();