<?php
require_once '../../../wp-load.php';

$active_plugins = get_option('active_plugins');
echo "Active plugins:\n";
foreach ($active_plugins as $plugin) {
    echo "  - $plugin\n";
}
?>