<?php
echo "=== Detailed Page Content Check ===\n\n";

// Test one specific page in detail
$url = 'https://ledare.bkgt.se/?page_id=51'; // Lag page
echo "Testing: $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'BKGT Page Tester');

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $http_code\n";
echo "Content Type: $content_type\n";

if ($error) {
    echo "Error: $error\n";
} else {
    // Check for key indicators
    $indicators = array(
        '<!DOCTYPE html>' => 'HTML document',
        '<title>' => 'Page title tag',
        'wp-content' => 'WordPress assets',
        'Lag' => 'Page title in content',
        'bkgt-ledare' => 'Theme assets',
        '<body' => 'Body tag',
        '</html>' => 'HTML closing tag'
    );

    echo "\nContent Indicators:\n";
    foreach ($indicators as $indicator => $description) {
        if (strpos($response, $indicator) !== false) {
            echo "✅ $description found\n";
        } else {
            echo "❌ $description NOT found\n";
        }
    }

    // Show first 500 characters
    echo "\nFirst 500 characters of response:\n";
    echo "----------------------------------------\n";
    echo substr($response, 0, 500) . "\n";
    echo "----------------------------------------\n";

    // Check content length
    echo "\nContent Length: " . strlen($response) . " characters\n";

    if (strlen($response) < 1000) {
        echo "\n⚠️ Response is very short - might indicate an issue\n";
        echo "Full response:\n";
        echo $response . "\n";
    }
}
?>