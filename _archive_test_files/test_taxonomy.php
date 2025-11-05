<?php
require_once('wp-load.php');

echo "=== Testing Taxonomy Registration ===\n\n";

// Check if taxonomy is registered
if (taxonomy_exists('bkgt_condition')) {
    echo "✅ Taxonomy 'bkgt_condition' is registered\n";
} else {
    echo "❌ Taxonomy 'bkgt_condition' is NOT registered\n";
}

// Check if post type is registered
if (post_type_exists('bkgt_inventory_item')) {
    echo "✅ Post type 'bkgt_inventory_item' is registered\n";
} else {
    echo "❌ Post type 'bkgt_inventory_item' is NOT registered\n";
}

// Test get_terms function
echo "\n=== Testing get_terms ===\n";
$conditions = get_terms(array(
    'taxonomy' => 'bkgt_condition',
    'hide_empty' => false,
));

if (is_wp_error($conditions)) {
    echo "❌ get_terms returned WP_Error: " . $conditions->get_error_message() . "\n";
} else {
    echo "✅ get_terms returned " . count($conditions) . " terms\n";
    if (empty($conditions)) {
        echo "ℹ️  No condition terms exist yet. You can add them in the admin.\n";
    }
}

// Test admin class loading
echo "\n=== Testing Admin Class ===\n";
if (class_exists('BKGT_Inventory_Admin')) {
    echo "✅ BKGT_Inventory_Admin class exists\n";
    
    try {
        $admin = new BKGT_Inventory_Admin();
        echo "✅ BKGT_Inventory_Admin instantiated successfully\n";
        
        if (method_exists($admin, 'render_condition_meta_box')) {
            echo "✅ render_condition_meta_box method exists\n";
        } else {
            echo "❌ render_condition_meta_box method does NOT exist\n";
        }
    } catch (Exception $e) {
        echo "❌ Error instantiating BKGT_Inventory_Admin: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ BKGT_Inventory_Admin class does NOT exist\n";
}

echo "\n=== Next Steps ===\n";
echo "1. Go to WordPress Admin: https://ledare.bkgt.se/wp-admin/\n";
echo "2. Navigate to Utrustning → Lägg till artikel\n";
echo "3. The condition meta box should now work without errors\n";
echo "4. You can add condition terms by clicking 'Hantera skickstyper' in the meta box\n";