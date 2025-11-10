<?php
/**
 * Simple SWE3 Scraper Test
 * Test basic functionality without running full scrape
 */

require_once '../../../wp-load.php';

if (!defined('ABSPATH')) {
    die('WordPress not found' . PHP_EOL);
}

echo "Testing SWE3 Scraper Basic Functionality\n";
echo "========================================\n\n";

// Check if plugin is active
if (!is_plugin_active('bkgt-swe3-scraper/bkgt-swe3-scraper.php')) {
    die('BKGT SWE3 Scraper plugin is not active' . PHP_EOL);
}

echo "✓ Plugin is active\n";

// Test plugin loading
try {
    $plugin = bkgt_swe3_scraper();
    echo "✓ Plugin instance created\n";
} catch (Exception $e) {
    die('✗ Plugin loading failed: ' . $e->getMessage() . PHP_EOL);
}

// Test components
$components = ['scraper', 'parser', 'scheduler', 'dms_integration'];
foreach ($components as $component) {
    if (isset($plugin->$component)) {
        echo "✓ $component component loaded\n";
    } else {
        echo "✗ $component component missing\n";
    }
}

// Test database table
global $wpdb;
$table_name = $wpdb->prefix . 'bkgt_swe3_documents';

if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
    echo "✓ Database table exists\n";

    // Check table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    $expected_columns = ['id', 'swe3_id', 'title', 'document_type', 'swe3_url', 'local_path', 'file_hash', 'version', 'publication_date', 'scraped_date', 'dms_document_id', 'status', 'last_checked', 'error_message'];

    $actual_columns = array_map(function($col) { return $col->Field; }, $columns);
    $missing_columns = array_diff($expected_columns, $actual_columns);

    if (empty($missing_columns)) {
        echo "✓ Database table structure is correct\n";
    } else {
        echo "✗ Missing columns: " . implode(', ', $missing_columns) . "\n";
    }
} else {
    echo "✗ Database table does not exist\n";
}

// Test parser functionality
echo "\nTesting parser...\n";
try {
    $parser = $plugin->parser;
    $test_html = '<html><body><a href="https://example.com/test.pdf">Test Document</a></body></html>';
    $documents = $parser->parse_documents($test_html);

    if (is_array($documents)) {
        echo "✓ Parser returned array\n";
        echo "  Found " . count($documents) . " documents\n";
    } else {
        echo "✗ Parser did not return array\n";
    }
} catch (Exception $e) {
    echo "✗ Parser test failed: " . $e->getMessage() . "\n";
}

// Test SWE3 website connectivity
echo "\nTesting SWE3 website connectivity...\n";
$swe3_url = 'https://amerikanskfotboll.swe3.se/information-verktyg/spelregler-tavlingsbestammelser/';

$headers = @get_headers($swe3_url, 1);
if ($headers && strpos($headers[0], '200') !== false) {
    echo "✓ SWE3 website is accessible\n";
} else {
    echo "✗ SWE3 website is not accessible\n";
}

echo "\nBasic functionality test completed!\n";
?>