<?php
// Test page for Team & Player functionality
// Access via: https://ledare.bkgt.se/test-team-player.php

require_once('wp-load.php');

echo "<h1>Team & Player Plugin Test</h1>";

// Check if plugin is active
if (is_plugin_active('bkgt-team-player/bkgt-team-player.php')) {
    echo "<p style='color: green;'>✓ Plugin is active</p>";

    // Test shortcodes
    echo "<h2>Testing Shortcodes</h2>";

    echo "<h3>Team Page Shortcode:</h3>";
    echo do_shortcode('[bkgt_team_page]');

    echo "<h3>Player Dossier Shortcode (if player exists):</h3>";
    echo do_shortcode('[bkgt_player_dossier player="1"]');

    echo "<h3>Performance Page Shortcode:</h3>";
    echo do_shortcode('[bkgt_performance_page]');

} else {
    echo "<p style='color: red;'>✗ Plugin is not active</p>";
    echo "<p><a href='activate-team-player-web.php'>Click here to activate the plugin</a></p>";
}

// Check database tables
global $wpdb;
$tables = array(
    'bkgt_teams',
    'bkgt_players',
    'bkgt_player_notes',
    'bkgt_performance_ratings',
    'bkgt_player_statistics'
);

echo "<h2>Database Tables Check</h2>";
foreach ($tables as $table) {
    $table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    if ($exists) {
        echo "<p style='color: green;'>✓ Table $table_name exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Table $table_name missing</p>";
    }
}

echo "<p><a href='" . home_url() . "'>Return to homepage</a></p>";
?>