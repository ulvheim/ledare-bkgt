<?php
/**
 * Plugin Name: BKGT Data Scraping & Management
 * Plugin URI: https://github.com/your-repo/bkgt-data-scraping
 * Description: Automated data retrieval and manual entry system for BKGT football club management.
 * Version: 1.0.0
 * Author: BKGT Amerikansk Fotboll
 * License: GPL v2 or later
 * Text Domain: bkgt-data-scraping
 */

// Prevent direct access
if (!defined('ABSPATH')) {

    exit;    exit;

}}



// Define plugin constants// Define plugin constants

define('BKGT_DATA_SCRAPING_VERSION', '1.0.0');define('BKGT_DATA_SCRAPING_VERSION', '1.0.0');

define('BKGT_DATA_SCRAPING_PLUGIN_DIR', plugin_dir_path(__FILE__));define('BKGT_DATA_SCRAPING_PLUGIN_DIR', plugin_dir_path(__FILE__));

define('BKGT_DATA_SCRAPING_PLUGIN_URL', plugin_dir_url(__FILE__));define('BKGT_DATA_SCRAPING_PLUGIN_URL', plugin_dir_url(__FILE__));



// Plugin activation// Plugin activation

register_activation_hook(__FILE__, 'bkgt_activate');register_activation_hook(__FILE__, 'bkgt_activate');

function bkgt_activate() {function bkgt_activate() {

    add_option('bkgt_test', 'activated');    add_option('bkgt_test', 'activated');

}}



// Plugin deactivation// Plugin deactivation

register_deactivation_hook(__FILE__, 'bkgt_deactivate');register_deactivation_hook(__FILE__, 'bkgt_deactivate');

function bkgt_deactivate() {function bkgt_deactivate() {

    delete_option('bkgt_test');    delete_option('bkgt_test');

}}



// Register shortcodes// Register shortcodes

add_action('init', 'bkgt_register_shortcodes');add_action('init', 'bkgt_register_shortcodes');

function bkgt_register_shortcodes() {function bkgt_register_shortcodes() {

    add_shortcode('bkgt_players', 'bkgt_shortcode_players');    add_shortcode('bkgt_players', 'bkgt_shortcode_players');

    add_shortcode('bkgt_events', 'bkgt_shortcode_events');    add_shortcode('bkgt_events', 'bkgt_shortcode_events');

    add_shortcode('bkgt_team_overview', 'bkgt_shortcode_team_overview');    add_shortcode('bkgt_team_overview', 'bkgt_shortcode_team_overview');

    add_shortcode('bkgt_player_profile', 'bkgt_shortcode_player_profile');    add_shortcode('bkgt_player_profile', 'bkgt_shortcode_player_profile');

    add_shortcode('bkgt_admin_dashboard', 'bkgt_shortcode_admin_dashboard');    add_shortcode('bkgt_admin_dashboard', 'bkgt_shortcode_admin_dashboard');

}}



// Simple shortcode implementations// Simple shortcode implementations

function bkgt_shortcode_players($atts) {function bkgt_shortcode_players($atts) {

    return '<div class="bkgt-players"><p>BKGT Players shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';    return '<div class="bkgt-players"><p>BKGT Players shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';

}}



function bkgt_shortcode_events($atts) {function bkgt_shortcode_events($atts) {

    return '<div class="bkgt-events"><p>BKGT Events shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';    return '<div class="bkgt-events"><p>BKGT Events shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';

}}



function bkgt_shortcode_team_overview($atts) {function bkgt_shortcode_team_overview($atts) {

    return '<div class="bkgt-team-overview"><p>BKGT Team Overview shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';    return '<div class="bkgt-team-overview"><p>BKGT Team Overview shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';

}}



function bkgt_shortcode_player_profile($atts) {function bkgt_shortcode_player_profile($atts) {

    return '<div class="bkgt-player-profile"><p>BKGT Player Profile shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';    return '<div class="bkgt-player-profile"><p>BKGT Player Profile shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';

}}



function bkgt_shortcode_admin_dashboard($atts) {function bkgt_shortcode_admin_dashboard($atts) {

    if (!current_user_can('manage_options')) {    if (!current_user_can('manage_options')) {

        return '<div class="bkgt-access-denied"><p>You do not have permission to access this content.</p></div>';        return '<div class="bkgt-access-denied"><p>You do not have permission to access this content.</p></div>';

    }    }

    return '<div class="bkgt-admin-dashboard"><p>BKGT Admin Dashboard shortcode loaded successfully.</p><p>Full admin functionality will be restored after debugging.</p></div>';    return '<div class="bkgt-admin-dashboard"><p>BKGT Admin Dashboard shortcode loaded successfully.</p><p>Full admin functionality will be restored after debugging.</p></div>';

}}



// Enqueue frontend styles// Enqueue frontend styles

add_action('wp_enqueue_scripts', 'bkgt_enqueue_styles');add_action('wp_enqueue_scripts', 'bkgt_enqueue_styles');

function bkgt_enqueue_styles() {function bkgt_enqueue_styles() {

    wp_enqueue_style('bkgt-frontend', BKGT_DATA_SCRAPING_PLUGIN_URL . 'assets/css/frontend.css', array(), '1.0.0');    wp_enqueue_style('bkgt-frontend', BKGT_DATA_SCRAPING_PLUGIN_URL . 'assets/css/frontend.css', array(), '1.0.0');

}}

 * Version: 1.0.0 * Version: 1.0.0

 * Author: BKGT Development Team * Author: BKGT Development Team

 * License: GPL v2 or later * License: GPL v2 or later

 * Text Domain: bkgt-data-scraping * Text Domain: bkgt-data-scraping

 */ */



// Prevent direct access// Prevent direct access

if (!defined('ABSPATH')) {if (!defined('ABSPATH')) {

    exit;    exit;

}}



