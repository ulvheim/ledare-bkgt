<?php
require_once('wp-load.php');

echo "=== Basic Functionality Smoke Test ===\n\n";

$tests_passed = 0;
$tests_total = 0;

// Test 1: WordPress core functionality
$tests_total++;
echo "Test 1: WordPress core loaded... ";
if (function_exists('wp_get_current_user')) {
    echo "✅ PASSED\n";
    $tests_passed++;
} else {
    echo "❌ FAILED\n";
}

// Test 2: Custom post types registered
$tests_total++;
echo "Test 2: Custom post types registered... ";
$post_types = get_post_types(array('_builtin' => false));
$required_types = array('bkgt_team', 'bkgt_player', 'bkgt_document', 'bkgt_offboarding');
$types_found = 0;
foreach ($required_types as $type) {
    if (in_array($type, $post_types)) {
        $types_found++;
    }
}
if ($types_found == count($required_types)) {
    echo "✅ PASSED ($types_found/4 post types)\n";
    $tests_passed++;
} else {
    echo "❌ FAILED ($types_found/4 post types found)\n";
}

// Test 3: User roles exist
$tests_total++;
echo "Test 3: Custom user roles exist... ";
$roles = wp_roles()->roles;
$required_roles = array('styrelsemedlem', 'tranare', 'lagledare');
$roles_found = 0;
foreach ($required_roles as $role) {
    if (isset($roles[$role])) {
        $roles_found++;
    }
}
if ($roles_found == count($required_roles)) {
    echo "✅ PASSED ($roles_found/3 roles)\n";
    $tests_passed++;
} else {
    echo "❌ FAILED ($roles_found/3 roles found)\n";
}

// Test 4: Plugins active
$tests_total++;
echo "Test 4: Required plugins active... ";
$active_plugins = get_option('active_plugins');
$required_plugins = array(
    'bkgt-user-management/bkgt-user-management.php',
    'bkgt-document-management/bkgt-document-management.php',
    'bkgt-offboarding/bkgt-offboarding.php',
    'bkgt-inventory/bkgt-inventory.php'
);
$plugins_found = 0;
foreach ($required_plugins as $plugin) {
    if (in_array($plugin, $active_plugins)) {
        $plugins_found++;
    }
}
if ($plugins_found == count($required_plugins)) {
    echo "✅ PASSED ($plugins_found/4 plugins active)\n";
    $tests_passed++;
} else {
    echo "❌ FAILED ($plugins_found/4 plugins active)\n";
}

// Test 5: Database tables exist
$tests_total++;
echo "Test 5: Required database tables exist... ";
global $wpdb;
$required_tables = array(
    $wpdb->prefix . 'bkgt_teams',
    $wpdb->prefix . 'bkgt_manufacturers',
    $wpdb->prefix . 'bkgt_item_types'
);
$tables_found = 0;
foreach ($required_tables as $table) {
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
        $tables_found++;
    }
}
if ($tables_found == count($required_tables)) {
    echo "✅ PASSED ($tables_found/3 tables exist)\n";
    $tests_passed++;
} else {
    echo "❌ FAILED ($tables_found/3 tables exist)\n";
}

// Test 6: Sample data exists
$tests_total++;
echo "Test 6: Production data populated... ";
$data_checks = array(
    'teams' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bkgt_team'"),
    'players' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bkgt_player'"),
    'documents' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bkgt_document'"),
    'offboarding' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bkgt_offboarding'")
);
$data_populated = 0;
foreach ($data_checks as $type => $count) {
    if ($count > 0) $data_populated++;
}
if ($data_populated >= 3) { // At least 3 out of 4 have data
    echo "✅ PASSED ($data_populated/4 data types populated)\n";
    $tests_passed++;
} else {
    echo "❌ FAILED ($data_populated/4 data types populated)\n";
}

echo "\n=== Test Results ===\n";
echo "Tests passed: $tests_passed/$tests_total\n";

if ($tests_passed == $tests_total) {
    echo "🎉 ALL TESTS PASSED - System is ready for validation!\n";
} elseif ($tests_passed >= $tests_total * 0.8) {
    echo "⚠️ MOST TESTS PASSED - Minor issues to address\n";
} else {
    echo "❌ CRITICAL ISSUES - System needs attention\n";
}
?>