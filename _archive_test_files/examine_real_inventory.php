<?php
require_once('wp-load.php');

echo "Examining the real inventory item (Post ID 100)...\n\n";

$post = get_post(100);
if ($post) {
    echo "Post Title: " . $post->post_title . "\n";
    echo "Post Content: " . $post->post_content . "\n";
    echo "Post Type: " . $post->post_type . "\n\n";
    
    $meta = get_post_meta(100);
    echo "All metadata:\n";
    foreach ($meta as $key => $value) {
        echo "  $key: " . $value[0] . "\n";
    }
} else {
    echo "Post 100 not found\n";
}

echo "\nChecking how many real inventory items exist...\n";
$real_items = get_posts(array(
    'post_type' => 'bkgt_inventory_item',
    'post_status' => 'publish',
    'numberposts' => -1
));

echo "Published bkgt_inventory_item posts: " . count($real_items) . "\n";

foreach ($real_items as $item) {
    $unique_id = get_post_meta($item->ID, '_bkgt_unique_id', true);
    echo "  {$item->ID}: {$item->post_title} (ID: $unique_id)\n";
}
?>