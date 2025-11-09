<?php
/**
 * BKGT API Production Test Script
 * Tests all endpoints with authentication to verify deployment
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "<!DOCTYPE html><html><head><title>BKGT API Production Test</title><style>
body { font-family: Arial, sans-serif; margin: 20px; }
.test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
.success { background-color: #d4edda; border-color: #c3e6cb; }
.error { background-color: #f8d7da; border-color: #f5c6cb; }
.warning { background-color: #fff3cd; border-color: #ffeaa7; }
h2 { color: #333; margin-top: 0; }
h3 { color: #666; margin-bottom: 10px; }
pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
.status { font-weight: bold; }
.pass { color: #28a745; }
.fail { color: #dc3545; }
</style></head><body>";

echo "<h1>🔍 BKGT API Production Test Suite</h1>";
echo "<p><strong>Test Date:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>API Key:</strong> 047619e3c335576a70fcd1f9929883ca</p>";
echo "<hr>";

// Test 1: API Key Authentication
echo "<div class='test-section success'>";
echo "<h2>🧪 Test 1: API Key Authentication</h2>";
echo "<h3>Testing Equipment Manufacturers Endpoint</h3>";

$url = rest_url('bkgt/v1/equipment/manufacturers');
echo "<p><strong>URL:</strong> $url</p>";
echo "<p><strong>Method:</strong> GET</p>";
echo "<p><strong>Headers:</strong> X-API-Key: 047619e3c335576a70fcd1f9929883ca</p>";

$response = wp_remote_get($url, array(
    'headers' => array(
        'X-API-Key' => '047619e3c335576a70fcd1f9929883ca'
    )
));

$status_code = wp_remote_retrieve_response_code($response);
$body = wp_remote_retrieve_body($response);

echo "<p><strong>Status Code:</strong> <span class='status " . ($status_code === 200 ? 'pass' : 'fail') . "'>$status_code</span></p>";

if ($status_code === 200) {
    $data = json_decode($body, true);
    $manufacturer_count = isset($data['manufacturers']) ? count($data['manufacturers']) : 0;
    echo "<p><strong>Result:</strong> <span class='pass'>✅ SUCCESS</span> - Retrieved $manufacturer_count manufacturers</p>";
    echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
} else {
    echo "<p><strong>Result:</strong> <span class='fail'>❌ FAILED</span></p>";
    echo "<pre>" . htmlspecialchars($body) . "</pre>";
}
echo "</div>";

// Test 2: Authentication Required (should fail)
echo "<div class='test-section " . ($status_code === 401 ? 'success' : 'error') . "'>";
echo "<h2>🧪 Test 2: Authentication Required</h2>";
echo "<h3>Testing Without Authentication (Should Fail)</h3>";

$url2 = rest_url('bkgt/v1/equipment/manufacturers');
echo "<p><strong>URL:</strong> $url2</p>";
echo "<p><strong>Method:</strong> GET</p>";
echo "<p><strong>Headers:</strong> None</p>";

$response2 = wp_remote_get($url2);
$status_code2 = wp_remote_retrieve_response_code($response2);
$body2 = wp_remote_retrieve_body($response2);

echo "<p><strong>Status Code:</strong> <span class='status " . ($status_code2 === 401 ? 'pass' : 'fail') . "'>$status_code2</span></p>";

if ($status_code2 === 401) {
    echo "<p><strong>Result:</strong> <span class='pass'>✅ SUCCESS</span> - Authentication properly required</p>";
} else {
    echo "<p><strong>Result:</strong> <span class='fail'>❌ FAILED</span> - Authentication not enforced</p>";
    echo "<pre>" . htmlspecialchars($body2) . "</pre>";
}
echo "</div>";

// Test 3: Equipment Types Endpoint
echo "<div class='test-section success'>";
echo "<h2>🧪 Test 3: Equipment Types Endpoint</h2>";

$url3 = rest_url('bkgt/v1/equipment/types');
echo "<p><strong>URL:</strong> $url3</p>";

$response3 = wp_remote_get($url3, array(
    'headers' => array(
        'X-API-Key' => 'f1d0f6be40b1d78d3ac876b7be41e792'
    )
));

$status_code3 = wp_remote_retrieve_response_code($response3);
$body3 = wp_remote_retrieve_body($response3);

echo "<p><strong>Status Code:</strong> <span class='status " . ($status_code3 === 200 ? 'pass' : 'fail') . "'>$status_code3</span></p>";

if ($status_code3 === 200) {
    $data3 = json_decode($body3, true);
    $type_count = isset($data3['types']) ? count($data3['types']) : 0;
    echo "<p><strong>Result:</strong> <span class='pass'>✅ SUCCESS</span> - Retrieved $type_count equipment types</p>";
} else {
    echo "<p><strong>Result:</strong> <span class='fail'>❌ FAILED</span></p>";
    echo "<pre>" . htmlspecialchars($body3) . "</pre>";
}
echo "</div>";

// Test 4: Teams Endpoint
echo "<div class='test-section success'>";
echo "<h2>🧪 Test 4: Teams Endpoint</h2>";

$url4 = rest_url('bkgt/v1/teams');
echo "<p><strong>URL:</strong> $url4</p>";

$response4 = wp_remote_get($url4, array(
    'headers' => array(
        'X-API-Key' => 'f1d0f6be40b1d78d3ac876b7be41e792'
    )
));

$status_code4 = wp_remote_retrieve_response_code($response4);
$body4 = wp_remote_retrieve_body($response4);

echo "<p><strong>Status Code:</strong> <span class='status " . ($status_code4 === 200 ? 'pass' : 'fail') . "'>$status_code4</span></p>";

if ($status_code4 === 200) {
    $data4 = json_decode($body4, true);
    $team_count = isset($data4['teams']) ? count($data4['teams']) : 0;
    echo "<p><strong>Result:</strong> <span class='pass'>✅ SUCCESS</span> - Retrieved $team_count teams</p>";
} else {
    echo "<p><strong>Result:</strong> <span class='fail'>❌ FAILED</span></p>";
    echo "<pre>" . htmlspecialchars($body4) . "</pre>";
}
echo "</div>";

// Test 5: Stats Endpoint
echo "<div class='test-section success'>";
echo "<h2>🧪 Test 5: Statistics Endpoint</h2>";

$url5 = rest_url('bkgt/v1/stats/overview');
echo "<p><strong>URL:</strong> $url5</p>";

$response5 = wp_remote_get($url5, array(
    'headers' => array(
        'X-API-Key' => 'f1d0f6be40b1d78d3ac876b7be41e792'
    )
));

$status_code5 = wp_remote_retrieve_response_code($response5);
$body5 = wp_remote_retrieve_body($response5);

echo "<p><strong>Status Code:</strong> <span class='status " . ($status_code5 === 200 ? 'pass' : 'fail') . "'>$status_code5</span></p>";

if ($status_code5 === 200) {
    $data5 = json_decode($body5, true);
    echo "<p><strong>Result:</strong> <span class='pass'>✅ SUCCESS</span> - Retrieved system statistics</p>";
    echo "<pre>" . htmlspecialchars(json_encode($data5, JSON_PRETTY_PRINT)) . "</pre>";
} else {
    echo "<p><strong>Result:</strong> <span class='fail'>❌ FAILED</span></p>";
    echo "<pre>" . htmlspecialchars($body5) . "</pre>";
}
echo "</div>";

// Summary
echo "<div class='test-section warning'>";
echo "<h2>📊 Test Summary</h2>";
$tests = [
    ['API Key Authentication', $status_code === 200],
    ['Authentication Required', $status_code2 === 401],
    ['Equipment Types', $status_code3 === 200],
    ['Teams Endpoint', $status_code4 === 200],
    ['Statistics', $status_code5 === 200]
];

$passed = 0;
$total = count($tests);

foreach ($tests as $test) {
    $status = $test[1] ? '✅ PASS' : '❌ FAIL';
    echo "<p><strong>{$test[0]}:</strong> $status</p>";
    if ($test[1]) $passed++;
}

echo "<hr>";
echo "<p><strong>Overall Result:</strong> <span class='status " . ($passed === $total ? 'pass' : 'fail') . "'>$passed / $total tests passed</span></p>";

if ($passed === $total) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>🎉 ALL TESTS PASSED!</h3>";
    echo "<p>The BKGT API is properly secured and functioning correctly.</p>";
    echo "<p><strong>API Key:</strong> f1d0f6be40b1d78d3ac876b7be41e792</p>";
    echo "<p><strong>Status:</strong> Ready for production use</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>⚠️ SOME TESTS FAILED</h3>";
    echo "<p>Please check the failed tests above and verify the deployment.</p>";
    echo "</div>";
}

echo "</div>";

echo "</body></html>";
?></content>
<parameter name="filePath">c:\Users\Olheim\Desktop\GH\ledare-bkgt\wp-content\plugins\bkgt-api\test-production-api.php