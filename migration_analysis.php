<?php
require_once('wp-load.php');

global $wpdb;

echo "=== MIGRATION ANALYSIS: WordPress Posts to Database Tables ===\n\n";

// Check for existing WordPress posts
echo "1. CHECKING WORDPRESS POSTS:\n";
$posts = get_posts(array(
    'post_type' => 'bkgt_inventory_item',
    'posts_per_page' => -1,
    'post_status' => 'any'
));

echo "Found " . count($posts) . " bkgt_inventory_item posts\n\n";

if (!empty($posts)) {
    echo "POST DETAILS:\n";
    foreach ($posts as $post) {
        echo "Post ID: {$post->ID}, Title: {$post->post_title}, Status: {$post->post_status}\n";

        // Get post meta
        $manufacturer_id = get_post_meta($post->ID, '_bkgt_manufacturer_id', true);
        $item_type_id = get_post_meta($post->ID, '_bkgt_item_type_id', true);
        $unique_identifier = get_post_meta($post->ID, '_bkgt_unique_identifier', true);
        $storage_location = get_post_meta($post->ID, '_bkgt_storage_location', true);
        $condition_status = get_post_meta($post->ID, '_bkgt_condition_status', true);
        $sticker_code = get_post_meta($post->ID, '_bkgt_sticker_code', true);

        echo "  Manufacturer ID: $manufacturer_id\n";
        echo "  Item Type ID: $item_type_id\n";
        echo "  Unique Identifier: $unique_identifier\n";
        echo "  Storage Location: $storage_location\n";
        echo "  Condition Status: $condition_status\n";
        echo "  Sticker Code: $sticker_code\n";
        echo "  ---\n";
    }
}

// Check current database tables
echo "\n2. CHECKING DATABASE TABLES:\n";

$manufacturers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkgt_manufacturers ORDER BY id");
echo "Manufacturers table: " . count($manufacturers) . " records\n";

$item_types = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkgt_item_types ORDER BY id");
echo "Item types table: " . count($item_types) . " records\n";

$inventory_items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkgt_inventory_items ORDER BY id");
echo "Inventory items table: " . count($inventory_items) . " records\n";

$assignments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkgt_inventory_assignments ORDER BY id");
echo "Assignments table: " . count($assignments) . " records\n";

// Check if migration is possible
echo "\n3. MIGRATION FEASIBILITY:\n";

if (empty($posts)) {
    echo "❌ No WordPress posts found to migrate\n";
} else {
    echo "✅ Found " . count($posts) . " posts that could be migrated\n";

    // Check if manufacturers and item types exist for the posts
    $missing_manufacturers = 0;
    $missing_item_types = 0;

    foreach ($posts as $post) {
        $manufacturer_id = get_post_meta($post->ID, '_bkgt_manufacturer_id', true);
        $item_type_id = get_post_meta($post->ID, '_bkgt_item_type_id', true);

        // Check if manufacturer exists in new table
        if ($manufacturer_id) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}bkgt_manufacturers WHERE id = %d",
                $manufacturer_id
            ));
            if (!$exists) $missing_manufacturers++;
        }

        // Check if item type exists in new table
        if ($item_type_id) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}bkgt_item_types WHERE id = %d",
                $item_type_id
            ));
            if (!$exists) $missing_item_types++;
        }
    }

    if ($missing_manufacturers > 0) {
        echo "⚠️  $missing_manufacturers posts have manufacturers not in new table\n";
    }
    if ($missing_item_types > 0) {
        echo "⚠️  $missing_item_types posts have item types not in new table\n";
    }

    if ($missing_manufacturers == 0 && $missing_item_types == 0) {
        echo "✅ All posts can be migrated (manufacturers and item types exist)\n";
    }
}

echo "\n4. MIGRATION PLAN:\n";
if (!empty($posts)) {
    echo "Steps to migrate:\n";
    echo "1. Backup current database\n";
    echo "2. Create manufacturers/item types if missing\n";
    echo "3. Migrate posts to bkgt_inventory_items table\n";
    echo "4. Migrate assignments if any\n";
    echo "5. Update any references\n";
    echo "6. Test API endpoints\n";
    echo "7. Clean up old posts (optional)\n";
} else {
    echo "No migration needed - no old posts found\n";
}