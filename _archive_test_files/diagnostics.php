<?php
require_once('wp-load.php');

echo "=== Detailed System Diagnostics ===\n\n";

// Check active plugins
echo "Active Plugins:\n";
$active_plugins = get_option('active_plugins');
foreach ($active_plugins as $plugin) {
    if (strpos($plugin, 'bkgt') !== false) {
        echo "- $plugin\n";
    }
}
echo "\n";

// Check registered post types
echo "Registered Post Types:\n";
$post_types = get_post_types(array('_builtin' => false), 'objects');
foreach ($post_types as $post_type) {
    if (strpos($post_type->name, 'bkgt') !== false) {
        echo "- {$post_type->name}: {$post_type->label}\n";
    }
}
echo "\n";

// Check user roles
echo "User Roles:\n";
$roles = wp_roles()->roles;
foreach ($roles as $role_key => $role) {
    if (in_array($role_key, array('styrelsemedlem', 'tranare', 'lagledare', 'administrator', 'editor', 'author', 'contributor', 'subscriber'))) {
        echo "- $role_key: {$role['name']}\n";
    }
}
echo "\n";

// Check if shortcodes work
echo "Shortcode Tests:\n";
if (shortcode_exists('bkgt_inventory')) {
    echo "✅ bkgt_inventory shortcode exists\n";
} else {
    echo "❌ bkgt_inventory shortcode missing\n";
}

if (shortcode_exists('bkgt_documents')) {
    echo "✅ bkgt_documents shortcode exists\n";
} else {
    echo "❌ bkgt_documents shortcode missing\n";
}
?>