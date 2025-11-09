<?php
require_once('wp-load.php');
global $wpdb;

// Check all posts of this type
$posts = $wpdb->get_results("SELECT ID, post_title, post_status FROM {$wpdb->prefix}posts WHERE post_type = 'bkgt_inventory_item' ORDER BY ID");

echo 'All Equipment Posts:\n';
foreach ($posts as $post) {
    echo "Post ID {$post->ID}: '{$post->post_title}' (Status: {$post->post_status})\n";
}
?>