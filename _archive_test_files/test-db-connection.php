<?php
// Test database connection
$host = 'mysql513.loopia.se';
$user = 'dbaadmin@b383837';
$pass = 'Anna1Martin2';
$db = 'bkgt_se_db_1';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Database connection successful!";

mysqli_close($conn);
?>