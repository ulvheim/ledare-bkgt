<?php
require_once('wp-load.php');
global $wpdb;

// Check posts table
$posts = $wpdb->get_results("SELECT ID, post_title, post_type FROM {$wpdb->prefix}posts WHERE post_type = 'bkgt_inventory_item' LIMIT 5");
echo "Posts table (bkgt_inventory_item):\n";
foreach ($posts as $post) {
    echo "ID {$post->ID}: {$post->post_title}\n";
}

// Check custom table
$items = $wpdb->get_results("SELECT id, title, unique_identifier FROM {$wpdb->prefix}bkgt_inventory_items LIMIT 5");
echo "\nCustom table (bkgt_inventory_items):\n";
foreach ($items as $item) {
    echo "ID {$item->id}: {$item->title} - {$item->unique_identifier}\n";
}
?>