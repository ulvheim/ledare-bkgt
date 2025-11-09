<?php
require_once('wp-load.php');
global $wpdb;

// Add missing manufacturers (IDs 2, 3, 4, 5)
$missing_manufacturers = array(2, 3, 4, 5);
$manufacturer_names = array(
    2 => 'Adidas',
    3 => 'Puma',
    4 => 'Reebok',
    5 => 'Unknown Manufacturer'
);

foreach ($missing_manufacturers as $id) {
    $exists = $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'bkgt_manufacturers WHERE id = ' . $id);
    if (!$exists) {
        $wpdb->insert(
            $wpdb->prefix . 'bkgt_manufacturers',
            array(
                'id' => $id,
                'manufacturer_id' => $id,  // Set manufacturer_id to same as id
                'name' => $manufacturer_names[$id],
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        );
        echo "Added manufacturer ID $id: {$manufacturer_names[$id]}\n";
    } else {
        echo "Manufacturer ID $id already exists\n";
    }
}

// Add missing item types (IDs 3, 4, 5)
$missing_item_types = array(3, 4, 5);
$item_type_names = array(
    3 => 'Tröja',
    4 => 'Byxor',
    5 => 'Unknown Item Type'
);

foreach ($missing_item_types as $id) {
    $exists = $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'bkgt_item_types WHERE id = ' . $id);
    if (!$exists) {
        $wpdb->insert(
            $wpdb->prefix . 'bkgt_item_types',
            array(
                'id' => $id,
                'item_type_id' => $id,  // Set item_type_id to same as id
                'name' => $item_type_names[$id],
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        );
        echo "Added item type ID $id: {$item_type_names[$id]}\n";
    } else {
        echo "Item type ID $id already exists\n";
    }
}

echo "Missing records added successfully\n";
?>