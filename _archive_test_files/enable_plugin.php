<?php
// Connect to database directly
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wordpress";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT option_value FROM wp_options WHERE option_name = 'active_plugins'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $active_plugins = unserialize($row["option_value"]);
    
    $plugin_path = 'bkgt-document-management/bkgt-document-management.php';
    $active_plugins = array_filter($active_plugins, function($plugin) use ($plugin_path) {
        return $plugin !== $plugin_path;
    });
    
    $serialized = serialize($active_plugins);
    $update_sql = "UPDATE wp_options SET option_value = '" . $conn->real_escape_string($serialized) . "' WHERE option_name = 'active_plugins'";
    $conn->query($update_sql);
    
    echo "Plugin removed from active plugins\n";
} else {
    echo "No active plugins found\n";
}

$conn->close();
?>