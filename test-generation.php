<?php
require_once('wp-load.php');

// Test the unique identifier and sticker code generation
echo "Testing unique identifier and sticker code generation...\n\n";

// Test generate_unique_identifier
$unique_id = BKGT_Inventory_Item::generate_unique_identifier(5, 5);
echo "Generated unique identifier: $unique_id\n";

// Test generate_sticker_code
$sticker_code = BKGT_Inventory_Item::generate_sticker_code($unique_id);
echo "Generated sticker code: $sticker_code\n";

// Test with different values
$test_cases = [
    ['manufacturer_id' => 1, 'item_type_id' => 1],
    ['manufacturer_id' => 10, 'item_type_id' => 20],
    ['manufacturer_id' => 100, 'item_type_id' => 200],
];

foreach ($test_cases as $test) {
    $uid = BKGT_Inventory_Item::generate_unique_identifier($test['manufacturer_id'], $test['item_type_id']);
    $scode = BKGT_Inventory_Item::generate_sticker_code($uid);
    echo "Manufacturer {$test['manufacturer_id']}, Type {$test['item_type_id']}: UID=$uid, Sticker=$scode\n";
}
?>