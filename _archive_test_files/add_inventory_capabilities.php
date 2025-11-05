<?php
require_once('wp-load.php');

echo "=== Adding Inventory Admin Capabilities ===\n\n";

// Add manage_inventory capability to administrator role
$admin_role = get_role('administrator');
if ($admin_role) {
    if ($admin_role->has_cap('manage_inventory')) {
        echo "✅ Administrator already has 'manage_inventory' capability\n";
    } else {
        $admin_role->add_cap('manage_inventory');
        echo "✅ Added 'manage_inventory' capability to administrator role\n";
    }
} else {
    echo "❌ Could not find administrator role\n";
}

// Check current user capabilities
if (current_user_can('manage_inventory')) {
    echo "✅ Current user has 'manage_inventory' capability\n";
} else {
    echo "❌ Current user does NOT have 'manage_inventory' capability\n";
}

// Force load admin classes for testing
$plugin_dir = WP_PLUGIN_DIR . '/bkgt-inventory/';
require_once $plugin_dir . 'admin/class-admin.php';
require_once $plugin_dir . 'admin/class-item-admin.php';

// Test admin menu registration by manually calling the function
if (class_exists('BKGT_Inventory_Admin')) {
    echo "✅ BKGT_Inventory_Admin class exists\n";
    
    // Try to instantiate and check for errors
    try {
        $admin_instance = new BKGT_Inventory_Admin();
        echo "✅ BKGT_Inventory_Admin instantiated successfully\n";
    } catch (Exception $e) {
        echo "❌ Error instantiating BKGT_Inventory_Admin: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ BKGT_Inventory_Admin class does NOT exist\n";
    echo "This means the admin/class-admin.php file is not being loaded properly\n";
}

// Check if admin files exist
$admin_files = array(
    'admin/class-admin.php',
    'admin/class-item-admin.php'
);

echo "\nAdmin Files Check:\n";
foreach ($admin_files as $file) {
    $file_path = plugin_dir_path('bkgt-inventory/bkgt-inventory.php') . $file;
    if (file_exists($file_path)) {
        echo "✅ $file exists\n";
    } else {
        echo "❌ $file does NOT exist\n";
    }
}

echo "\n=== Manual Admin Menu Check ===\n";
// Manually check if the admin menu function exists
if (function_exists('add_menu_page')) {
    echo "✅ add_menu_page function is available\n";
} else {
    echo "❌ add_menu_page function is NOT available (not in admin context?)\n";
}

echo "\n=== Plugin Load Check ===\n";
// Check if our plugin functions exist
if (function_exists('bkgt_inventory_register_post_type')) {
    echo "✅ bkgt_inventory_register_post_type function exists\n";
} else {
    echo "❌ bkgt_inventory_register_post_type function does NOT exist\n";
}

if (function_exists('bkgt_inventory_shortcode')) {
    echo "✅ bkgt_inventory_shortcode function exists\n";
} else {
    echo "❌ bkgt_inventory_shortcode function does NOT exist\n";
}