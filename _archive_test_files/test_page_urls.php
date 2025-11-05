<?php
echo "=== Testing All Page URLs ===\n\n";

// List of all page URLs to test
$pages_to_test = array(
    'Lag' => 'https://ledare.bkgt.se/?page_id=51',
    'Spelare' => 'https://ledare.bkgt.se/?page_id=52',
    'Utrustning' => 'https://ledare.bkgt.se/?page_id=15',
    'Dokument' => 'https://ledare.bkgt.se/?page_id=16',
    'Kommunikation' => 'https://ledare.bkgt.se/?page_id=17',
    'Utvärdering' => 'https://ledare.bkgt.se/?page_id=53',
    'Kontakt' => 'https://ledare.bkgt.se/?page_id=42',
    'Hantera Dokument' => 'https://ledare.bkgt.se/?page_id=19',
    'Hantera Utrustning' => 'https://ledare.bkgt.se/?page_id=18'
);

$success_count = 0;
$total_count = count($pages_to_test);

foreach ($pages_to_test as $page_name => $url) {
    echo "Testing: $page_name\n";
    echo "URL: $url\n";

    // Use curl to test the URL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing purposes
    curl_setopt($ch, CURLOPT_USERAGENT, 'BKGT Page Tester');

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "❌ CURL Error: $error\n";
    } elseif ($http_code == 200) {
        echo "✅ HTTP $http_code - Success\n";

        // Check if the response contains expected content
        if (strpos($response, '<!DOCTYPE html>') !== false) {
            echo "✅ Contains HTML content\n";
        } else {
            echo "⚠️ No HTML content found\n";
        }

        if (strpos($response, $page_name) !== false) {
            echo "✅ Page title/content found\n";
        } else {
            echo "⚠️ Page title not found in content\n";
        }

        if (strpos($response, 'wp-content') !== false) {
            echo "✅ WordPress assets loaded\n";
        } else {
            echo "⚠️ WordPress assets not found\n";
        }

        $success_count++;
    } else {
        echo "❌ HTTP $http_code - Failed\n";
        if ($response) {
            // Show first 200 chars of response for debugging
            echo "Response preview: " . substr($response, 0, 200) . "...\n";
        }
    }

    echo "\n" . str_repeat("-", 50) . "\n\n";
}

echo "=== Test Summary ===\n";
echo "Total pages tested: $total_count\n";
echo "Successful: $success_count\n";
echo "Failed: " . ($total_count - $success_count) . "\n";

if ($success_count == $total_count) {
    echo "\n🎉 ALL PAGES WORKING PERFECTLY!\n";
} else {
    echo "\n⚠️ Some pages have issues - check above for details.\n";
}
?>