<?php
define('ABSPATH', '/some/path/');

echo "Testing scraper class...\n";
require_once 'includes/class-bkgt-swe3-scraper.php';
echo "Scraper class loaded\n";

try {
    $scraper = new BKGT_SWE3_Scraper();
    echo "Scraper instantiated\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Testing scheduler class...\n";
require_once 'includes/class-bkgt-swe3-scheduler.php';
echo "Scheduler class loaded\n";

try {
    $scheduler = new BKGT_SWE3_Scheduler();
    echo "Scheduler instantiated\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Testing DMS integration class...\n";
require_once 'includes/class-bkgt-swe3-dms-integration.php';
echo "DMS integration class loaded\n";

try {
    $dms = new BKGT_SWE3_DMS_Integration();
    echo "DMS integration instantiated\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>