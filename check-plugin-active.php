<?php
require_once '../../../wp-load.php';

$active_plugins = get_option('active_plugins');
$is_active = in_array('bkgt-swe3-scraper/bkgt-swe3-scraper.php', $active_plugins);

echo "Plugin active: " . ($is_active ? 'yes' : 'no') . "\n";

if ($is_active) {
    echo "Active plugins:\n";
    foreach ($active_plugins as $plugin) {
        echo "  - $plugin\n";
    }
}
?>