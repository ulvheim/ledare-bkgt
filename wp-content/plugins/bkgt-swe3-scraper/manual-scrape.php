<?php
/**
 * Manual SWE3 Scraper Trigger
 * Run this script to manually trigger a SWE3 document scrape
 */

// Bootstrap WordPress
require_once dirname(__FILE__) . '/../../../wp-load.php';

if (!defined('ABSPATH')) {
    die('WordPress not found' . PHP_EOL);
}

// Check if plugin is active
if (!is_plugin_active('bkgt-swe3-scraper/bkgt-swe3-scraper.php')) {
    die('BKGT SWE3 Scraper plugin is not active' . PHP_EOL);
}

echo "Triggering manual SWE3 scrape..." . PHP_EOL;

try {
    $scraper = bkgt_swe3_scraper()->scraper;
    echo "Directly calling scraper->execute_scrape()..." . PHP_EOL;
    $result = $scraper->execute_scrape();

    if ($result) {
        echo "✓ Scraping completed successfully!" . PHP_EOL;
    } else {
        echo "✗ Scraping failed!" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "Checking DMS for new documents..." . PHP_EOL;

// Check how many documents are now in DMS
global $wpdb;
$dms_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bkgt_document'");
$swe3_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_swe3_documents");

echo "DMS documents: $dms_count" . PHP_EOL;
echo "SWE3 tracked documents: $swe3_count" . PHP_EOL;
?>