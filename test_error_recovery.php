<?php
require_once('wp-load.php');

// Test the error recovery system
echo "Testing error recovery system...\n";

try {
    // Trigger a test exception
    throw new Exception("Test error to check serialization");
} catch (Exception $e) {
    echo "Exception caught and handled successfully!\n";
    echo "Error message: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";
?>