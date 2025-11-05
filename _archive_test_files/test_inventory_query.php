<?php
require_once('wp-load.php');
global $wpdb;

echo "Testing the inventory query from shortcode...\n";

$inventory_db = new BKGT_Inventory_Database();

$query = "SELECT
    i.*,
    m.name as manufacturer_name,
    t.name as item_type_name,
    a.assignee_type as assignment_type,
    a.assignee_id as assigned_to,
    CASE 
        WHEN a.assignee_type = 'location' THEN l.name
        WHEN a.assignee_type = 'team' THEN tm.name
        WHEN a.assignee_type = 'user' THEN CONCAT(u.display_name, ' (', u.user_login, ')')
        ELSE NULL
    END as location_name
    FROM {$inventory_db->get_inventory_items_table()} i
    LEFT JOIN {$inventory_db->get_manufacturers_table()} m ON i.manufacturer_id = m.id
    LEFT JOIN {$inventory_db->get_item_types_table()} t ON i.item_type_id = t.id
    LEFT JOIN {$inventory_db->get_assignments_table()} a ON i.id = a.item_id AND a.unassigned_date IS NULL
    LEFT JOIN {$inventory_db->get_locations_table()} l ON a.assignee_type = 'location' AND a.assignee_id = l.id
    LEFT JOIN {$wpdb->prefix}bkgt_teams tm ON a.assignee_type = 'team' AND a.assignee_id = tm.id
    LEFT JOIN {$wpdb->users} u ON a.assignee_type = 'user' AND a.assignee_id = u.ID
    ORDER BY i.created_at DESC";

echo "Query: $query\n\n";

$results = $wpdb->get_results($query);
echo "Number of results: " . count($results) . "\n\n";

if (count($results) > 0) {
    echo "First few results:\n";
    for ($i = 0; $i < min(3, count($results)); $i++) {
        $item = $results[$i];
        echo "ID: {$item->id}, Identifier: {$item->unique_identifier}, Title: {$item->title}, Manufacturer: {$item->manufacturer_name}, Type: {$item->item_type_name}\n";
    }
} else {
    echo "No results found - this would trigger sample data display\n";
}

// Also check if tables have data
echo "\nTable counts:\n";
$tables = array(
    'manufacturers' => $inventory_db->get_manufacturers_table(),
    'item_types' => $inventory_db->get_item_types_table(),
    'inventory_items' => $inventory_db->get_inventory_items_table(),
    'assignments' => $inventory_db->get_assignments_table(),
    'locations' => $inventory_db->get_locations_table()
);

foreach ($tables as $name => $table) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    echo "$name: $count records\n";
}
?>