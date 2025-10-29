<?php
// Emergency plugin disabler
require_once 'wp-load.php';

// Deactivate the problematic plugin
$active_plugins = get_option('active_plugins');
$plugin_to_disable = 'bkgt-data-scraping/bkgt-data-scraping.php';

if (($key = array_search($plugin_to_disable, $active_plugins)) !== false) {
    unset($active_plugins[$key]);
    update_option('active_plugins', $active_plugins);
    echo "Plugin deactivated successfully.";
} else {
    echo "Plugin was not active.";
}

// Also try to deactivate other potentially problematic plugins
$plugins_to_check = [
    'bkgt-communication/bkgt-communication.php',
    'bkgt-document-management/bkgt-document-management.php',
    'bkgt-inventory/bkgt-inventory.php',
    'bkgt-user-management/bkgt-user-management.php'
];

foreach ($plugins_to_check as $plugin) {
    if (($key = array_search($plugin, $active_plugins)) !== false) {
        unset($active_plugins[$key]);
        echo "Deactivated: $plugin\n";
    }
}

update_option('active_plugins', $active_plugins);
echo "All potentially problematic plugins have been deactivated.";
?>