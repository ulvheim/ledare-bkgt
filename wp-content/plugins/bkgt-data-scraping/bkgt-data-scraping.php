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
 * Requires Plugins: bkgt-core
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BKGT_DATA_SCRAPING_VERSION', '1.1.0');
define('BKGT_DATA_SCRAPING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKGT_DATA_SCRAPING_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files with error handling
try {
    require_once BKGT_DATA_SCRAPING_PLUGIN_DIR . 'includes/class-bkgt-database.php';
} catch (Exception $e) {
    error_log('BKGT Data Scraping: Failed to load database class: ' . $e->getMessage());
    return; // Exit early if critical file fails to load
}

try {
    require_once BKGT_DATA_SCRAPING_PLUGIN_DIR . 'includes/class-bkgt-scraper.php';
} catch (Exception $e) {
    error_log('BKGT Data Scraping: Failed to load scraper class: ' . $e->getMessage());
    return; // Exit early if critical file fails to load
}

try {
    require_once BKGT_DATA_SCRAPING_PLUGIN_DIR . 'includes/class-bkgt-admin.php';
} catch (Exception $e) {
    error_log('BKGT Data Scraping: Failed to load admin class: ' . $e->getMessage());
    return; // Exit early if critical file fails to load
}

// Activation hook
register_activation_hook(__FILE__, 'bkgt_data_scraping_activate');

function bkgt_data_scraping_activate() {
    // Check for BKGT Core
    if (!function_exists('bkgt_log')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('BKGT Core plugin must be activated first.', 'bkgt-data-scraping'));
    }
    
    bkgt_log('info', 'Data Scraping plugin activated');
    
    // Only set basic options during activation - defer everything else
    if (!get_option('bkgt_scraping_enabled')) {
        add_option('bkgt_scraping_enabled', 'no');
    }
    if (!get_option('bkgt_scraping_source_url')) {
        add_option('bkgt_scraping_source_url', 'https://www.svenskalag.se/bkgt');
    }
    if (!get_option('bkgt_scraping_interval')) {
        add_option('bkgt_scraping_interval', 'daily');
    }
    if (!get_option('bkgt_scraping_username')) {
        add_option('bkgt_scraping_username', '');
    }
    if (!get_option('bkgt_scraping_password')) {
        add_option('bkgt_scraping_password', '');
    }

    // Flush rewrite rules
    if (function_exists('flush_rewrite_rules')) {
        flush_rewrite_rules();
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'bkgt_data_scraping_deactivate');

function bkgt_data_scraping_deactivate() {
    // Clear any scheduled events
    wp_clear_scheduled_hook('bkgt_daily_scraping');

    // Flush rewrite rules
    if (function_exists('flush_rewrite_rules')) {
        flush_rewrite_rules();
    }
    
    if (function_exists('bkgt_log')) {
        bkgt_log('info', 'Data Scraping plugin deactivated');
    }
}

// Initialize the plugin
add_action('plugins_loaded', 'bkgt_data_scraping_init');

function bkgt_data_scraping_init() {
    // Load text domain
    if (function_exists('load_plugin_textdomain')) {
        load_plugin_textdomain('bkgt-data-scraping', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    try {
        // Initialize database and create tables if needed
        bkgt_data_scraping_create_tables();

        // Initialize admin interface only if in admin and class exists
        if (is_admin() && class_exists('BKGT_Admin')) {
            global $bkgt_db;
            if (!isset($bkgt_db) && class_exists('BKGT_DataScraping_Database')) {
                $bkgt_db = new BKGT_DataScraping_Database();
            }
            if (isset($bkgt_db)) {
                new BKGT_Admin($bkgt_db);
            }
        }

        // Initialize scraper if class exists
        if (class_exists('BKGT_Scraper')) {
            global $bkgt_db;
            if (!isset($bkgt_db) && class_exists('BKGT_DataScraping_Database')) {
                $bkgt_db = new BKGT_DataScraping_Database();
            }
            if (isset($bkgt_db)) {
                $scraper = new BKGT_Scraper($bkgt_db);
            }
        }
    } catch (Exception $e) {
        // Log initialization error
        error_log('BKGT Data Scraping Plugin initialization failed: ' . $e->getMessage());

        // Add admin notice for the error only in admin
        if (is_admin() && function_exists('add_action')) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p><strong>BKGT Data Scraping Plugin Error:</strong> ' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }
}

// Utility functions
function bkgt_data_scraping_create_tables() {
    if (class_exists('BKGT_DataScraping_Database')) {
        $db = new BKGT_DataScraping_Database();
        return $db->create_tables();
    }
    return false;
}

// Utility functions for shortcodes - include with error handling
try {
    require_once BKGT_DATA_SCRAPING_PLUGIN_DIR . 'includes/shortcodes.php';
} catch (Exception $e) {
    error_log('BKGT Data Scraping: Failed to load shortcodes: ' . $e->getMessage());
}