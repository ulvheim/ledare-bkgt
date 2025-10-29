<?php
require_once('wp-load.php');

// Disable all plugins
update_option('active_plugins', array());
echo "All plugins disabled\n";

// Check current status
echo "Active plugins: " . count(get_option('active_plugins')) . "\n";
echo "Theme: " . get_option('stylesheet') . "\n";
?>