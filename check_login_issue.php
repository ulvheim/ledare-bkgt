<?php
require_once('wp-load.php');

echo "=== Checking for Login-Restricting Plugins ===\n\n";

$active_plugins = get_option('active_plugins');
$suspicious_plugins = [];

foreach ($active_plugins as $plugin) {
    if (strpos(strtolower($plugin), 'login') !== false ||
        strpos(strtolower($plugin), 'force') !== false ||
        strpos(strtolower($plugin), 'restrict') !== false ||
        strpos(strtolower($plugin), 'private') !== false ||
        strpos(strtolower($plugin), 'member') !== false) {
        $suspicious_plugins[] = $plugin;
    }
}

if (!empty($suspicious_plugins)) {
    echo "POTENTIAL CAUSE - Suspicious plugins found:\n";
    foreach ($suspicious_plugins as $plugin) {
        echo "- $plugin\n";
    }
} else {
    echo "No obviously suspicious plugins found.\n";
}

echo "\n=== Checking Site Visibility ===\n";
echo "Blog Public Setting: " . get_option('blog_public') . "\n";

// Check for any custom login redirects
echo "\n=== Checking for Redirects ===\n";
if (has_filter('login_redirect')) {
    echo "Custom login redirect filter found\n";
}

if (has_action('wp')) {
    echo "Custom wp action found (might redirect)\n";
}

// Check if there's a maintenance mode
echo "\n=== Maintenance Mode Check ===\n";
if (get_option('maintenance_mode')) {
    echo "Maintenance mode is ACTIVE\n";
} else {
    echo "Maintenance mode is NOT active\n";
}
?>