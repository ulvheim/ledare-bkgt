<?php
/**
 * Insert New API Key
 * Directly insert the new API key into the database
 */

// Database connection details
$host = 'mysql513.loopia.se';
$user = 'dbaadmin@b383837';
$password = 'Anna1Martin2';
$database = 'bkgt_se_db_1';

echo "<h1>Inserting New BKGT API Key</h1>";
echo "<p>API Key: <code>047619e3c335576a70fcd1f9929883ca</code></p>";

// Connect to database
$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_error) {
    echo "<p style='color:red'>❌ Database connection failed: " . $mysqli->connect_error . "</p>";
    exit;
}

echo "<p style='color:green'>✅ Database connection successful</p>";

// Check for bkgt_api_keys table
$table_name = 'wp_bkgt_api_keys';
$result = $mysqli->query("SHOW TABLES LIKE '$table_name'");

if ($result->num_rows == 0) {
    echo "<p style='color:red'>❌ bkgt_api_keys table does not exist</p>";
    $mysqli->close();
    exit;
}

echo "<p style='color:green'>✅ bkgt_api_keys table exists</p>";

// Check if API key already exists
$api_key = '047619e3c335576a70fcd1f9929883ca';
$query = "SELECT id FROM $table_name WHERE api_key = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $api_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<p style='color:blue'>ℹ️ API key already exists in database</p>";
} else {
    // Insert the new API key
    $insert_query = "INSERT INTO $table_name (api_key, api_secret, name, permissions, created_by, is_active, created_at) VALUES (?, ?, ?, NULL, 1, 1, NOW())";
    $stmt = $mysqli->prepare($insert_query);

    $api_secret = md5('production_secret_' . time());
    $name = 'Production API Key - ' . date('Y-m-d H:i:s');

    $stmt->bind_param("sss", $api_key, $api_secret, $name);

    if ($stmt->execute()) {
        echo "<p style='color:green'>✅ API key inserted successfully</p>";
        echo "<p>Name: <code>$name</code></p>";
    } else {
        echo "<p style='color:red'>❌ Failed to insert API key: " . $stmt->error . "</p>";
    }
}

$stmt->close();
$mysqli->close();

echo "<hr>";
echo "<p><a href='https://ledare.bkgt.se/wp-content/plugins/bkgt-api/test-production-api.php' target='_blank'>Run Full Test Suite</a></p>";
?>