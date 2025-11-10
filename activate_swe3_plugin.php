<?php
/**
 * Script to activate the SWE3 scraper and DMS plugins
 */

require_once 'wp-load.php';

// Get current active plugins
$active_plugins = get_option('active_plugins', array());

// Plugins to activate
$plugins_to_activate = array(
    'bkgt-document-management/bkgt-document-management.php',
    'bkgt-swe3-scraper/bkgt-swe3-scraper.php'
);

$activated = array();
foreach ($plugins_to_activate as $plugin_file) {
    if (!in_array($plugin_file, $active_plugins)) {
        $active_plugins[] = $plugin_file;
        $activated[] = $plugin_file;
        echo "Activated: $plugin_file\n";
    } else {
        echo "Already active: $plugin_file\n";
    }
}

// Update the option
if (!empty($activated)) {
    update_option('active_plugins', $active_plugins);
    echo "Plugins activated successfully\n";
} else {
    echo "All plugins were already active\n";
}

// Verify the plugins are loaded
if (function_exists('bkgt_swe3_scraper')) {
    echo "SWE3 scraper plugin is loaded\n";
} else {
    echo "SWE3 scraper plugin is not loaded\n";
}
?>