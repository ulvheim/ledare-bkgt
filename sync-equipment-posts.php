<?php
/**
 * Sync Equipment Data to WordPress Posts
 * This script will create WordPress posts for existing inventory items
 * so the admin interface can display them properly.
 */

require_once('wp-load.php');

global $wpdb;

echo "<h1>Syncing Equipment Data to WordPress Posts</h1>";

// Get all inventory items from custom tables
$items = $wpdb->get_results("
    SELECT i.*, m.name as manufacturer_name, it.name as item_type_name
    FROM {$wpdb->prefix}bkgt_inventory_items i
    LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
    LEFT JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
    ORDER BY i.id
");

echo "<p>Found " . count($items) . " inventory items to sync.</p>";

$synced = 0;
$skipped = 0;

foreach ($items as $item) {
    // Check if WordPress post already exists for this item
    $existing_post = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'bkgt_inventory_item' AND post_title = %s",
        $item->unique_identifier
    ));

    if ($existing_post) {
        echo "<p>⚠️ Skipping item {$item->id} ({$item->title}) - WordPress post already exists (ID: {$existing_post})</p>";
        $skipped++;
        continue;
    }

    // Create WordPress post
    $post_data = array(
        'post_type' => 'bkgt_inventory_item',
        'post_title' => $item->unique_identifier,
        'post_content' => $item->notes ?: '',
        'post_status' => 'publish',
        'post_author' => 1, // Default to admin user
    );

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        echo "<p>❌ Failed to create post for item {$item->id}: " . $post_id->get_error_message() . "</p>";
        continue;
    }

    // Save meta data
    update_post_meta($post_id, '_bkgt_unique_id', $item->unique_identifier);
    update_post_meta($post_id, '_bkgt_unique_id_short', $item->unique_identifier); // For now, same as full
    update_post_meta($post_id, '_bkgt_manufacturer_id', $item->manufacturer_id);
    update_post_meta($post_id, '_bkgt_item_type_id', $item->item_type_id);
    update_post_meta($post_id, '_bkgt_purchase_date', $item->purchase_date);
    update_post_meta($post_id, '_bkgt_purchase_price', $item->purchase_price);
    update_post_meta($post_id, '_bkgt_warranty_expiry', $item->warranty_expiry);
    update_post_meta($post_id, '_bkgt_notes', $item->notes);
    update_post_meta($post_id, '_bkgt_storage_location', $item->storage_location);
    update_post_meta($post_id, '_bkgt_condition_status', $item->condition_status);
    update_post_meta($post_id, '_bkgt_sticker_code', $item->sticker_code);

    // Set condition taxonomy if condition exists
    if (!empty($item->condition_status)) {
        // Try to find or create condition term
        $condition_term = get_term_by('slug', $item->condition_status, 'bkgt_condition');
        if (!$condition_term) {
            $condition_term = wp_insert_term($item->condition_status, 'bkgt_condition');
            if (!is_wp_error($condition_term)) {
                wp_set_post_terms($post_id, $condition_term['term_id'], 'bkgt_condition');
            }
        } else {
            wp_set_post_terms($post_id, $condition_term->term_id, 'bkgt_condition');
        }
    }

    echo "<p>✅ Created WordPress post (ID: {$post_id}) for inventory item {$item->id} ({$item->title})</p>";
    $synced++;
}

echo "<h2>Sync Complete</h2>";
echo "<p>Synced: {$synced} items</p>";
echo "<p>Skipped: {$skipped} items (already existed)</p>";
echo "<p><a href='" . admin_url('edit.php?post_type=bkgt_inventory_item') . "'>View Equipment in Admin</a></p>";
?>