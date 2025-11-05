<?php
// Activate BKGT Team & Player Management Plugin
require_once('../../../wp-load.php');

if (!function_exists('activate_plugin')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

$plugin = 'bkgt-team-player/bkgt-team-player.php';

if (!is_plugin_active($plugin)) {
    $result = activate_plugin($plugin);

    if (is_wp_error($result)) {
        echo "Error activating plugin: " . $result->get_error_message() . "\n";
    } else {
        echo "Plugin activated successfully!\n";

        // Run database setup
        if (class_exists('BKGT_Team_Player_Database')) {
            BKGT_Team_Player_Database::create_tables();
            echo "Database tables created successfully!\n";
        } else {
            echo "Warning: Database class not found. Tables may need to be created manually.\n";
        }
    }
} else {
    echo "Plugin is already active.\n";
}
?>