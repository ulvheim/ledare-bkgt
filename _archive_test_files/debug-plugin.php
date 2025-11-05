<?php
/**
 * Debug plugin activation issues
 */

// Load WordPress environment
require_once '../../../wp-load.php';

echo "<h1>Debugging Plugin Activation</h1>";
echo "<pre>";

// Test 1: Check if files exist
echo "=== Testing File Existence ===\n";
$files_to_check = array(
    'bkgt-data-scraping.php',
    'includes/class-bkgt-database.php',
    'includes/class-bkgt-scraper.php',
    'includes/class-bkgt-admin.php',
    'includes/shortcodes.php'
);

foreach ($files_to_check as $file) {
    $path = WP_PLUGIN_DIR . '/bkgt-data-scraping/' . $file;
    if (file_exists($path)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file missing: $path\n";
    }
}

echo "\n=== Testing File Inclusion ===\n";
try {
    // Test including each file individually
    require_once WP_PLUGIN_DIR . '/bkgt-data-scraping/includes/class-bkgt-database.php';
    echo "✓ class-bkgt-database.php included successfully\n";

    require_once WP_PLUGIN_DIR . '/bkgt-data-scraping/includes/class-bkgt-scraper.php';
    echo "✓ class-bkgt-scraper.php included successfully\n";

    require_once WP_PLUGIN_DIR . '/bkgt-data-scraping/includes/class-bkgt-admin.php';
    echo "✓ class-bkgt-admin.php included successfully\n";

    // Don't include shortcodes.php yet as it might instantiate classes
    echo "✓ All class files included successfully\n";

} catch (Exception $e) {
    echo "✗ Error including files: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Testing Class Instantiation ===\n";
try {
    // Test database class
    $db = new BKGT_Database();
    echo "✓ BKGT_Database instantiated\n";

    // Test admin class
    $admin = new BKGT_Admin($db);
    echo "✓ BKGT_Admin instantiated\n";

    // Test scraper class
    $scraper = new BKGT_Scraper($db);
    echo "✓ BKGT_Scraper instantiated\n";

} catch (Exception $e) {
    echo "✗ Error instantiating classes: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Testing Shortcodes Inclusion ===\n";
try {
    require_once WP_PLUGIN_DIR . '/bkgt-data-scraping/includes/shortcodes.php';
    echo "✓ shortcodes.php included successfully\n";
} catch (Exception $e) {
    echo "✗ Error including shortcodes.php: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>