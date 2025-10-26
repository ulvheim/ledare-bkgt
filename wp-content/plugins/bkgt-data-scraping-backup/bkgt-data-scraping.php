<?php
/**
 * Plugin Name: BKGT Data Scraping & Management
 * Plugin URI: https://github.com/your-repo/bkgt-data-scraping
 * Description: Automated data retrieval and manual entry system for BKGT football club management. Scrapes player data, events, and statistics from svenskalag.se with fallback manual entry capabilities.
 * Version: 1.0.0
 * Author: BKGT Development Team
 * License: GPL v2 or later
 * Text Domain: bkgt-data-scraping
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BKGT_DATA_SCRAPING_VERSION', '1.0.0');
define('BKGT_DATA_SCRAPING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKGT_DATA_SCRAPING_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once BKGT_DATA_SCRAPING_PLUGIN_DIR . 'includes/class-bkgt-database.php';
require_once BKGT_DATA_SCRAPING_PLUGIN_DIR . 'includes/class-bkgt-scraper.php';
require_once BKGT_DATA_SCRAPING_PLUGIN_DIR . 'admin/class-bkgt-admin.php';
require_once BKGT_DATA_SCRAPING_PLUGIN_DIR . 'includes/class-bkgt-frontend.php';

/**
 * Main Plugin Class
 */
class BKGT_Data_Scraping {

    /**
     * Single instance of the plugin
     */
    private static $instance = null;

    /**
     * Database handler
     */
    public $db;

    /**
     * Scraper handler
     */
    public $scraper;

    /**
     * Admin handler
     */
    public $admin;