// Define plugin constants// Define plugin constants

define('BKGT_DATA_SCRAPING_VERSION', '1.0.0');define('BKGT_DATA_SCRAPING_VERSION', '1.0.0');

define('BKGT_DATA_SCRAPING_PLUGIN_DIR', plugin_dir_path(__FILE__));define('BKGT_DATA_SCRAPING_PLUGIN_DIR', plugin_dir_path(__FILE__));

define('BKGT_DATA_SCRAPING_PLUGIN_URL', plugin_dir_url(__FILE__));define('BKGT_DATA_SCRAPING_PLUGIN_URL', plugin_dir_url(__FILE__));



// Plugin activation// Plugin activation

register_activation_hook(__FILE__, 'bkgt_activate');register_activation_hook(__FILE__, 'bkgt_activate');

function bkgt_activate() {function bkgt_activate() {

    add_option('bkgt_test', 'activated');    add_option('bkgt_test', 'activated');

}}



// Plugin deactivation// Plugin deactivation

register_deactivation_hook(__FILE__, 'bkgt_deactivate');register_deactivation_hook(__FILE__, 'bkgt_deactivate');

function bkgt_deactivate() {function bkgt_deactivate() {

    delete_option('bkgt_test');    delete_option('bkgt_test');

}}



// Register shortcodes// Register shortcodes

add_action('init', 'bkgt_register_shortcodes');add_action('init', 'bkgt_register_shortcodes');

function bkgt_register_shortcodes() {function bkgt_register_shortcodes() {

    add_shortcode('bkgt_players', 'bkgt_shortcode_players');    add_shortcode('bkgt_players', 'bkgt_shortcode_players');

    add_shortcode('bkgt_events', 'bkgt_shortcode_events');    add_shortcode('bkgt_events', 'bkgt_shortcode_events');

    add_shortcode('bkgt_team_overview', 'bkgt_shortcode_team_overview');    add_shortcode('bkgt_team_overview', 'bkgt_shortcode_team_overview');

    add_shortcode('bkgt_player_profile', 'bkgt_shortcode_player_profile');    add_shortcode('bkgt_player_profile', 'bkgt_shortcode_player_profile');

    add_shortcode('bkgt_admin_dashboard', 'bkgt_shortcode_admin_dashboard');    add_shortcode('bkgt_admin_dashboard', 'bkgt_shortcode_admin_dashboard');

}}



// Simple shortcode implementations// Simple shortcode implementations

function bkgt_shortcode_players($atts) {function bkgt_shortcode_players($atts) {

    return '<div class="bkgt-players"><p>BKGT Players shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';    return '<div class="bkgt-players"><p>BKGT Players shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';

}}



function bkgt_shortcode_events($atts) {function bkgt_shortcode_events($atts) {

    return '<div class="bkgt-events"><p>BKGT Events shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';    return '<div class="bkgt-events"><p>BKGT Events shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';

}}



function bkgt_shortcode_team_overview($atts) {function bkgt_shortcode_team_overview($atts) {

    return '<div class="bkgt-team-overview"><p>BKGT Team Overview shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';    return '<div class="bkgt-team-overview"><p>BKGT Team Overview shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';

}}



function bkgt_shortcode_player_profile($atts) {function bkgt_shortcode_player_profile($atts) {

    return '<div class="bkgt-player-profile"><p>BKGT Player Profile shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';    return '<div class="bkgt-player-profile"><p>BKGT Player Profile shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';

}}



function bkgt_shortcode_admin_dashboard($atts) {function bkgt_shortcode_admin_dashboard($atts) {

    if (!current_user_can('manage_options')) {    if (!current_user_can('manage_options')) {

        return '<div class="bkgt-access-denied"><p>You do not have permission to access this content.</p></div>';        return '<div class="bkgt-access-denied"><p>You do not have permission to access this content.</p></div>';

    }    }

    return '<div class="bkgt-admin-dashboard"><p>BKGT Admin Dashboard shortcode loaded successfully.</p><p>Full admin functionality will be restored after debugging.</p></div>';    return '<div class="bkgt-admin-dashboard"><p>BKGT Admin Dashboard shortcode loaded successfully.</p><p>Full admin functionality will be restored after debugging.</p></div>';

}}



// Enqueue frontend styles// Enqueue frontend styles

add_action('wp_enqueue_scripts', 'bkgt_enqueue_styles');add_action('wp_enqueue_scripts', 'bkgt_enqueue_styles');

