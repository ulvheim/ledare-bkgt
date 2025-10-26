<?php
/**
 * Database management class for BKGT Data Scraping plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * BKGT Database Class
 */
class BKGT_Database {

    /**
     * Database table names
     */
    private $tables = array();

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;

        $this->tables = array(
            'players' => $wpdb->prefix . 'bkgt_players',
            'events' => $wpdb->prefix . 'bkgt_events',
            'statistics' => $wpdb->prefix . 'bkgt_statistics',
            'sources' => $wpdb->prefix . 'bkgt_sources'
        );
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Players table
        $players_table = "CREATE TABLE {$this->tables['players']} (
            id int(11) NOT NULL AUTO_INCREMENT,
            player_id varchar(50) NOT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            position varchar(50),
            birth_date date,
            jersey_number int(11),
            status enum('active','inactive','injured','suspended') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY player_id (player_id),
            KEY status (status)
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
            PRIMARY KEY (id),
            UNIQUE KEY event_id (event_id),
            KEY event_date (event_date),
            KEY event_type (event_type),
            KEY status (status)
        ) $charset_collate;";

        // Statistics table
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
            PRIMARY KEY (id),
            UNIQUE KEY player_event (player_id, event_id),
            KEY player_id (player_id),
            KEY event_id (event_id),
            FOREIGN KEY (player_id) REFERENCES {$this->tables['players']}(id) ON DELETE CASCADE,
            FOREIGN KEY (event_id) REFERENCES {$this->tables['events']}(id) ON DELETE CASCADE
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

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Execute table creation
        dbDelta($players_table);
        dbDelta($events_table);
        dbDelta($statistics_table);
        dbDelta($sources_table);

        // Update database version
        update_option('bkgt_db_version', '1.0.0');
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
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'position' => isset($data['position']) ? $data['position'] : null,
                'birth_date' => isset($data['birth_date']) ? $data['birth_date'] : null,
                'jersey_number' => isset($data['jersey_number']) ? $data['jersey_number'] : null,
                'status' => isset($data['status']) ? $data['status'] : 'active'
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%s')
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
}