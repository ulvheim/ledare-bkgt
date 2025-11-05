<?php
require_once('wp-load.php');

echo "=== Populating Sample Inventory Data ===\n\n";

global $wpdb;
$prefix = $wpdb->prefix;

// Get manufacturer and item type IDs
$manufacturers = $wpdb->get_results("SELECT id, manufacturer_id FROM {$prefix}bkgt_manufacturers", ARRAY_A);
$item_types = $wpdb->get_results("SELECT id, item_type_id FROM {$prefix}bkgt_item_types", ARRAY_A);

if (empty($manufacturers) || empty($item_types)) {
    echo "❌ No manufacturers or item types found. Please run fix_inventory_tables.php first.\n";
    exit(1);
}

echo "Found " . count($manufacturers) . " manufacturers and " . count($item_types) . " item types\n\n";

// Sample inventory items - using the actual codes from database
$sample_items = array(
    array('HELM-001', 'NIKE', 'HELM', 'Football Helmet - Adult Large', 'Storage Room A'),
    array('HELM-002', 'SCHT', 'HELM', 'Football Helmet - Adult Medium', 'Storage Room A'),
    array('HELM-003', 'RIDL', 'HELM', 'Football Helmet - Youth Large', 'Storage Room B'),
    array('SHLD-001', 'NIKE', 'SHLD', 'Shoulder Pads - Adult Large', 'Storage Room A'),
    array('SHLD-002', 'UA', 'SHLD', 'Shoulder Pads - Adult Medium', 'Storage Room A'),
    array('SHLD-003', 'SCHT', 'SHLD', 'Shoulder Pads - Youth Large', 'Storage Room B'),
    array('SHRT-001', 'NIKE', 'SHRT', 'Practice Jersey - Blue', 'Jersey Locker'),
    array('SHRT-002', 'NIKE', 'SHRT', 'Practice Jersey - Red', 'Jersey Locker'),
    array('SHRT-003', 'UA', 'SHRT', 'Game Jersey - Home', 'Jersey Locker'),
    array('PANT-001', 'NIKE', 'PANT', 'Football Pants - Adult Large', 'Pants Locker'),
    array('PANT-002', 'NIKE', 'PANT', 'Football Pants - Adult Medium', 'Pants Locker'),
    array('PANT-003', 'UA', 'PANT', 'Football Pants - Youth Large', 'Pants Locker'),
    array('SHOE-001', 'NIKE', 'SHOE', 'Cleats - Size 10', 'Shoe Room'),
    array('SHOE-002', 'NIKE', 'SHOE', 'Cleats - Size 9', 'Shoe Room'),
    array('SHOE-003', 'UA', 'SHOE', 'Cleats - Size 11', 'Shoe Room'),
);

$inserted_count = 0;
foreach ($sample_items as $item) {
    list($unique_id, $manufacturer_name, $item_type_code, $title, $location) = $item;

    // Find manufacturer ID
    $manufacturer_id = 0;
    foreach ($manufacturers as $manuf) {
        if ($manuf['manufacturer_id'] === $manufacturer_name) {
            $manufacturer_id = $manuf['id'];
            break;
        }
    }

    // Find item type ID
    $item_type_id = 0;
    foreach ($item_types as $type) {
        if ($type['item_type_id'] === $item_type_code) {
            $item_type_id = $type['id'];
            break;
        }
    }

    if ($manufacturer_id == 0 || $item_type_id == 0) {
        echo "❌ Could not find IDs for $manufacturer_name / $item_type_code\n";
        continue;
    }

    // Check if item already exists
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$prefix}bkgt_inventory_items WHERE unique_identifier = %s",
        $unique_id
    ));

    if ($exists) {
        echo "⚠️  $unique_id already exists, skipping\n";
        continue;
    }

    // Insert the item
    $result = $wpdb->insert(
        "{$prefix}bkgt_inventory_items",
        array(
            'unique_identifier' => $unique_id,
            'manufacturer_id' => $manufacturer_id,
            'item_type_id' => $item_type_id,
            'title' => $title,
            'storage_location' => $location,
            'condition_status' => 'normal'
        )
    );

    if ($result) {
        echo "✅ Added: $unique_id - $title\n";
        $inserted_count++;
    } else {
        echo "❌ Failed to add: $unique_id - " . $wpdb->last_error . "\n";
    }
}

echo "\n=== Summary ===\n";
echo "Inserted $inserted_count new inventory items\n";

$total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}bkgt_inventory_items");
echo "Total inventory items in database: $total_items\n";

// Test the shortcode again
echo "\n=== Testing Shortcode with Real Data ===\n";
$shortcode_result = do_shortcode('[bkgt_inventory]');
if (strpos($shortcode_result, 'wpdberror') !== false) {
    echo "❌ Still has database errors\n";
} elseif (strpos($shortcode_result, 'Sample Equipment Data') !== false) {
    echo "⚠️  Still showing sample data fallback\n";
} else {
    echo "✅ Shortcode working with real data\n";
    echo "Shortcode output length: " . strlen($shortcode_result) . " characters\n";
}