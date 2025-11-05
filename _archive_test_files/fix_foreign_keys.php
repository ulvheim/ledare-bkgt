<?php
require_once('wp-load.php');
global $wpdb;

$inventory_db = new BKGT_Inventory_Database();

echo "=== Fixing Foreign Key Relationships ===\n\n";

// Get all manufacturers and item types with their mappings
$manufacturers = $wpdb->get_results("SELECT id, name, manufacturer_id FROM {$inventory_db->get_manufacturers_table()}", ARRAY_A);
$item_types = $wpdb->get_results("SELECT id, name, item_type_id FROM {$inventory_db->get_item_types_table()}", ARRAY_A);

echo "Manufacturers:\n";
foreach ($manufacturers as $m) {
    echo "ID {$m['id']}: {$m['name']} (code: {$m['manufacturer_id']})\n";
}

echo "\nItem Types:\n";
foreach ($item_types as $t) {
    echo "ID {$t['id']}: {$t['name']} (code: {$t['item_type_id']})\n";
}

// Create mapping arrays based on the unique identifiers
// From the identifiers, we can see patterns:
// HELM-001, HELM-002, HELM-003 -> Hjälmar (ID 1)
// SHLD-001, SHLD-002 -> Axelskydd (ID 2)

$manufacturer_mapping = array(
    // Based on the populate_inventory.php script and current data
    // We need to map the old codes to new IDs
    'NIKE' => 1, // Assuming Nike is ID 1
    'SCHT' => 3, // Schutt
    'RIDL' => 2, // Riddell  
    'UA' => 4,   // Under Armour
);

$item_type_mapping = array(
    'HELM' => 1, // Hjälmar
    'SHLD' => 2, // Axelskydd
    'SHRT' => 4, // Träningströjor
    'PANT' => 3, // Spelarbyxor
    'SHOE' => 5, // Fotbollar (assuming shoes map to balls for now)
);

// Get inventory items that need fixing
$items = $wpdb->get_results("SELECT id, unique_identifier, manufacturer_id, item_type_id FROM {$inventory_db->get_inventory_items_table()}");

echo "\n=== Updating Inventory Items ===\n";

$updated = 0;
foreach ($items as $item) {
    $identifier_parts = explode('-', $item->unique_identifier);
    if (count($identifier_parts) >= 2) {
        $manufacturer_code = $identifier_parts[0];
        $item_type_code = ''; // Need to determine from identifier
        
        // Extract item type from identifier
        if (strpos($item->unique_identifier, 'HELM') === 0) {
            $item_type_code = 'HELM';
        } elseif (strpos($item->unique_identifier, 'SHLD') === 0) {
            $item_type_code = 'SHLD';
        } elseif (strpos($item->unique_identifier, 'SHRT') === 0) {
            $item_type_code = 'SHRT';
        } elseif (strpos($item->unique_identifier, 'PANT') === 0) {
            $item_type_code = 'PANT';
        } elseif (strpos($item->unique_identifier, 'SHOE') === 0) {
            $item_type_code = 'SHOE';
        }
        
        $new_manufacturer_id = isset($manufacturer_mapping[$manufacturer_code]) ? $manufacturer_mapping[$manufacturer_code] : $item->manufacturer_id;
        $new_item_type_id = isset($item_type_mapping[$item_type_code]) ? $item_type_mapping[$item_type_code] : $item->item_type_id;
        
        if ($new_manufacturer_id != $item->manufacturer_id || $new_item_type_id != $item->item_type_id) {
            $wpdb->update(
                $inventory_db->get_inventory_items_table(),
                array(
                    'manufacturer_id' => $new_manufacturer_id,
                    'item_type_id' => $new_item_type_id
                ),
                array('id' => $item->id)
            );
            echo "Updated {$item->unique_identifier}: manufacturer {$item->manufacturer_id}->{$new_manufacturer_id}, type {$item->item_type_id}->{$new_item_type_id}\n";
            $updated++;
        }
    }
}

echo "\nUpdated $updated items\n";

// Test the query again
echo "\n=== Testing Fixed Query ===\n";
$query = "SELECT
    i.*,
    m.name as manufacturer_name,
    t.name as item_type_name
    FROM {$inventory_db->get_inventory_items_table()} i
    LEFT JOIN {$inventory_db->get_manufacturers_table()} m ON i.manufacturer_id = m.id
    LEFT JOIN {$inventory_db->get_item_types_table()} t ON i.item_type_id = t.id
    ORDER BY i.created_at DESC LIMIT 5";

$results = $wpdb->get_results($query);
echo "Results with proper joins:\n";
foreach ($results as $item) {
    echo "{$item->unique_identifier}: {$item->title} - {$item->manufacturer_name} / {$item->item_type_name}\n";
}
?>