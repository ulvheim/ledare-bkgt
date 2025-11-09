<?php
// Load WordPress
require_once('wp-load.php');

// Test API documentation endpoints
echo "Testing BKGT API Documentation Endpoints\n";
echo "=========================================\n\n";

// Test 1: Get API documentation in HTML format
echo "Test 1: Getting API documentation (HTML format)\n";
$url = 'https://ledare.bkgt.se/wp-json/bkgt/v1/docs?format=html';
$args = array(
    'headers' => array(
        'X-API-Key' => '047619e3c335576a70fcd1f9929883ca'
    ),
    'method' => 'GET'
);

$response = wp_remote_request($url, $args);
if (is_wp_error($response)) {
    echo "❌ Error: " . $response->get_error_message() . "\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    echo "Status: $status_code\n";
    if ($status_code === 200) {
        $body = wp_remote_retrieve_body($response);
        echo "✅ Success! Documentation length: " . strlen($body) . " characters\n";
        if (strpos($body, '<html>') !== false) {
            echo "✅ HTML format confirmed\n";
        }
    } else {
        echo "❌ Failed with status $status_code\n";
    }
}

echo "\n";

// Test 2: Get API documentation in JSON format
echo "Test 2: Getting API documentation (JSON format)\n";
$url = 'https://ledare.bkgt.se/wp-json/bkgt/v1/docs?format=json';

$response = wp_remote_request($url, $args);
if (is_wp_error($response)) {
    echo "❌ Error: " . $response->get_error_message() . "\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    echo "Status: $status_code\n";
    if ($status_code === 200) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if ($data && isset($data['documentation'])) {
            echo "✅ Success! Documentation length: " . strlen($data['documentation']) . " characters\n";
            echo "✅ Last updated: " . (isset($data['last_updated']) ? date('Y-m-d H:i:s', $data['last_updated']) : 'unknown') . "\n";
        } else {
            echo "❌ Invalid JSON response\n";
        }
    } else {
        echo "❌ Failed with status $status_code\n";
    }
}

echo "\n";

// Test 3: Get API routes
echo "Test 3: Getting API routes\n";
$url = 'https://ledare.bkgt.se/wp-json/bkgt/v1/routes';

$response = wp_remote_request($url, $args);
if (is_wp_error($response)) {
    echo "❌ Error: " . $response->get_error_message() . "\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    echo "Status: $status_code\n";
    if ($status_code === 200) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if ($data && isset($data['routes'])) {
            echo "✅ Success! Found " . $data['total_routes'] . " routes\n";
            echo "✅ Namespace: " . $data['namespace'] . "\n";
            echo "✅ Generated at: " . $data['generated_at'] . "\n";

            // Show first few routes as examples
            $route_keys = array_keys($data['routes']);
            echo "Sample routes:\n";
            for ($i = 0; $i < min(5, count($route_keys)); $i++) {
                echo "  - " . $route_keys[$i] . "\n";
            }
            if (count($route_keys) > 5) {
                echo "  ... and " . (count($route_keys) - 5) . " more\n";
            }
        } else {
            echo "❌ Invalid response format\n";
        }
    } else {
        echo "❌ Failed with status $status_code\n";
    }
}

echo "\n";

// Test 4: Get detailed API routes
echo "Test 4: Getting detailed API routes\n";
$url = 'https://ledare.bkgt.se/wp-json/bkgt/v1/routes?detailed=true';

$response = wp_remote_request($url, $args);
if (is_wp_error($response)) {
    echo "❌ Error: " . $response->get_error_message() . "\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    echo "Status: $status_code\n";
    if ($status_code === 200) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if ($data && isset($data['routes'])) {
            echo "✅ Success! Detailed routes retrieved\n";
            echo "✅ Detailed mode: " . ($data['detailed'] ? 'true' : 'false') . "\n";
        } else {
            echo "❌ Invalid response format\n";
        }
    } else {
        echo "❌ Failed with status $status_code\n";
    }
}

echo "\nTest completed!\n";