<?php
// Test database connection without loading WordPress
$host = 'mysql513.loopia.se';
$user = 'dbaadmin@b383837';
$pass = 'Anna1Martin2';
$db = 'bkgt_se_db_1';

echo "Testing database connection...\n";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Database connection successful!\n";

// Test a simple query
$result = mysqli_query($conn, "SHOW TABLES");
if ($result) {
    echo "Database query successful!\n";
    $count = mysqli_num_rows($result);
    echo "Found $count tables.\n";
} else {
    echo "Database query failed: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>