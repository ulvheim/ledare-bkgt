<?php
/**
 * Sync Database Items to WordPress Posts
 *
 * This script ensures that all items in the bkgt_inventory_items table
 * have corresponding WordPress posts for admin interface compatibility.
 */

require_once('wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

global $wpdb;
$bkgt_inventory_db = new BKGT_Inventory_Database();
$table = $bkgt_inventory_db->get_inventory_items_table();

// Get all items from database
$items = $wpdb->get_results("SELECT * FROM {$table}", ARRAY_A);

echo "<h1>Sync Database Items to WordPress Posts</h1>";
echo "<p>Found " . count($items) . " items in database.</p>";

$created = 0;
$updated = 0;
$errors = 0;

foreach ($items as $item) {
    // Check if WordPress post already exists
    $existing_posts = get_posts(array(
        'post_type' => 'bkgt_inventory_item',
        'meta_key' => '_bkgt_unique_identifier',
        'meta_value' => $item['unique_identifier'],
        'posts_per_page' => 1,
        'fields' => 'ids'
    ));

    if (empty($existing_posts)) {
        // Create new WordPress post
        $post_data = array(
            'post_type' => 'bkgt_inventory_item',
            'post_title' => $item['unique_identifier'],
            'post_status' => 'publish',
            'post_date' => $item['created_at'],
            'post_date_gmt' => get_gmt_from_date($item['created_at']),
        );

        $post_id = wp_insert_post($post_data);

        if (!is_wp_error($post_id)) {
            // Save meta data
            update_post_meta($post_id, '_bkgt_unique_identifier', $item['unique_identifier']);
            update_post_meta($post_id, '_bkgt_manufacturer_id', $item['manufacturer_id']);
            update_post_meta($post_id, '_bkgt_item_type_id', $item['item_type_id']);

            // Save additional fields
            if (!empty($item['size'])) {
                update_post_meta($post_id, '_bkgt_size', $item['size']);
            }
            if (!empty($item['sticker_code'])) {
                update_post_meta($post_id, '_bkgt_sticker_code', $item['sticker_code']);
            }
            if (!empty($item['purchase_date'])) {
                update_post_meta($post_id, '_bkgt_purchase_date', $item['purchase_date']);
            }
            if (!empty($item['purchase_price'])) {
                update_post_meta($post_id, '_bkgt_purchase_price', $item['purchase_price']);
            }
            if (!empty($item['warranty_expiry'])) {
                update_post_meta($post_id, '_bkgt_warranty_expiry', $item['warranty_expiry']);
            }
            if (!empty($item['location_id'])) {
                update_post_meta($post_id, '_bkgt_location_id', $item['location_id']);
            }
            if (!empty($item['notes'])) {
                update_post_meta($post_id, '_bkgt_notes', $item['notes']);
            }

            echo "<p>✓ Created post for item: {$item['unique_identifier']} (Post ID: {$post_id})</p>";
            $created++;
        } else {
            echo "<p>✗ Failed to create post for item: {$item['unique_identifier']} - " . $post_id->get_error_message() . "</p>";
            $errors++;
        }
    } else {
        // Update existing post with any missing meta data
        $post_id = $existing_posts[0];

        $updated_meta = false;

        // Check and update missing meta
        if (empty(get_post_meta($post_id, '_bkgt_size', true)) && !empty($item['size'])) {
            update_post_meta($post_id, '_bkgt_size', $item['size']);
            $updated_meta = true;
        }
        if (empty(get_post_meta($post_id, '_bkgt_sticker_code', true)) && !empty($item['sticker_code'])) {
            update_post_meta($post_id, '_bkgt_sticker_code', $item['sticker_code']);
            $updated_meta = true;
        }
        if (empty(get_post_meta($post_id, '_bkgt_purchase_date', true)) && !empty($item['purchase_date'])) {
            update_post_meta($post_id, '_bkgt_purchase_date', $item['purchase_date']);
            $updated_meta = true;
        }
        if (empty(get_post_meta($post_id, '_bkgt_purchase_price', true)) && !empty($item['purchase_price'])) {
            update_post_meta($post_id, '_bkgt_purchase_price', $item['purchase_price']);
            $updated_meta = true;
        }
        if (empty(get_post_meta($post_id, '_bkgt_warranty_expiry', true)) && !empty($item['warranty_expiry'])) {
            update_post_meta($post_id, '_bkgt_warranty_expiry', $item['warranty_expiry']);
            $updated_meta = true;
        }
        if (empty(get_post_meta($post_id, '_bkgt_location_id', true)) && !empty($item['location_id'])) {
            update_post_meta($post_id, '_bkgt_location_id', $item['location_id']);
            $updated_meta = true;
        }
        if (empty(get_post_meta($post_id, '_bkgt_notes', true)) && !empty($item['notes'])) {
            update_post_meta($post_id, '_bkgt_notes', $item['notes']);
            $updated_meta = true;
        }

        if ($updated_meta) {
            echo "<p>✓ Updated meta for existing post: {$item['unique_identifier']} (Post ID: {$post_id})</p>";
            $updated++;
        }
    }
}

echo "<h2>Summary</h2>";
echo "<p>Posts created: {$created}</p>";
echo "<p>Posts updated: {$updated}</p>";
echo "<p>Errors: {$errors}</p>";
echo "<p><a href='" . admin_url('edit.php?post_type=bkgt_inventory_item') . "'>View Inventory Items</a></p>";
?>