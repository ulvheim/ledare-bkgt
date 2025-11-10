<?php
/**
 * Test script to test error logging
 */

require_once 'wp-load.php';

echo "Testing error logging...\n";

error_log('Test error log message');

echo "Error logged. Check if debug.log was created.\n";

// Also test the scraper log method
$scraper = new BKGT_SWE3_Scraper();
$scraper->log('error', 'Test scraper log message');

echo "Scraper log called.\n";
?>