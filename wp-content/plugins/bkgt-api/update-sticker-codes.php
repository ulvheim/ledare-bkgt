<?php
/**
 * Update existing equipment items to have sticker codes
 * Run this script once to populate sticker codes for existing items
 */

if (!defined('ABSPATH')) {
    // Define WordPress environment
    define('ABSPATH', dirname(__FILE__) . '/../../../');
}

require_once ABSPATH . 'wp-load.php';

if (!current_user_can('manage_options')) {
    die('Access denied');
}

global $wpdb;

echo "<h1>Updating Equipment Sticker Codes</h1>";

// Get all equipment items that don't have sticker codes
$items = $wpdb->get_results("
    SELECT id, unique_identifier, sticker_code
    FROM {$wpdb->prefix}bkgt_inventory_items
    WHERE sticker_code IS NULL OR sticker_code = ''
");

echo "<p>Found " . count($items) . " items without sticker codes</p>";

$updated = 0;
foreach ($items as $item) {
    // Generate sticker code from unique identifier
    $parts = explode('-', $item->unique_identifier);
    if (count($parts) === 3) {
        $manufacturer = intval($parts[0]);
        $item_type = intval($parts[1]);
        $sequential = intval($parts[2]);
        $sticker_code = sprintf('%d-%d-%d', $manufacturer, $item_type, $sequential);

        // Update the item
        $result = $wpdb->update(
            $wpdb->prefix . 'bkgt_inventory_items',
            array('sticker_code' => $sticker_code),
            array('id' => $item->id)
        );

        if ($result !== false) {
            $updated++;
            echo "<p>Updated item {$item->id}: {$item->unique_identifier} → {$sticker_code}</p>";
        } else {
            echo "<p>Failed to update item {$item->id}</p>";
        }
    } else {
        echo "<p>Invalid unique identifier format for item {$item->id}: {$item->unique_identifier}</p>";
    }
}

echo "<p><strong>Updated {$updated} items with sticker codes</strong></p>";
echo "<p><a href='" . admin_url() . "'>Return to WordPress Admin</a></p>";
?></content>
<parameter name="filePath">c:\Users\Olheim\Desktop\GH\ledare-bkgt\wp-content\plugins\bkgt-api\update-sticker-codes.php