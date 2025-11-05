<?php
require_once('wp-load.php');

echo "=== BKGT Inventory Admin Access Check ===\n\n";

// Check if plugin is active
$active_plugins = get_option('active_plugins');
$inventory_plugin = 'bkgt-inventory/bkgt-inventory.php';

if (in_array($inventory_plugin, $active_plugins)) {
    echo "✅ BKGT Inventory plugin is ACTIVE\n\n";
} else {
    echo "❌ BKGT Inventory plugin is NOT ACTIVE\n";
    echo "Active plugins:\n";
    foreach ($active_plugins as $plugin) {
        echo "  - $plugin\n";
    }
    echo "\nTo activate: Go to WordPress Admin → Plugins → Find 'BKGT Inventory System' → Activate\n\n";
    exit(1);
}

// Check if admin user has required capabilities
if (current_user_can('manage_inventory')) {
    echo "✅ Current user has 'manage_inventory' capability\n";
} else {
    echo "❌ Current user does NOT have 'manage_inventory' capability\n";
    echo "Required capability: manage_inventory\n\n";
}

// Check admin menu structure
global $menu;
echo "Admin Menu Structure (looking for Utrustning):\n";
$found_inventory = false;
foreach ($menu as $menu_item) {
    if (isset($menu_item[0]) && strpos($menu_item[0], 'Utrustning') !== false) {
        echo "✅ Found: {$menu_item[0]}\n";
        $found_inventory = true;
        break;
    }
}

if (!$found_inventory) {
    echo "❌ Utrustning menu not found in admin menu\n";
    echo "This could mean:\n";
    echo "  - Plugin not loaded properly\n";
    echo "  - Admin menu not registered\n";
    echo "  - PHP errors preventing menu registration\n\n";
}

// Check custom post type registration
if (post_type_exists('bkgt_inventory_item')) {
    echo "✅ Custom post type 'bkgt_inventory_item' is registered\n";
} else {
    echo "❌ Custom post type 'bkgt_inventory_item' is NOT registered\n";
}

// Check database tables
global $wpdb;
$tables_to_check = array(
    'bkgt_manufacturers',
    'bkgt_item_types',
    'bkgt_inventory_items',
    'bkgt_assignments',
    'bkgt_locations'
);

echo "\nDatabase Tables:\n";
foreach ($tables_to_check as $table) {
    $full_table = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'");
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
        echo "✅ $full_table exists ($count records)\n";
    } else {
        echo "❌ $full_table does NOT exist\n";
    }
}

echo "\n=== Access Instructions ===\n";
echo "To access the inventory system:\n";
echo "1. Log in to WordPress admin\n";
echo "2. Look for 'Utrustning' in the left sidebar menu\n";
echo "3. Click 'Utrustning' to access the main inventory dashboard\n";
echo "4. Use submenus to:\n";
echo "   - View all items: 'Alla artiklar'\n";
echo "   - Add new item: 'Lägg till artikel'\n";
echo "   - Manage manufacturers: 'Tillverkare'\n";
echo "   - Manage item types: 'Artikeltyper'\n";
echo "   - View locations: 'Platser'\n";
echo "   - View history: 'Historik'\n";
echo "   - Generate reports: 'Rapporter'\n\n";

echo "Direct URLs (replace YOUR_SITE with your domain):\n";
echo "- Main inventory: YOUR_SITE/wp-admin/admin.php?page=bkgt-inventory\n";
echo "- All items: YOUR_SITE/wp-admin/edit.php?post_type=bkgt_inventory_item\n";
echo "- Add new item: YOUR_SITE/wp-admin/post-new.php?post_type=bkgt_inventory_item\n";
echo "- Manufacturers: YOUR_SITE/wp-admin/admin.php?page=bkgt-manufacturers\n";
echo "- Item types: YOUR_SITE/wp-admin/admin.php?page=bkgt-item-types\n";
echo "- Locations: YOUR_SITE/wp-admin/admin.php?page=bkgt-locations\n";