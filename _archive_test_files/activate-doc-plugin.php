<?php
require_once('wp-load.php');

$plugin_path = 'bkgt-document-management/bkgt-document-management.php';

// Check if plugin exists
if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_path)) {
    echo "Plugin file not found: $plugin_path\n";
    exit(1);
}

// Get current active plugins
$active_plugins = get_option('active_plugins', array());

// Add our plugin if not already active
if (!in_array($plugin_path, $active_plugins)) {
    $active_plugins[] = $plugin_path;
    update_option('active_plugins', $active_plugins);
    echo "Plugin activated: $plugin_path\n";
} else {
    echo "Plugin already active: $plugin_path\n";
}

// Verify activation
$updated_plugins = get_option('active_plugins');
if (in_array($plugin_path, $updated_plugins)) {
    echo "Activation successful!\n";
} else {
    echo "Activation failed!\n";
}
?>