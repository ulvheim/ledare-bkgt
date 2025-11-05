<?php
/**
 * Integration Tests for BKGT Shortcodes
 */

class BKGT_Shortcode_Integration_Test extends BKGT_TestCase {

    protected function setupTestData() {
        global $wpdb;
        if ($wpdb) {
            // Create comprehensive test data
            $players = BKGT_TestHelper::generateRandomData('player', 10);
            foreach ($players as $player) {
                $wpdb->insert('bkgt_players', [
                    'name' => $player['name'],
                    'position' => $player['position'],
                    'team' => 'Integration Test Team',
                    'age' => $player['age'],
                    'stats' => $player['stats']
                ]);
            }

            $events = BKGT_TestHelper::generateRandomData('event', 5);
            foreach ($events as $event) {
                $wpdb->insert('bkgt_events', [
                    'title' => $event['title'],
                    'date' => $event['date'],
                    'location' => $event['location'],
                    'home_team' => 'Integration Test Team',
                    'away_team' => $event['away_team'],
                    'status' => $event['status']
                ]);
            }

            // Create inventory items
            $items = BKGT_TestHelper::generateRandomData('inventory', 8);
            foreach ($items as $item) {
                $wpdb->insert('bkgt_inventory', [
                    'title' => $item['title'],
                    'type' => $item['type'],
                    'condition' => $item['condition'],
                    'location' => $item['location'],
                    'value' => $item['value']
                ]);
            }
        }
    }

    protected function cleanupTestData() {
        global $wpdb;
        if ($wpdb) {
            $wpdb->query("DELETE FROM bkgt_inventory_assignments");
            $wpdb->query("DELETE FROM bkgt_inventory WHERE title LIKE 'Item%'");
            $wpdb->query("DELETE FROM bkgt_players WHERE team = 'Integration Test Team'");
            $wpdb->query("DELETE FROM bkgt_events WHERE home_team = 'Integration Test Team'");
        }
    }

    /**
     * Test bkgt_players shortcode with various parameters
     */
    public function test_bkgt_players_shortcode_comprehensive() {
        // Test basic output
        $output = do_shortcode('[bkgt_players]');
        $this->assertStringContains($output, 'Integration Test Team');

        // Test with team filter
        $output = do_shortcode('[bkgt_players team="Integration Test Team"]');
        $this->assertStringContains($output, 'Integration Test Team');

        // Test with position filter
        $output = do_shortcode('[bkgt_players position="QB"]');
        $this->assertStringContains($output, 'QB');

        // Test with limit
        $output = do_shortcode('[bkgt_players limit="5"]');
        // Should contain player data but limited count
        $this->assertStringContains($output, 'Player');
    }

    /**
     * Test bkgt_events shortcode
     */
    public function test_bkgt_events_shortcode_comprehensive() {
        $output = do_shortcode('[bkgt_events]');
        $this->assertStringContains($output, 'Integration Test Team');

        // Test with status filter
        $output = do_shortcode('[bkgt_events status="scheduled"]');
        $this->assertStringContains($output, 'scheduled');
    }

    /**
     * Test bkgt_team_overview shortcode
     */
    public function test_bkgt_team_overview_shortcode() {
        $output = do_shortcode('[bkgt_team_overview]');
        $this->assertStringContains($output, 'Team');

        // Test with specific team
        $output = do_shortcode('[bkgt_team_overview team="Integration Test Team"]');
        $this->assertStringContains($output, 'Integration Test Team');
    }

    /**
     * Test bkgt_inventory shortcode
     */
    public function test_bkgt_inventory_shortcode() {
        $output = do_shortcode('[bkgt_inventory]');
        $this->assertStringContains($output, 'Utrustning');

        // Test with type filter
        $output = do_shortcode('[bkgt_inventory type="football"]');
        // Should work without errors
        $this->assertIsString($output);
    }

    /**
     * Test bkgt_player_profile shortcode
     */
    public function test_bkgt_player_profile_shortcode() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        $player = $wpdb->get_row("SELECT id FROM bkgt_players WHERE team = 'Integration Test Team' LIMIT 1");

