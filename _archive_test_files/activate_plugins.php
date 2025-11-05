<?php
require_once('wp-load.php');

echo "Activating missing plugins...\n\n";

$plugins_to_activate = array(
    'bkgt-offboarding/bkgt-offboarding.php',
    'bkgt-team-player/bkgt-team-player.php'
);

foreach ($plugins_to_activate as $plugin) {
    echo "Activating $plugin... ";
    $result = activate_plugin($plugin);
    if (is_wp_error($result)) {
        echo "❌ FAILED: " . $result->get_error_message() . "\n";
    } else {
        echo "✅ SUCCESS\n";
    }
}

echo "\n=== Active BKGT Plugins After Activation ===\n";
$active_plugins = get_option('active_plugins');
foreach($active_plugins as $p) {
    if(strpos($p, 'bkgt') !== false) {
        echo "- $p\n";
    }
}
?>