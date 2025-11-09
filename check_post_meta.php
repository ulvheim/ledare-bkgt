<?php
require_once('wp-load.php');
global $wpdb;

// Check post meta for the inventory item post
$post_id = 100;
echo "POST META FOR POST ID $post_id:\n";

$meta_keys = array(
    'manufacturer_id',
    'item_type_id',
    'unique_identifier',
    'storage_location',
    'condition_status',
    'sticker_code',
    '_bkgt_manufacturer_id',
    '_bkgt_item_type_id',
    '_bkgt_unique_identifier',
    '_bkgt_storage_location',
    '_bkgt_condition_status',
    '_bkgt_sticker_code'
);

foreach ($meta_keys as $key) {
    $value = get_post_meta($post_id, $key, true);
    echo "$key: '" . ($value ?: 'EMPTY') . "'\n";
}

echo "\nPOST TITLE: " . get_the_title($post_id) . "\n";
echo "POST STATUS: " . get_post_status($post_id) . "\n";

// Also check all post meta for this post
echo "\nALL POST META:\n";
$all_meta = get_post_meta($post_id);
if (empty($all_meta)) {
    echo "No post meta found at all\n";
} else {
    foreach ($all_meta as $key => $values) {
        echo "$key: " . implode(', ', $values) . "\n";
    }
}
?>