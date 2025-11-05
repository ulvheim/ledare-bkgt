<?php
require_once('wp-load.php');

global $menu;
echo "Checking admin menu for Documents...\n";

foreach($menu as $item) {
    if(strpos($item[0], 'Documents') !== false) {
        echo 'Found: ' . $item[0] . "\n";
    }
}

echo "Menu check complete.\n";
?>