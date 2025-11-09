<?php
/**
 * DMS API Test Script
 * Run this in WordPress admin to test document management endpoints
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "<h1>DMS API Test</h1>";

// Test document categories endpoint
function test_document_categories() {
    echo "<h2>Testing Document Categories Endpoint</h2>";

    $url = rest_url('bkgt/v1/documents/categories');

    echo "<p>Testing URL: $url</p>";

    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Bearer test-token' // You'll need a valid token
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

// Test document templates endpoint
function test_document_templates() {
    echo "<h2>Testing Document Templates Endpoint</h2>";

    $url = rest_url('bkgt/v1/documents/templates');

    echo "<p>Testing URL: $url</p>";

    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Bearer test-token' // You'll need a valid token
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

// Test export formats endpoint
function test_export_formats() {
    echo "<h2>Testing Export Formats Endpoint</h2>";

    $url = rest_url('bkgt/v1/documents/export/formats');

    echo "<p>Testing URL: $url</p>";

    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Bearer test-token' // You'll need a valid token
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
test_document_categories();
test_document_templates();
test_export_formats();

echo "<p><strong>Note:</strong> These tests require valid authentication tokens. Update the Bearer token with a real JWT or API key to test fully.</p>";
?>