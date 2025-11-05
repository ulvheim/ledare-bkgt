<?php
// Comprehensive database test
echo "=== Database Connection Test ===\n";

$host = 'mysql513.loopia.se';
$user = 'dbaadmin@b383837';
$pass = 'Anna1Martin2';
$db = 'bkgt_se_db_1';

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error() . "\n");
}
echo "✓ Database connection successful\n";

// Test basic queries
$result = mysqli_query($conn, "SHOW TABLES");
if ($result) {
    $count = mysqli_num_rows($result);
    echo "✓ Found $count tables\n";

    // List tables
    echo "Tables:\n";
    while ($row = mysqli_fetch_row($result)) {
        echo "  - " . $row[0] . "\n";
    }
} else {
    echo "✗ Failed to list tables: " . mysqli_error($conn) . "\n";
}

// Test WordPress options
$result = mysqli_query($conn, "SELECT option_name, option_value FROM wp_options WHERE option_name IN ('siteurl', 'home', 'active_plugins', 'stylesheet', 'template')");
if ($result) {
    echo "\n=== WordPress Options ===\n";
    while ($row = mysqli_fetch_assoc($result)) {
        $value = strlen($row['option_value']) > 50 ? substr($row['option_value'], 0, 50) . '...' : $row['option_value'];
        echo $row['option_name'] . ": " . $value . "\n";
    }
} else {
    echo "✗ Failed to read WordPress options: " . mysqli_error($conn) . "\n";
}

// Test if plugins are active
$result = mysqli_query($conn, "SELECT option_value FROM wp_options WHERE option_name = 'active_plugins'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $active_plugins = unserialize($row['option_value']);
    echo "\n=== Active Plugins ===\n";
    if (is_array($active_plugins)) {
        foreach ($active_plugins as $plugin) {
            echo "  - $plugin\n";
        }
    } else {
        echo "✗ Active plugins data corrupted\n";
    }
}

// Test user table
$result = mysqli_query($conn, "SELECT COUNT(*) as user_count FROM wp_users");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "\n✓ Users table: " . $row['user_count'] . " users\n";
} else {
    echo "✗ Failed to query users table: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
echo "\n=== Database Test Complete ===\n";
?>