<?php
require_once('wp-load.php');

// Test if the bkgt-inventory plugin loads without syntax errors
echo "Testing bkgt-inventory plugin load...\n";

try {
    // Include the main plugin file
    require_once('wp-content/plugins/bkgt-inventory/bkgt-inventory.php');
    echo "Main plugin file loaded successfully\n";

    // Check if the admin class can be instantiated
    if (class_exists('BKGT_Inventory_Admin')) {
        echo "BKGT_Inventory_Admin class exists\n";

        // Try to create an instance (this will catch any syntax errors in the constructor)
        $admin = new BKGT_Inventory_Admin();
        echo "BKGT_Inventory_Admin instance created successfully\n";

        echo "✅ Plugin syntax check PASSED - no parse errors detected\n";
    } else {
        echo "❌ BKGT_Inventory_Admin class not found\n";
    }

} catch (Exception $e) {
    echo "❌ Error loading plugin: " . $e->getMessage() . "\n";
} catch (ParseError $e) {
    echo "❌ Parse error in plugin: " . $e->getMessage() . "\n";
} catch (Throwable $e) {
    echo "❌ Fatal error in plugin: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";
?>