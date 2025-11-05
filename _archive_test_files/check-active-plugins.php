<?php
// Check active plugins
require_once 'wp-load.php';

$active_plugins = get_option('active_plugins');
echo "Active plugins:\n";
foreach ($active_plugins as $plugin) {
    echo "- $plugin\n";
}

$bkgt_plugin = 'bkgt-data-scraping/bkgt-data-scraping.php';
if (in_array($bkgt_plugin, $active_plugins)) {
    echo "\nBKGT plugin is ACTIVE\n";
} else {
    echo "\nBKGT plugin is NOT active\n";
}
?>