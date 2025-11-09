<?php
/**
 * Check BKGT API Key in Remote Database
 * Connect to the production database and verify the API key exists
 */

// Database credentials from .env and wp-config.php
$host = 'mysql513.loopia.se';
$user = 'dbaadmin@b383837';
$password = 'Anna1Martin2';
$database = 'bkgt_se_db_1';

echo "<h1>Checking BKGT API Key in Production Database</h1>";
echo "<p>Database: <code>$database</code></p>";
echo "<p>Host: <code>$host</code></p>";
echo "<p>API Key to check: <code>047619e3c335576a70fcd1f9929883ca</code></p>";

// Connect to database
$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_error) {
    echo "<p style='color:red'>❌ Database connection failed: " . $mysqli->connect_error . "</p>";
    exit;
}

echo "<p style='color:green'>✅ Database connection successful</p>";

// Check for bkgt_api_keys table with different possible names
$possible_tables = array(
    'wp_bkgt_api_keys',
    'bkgt_api_keys',
    'wp_bkgt_api_keys', // Already checked
);

$table_found = false;
$actual_table_name = '';

foreach ($possible_tables as $table_name) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table_name'");
    if ($result->num_rows > 0) {
        $table_found = true;
        $actual_table_name = $table_name;
        break;
    }
}

if (!$table_found) {
    echo "<p style='color:red'>❌ bkgt_api_keys table does not exist with any common name</p>";

    // List all tables to see what's available
    echo "<h3>All Tables in Database:</h3><ul>";
    $tables_result = $mysqli->query("SHOW TABLES");
    while ($row = $tables_result->fetch_array()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
    $mysqli->close();
    exit;
}

echo "<p style='color:green'>✅ bkgt_api_keys table exists: <code>$actual_table_name</code></p>";

// Check if API key exists
$api_key = '047619e3c335576a70fcd1f9929883ca';
$query = "SELECT * FROM $actual_table_name WHERE api_key = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $api_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<p style='color:red'>❌ API key not found in database</p>";

    // Show all existing API keys (without revealing full keys for security)
    echo "<h3>Existing API Keys:</h3>";
    $all_keys_query = "SELECT id, LEFT(api_key, 8) as key_prefix, name, is_active, created_at FROM $actual_table_name";
    $all_keys_result = $mysqli->query($all_keys_query);

    if ($all_keys_result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Key Prefix</th><th>Name</th><th>Active</th><th>Created</th></tr>";
        while ($row = $all_keys_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['key_prefix']}...</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No API keys found in the table.</p>";
    }
} else {
    echo "<p style='color:green'>✅ API key found in database</p>";
    $row = $result->fetch_assoc();

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>{$row['id']}</td></tr>";
    echo "<tr><td>API Key</td><td><code>{$row['api_key']}</code></td></tr>";
    echo "<tr><td>Name</td><td>" . htmlspecialchars($row['name']) . "</td></tr>";
    echo "<tr><td>Created By</td><td>{$row['created_by']}</td></tr>";
    echo "<tr><td>Active</td><td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td></tr>";
    echo "<tr><td>Expires</td><td>" . ($row['expires_at'] ?: 'Never') . "</td></tr>";
    echo "<tr><td>Last Used</td><td>" . ($row['last_used'] ?: 'Never') . "</td></tr>";
    echo "<tr><td>Created</td><td>{$row['created_at']}</td></tr>";
    echo "</table>";
}

$stmt->close();
$mysqli->close();

echo "<hr>";
echo "<p><a href='https://ledare.bkgt.se/wp-content/plugins/bkgt-api/test-production-api.php'>Run Full Test Suite</a></p>";
?>