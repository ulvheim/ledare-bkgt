<?php
require_once('wp-load.php');

echo "Debugging the shortcode query...\n\n";

// Test the get_posts query
$args = array(
    'post_type' => 'bkgt_inventory_item',
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby' => 'date',
    'order' => 'DESC'
);

$inventory_posts = get_posts($args);
echo "get_posts found: " . count($inventory_posts) . " posts\n\n";

foreach ($inventory_posts as $post) {
    echo "Post ID: {$post->ID}, Title: {$post->post_title}, Status: {$post->post_status}\n";
    $unique_id = get_post_meta($post->ID, '_bkgt_unique_id', true);
    echo "  Unique ID: $unique_id\n";
    $manufacturer_id = get_post_meta($post->ID, '_bkgt_manufacturer_id', true);
    $item_type_id = get_post_meta($post->ID, '_bkgt_item_type_id', true);
    echo "  Manufacturer ID: $manufacturer_id, Item Type ID: $item_type_id\n\n";
}

// Also check all posts of this type
echo "All bkgt_inventory_item posts:\n";
$all_posts = get_posts(array(
    'post_type' => 'bkgt_inventory_item',
    'post_status' => 'any',
    'numberposts' => -1
));

foreach ($all_posts as $post) {
    echo "  {$post->ID}: {$post->post_title} [{$post->post_status}]\n";
}
?>