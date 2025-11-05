<?php
require_once('wp-load.php');

echo "=== WordPress Admin User Check ===\n\n";

global $wpdb;

// Check if admin user exists
$admin_user = $wpdb->get_row("SELECT ID, user_login, user_email, user_pass FROM {$wpdb->users} WHERE user_login = 'admin'");

if ($admin_user) {
    echo "✅ Admin user found:\n";
    echo "  ID: {$admin_user->ID}\n";
    echo "  Username: {$admin_user->user_login}\n";
    echo "  Email: {$admin_user->user_email}\n";
    echo "  Password Hash: " . substr($admin_user->user_pass, 0, 20) . "...\n";

    // Check if password hash looks valid
    if (strlen($admin_user->user_pass) < 20) {
        echo "⚠️ Password hash appears too short - may be corrupted!\n";
    } else {
        echo "✅ Password hash appears valid\n";
    }
} else {
    echo "❌ Admin user not found!\n";

    // Check what users do exist
    $users = $wpdb->get_results("SELECT ID, user_login, user_email FROM {$wpdb->users}");
    echo "\nExisting users:\n";
    foreach ($users as $user) {
        echo "  - {$user->user_login} ({$user->user_email})\n";
    }
}

echo "\n=== Password Reset Check ===\n";

// Check for any password reset related code or plugins
$active_plugins = get_option('active_plugins');
if (is_array($active_plugins)) {
    echo "Active plugins:\n";
    foreach ($active_plugins as $plugin) {
        echo "  - $plugin\n";
    }
}

// Check for any custom authentication filters
echo "\nChecking for authentication filters...\n";
global $wp_filter;
if (isset($wp_filter['authenticate'])) {
    echo "⚠️ Custom authentication filters found!\n";
    foreach ($wp_filter['authenticate'] as $priority => $filters) {
        foreach ($filters as $filter) {
            if (is_array($filter['function'])) {
                echo "  - {$filter['function'][1]} (in " . get_class($filter['function'][0]) . ")\n";
            } else {
                echo "  - {$filter['function']}\n";
            }
        }
    }
} else {
    echo "✅ No custom authentication filters found\n";
}
?>