    /**
     * Frontend handler
     */
    public $frontend;

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
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        $this->db = new BKGT_Database();
        $this->scraper = new BKGT_Scraper($this->db);
        $this->admin = new BKGT_Admin($this->db);
        $this->frontend = new BKGT_Frontend($this->db);
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'bkgt-data-scraping',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Create default pages on first run (only once)
        $needs_setup = get_option('bkgt_needs_setup', false);
        if ($needs_setup) {
            $this->create_default_pages();
            delete_option('bkgt_needs_setup');
        }
    }

    /**
     * Plugin activation hook
     */
    public function activate() {
        // Initialize database handler for activation
        if (!$this->db) {
            $this->db = new BKGT_Database();
        }

        // Create database tables
        $this->db->create_tables();

        // Set default options
        add_option('bkgt_scraping_enabled', 'yes');
        add_option('bkgt_scraping_interval', 'daily');
        add_option('bkgt_scraping_source_url', 'https://www.svenskalag.se/bkgt');

        // Schedule scraping cron job
        if (!wp_next_scheduled('bkgt_daily_scraping')) {
            wp_schedule_event(time(), 'daily', 'bkgt_daily_scraping');
        }

        // Mark that we need to create pages on next init
        update_option('bkgt_needs_setup', 'yes');
    }

    /**
     * Plugin deactivation hook
     */
    public function deactivate() {
        // Clear scheduled hooks
        wp_clear_scheduled_hook('bkgt_daily_scraping');

        // Note: We don't delete tables on deactivation to preserve data
    }

    /**
     * Create default pages for the plugin
     */
    public function create_default_pages() {
        // Check if functions are available
        if (!function_exists('wp_insert_post') || !function_exists('get_page_by_path')) {
            return; // WordPress not fully loaded yet
        }

        $pages = array(
            array(
                'title' => 'Spelare',
                'slug' => 'spelare',
                'content' => 'Här hittar du alla våra spelare i BKGT.'
            ),
            array(
                'title' => 'Matcher & Event',
                'slug' => 'matcher',
                'content' => 'Kommande matcher och event för BKGT.'
            ),
            array(
                'title' => 'Lagöversikt',
                'slug' => 'lagoversikt',
                'content' => 'Statistik och översikt över BKGT laget.'
            )
        );

        foreach ($pages as $page_data) {
            // Check if page already exists
            $existing_page = get_page_by_path($page_data['slug']);

            if (!$existing_page) {
                // Create new page
                $result = wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_name' => $page_data['slug'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page'
                ));

                if (is_wp_error($result)) {
                    // Log error but continue
                    error_log('BKGT Plugin: Failed to create page ' . $page_data['title'] . ': ' . $result->get_error_message());
                }
            }
        }

        // Add sample data
        $this->add_sample_data();
    }

    /**
     * Add sample data for demonstration
     */
    private function add_sample_data() {
        global $wpdb;

        // Check if database is available
        if (!$this->db || !isset($this->db->tables['players'])) {
            return;
        }

        try {
            // Check if we already have data
            $player_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->db->tables['players']}");
            if ($player_count > 0) {
                return; // Don't add sample data if we already have players
            }

            // Sample players
            $players = array(
                array('player_id' => 'P001', 'first_name' => 'Erik', 'last_name' => 'Andersson', 'position' => 'Forward', 'birth_date' => '1995-03-15', 'jersey_number' => 9, 'status' => 'active'),
                array('player_id' => 'P002', 'first_name' => 'Lars', 'last_name' => 'Johansson', 'position' => 'Midfielder', 'birth_date' => '1992-07-22', 'jersey_number' => 7, 'status' => 'active'),
                array('player_id' => 'P003', 'first_name' => 'Mikael', 'last_name' => 'Karlsson', 'position' => 'Defender', 'birth_date' => '1990-11-08', 'jersey_number' => 4, 'status' => 'active'),
                array('player_id' => 'P004', 'first_name' => 'Anders', 'last_name' => 'Nilsson', 'position' => 'Goalkeeper', 'birth_date' => '1988-05-30', 'jersey_number' => 1, 'status' => 'active'),
                array('player_id' => 'P005', 'first_name' => 'Johan', 'last_name' => 'Eriksson', 'position' => 'Forward', 'birth_date' => '1997-01-12', 'jersey_number' => 11, 'status' => 'active'),
            );

            foreach ($players as $player) {
                $this->db->insert_player($player);
            }

            // Sample events
            $events = array(
                array('event_id' => 'E001', 'title' => 'Träning', 'event_type' => 'training', 'event_date' => date('Y-m-d H:i:s', strtotime('+2 days')), 'location' => 'BKGT Arena', 'status' => 'scheduled'),
                array('event_id' => 'E002', 'title' => 'Match vs IFK Göteborg', 'event_type' => 'match', 'event_date' => date('Y-m-d H:i:s', strtotime('+7 days')), 'location' => 'BKGT Arena', 'opponent' => 'IFK Göteborg', 'home_away' => 'home', 'status' => 'scheduled'),
                array('event_id' => 'E003', 'title' => 'Lagmöte', 'event_type' => 'meeting', 'event_date' => date('Y-m-d H:i:s', strtotime('+5 days')), 'location' => 'Klubbhuset', 'status' => 'scheduled'),
            );

            foreach ($events as $event) {
                $this->db->insert_event($event);
            }

            // Sample statistics
            $stats = array(
                array('player_id' => 1, 'event_id' => 2, 'goals' => 2, 'assists' => 1, 'minutes_played' => 90),
                array('player_id' => 2, 'event_id' => 2, 'goals' => 0, 'assists' => 2, 'minutes_played' => 85),
                array('player_id' => 3, 'event_id' => 2, 'goals' => 1, 'assists' => 0, 'minutes_played' => 90),
            );

            foreach ($stats as $stat) {
                $this->db->insert_statistics($stat);
            }
        } catch (Exception $e) {
            // Log error but don't break activation
            error_log('BKGT Plugin: Error adding sample data: ' . $e->getMessage());
        }
    }
}

// Initialize the plugin
function bkgt_data_scraping() {
    return BKGT_Data_Scraping::get_instance();
}

// Start the plugin
bkgt_data_scraping();

// Include AJAX handlers (only if file exists)
$ajax_file = BKGT_DATA_SCRAPING_PLUGIN_DIR . 'includes/ajax-handlers.php';
if (file_exists($ajax_file)) {
    require_once $ajax_file;
}