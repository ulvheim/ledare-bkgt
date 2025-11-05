<?php
require_once('wp-load.php');

echo "Active BKGT Plugins:\n";
$plugins = get_option('active_plugins');
foreach($plugins as $p) {
    if(strpos($p, 'bkgt') !== false) {
        echo "- $p\n";
    }
}
?>