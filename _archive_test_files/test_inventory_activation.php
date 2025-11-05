<?php
/**
 * Test script to check if BKGT Inventory plugin can be activated
 */

// Load WordPress
require_once('wp-load.php');

// Check if plugin exists
$plugin_file = 'bkgt-inventory/bkgt-inventory.php';
$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;

if (!file_exists($plugin_path)) {
    echo "ERROR: Plugin file not found at $plugin_path\n";
    exit(1);
}

echo "Plugin file found: $plugin_path\n";

// Try to include the plugin file
echo "Testing plugin inclusion...\n";
try {
    include_once($plugin_path);
    echo "SUCCESS: Plugin included without errors\n";
} catch (Exception $e) {
    echo "ERROR: Exception during plugin inclusion: " . $e->getMessage() . "\n";
    exit(1);
} catch (Error $e) {
    echo "ERROR: Fatal error during plugin inclusion: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if activation functions exist
if (!function_exists('bkgt_inventory_activate')) {
    echo "ERROR: bkgt_inventory_activate function not found\n";
    exit(1);
}

if (!function_exists('bkgt_inventory_deactivate')) {
    echo "ERROR: bkgt_inventory_deactivate function not found\n";
    exit(1);
}

echo "SUCCESS: Activation functions found\n";

// Test activation hook
echo "Testing activation hook...\n";
try {
    bkgt_inventory_activate();
    echo "SUCCESS: Activation hook executed without errors\n";
} catch (Exception $e) {
    echo "ERROR: Exception during activation: " . $e->getMessage() . "\n";
    exit(1);
} catch (Error $e) {
    echo "ERROR: Fatal error during activation: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if option was set
$test_option = get_option('bkgt_inventory_test');
if ($test_option === 'activated') {
    echo "SUCCESS: Plugin activation test option set correctly\n";
} else {
    echo "ERROR: Plugin activation test option not set (got: " . var_export($test_option, true) . ")\n";
    exit(1);
}

echo "\nAll tests passed! BKGT Inventory plugin should activate successfully.\n";
?>