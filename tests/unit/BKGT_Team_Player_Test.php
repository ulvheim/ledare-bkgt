<?php
/**
 * Unit Tests for BKGT Team Player Plugin
 */

class BKGT_Team_Player_Test extends BKGT_TestCase {

    protected function setupTestData() {
        // Create test data for team player functionality
        global $wpdb;
        if ($wpdb) {
            // Insert test players
            $players = BKGT_TestHelper::generateRandomData('player', 5);
            foreach ($players as $player) {
                $wpdb->insert('bkgt_players', [
                    'name' => $player['name'],
                    'position' => $player['position'],
                    'team' => 'Test Team',
                    'age' => $player['age']
                ]);
            }

            // Insert test events
            $events = BKGT_TestHelper::generateRandomData('event', 3);
            foreach ($events as $event) {
                $wpdb->insert('bkgt_events', [
                    'title' => $event['title'],
                    'date' => $event['date'],
                    'home_team' => 'Test Team',
                    'away_team' => $event['away_team']
                ]);
            }
        }
    }

    protected function cleanupTestData() {
        global $wpdb;
        if ($wpdb) {
            $wpdb->query("DELETE FROM bkgt_players WHERE team = 'Test Team'");
            $wpdb->query("DELETE FROM bkgt_events WHERE home_team = 'Test Team'");
        }
    }

    /**
     * Test plugin class exists and initializes
     */
    public function test_plugin_class_exists() {
        $this->assertTrue(class_exists('BKGT_Team_Player'));
    }

    /**
     * Test shortcode registrations
     */
    public function test_shortcodes_registered() {
        $shortcodes = [
            'bkgt_team_page',
            'bkgt_player_dossier',
            'bkgt_performance_page',
            'bkgt_team_overview',
            'bkgt_players',
            'bkgt_events'
        ];

        foreach ($shortcodes as $shortcode) {
            $this->assertTrue(shortcode_exists($shortcode), "Shortcode '{$shortcode}' should be registered");
        }
    }

    /**
     * Test team overview shortcode
     */
    public function test_team_overview_shortcode() {
        $this->assertShortcodeOutput('bkgt_team_overview', 'Test Team');
    }

    /**
     * Test players shortcode with team filter
     */
    public function test_players_shortcode_with_team() {
        $this->assertShortcodeOutput('bkgt_players', 'Test Team', ['team' => 'Test Team']);
    }

    /**
     * Test events shortcode
     */
    public function test_events_shortcode() {
        $this->assertShortcodeOutput('bkgt_events', 'Test Team');
    }

    /**
     * Test player dossier shortcode
     */
    public function test_player_dossier_shortcode() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Get a test player ID
        $player = $wpdb->get_row("SELECT id FROM bkgt_players WHERE team = 'Test Team' LIMIT 1");

        if ($player) {
            $this->assertShortcodeOutput('bkgt_player_dossier', 'Player', ['id' => $player->id]);
        }
    }

    /**
     * Test team page shortcode
     */
    public function test_team_page_shortcode() {
        $this->assertShortcodeOutput('bkgt_team_page', 'Test Team', ['team' => 'Test Team']);
    }

    /**
     * Test performance page shortcode
     */
    public function test_performance_page_shortcode() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        $player = $wpdb->get_row("SELECT id FROM bkgt_players WHERE team = 'Test Team' LIMIT 1");

        if ($player) {
            $this->assertShortcodeOutput('bkgt_performance_page', 'Performance', ['player_id' => $player->id]);
        }
    }

    /**
     * Test player data retrieval
     */
    public function test_player_data_retrieval() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        $players = $wpdb->get_results("SELECT * FROM bkgt_players WHERE team = 'Test Team'");

        $this->assertNotEmpty($players);
        $this->assertGreaterThanOrEqual(5, count($players));

        // Verify data structure
        foreach ($players as $player) {
            $this->assertObjectHasAttribute('name', $player);
            $this->assertObjectHasAttribute('position', $player);
            $this->assertObjectHasAttribute('team', $player);
            $this->assertEquals('Test Team', $player->team);
        }
    }

    /**
     * Test event data retrieval
     */
    public function test_event_data_retrieval() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        $events = $wpdb->get_results("SELECT * FROM bkgt_events WHERE home_team = 'Test Team'");

        $this->assertNotEmpty($events);
        $this->assertGreaterThanOrEqual(3, count($events));

        // Verify data structure
        foreach ($events as $event) {
            $this->assertObjectHasAttribute('title', $event);
            $this->assertObjectHasAttribute('date', $event);
            $this->assertObjectHasAttribute('home_team', $event);
            $this->assertEquals('Test Team', $event->home_team);
        }
    }

    /**
     * Test player statistics calculation
     */
    public function test_player_statistics_calculation() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Insert player with stats
        $stats = json_encode([
            'games_played' => 10,
            'touchdowns' => 5,
            'yards' => 450,
            'completions' => 25,
            'attempts' => 40
        ]);

        $wpdb->insert('bkgt_players', [
            'name' => 'Stats Test Player',
            'position' => 'QB',
            'team' => 'Test Team',
            'stats' => $stats
        ]);

        $player_id = $wpdb->insert_id;

        // Retrieve and verify stats
        $player = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM bkgt_players WHERE id = %d",
            $player_id
        ));

        $this->assertNotNull($player);
        $this->assertJson($player->stats);

        $decoded_stats = json_decode($player->stats, true);
        $this->assertEquals(10, $decoded_stats['games_played']);
        $this->assertEquals(5, $decoded_stats['touchdowns']);
    }

    /**
     * Test team roster functionality
     */
    public function test_team_roster_functionality() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Count players by position
        $position_counts = $wpdb->get_results("
            SELECT position, COUNT(*) as count
            FROM bkgt_players
            WHERE team = 'Test Team'
            GROUP BY position
        ");

        $this->assertNotEmpty($position_counts);

        // Verify we have different positions
        $positions = array_column($position_counts, 'position');
        $this->assertContains('QB', $positions);
    }

    /**
     * Test event scheduling
     */
    public function test_event_scheduling() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Get upcoming events
        $upcoming_events = $wpdb->get_results("
            SELECT * FROM bkgt_events
            WHERE date >= NOW() AND home_team = 'Test Team'
            ORDER BY date ASC
        ");

        $this->assertNotEmpty($upcoming_events);

        // Verify dates are in future
        foreach ($upcoming_events as $event) {
            $event_date = strtotime($event->date);
            $now = time();
            $this->assertGreaterThanOrEqual($now, $event_date);
        }
    }

    /**
     * Test data filtering and searching
     */
    public function test_data_filtering() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Test position filtering
        $qb_players = $wpdb->get_results("
            SELECT * FROM bkgt_players
            WHERE team = 'Test Team' AND position = 'QB'
        ");

        $this->assertNotEmpty($qb_players);

        foreach ($qb_players as $player) {
            $this->assertEquals('QB', $player->position);
        }

        // Test name search
        $search_results = $wpdb->get_results("
            SELECT * FROM bkgt_players
            WHERE team = 'Test Team' AND name LIKE '%Player%'
        ");

        $this->assertNotEmpty($search_results);
    }
}