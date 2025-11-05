<?php
require_once('wp-load.php');

echo "=== Final Verification ===\n\n";

$output = do_shortcode('[bkgt_inventory]');

// Check for duplicate search inputs
preg_match_all('/<input[^>]*id="bkgt-inventory-search"/', $output, $searches);
echo "Search inputs found: " . count($searches[0]) . " (should be 1)\n";

// Check title
if (strpos($output, '<h4>Wilson Fotbollar - TDJ</h4>') !== false) {
    echo "✅ Title shows meaningful information\n";
} else {
    echo "❌ Title not updated\n";
}

// Check that unique ID is still searchable
if (strpos($output, '0005-0005-00001') !== false) {
    echo "✅ Unique ID still present for searching\n";
} else {
    echo "❌ Unique ID missing\n";
}

// Check metadata includes size and material
if (strpos($output, 'Storlek:') !== false && strpos($output, 'Material:') !== false) {
    echo "✅ Size and material metadata added\n";
} else {
    echo "❌ Size/material metadata missing\n";
}

echo "\n=== Success! ===\n";
?>