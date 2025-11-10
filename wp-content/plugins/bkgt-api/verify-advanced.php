<?php
/**
 * Quick test to verify advanced equipment routes are working
 */

// Include WordPress
require_once '../../../wp-load.php';

echo "=== BKGT API Advanced Equipment Routes Test ===\n\n";

// Check if routes are registered
$routes = rest_get_server()->get_routes();

$bulk_route = isset($routes['bkgt/v1/equipment/bulk']);
$search_route = isset($routes['bkgt/v1/equipment/search']);

echo "Bulk route registered: " . ($bulk_route ? "✓ YES" : "✗ NO") . "\n";
echo "Search route registered: " . ($search_route ? "✓ YES" : "✗ NO") . "\n\n";

// Check if methods exist
$endpoints_class = new BKGT_API_Endpoints();
$bulk_method = method_exists($endpoints_class, 'bulk_equipment_operation');
$search_method = method_exists($endpoints_class, 'search_equipment');

echo "Bulk method exists: " . ($bulk_method ? "✓ YES" : "✗ NO") . "\n";
echo "Search method exists: " . ($search_method ? "✓ YES" : "✗ NO") . "\n\n";

// Check class loading
$history_class = class_exists('BKGT_History');
$assignment_class = class_exists('BKGT_Assignment');

echo "BKGT_History class loaded: " . ($history_class ? "✓ YES" : "✗ NO") . "\n";
echo "BKGT_Assignment class loaded: " . ($assignment_class ? "✓ YES" : "✗ NO") . "\n\n";

if ($bulk_route && $search_route && $bulk_method && $search_method && $history_class && $assignment_class) {
    echo "🎉 ALL CHECKS PASSED - Advanced features should be working!\n";
} else {
    echo "❌ Some checks failed - routes may still return 404\n";
}
?>