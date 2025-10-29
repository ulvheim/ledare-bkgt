<?php
// Web-accessible plugin activation script
// Access via: https://ledare.bkgt.se/activate-team-player-web.php

require_once('wp-load.php');

if (!current_user_can('activate_plugins')) {
    wp_die('You do not have permission to activate plugins.');
}

if (!function_exists('activate_plugin')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

$plugin = 'bkgt-team-player/bkgt-team-player.php';

echo "<h1>BKGT Team & Player Plugin Activation</h1>";

if (!is_plugin_active($plugin)) {
    echo "<p>Activating plugin...</p>";

    $result = activate_plugin($plugin);

    if (is_wp_error($result)) {
        echo "<p style='color: red;'>Error activating plugin: " . $result->get_error_message() . "</p>";
    } else {
        echo "<p style='color: green;'>Plugin activated successfully!</p>";

        // Run database setup
        if (class_exists('BKGT_Team_Player_Database')) {
            BKGT_Team_Player_Database::create_tables();
            echo "<p style='color: green;'>Database tables created successfully!</p>";
        } else {
            echo "<p style='color: orange;'>Warning: Database class not found. Tables may need to be created manually.</p>";
        }
    }
} else {
    echo "<p style='color: blue;'>Plugin is already active.</p>";
}

echo "<p><a href='" . admin_url('plugins.php') . "'>Return to Plugins page</a></p>";
echo "<p><a href='" . home_url() . "'>Return to homepage</a></p>";
?>