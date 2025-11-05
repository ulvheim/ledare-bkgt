<?php
/**
 * Base Test Case for BKGT Plugin Tests
 */

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class BKGT_TestCase extends PHPUnitTestCase {

    protected static $wpdb_backup;

    /**
     * Set up test environment before each test
     */
    protected function setUp(): void {
        parent::setUp();

        // Backup database state if WordPress is loaded
        if (isset($GLOBALS['wpdb'])) {
            self::$wpdb_backup = $GLOBALS['wpdb'];
        }

        // Initialize test data
        $this->setupTestData();
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void {
        // Restore database state
        if (self::$wpdb_backup) {
            $GLOBALS['wpdb'] = self::$wpdb_backup;
        }

        // Clean up test data
        $this->cleanupTestData();

        parent::tearDown();
    }

    /**
     * Set up test data for each test
     */
    protected function setupTestData() {
        // Override in child classes
    }

    /**
     * Clean up test data after each test
     */
    protected function cleanupTestData() {
        // Override in child classes
    }

    /**
     * Assert that a shortcode produces expected output
     */
    protected function assertShortcodeOutput($shortcode, $expected, $atts = []) {
        $output = do_shortcode('[' . $shortcode . ' ' . $this->buildShortcodeAtts($atts) . ']');
        $this->assertStringContains($expected, $output);
    }

    /**
     * Build shortcode attributes string
     */
    private function buildShortcodeAtts($atts) {
        $attr_string = '';
        foreach ($atts as $key => $value) {
            $attr_string .= $key . '="' . $value . '" ';
        }
        return trim($attr_string);
    }

    /**
     * Create a mock user with specific role
     */
    protected function createMockUser($role = 'subscriber') {
        if (!function_exists('wp_create_user')) {
            return null;
        }

        $user_id = wp_create_user('testuser_' . rand(1000, 9999), 'password', 'test@example.com');

        if (!is_wp_error($user_id)) {
            $user = new WP_User($user_id);
            $user->set_role($role);
            return $user;
        }

        return null;
    }

    /**
     * Create mock post data
     */
    protected function createMockPost($post_data = []) {
        $defaults = [
            'post_title' => 'Test Post',
            'post_content' => 'Test content',
            'post_status' => 'publish',
            'post_type' => 'post'
        ];

        $data = array_merge($defaults, $post_data);

        if (function_exists('wp_insert_post')) {
            return wp_insert_post($data);
        }

        return rand(1, 1000); // Mock ID
    }

    /**
     * Assert that a database table exists
     */
    protected function assertTableExists($table_name) {
        global $wpdb;
        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));

        $this->assertNotNull($table_exists, "Table '{$table_name}' should exist");
    }

    /**
     * Assert that a database table has expected columns
     */
    protected function assertTableHasColumns($table_name, $expected_columns) {
        global $wpdb;
        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        $columns = $wpdb->get_col("DESCRIBE {$table_name}", 0);

        foreach ($expected_columns as $column) {
            $this->assertContains($column, $columns, "Column '{$column}' should exist in table '{$table_name}'");
        }
    }
}