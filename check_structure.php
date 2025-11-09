<?php
require_once('wp-load.php');
global $wpdb;

$result = $wpdb->get_results('DESCRIBE ' . $wpdb->prefix . 'bkgt_manufacturers');
echo 'MANUFACTURERS TABLE:\n';
foreach($result as $row) {
    echo $row->Field . ' - ' . $row->Type . ' - ' . $row->Key . '\n';
}

echo '\nITEM TYPES TABLE:\n';
$result = $wpdb->get_results('DESCRIBE ' . $wpdb->prefix . 'bkgt_item_types');
foreach($result as $row) {
    echo $row->Field . ' - ' . $row->Type . ' - ' . $row->Key . '\n';
}
?>