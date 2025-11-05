<?php
require_once('wp-load.php');

$plugins = get_plugins();
$data_scraping_count = 0;

foreach($plugins as $file => $data) {
    if(strpos(strtolower($data['Name']), 'data scraping') !== false) {
        $data_scraping_count++;
        echo $data['Name'] . ' (' . $file . ")\n";
    }
}

echo "Total data scraping plugins: $data_scraping_count\n";
?>