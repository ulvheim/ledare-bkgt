<?php
require_once('wp-load.php');

$active_plugins = get_option('active_plugins');
echo "Active plugins:\n";
foreach ($active_plugins as $plugin) {
    echo "- $plugin\n";
}

echo "\nChecking if bkgt-team-player is active: " . (in_array('bkgt-team-player/bkgt-team-player.php', $active_plugins) ? 'YES' : 'NO') . "\n";
echo "\nChecking if bkgt-inventory is active: " . (in_array('bkgt-inventory/bkgt-inventory.php', $active_plugins) ? 'YES' : 'NO') . "\n";
?>