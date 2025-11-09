<?php
/**
 * Test API Key Authentication
 * Test the provided API key with equipment endpoints
 */

// Include WordPress
require_once('../../../wp-load.php');

// Test API key with equipment manufacturers endpoint
function test_api_key_auth() {
    echo "<h1>Testing API Key Authentication</h1>";
    echo "<p>API Key: f1d0f6be40b1d78d3ac876b7be41e792</p>";

    // Test equipment manufacturers endpoint
    $url = rest_url('bkgt/v1/equipment/manufacturers');

    echo "<h2>Testing Equipment Manufacturers Endpoint</h2>";
    echo "<p>URL: $url</p>";

    $response = wp_remote_get($url, array(
        'headers' => array(
            'X-API-Key' => 'f1d0f6be40b1d78d3ac876b7be41e792'
        )
    ));

    if (is_wp_error($response)) {
        echo "<p style='color: red;'>Error: " . $response->get_error_message() . "</p>";
        return;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    echo "<p>Response Code: <strong>$status_code</strong></p>";

    if ($status_code === 200) {
        echo "<p style='color: green;'>✅ Authentication successful!</p>";
        $data = json_decode($body, true);
        echo "<p>Response contains " . count($data['manufacturers'] ?? []) . " manufacturers</p>";
    } else {
        echo "<p style='color: red;'>❌ Authentication failed!</p>";
        echo "<pre>" . htmlspecialchars($body) . "</pre>";
    }

    // Test equipment types endpoint
    echo "<h2>Testing Equipment Types Endpoint</h2>";
    $url2 = rest_url('bkgt/v1/equipment/types');
    echo "<p>URL: $url2</p>";

    $response2 = wp_remote_get($url2, array(
        'headers' => array(
            'X-API-Key' => 'f1d0f6be40b1d78d3ac876b7be41e792'
        )
    ));

    if (is_wp_error($response2)) {
        echo "<p style='color: red;'>Error: " . $response2->get_error_message() . "</p>";
        return;
    }

    $status_code2 = wp_remote_retrieve_response_code($response2);
    $body2 = wp_remote_retrieve_body($response2);

    echo "<p>Response Code: <strong>$status_code2</strong></p>";

    if ($status_code2 === 200) {
        echo "<p style='color: green;'>✅ Authentication successful!</p>";
        $data2 = json_decode($body2, true);
        echo "<p>Response contains " . count($data2['types'] ?? []) . " equipment types</p>";
    } else {
        echo "<p style='color: red;'>❌ Authentication failed!</p>";
        echo "<pre>" . htmlspecialchars($body2) . "</pre>";
    }
}

// Test without API key (should fail)
function test_without_auth() {
    echo "<h2>Testing Without Authentication (Should Fail)</h2>";

    $url = rest_url('bkgt/v1/equipment/manufacturers');

    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        echo "<p style='color: red;'>Error: " . $response->get_error_message() . "</p>";
        return;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    echo "<p>Response Code: <strong>$status_code</strong></p>";

    if ($status_code === 401) {
        echo "<p style='color: green;'>✅ Correctly requires authentication!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Unexpected response code</p>";
        echo "<pre>" . htmlspecialchars($body) . "</pre>";
    }
}

// Run tests
test_api_key_auth();
echo "<hr>";
test_without_auth();
?></content>
<parameter name="filePath">c:\Users\Olheim\Desktop\GH\ledare-bkgt\wp-content\plugins\bkgt-api\test-api-key-auth.php