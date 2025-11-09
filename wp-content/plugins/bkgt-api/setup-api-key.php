<?php
/**
 * Setup BKGT API Key
 * Manually insert the production API key into the database
 */

// Include WordPress
require_once('../../../wp-load.php');

echo "<h1>Setting up BKGT API Key</h1>";

global $wpdb;

// Check if table exists
$table_name = $wpdb->prefix . 'bkgt_api_keys';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if (!$table_exists) {
    echo "<p style='color:red'>❌ bkgt_api_keys table does not exist. Creating it...</p>";

    // Create the table
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        api_key varchar(64) NOT NULL,
        api_secret varchar(128) NOT NULL,
        name varchar(255) NOT NULL,
        permissions text,
        created_by bigint(20) unsigned NOT NULL,
        expires_at datetime DEFAULT NULL,
        last_used datetime DEFAULT NULL,
        is_active tinyint(1) NOT NULL DEFAULT 1,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY api_key (api_key),
        KEY created_by (created_by),
        KEY is_active (is_active)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

    if ($table_exists) {
        echo "<p style='color:green'>✅ bkgt_api_keys table created successfully</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to create bkgt_api_keys table</p>";
        exit;
    }
} else {
    echo "<p style='color:green'>✅ bkgt_api_keys table exists</p>";
}

// Check if API key already exists
$existing_key = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM $table_name WHERE api_key = %s",
    '047619e3c335576a70fcd1f9929883ca'
));

if ($existing_key) {
    echo "<p style='color:blue'>ℹ️ API key already exists in database</p>";
} else {
    // Get admin user ID
    $admin_user = get_users(array('role' => 'administrator', 'number' => 1));
    if (empty($admin_user)) {
        echo "<p style='color:red'>❌ No admin user found</p>";
        exit;
    }

    $admin_id = $admin_user[0]->ID;

    // Insert the API key
    $result = $wpdb->insert(
        $table_name,
        array(
            'api_key' => '047619e3c335576a70fcd1f9929883ca',
            'api_secret' => wp_hash('production_secret_' . time()),
            'name' => 'Production API Key - Updated',
            'permissions' => null,
            'created_by' => $admin_id,
            'expires_at' => null,
            'is_active' => 1,
        ),
        array('%s', '%s', '%s', '%s', '%d', '%s', '%d')
    );

    if ($result) {
        echo "<p style='color:green'>✅ API key inserted successfully</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to insert API key: " . $wpdb->last_error . "</p>";
    }
}

// Test the API key
echo "<h2>Testing API Key</h2>";

$url = rest_url('bkgt/v1/equipment/manufacturers');
echo "<p><strong>Testing URL:</strong> $url</p>";

$response = wp_remote_get($url, array(
    'headers' => array(
        'X-API-Key' => '047619e3c335576a70fcd1f9929883ca'
    )
));

$status_code = wp_remote_retrieve_response_code($response);
$body = wp_remote_retrieve_body($response);

echo "<p><strong>Status Code:</strong> $status_code</p>";

if ($status_code === 200) {
    echo "<p style='color:green'>✅ API key authentication working!</p>";
    $data = json_decode($body, true);
    $count = isset($data['manufacturers']) ? count($data['manufacturers']) : 0;
    echo "<p>Retrieved $count manufacturers</p>";
} else {
    echo "<p style='color:red'>❌ API key authentication failed</p>";
    echo "<pre>" . htmlspecialchars($body) . "</pre>";
}

echo "<hr><p><a href='" . home_url('wp-content/plugins/bkgt-api/test-production-api.php') . "'>Run Full Test Suite</a></p>";
?>