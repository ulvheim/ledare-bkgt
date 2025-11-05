<?php
/**
 * Test Helper Utilities for BKGT Plugin Tests
 */

class BKGT_TestHelper {

    /**
     * Create sample player data for testing
     */
    public static function createSamplePlayer($overrides = []) {
        $defaults = [
            'name' => 'Test Player',
            'position' => 'QB',
            'team' => 'Test Team',
            'age' => 25,
            'height' => '180cm',
            'weight' => '85kg',
            'stats' => json_encode(['games' => 10, 'touchdowns' => 5])
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create sample event data for testing
     */
    public static function createSampleEvent($overrides = []) {
        $defaults = [
            'title' => 'Test Match',
            'date' => date('Y-m-d H:i:s'),
            'location' => 'Test Stadium',
            'home_team' => 'Home Team',
            'away_team' => 'Away Team',
            'status' => 'scheduled'
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create sample team data for testing
     */
    public static function createSampleTeam($overrides = []) {
        $defaults = [
            'name' => 'Test Team',
            'coach' => 'Test Coach',
            'category' => 'Senior',
            'season' => date('Y'),
            'players' => []
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create sample inventory item for testing
     */
    public static function createSampleInventoryItem($overrides = []) {
        $defaults = [
            'title' => 'Test Equipment',
            'type' => 'football',
            'condition' => 'good',
            'assigned_to' => null,
            'location' => 'storage',
            'purchase_date' => date('Y-m-d'),
            'value' => 100.00
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create sample document for testing
     */
    public static function createSampleDocument($overrides = []) {
        $defaults = [
            'title' => 'Test Document',
            'type' => 'contract',
            'file_path' => '/uploads/test.pdf',
            'uploaded_by' => 1,
            'upload_date' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create sample user for testing
     */
    public static function createSampleUser($overrides = []) {
        $defaults = [
            'user_login' => 'testuser_' . rand(1000, 9999),
            'user_email' => 'test' . rand(1000, 9999) . '@example.com',
            'user_pass' => 'testpass123',
            'role' => 'subscriber',
            'first_name' => 'Test',
            'last_name' => 'User',
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create sample offboarding process for testing
     */
    public static function createSampleOffboardingProcess($overrides = []) {
        $defaults = [
            'title' => 'Offboarding: Test User',
            'user_id' => 1,
            'status' => 'pending',
            'tasks' => json_encode([
                'return_equipment' => false,
                'revoke_access' => false,
                'final_payroll' => false,
                'exit_interview' => false
            ]),
            'equipment' => json_encode([]),
            'notes' => 'Test offboarding process',
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Mock HTTP response for external API calls
     */
    public static function mockApiResponse($url, $response_data, $response_code = 200) {
        // This would be used with a mocking library like Mockery
        // For now, return the mock data
        return [
            'body' => json_encode($response_data),
            'response' => ['code' => $response_code],
            'headers' => ['content-type' => 'application/json']
        ];
    }

    /**
     * Generate random test data
     */
    public static function generateRandomData($type, $count = 1) {
        $data = [];

        for ($i = 0; $i < $count; $i++) {
            switch ($type) {
                case 'player':
                    $data[] = self::createSamplePlayer([
                        'name' => 'Player ' . ($i + 1),
                        'position' => ['QB', 'RB', 'WR', 'LB', 'DB'][rand(0, 4)]
                    ]);
                    break;

                case 'event':
                    $data[] = self::createSampleEvent([
                        'title' => 'Match ' . ($i + 1),
                        'date' => date('Y-m-d H:i:s', strtotime('+' . rand(1, 30) . ' days'))
                    ]);
                    break;

                case 'inventory':
                    $data[] = self::createSampleInventoryItem([
                        'title' => 'Item ' . ($i + 1),
                        'type' => ['football', 'helmet', 'pads', 'cones'][rand(0, 3)]
                    ]);
                    break;
            }
        }

        return $count === 1 ? $data[0] : $data;
    }

    /**
     * Clean up test files and directories
     */
    public static function cleanupTestFiles($directory = null) {
        if (!$directory) {
            $directory = sys_get_temp_dir() . '/bkgt_tests';
        }

        if (is_dir($directory)) {
            $files = glob($directory . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($directory);
        }
    }

    /**
     * Assert that an array has required keys
     */
    public static function assertArrayHasKeys($array, $keys, $message = '') {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array)) {
                $message = $message ?: "Array is missing required key: {$key}";
                throw new PHPUnit\Framework\AssertionFailedError($message);
            }
        }
    }

    /**
     * Validate data structure against schema
     */
    public static function validateDataStructure($data, $schema) {
        foreach ($schema as $key => $rules) {
            if (!isset($data[$key])) {
                if (isset($rules['required']) && $rules['required']) {
                    return false;
                }
                continue;
            }

            $value = $data[$key];

            // Type validation
            if (isset($rules['type'])) {
                $type = gettype($value);
                if ($type !== $rules['type']) {
                    return false;
                }
            }

            // Length validation for strings
            if (isset($rules['max_length']) && is_string($value)) {
                if (strlen($value) > $rules['max_length']) {
                    return false;
                }
            }

            // Range validation for numbers
            if (isset($rules['min']) && is_numeric($value)) {
                if ($value < $rules['min']) {
                    return false;
                }
            }

            if (isset($rules['max']) && is_numeric($value)) {
                if ($value > $rules['max']) {
                    return false;
                }
            }
        }

        return true;
    }
}