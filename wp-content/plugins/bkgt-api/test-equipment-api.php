<?php
/**
 * Equipment API Test Script
 * Run this in WordPress admin to test equipment endpoints
 */

// Include WordPress
require_once('../../../wp-load.php');

// Test the preview identifier endpoint
function test_preview_identifier() {
    echo "<h2>Testing Equipment Preview Identifier Endpoint</h2>";

    // Test with manufacturer_id=1, item_type_id=1
    $url = rest_url('bkgt/v1/equipment/preview-identifier') . '?manufacturer_id=1&item_type_id=1';

    echo "<p>Testing URL: $url</p>";

    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . 'test-token' // You'll need a valid token
        )
    ));

    if (is_wp_error($response)) {
        echo "<p>Error: " . $response->get_error_message() . "</p>";
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    echo "<p>Response Code: " . wp_remote_retrieve_response_code($response) . "</p>";
    echo "<pre>" . print_r($data, true) . "</pre>";
}

// Test equipment listing
function test_equipment_listing() {
    echo "<h2>Testing Equipment Listing Endpoint</h2>";

    $url = rest_url('bkgt/v1/equipment');

    echo "<p>Testing URL: $url</p>";

    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . 'test-token' // You'll need a valid token
        )
    ));

    if (is_wp_error($response)) {
        echo "<p>Error: " . $response->get_error_message() . "</p>";
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    echo "<p>Response Code: " . wp_remote_retrieve_response_code($response) . "</p>";
    echo "<pre>" . print_r($data, true) . "</pre>";
}

// Run tests
if (isset($_GET['test'])) {
    switch ($_GET['test']) {
        case 'preview':
            test_preview_identifier();
            break;
        case 'list':
            test_equipment_listing();
            break;
        default:
            echo "<p>Available tests: ?test=preview, ?test=list</p>";
    }
} else {
    echo "<h1>Equipment API Test</h1>";
    echo "<p><a href='?test=preview'>Test Preview Identifier</a></p>";
    echo "<p><a href='?test=list'>Test Equipment Listing</a></p>";
}
?>