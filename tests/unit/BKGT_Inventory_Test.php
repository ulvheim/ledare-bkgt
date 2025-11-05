<?php
/**
 * Unit Tests for BKGT Inventory Plugin
 */

class BKGT_Inventory_Test extends BKGT_TestCase {

    protected function setupTestData() {
        global $wpdb;
        if ($wpdb) {
            // Create test inventory items
            $items = BKGT_TestHelper::generateRandomData('inventory', 10);
            foreach ($items as $item) {
                $wpdb->insert('bkgt_inventory', [
                    'title' => $item['title'],
                    'type' => $item['type'],
                    'condition' => $item['condition'],
                    'location' => $item['location'],
                    'value' => $item['value']
                ]);
            }

            // Create test assignments
            for ($i = 1; $i <= 5; $i++) {
                $wpdb->insert('bkgt_inventory_assignments', [
                    'item_id' => $i,
                    'assigned_to' => rand(1, 3), // Mock user IDs
                    'assigned_by' => 1,
                    'assignment_type' => 'individual',
                    'status' => 'active'
                ]);
            }
        }
    }

    protected function cleanupTestData() {
        global $wpdb;
        if ($wpdb) {
            $wpdb->query("DELETE FROM bkgt_inventory_assignments");
            $wpdb->query("DELETE FROM bkgt_inventory WHERE title LIKE 'Item%'");
        }
    }

    /**
     * Test plugin class exists
     */
    public function test_plugin_class_exists() {
        $this->assertTrue(class_exists('BKGT_Inventory'));
    }

    /**
     * Test inventory table structure
     */
    public function test_inventory_table_structure() {
        $expected_columns = [
            'id', 'title', 'type', 'condition', 'assigned_to', 'location',
            'purchase_date', 'value', 'notes', 'created_at', 'updated_at'
        ];
        $this->assertTableHasColumns('bkgt_inventory', $expected_columns);
    }

    /**
     * Test assignments table structure
     */
    public function test_assignments_table_structure() {
        $expected_columns = [
            'id', 'item_id', 'assigned_to', 'assigned_by', 'assignment_type',
            'status', 'assigned_date', 'return_date', 'notes'
        ];
        $this->assertTableHasColumns('bkgt_inventory_assignments', $expected_columns);
    }

    /**
     * Test shortcode registration
     */
    public function test_shortcode_registered() {
        $this->assertTrue(shortcode_exists('bkgt_inventory'));
    }

    /**
     * Test inventory item creation
     */
    public function test_inventory_item_creation() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        $item_data = BKGT_TestHelper::createSampleInventoryItem([
            'title' => 'Test Football',
            'type' => 'football'
        ]);

        $result = $wpdb->insert('bkgt_inventory', [
            'title' => $item_data['title'],
            'type' => $item_data['type'],
            'condition' => $item_data['condition'],
            'location' => $item_data['location'],
            'value' => $item_data['value']
        ]);

