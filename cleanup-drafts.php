<?php
require_once('wp-load.php');
global $wpdb;

// Delete auto-draft posts
$deleted = $wpdb->query("DELETE FROM {$wpdb->prefix}posts WHERE post_type = 'bkgt_inventory_item' AND post_status = 'auto-draft'");
echo "Deleted {$deleted} auto-draft posts\n";

// Check remaining posts
$posts = $wpdb->get_results("SELECT ID, post_title, post_status FROM {$wpdb->prefix}posts WHERE post_type = 'bkgt_inventory_item' ORDER BY ID");
echo 'Remaining Equipment Posts:\n';
foreach ($posts as $post) {
    echo "Post ID {$post->ID}: '{$post->post_title}' (Status: {$post->post_status})\n";
}
?>