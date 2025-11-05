<?php
/**
 * Simple test to check if WordPress admin is accessible
 */

// Load WordPress
require_once('wp-load.php');

echo "<h1>WordPress Admin Access Test</h1>";
echo "<p>If you can see this, wp-admin is working!</p>";

// Check if user is logged in
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    echo "<p>Logged in as: " . esc_html($current_user->display_name) . "</p>";
    echo "<p>User role: " . implode(', ', $current_user->roles) . "</p>";
} else {
    echo "<p>Not logged in</p>";
}

// Check plugin status
$active_plugins = get_option('active_plugins');
$bkgt_plugin = 'bkgt-data-scraping/bkgt-data-scraping.php';
$is_active = in_array($bkgt_plugin, $active_plugins);

echo "<h2>Plugin Status</h2>";
echo "<p>BKGT Plugin active: " . ($is_active ? 'YES' : 'NO') . "</p>";

if ($is_active) {
    echo "<p>Plugin loaded successfully - basic functionality available</p>";
    echo "<p>Shortcodes should work on front-end pages</p>";
} else {
    echo "<p>Plugin not active - activate it in wp-admin</p>";
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Go to <a href='" . admin_url('plugins.php') . "'>Plugins page</a> and activate BKGT plugin</li>";
echo "<li>Test shortcodes on front-end pages</li>";
echo "<li>Full admin functionality will be restored after debugging</li>";
echo "</ol>";
?>