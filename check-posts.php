<?php
require_once('wp-load.php');
global $wpdb;

// Check the actual posts
$posts = $wpdb->get_results("SELECT ID, post_title, post_status FROM {$wpdb->prefix}posts WHERE post_type = 'bkgt_inventory_item' AND post_status = 'publish' ORDER BY ID");

echo 'Published Equipment Posts:\n';
foreach ($posts as $post) {
    $unique_id = get_post_meta($post->ID, '_bkgt_unique_id', true);
    $manufacturer = get_post_meta($post->ID, '_bkgt_manufacturer_id', true);
    $item_type = get_post_meta($post->ID, '_bkgt_item_type_id', true);
    echo "Post ID {$post->ID}: {$post->post_title} (Unique: {$unique_id}, Mfg: {$manufacturer}, Type: {$item_type})\n";
}
?>