<?php
require_once('wp-load.php');
global $wpdb;

$posts = $wpdb->get_results("SELECT ID, post_title, post_type, post_status FROM $wpdb->posts WHERE post_type = 'bkgt_inventory_item' ORDER BY ID");
echo "ALL BKGT_INVENTORY_ITEM POSTS:\n";
foreach($posts as $post) {
    echo $post->ID . ': ' . $post->post_title . ' (' . $post->post_status . ")\n";

    // Check meta data
    $manufacturer_id = get_post_meta($post->ID, '_bkgt_manufacturer_id', true);
    $item_type_id = get_post_meta($post->ID, '_bkgt_item_type_id', true);
    $unique_id = get_post_meta($post->ID, '_bkgt_unique_id', true);

    echo "  Manufacturer ID: $manufacturer_id\n";
    echo "  Item Type ID: $item_type_id\n";
    echo "  Unique ID: $unique_id\n";
    echo "  ---\n";
}
?>