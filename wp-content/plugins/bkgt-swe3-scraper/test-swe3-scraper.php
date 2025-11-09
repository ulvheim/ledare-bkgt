<?php
/**
 * BKGT SWE3 Scraper Test Script
 *
 * This script can be used to test the plugin functionality
 * Run from command line: php test-swe3-scraper.php
 */

// Prevent web access
if (isset($_SERVER['HTTP_HOST'])) {
    die('This script can only be run from command line');
}

// Bootstrap WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

if (!defined('ABSPATH')) {
    die('WordPress not found');
}

// Check if plugin is active
if (!is_plugin_active('bkgt-swe3-scraper/bkgt-swe3-scraper.php')) {
    die('BKGT SWE3 Scraper plugin is not active' . PHP_EOL);
}

echo "BKGT SWE3 Scraper Test Script" . PHP_EOL;
echo "==============================" . PHP_EOL . PHP_EOL;

// Test plugin loading
echo "1. Testing plugin loading..." . PHP_EOL;
try {
    $plugin = bkgt_swe3_scraper();
    echo "✓ Plugin loaded successfully" . PHP_EOL;
} catch (Exception $e) {
    echo "✗ Plugin loading failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Test database table
echo PHP_EOL . "2. Testing database table..." . PHP_EOL;
global $wpdb;
$table_name = $wpdb->prefix . 'bkgt_swe3_documents';

if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
    echo "✓ Database table exists" . PHP_EOL;

    // Check table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    $expected_columns = array('id', 'swe3_id', 'title', 'document_type', 'swe3_url', 'local_path', 'file_hash', 'version', 'publication_date', 'scraped_date', 'dms_document_id', 'status', 'last_checked', 'error_message');

    $actual_columns = array_map(function($col) { return $col->Field; }, $columns);

    $missing_columns = array_diff($expected_columns, $actual_columns);
    if (empty($missing_columns)) {
        echo "✓ Database table structure is correct" . PHP_EOL;
    } else {
        echo "✗ Missing columns: " . implode(', ', $missing_columns) . PHP_EOL;
    }
} else {
    echo "✗ Database table does not exist" . PHP_EOL;
}

// Test scraper class
echo PHP_EOL . "3. Testing scraper class..." . PHP_EOL;
try {
    $scraper = $plugin->scraper;
    if ($scraper instanceof BKGT_SWE3_Scraper) {
        echo "✓ Scraper class instantiated" . PHP_EOL;
    } else {
        echo "✗ Scraper class not properly instantiated" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "✗ Scraper class error: " . $e->getMessage() . PHP_EOL;
}

// Test parser class
echo PHP_EOL . "4. Testing parser class..." . PHP_EOL;
try {
    $parser = $plugin->parser;
    if ($parser instanceof BKGT_SWE3_Parser) {
        echo "✓ Parser class instantiated" . PHP_EOL;
    } else {
        echo "✗ Parser class not properly instantiated" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "✗ Parser class error: " . $e->getMessage() . PHP_EOL;
}

// Test scheduler class
echo PHP_EOL . "5. Testing scheduler class..." . PHP_EOL;
try {
    $scheduler = $plugin->scheduler;
    if ($scheduler instanceof BKGT_SWE3_Scheduler) {
        echo "✓ Scheduler class instantiated" . PHP_EOL;

        $status = $scheduler->get_scheduler_status();
        echo "  - Scraping enabled: " . ($status['enabled'] ? 'Yes' : 'No') . PHP_EOL;
        echo "  - Next run: " . ($status['next_run'] ?: 'Not scheduled') . PHP_EOL;
    } else {
        echo "✗ Scheduler class not properly instantiated" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "✗ Scheduler class error: " . $e->getMessage() . PHP_EOL;
}

// Test DMS integration class
echo PHP_EOL . "6. Testing DMS integration class..." . PHP_EOL;
try {
    $dms_integration = $plugin->dms_integration;
    if ($dms_integration instanceof BKGT_SWE3_DMS_Integration) {
        echo "✓ DMS integration class instantiated" . PHP_EOL;

        $stats = $dms_integration->get_document_statistics();
        echo "  - Total documents: " . $stats['total_documents'] . PHP_EOL;
        echo "  - Last updated: " . ($stats['last_updated'] ?: 'Never') . PHP_EOL;
    } else {
        echo "✗ DMS integration class not properly instantiated" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "✗ DMS integration class error: " . $e->getMessage() . PHP_EOL;
}

// Test admin class
echo PHP_EOL . "7. Testing admin class..." . PHP_EOL;
try {
    $admin = $plugin->admin;
    if ($admin instanceof BKGT_SWE3_Admin) {
        echo "✓ Admin class instantiated" . PHP_EOL;
    } else {
        echo "✗ Admin class not properly instantiated" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "✗ Admin class error: " . $e->getMessage() . PHP_EOL;
}

// Test SWE3 website connectivity
echo PHP_EOL . "8. Testing SWE3 website connectivity..." . PHP_EOL;
$swe3_url = 'https://amerikanskfotboll.swe3.se/information-verktyg/spelregler-tavlingsbestammelser/';

$headers = @get_headers($swe3_url, 1);
if ($headers && strpos($headers[0], '200') !== false) {
    echo "✓ SWE3 website is accessible" . PHP_EOL;
} else {
    echo "✗ SWE3 website is not accessible" . PHP_EOL;
    echo "  This may affect scraping functionality" . PHP_EOL;
}

// Test file permissions
echo PHP_EOL . "9. Testing file permissions..." . PHP_EOL;
$upload_dir = wp_upload_dir();
$test_file = $upload_dir['path'] . '/swe3-test.tmp';

if (wp_mkdir_p($upload_dir['path'])) {
    if (file_put_contents($test_file, 'test') !== false) {
        unlink($test_file);
        echo "✓ Upload directory is writable" . PHP_EOL;
    } else {
        echo "✗ Upload directory is not writable" . PHP_EOL;
    }
} else {
    echo "✗ Cannot create upload directory" . PHP_EOL;
}

// Summary
echo PHP_EOL . "==============================" . PHP_EOL;
echo "Test completed. Check above for any issues." . PHP_EOL;
echo "If all tests passed, the plugin should be ready for use." . PHP_EOL . PHP_EOL;

// Recommendations
echo "Next steps:" . PHP_EOL;
echo "1. Activate the plugin in WordPress admin" . PHP_EOL;
echo "2. Go to Tools > SWE3 Scraper to configure settings" . PHP_EOL;
echo "3. Run a manual scrape to test functionality" . PHP_EOL;
echo "4. Monitor the admin dashboard for status updates" . PHP_EOL;