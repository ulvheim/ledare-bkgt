<?php
/**
 * Flush All BKGT API Keys
 * Removes all existing API keys from the database to start fresh
 */

// Database credentials from .env and wp-config.php
$host = 'mysql513.loopia.se';
$user = 'dbaadmin@b383837';
$password = 'Anna1Martin2';
$database = 'bkgt_se_db_1';

echo "<h1>Flushing All BKGT API Keys</h1>";
echo "<p>Database: <code>$database</code></p>";
echo "<p>Host: <code>$host</code></p>";

// Connect to database
$mysqli = new mysqli($host, $user, $password, $database);

if ($mysqli->connect_error) {
    echo "<p style='color:red'>❌ Database connection failed: " . $mysqli->connect_error . "</p>";
    exit;
}

echo "<p style='color:green'>✅ Database connection successful</p>";

// Check for bkgt_api_keys table
$possible_tables = array(
    'wp_bkgt_api_keys',
    'bkgt_api_keys',
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
    echo "<p style='color:orange'>⚠️ bkgt_api_keys table does not exist. Creating it...</p>";

    // Create the table
    $create_table_sql = "
    CREATE TABLE `wp_bkgt_api_keys` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `api_key` varchar(255) NOT NULL,
        `name` varchar(255) NOT NULL,
        `permissions` text,
        `created_by` bigint(20) unsigned NOT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `expires_at` datetime DEFAULT NULL,
        `last_used` datetime DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `api_key` (`api_key`),
        KEY `created_by` (`created_by`),
        KEY `is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    if ($mysqli->query($create_table_sql)) {
        echo "<p style='color:green'>✅ bkgt_api_keys table created successfully</p>";
        $actual_table_name = 'wp_bkgt_api_keys';
    } else {
        echo "<p style='color:red'>❌ Failed to create table: " . $mysqli->error . "</p>";
        $mysqli->close();
        exit;
    }
} else {
    echo "<p style='color:green'>✅ bkgt_api_keys table exists: <code>$actual_table_name</code></p>";
}

// Count existing keys before deletion
$count_query = "SELECT COUNT(*) as total FROM $actual_table_name";
$count_result = $mysqli->query($count_query);
$count_row = $count_result->fetch_assoc();
$total_keys = $count_row['total'];

echo "<p>Found <strong>$total_keys</strong> existing API keys</p>";

// Show existing keys before deletion
if ($total_keys > 0) {
    echo "<h3>Existing API Keys (before deletion):</h3>";
    $existing_keys_query = "SELECT id, LEFT(api_key, 8) as key_prefix, name, is_active, created_at FROM $actual_table_name ORDER BY created_at DESC";
    $existing_keys_result = $mysqli->query($existing_keys_query);

    if ($existing_keys_result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Key Prefix</th><th>Name</th><th>Active</th><th>Created</th></tr>";
        while ($row = $existing_keys_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['key_prefix']}...</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// Delete all existing keys
$delete_query = "DELETE FROM $actual_table_name";
if ($mysqli->query($delete_query)) {
    echo "<p style='color:green'>✅ Successfully deleted all $total_keys API keys</p>";
} else {
    echo "<p style='color:red'>❌ Failed to delete API keys: " . $mysqli->error . "</p>";
}

// Reset auto-increment
$reset_query = "ALTER TABLE $actual_table_name AUTO_INCREMENT = 1";
if ($mysqli->query($reset_query)) {
    echo "<p style='color:green'>✅ Auto-increment reset to 1</p>";
} else {
    echo "<p style='color:orange'>⚠️ Could not reset auto-increment: " . $mysqli->error . "</p>";
}

// Verify table is empty
$verify_query = "SELECT COUNT(*) as total FROM $actual_table_name";
$verify_result = $mysqli->query($verify_query);
$verify_row = $verify_result->fetch_assoc();
$remaining_keys = $verify_row['total'];

if ($remaining_keys == 0) {
    echo "<p style='color:green'>✅ Verification: Table is now empty (0 keys remaining)</p>";
} else {
    echo "<p style='color:red'>❌ Verification failed: $remaining_keys keys still remain</p>";
}

$mysqli->close();

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li><a href='https://ledare.bkgt.se/wp-admin/admin.php?page=bkgt-api-keys'>Go to API Keys admin page</a> to create a new API key</li>";
echo "<li><a href='https://ledare.bkgt.se/wp-content/plugins/bkgt-api/test-production-api.php'>Test the new API key</a> with the production test suite</li>";
echo "</ol>";
?>