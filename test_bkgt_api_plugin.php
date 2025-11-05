<?php
/**
 * BKGT API Plugin Test Script
 * Tests basic plugin loading and class instantiation
 */

echo "Testing BKGT API Plugin...\n\n";

// Define WordPress constants
define('ABSPATH', dirname(__FILE__) . '/');
define('WPINC', 'wp-includes');

// Include the main plugin file
$plugin_file = __DIR__ . '/wp-content/plugins/bkgt-api/bkgt-api.php';

if (!file_exists($plugin_file)) {
    echo "ERROR: Plugin file not found: $plugin_file\n";
    exit(1);
}

echo "Loading plugin file...\n";
include_once $plugin_file;

echo "Plugin loaded successfully!\n";

// Check if main class exists
if (class_exists('BKGT_API_Plugin')) {
    echo "✓ Main plugin class found\n";

    // Try to instantiate (this will test basic constructor)
    try {
        $plugin = new BKGT_API_Plugin();
        echo "✓ Plugin class instantiated successfully\n";
    } catch (Exception $e) {
        echo "✗ Error instantiating plugin class: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Main plugin class not found\n";
}

// Check include classes
$classes_to_check = [
    'BKGT_API',
    'BKGT_Auth',
    'BKGT_Endpoints',
    'BKGT_Security',
    'BKGT_Notifications',
    'BKGT_API_Admin'
];

foreach ($classes_to_check as $class) {
    if (class_exists($class)) {
        echo "✓ Class $class found\n";
    } else {
        echo "✗ Class $class not found\n";
    }
}

echo "\nPlugin test completed!\n";