<?php
require_once('wp-load.php');

// Test has_errors method
echo "Testing has_errors method...\n";

$no_errors = array();
$with_errors = array('name' => 'Required field');

echo "Empty array: " . (BKGT_Validator::has_errors($no_errors) ? 'HAS ERRORS' : 'NO ERRORS') . "\n";
echo "Array with errors: " . (BKGT_Validator::has_errors($with_errors) ? 'HAS ERRORS' : 'NO ERRORS') . "\n";

echo "Test completed.\n";
?>