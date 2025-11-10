<?php
/**
 * Test script for SWE3 scraper and BKGT equipment API fixes
 */

require_once('wp-load.php');

// Test SWE3 scraper
echo "Testing SWE3 Scraper...\n";

$scraper = new BKGT_SWE3_Scraper();
$documents = $scraper->scrape_rules_page();

if (empty($documents)) {
    echo "❌ SWE3 scraper returned no documents\n";
} else {
    echo "✅ SWE3 scraper found " . count($documents) . " documents:\n";
    foreach ($documents as $doc) {
        echo "  - " . $doc['title'] . " (" . $doc['type'] . ")\n";
    }
}

echo "\n";

// Test equipment API
echo "Testing Equipment API...\n";

// First, get an existing equipment item
$equipment_id = 1; // Assuming there's at least one item

$api_url = get_rest_url() . 'bkgt/v1/equipment/' . $equipment_id;
$token = 'test-token'; // You'll need to set this to a valid token

$args = array(
    'headers' => array(
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json',
    ),
    'body' => json_encode(array(
        'manufacturer_id' => 1,
        'item_type_id' => 2,
        'location_id' => 3,
        'purchase_date' => '2024-01-15',
        'purchase_price' => 1500.00,
        'warranty_expiry' => '2026-01-15',
        'serial_number' => 'TEST123',
        'notes' => 'Updated via API test',
    )),
    'method' => 'PUT',
);

$response = wp_remote_request($api_url, $args);

if (is_wp_error($response)) {
    echo "❌ Equipment API request failed: " . $response->get_error_message() . "\n";
} else {
    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($response_code === 200) {
        echo "✅ Equipment API update successful\n";
        $data = json_decode($body, true);
        echo "Updated fields: " . implode(', ', array_keys($data)) . "\n";
    } else {
        echo "❌ Equipment API returned error " . $response_code . ": " . $body . "\n";
    }
}

echo "\nTest completed.\n";