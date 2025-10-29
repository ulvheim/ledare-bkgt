<?php
/**
 * Test script to check BKGT plugin activation and database tables
 */

// Load WordPress
require_once('wp-load.php');

echo "<h1>BKGT Plugin Test</h1>";

// Check if plugin is active
$active_plugins = get_option('active_plugins');
$bkgt_plugin = 'bkgt-data-scraping/bkgt-data-scraping.php';
$is_active = in_array($bkgt_plugin, $active_plugins);

echo "<h2>Plugin Status</h2>";
echo "<p>Plugin active: " . ($is_active ? 'YES' : 'NO') . "</p>";

// Check database tables
global $wpdb;
$tables = array(
    'bkgt_players' => $wpdb->prefix . 'bkgt_players',
    'bkgt_teams' => $wpdb->prefix . 'bkgt_teams',
    'bkgt_events' => $wpdb->prefix . 'bkgt_events',
    'bkgt_scraping_logs' => $wpdb->prefix . 'bkgt_scraping_logs'
);

echo "<h2>Database Tables</h2>";
foreach ($tables as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    echo "<p>$name: " . ($exists ? 'EXISTS' : 'MISSING') . "</p>";

    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "<p>&nbsp;&nbsp;Records: $count</p>";
    }
}

// Test shortcodes
echo "<h2>Shortcode Test</h2>";
if (function_exists('bkgt_data_scraping')) {
    echo "<p>Main plugin function: EXISTS</p>";
    $instance = bkgt_data_scraping();
    echo "<p>Plugin instance: " . (is_object($instance) ? 'CREATED' : 'FAILED') . "</p>";
} else {
    echo "<p>Main plugin function: MISSING</p>";
}

// Check if shortcodes are registered
global $shortcode_tags;
$bkgt_shortcodes = array('bkgt_players', 'bkgt_events', 'bkgt_team_overview', 'bkgt_player_profile', 'bkgt_admin_dashboard');
echo "<h3>Registered Shortcodes:</h3>";
foreach ($bkgt_shortcodes as $shortcode) {
    $registered = isset($shortcode_tags[$shortcode]);
    echo "<p>$shortcode: " . ($registered ? 'REGISTERED' : 'NOT REGISTERED') . "</p>";
}

echo "<h2>Test Complete</h2>";
?>