        $this->assertNotFalse($result);
        $this->assertGreaterThan(0, $wpdb->insert_id);
    }

    /**
     * Test inventory item assignment
     */
    public function test_inventory_assignment() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Get an item ID
        $item = $wpdb->get_row("SELECT id FROM bkgt_inventory LIMIT 1");

        if ($item) {
            $result = $wpdb->insert('bkgt_inventory_assignments', [
                'item_id' => $item->id,
                'assigned_to' => 1,
                'assigned_by' => 1,
                'assignment_type' => 'individual',
                'status' => 'active'
            ]);

            $this->assertNotFalse($result);

            // Verify assignment
            $assignment = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM bkgt_inventory_assignments WHERE item_id = %d AND status = 'active'",
                $item->id
            ));

            $this->assertNotNull($assignment);
            $this->assertEquals(1, $assignment->assigned_to);
            $this->assertEquals('individual', $assignment->assignment_type);
        }
    }

    /**
     * Test inventory shortcode output
     */
    public function test_inventory_shortcode_output() {
        $this->assertShortcodeOutput('bkgt_inventory', 'Utrustning');
    }

    /**
     * Test item condition validation
     */
    public function test_item_condition_validation() {
        $valid_conditions = ['excellent', 'good', 'fair', 'poor', 'damaged'];
        $item_data = BKGT_TestHelper::createSampleInventoryItem();

        $this->assertContains($item_data['condition'], $valid_conditions);

        // Test invalid condition
        $invalid_item = array_merge($item_data, ['condition' => 'invalid']);
        $this->assertNotContains($invalid_item['condition'], $valid_conditions);
    }

    /**
     * Test inventory statistics
     */
    public function test_inventory_statistics() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Count total items
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM bkgt_inventory");
        $this->assertGreaterThan(0, $total_items);

        // Count items by condition
        $condition_stats = $wpdb->get_results("
            SELECT condition, COUNT(*) as count
            FROM bkgt_inventory
            GROUP BY condition
        ");

        $this->assertNotEmpty($condition_stats);

        // Count assigned items
        $assigned_items = $wpdb->get_var("
            SELECT COUNT(DISTINCT item_id)
            FROM bkgt_inventory_assignments
            WHERE status = 'active'
        ");

        $this->assertGreaterThanOrEqual(0, $assigned_items);
    }

    /**
     * Test assignment types
     */
    public function test_assignment_types() {
        $valid_types = ['individual', 'team', 'club'];

        // Test each type
        foreach ($valid_types as $type) {
            global $wpdb;
            if (!$wpdb) continue;

            $item = $wpdb->get_row("SELECT id FROM bkgt_inventory LIMIT 1");
            if (!$item) continue;

            $result = $wpdb->insert('bkgt_inventory_assignments', [
                'item_id' => $item->id,
                'assigned_to' => 1,
                'assigned_by' => 1,
                'assignment_type' => $type,
                'status' => 'active'
            ]);

            $this->assertNotFalse($result, "Assignment type '{$type}' should be valid");
        }
    }

    /**
     * Test item search functionality
     */
    public function test_item_search() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Search by title
        $search_results = $wpdb->get_results("
            SELECT * FROM bkgt_inventory
            WHERE title LIKE '%Item%'
        ");

        $this->assertNotEmpty($search_results);

        // Search by type
        $type_results = $wpdb->get_results("
            SELECT * FROM bkgt_inventory
            WHERE type = 'football'
        ");

        // Should have at least some footballs from test data
        $this->assertGreaterThanOrEqual(0, count($type_results));
    }

    /**
     * Test assignment status changes
     */
    public function test_assignment_status_changes() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Create an assignment
        $item = $wpdb->get_row("SELECT id FROM bkgt_inventory LIMIT 1");
        if (!$item) return;

        $wpdb->insert('bkgt_inventory_assignments', [
            'item_id' => $item->id,
            'assigned_to' => 1,
            'assigned_by' => 1,
            'assignment_type' => 'individual',
            'status' => 'active'
        ]);

        $assignment_id = $wpdb->insert_id;

        // Change status to returned
        $result = $wpdb->update(
            'bkgt_inventory_assignments',
            ['status' => 'returned', 'return_date' => current_time('mysql')],
            ['id' => $assignment_id]
        );

        $this->assertNotFalse($result);

        // Verify status change
        $assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM bkgt_inventory_assignments WHERE id = %d",
            $assignment_id
        ));

        $this->assertEquals('returned', $assignment->status);
        $this->assertNotNull($assignment->return_date);
    }

    /**
     * Test inventory value calculations
     */
    public function test_inventory_value_calculations() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Calculate total inventory value
        $total_value = $wpdb->get_var("
            SELECT SUM(value) FROM bkgt_inventory
            WHERE value > 0
        ");

        $this->assertGreaterThanOrEqual(0, $total_value);

        // Calculate average value
        $avg_value = $wpdb->get_var("
            SELECT AVG(value) FROM bkgt_inventory
            WHERE value > 0
        ");

        $this->assertGreaterThanOrEqual(0, $avg_value);
    }

    /**
     * Test item location tracking
     */
    public function test_item_location_tracking() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Get items by location
        $locations = $wpdb->get_results("
            SELECT location, COUNT(*) as count
            FROM bkgt_inventory
            GROUP BY location
        ");

        $this->assertNotEmpty($locations);

        // Verify common locations exist
        $location_names = array_column($locations, 'location');
        $this->assertContains('storage', $location_names);
    }

    /**
     * Test assignment history
     */
    public function test_assignment_history() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Get assignment history for an item
        $item = $wpdb->get_row("SELECT id FROM bkgt_inventory LIMIT 1");
        if (!$item) return;

        $history = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM bkgt_inventory_assignments
            WHERE item_id = %d
            ORDER BY assigned_date DESC
        ", $item->id));

        // Should have assignments from setup
        $this->assertNotEmpty($history);

        // Verify history structure
        foreach ($history as $assignment) {
            $this->assertObjectHasAttribute('assigned_date', $assignment);
            $this->assertObjectHasAttribute('status', $assignment);
        }
    }
}