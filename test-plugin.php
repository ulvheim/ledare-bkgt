<?php
/**
 * Simple test script to verify BKGT Data Scraping plugin
 */

// Define WordPress constants for testing
define('ABSPATH', dirname(__FILE__) . '/');
define('WPINC', 'wp-includes');

// Simple test to check if plugin files can be included
echo "Testing BKGT Data Scraping Plugin...\n\n";

$plugin_files = array(
    'bkgt-data-scraping.php',
    'includes/class-bkgt-database.php',
    'includes/class-bkgt-scraper.php',
    'includes/ajax-handlers.php',
    'admin/class-bkgt-admin.php'
);

$errors = array();

foreach ($plugin_files as $file) {
    $file_path = __DIR__ . '/wp-content/plugins/bkgt-data-scraping/' . $file;
    if (file_exists($file_path)) {
        echo "✓ Found: $file\n";
        // Try to check syntax by including (this will fail on syntax errors)
        ob_start();
        $result = include_once($file_path);
        ob_end_clean();
        if ($result === false) {
            $errors[] = "Syntax error in $file";
        } else {
            echo "  ✓ Syntax OK\n";
        }
    } else {
        $errors[] = "Missing file: $file";
    }
}

echo "\nTest Results:\n";
if (empty($errors)) {
    echo "✓ All plugin files found and syntax appears correct!\n";
    echo "The plugin should be ready for activation in WordPress.\n";
} else {
    echo "✗ Issues found:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\nNext steps:\n";
echo "1. Start your WordPress development server\n";
echo "2. Go to wp-admin/plugins.php\n";
echo "3. Activate 'BKGT Data Scraping & Management'\n";
echo "4. Check that database tables are created\n";
echo "5. Test the admin interface at wp-admin/admin.php?page=bkgt-data-management\n";