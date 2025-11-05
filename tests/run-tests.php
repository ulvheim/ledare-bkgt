<?php
/**
 * BKGT Test Runner
 * Simple script to run tests without full PHPUnit setup
 */

// Define test environment
define('TEST_ENV', true);
define('WP_DEBUG', false);

// Include bootstrap
require_once __DIR__ . '/bootstrap.php';

echo "BKGT Plugin Test Runner\n";
echo "=======================\n\n";

// Basic functionality tests
$tests_passed = 0;
$tests_failed = 0;

function run_test($test_name, $test_function) {
    global $tests_passed, $tests_failed;

    echo "Running: {$test_name}... ";

    try {
        $result = $test_function();
        if ($result === true || $result === null) {
            echo "✓ PASSED\n";
            $tests_passed++;
        } else {
            echo "✗ FAILED: {$result}\n";
            $tests_failed++;
        }
    } catch (Exception $e) {
        echo "✗ ERROR: {$e->getMessage()}\n";
        $tests_failed++;
    }
}

// Test plugin file existence
run_test("Plugin Files Exist", function() {
    $plugins = [
        '../wp-content/plugins/bkgt-data-scraping/bkgt-data-scraping.php',
        '../wp-content/plugins/bkgt-team-player/bkgt-team-player.php',
        '../wp-content/plugins/bkgt-inventory/bkgt-inventory.php'
    ];

    foreach ($plugins as $plugin) {
        if (!file_exists($plugin)) {
            return "Plugin file not found: {$plugin}";
        }
    }

    return true;
});

// Test PHP syntax
run_test("PHP Syntax Check", function() {
    $plugin_files = glob('../wp-content/plugins/bkgt-*/bkgt-*.php');

    foreach ($plugin_files as $file) {
        $output = shell_exec("php -l \"{$file}\" 2>&1");
        if (strpos($output, 'No syntax errors') === false) {
            return "Syntax error in {$file}: {$output}";
        }
    }

    return true;
});

// Test shortcode functions exist
run_test("Shortcode Functions", function() {
    $shortcodes = [
        'bkgt_players',
        'bkgt_events',
        'bkgt_team_overview',
        'bkgt_inventory'
    ];

    foreach ($shortcodes as $shortcode) {
        if (!shortcode_exists($shortcode)) {
            return "Shortcode not registered: {$shortcode}";
        }
    }

    return true;
});

// Test database tables (if WordPress available)
run_test("Database Tables", function() {
    if (!isset($GLOBALS['wpdb'])) {
        return "WordPress database not available - skipping table check";
    }

    global $wpdb;
    $tables = ['bkgt_players', 'bkgt_events', 'bkgt_inventory'];

    foreach ($tables as $table) {
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table
        ));

        if (!$table_exists) {
            return "Table not found: {$table}";
        }
    }

    return true;
});

// Test data validation
run_test("Data Validation", function() {
    $test_data = BKGT_TestHelper::createSamplePlayer();
    $schema = [
        'name' => ['required' => true, 'type' => 'string'],
        'position' => ['required' => true, 'type' => 'string'],
        'age' => ['required' => false, 'type' => 'integer', 'min' => 0, 'max' => 100]
    ];

    $is_valid = BKGT_TestHelper::validateDataStructure($test_data, $schema);

    if (!$is_valid) {
        return "Data validation failed for sample player data";
    }

    return true;
});

// Summary
echo "\nTest Summary:\n";
echo "=============\n";
echo "Passed: {$tests_passed}\n";
echo "Failed: {$tests_failed}\n";
echo "Total:  " . ($tests_passed + $tests_failed) . "\n";

if ($tests_failed === 0) {
    echo "\n🎉 All tests passed!\n";
    exit(0);
} else {
    echo "\n❌ Some tests failed. Please review the output above.\n";
    exit(1);
}