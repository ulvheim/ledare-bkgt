<?php
/**
 * Script to activate the SWE3 plugin on the server
 * Run from the web root directory
 */

define('WP_USE_THEMES', false);
require dirname(__FILE__) . '/wp-load.php';

// Check if plugin exists
$plugin_file = 'bkgt-swe3-scraper/bkgt-swe3-scraper.php';

echo "Attempting to activate $plugin_file\n";

// Get current active plugins list
$active_plugins = get_option('active_plugins', array());

if (!is_array($active_plugins)) {
    $active_plugins = array();
}

// Check if already active
if (in_array($plugin_file, $active_plugins)) {
    echo "Plugin is already active\n";
} else {
    // Add to active list
    $active_plugins[] = $plugin_file;
    update_option('active_plugins', $active_plugins);
    echo "Plugin activated in database\n";
}

// Verify it's in the database
$verify = get_option('active_plugins', array());
if (in_array($plugin_file, $verify)) {
    echo "✓ Plugin activation verified in database\n";
} else {
    echo "✗ Plugin activation FAILED\n";
}

// Try to check if plugin is now accessible
require_once dirname(__FILE__) . '/wp-content/plugins/bkgt-swe3-scraper/bkgt-swe3-scraper.php';

// Trigger plugin loading
do_action('plugins_loaded');

echo "\n✓ Plugin file loaded and activated\n";
echo "✓ REST endpoint should now be available at /wp-json/bkgt/v1/swe3-upload-document\n";
?>
