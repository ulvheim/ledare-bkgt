<?php
// Test WordPress loading
define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "WordPress loaded successfully!\n";
echo "Version: " . get_bloginfo('version') . "\n";
echo "Site URL: " . get_bloginfo('url') . "\n";
echo "Theme: " . get_option('stylesheet') . "\n";
echo "Active plugins: " . count(get_option('active_plugins')) . "\n";
?>