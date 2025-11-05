<?php
require_once('wp-load.php');

echo "=== Creating Sample Users for Validation ===\n\n";

// Sample users data
$sample_users = array(
    array(
        'user_login' => 'anna.andersson',
        'user_email' => 'anna.andersson@bkgt.se',
        'first_name' => 'Anna',
        'last_name' => 'Andersson',
        'role' => 'styrelsemedlem',
        'display_name' => 'Anna Andersson'
    ),
    array(
        'user_login' => 'bjorn.bjorklund',
        'user_email' => 'bjorn.bjorklund@bkgt.se',
        'first_name' => 'Björn',
        'last_name' => 'Björklund',
        'role' => 'styrelsemedlem',
        'display_name' => 'Björn Björklund'
    ),
    array(
        'user_login' => 'carl.coach',
        'user_email' => 'carl.coach@bkgt.se',
        'first_name' => 'Carl',
        'last_name' => 'Coach',
        'role' => 'tranare',
        'display_name' => 'Carl Coach'
    ),
    array(
        'user_login' => 'david.coach',
        'user_email' => 'david.coach@bkgt.se',
        'first_name' => 'David',
        'last_name' => 'Coach',
        'role' => 'tranare',
        'display_name' => 'David Coach'
    ),
    array(
        'user_login' => 'erik.manager',
        'user_email' => 'erik.manager@bkgt.se',
        'first_name' => 'Erik',
        'last_name' => 'Manager',
        'role' => 'lagledare',
        'display_name' => 'Erik Manager'
    ),
    array(
        'user_login' => 'fredrik.manager',
        'user_email' => 'fredrik.manager@bkgt.se',
        'first_name' => 'Fredrik',
        'last_name' => 'Manager',
        'role' => 'lagledare',
        'display_name' => 'Fredrik Manager'
    )
);

$created_count = 0;
$existing_count = 0;

foreach ($sample_users as $user_data) {
    // Check if user already exists
    if (username_exists($user_data['user_login']) || email_exists($user_data['user_email'])) {
        echo "User already exists: " . $user_data['user_login'] . "\n";
        $existing_count++;
        continue;
    }

    // Create the user
    $user_id = wp_insert_user(array(
        'user_login' => $user_data['user_login'],
        'user_email' => $user_data['user_email'],
        'first_name' => $user_data['first_name'],
        'last_name' => $user_data['last_name'],
        'display_name' => $user_data['display_name'],
        'user_pass' => 'TempPass123!', // Temporary password
        'role' => $user_data['role']
    ));

    if (is_wp_error($user_id)) {
        echo "Error creating user " . $user_data['user_login'] . ": " . $user_id->get_error_message() . "\n";
    } else {
        echo "Created user: " . $user_data['user_login'] . " (" . $user_data['role'] . ")\n";
        $created_count++;
    }
}

echo "\n=== Summary ===\n";
echo "Users created: $created_count\n";
echo "Users already existed: $existing_count\n";
echo "Total users processed: " . count($sample_users) . "\n\n";

// Display all users
echo "=== All Users ===\n";
$all_users = get_users();
foreach($all_users as $user) {
    $roles = $user->roles;
    echo $user->user_login . ' (' . $user->display_name . ') - Role: ' . implode(', ', $roles) . "\n";
}
?>