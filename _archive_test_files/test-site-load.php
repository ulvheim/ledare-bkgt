<?php
// Test if the site loads without plugins
$url = 'https://ledare.bkgt.se';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'user_agent' => 'Test Script'
    ]
]);

echo "Testing site load with plugins...\n";
$response = file_get_contents($url, false, $context);

if ($response !== false) {
    echo "SUCCESS: Site loads without plugins!\n";
    echo "Response length: " . strlen($response) . " characters\n";

    // Check for critical error
    if (strpos($response, 'critical error') !== false || strpos($response, 'fatal error') !== false) {
        echo "WARNING: Still contains error messages\n";
    } else {
        echo "No error messages found in response\n";
    }
} else {
    echo "FAILED: Site does not load\n";
}
?>