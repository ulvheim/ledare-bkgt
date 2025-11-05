<?php
require_once('wp-load.php');

echo "=== WordPress User Accounts ===\n\n";

$users = get_users();
foreach($users as $user) {
    $roles = $user->roles;
    echo $user->user_login . ' (' . $user->display_name . ') - Role: ' . implode(', ', $roles) . "\n";
}

echo "\n=== Total Users: " . count($users) . " ===\n";
?>