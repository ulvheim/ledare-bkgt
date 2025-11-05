<?php
require 'wp-load.php';

echo "Active plugins:\n";
$plugins = get_option('active_plugins');
if ($plugins) {
    foreach ($plugins as $plugin) {
        echo "- $plugin\n";
    }
} else {
    echo "No active plugins found\n";
}

// Check for maintenance mode
$maintenance = get_option('maintenance_mode');
if ($maintenance) {
    echo "\nMaintenance mode: ENABLED\n";
} else {
    echo "\nMaintenance mode: DISABLED\n";
}

// Check if site is private
$private = get_option('blog_public');
echo "Blog public: $private (0=private, 1=public)\n";
?>