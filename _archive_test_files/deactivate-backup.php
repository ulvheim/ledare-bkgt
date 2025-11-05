<?php
require_once('wp-load.php');
$active_plugins = get_option('active_plugins');
$backup_plugin = 'bkgt-data-scraping-backup/bkgt-data-scraping.php';
if (($key = array_search($backup_plugin, $active_plugins)) !== false) {
    unset($active_plugins[$key]);
    update_option('active_plugins', $active_plugins);
    echo "Backup plugin deactivated\n";
}
echo "Active plugins:\n";
foreach (get_option('active_plugins') as $plugin) {
    echo "- $plugin\n";
}
?>