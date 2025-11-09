<?php
require_once('wp-load.php');
global $wpdb;

$items = $wpdb->get_results("SELECT id, title, manufacturer_id, item_type_id, unique_identifier FROM {$wpdb->prefix}bkgt_inventory_items WHERE manufacturer_id = 5 AND item_type_id = 5 ORDER BY id");

echo "Wilson Footballs:\n";
foreach ($items as $item) {
    echo "ID {$item->id}: {$item->title} - {$item->unique_identifier}\n";
}
?>