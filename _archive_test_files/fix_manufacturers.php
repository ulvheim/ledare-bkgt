<?php
require_once('wp-load.php');
global $wpdb;

$inventory_db = new BKGT_Inventory_Database();

// Clear and repopulate manufacturers with correct data
$wpdb->query("DELETE FROM " . $inventory_db->get_manufacturers_table());

$manufacturers = array(
    array('Nike', 'NIKE'),
    array('Under Armour', 'UA'),
    array('Schutt', 'SCHT'),
    array('Riddell', 'RIDL')
);

foreach ($manufacturers as $manufacturer) {
    $wpdb->insert(
        $inventory_db->get_manufacturers_table(),
        array(
            'name' => $manufacturer[0],
            'manufacturer_id' => $manufacturer[1]
        )
    );
    echo "Added manufacturer: " . $manufacturer[0] . "\n";
}

// Clear and repopulate item types
$wpdb->query("DELETE FROM " . $inventory_db->get_item_types_table());

$item_types = array(
    array('Hjälmar', 'HELM'),
    array('Axelskydd', 'SHLD'),
    array('Tröja', 'SHRT'),
    array('Byxor', 'PANT'),
    array('Skor', 'SHOE')
);

foreach ($item_types as $item_type) {
    $wpdb->insert(
        $inventory_db->get_item_types_table(),
        array(
            'name' => $item_type[0],
            'item_type_id' => $item_type[1]
        )
    );
    echo "Added item type: " . $item_type[0] . "\n";
}

// Now update inventory items to use correct integer IDs
$manufacturers_db = $wpdb->get_results("SELECT id, manufacturer_id FROM " . $inventory_db->get_manufacturers_table(), ARRAY_A);
$item_types_db = $wpdb->get_results("SELECT id, item_type_id FROM " . $inventory_db->get_item_types_table(), ARRAY_A);

$manufacturer_map = array();
foreach ($manufacturers_db as $m) {
    $manufacturer_map[$m['manufacturer_id']] = $m['id'];
}

$item_type_map = array();
foreach ($item_types_db as $t) {
    $item_type_map[$t['item_type_id']] = $t['id'];
}

// Update inventory items
$items = $wpdb->get_results("SELECT id, unique_identifier FROM " . $inventory_db->get_inventory_items_table());

foreach ($items as $item) {
    // Extract codes from identifier
    $parts = explode('-', $item->unique_identifier);
    if (count($parts) >= 2) {
        $item_type_code = $parts[0]; // HELM, SHLD, etc.
        
        // Map manufacturer based on the identifier
        $manufacturer_code = 'NIKE'; // default
        if (strpos($item->unique_identifier, 'HELM-002') === 0 || 
            strpos($item->unique_identifier, 'SHLD-002') === 0 ||
            strpos($item->unique_identifier, 'SHRT-003') === 0 ||
            strpos($item->unique_identifier, 'PANT-003') === 0 ||
            strpos($item->unique_identifier, 'SHOE-003') === 0) {
            $manufacturer_code = 'UA';
        } elseif (strpos($item->unique_identifier, 'HELM-003') === 0 ||
                  strpos($item->unique_identifier, 'SHLD-003') === 0) {
            $manufacturer_code = 'SCHT';
        }
        
        $new_manufacturer_id = isset($manufacturer_map[$manufacturer_code]) ? $manufacturer_map[$manufacturer_code] : 1;
        $new_item_type_id = isset($item_type_map[$item_type_code]) ? $item_type_map[$item_type_code] : 1;
        
        $wpdb->update(
            $inventory_db->get_inventory_items_table(),
            array(
                'manufacturer_id' => $new_manufacturer_id,
                'item_type_id' => $new_item_type_id
            ),
            array('id' => $item->id)
        );
        
        echo "Updated " . $item->unique_identifier . ": manufacturer -> $new_manufacturer_id, type -> $new_item_type_id\n";
    }
}

echo "\nDone!\n";
?>