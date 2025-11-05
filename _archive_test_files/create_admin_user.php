<?php
require_once('wp-load.php');

echo "=== Recreating Admin User ===\n\n";

// Check if admin user already exists
$existing_admin = get_user_by('login', 'admin');
if ($existing_admin) {
    echo "❌ Admin user already exists!\n";
    echo "  ID: {$existing_admin->ID}\n";
    echo "  Email: {$existing_admin->user_email}\n";
    exit;
}

// Create new admin user
$userdata = array(
    'user_login' => 'admin',
    'user_email' => 'admin@bkgt.se',
    'user_pass' => 'Anna1Martin2',
    'role' => 'administrator',
    'display_name' => 'BKGT Administrator'
);

$user_id = wp_insert_user($userdata);

if (is_wp_error($user_id)) {
    echo "❌ Failed to create admin user: " . $user_id->get_error_message() . "\n";
} else {
    echo "✅ Admin user created successfully!\n";
    echo "  Username: admin\n";
    echo "  Password: Anna1Martin2\n";
    echo "  Email: admin@bkgt.se\n";
    echo "  User ID: $user_id\n";

    // Verify the user was created
    $new_user = get_user_by('id', $user_id);
    if ($new_user) {
        echo "✅ User verification successful\n";
        echo "  Password hash: " . substr($new_user->user_pass, 0, 20) . "...\n";
    }
}

echo "\n=== Current Users ===\n";
$users = get_users();
foreach ($users as $user) {
    echo "  - {$user->user_login} ({$user->user_email})\n";
}
?>