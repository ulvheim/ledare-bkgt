<?php
require_once('wp-load.php');

// Get all plugins (active and inactive)
$all_plugins = get_plugins();

echo "All installed plugins:\n";
echo "======================\n";

$data_scraping_plugins = array();

foreach ($all_plugins as $plugin_file => $plugin_data) {
    echo "- " . $plugin_data['Name'] . " (" . $plugin_file . ")\n";

    // Check if this plugin is related to data scraping
    $name_lower = strtolower($plugin_data['Name']);
    $description_lower = strtolower($plugin_data['Description']);

    if (strpos($name_lower, 'scraping') !== false ||
        strpos($name_lower, 'data') !== false && strpos($name_lower, 'scraping') !== false ||
        strpos($description_lower, 'scraping') !== false) {
        $data_scraping_plugins[] = $plugin_file;
    }
}

echo "\nData scraping related plugins:\n";
echo "===============================\n";

if (empty($data_scraping_plugins)) {
    echo "None found\n";
} else {
    foreach ($data_scraping_plugins as $plugin) {
        $plugin_data = $all_plugins[$plugin];
        echo "- " . $plugin_data['Name'] . " (" . $plugin . ")\n";
        echo "  Description: " . $plugin_data['Description'] . "\n";
    }
}

// Check active plugins
$active_plugins = get_option('active_plugins');
echo "\nActive plugins:\n";
echo "===============\n";

foreach ($active_plugins as $plugin_file) {
    if (isset($all_plugins[$plugin_file])) {
        echo "- " . $all_plugins[$plugin_file]['Name'] . " (" . $plugin_file . ")\n";
    }
}
?>