<?php
require_once('wp-load.php');
global $wpdb;

$items = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'bkgt_inventory_items ORDER BY id');
echo "DETAILED INVENTORY ITEMS:\n";
foreach($items as $item) {
    echo 'ID: ' . $item->id . ', Title: ' . $item->title . ', Identifier: ' . $item->unique_identifier . ', Created: ' . $item->created_at . "\n";
}

// Check if any of these items have corresponding WordPress posts
echo "\nCHECKING FOR CORRESPONDING POSTS:\n";
foreach($items as $item) {
    $post = $wpdb->get_row($wpdb->prepare("SELECT ID, post_title FROM $wpdb->posts WHERE post_title = %s", $item->unique_identifier));
    if ($post) {
        echo "Item {$item->id} ({$item->unique_identifier}) has corresponding post: {$post->ID}\n";
    }
}
?>