<?php
/**
 * Post-Deployment Verification for Equipment Search Enhancement
 *
 * This script verifies that the equipment search enhancements have been deployed correctly.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

echo "BKGT Equipment Search Enhancement - Post-Deployment Verification\n";
echo "================================================================\n\n";

// Check if required files exist and are readable
$required_files = array(
    'wp-content/plugins/bkgt-inventory/includes/class-inventory-item.php',
    'wp-content/plugins/bkgt-inventory/includes/class-api-endpoints.php',
    'wp-content/plugins/bkgt-inventory/includes/class-database.php'
);

echo "1. File Existence Check:\n";
foreach ($required_files as $file) {
    $full_path = ABSPATH . $file;
    if (file_exists($full_path)) {
        echo "   ✓ $file exists\n";
    } else {
        echo "   ✗ $file NOT FOUND\n";
    }
}

echo "\n2. Class Loading Check:\n";

// Check if classes can be loaded
$classes_to_check = array(
    'BKGT_Inventory_Item',
    'BKGT_Inventory_API_Endpoints',
    'BKGT_Inventory_Database'
);

foreach ($classes_to_check as $class) {
    if (class_exists($class)) {
        echo "   ✓ Class $class loaded successfully\n";
    } else {
        echo "   ✗ Class $class NOT FOUND\n";
    }
}

echo "\n3. Method Existence Check:\n";

// Check if our new methods exist
$methods_to_check = array(
    array('BKGT_Inventory_Item', 'build_search_conditions'),
    array('BKGT_Inventory_Item', 'build_search_conditions_for_count'),
    array('BKGT_Inventory_API_Endpoints', 'log_search_query'),
    array('BKGT_Inventory_API_Endpoints', 'get_popular_searches')
);

foreach ($methods_to_check as $method_check) {
    list($class, $method) = $method_check;
    if (method_exists($class, $method)) {
        echo "   ✓ Method $class::$method exists\n";
    } else {
        echo "   ✗ Method $class::$method NOT FOUND\n";
    }
}

echo "\n4. API Endpoint Registration Check:\n";

// Check if REST routes are registered
global $wp_rest_server;
if ($wp_rest_server) {
    $routes = $wp_rest_server->get_routes();
    $equipment_route_found = false;
    $analytics_route_found = false;

    foreach ($routes as $route => $route_data) {
        if (strpos($route, '/bkgt/v1/equipment') === 0) {
            $equipment_route_found = true;
        }
        if (strpos($route, '/bkgt/v1/equipment/search-analytics') === 0) {
            $analytics_route_found = true;
        }
    }

    echo "   " . ($equipment_route_found ? "✓" : "✗") . " Equipment API route registered\n";
    echo "   " . ($analytics_route_found ? "✓" : "✗") . " Search analytics API route registered\n";
}

echo "\n5. Database Table Check:\n";

// Check if search logs table exists
global $wpdb;
$table_name = $wpdb->prefix . 'bkgt_search_logs';
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
    echo "   ✓ Search logs table exists\n";

    // Check table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    $expected_columns = array('id', 'search_term', 'results_count', 'search_fields', 'search_operator', 'fuzzy', 'search_time_ms', 'user_id', 'ip_address', 'timestamp');
    $actual_columns = array_column($columns, 'Field');

    $missing_columns = array_diff($expected_columns, $actual_columns);
    if (empty($missing_columns)) {
        echo "   ✓ Search logs table structure is correct\n";
    } else {
        echo "   ✗ Search logs table missing columns: " . implode(', ', $missing_columns) . "\n";
    }
} else {
    echo "   ✗ Search logs table does not exist\n";
}

echo "\n6. Database Index Check:\n";

// Check if search indexes exist
$indexes_to_check = array(
    'idx_equipment_search_core',
    'idx_equipment_search_text',
    'idx_equipment_size',
    'idx_equipment_notes',
    'idx_equipment_storage',
    'idx_equipment_condition_reason',
    'idx_equipment_sticker_code'
);

$table_name = $wpdb->prefix . 'bkgt_inventory_items';
$existing_indexes = $wpdb->get_results("SHOW INDEX FROM $table_name");
$existing_index_names = array_unique(array_column($existing_indexes, 'Key_name'));

foreach ($indexes_to_check as $index) {
    if (in_array($index, $existing_index_names)) {
        echo "   ✓ Index $index exists\n";
    } else {
        echo "   ✗ Index $index NOT FOUND\n";
    }
}

echo "\n================================================================\n";
echo "Verification Complete!\n";
echo "\nNext Steps:\n";
echo "1. Test the equipment search API: GET /wp-json/bkgt/v1/equipment?search=TDJ\n";
echo "2. Test field-specific search: GET /wp-json/bkgt/v1/equipment?search=TDJ&search_fields=size\n";
echo "3. Test search analytics: GET /wp-json/bkgt/v1/equipment/search-analytics\n";
echo "4. Verify search results include new fields: size, condition_reason, sticker_code\n";
?>