function bkgt_enqueue_styles() {function bkgt_enqueue_styles() {

    wp_enqueue_style('bkgt-frontend', BKGT_DATA_SCRAPING_PLUGIN_URL . 'assets/css/frontend.css', array(), '1.0.0');    wp_enqueue_style('bkgt-frontend', BKGT_DATA_SCRAPING_PLUGIN_URL . 'assets/css/frontend.css', array(), '1.0.0');

}}
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
        // Register shortcodes
        add_shortcode('bkgt_players', array($this, 'shortcode_players'));
        add_shortcode('bkgt_events', array($this, 'shortcode_events'));
        add_shortcode('bkgt_team_overview', array($this, 'shortcode_team_overview'));
        add_shortcode('bkgt_player_profile', array($this, 'shortcode_player_profile'));
        add_shortcode('bkgt_admin_dashboard', array($this, 'shortcode_admin_dashboard'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->db->create_tables();

        // Create sample data
        $this->create_sample_data();

        // Create main pages
        $this->create_main_pages();

        // Set default options
        add_option('bkgt_version', BKGT_DATA_SCRAPING_VERSION);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
    }

    /**
     * Create sample data
     */
    private function create_sample_data() {
        global $wpdb;

        // Create tables if they don't exist
        $charset_collate = $wpdb->get_charset_collate();

        // Players table
        $players_table = $wpdb->prefix . 'bkgt_players';
        $sql = "CREATE TABLE IF NOT EXISTS $players_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            team_id mediumint(9) DEFAULT NULL,
            first_name varchar(50) NOT NULL,
            last_name varchar(50) NOT NULL,
            position varchar(50) DEFAULT '',
            birth_date date DEFAULT NULL,
            jersey_number int(11) DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id)
        ) $charset_collate;";
        $wpdb->query($sql);

        // Teams table
        $teams_table = $wpdb->prefix . 'bkgt_teams';
        $sql = "CREATE TABLE IF NOT EXISTS $teams_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            category varchar(50) DEFAULT '',
            season varchar(20) DEFAULT '',
            coach varchar(100) DEFAULT '',
            PRIMARY KEY (id)
        ) $charset_collate;";
        $wpdb->query($sql);

        // Events table
        $events_table = $wpdb->prefix . 'bkgt_events';
        $sql = "CREATE TABLE IF NOT EXISTS $events_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event_id varchar(50) NOT NULL,
            title varchar(200) NOT NULL,
            event_type varchar(50) DEFAULT 'match',
            event_date datetime NOT NULL,
            location varchar(200) DEFAULT '',
            opponent varchar(100) DEFAULT '',
            home_away varchar(20) DEFAULT 'home',
            status varchar(20) DEFAULT 'scheduled',
            PRIMARY KEY (id),
            UNIQUE KEY event_id (event_id)
        ) $charset_collate;";
        $wpdb->query($sql);

        // Scraping logs table
        $logs_table = $wpdb->prefix . 'bkgt_scraping_logs';
        $sql = "CREATE TABLE IF NOT EXISTS $logs_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            operation_type varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'started',
            start_time datetime DEFAULT CURRENT_TIMESTAMP,
            end_time datetime DEFAULT NULL,
            records_processed int(11) DEFAULT 0,
            records_added int(11) DEFAULT 0,
            records_updated int(11) DEFAULT 0,
            error_message text DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        $wpdb->query($sql);

        // Insert sample teams
        $sample_teams = array(
            array('BKGT Herrar', 'Senior', '2024', 'Johan Karlsson'),
            array('BKGT Damer', 'Senior', '2024', 'Anna Svensson')
        );

        foreach ($sample_teams as $team) {
            $wpdb->insert($teams_table, array(
                'name' => $team[0],
                'category' => $team[1],
                'season' => $team[2],
                'coach' => $team[3]
            ));
        }

        // Insert sample players
        $sample_players = array(
            array(1, 'Erik', 'Andersson', 'Quarterback', '1995-03-15', 12),
            array(1, 'Lars', 'Johansson', 'Running Back', '1997-08-22', 28),
            array(1, 'Daniel', 'Nilsson', 'Wide Receiver', '1996-11-08', 81),
            array(1, 'Fredrik', 'Larsson', 'Offensive Line', '1994-05-30', 75),
            array(1, 'Anders', 'Svensson', 'Defensive Back', '1998-01-12', 24)
        );

        foreach ($sample_players as $player) {
            $wpdb->insert($players_table, array(
                'team_id' => $player[0],
                'first_name' => $player[1],
                'last_name' => $player[2],
                'position' => $player[3],
                'birth_date' => $player[4],
                'jersey_number' => $player[5],
                'status' => 'active'
            ));
        }

        // Insert sample events
        $sample_events = array(
            array('träning-001', 'Träning', 'training', date('Y-m-d H:i:s', strtotime('+2 days 18:00')), 'BKGT Arena', '', 'home'),
            array('match-001', 'Match vs Stockholm Snipers', 'match', date('Y-m-d H:i:s', strtotime('+7 days 14:00')), 'Zinkensdamms IP', 'Stockholm Snipers', 'away'),
            array('träning-002', 'Träning', 'training', date('Y-m-d H:i:s', strtotime('+9 days 18:00')), 'BKGT Arena', '', 'home')
        );

        foreach ($sample_events as $event) {
            $wpdb->insert($events_table, array(
                'event_id' => $event[0],
                'title' => $event[1],
                'event_type' => $event[2],
                'event_date' => $event[3],
                'location' => $event[4],
                'opponent' => $event[5],
                'home_away' => $event[6],
                'status' => 'scheduled'
            ));
        }
    }

    /**
     * Create main pages
     */
    private function create_main_pages() {
        $pages = array(
            array(
                'title' => 'Dokument',
                'slug' => 'dokument',
                'content' => 'Klubbens dokument och filer.'
            ),
            array(
                'title' => 'Kommunikation',
                'slug' => 'kommunikation',
                'content' => 'Intern kommunikation och meddelanden.'
            ),
            array(
                'title' => 'Utrustning',
                'slug' => 'utrustning',
                'content' => 'Hantering av klubbens utrustning och inventarier.'
            )
        );

        foreach ($pages as $page_data) {
            // Check if page already exists
            $existing_page = get_page_by_path($page_data['slug']);

            if (!$existing_page) {
                // Create new page
                wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_name' => $page_data['slug'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page'
                ));
            }
        }
    }

    /**
     * Admin dashboard shortcode - allows front-end access to admin functionality
     */
    public function shortcode_admin_dashboard($atts) {
        // Check if user has admin capabilities
        if (!current_user_can('manage_options')) {
            return '<div class="bkgt-access-denied"><p>Du har inte behörighet att komma åt denna sida.</p></div>';
        }

        $atts = shortcode_atts(array(
            'tab' => 'overview'
        ), $atts);

        // Enqueue admin scripts and styles for front-end
        wp_enqueue_script('bkgt-admin-js', BKGT_DATA_SCRAPING_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('bkgt-admin-css', BKGT_DATA_SCRAPING_PLUGIN_URL . 'admin/css/admin.css', array(), '1.0.0');
        wp_enqueue_style('bkgt-frontend-css', BKGT_DATA_SCRAPING_PLUGIN_URL . 'assets/css/frontend.css', array(), '1.0.0');

        // Localize script for AJAX
        wp_localize_script('bkgt-admin-js', 'bkgt_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkgt_admin_nonce')
        ));

        ob_start();
        ?>
        <div class="bkgt-frontend-admin wrap">
            <h1><?php _e('BKGT Datahantering', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-groups" aria-hidden="true"></span></h1>

            <!-- Tab Navigation -->
            <nav class="bkgt-tabs-nav" role="tablist" aria-label="<?php _e('Huvudnavigering', 'bkgt-data-scraping'); ?>">
                <button class="bkgt-tab-button active" data-tab="overview" role="tab" aria-selected="true" aria-controls="bkgt-tab-overview" id="bkgt-tab-overview-btn">
                    <span class="dashicons dashicons-dashboard" aria-hidden="true"></span>
                    <?php _e('Översikt', 'bkgt-data-scraping'); ?>
                </button>
                <button class="bkgt-tab-button" data-tab="players" role="tab" aria-selected="false" aria-controls="bkgt-tab-players" id="bkgt-tab-players-btn">
                    <span class="dashicons dashicons-groups" aria-hidden="true"></span>
                    <?php _e('Spelare', 'bkgt-data-scraping'); ?>
                </button>
                <button class="bkgt-tab-button" data-tab="scraper" role="tab" aria-selected="false" aria-controls="bkgt-tab-scraper" id="bkgt-tab-scraper-btn">
                    <span class="dashicons dashicons-update" aria-hidden="true"></span>
                    <?php _e('Skrapning', 'bkgt-data-scraping'); ?>
                </button>
                <button class="bkgt-tab-button" data-tab="settings" role="tab" aria-selected="false" aria-controls="bkgt-tab-settings" id="bkgt-tab-settings-btn">
                    <span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
                    <?php _e('Inställningar', 'bkgt-data-scraping'); ?>
                </button>
            </nav>

            <!-- Tab Content -->
            <main class="bkgt-tabs-content" id="bkgt-main-content" role="main">
                <!-- Overview Tab -->
                <div id="bkgt-tab-overview" class="bkgt-tab-panel active" role="tabpanel" aria-labelledby="bkgt-tab-overview-btn" tabindex="0">
                    <div class="bkgt-dashboard-grid">
                        <!-- Data Overview Cards -->
                        <div class="bkgt-dashboard-card bkgt-overview-card">
                            <h3><?php _e('Dataöversikt', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-chart-bar"></span></h3>
                            <div class="bkgt-stats-grid">
                                <?php
                                global $wpdb;
                                $players_table = $wpdb->prefix . 'bkgt_players';
                                $events_table = $wpdb->prefix . 'bkgt_events';
                                $teams_table = $wpdb->prefix . 'bkgt_teams';

                                $player_count = $wpdb->get_var("SELECT COUNT(*) FROM $players_table WHERE status = 'active'");
                                $event_count = $wpdb->get_var("SELECT COUNT(*) FROM $events_table WHERE status = 'scheduled'");
                                $team_count = $wpdb->get_var("SELECT COUNT(*) FROM $teams_table");
                                ?>
                                <div class="bkgt-stat-item">
                                    <span class="bkgt-stat-number"><?php echo $player_count; ?></span>
                                    <span class="bkgt-stat-label">Aktiva Spelare</span>
                                </div>
                                <div class="bkgt-stat-item">
                                    <span class="bkgt-stat-number"><?php echo $event_count; ?></span>
                                    <span class="bkgt-stat-label">Kommande Event</span>
                                </div>
                                <div class="bkgt-stat-item">
                                    <span class="bkgt-stat-number"><?php echo $team_count; ?></span>
                                    <span class="bkgt-stat-label">Lag</span>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="bkgt-dashboard-card bkgt-activity-card">
                            <h3><?php _e('Senaste Aktivitet', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-clock"></span></h3>
                            <div class="bkgt-activity-list">
                                <?php
                                $logs_table = $wpdb->prefix . 'bkgt_scraping_logs';
                                $recent_logs = $wpdb->get_results("SELECT * FROM $logs_table ORDER BY start_time DESC LIMIT 5");

                                if (!empty($recent_logs)) {
                                    foreach ($recent_logs as $log) {
                                        $status_class = $log->status === 'completed' ? 'success' : ($log->status === 'failed' ? 'error' : 'warning');
                                        echo '<div class="bkgt-activity-item ' . $status_class . '">';
                                        echo '<span class="bkgt-activity-time">' . date('H:i', strtotime($log->start_time)) . '</span>';
                                        echo '<span class="bkgt-activity-desc">' . ucfirst($log->operation_type) . ' - ' . $log->status . '</span>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<p>Ingen aktivitet ännu.</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Players Tab -->
                <div id="bkgt-tab-players" class="bkgt-tab-panel" role="tabpanel" aria-labelledby="bkgt-tab-players-btn" tabindex="0">
                    <div class="bkgt-section-header">
                        <h2><?php _e('Spelarhantering', 'bkgt-data-scraping'); ?></h2>
                        <button class="button button-primary" id="bkgt-add-player-btn">
                            <span class="dashicons dashicons-plus" aria-hidden="true"></span>
                            <?php _e('Lägg till Spelare', 'bkgt-data-scraping'); ?>
                        </button>
                    </div>

                    <div class="bkgt-players-table-container">
                        <table class="wp-list-table widefat fixed striped bkgt-players-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Namn', 'bkgt-data-scraping'); ?></th>
                                    <th><?php _e('Position', 'bkgt-data-scraping'); ?></th>
                                    <th><?php _e('Tröjnummer', 'bkgt-data-scraping'); ?></th>
                                    <th><?php _e('Lag', 'bkgt-data-scraping'); ?></th>
                                    <th><?php _e('Status', 'bkgt-data-scraping'); ?></th>
                                    <th><?php _e('Åtgärder', 'bkgt-data-scraping'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="bkgt-players-tbody">
                                <?php
                                $players = $wpdb->get_results("
                                    SELECT p.*, t.name as team_name
                                    FROM $players_table p
                                    LEFT JOIN $teams_table t ON p.team_id = t.id
                                    ORDER BY p.last_name, p.first_name
                                ");

                                foreach ($players as $player) {
                                    $status_class = $player->status === 'active' ? 'bkgt-status-active' : 'bkgt-status-inactive';
                                    echo '<tr data-id="' . $player->id . '">';
                                    echo '<td>' . esc_html($player->first_name . ' ' . $player->last_name) . '</td>';
                                    echo '<td>' . esc_html($player->position) . '</td>';
                                    echo '<td>' . esc_html($player->jersey_number ?: '-') . '</td>';
                                    echo '<td>' . esc_html($player->team_name ?: 'Ej tilldelad') . '</td>';
                                    echo '<td><span class="bkgt-status ' . $status_class . '">' . ucfirst($player->status) . '</span></td>';
                                    echo '<td>';
                                    echo '<button class="button button-small bkgt-edit-player" data-id="' . $player->id . '">' . __('Redigera', 'bkgt-data-scraping') . '</button> ';
                                    echo '<button class="button button-small bkgt-delete-player" data-id="' . $player->id . '">' . __('Ta bort', 'bkgt-data-scraping') . '</button>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Scraper Tab -->
                <div id="bkgt-tab-scraper" class="bkgt-tab-panel" role="tabpanel" aria-labelledby="bkgt-tab-scraper-btn" tabindex="0">
                    <div class="bkgt-section-header">
                        <h2><?php _e('Dataskrapning', 'bkgt-data-scraping'); ?></h2>
                        <button class="button button-primary" id="bkgt-run-scraper-btn">
                            <span class="dashicons dashicons-update" aria-hidden="true"></span>
                            <?php _e('Kör Skrapning', 'bkgt-data-scraping'); ?>
                        </button>
                    </div>

                    <div class="bkgt-scraper-status">
                        <div class="bkgt-status-indicator" id="bkgt-scraper-status">
                            <span class="dashicons dashicons-clock" aria-hidden="true"></span>
                            <?php _e('Redo att köra', 'bkgt-data-scraping'); ?>
                        </div>
                        <div class="bkgt-progress-bar" id="bkgt-scraper-progress" style="display: none;">
                            <div class="bkgt-progress-fill" id="bkgt-progress-fill"></div>
                            <span class="bkgt-progress-text" id="bkgt-progress-text">0%</span>
                        </div>
                    </div>

                    <div class="bkgt-scraper-logs">
                        <h3><?php _e('Skrapningsloggar', 'bkgt-data-scraping'); ?></h3>
                        <div class="bkgt-logs-container" id="bkgt-scraper-logs">
                            <?php
                            $logs = $wpdb->get_results("SELECT * FROM $logs_table ORDER BY start_time DESC LIMIT 10");
                            if (!empty($logs)) {
                                foreach ($logs as $log) {
                                    $status_class = $log->status === 'completed' ? 'success' : ($log->status === 'failed' ? 'error' : 'warning');
                                    echo '<div class="bkgt-log-entry ' . $status_class . '">';
                                    echo '<span class="bkgt-log-time">' . date('Y-m-d H:i:s', strtotime($log->start_time)) . '</span>';
                                    echo '<span class="bkgt-log-operation">' . ucfirst($log->operation_type) . '</span>';
                                    echo '<span class="bkgt-log-status">' . $log->status . '</span>';
                                    if ($log->error_message) {
                                        echo '<span class="bkgt-log-error">' . esc_html($log->error_message) . '</span>';
                                    }
                                    echo '</div>';
                                }
                            } else {
                                echo '<p>Inga loggar ännu.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Settings Tab -->
                <div id="bkgt-tab-settings" class="bkgt-tab-panel" role="tabpanel" aria-labelledby="bkgt-tab-settings-btn" tabindex="0">
                    <h2><?php _e('Inställningar', 'bkgt-data-scraping'); ?></h2>
                    <form id="bkgt-settings-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Automatisk Skrapning', 'bkgt-data-scraping'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="bkgt-auto-scrape-enabled" value="1">
                                        <?php _e('Aktivera automatisk skrapning', 'bkgt-data-scraping'); ?>
                                    </label>
                                    <p class="description"><?php _e('Skrapa data automatiskt enligt schema.', 'bkgt-data-scraping'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Skrapningsintervall', 'bkgt-data-scraping'); ?></th>
                                <td>
                                    <select id="bkgt-scrape-interval">
                                        <option value="daily"><?php _e('Dagligen', 'bkgt-data-scraping'); ?></option>
                                        <option value="twicedaily"><?php _e('Två gånger om dagen', 'bkgt-data-scraping'); ?></option>
                                        <option value="weekly"><?php _e('Veckovis', 'bkgt-data-scraping'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Spara Inställningar', 'bkgt-data-scraping'); ?></button>
                        </p>
                    </form>
                </div>
            </main>
        </div>

        <!-- Modal dialogs for forms -->
        <div id="bkgt-player-modal" class="bkgt-modal" style="display: none;">
            <div class="bkgt-modal-content">
                <div class="bkgt-modal-header">
                    <h3 id="bkgt-modal-title"><?php _e('Lägg till Spelare', 'bkgt-data-scraping'); ?></h3>
                    <button class="bkgt-modal-close">&times;</button>
                </div>
                <div class="bkgt-modal-body">
                    <form id="bkgt-player-form">
                        <input type="hidden" id="bkgt-player-id" value="">
                        <p>
                            <label for="bkgt-player-first-name"><?php _e('Förnamn', 'bkgt-data-scraping'); ?></label>
                            <input type="text" id="bkgt-player-first-name" required>
                        </p>
                        <p>
                            <label for="bkgt-player-last-name"><?php _e('Efternamn', 'bkgt-data-scraping'); ?></label>
                            <input type="text" id="bkgt-player-last-name" required>
                        </p>
                        <p>
                            <label for="bkgt-player-position"><?php _e('Position', 'bkgt-data-scraping'); ?></label>
                            <input type="text" id="bkgt-player-position">
                        </p>
                        <p>
                            <label for="bkgt-player-jersey"><?php _e('Tröjnummer', 'bkgt-data-scraping'); ?></label>
                            <input type="number" id="bkgt-player-jersey">
                        </p>
                        <p>
                            <label for="bkgt-player-birth-date"><?php _e('Födelsedatum', 'bkgt-data-scraping'); ?></label>
                            <input type="date" id="bkgt-player-birth-date">
                        </p>
                        <p>
                            <label for="bkgt-player-team"><?php _e('Lag', 'bkgt-data-scraping'); ?></label>
                            <select id="bkgt-player-team">
                                <option value=""><?php _e('Välj lag', 'bkgt-data-scraping'); ?></option>
                                <?php
                                $teams = $wpdb->get_results("SELECT * FROM $teams_table ORDER BY name");
                                foreach ($teams as $team) {
                                    echo '<option value="' . $team->id . '">' . esc_html($team->name) . '</option>';
                                }
                                ?>
                            </select>
                        </p>
                        <p>
                            <label for="bkgt-player-status"><?php _e('Status', 'bkgt-data-scraping'); ?></label>
                            <select id="bkgt-player-status">
                                <option value="active"><?php _e('Aktiv', 'bkgt-data-scraping'); ?></option>
                                <option value="inactive"><?php _e('Inaktiv', 'bkgt-data-scraping'); ?></option>
                            </select>
                        </p>
                    </form>
                </div>
                <div class="bkgt-modal-footer">
                    <button class="button" id="bkgt-modal-cancel"><?php _e('Avbryt', 'bkgt-data-scraping'); ?></button>
                    <button class="button button-primary" id="bkgt-modal-save"><?php _e('Spara', 'bkgt-data-scraping'); ?></button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * Main plugin function
 */
function bkgt_data_scraping() {
    return BKGT_Data_Scraping::get_instance();
}

// Initialize the plugin
bkgt_data_scraping();

/**
 * Create sample data for testing the plugin
 */
function bkgt_create_sample_data() {
    global $wpdb;

    // Create tables if they don't exist
    $charset_collate = $wpdb->get_charset_collate();

    // Players table
    $players_table = $wpdb->prefix . 'bkgt_players';
    $sql = "CREATE TABLE IF NOT EXISTS $players_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        team_id mediumint(9) DEFAULT NULL,
        name varchar(100) NOT NULL,
        position varchar(50) DEFAULT '',
        number int DEFAULT 0,
        birth_date date DEFAULT NULL,
        height int DEFAULT 0,
        weight int DEFAULT 0,
        nationality varchar(50) DEFAULT '',
        joined_date date DEFAULT NULL,
        status varchar(20) DEFAULT 'active',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (team_id) REFERENCES {$wpdb->prefix}bkgt_teams(id) ON DELETE SET NULL
    ) $charset_collate;";
    $wpdb->query($sql);

    // Events table
    $events_table = $wpdb->prefix . 'bkgt_events';
    $sql = "CREATE TABLE IF NOT EXISTS $events_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(200) NOT NULL,
        event_type varchar(50) DEFAULT 'match',
        event_date datetime NOT NULL,
        location varchar(100) DEFAULT '',
        opponent varchar(100) DEFAULT '',
        description text,
        status varchar(20) DEFAULT 'scheduled',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    $wpdb->query($sql);

    // Teams table
    $teams_table = $wpdb->prefix . 'bkgt_teams';
    $sql = "CREATE TABLE IF NOT EXISTS $teams_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        category varchar(50) DEFAULT '',
        season varchar(20) DEFAULT '',
        coach varchar(100) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    $wpdb->query($sql);

    // Insert sample players
    $sample_players = array(
        array(1, 'Erik', 'Johansson', 'Quarterback', '1995-03-15', 12),
        array(1, 'Marcus', 'Andersson', 'Running Back', '1997-07-22', 28),
        array(1, 'Daniel', 'Nilsson', 'Wide Receiver', '1996-11-08', 81),
        array(1, 'Fredrik', 'Larsson', 'Offensive Line', '1994-05-30', 75),
        array(1, 'Anders', 'Svensson', 'Defensive Back', '1998-01-12', 24)
    );

    foreach ($sample_players as $player) {
        $wpdb->insert($players_table, array(
            'team_id' => $player[0],
            'first_name' => $player[1],
            'last_name' => $player[2],
            'position' => $player[3],
            'birth_date' => $player[4],
            'jersey_number' => $player[5],
            'status' => 'active'
        ));
    }

    // Insert sample events
    $sample_events = array(
        array('träning-001', 'Träning', 'training', date('Y-m-d H:i:s', strtotime('+2 days 18:00')), 'BKGT Arena', '', 'home'),
        array('match-001', 'Match vs Stockholm Snipers', 'match', date('Y-m-d H:i:s', strtotime('+7 days 14:00')), 'Zinkensdamms IP', 'Stockholm Snipers', 'away'),
        array('träning-002', 'Träning', 'training', date('Y-m-d H:i:s', strtotime('+9 days 18:00')), 'BKGT Arena', '', 'home')
    );

    foreach ($sample_events as $event) {
        $wpdb->insert($events_table, array(
            'event_id' => $event[0],
            'title' => $event[1],
            'event_type' => $event[2],
            'event_date' => $event[3],
            'location' => $event[4],
            'opponent' => $event[5],
            'home_away' => $event[6],
            'status' => 'scheduled'
        ));
    }

    // Insert sample team
    $wpdb->insert($teams_table, array(
        'name' => 'BKGT Herrar',
        'category' => 'Senior',
        'season' => '2024',
        'coach' => 'Johan Karlsson'
    ));
}

    /**
     * Players shortcode
     */
    public function shortcode_players($atts) {
        global $wpdb;

        $atts = shortcode_atts(array(
            'show_filters' => 'true',
            'layout' => 'grid',
            'limit' => -1
        ), $atts);

        $players_table = $wpdb->prefix . 'bkgt_players';
        $query = "SELECT * FROM $players_table WHERE status = 'active' ORDER BY last_name, first_name";

        if ($atts['limit'] > 0) {
            $query .= $wpdb->prepare(" LIMIT %d", $atts['limit']);
        }

        $players = $wpdb->get_results($query);

        if (empty($players)) {
            return '<p>Inga spelare registrerade ännu.</p>';
        }

        ob_start();
        ?>
        <div class="bkgt-players <?php echo esc_attr($atts['layout']); ?>">
            <?php if ($atts['show_filters'] === 'true'): ?>
            <div class="bkgt-filters">
                <input type="text" id="bkgt-player-search" placeholder="Sök spelare..." class="bkgt-search-input">
            </div>
            <?php endif; ?>

            <div class="bkgt-players-grid">
                <?php foreach ($players as $player): ?>
                <div class="bkgt-player-card" data-name="<?php echo esc_attr(strtolower($player->first_name . ' ' . $player->last_name)); ?>">
                    <div class="bkgt-player-number"><?php echo esc_html($player->jersey_number ?: '?'); ?></div>
                    <div class="bkgt-player-info">
                        <h3><?php echo esc_html($player->first_name . ' ' . $player->last_name); ?></h3>
                        <p class="bkgt-player-position"><?php echo esc_html($player->position ?: 'Position ej angiven'); ?></p>
                        <?php if ($player->birth_date): ?>
                            <p class="bkgt-player-age"><?php echo date('Y') - date('Y', strtotime($player->birth_date)); ?> år</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Events shortcode
     */
    public function shortcode_events($atts) {
        global $wpdb;

        $atts = shortcode_atts(array(
            'upcoming' => 'true',
            'limit' => 10,
            'layout' => 'list'
        ), $atts);

        $events_table = $wpdb->prefix . 'bkgt_events';
        $where = "status = 'scheduled'";

        if ($atts['upcoming'] === 'true') {
            $where .= " AND event_date >= NOW()";
        }

        $query = $wpdb->prepare("SELECT * FROM $events_table WHERE $where ORDER BY event_date ASC LIMIT %d", $atts['limit']);
        $events = $wpdb->get_results($query);

        if (empty($events)) {
            return '<p>Inga kommande event.</p>';
        }

        ob_start();
        ?>
        <div class="bkgt-events <?php echo esc_attr($atts['layout']); ?>">
            <?php foreach ($events as $event): ?>
            <div class="bkgt-event-item">
                <div class="bkgt-event-date">
                    <?php echo date('d M Y H:i', strtotime($event->event_date)); ?>
                </div>
                <div class="bkgt-event-info">
                    <h3><?php echo esc_html($event->title); ?></h3>
                    <?php if ($event->location): ?>
                        <p class="bkgt-event-location"><?php echo esc_html($event->location); ?></p>
                    <?php endif; ?>
                    <?php if ($event->opponent): ?>
                        <p class="bkgt-event-opponent">Mot: <?php echo esc_html($event->opponent); ?></p>
                    <?php endif; ?>
                    <p class="bkgt-event-type"><?php echo esc_html(ucfirst($event->event_type)); ?> <?php echo $event->home_away === 'home' ? '(Hemmamatch)' : ($event->home_away === 'away' ? '(Bortamatch)' : ''); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Team overview shortcode
     */
    public function shortcode_team_overview($atts) {
        global $wpdb;

        $atts = shortcode_atts(array(
            'show_stats' => 'true',
            'show_upcoming' => 'true',
            'team_id' => null,
            'show_players' => 'true'
        ), $atts);

        $teams_table = $wpdb->prefix . 'bkgt_teams';
        $players_table = $wpdb->prefix . 'bkgt_players';
        $events_table = $wpdb->prefix . 'bkgt_events';

        // Get team info - if no team_id specified, get the first team
        $team = null;
        if ($atts['team_id']) {
            $team = $wpdb->get_row($wpdb->prepare("SELECT * FROM $teams_table WHERE id = %d", $atts['team_id']));
        } else {
            $team = $wpdb->get_row("SELECT * FROM $teams_table LIMIT 1");
        }

        // Get players for this team
        $players = array();
        if ($team && $atts['show_players'] === 'true') {
            $players = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $players_table WHERE team_id = %d AND status = 'active' ORDER BY last_name, first_name",
                $team->id
            ));
        }

        // Get upcoming events
        $upcoming_events = array();
        if ($atts['show_upcoming'] === 'true') {
            $upcoming_events = $wpdb->get_results("SELECT * FROM $events_table WHERE status = 'scheduled' AND event_date >= NOW() ORDER BY event_date ASC LIMIT 3");
        }

        ob_start();
        ?>
        <div class="bkgt-team-overview">
            <?php if ($team): ?>
            <div class="bkgt-team-header">
                <h2><?php echo esc_html($team->name); ?></h2>
                <p><?php echo esc_html($team->category); ?> - Säsong <?php echo esc_html($team->season); ?></p>
                <?php if ($team->coach): ?>
                    <p>Tränare: <?php echo esc_html($team->coach); ?></p>
                <?php endif; ?>
            </div>

            <?php if ($atts['show_players'] === 'true' && !empty($players)): ?>
            <div class="bkgt-team-players">
                <h3>Spelare (<?php echo count($players); ?>)</h3>
                <div class="bkgt-players-grid">
                    <?php foreach ($players as $player): ?>
                    <div class="bkgt-player-card" data-name="<?php echo esc_attr(strtolower($player->first_name . ' ' . $player->last_name)); ?>">
                        <div class="bkgt-player-number"><?php echo esc_html($player->jersey_number ?: '?'); ?></div>
                        <div class="bkgt-player-info">
                            <h4><?php echo esc_html($player->first_name . ' ' . $player->last_name); ?></h4>
                            <p class="bkgt-player-position"><?php echo esc_html($player->position ?: 'Position ej angiven'); ?></p>
                            <?php if ($player->birth_date): ?>
                                <p class="bkgt-player-age"><?php echo date('Y') - date('Y', strtotime($player->birth_date)); ?> år</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($atts['show_upcoming'] === 'true' && !empty($upcoming_events)): ?>
            <div class="bkgt-upcoming-events">
                <h3>Kommande Matcher & Event</h3>
                <div class="bkgt-events">
                    <?php foreach ($upcoming_events as $event): ?>
                    <div class="bkgt-event-item">
                        <div class="bkgt-event-date">
                            <?php echo date('d M Y H:i', strtotime($event->event_date)); ?>
                        </div>
                        <div class="bkgt-event-info">
                            <h3><?php echo esc_html($event->title); ?></h3>
                            <?php if ($event->location): ?>
                                <p class="bkgt-event-location"><?php echo esc_html($event->location); ?></p>
                            <?php endif; ?>
                            <?php if ($event->opponent): ?>
                                <p class="bkgt-event-opponent">Mot: <?php echo esc_html($event->opponent); ?></p>
                            <?php endif; ?>
                            <p class="bkgt-event-type"><?php echo esc_html(ucfirst($event->event_type)); ?> <?php echo $event->home_away === 'home' ? '(Hemmamatch)' : ($event->home_away === 'away' ? '(Bortamatch)' : ''); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Player profile shortcode
     */
    public function shortcode_player_profile($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);

        if (!$atts['id']) {
            return '<p>Ange ett spelare-ID för att visa profilen.</p>';
        }

        global $wpdb;
        $players_table = $wpdb->prefix . 'bkgt_players';
        $player = $wpdb->get_row($wpdb->prepare("SELECT * FROM $players_table WHERE id = %d", $atts['id']));

        if (!$player) {
            return '<p>Spelaren hittades inte.</p>';
        }

        ob_start();
        ?>
        <div class="bkgt-player-profile">
            <div class="bkgt-player-header">
                <div class="bkgt-player-number"><?php echo esc_html($player->jersey_number ?: '?'); ?></div>
                <h2><?php echo esc_html($player->first_name . ' ' . $player->last_name); ?></h2>
                <p class="bkgt-player-position"><?php echo esc_html($player->position ?: 'Position ej angiven'); ?></p>
            </div>

            <div class="bkgt-player-details">
                <?php if ($player->birth_date): ?>
                    <p><strong>Ålder:</strong> <?php echo date('Y') - date('Y', strtotime($player->birth_date)); ?> år</p>
                <?php endif; ?>
                <p><strong>Status:</strong> <?php echo ucfirst($player->status); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    global $wpdb;

    $atts = shortcode_atts(array(
        'show_filters' => 'true',
        'layout' => 'grid',
        'limit' => -1
    ), $atts);

    $players_table = $wpdb->prefix . 'bkgt_players';
    $query = "SELECT * FROM $players_table WHERE status = 'active' ORDER BY name";

    if ($atts['limit'] > 0) {
        $query .= $wpdb->prepare(" LIMIT %d", $atts['limit']);
    }

    $players = $wpdb->get_results($query);

    if (empty($players)) {
        return '<p>Inga spelare registrerade ännu.</p>';
    }

    ob_start();
    ?>
    <div class="bkgt-players <?php echo esc_attr($atts['layout']); ?>">
        <?php if ($atts['show_filters'] === 'true'): ?>
        <div class="bkgt-filters">
            <input type="text" id="bkgt-player-search" placeholder="Sök spelare..." class="bkgt-search-input">
        </div>
        <?php endif; ?>

        <div class="bkgt-players-grid">
            <?php foreach ($players as $player): ?>
            <div class="bkgt-player-card" data-name="<?php echo esc_attr(strtolower($player->first_name . ' ' . $player->last_name)); ?>">
                <div class="bkgt-player-number"><?php echo esc_html($player->jersey_number); ?></div>
                <div class="bkgt-player-info">
                    <h3><?php echo esc_html($player->first_name . ' ' . $player->last_name); ?></h3>
                    <p class="bkgt-player-position"><?php echo esc_html($player->position); ?></p>
                    <?php if ($player->birth_date): ?>
                        <p class="bkgt-player-age"><?php echo date('Y') - date('Y', strtotime($player->birth_date)); ?> år</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Enqueue frontend styles
 */
add_action('wp_enqueue_scripts', 'bkgt_enqueue_styles');
function bkgt_enqueue_styles() {
    wp_enqueue_style('bkgt-frontend', BKGT_DATA_SCRAPING_PLUGIN_URL . 'assets/css/frontend.css', array(), '1.0.0');
}

/**
 * Main plugin function
 */
function bkgt_data_scraping() {
    return BKGT_Data_Scraping::get_instance();
}

// Initialize the plugin
bkgt_data_scraping();

/**
 * Enqueue frontend styles
 */
add_action('wp_enqueue_scripts', 'bkgt_enqueue_styles');
function bkgt_enqueue_styles() {
    wp_enqueue_style('bkgt-frontend', BKGT_DATA_SCRAPING_PLUGIN_URL . 'assets/css/frontend.css', array(), '1.0.0');
}
