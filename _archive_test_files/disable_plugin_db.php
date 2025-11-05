<?php
// Database connection
$servername = "mysql513.loopia.se";
$username = "dbaadmin@b383837";
$password = "Anna1Martin2";
$dbname = "bkgt_se_db_1";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT option_value FROM wp_options WHERE option_name = 'active_plugins'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $active_plugins = unserialize($row["option_value"]);

    echo "Current active plugins:\n";
    foreach ($active_plugins as $plugin) {
        echo "- $plugin\n";
    }

    // Disable all plugins for clean slate
    $active_plugins = array();

    $serialized = serialize($active_plugins);
    $update_sql = "UPDATE wp_options SET option_value = '" . $conn->real_escape_string($serialized) . "' WHERE option_name = 'active_plugins'";
    if ($conn->query($update_sql) === TRUE) {
        echo "All plugins disabled for clean slate\n";
    } else {
        echo "Error updating record: " . $conn->error;
    }
} else {
    echo "No active plugins found\n";
}

$conn->close();
?>