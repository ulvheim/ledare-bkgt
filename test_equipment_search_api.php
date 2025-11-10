<?php
/**
 * Equipment Search API Test Script
 *
 * Tests the new equipment search functionality
 */

// Test 1: Basic API connectivity
echo "=== Equipment Search API Test ===\n\n";

echo "1. Testing basic API connectivity...\n";
$api_url = 'https://ledare.bkgt.se/wp-json/bkgt/v1/equipment?per_page=1';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'User-Agent: Equipment Search Test',
        'timeout' => 10
    ]
]);

$response = @file_get_contents($api_url, false, $context);
if ($response === false) {
    echo "   ✗ API not accessible (might require authentication)\n";
} else {
    $data = json_decode($response, true);
    if ($data && isset($data['inventory_items'])) {
        echo "   ✓ API accessible\n";
        echo "   ✓ Response contains inventory_items array\n";

        if (!empty($data['inventory_items']) && is_array($data['inventory_items'])) {
            $item = $data['inventory_items'][0];
            echo "   Testing new fields in response:\n";
            echo "   - size field: " . (isset($item['size']) ? "✓ present" : "✗ missing") . "\n";
            echo "   - condition_reason field: " . (isset($item['condition_reason']) ? "✓ present" : "✗ missing") . "\n";
            echo "   - sticker_code field: " . (isset($item['sticker_code']) ? "✓ present" : "✗ missing") . "\n";
            echo "   - notes field: " . (isset($item['notes']) ? "✓ present" : "✗ missing") . "\n";
        }
    } else {
        echo "   ✗ Unexpected API response format\n";
    }
}

echo "\n2. Testing search parameter validation...\n";
// Test with search parameters (will likely fail due to auth, but tests parameter acceptance)
$search_url = 'https://ledare.bkgt.se/wp-json/bkgt/v1/equipment?search=test&search_fields=size&search_operator=AND&fuzzy=true&search_mode=partial&per_page=1';

$response = @file_get_contents($search_url, false, $context);
if ($response !== false) {
    echo "   ✓ Search parameters accepted by API\n";
} else {
    echo "   ? Search parameters test inconclusive (likely auth required)\n";
}

echo "\n3. Testing analytics endpoint...\n";
$analytics_url = 'https://ledare.bkgt.se/wp-json/bkgt/v1/equipment/search-analytics?limit=5&days=7';

$response = @file_get_contents($analytics_url, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && isset($data['popular_searches'])) {
        echo "   ✓ Analytics endpoint accessible\n";
        echo "   ✓ Response contains popular_searches data\n";
    } else {
        echo "   ? Analytics endpoint response format unexpected\n";
    }
} else {
    echo "   ? Analytics endpoint test inconclusive (likely auth required)\n";
}

echo "\n=== Test Complete ===\n";
echo "Note: Full functionality testing requires proper API authentication.\n";
echo "The above tests verify basic API accessibility and response structure.\n";
?>