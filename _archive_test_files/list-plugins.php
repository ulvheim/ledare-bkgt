<?php
require_once('wp-load.php');
$plugins = get_option('active_plugins');
echo "Active plugins:\n";
foreach($plugins as $plugin) {
    echo "- $plugin\n";
}
?>