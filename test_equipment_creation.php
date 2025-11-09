<?php
// Load WordPress
require_once('wp-load.php');

// Test equipment creation
$url = 'https://ledare.bkgt.se/wp-json/bkgt/v1/equipment';
$api_key = '047619e3c335576a70fcd1f9929883ca';

$data = array(
    'title' => 'Test Football',
    'manufacturer_id' => 1,
    'item_type_id' => 1,
    'size' => 'Medium',
    'unique_identifier' => 'TEST-001'
);

$args = array(
    'headers' => array(
        'X-API-Key' => $api_key,
        'Content-Type' => 'application/json'
    ),
    'body' => json_encode($data),
    'method' => 'POST'
);

$response = wp_remote_request($url, $args);

if (is_wp_error($response)) {
    echo "Error: " . $response->get_error_message();
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    echo "Status: $status_code\n";
    echo "Response: $body\n";
}