<?php
/**
 * Check BKGT API Key in Database
 * Verify that the production API key exists in the database
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "<h1>Checking BKGT API Key in Database</h1>";
echo "<p>API Key to check: <code>f1d0f6be40b1d78d3ac876b7be41e792</code></p>";

global $wpdb;

// Check if table exists
$table_name = $wpdb->prefix . 'bkgt_api_keys';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

echo "<h2>Database Table Status</h2>";
if (!$table_exists) {
    echo "<p style='color:red'>❌ bkgt_api_keys table does not exist</p>";
    echo "<p>This table should have been created when the plugin was activated.</p>";
    exit;
} else {
    echo "<p style='color:green'>✅ bkgt_api_keys table exists</p>";
}

// Check if API key exists
$api_key = 'f1d0f6be40b1d78d3ac876b7be41e792';
$key_data = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE api_key = %s",
    $api_key
));

echo "<h2>API Key Status</h2>";
if (!$key_data) {
    echo "<p style='color:red'>❌ API key not found in database</p>";
    echo "<p>The API key <code>$api_key</code> is not present in the bkgt_api_keys table.</p>";

    // Show all existing keys (without revealing full keys for security)
    $all_keys = $wpdb->get_results("SELECT id, LEFT(api_key, 8) as key_prefix, name, is_active, created_at FROM $table_name");
    echo "<h3>Existing API Keys:</h3>";
    if (empty($all_keys)) {
        echo "<p>No API keys found in database.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Key Prefix</th><th>Name</th><th>Active</th><th>Created</th></tr>";
        foreach ($all_keys as $key) {
            echo "<tr>";
            echo "<td>{$key->id}</td>";
            echo "<td>{$key->key_prefix}...</td>";
            echo "<td>" . esc_html($key->name) . "</td>";
            echo "<td>" . ($key->is_active ? 'Yes' : 'No') . "</td>";
            echo "<td>{$key->created_at}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color:green'>✅ API key found in database</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>{$key_data->id}</td></tr>";
    echo "<tr><td>API Key</td><td><code>{$key_data->api_key}</code></td></tr>";
    echo "<tr><td>Name</td><td>" . esc_html($key_data->name) . "</td></tr>";
    echo "<tr><td>Created By</td><td>{$key_data->created_by}</td></tr>";
    echo "<tr><td>Active</td><td>" . ($key_data->is_active ? 'Yes' : 'No') . "</td></tr>";
    echo "<tr><td>Expires</td><td>" . ($key_data->expires_at ?: 'Never') . "</td></tr>";
    echo "<tr><td>Last Used</td><td>" . ($key_data->last_used ?: 'Never') . "</td></tr>";
    echo "<tr><td>Created</td><td>{$key_data->created_at}</td></tr>";
    echo "</table>";
}

// Test authentication
echo "<h2>Authentication Test</h2>";
$url = rest_url('bkgt/v1/equipment/manufacturers');
echo "<p><strong>Testing URL:</strong> $url</p>";

$response = wp_remote_get($url, array(
    'headers' => array(
        'X-API-Key' => $api_key
    )
));

$status_code = wp_remote_retrieve_response_code($response);
$body = wp_remote_retrieve_body($response);

echo "<p><strong>Status Code:</strong> $status_code</p>";

if ($status_code === 200) {
    echo "<p style='color:green'>✅ Authentication successful!</p>";
    $data = json_decode($body, true);
    $count = isset($data['manufacturers']) ? count($data['manufacturers']) : 0;
    echo "<p>Retrieved $count manufacturers</p>";
} elseif ($status_code === 401) {
    echo "<p style='color:red'>❌ Authentication failed - API key not recognized</p>";
    $error_data = json_decode($body, true);
    if ($error_data && isset($error_data['message'])) {
        echo "<p><strong>Error:</strong> " . esc_html($error_data['message']) . "</p>";
    }
} else {
    echo "<p style='color:orange'>⚠️ Unexpected status code</p>";
    echo "<pre>" . htmlspecialchars($body) . "</pre>";
}

echo "<hr>";
echo "<p><a href='" . home_url('wp-content/plugins/bkgt-api/test-production-api.php') . "'>Run Full Test Suite</a></p>";
?>