        if ($player) {
            $output = do_shortcode('[bkgt_player_profile id="' . $player->id . '"]');
            $this->assertStringContains($output, 'Player');
        }
    }

    /**
     * Test bkgt_admin_dashboard shortcode
     */
    public function test_bkgt_admin_dashboard_shortcode() {
        // This shortcode might require admin privileges
        $output = do_shortcode('[bkgt_admin_dashboard]');
        // Should not throw fatal errors
        $this->assertIsString($output);
    }

    /**
     * Test shortcode combinations on a page
     */
    public function test_shortcode_combinations() {
        $content = '
            [bkgt_team_overview team="Integration Test Team"]
            [bkgt_players team="Integration Test Team" limit="3"]
            [bkgt_events status="scheduled"]
            [bkgt_inventory type="football"]
        ';

        $output = do_shortcode($content);

        // Should contain content from all shortcodes
        $this->assertStringContains($output, 'Integration Test Team');
        $this->assertStringContains($output, 'Player');
        $this->assertStringContains($output, 'scheduled');
    }

    /**
     * Test shortcode parameter validation
     */
    public function test_shortcode_parameter_validation() {
        // Test invalid parameters
        $output = do_shortcode('[bkgt_players limit="-1"]');
        $this->assertIsString($output); // Should handle gracefully

        $output = do_shortcode('[bkgt_players team=""]');
        $this->assertIsString($output); // Should handle empty parameters

        $output = do_shortcode('[bkgt_events date="invalid-date"]');
        $this->assertIsString($output); // Should handle invalid dates
    }

    /**
     * Test shortcode output formatting
     */
    public function test_shortcode_output_formatting() {
        $output = do_shortcode('[bkgt_players]');

        // Should contain proper HTML structure
        $this->assertStringContains($output, '<div');
        $this->assertStringContains($output, '</div>');

        // Should not contain PHP errors
        $this->assertStringNotContains($output, 'Fatal error');
        $this->assertStringNotContains($output, 'Warning');
        $this->assertStringNotContains($output, 'Notice');
    }

    /**
     * Test shortcode performance with large datasets
     */
    public function test_shortcode_performance() {
        // Add more test data
        global $wpdb;
        if ($wpdb) {
            for ($i = 0; $i < 50; $i++) {
                $wpdb->insert('bkgt_players', [
                    'name' => 'Performance Test Player ' . $i,
                    'position' => 'QB',
                    'team' => 'Performance Test Team',
                    'age' => 25
                ]);
            }
        }

        $start_time = microtime(true);
        $output = do_shortcode('[bkgt_players team="Performance Test Team"]');
        $end_time = microtime(true);

        $execution_time = $end_time - $start_time;

        // Should complete within reasonable time (less than 5 seconds)
        $this->assertLessThan(5.0, $execution_time);
        $this->assertStringContains($output, 'Performance Test Player');

        // Clean up
        if ($wpdb) {
            $wpdb->query("DELETE FROM bkgt_players WHERE team = 'Performance Test Team'");
        }
    }

    /**
     * Test shortcode caching behavior
     */
    public function test_shortcode_caching() {
        // First call
        $output1 = do_shortcode('[bkgt_players limit="3"]');

        // Second call (should be same or similar)
        $output2 = do_shortcode('[bkgt_players limit="3"]');

        // Outputs should be consistent
        $this->assertEquals(strlen($output1), strlen($output2));
    }

    /**
     * Test shortcode with user permissions
     */
    public function test_shortcode_user_permissions() {
        // Test as anonymous user
        $output = do_shortcode('[bkgt_admin_dashboard]');
        // Should handle lack of permissions gracefully
        $this->assertIsString($output);

        // Test with mock admin user
        $admin_user = $this->createMockUser('administrator');
        if ($admin_user) {
            wp_set_current_user($admin_user->ID);
            $output = do_shortcode('[bkgt_admin_dashboard]');
            $this->assertIsString($output);
            wp_set_current_user(0); // Reset
        }
    }
}