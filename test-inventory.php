<?php
/**
 * Test script for BKGT Inventory functionality
 */

// Load WordPress
require_once('wp-load.php');

// Test database tables
echo "=== Database Tables Test ===\n";
global $wpdb;
$db = new BKGT_Database();

$tables = [
    'Manufacturers' => $db->get_manufacturers_table(),
    'Item Types' => $db->get_item_types_table(),
    'Inventory Items' => $db->get_inventory_items_table(),
    'Assignments' => $db->get_assignments_table()
];

foreach ($tables as $name => $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    echo "$name: " . ($exists ? '✓ EXISTS' : '✗ MISSING') . "\n";
}

// Test assignment class
echo "\n=== Assignment Class Test ===\n";
if (class_exists('BKGT_Assignment')) {
    echo "BKGT_Assignment class: ✓ LOADED\n";

    // Test workflow suggestions
    $suggestions = BKGT_Assignment::get_workflow_suggestions(1);
    echo "Workflow suggestions: ✓ WORKING (" . count($suggestions) . " suggestions)\n";

    // Test system alerts
    $alerts = BKGT_Assignment::get_system_alerts();
    echo "System alerts: ✓ WORKING (" . count($alerts) . " alert types)\n";

} else {
    echo "BKGT_Assignment class: ✗ NOT FOUND\n";
}

// Test AJAX handlers
echo "\n=== AJAX Handlers Test ===\n";
$ajax_actions = [
    'bkgt_search_items',
    'bkgt_search_assignees',
    'bkgt_assign_items',
    'bkgt_get_workflow_suggestions',
    'bkgt_get_assignment_history'
];

foreach ($ajax_actions as $action) {
    if (has_action('wp_ajax_' . $action)) {
        echo "$action: ✓ REGISTERED\n";
    } else {
        echo "$action: ✗ NOT REGISTERED\n";
    }
}

echo "\n=== Test Complete ===\n";
?>