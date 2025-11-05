<?php
// Simple Plugin Activation Test - No Auth Required
echo "Testing BKGT Data Scraping Plugin Activation\n";
echo "===========================================\n\n";

// Load WordPress environment
define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "WordPress loaded successfully\n\n";

echo "Testing plugin file inclusion...\n";
try {
    require_once WP_PLUGIN_DIR . '/bkgt-data-scraping/bkgt-data-scraping.php';
    echo "✓ Plugin file included successfully\n\n";
} catch (Exception $e) {
    echo "✗ Failed to include plugin file: " . $e->getMessage() . "\n\n";
    exit(1);
}

echo "Testing activation function...\n";
if (function_exists('bkgt_data_scraping_activate')) {
    try {
        bkgt_data_scraping_activate();
        echo "✓ Activation function executed successfully\n\n";
    } catch (Exception $e) {
        echo "✗ Activation function failed: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "✗ Activation function not found\n\n";
}

echo "Testing plugin functions...\n";
$functions = [
    'bkgt_data_scraping_init',
    'bkgt_create_tables',
    'bkgt_data_scraping_create_tables'
];

foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "✓ $func exists\n";
    } else {
        echo "✗ $func not found\n";
    }
}

echo "\nTesting plugin classes...\n";
$classes = [
    'BKGT_Database',
    'BKGT_Admin',
    'BKGT_Scraper'
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "✓ $class exists\n";
    } else {
        echo "✗ $class not found\n";
    }
}

echo "\nTest completed.\n";
?>