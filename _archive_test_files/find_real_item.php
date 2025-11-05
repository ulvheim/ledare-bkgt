<?php
require_once('wp-load.php');
global $wpdb;

// Check for the real item identifier
echo "Searching for the real item '0005-0005-00001'...\n";

// Check in inventory items table
$inventory_db = new BKGT_Inventory_Database();
$real_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $inventory_db->get_inventory_items_table() . " WHERE unique_identifier = %s", "0005-0005-00001"));

if ($real_item) {
    echo "✅ Found in inventory_items table: " . $real_item->title . "\n";
} else {
    echo "❌ Not found in inventory_items table\n";
}

// Check in postmeta (for custom post types)
$meta_results = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_key, meta_value FROM $wpdb->postmeta WHERE meta_value LIKE %s", "%0005-0005-00001%"));
if (!empty($meta_results)) {
    echo "✅ Found in postmeta:\n";
    foreach ($meta_results as $meta) {
        echo "  Post ID: {$meta->post_id}, Key: {$meta->meta_key}, Value: {$meta->meta_value}\n";
    }
} else {
    echo "❌ Not found in postmeta\n";
}

// Check in posts content
$content_results = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM $wpdb->posts WHERE post_content LIKE %s", "%0005-0005-00001%"));
if (!empty($content_results)) {
    echo "✅ Found in post content:\n";
    foreach ($content_results as $post) {
        echo "  Post ID: {$post->ID}, Title: {$post->post_title}\n";
    }
} else {
    echo "❌ Not found in post content\n";
}

// Check custom post types
$custom_posts = $wpdb->get_results("SELECT ID, post_title, post_type FROM $wpdb->posts WHERE post_type = 'bkgt_inventory_item'");
echo "\nCustom post type 'bkgt_inventory_item' items: " . count($custom_posts) . "\n";
foreach ($custom_posts as $post) {
    echo "  {$post->ID} - {$post->post_title} ({$post->post_type})\n";
    
    // Check meta for this post
    $meta = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d", $post->ID));
    foreach ($meta as $m) {
        if (strpos($m->meta_value, '0005') !== false) {
            echo "    Meta: {$m->meta_key} = {$m->meta_value}\n";
        }
    }
}
?>