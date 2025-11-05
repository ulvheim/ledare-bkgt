<?php
/**
 * Database setup for BKGT Team & Player Management
 * This file handles database table creation and modifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Team_Player_Database {

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Check if teams table exists and add missing columns
        self::upgrade_teams_table();

        // Players table
        $players_table = $wpdb->prefix . 'bkgt_players';
        $sql = "CREATE TABLE IF NOT EXISTS $players_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            svenskalag_id varchar(50) DEFAULT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            display_name varchar(200) GENERATED ALWAYS AS (CONCAT(first_name, ' ', last_name)) STORED,
            jersey_number int(11) DEFAULT NULL,
            position varchar(50) DEFAULT NULL,
            birth_date date DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            team_id mediumint(9) DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY svenskalag_id (svenskalag_id),
            KEY team_id (team_id),
            KEY status (status),
            KEY position (position),
            FULLTEXT KEY player_search (first_name, last_name, display_name)
        ) $charset_collate;";
        $wpdb->query($sql);

        // Player notes/dossiers table
        $notes_table = $wpdb->prefix . 'bkgt_player_notes';
        $sql = "CREATE TABLE IF NOT EXISTS $notes_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            player_id mediumint(9) NOT NULL,
            author_id bigint(20) unsigned NOT NULL,
            note_type varchar(50) DEFAULT 'general',
            title varchar(200) DEFAULT NULL,
            content text NOT NULL,
            is_private tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY player_id (player_id),
            KEY author_id (author_id),
            KEY note_type (note_type),
            KEY is_private (is_private)
        ) $charset_collate;";
        $wpdb->query($sql);

        // Performance ratings table
        $ratings_table = $wpdb->prefix . 'bkgt_performance_ratings';
        $sql = "CREATE TABLE IF NOT EXISTS $ratings_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            player_id mediumint(9) NOT NULL,
            team_id mediumint(9) NOT NULL,
            rater_id bigint(20) unsigned NOT NULL,
            enthusiasm_rating tinyint(1) NOT NULL,
            performance_rating tinyint(1) NOT NULL,
            skill_rating tinyint(1) NOT NULL,
            overall_rating decimal(3,2) GENERATED ALWAYS AS ((enthusiasm_rating + performance_rating + skill_rating) / 3) STORED,
            comments text,
            rating_date datetime DEFAULT CURRENT_TIMESTAMP,
            season varchar(20) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY player_id (player_id),
            KEY team_id (team_id),
            KEY rater_id (rater_id),
            KEY rating_date (rating_date),
            KEY season (season)
        ) $charset_collate;";
        $wpdb->query($sql);

        // Player statistics table
        $stats_table = $wpdb->prefix . 'bkgt_player_statistics';
        $sql = "CREATE TABLE IF NOT EXISTS $stats_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            player_id mediumint(9) NOT NULL,
            game_date date NOT NULL,
            opponent varchar(100) DEFAULT NULL,
            points_scored int(11) DEFAULT 0,
            touchdowns int(11) DEFAULT 0,
            interceptions int(11) DEFAULT 0,
            tackles int(11) DEFAULT 0,
            sacks decimal(4,1) DEFAULT 0.0,
            yards_rushing int(11) DEFAULT 0,
            yards_passing int(11) DEFAULT 0,
            yards_receiving int(11) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY player_id (player_id),
            KEY game_date (game_date),
            KEY opponent (opponent)
        ) $charset_collate;";
        $wpdb->query($sql);

        // Insert default teams if they don't exist
        self::insert_default_teams();
    }

    private static function upgrade_teams_table() {
        global $wpdb;
        $teams_table = $wpdb->prefix . 'bkgt_teams';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$teams_table'") === $teams_table;

        if ($table_exists) {
            // Add missing columns to existing teams table
            $columns_to_add = array(
                'slug' => "ALTER TABLE $teams_table ADD COLUMN slug varchar(100) DEFAULT NULL AFTER name",
                'svenskalag_id' => "ALTER TABLE $teams_table ADD COLUMN svenskalag_id varchar(50) DEFAULT NULL AFTER slug",
                'description' => "ALTER TABLE $teams_table ADD COLUMN description text AFTER svenskalag_id",
                'team_type' => "ALTER TABLE $teams_table ADD COLUMN team_type varchar(50) DEFAULT 'regular' AFTER description",
                'status' => "ALTER TABLE $teams_table ADD COLUMN status varchar(20) DEFAULT 'active' AFTER team_type",
                'created_date' => "ALTER TABLE $teams_table ADD COLUMN created_date datetime DEFAULT CURRENT_TIMESTAMP AFTER status",
                'updated_date' => "ALTER TABLE $teams_table ADD COLUMN updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_date"
            );

            foreach ($columns_to_add as $column => $sql) {
                // Check if column exists
                $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $teams_table LIKE '$column'");
                if (empty($column_exists)) {
                    $wpdb->query($sql);
                }
            }

            // Add indexes for new columns
            $indexes_to_add = array(
                'slug' => "ALTER TABLE $teams_table ADD UNIQUE KEY slug (slug)",
                'svenskalag_id' => "ALTER TABLE $teams_table ADD KEY svenskalag_id (svenskalag_id)",
                'team_type' => "ALTER TABLE $teams_table ADD KEY team_type (team_type)",
                'status' => "ALTER TABLE $teams_table ADD KEY status (status)"
            );

            foreach ($indexes_to_add as $index_name => $sql) {
                // Check if index exists
                $index_exists = $wpdb->get_results("SHOW INDEX FROM $teams_table WHERE Key_name = '$index_name'");
                if (empty($index_exists)) {
                    $wpdb->query($sql);
                }
            }
        }
    }

    private static function insert_default_teams() {
        global $wpdb;
        $teams_table = $wpdb->prefix . 'bkgt_teams';

        $default_teams = array(
            array('name' => 'Herrlag', 'slug' => 'herrlag', 'team_type' => 'men', 'description' => 'Herrarnas representationslag'),
            array('name' => 'Damlag', 'slug' => 'damlag', 'team_type' => 'women', 'description' => 'Damernas representationslag'),
            array('name' => 'U19', 'slug' => 'u19', 'team_type' => 'youth', 'description' => 'U19 ungdomslag'),
            array('name' => 'U17', 'slug' => 'u17', 'team_type' => 'youth', 'description' => 'U17 ungdomslag'),
            array('name' => 'U15', 'slug' => 'u15', 'team_type' => 'youth', 'description' => 'U15 ungdomslag'),
            array('name' => 'U13', 'slug' => 'u13', 'team_type' => 'youth', 'description' => 'U13 ungdomslag')
        );

        foreach ($default_teams as $team) {
            // Check if team already exists by slug
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $teams_table WHERE slug = %s",
                $team['slug']
            ));

            if (!$exists) {
                $wpdb->insert($teams_table, $team);
            }
        }
    }
}

// Hook into plugin activation
register_activation_hook(BKGT_TP_PLUGIN_DIR . '/bkgt-team-player.php', array('BKGT_Team_Player_Database', 'create_tables'));