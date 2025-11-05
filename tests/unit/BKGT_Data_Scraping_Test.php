<?php
/**
 * Unit Tests for BKGT Data Scraping Plugin
 */

class BKGT_Data_Scraping_Test extends BKGT_TestCase {

    protected function setupTestData() {
        // Create test database tables
        if (class_exists('BKGT_Database')) {
            $database = new BKGT_Database();
            $database->create_tables();
        }
    }

    protected function cleanupTestData() {
        // Clean up test data
        global $wpdb;
        if ($wpdb) {
            $wpdb->query("DELETE FROM bkgt_players WHERE name LIKE 'Test%'");
            $wpdb->query("DELETE FROM bkgt_events WHERE title LIKE 'Test%'");
        }
    }

    /**
     * Test plugin initialization
     */
    public function test_plugin_initialization() {
        $this->assertTrue(class_exists('BKGT_Data_Scraping'));
        $this->assertTrue(function_exists('bkgt_data_scraping_activate'));
        $this->assertTrue(function_exists('bkgt_data_scraping_deactivate'));
    }

    /**
     * Test database table creation
     */
    public function test_database_tables_created() {
        $this->assertTableExists('bkgt_players');
        $this->assertTableExists('bkgt_events');
        $this->assertTableExists('bkgt_teams');
        $this->assertTableExists('bkgt_player_stats');
    }

    /**
     * Test player table structure
     */
    public function test_player_table_structure() {
        $expected_columns = [
            'id', 'name', 'position', 'team', 'age', 'height', 'weight',
            'stats', 'created_at', 'updated_at'
        ];
        $this->assertTableHasColumns('bkgt_players', $expected_columns);
    }

    /**
     * Test events table structure
     */
    public function test_events_table_structure() {
        $expected_columns = [
            'id', 'title', 'date', 'location', 'home_team', 'away_team',
            'status', 'result', 'created_at', 'updated_at'
        ];
        $this->assertTableHasColumns('bkgt_events', $expected_columns);
    }

    /**
     * Test shortcode registration
     */
    public function test_shortcodes_registered() {
        $shortcodes = [
            'bkgt_players',
            'bkgt_events',
            'bkgt_team_overview',
            'bkgt_player_profile',
            'bkgt_admin_dashboard'
        ];

        foreach ($shortcodes as $shortcode) {
            $this->assertTrue(shortcode_exists($shortcode), "Shortcode '{$shortcode}' should be registered");
        }
    }

    /**
     * Test player data insertion
     */
    public function test_player_data_insertion() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        $player_data = BKGT_TestHelper::createSamplePlayer();

        $result = $wpdb->insert('bkgt_players', [
            'name' => $player_data['name'],
            'position' => $player_data['position'],
            'team' => $player_data['team'],
            'age' => $player_data['age'],
            'height' => $player_data['height'],
            'weight' => $player_data['weight'],
            'stats' => $player_data['stats']
        ]);

        $this->assertNotFalse($result);
        $this->assertGreaterThan(0, $wpdb->insert_id);
    }

    /**
     * Test event data insertion
     */
    public function test_event_data_insertion() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        $event_data = BKGT_TestHelper::createSampleEvent();

        $result = $wpdb->insert('bkgt_events', [
            'title' => $event_data['title'],
            'date' => $event_data['date'],
            'location' => $event_data['location'],
            'home_team' => $event_data['home_team'],
            'away_team' => $event_data['away_team'],
            'status' => $event_data['status']
        ]);

        $this->assertNotFalse($result);
        $this->assertGreaterThan(0, $wpdb->insert_id);
    }

    /**
     * Test data validation
     */
    public function test_player_data_validation() {
        $valid_data = BKGT_TestHelper::createSamplePlayer();
        $schema = [
            'name' => ['required' => true, 'type' => 'string', 'max_length' => 100],
            'position' => ['required' => true, 'type' => 'string', 'max_length' => 50],
            'age' => ['required' => false, 'type' => 'integer', 'min' => 0, 'max' => 100]
        ];

        $this->assertTrue(BKGT_TestHelper::validateDataStructure($valid_data, $schema));

        // Test invalid data
        $invalid_data = array_merge($valid_data, ['age' => 150]); // Age too high
        $this->assertFalse(BKGT_TestHelper::validateDataStructure($invalid_data, $schema));
    }

    /**
     * Test players shortcode output
     */
    public function test_players_shortcode_output() {
        // Insert test data
        global $wpdb;
        if ($wpdb) {
            $player_data = BKGT_TestHelper::createSamplePlayer();
            $wpdb->insert('bkgt_players', [
                'name' => $player_data['name'],
                'position' => $player_data['position'],
                'team' => $player_data['team']
            ]);
        }

        $this->assertShortcodeOutput('bkgt_players', 'Test Player');
    }

    /**
     * Test events shortcode output
     */
    public function test_events_shortcode_output() {
        // Insert test data
        global $wpdb;
        if ($wpdb) {
            $event_data = BKGT_TestHelper::createSampleEvent();
            $wpdb->insert('bkgt_events', [
                'title' => $event_data['title'],
                'location' => $event_data['location']
            ]);
        }

        $this->assertShortcodeOutput('bkgt_events', 'Test Match');
    }

    /**
     * Test data export functionality
     */
    public function test_data_export_functionality() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Insert test players
        $players = BKGT_TestHelper::generateRandomData('player', 3);
        foreach ($players as $player) {
            $wpdb->insert('bkgt_players', [
                'name' => $player['name'],
                'position' => $player['position'],
                'team' => $player['team']
            ]);
        }

        // Test CSV export (mock)
        $players_data = $wpdb->get_results("SELECT * FROM bkgt_players WHERE name LIKE 'Player%'", ARRAY_A);
        $this->assertCount(3, $players_data);

        // Verify CSV structure
        foreach ($players_data as $player) {
            $this->assertArrayHasKey('name', $player);
            $this->assertArrayHasKey('position', $player);
            $this->assertArrayHasKey('team', $player);
        }
    }

    /**
     * Test plugin activation hook
     */
    public function test_plugin_activation() {
        // Test that activation function exists and can be called
        $this->assertTrue(function_exists('bkgt_data_scraping_activate'));

        // Note: We can't actually call activation in tests as it modifies the database
        // This would be tested in integration tests
    }

    /**
     * Test plugin deactivation hook
     */
    public function test_plugin_deactivation() {
        $this->assertTrue(function_exists('bkgt_data_scraping_deactivate'));
    }
}