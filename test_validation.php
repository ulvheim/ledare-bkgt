<?php
require_once('wp-load.php');

// Test manufacturer validation
echo "Testing manufacturer validation...\n";

$test_data = array(
    'name' => 'Test Manufacturer'
);

$errors = BKGT_Validator::validate($test_data, 'manufacturer');

if (empty($errors)) {
    echo "✅ Validation passed!\n";
} else {
    echo "❌ Validation failed:\n";
    foreach ($errors as $field => $error) {
        echo "  {$field}: {$error}\n";
    }
}

echo "\nTest completed.\n";
?>