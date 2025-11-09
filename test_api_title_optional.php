<?php
/**
 * Test script to verify equipment creation without title parameter
 */

require_once('wp-load.php');

// Test data - no title provided
$test_data = array(
    'manufacturer_id' => 1, // Assuming manufacturer exists
    'item_type_id' => 1,    // Assuming item type exists
    'storage_location' => 'Test Location',
    'condition_status' => 'normal'
);

// Get API key from database
global $wpdb;
$api_key = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}bkgt_api_keys WHERE is_active = 1 LIMIT 1");

if (!$api_key) {
    echo "❌ ERROR: No active API key found\n";
    exit(1);
}

// Test the API endpoint
$url = get_site_url() . '/wp-json/bkgt/v1/equipment';
$headers = array(
    'Content-Type' => 'application/json',
    'X-API-Key' => $api_key->api_key,
    'X-API-Secret' => $api_key->api_secret
);

echo "Testing API endpoint: $url\n";
echo "Using API Key: " . substr($api_key->api_key, 0, 10) . "...\n";
echo "Test data (no title provided):\n";
echo json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

$response = wp_remote_post($url, array(
    'headers' => $headers,
    'body' => json_encode($test_data),
    'timeout' => 30
));

if (is_wp_error($response)) {
    echo "❌ ERROR: " . $response->get_error_message() . "\n";
} else {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    echo "Response Code: " . wp_remote_retrieve_response_code($response) . "\n";
    echo "Response Body:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";

    if (wp_remote_retrieve_response_code($response) === 201) {
        if (isset($data['title'])) {
            echo "\n✅ SUCCESS: Title was auto-generated: '" . $data['title'] . "'\n";
            echo "✅ Equipment creation without title parameter works correctly!\n";
        } else {
            echo "\n❌ ERROR: No title found in response\n";
        }
    } else {
        echo "\n❌ ERROR: API call failed with code " . wp_remote_retrieve_response_code($response) . "\n";
    }
}
?>