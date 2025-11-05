<?php
require_once('wp-load.php');

echo "Examining current shortcode output...\n\n";
$output = do_shortcode('[bkgt_inventory]');

// Find all input elements
preg_match_all('/<input[^>]*>/', $output, $inputs);
echo "Input elements found: " . count($inputs[0]) . "\n";
foreach ($inputs[0] as $input) {
    echo "  $input\n";
}

// Find h4 elements
preg_match_all('/<h4[^>]*>.*?<\/h4>/', $output, $h4s);
echo "\nH4 elements found: " . count($h4s[0]) . "\n";
foreach ($h4s[0] as $h4) {
    echo "  $h4\n";
}

// Show the structure around the inventory items
$start = strpos($output, '<div class="bkgt-inventory-grid">');
$end = strpos($output, '</div>', $start + 1000);
if ($start !== false && $end !== false) {
    $grid_section = substr($output, $start, $end - $start + 6);
    echo "\nInventory grid section:\n$grid_section\n";
}
?>