<?php
require_once('wp-load.php');

$active_plugins = get_option('active_plugins');

// Add the bkgt-team-player plugin to active plugins
if (!in_array('bkgt-team-player/bkgt-team-player.php', $active_plugins)) {
    $active_plugins[] = 'bkgt-team-player/bkgt-team-player.php';
    update_option('active_plugins', $active_plugins);
    echo "Activated bkgt-team-player plugin\n";
} else {
    echo "bkgt-team-player plugin was already active\n";
}

// Verify
$active_plugins = get_option('active_plugins');
echo "Plugin is now active: " . (in_array('bkgt-team-player/bkgt-team-player.php', $active_plugins) ? 'YES' : 'NO') . "\n";
?>