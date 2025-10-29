<?php
require_once('wp-load.php');
echo "Active plugins: " . count(get_option('active_plugins')) . "\n";
echo "Theme: " . get_option('stylesheet') . "\n";
?>