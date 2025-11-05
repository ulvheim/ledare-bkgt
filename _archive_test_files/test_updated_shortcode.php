<?php
require_once('wp-load.php');

echo "Testing updated shortcode...\n";
$output = do_shortcode('[bkgt_inventory]');

if (strpos($output, '0005-0005-00001') !== false) {
    echo "✅ SUCCESS: Real item found in output!\n";
} else {
    echo "❌ FAIL: Real item not found in output\n";
}

if (strpos($output, 'HELM-001') !== false) {
    echo "❌ Still showing fake data (HELM-001)\n";
} else {
    echo "✅ Not showing fake data\n";
}

echo "\nFirst 1000 chars of output:\n";
echo substr($output, 0, 1000) . "\n";
?>