<?php
require_once('wp-load.php');
global $wpdb;
$locations_table = $wpdb->prefix . 'bkgt_locations';
$locations = $wpdb->get_results("SELECT id, name FROM $locations_table LIMIT 10");
echo "Locations in database:\n";
foreach ($locations as $loc) {
    echo $loc->id . ': ' . $loc->name . "\n";
}

// Also check assignment for the specific item
$item_id = 5; // The item with ID 0005-0005-00001
$assignment_type = get_post_meta($item_id, '_bkgt_assignment_type', true);
$assigned_to = get_post_meta($item_id, '_bkgt_assigned_to', true);
echo "\nItem $item_id assignment:\n";
echo "Type: $assignment_type\n";
echo "Assigned to: $assigned_to\n";
?>