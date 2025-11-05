<?php
/**
 * Plugin Name: BKGT Data Scraping & Management
 * Plugin URI: https://github.com/your-repo/bkgt-data-scraping
 * Description: Automated data retrieval and manual entry system for BKGT football club management.
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

// Plugin activation
register_activation_hook(__FILE__, 'bkgt_activate');
function bkgt_activate() {
    add_option('bkgt_test', 'activated');
}

// Plugin deactivation
register_deactivation_hook(__FILE__, 'bkgt_deactivate');
function bkgt_deactivate() {
    delete_option('bkgt_test');
}

// Register shortcodes
add_action('init', 'bkgt_register_shortcodes');
function bkgt_register_shortcodes() {
    add_shortcode('bkgt_players', 'bkgt_shortcode_players');
    add_shortcode('bkgt_events', 'bkgt_shortcode_events');
    add_shortcode('bkgt_team_overview', 'bkgt_shortcode_team_overview');
    add_shortcode('bkgt_player_profile', 'bkgt_shortcode_player_profile');
    add_shortcode('bkgt_admin_dashboard', 'bkgt_shortcode_admin_dashboard');
}

// Simple shortcode implementations
function bkgt_shortcode_players() {
    return '<div class= bkgt-players><p>BKGT Players shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';
}

function bkgt_shortcode_events() {
    return '<div class=bkgt-events><p>BKGT Events shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';
}

function bkgt_shortcode_team_overview() {
    return '<div class=bkgt-team-overview><p>BKGT Team Overview shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';
}

function bkgt_shortcode_player_profile() {
    return '<div class=bkgt-player-profile><p>BKGT Player Profile shortcode loaded successfully.</p><p>Full functionality will be restored after debugging.</p></div>';
}

function bkgt_shortcode_admin_dashboard() {
    if (!current_user_can('manage_options')) {
        return '<div class=bkgt-access-denied><p>You do not have permission to access this content.</p></div>';
    }
    return '<div class=bkgt-admin-dashboard><p>BKGT Admin Dashboard shortcode loaded successfully.</p><p>Full admin functionality will be restored after debugging.</p></div>';
}

// Enqueue frontend styles
add_action('wp_enqueue_scripts', 'bkgt_enqueue_styles');
function bkgt_enqueue_styles() {
    wp_enqueue_style('bkgt-frontend', BKGT_DATA_SCRAPING_PLUGIN_URL . 'assets/css/frontend.css', array(), '1.0.0');
}
