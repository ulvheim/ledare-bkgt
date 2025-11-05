<?php
/**
 * Database Tests for BKGT Plugin Tables and Operations
 */

class BKGT_Database_Test extends BKGT_TestCase {

    /**
     * Test all required tables exist
     */
    public function test_all_tables_exist() {
        $required_tables = [
            'bkgt_players',
            'bkgt_events',
            'bkgt_teams',
            'bkgt_player_stats',
            'bkgt_inventory',
            'bkgt_inventory_assignments',
            'bkgt_documents',
            'bkgt_user_team_assignments'
        ];

        foreach ($required_tables as $table) {
            $this->assertTableExists($table);
        }
    }

    /**
     * Test players table schema
     */
    public function test_players_table_schema() {
        $expected_columns = [
            'id' => 'int',
            'name' => 'varchar',
            'position' => 'varchar',
            'team' => 'varchar',
            'age' => 'int',
            'height' => 'varchar',
            'weight' => 'varchar',
            'stats' => 'text',
            'created_at' => 'datetime',
            'updated_at' => 'datetime'
        ];

        $this->assertTableHasColumns('bkgt_players', array_keys($expected_columns));

        // Test data types (basic check)
        global $wpdb;
        if ($wpdb) {
            $columns = $wpdb->get_results("DESCRIBE bkgt_players");
            foreach ($columns as $column) {
                if (isset($expected_columns[$column->Field])) {
                    $expected_type = $expected_columns[$column->Field];
                    $this->assertStringContains($column->Type, $expected_type,
                        "Column {$column->Field} should be {$expected_type} type");
                }
            }
        }
    }

    /**
     * Test events table schema
     */
    public function test_events_table_schema() {
        $expected_columns = [
            'id', 'title', 'date', 'location', 'home_team', 'away_team',
            'status', 'result', 'created_at', 'updated_at'
        ];

        $this->assertTableHasColumns('bkgt_events', $expected_columns);
    }

    /**
     * Test inventory table schema
     */
    public function test_inventory_table_schema() {
        $expected_columns = [
            'id', 'title', 'type', 'condition', 'assigned_to', 'location',
            'purchase_date', 'value', 'notes', 'created_at', 'updated_at'
        ];

        $this->assertTableHasColumns('bkgt_inventory', $expected_columns);
    }

    /**
     * Test foreign key relationships
     */
    public function test_foreign_key_relationships() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Test inventory assignments reference inventory items
        $assignment = $wpdb->get_row("
            SELECT ia.*, i.title as item_title
            FROM bkgt_inventory_assignments ia
            LEFT JOIN bkgt_inventory i ON ia.item_id = i.id
            LIMIT 1
        ");

        if ($assignment) {
            $this->assertNotNull($assignment->item_title,
                'Inventory assignment should reference valid inventory item');
        }
    }

    /**
     * Test data integrity constraints
     */
    public function test_data_integrity_constraints() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Test NOT NULL constraints
        $columns = $wpdb->get_results("DESCRIBE bkgt_players");
        $not_null_columns = ['name', 'position', 'team'];

