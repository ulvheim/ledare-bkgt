<?php
/**
 * Database management class for BKGT Data Scraping plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * BKGT_DataScraping_Database Class
 * 
 * Renamed from BKGT_Database to avoid conflicts with bkgt-core
 */
class BKGT_DataScraping_Database {

    /**
     * Database table names
     */
    private $tables = array();

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;

        if (!isset($wpdb)) {
            throw new Exception('WordPress database object not available');
        }

        $this->tables = array(
            'players' => $wpdb->prefix . 'bkgt_players',
            'events' => $wpdb->prefix . 'bkgt_events',
            'teams' => $wpdb->prefix . 'bkgt_teams',
            'statistics' => $wpdb->prefix . 'bkgt_statistics',
            'sources' => $wpdb->prefix . 'bkgt_sources',
            'scraping_logs' => $wpdb->prefix . 'bkgt_scraping_logs'
        );
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $current_version = get_option('bkgt_db_version', '0.0.0');

        // If tables already exist, skip creation
        if ($this->table_exists('players') && $this->table_exists('teams') && $this->table_exists('events')) {
            return true;
        }

        // Players table
        $players_table = "CREATE TABLE {$this->tables['players']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            player_id varchar(50) NOT NULL,
            team_id int(11) DEFAULT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            position varchar(50),
            birth_date date,
            jersey_number int(11),
            status enum('active','inactive','injured','suspended') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Events table
        $events_table = "CREATE TABLE {$this->tables['events']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            event_id varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            event_type enum('match','training','meeting','other') DEFAULT 'match',
            event_date datetime NOT NULL,
            location varchar(255),
            opponent varchar(100),
            home_away enum('home','away') DEFAULT 'home',
            result varchar(20),
            status enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Teams table - remove the problematic unique constraint for now
        $teams_table = "CREATE TABLE {$this->tables['teams']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            category enum('senior','junior','youth') DEFAULT 'senior',
            season varchar(20) DEFAULT '',
            coach varchar(100),
            source_id varchar(50) DEFAULT NULL,
            source_url varchar(500) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Statistics table - remove foreign keys from CREATE TABLE to avoid issues
        $statistics_table = "CREATE TABLE {$this->tables['statistics']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            player_id int(11) NOT NULL,
            event_id int(11) NOT NULL,
            goals int(11) DEFAULT 0,
            assists int(11) DEFAULT 0,
            minutes_played int(11) DEFAULT 0,
            yellow_cards int(11) DEFAULT 0,
            red_cards int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Sources table for tracking data sources
        $sources_table = "CREATE TABLE {$this->tables['sources']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            source_url varchar(500) NOT NULL,
            source_type enum('players','events','statistics') NOT NULL,
            last_scraped datetime,
            scrape_status enum('success','failed','pending') DEFAULT 'pending',
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY source_type (source_type),
            KEY last_scraped (last_scraped)
        ) $charset_collate;";

        // Scraping logs table
        $scraping_logs_table = "CREATE TABLE {$this->tables['scraping_logs']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            scrape_type enum('teams','players','events','all') NOT NULL,
            status enum('running','completed','failed') DEFAULT 'running',
            records_processed int(11) DEFAULT 0,
            records_added int(11) DEFAULT 0,
            records_updated int(11) DEFAULT 0,
            records_failed int(11) DEFAULT 0,
            error_message text,
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            duration_seconds int(11) DEFAULT NULL,
            source_url varchar(500),
            PRIMARY KEY (id),
            KEY scrape_type (scrape_type),
            KEY status (status),
            KEY started_at (started_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Execute table creation with error handling
        try {
            dbDelta($players_table);
            dbDelta($events_table);
            dbDelta($teams_table);
            dbDelta($statistics_table);
            dbDelta($sources_table);
            dbDelta($scraping_logs_table);

            // Update database version to indicate tables are created
            update_option('bkgt_db_version', '0.1.0');
        } catch (Exception $e) {
            error_log('BKGT Database: Error creating tables: ' . $e->getMessage());
            // Don't update version if there were errors
            return false;
        }

        return true;
    }

    /**
     * Upgrade database tables
     */
    public function upgrade_tables() {
        global $wpdb;

        $current_version = get_option('bkgt_db_version', '0.0.0');
        error_log("BKGT Database: Starting upgrade from version $current_version");

        // If already at latest version, skip
        if (version_compare($current_version, '1.0.0', '>=')) {
            error_log('BKGT Database: Already at latest version, skipping upgrade');
            return true;
        }

        // Only upgrade if tables are created (version 0.1.0 or higher)
        if (version_compare($current_version, '0.1.0', '<')) {
            error_log('BKGT Database: Tables not created yet, skipping upgrade');
            return false;
        }

        try {
            // Clean up duplicate teams data that might conflict with unique constraints
            if ($this->table_exists('teams')) {
                error_log('BKGT Database: Teams table exists, checking for duplicates');

                // First, let's check what duplicates exist
                $duplicates = $wpdb->get_results("
                    SELECT name, season, COUNT(*) as cnt, GROUP_CONCAT(id ORDER BY id DESC) as ids
                    FROM {$this->tables['teams']}
                    GROUP BY name, season
                    HAVING cnt > 1
                ");

                error_log('BKGT Database: Found ' . count($duplicates) . ' duplicate groups');

                if (!empty($duplicates)) {
                    error_log('BKGT Database: Cleaning up duplicates...');
                    foreach ($duplicates as $dup) {
                        $ids = explode(',', $dup->ids);
                        error_log('BKGT Database: Processing duplicate group: ' . $dup->name . ' (' . $dup->season . ') - IDs: ' . $dup->ids);
                        // Keep the first (highest) ID, delete the rest
                        array_shift($ids);
                        if (!empty($ids)) {
                            $ids_str = implode(',', $ids);
                            $result = $wpdb->query("DELETE FROM {$this->tables['teams']} WHERE id IN ($ids_str)");
                            error_log('BKGT Database: Deleted ' . $wpdb->rows_affected . ' duplicate rows');
                        }
                    }
                }

                // Now try to add all the necessary constraints and indexes
                error_log('BKGT Database: Attempting to add constraints and indexes');
                
                // Add indexes and constraints for all tables
                $this->add_index_safe('players', 'player_id', 'player_id');
                $this->add_index_safe('players', 'status', 'status');
                
                $this->add_index_safe('events', 'event_id', 'event_id');
                $this->add_index_safe('events', 'event_date', 'event_date');
                $this->add_index_safe('events', 'event_type', 'event_type');
                $this->add_index_safe('events', 'status', 'status');
                
                $this->add_index_safe('teams', 'category', 'category');
                
                $this->add_index_safe('statistics', 'player_id', 'player_id');
                $this->add_index_safe('statistics', 'event_id', 'event_id');
                $this->add_index_safe('statistics', 'player_event', 'player_id, event_id');
                
                $this->add_index_safe('sources', 'source_type', 'source_type');
                $this->add_index_safe('sources', 'last_scraped', 'last_scraped');
                
                $this->add_index_safe('scraping_logs', 'scrape_type', 'scrape_type');
                $this->add_index_safe('scraping_logs', 'status', 'status');
                $this->add_index_safe('scraping_logs', 'started_at', 'started_at');
                
                // Add unique constraints
                $this->add_unique_constraint_safe('teams', 'name_season', 'name', 'season');
                $this->add_unique_constraint_safe('players', 'player_id_unique', 'player_id', null);
                $this->add_unique_constraint_safe('events', 'event_id_unique', 'event_id', null);
                $this->add_unique_constraint_safe('statistics', 'player_event_unique', 'player_id', 'event_id');
            } else {
                error_log('BKGT Database: Teams table does not exist');
            }

            update_option('bkgt_db_version', '1.0.0');
            error_log('BKGT Database: Upgrade completed successfully');
            return true;

        } catch (Exception $e) {
            error_log('BKGT Database: Error upgrading tables: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if table exists
     */
    private function table_exists($table_key) {
        global $wpdb;

        if (!isset($this->tables[$table_key])) {
            return false;
        }

        $table_name = $this->tables[$table_key];
        $result = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

        return !empty($result);
    }

    /**
     * Add index if it doesn't exist
     */
    private function add_index_safe($table_key, $index_name, $columns) {
        global $wpdb;

        if (!isset($this->tables[$table_key])) {
            return false;
        }

        $table_name = $this->tables[$table_key];

        try {
            // Check if index already exists
            $existing = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = '$index_name'");

            if (empty($existing)) {
                // Add the index
                $sql = "ALTER TABLE $table_name ADD KEY $index_name ($columns)";
                $result = $wpdb->query($sql);

                if ($result === false) {
                    error_log("BKGT Database: Failed to add index $index_name to $table_name");
                    return false;
                } else {
                    error_log("BKGT Database: Successfully added index $index_name to $table_name");
                    return true;
                }
            } else {
                error_log("BKGT Database: Index $index_name already exists on $table_name");
                return true;
            }
        } catch (Exception $e) {
            error_log('BKGT Database: Error adding index: ' . $e->getMessage());
            return false;
        }
    }
    private function add_unique_constraint_safe($table_key, $constraint_name, $col1, $col2) {
        global $wpdb;

        if (!isset($this->tables[$table_key])) {
            return false;
        }

        $table_name = $this->tables[$table_key];

        try {
            // Check if constraint already exists
            $existing = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = '$constraint_name'");

            if (empty($existing)) {
                // Build the columns part
                $columns = $col2 ? "($col1, $col2)" : "($col1)";

                // Add the constraint
                $sql = "ALTER TABLE $table_name ADD UNIQUE KEY $constraint_name $columns";
                $result = $wpdb->query($sql);

                if ($result === false) {
                    error_log("BKGT Database: Failed to add unique constraint $constraint_name to $table_name");
                    return false;
                } else {
                    error_log("BKGT Database: Successfully added unique constraint $constraint_name to $table_name");
                    return true;
                }
            } else {
                error_log("BKGT Database: Unique constraint $constraint_name already exists on $table_name");
                return true;
            }
        } catch (Exception $e) {
            error_log('BKGT Database: Error adding unique constraint: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Add unique constraint if it doesn't exist
     */
    private function add_unique_constraint($table_key, $constraint_name, $columns) {
        global $wpdb;

        if (!isset($this->tables[$table_key])) {
            return false;
        }

        $table_name = $this->tables[$table_key];

        // Check if constraint already exists
        $existing = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = '$constraint_name'");

        if (empty($existing)) {
            // Add the constraint
            $wpdb->query("ALTER TABLE $table_name ADD UNIQUE KEY $constraint_name $columns");
        }

        return true;
    }

    /**
     * Get table name
     */
    public function get_table($table) {
        return isset($this->tables[$table]) ? $this->tables[$table] : false;
    }

    /**
     * Insert player data
     */
    public function insert_player($data) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->tables['players'],
            array(
                'player_id' => $data['player_id'],
                'team_id' => isset($data['team_id']) ? $data['team_id'] : null,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'position' => isset($data['position']) ? $data['position'] : null,
                'birth_date' => isset($data['birth_date']) ? $data['birth_date'] : null,
                'jersey_number' => isset($data['jersey_number']) ? $data['jersey_number'] : null,
                'status' => isset($data['status']) ? $data['status'] : 'active'
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Insert event data
     */
    public function insert_event($data) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->tables['events'],
            array(
                'event_id' => $data['event_id'],
                'title' => $data['title'],
                'event_type' => isset($data['event_type']) ? $data['event_type'] : 'match',
                'event_date' => $data['event_date'],
                'location' => isset($data['location']) ? $data['location'] : null,
                'opponent' => isset($data['opponent']) ? $data['opponent'] : null,
                'home_away' => isset($data['home_away']) ? $data['home_away'] : 'home',
                'result' => isset($data['result']) ? $data['result'] : null,
                'status' => isset($data['status']) ? $data['status'] : 'scheduled'
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Insert team data
     */
    public function insert_team($data) {
        global $wpdb;

        // Check if team with this source_id already exists
        if (!empty($data['source_id'])) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->tables['teams']} WHERE source_id = %s",
                $data['source_id']
            ));

            if ($existing) {
                // Team already exists, return false to indicate no new insertion
                return false;
            }
        }

        $result = $wpdb->insert(
            $this->tables['teams'],
            array(
                'name' => $data['name'],
                'category' => isset($data['category']) ? $data['category'] : 'senior',
                'season' => isset($data['season']) ? $data['season'] : date('Y'),
                'coach' => isset($data['coach']) ? $data['coach'] : null,
                'source_id' => isset($data['source_id']) ? $data['source_id'] : null,
                'source_url' => isset($data['source_url']) ? $data['source_url'] : null
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );

        return $result ? true : false;
    }

    /**
     * Insert statistics data
     */
    public function insert_statistics($data) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->tables['statistics'],
            array(
                'player_id' => $data['player_id'],
                'event_id' => $data['event_id'],
                'goals' => isset($data['goals']) ? $data['goals'] : 0,
                'assists' => isset($data['assists']) ? $data['assists'] : 0,
                'minutes_played' => isset($data['minutes_played']) ? $data['minutes_played'] : 0,
                'yellow_cards' => isset($data['yellow_cards']) ? $data['yellow_cards'] : 0,
                'red_cards' => isset($data['red_cards']) ? $data['red_cards'] : 0
            ),
            array('%d', '%d', '%d', '%d', '%d', '%d', '%d')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get all players
     */
    public function get_players($status = 'active', $limit = -1, $position = '') {
        global $wpdb;

        $where_clauses = array();
        $where_values = array();

        if ($status !== 'all') {
            $where_clauses[] = "status = %s";
            $where_values[] = $status;
        }

        if (!empty($position)) {
            $where_clauses[] = "position = %s";
            $where_values[] = $position;
        }

        $where = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
        $limit_sql = ($limit > 0) ? $wpdb->prepare("LIMIT %d", $limit) : "";

        $query = "SELECT * FROM {$this->tables['players']} $where ORDER BY last_name, first_name $limit_sql";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Get all events
     */
    public function get_events($status = 'all', $limit = null, $upcoming_only = false) {
        global $wpdb;

        $where_clauses = array();
        $where_values = array();

        if ($status !== 'all') {
            $where_clauses[] = "status = %s";
            $where_values[] = $status;
        }

        if ($upcoming_only) {
            $where_clauses[] = "event_date >= CURDATE()";
        }

        $where = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
        $limit_sql = $limit ? $wpdb->prepare("LIMIT %d", $limit) : '';

        $query = "SELECT * FROM {$this->tables['events']} $where ORDER BY event_date DESC $limit_sql";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Get all teams
     */
    public function get_teams($status = 'all', $limit = null) {
        global $wpdb;

        $where_clauses = array();
        $where_values = array();

        if ($status !== 'all') {
            $where_clauses[] = "category = %s";
            $where_values[] = $status;
        }

        $where = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
        $limit_sql = $limit ? $wpdb->prepare("LIMIT %d", $limit) : '';

        $query = "SELECT * FROM {$this->tables['teams']} $where ORDER BY name $limit_sql";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        return $wpdb->get_results($query);
    }

    /**
     * Get players by team
     */
    public function get_players_by_team($team_id, $status = 'active') {
        global $wpdb;

        $where_clauses = array("team_id = %d");
        $where_values = array($team_id);

        if ($status !== 'all') {
            $where_clauses[] = "status = %s";
            $where_values[] = $status;
        }

        $where = "WHERE " . implode(" AND ", $where_clauses);

        $query = "SELECT * FROM {$this->tables['players']} $where ORDER BY last_name, first_name";

        return $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
    }

    /**
     * Get player statistics
     */
    public function get_player_statistics($player_id) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.*, e.title as event_title, e.event_date
                 FROM {$this->tables['statistics']} s
                 JOIN {$this->tables['events']} e ON s.event_id = e.id
                 WHERE s.player_id = %d
                 ORDER BY e.event_date DESC",
                $player_id
            ),
            ARRAY_A
        );
    }

    /**
     * Update source scrape status
     */
    public function update_source_status($source_url, $status, $error_message = null) {
        global $wpdb;

        $data = array(
            'last_scraped' => current_time('mysql'),
            'scrape_status' => $status,
            'error_message' => $error_message,
            'updated_at' => current_time('mysql')
        );

        $where = array('source_url' => $source_url);

        $result = $wpdb->update(
            $this->tables['sources'],
            $data,
            $where,
            array('%s', '%s', '%s', '%s'),
            array('%s')
        );

        // If no row was updated, insert new source
        if ($result === 0) {
            $data['source_url'] = $source_url;
            $data['source_type'] = 'players'; // Default type
            $wpdb->insert(
                $this->tables['sources'],
                $data,
                array('%s', '%s', '%s', '%s', '%s', '%s')
            );
        }

        return $result;
    }

    /**
     * Assign player to event (creates statistics record if not exists)
     */
    public function assign_player_to_event($player_id, $event_id) {
        global $wpdb;

        // Check if assignment already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->tables['statistics']} WHERE player_id = %d AND event_id = %d",
            $player_id, $event_id
        ));

        if ($exists) {
            return true; // Already assigned
        }

        // Create statistics record (assignment)
        return $wpdb->insert(
            $this->tables['statistics'],
            array(
                'player_id' => $player_id,
                'event_id' => $event_id,
                'goals' => 0,
                'assists' => 0,
                'minutes_played' => 0,
                'yellow_cards' => 0,
                'red_cards' => 0
            ),
            array('%d', '%d', '%d', '%d', '%d', '%d', '%d')
        );
    }

    /**
     * Remove player from event
     */
    public function remove_player_from_event($player_id, $event_id) {
        global $wpdb;

        return $wpdb->delete(
            $this->tables['statistics'],
            array(
                'player_id' => $player_id,
                'event_id' => $event_id
            ),
            array('%d', '%d')
        );
    }

    /**
     * Remove all players from event
     */
    public function remove_all_players_from_event($event_id) {
        global $wpdb;

        return $wpdb->delete(
            $this->tables['statistics'],
            array('event_id' => $event_id),
            array('%d')
        );
    }

    /**
     * Get players assigned to event
     */
    public function get_event_players($event_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.* FROM {$this->tables['players']} p
             INNER JOIN {$this->tables['statistics']} s ON p.id = s.player_id
             WHERE s.event_id = %d
             ORDER BY p.last_name, p.first_name",
            $event_id
        ), ARRAY_A);
    }

    /**
     * Get all players
     */
    public function get_all_players() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$this->tables['players']}
             ORDER BY last_name, first_name",
            ARRAY_A
        );
    }

    /**
     * Get all events
     */
    public function get_all_events() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$this->tables['events']}
             ORDER BY event_date DESC",
            ARRAY_A
        );
    }

    /**
     * Check if player assignment exists
     */
    public function player_assignment_exists($player_id, $event_id) {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables['statistics']}
             WHERE player_id = %d AND event_id = %d",
            $player_id, $event_id
        ));

        return $count > 0;
    }

    /**
     * Remove player assignment
     */
    public function remove_player_assignment($player_id, $event_id) {
        global $wpdb;

        return $wpdb->delete(
            $this->tables['statistics'],
            array(
                'player_id' => $player_id,
                'event_id' => $event_id
            ),
            array('%d', '%d')
        );
    }

    /**
     * Get single player by ID
     */
    public function get_player($player_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['players']} WHERE id = %d",
            $player_id
        ), ARRAY_A);
    }

    /**
     * Start scraping log
     */
    public function start_scraping_log($scrape_type, $source_url = null) {
        global $wpdb;

        return $wpdb->insert(
            $this->tables['scraping_logs'],
            array(
                'scrape_type' => $scrape_type,
                'status' => 'running',
                'source_url' => $source_url,
                'started_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        ) ? $wpdb->insert_id : false;
    }

    /**
     * Update scraping log progress
     */
    public function update_scraping_log($log_id, $data) {
        global $wpdb;

        $update_data = array();
        $update_format = array();

        if (isset($data['records_processed'])) {
            $update_data['records_processed'] = $data['records_processed'];
            $update_format[] = '%d';
        }
        if (isset($data['records_added'])) {
            $update_data['records_added'] = $data['records_added'];
            $update_format[] = '%d';
        }
        if (isset($data['records_updated'])) {
            $update_data['records_updated'] = $data['records_updated'];
            $update_format[] = '%d';
        }
        if (isset($data['records_failed'])) {
            $update_data['records_failed'] = $data['records_failed'];
            $update_format[] = '%d';
        }

        if (!empty($update_data)) {
            return $wpdb->update(
                $this->tables['scraping_logs'],
                $update_data,
                array('id' => $log_id),
                $update_format,
                array('%d')
            );
        }

        return true;
    }

    /**
     * Complete scraping log
     */
    public function complete_scraping_log($log_id, $status = 'completed', $error_message = null) {
        global $wpdb;

        $completed_at = current_time('mysql');
        $started_at = $wpdb->get_var($wpdb->prepare(
            "SELECT started_at FROM {$this->tables['scraping_logs']} WHERE id = %d",
            $log_id
        ));

        $duration = null;
        if ($started_at) {
            $duration = strtotime($completed_at) - strtotime($started_at);
        }

        return $wpdb->update(
            $this->tables['scraping_logs'],
            array(
                'status' => $status,
                'error_message' => $error_message,
                'completed_at' => $completed_at,
                'duration_seconds' => $duration
            ),
            array('id' => $log_id),
            array('%s', '%s', '%s', '%d'),
            array('%d')
        );
    }

    /**
     * Get scraping logs
     */
    public function get_scraping_logs($limit = 10, $scrape_type = null) {
        global $wpdb;

        $where_clauses = array();
        $where_values = array();

        if ($scrape_type && $scrape_type !== 'all') {
            $where_clauses[] = "scrape_type = %s";
            $where_values[] = $scrape_type;
        }

        $where = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
        $limit_sql = $limit ? $wpdb->prepare("LIMIT %d", $limit) : '';

        $query = "SELECT * FROM {$this->tables['scraping_logs']} $where ORDER BY started_at DESC $limit_sql";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Get scraping statistics
     */
    public function get_scraping_stats() {
        global $wpdb;

        $stats = array(
            'total_runs' => 0,
            'successful_runs' => 0,
            'failed_runs' => 0,
            'last_run' => null,
            'avg_duration' => 0,
            'total_records_processed' => 0
        );

        // Get total runs
        $stats['total_runs'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['scraping_logs']}");

        // Get successful runs
        $stats['successful_runs'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['scraping_logs']} WHERE status = 'completed'");

        // Get failed runs
        $stats['failed_runs'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['scraping_logs']} WHERE status = 'failed'");

        // Get last run
        $stats['last_run'] = $wpdb->get_row("SELECT * FROM {$this->tables['scraping_logs']} ORDER BY started_at DESC LIMIT 1", ARRAY_A);

        // Get average duration
        $stats['avg_duration'] = $wpdb->get_var("SELECT AVG(duration_seconds) FROM {$this->tables['scraping_logs']} WHERE status = 'completed' AND duration_seconds IS NOT NULL");

        // Get total records processed
        $stats['total_records_processed'] = $wpdb->get_var("SELECT SUM(records_processed) FROM {$this->tables['scraping_logs']} WHERE status = 'completed'");

        return $stats;
    }

    /**
     * Get player stats (alias for get_player_statistics)
     */
    public function get_player_stats($player_id) {
        return $this->get_player_statistics($player_id);
    }

    /**
     * Get events for a specific player
     */
    public function get_player_events($player_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, s.goals, s.assists, s.yellow_cards, s.red_cards
             FROM {$this->tables['events']} e
             INNER JOIN {$this->tables['statistics']} s ON e.id = s.event_id
             WHERE s.player_id = %d
             ORDER BY e.event_date DESC",
            $player_id
        ), ARRAY_A);
    }

    /**
     * Get team statistics overview
     */
    public function get_team_stats() {
        global $wpdb;

        $stats = new stdClass();

        // Total players
        $stats->total_players = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->tables['players']}"
        );

        // Active players (all players in database are considered active since no status column)
        $stats->active_players = $stats->total_players;

        // Upcoming matches (events in the future)
        $stats->upcoming_matches = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tables['events']}
             WHERE event_date >= %s AND status = %s",
            current_time('Y-m-d'),
            'scheduled'
        ));

        // Total teams (all teams in database)
        $stats->total_teams = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->tables['teams']}"
        );

        // Active teams (all teams in database are considered active)
        $stats->active_teams = $stats->total_teams;

        return $stats;
    }
}