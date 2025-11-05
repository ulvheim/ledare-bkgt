<?php
require_once('wp-load.php');

echo "=== Testing All Inventory Classes ===\n\n";

// Test bkgt_inventory() function
echo "Testing bkgt_inventory() function:\n";
$plugin_instance = bkgt_inventory();
if ($plugin_instance && isset($plugin_instance->db)) {
    echo "✅ bkgt_inventory() function works\n";
    echo "✅ Database instance available\n";
} else {
    echo "❌ bkgt_inventory() function failed\n";
    exit(1);
}

// Test BKGT_Manufacturer class
echo "\nTesting BKGT_Manufacturer class:\n";
if (class_exists('BKGT_Manufacturer')) {
    echo "✅ BKGT_Manufacturer class exists\n";

    if (method_exists('BKGT_Manufacturer', 'get_all')) {
        echo "✅ get_all method exists\n";

        try {
            $manufacturers = BKGT_Manufacturer::get_all();
            if (is_array($manufacturers)) {
                echo "✅ get_all() returned array with " . count($manufacturers) . " manufacturers\n";
            } else {
                echo "❌ get_all() did not return array\n";
            }
        } catch (Exception $e) {
            echo "❌ Error calling get_all(): " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ get_all method does NOT exist\n";
    }
} else {
    echo "❌ BKGT_Manufacturer class does NOT exist\n";
}

// Test BKGT_Item_Type class
echo "\nTesting BKGT_Item_Type class:\n";
if (class_exists('BKGT_Item_Type')) {
    echo "✅ BKGT_Item_Type class exists\n";

    if (method_exists('BKGT_Item_Type', 'get_all')) {
        echo "✅ get_all method exists\n";

        try {
            $item_types = BKGT_Item_Type::get_all();
            if (is_array($item_types)) {
                echo "✅ get_all() returned array with " . count($item_types) . " item types\n";
            } else {
                echo "❌ get_all() did not return array\n";
            }
        } catch (Exception $e) {
            echo "❌ Error calling get_all(): " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ get_all method does NOT exist\n";
    }
} else {
    echo "❌ BKGT_Item_Type class does NOT exist\n";
}

// Test BKGT_History class
echo "\nTesting BKGT_History class:\n";
if (class_exists('BKGT_History')) {
    echo "✅ BKGT_History class exists\n";

    if (method_exists('BKGT_History', 'get_item_history')) {
        echo "✅ get_item_history method exists\n";
    } else {
        echo "❌ get_item_history method does NOT exist\n";
    }
} else {
    echo "❌ BKGT_History class does NOT exist\n";
}

// Test admin class loading
echo "\nTesting Admin Classes:\n";
if (class_exists('BKGT_Inventory_Admin')) {
    echo "✅ BKGT_Inventory_Admin class exists\n";

    if (method_exists('BKGT_Inventory_Admin', 'render_inventory_details_meta_box')) {
        echo "✅ render_inventory_details_meta_box method exists\n";
    } else {
        echo "❌ render_inventory_details_meta_box method does NOT exist\n";
    }
} else {
    echo "❌ BKGT_Inventory_Admin class does NOT exist\n";
}

echo "\n=== Summary ===\n";
echo "If all tests above are green, the inventory admin should work without fatal errors.\n";
echo "Try accessing: https://ledare.bkgt.se/wp-admin/post-new.php?post_type=bkgt_inventory_item\n";