        foreach ($columns as $column) {
            if (in_array($column->Field, $not_null_columns)) {
                $this->assertStringContains($column->Null, 'NO',
                    "Column {$column->Field} should be NOT NULL");
            }
        }
    }

    /**
     * Test index existence
     */
    public function test_database_indexes() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Check for primary keys
        $indexes = $wpdb->get_results("SHOW INDEX FROM bkgt_players");
        $has_primary = false;
        foreach ($indexes as $index) {
            if ($index->Key_name === 'PRIMARY') {
                $has_primary = true;
                break;
            }
        }
        $this->assertTrue($has_primary, 'Players table should have a primary key');

        // Check for other important indexes
        $index_columns = array_column($indexes, 'Column_name');
        $this->assertContains('team', $index_columns, 'Team column should be indexed');
        $this->assertContains('position', $index_columns, 'Position column should be indexed');
    }

    /**
     * Test data insertion operations
     */
    public function test_data_insertion_operations() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Test player insertion
        $player_data = BKGT_TestHelper::createSamplePlayer();
        $result = $wpdb->insert('bkgt_players', [
            'name' => $player_data['name'],
            'position' => $player_data['position'],
            'team' => $player_data['team'],
            'age' => $player_data['age'],
            'stats' => $player_data['stats']
        ]);

        $this->assertNotFalse($result);
        $player_id = $wpdb->insert_id;

        // Verify insertion
        $inserted_player = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM bkgt_players WHERE id = %d",
            $player_id
        ));

        $this->assertNotNull($inserted_player);
        $this->assertEquals($player_data['name'], $inserted_player->name);
        $this->assertEquals($player_data['position'], $inserted_player->position);
    }

    /**
     * Test data update operations
     */
    public function test_data_update_operations() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Insert test data
        $player_data = BKGT_TestHelper::createSamplePlayer();
        $wpdb->insert('bkgt_players', [
            'name' => $player_data['name'],
            'position' => $player_data['position'],
            'team' => $player_data['team']
        ]);

        $player_id = $wpdb->insert_id;

        // Update data
        $new_name = 'Updated Player Name';
        $result = $wpdb->update(
            'bkgt_players',
            ['name' => $new_name],
            ['id' => $player_id]
        );

        $this->assertNotFalse($result);

        // Verify update
        $updated_player = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM bkgt_players WHERE id = %d",
            $player_id
        ));

        $this->assertEquals($new_name, $updated_player->name);
    }

    /**
     * Test data deletion operations
     */
    public function test_data_deletion_operations() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Insert test data
        $player_data = BKGT_TestHelper::createSamplePlayer();
        $wpdb->insert('bkgt_players', [
            'name' => $player_data['name'],
            'position' => $player_data['position'],
            'team' => $player_data['team']
        ]);

        $player_id = $wpdb->insert_id;

        // Delete data
        $result = $wpdb->delete('bkgt_players', ['id' => $player_id]);
        $this->assertEquals(1, $result);

        // Verify deletion
        $deleted_player = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM bkgt_players WHERE id = %d",
            $player_id
        ));

        $this->assertNull($deleted_player);
    }

    /**
     * Test complex queries
     */
    public function test_complex_queries() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Insert test data
        $players = BKGT_TestHelper::generateRandomData('player', 20);
        foreach ($players as $player) {
            $wpdb->insert('bkgt_players', [
                'name' => $player['name'],
                'position' => $player['position'],
                'team' => 'Query Test Team',
                'age' => $player['age']
            ]);
        }

        // Test complex SELECT with JOIN
        $results = $wpdb->get_results("
            SELECT p.*, COUNT(*) as team_count
            FROM bkgt_players p
            WHERE p.team = 'Query Test Team'
            GROUP BY p.position
            ORDER BY team_count DESC
        ");

        $this->assertNotEmpty($results);

        // Test aggregation queries
        $stats = $wpdb->get_row("
            SELECT
                COUNT(*) as total_players,
                AVG(age) as avg_age,
                MIN(age) as min_age,
                MAX(age) as max_age
            FROM bkgt_players
            WHERE team = 'Query Test Team'
        ");

        $this->assertEquals(20, $stats->total_players);
        $this->assertGreaterThan(0, $stats->avg_age);
        $this->assertGreaterThan(0, $stats->min_age);
        $this->assertGreaterThan(0, $stats->max_age);

        // Clean up
        $wpdb->query("DELETE FROM bkgt_players WHERE team = 'Query Test Team'");
    }

    /**
     * Test transaction handling
     */
    public function test_transaction_handling() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Insert multiple related records
            $player_data = BKGT_TestHelper::createSamplePlayer();
            $wpdb->insert('bkgt_players', [
                'name' => $player_data['name'],
                'position' => $player_data['position'],
                'team' => 'Transaction Test Team'
            ]);

            $player_id = $wpdb->insert_id;

            // Insert related stats
            $wpdb->insert('bkgt_player_stats', [
                'player_id' => $player_id,
                'season' => date('Y'),
                'games_played' => 10,
                'touchdowns' => 5
            ]);

            // Commit transaction
            $wpdb->query('COMMIT');

            // Verify both records exist
            $player = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM bkgt_players WHERE id = %d",
                $player_id
            ));

            $stats = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM bkgt_player_stats WHERE player_id = %d",
                $player_id
            ));

            $this->assertNotNull($player);
            $this->assertNotNull($stats);

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }

        // Clean up
        $wpdb->query("DELETE FROM bkgt_player_stats WHERE player_id = {$player_id}");
        $wpdb->query("DELETE FROM bkgt_players WHERE id = {$player_id}");
    }

    /**
     * Test database performance
     */
    public function test_database_performance() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Insert bulk data for performance testing
        $start_time = microtime(true);

        $players = BKGT_TestHelper::generateRandomData('player', 100);
        foreach ($players as $player) {
            $wpdb->insert('bkgt_players', [
                'name' => $player['name'],
                'position' => $player['position'],
                'team' => 'Performance Test Team',
                'age' => $player['age']
            ]);
        }

        $insert_time = microtime(true) - $start_time;

        // Query performance
        $query_start = microtime(true);
        $results = $wpdb->get_results("
            SELECT * FROM bkgt_players
            WHERE team = 'Performance Test Team'
            ORDER BY name
        ");
        $query_time = microtime(true) - $query_start;

        // Performance assertions (reasonable times)
        $this->assertLessThan(5.0, $insert_time, 'Bulk insert should complete within 5 seconds');
        $this->assertLessThan(1.0, $query_time, 'Query should complete within 1 second');
        $this->assertCount(100, $results);

        // Clean up
        $wpdb->query("DELETE FROM bkgt_players WHERE team = 'Performance Test Team'");
    }

    /**
     * Test data backup and restore concepts
     */
    public function test_data_backup_restore() {
        global $wpdb;

        if (!$wpdb) {
            $this->markTestSkipped('WordPress database not available');
            return;
        }

        // Insert test data
        $player_data = BKGT_TestHelper::createSamplePlayer();
        $wpdb->insert('bkgt_players', [
            'name' => $player_data['name'],
            'position' => $player_data['position'],
            'team' => 'Backup Test Team'
        ]);

        $player_id = $wpdb->insert_id;

        // "Backup" by selecting data
        $backup_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM bkgt_players WHERE id = %d",
            $player_id
        ));

        $this->assertNotNull($backup_data);

        // Delete original
        $wpdb->delete('bkgt_players', ['id' => $player_id]);

        // "Restore" by re-inserting
        $wpdb->insert('bkgt_players', [
            'name' => $backup_data->name,
            'position' => $backup_data->position,
            'team' => $backup_data->team
        ]);

        $restored_id = $wpdb->insert_id;

        // Verify restore
        $restored_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM bkgt_players WHERE id = %d",
            $restored_id
        ));

        $this->assertEquals($backup_data->name, $restored_data->name);
        $this->assertEquals($backup_data->position, $restored_data->position);

        // Clean up
        $wpdb->delete('bkgt_players', ['id' => $restored_id]);
    }
}