<?php
require_once('wp-load.php');

echo "Testing shortcode execution...\n\n";

if (function_exists('bkgt_inventory_shortcode')) {
    $output = bkgt_inventory_shortcode(array());
    echo "Shortcode output length: " . strlen($output) . "\n\n";

    // Check if it contains sample data
    if (strpos($output, 'HELM001') !== false) {
        echo "❌ Output contains SAMPLE DATA (HELM001)\n";
    } else {
        echo "✅ Output does NOT contain sample data\n";
    }

    // Check if it contains real data
    if (strpos($output, 'HELM-001') !== false) {
        echo "✅ Output contains REAL DATA (HELM-001)\n";
    } else {
        echo "❌ Output does NOT contain real data\n";
    }

    // Check if it contains the real item
    if (strpos($output, '0005-0005-00001') !== false) {
        echo "✅ Output contains the REAL ITEM (0005-0005-00001)\n";
    } else {
        echo "❌ Output does NOT contain the real item\n";
    }

    echo "\nFirst 1000 characters of output:\n";
    echo substr($output, 0, 1000) . "\n";
} else {
    echo "Shortcode function not found\n";
}
?>