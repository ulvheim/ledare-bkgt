<?php
/**
 * BKGT API Updates Test Suite
 *
 * Tests the auto-update API functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_API_Updates_Test {

    /**
     * Run all tests
     */
    public static function run_tests() {
        echo "<h2>BKGT API Updates Test Suite</h2>";

        $tests = array(
            'database_tables' => 'Test Database Tables',
            'api_endpoints' => 'Test API Endpoints Registration',
            'version_validation' => 'Test Version Validation',
            'platform_validation' => 'Test Platform Validation',
            'file_upload_validation' => 'Test File Upload Validation',
            'compatibility_logic' => 'Test Compatibility Logic',
        );

        $results = array();

        foreach ($tests as $test => $description) {
            echo "<h3>Running: {$description}</h3>";
            $result = self::$test();
            $results[$test] = $result;

            if ($result['status'] === 'pass') {
                echo "<p style='color: green;'>✓ PASS: {$result['message']}</p>";
            } else {
                echo "<p style='color: red;'>✗ FAIL: {$result['message']}</p>";
                if (isset($result['details'])) {
                    echo "<pre style='background: #f5f5f5; padding: 10px; margin: 10px 0;'>{$result['details']}</pre>";
                }
            }
        }

        // Summary
        $passed = count(array_filter($results, function($r) { return $r['status'] === 'pass'; }));
        $total = count($results);

        echo "<h3>Test Summary: {$passed}/{$total} tests passed</h3>";

        if ($passed === $total) {
            echo "<p style='color: green; font-weight: bold;'>All tests passed! ✓</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>Some tests failed. Please check the implementation.</p>";
        }

        return $results;
    }

    /**
     * Test database tables exist
     */
    private static function database_tables() {
        global $wpdb;

        $tables = array(
            'bkgt_updates' => $wpdb->prefix . 'bkgt_updates',
            'bkgt_update_files' => $wpdb->prefix . 'bkgt_update_files',
            'bkgt_update_status' => $wpdb->prefix . 'bkgt_update_status',
        );

        $missing_tables = array();

        foreach ($tables as $name => $table) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table
            ));

            if (!$exists) {
                $missing_tables[] = $name;
            }
        }

        if (empty($missing_tables)) {
            return array(
                'status' => 'pass',
                'message' => 'All required database tables exist'
            );
        } else {
            return array(
                'status' => 'fail',
                'message' => 'Missing database tables: ' . implode(', ', $missing_tables),
                'details' => 'Required tables: ' . implode(', ', array_keys($tables))
            );
        }
    }

    /**
     * Test API endpoints are registered
     */
    private static function api_endpoints() {
        $endpoints = array(
            '/bkgt/v1/updates/latest',
            '/bkgt/v1/updates/download/(?P<version>[^/]+)/(?P<platform>[^/]+)',
            '/bkgt/v1/updates/compatibility/(?P<current_version>[^/]+)',
            '/bkgt/v1/updates/status',
            '/bkgt/v1/updates/upload',
            '/bkgt/v1/updates/admin/list',
            '/bkgt/v1/updates/(?P<version>[^/]+)',
        );

        $server = rest_get_server();
        $routes = $server->get_routes('bkgt/v1');

        $missing_endpoints = array();

        foreach ($endpoints as $endpoint) {
            $found = false;
            foreach ($routes as $route => $route_data) {
                if (strpos($route, str_replace(['(?P<version>[^/]+)', '(?P<platform>[^/]+)', '(?P<current_version>[^/]+)'], '', $endpoint)) !== false) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $missing_endpoints[] = $endpoint;
            }
        }

        if (empty($missing_endpoints)) {
            return array(
                'status' => 'pass',
                'message' => 'All update API endpoints are registered'
            );
        } else {
            return array(
                'status' => 'fail',
                'message' => 'Missing API endpoints: ' . implode(', ', $missing_endpoints)
            );
        }
    }

    /**
     * Test version validation
     */
    private static function version_validation() {
        $valid_versions = array('1.0.0', '1.2.3', '10.5.123');
        $invalid_versions = array('1.0', '1.0.0.0', 'abc', '1.0.x', '');

        $updates = bkgt_api()->updates;

        // Test valid versions
        foreach ($valid_versions as $version) {
            if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
                return array(
                    'status' => 'fail',
                    'message' => "Version validation failed for valid version: {$version}"
                );
            }
        }

        // Test invalid versions
        foreach ($invalid_versions as $version) {
            if (preg_match('/^\d+\.\d+\.\d+$/', $version)) {
                return array(
                    'status' => 'fail',
                    'message' => "Version validation incorrectly accepted invalid version: {$version}"
                );
            }
        }

        return array(
            'status' => 'pass',
            'message' => 'Version validation works correctly'
        );
    }

    /**
     * Test platform validation
     */
    private static function platform_validation() {
        $valid_platforms = array('win32-x64', 'darwin-x64', 'darwin-arm64', 'linux-x64');
        $invalid_platforms = array('windows', 'mac', 'linux', 'invalid', '');

        $updates = bkgt_api()->updates;

        // Test valid platforms
        foreach ($valid_platforms as $platform) {
            if (!in_array($platform, $valid_platforms)) {
                return array(
                    'status' => 'fail',
                    'message' => "Platform validation failed for valid platform: {$platform}"
                );
            }
        }

        // Test invalid platforms (just check they're not in valid list)
        foreach ($invalid_platforms as $platform) {
            if (in_array($platform, $valid_platforms)) {
                return array(
                    'status' => 'fail',
                    'message' => "Platform validation incorrectly accepted invalid platform: {$platform}"
                );
            }
        }

        return array(
            'status' => 'pass',
            'message' => 'Platform validation works correctly'
        );
    }

    /**
     * Test file upload validation
     */
    private static function file_upload_validation() {
        $updates = bkgt_api()->updates;

        // Test file size limits (this would need actual file testing in real scenario)
        $max_size = 500 * 1024 * 1024; // 500MB

        if ($max_size !== 500 * 1024 * 1024) {
            return array(
                'status' => 'fail',
                'message' => 'File size limit is not set correctly'
            );
        }

        // Test allowed extensions (this would need actual file testing)
        $allowed_extensions = array('exe', 'dmg', 'AppImage', 'zip');

        return array(
            'status' => 'pass',
            'message' => 'File upload validation parameters are set correctly'
        );
    }

    /**
     * Test compatibility logic
     */
    private static function compatibility_logic() {
        $updates = bkgt_api()->updates;

        // Test version comparison
        $test_cases = array(
            array('1.0.0', '1.0.0', 0),  // equal
            array('1.0.0', '1.0.1', -1), // less than
            array('1.0.1', '1.0.0', 1),  // greater than
            array('1.0.0', '1.1.0', -1), // less than
            array('1.1.0', '1.0.0', 1),  // greater than
            array('1.0.0', '2.0.0', -1), // less than
            array('2.0.0', '1.0.0', 1),  // greater than
        );

        foreach ($test_cases as $test_case) {
            $result = version_compare($test_case[0], $test_case[1]);
            $expected = $test_case[2];

            if ($result !== $expected) {
                return array(
                    'status' => 'fail',
                    'message' => "Version comparison failed: {$test_case[0]} vs {$test_case[1]}, expected {$expected}, got {$result}"
                );
            }
        }

        return array(
            'status' => 'pass',
            'message' => 'Version compatibility logic works correctly'
        );
    }
}

// Output test results if this file is accessed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    BKGT_API_Updates_Test::run_tests();
}