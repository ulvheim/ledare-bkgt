<?php
require_once('wp-load.php');

echo "=== Active Plugins Check ===\n\n";

$active_plugins = get_option('active_plugins');
if (empty($active_plugins)) {
    echo "No active plugins found.\n";
} else {
    echo "Active plugins:\n";
    foreach ($active_plugins as $plugin) {
        echo "- $plugin\n";
    }
}

echo "\n=== BKGT Plugin Status ===\n";

$bkgt_plugins = array(
    'bkgt-data-scraping/bkgt-data-scraping.php',
    'bkgt-inventory/bkgt-inventory.php'
);

foreach ($bkgt_plugins as $plugin) {
    $is_active = in_array($plugin, $active_plugins);
    $status = $is_active ? 'ACTIVE' : 'INACTIVE';
    echo "$plugin: $status\n";
}

echo "\n=== Plugin Files Check ===\n";

foreach ($bkgt_plugins as $plugin) {
    $plugin_path = WP_PLUGIN_DIR . '/' . $plugin;
    $exists = file_exists($plugin_path);
    $status = $exists ? 'EXISTS' : 'MISSING';
    echo "$plugin: $status\n";
}
?>