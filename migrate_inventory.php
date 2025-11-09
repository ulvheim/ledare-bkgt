<?php
/**
 * Migration Script: WordPress Posts to Database Tables
 * Run this script to migrate old inventory data from WordPress posts to custom database tables
 */

require_once('wp-load.php');

global $wpdb;

echo "=== BKGT INVENTORY MIGRATION SCRIPT ===\n\n";

// Check for existing WordPress posts
$posts = get_posts(array(
    'post_type' => 'bkgt_inventory_item',
    'posts_per_page' => -1,
    'post_status' => 'publish'
));

echo "Found " . count($posts) . " bkgt_inventory_item posts to migrate\n\n";

if (empty($posts)) {
    echo "No posts to migrate. Exiting.\n";
    exit;
}

$migrated = 0;
$skipped = 0;
$errors = 0;

foreach ($posts as $post) {
    echo "Processing post ID: {$post->ID} ({$post->post_title})\n";

    // Get post meta - check both possible keys
    $manufacturer_id = get_post_meta($post->ID, '_bkgt_manufacturer_id', true);
    $item_type_id = get_post_meta($post->ID, '_bkgt_item_type_id', true);
    $unique_identifier = get_post_meta($post->ID, '_bkgt_unique_identifier', true);
    if (!$unique_identifier) {
        $unique_identifier = get_post_meta($post->ID, '_bkgt_unique_id', true);
    }
    $storage_location = get_post_meta($post->ID, '_bkgt_storage_location', true);
    $condition_status = get_post_meta($post->ID, '_bkgt_condition_status', true);
    $sticker_code = get_post_meta($post->ID, '_bkgt_sticker_code', true);

    // Validate required fields
    if (!$manufacturer_id || !$item_type_id || !$unique_identifier) {
        echo "  ❌ Skipping - missing required fields (manufacturer_id, item_type_id, or unique_identifier)\n";
        $skipped++;
        continue;
    }

    // Check if manufacturer exists
    $manufacturer_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}bkgt_manufacturers WHERE id = %d",
        $manufacturer_id
    ));

    if (!$manufacturer_exists) {
        echo "  ❌ Skipping - manufacturer ID $manufacturer_id does not exist\n";
        $skipped++;
        continue;
    }

    // Check if item type exists
    $item_type_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}bkgt_item_types WHERE id = %d",
        $item_type_id
    ));

    if (!$item_type_exists) {
        echo "  ❌ Skipping - item type ID $item_type_id does not exist\n";
        $skipped++;
        continue;
    }

    // Check if item already exists (by unique identifier)
    $existing_item = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}bkgt_inventory_items WHERE unique_identifier = %s",
        $unique_identifier
    ));

    if ($existing_item) {
        echo "  ⚠️  Skipping - item with identifier $unique_identifier already exists\n";
        $skipped++;
        continue;
    }

    // Insert into database table
    $result = $wpdb->insert(
        $wpdb->prefix . 'bkgt_inventory_items',
        array(
            'unique_identifier' => $unique_identifier,
            'manufacturer_id' => $manufacturer_id,
            'item_type_id' => $item_type_id,
            'title' => $post->post_title,
            'storage_location' => $storage_location ?: '',
            'condition_status' => $condition_status ?: 'normal',
            'sticker_code' => $sticker_code ?: '',
            'created_at' => $post->post_date,
            'updated_at' => $post->post_modified,
        ),
        array('%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    if ($result === false) {
        echo "  ❌ Error inserting item: " . $wpdb->last_error . "\n";
        $errors++;
    } else {
        echo "  ✅ Successfully migrated item (new ID: {$wpdb->insert_id})\n";
        $migrated++;

        // Optionally mark post as migrated or delete it
        // update_post_meta($post->ID, '_bkgt_migrated', 'yes');
        // wp_delete_post($post->ID, true);
    }
}

echo "\n=== MIGRATION SUMMARY ===\n";
echo "Total posts found: " . count($posts) . "\n";
echo "Successfully migrated: $migrated\n";
echo "Skipped: $skipped\n";
echo "Errors: $errors\n";

if ($migrated > 0) {
    echo "\n✅ Migration completed! Check the API diagnostic to verify the migrated items.\n";
} else {
    echo "\n❌ No items were migrated. Check the error messages above.\n";
}