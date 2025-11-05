<?php
/**
 * Plugin Name: BKGT Data Scraping & Management
 * Plugin URI: https://github.com/your-repo/bkgt-data-scraping
 * Description: Automated data retrieval and manual entry system for BKGT football club management. Scrapes player data, events, and statistics from svenskalag.se with authenticated access and fallback manual entry capabilities.
 * Version: 1.1.0
 * Author: BKGT Development Team
 * License: GPL v2 or later
 * Text Domain: bkgt-data-scraping
 * Requires at least: 5.0
 * Tested up to: 6.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BKGT_DATA_SCRAPING_VERSION', '1.1.0');
define('BKGT_DATA_SCRAPING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKGT_DATA_SCRAPING_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once BKGT_DATA_SCRAPING_PLUGIN_DIR . 'includes/class-bkgt-database.php';
require_once BKGT_DATA_SCRAPING_PLUGIN_DIR . 'includes/class-bkgt-scraper.php';
require_once BKGT_DATA_SCRAPING_PLUGIN_DIR . 'includes/class-bkgt-admin.php';

// Activation hook
register_activation_hook(__FILE__, 'bkgt_data_scraping_activate');

function bkgt_data_scraping_activate() {
    // Create database tables
    $db = new BKGT_Database();
    $db->create_tables();

    // Set default options
    add_option('bkgt_scraping_enabled', 'no');
    add_option('bkgt_scraping_source_url', 'https://www.svenskalag.se/bkgt');
    add_option('bkgt_scraping_interval', 'daily');
    add_option('bkgt_scraping_username', '');
    add_option('bkgt_scraping_password', '');

    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'bkgt_data_scraping_deactivate');

function bkgt_data_scraping_deactivate() {
    // Clear any scheduled events
    wp_clear_scheduled_hook('bkgt_daily_scraping');

    // Flush rewrite rules
    flush_rewrite_rules();
}

// Initialize the plugin
add_action('plugins_loaded', 'bkgt_data_scraping_init');

function bkgt_data_scraping_init() {
    // Load text domain
    load_plugin_textdomain('bkgt-data-scraping', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Initialize database
    $db = new BKGT_Database();

    // Initialize admin interface
    if (is_admin()) {
        new BKGT_Admin($db);
    }

    // Initialize scraper
    $scraper = new BKGT_Scraper($db);
}

// Utility functions for shortcodes
require_once BKGT_DATA_SCRAPING_PLUGIN_DIR . 'includes/shortcodes.php';