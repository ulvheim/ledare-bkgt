<?php
require_once('wp-load.php');
global $wpdb;

$posts = $wpdb->get_results("SELECT ID, post_title, post_type, post_status FROM $wpdb->posts WHERE post_type NOT IN ('post', 'page', 'attachment', 'revision', 'nav_menu_item') AND post_status = 'publish' ORDER BY post_type, ID");

echo "ALL CUSTOM POST TYPES:\n";
foreach($posts as $post) {
    echo $post->post_type . ': ' . $post->ID . ' - ' . $post->post_title . "\n";
}

echo "\nPOST META FOR NON-STANDARD POSTS:\n";
foreach($posts as $post) {
    if ($post->post_type !== 'bkgt_inventory_item') {
        $meta = get_post_meta($post->ID, '_bkgt_unique_id', true);
        if ($meta) {
            echo "Post {$post->ID} ({$post->post_type}): _bkgt_unique_id = {$meta}\n";
        }
    }
}
?>