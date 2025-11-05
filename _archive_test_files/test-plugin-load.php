<?php
// Simple test to check if the plugin loads without errors
require_once 'wp-load.php';

echo "WordPress loaded successfully.\n";

// Check if our plugin is active
$active_plugins = get_option('active_plugins');
if (in_array('bkgt-data-scraping/bkgt-data-scraping.php', $active_plugins)) {
    echo "BKGT plugin is active.\n";
} else {
    echo "BKGT plugin is NOT active.\n";
}

// Test shortcode
echo "Testing shortcode: " . do_shortcode('[bkgt_players]');
?>