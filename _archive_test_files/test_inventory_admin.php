<?php
require_once('wp-load.php');

echo "=== Testing Inventory Admin Constants ===\n\n";

$constants = array(
    'BKGT_INV_VERSION',
    'BKGT_INV_PLUGIN_DIR',
    'BKGT_INV_PLUGIN_URL'
);

foreach ($constants as $constant) {
    if (defined($constant)) {
        echo "✅ $constant = " . constant($constant) . "\n";
    } else {
        echo "❌ $constant is NOT defined\n";
    }
}

echo "\n=== Testing Admin Class Loading ===\n";

// Force load admin files for testing
$plugin_dir = WP_PLUGIN_DIR . '/bkgt-inventory/';
require_once $plugin_dir . 'admin/class-admin.php';
require_once $plugin_dir . 'admin/class-item-admin.php';

if (class_exists('BKGT_Inventory_Admin')) {
    echo "✅ BKGT_Inventory_Admin class exists\n";

    try {
        // Test instantiation
        $admin = new BKGT_Inventory_Admin();
        echo "✅ BKGT_Inventory_Admin instantiated successfully\n";

        // Test enqueue method (this was causing the error)
        if (method_exists($admin, 'enqueue_admin_assets')) {
            echo "✅ enqueue_admin_assets method exists\n";
        } else {
            echo "❌ enqueue_admin_assets method does NOT exist\n";
        }

    } catch (Exception $e) {
        echo "❌ Error instantiating BKGT_Inventory_Admin: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ BKGT_Inventory_Admin class does NOT exist\n";
}

echo "\n=== Asset File Check ===\n";
$assets = array(
    'assets/admin.css',
    'assets/admin.js',
    'assets/frontend.css'
);

foreach ($assets as $asset) {
    $file_path = BKGT_INV_PLUGIN_DIR . $asset;
    if (file_exists($file_path)) {
        echo "✅ $asset exists\n";
    } else {
        echo "❌ $asset does NOT exist\n";
    }
}