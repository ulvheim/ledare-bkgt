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