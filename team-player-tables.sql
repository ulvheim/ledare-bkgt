-- BKGT Team & Player Management System - Database Tables
-- Run this script to create the necessary database tables for Team and Player Pages

USE bkgt_se_db_1;

-- Teams table (linking to svenskalag.se teams)
CREATE TABLE IF NOT EXISTS wp_bkgt_teams (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    name varchar(100) NOT NULL,
    slug varchar(100) NOT NULL,
    svenskalag_id varchar(50) DEFAULT NULL,
    description text,
    team_type varchar(50) DEFAULT 'regular', -- 'regular', 'youth', 'women', 'men'
    status varchar(20) DEFAULT 'active', -- 'active', 'inactive'
    created_date datetime DEFAULT CURRENT_TIMESTAMP,
    updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY slug (slug),
    KEY svenskalag_id (svenskalag_id),
    KEY team_type (team_type),
    KEY status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Players table (linking to svenskalag.se players)
CREATE TABLE IF NOT EXISTS wp_bkgt_players (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) unsigned DEFAULT NULL, -- Link to WordPress user if they have account
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
    status varchar(20) DEFAULT 'active', -- 'active', 'inactive', 'transferred'
    created_date datetime DEFAULT CURRENT_TIMESTAMP,
    updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY svenskalag_id (svenskalag_id),
    KEY team_id (team_id),
    KEY status (status),
    KEY position (position),
    FULLTEXT KEY player_search (first_name, last_name, display_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Player dossiers/notes table
CREATE TABLE IF NOT EXISTS wp_bkgt_player_notes (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    player_id mediumint(9) NOT NULL,
    author_id bigint(20) unsigned NOT NULL,
    note_type varchar(50) DEFAULT 'general', -- 'general', 'performance', 'medical', 'disciplinary'
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance ratings table (confidential)
CREATE TABLE IF NOT EXISTS wp_bkgt_performance_ratings (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    player_id mediumint(9) NOT NULL,
    team_id mediumint(9) NOT NULL,
    rater_id bigint(20) unsigned NOT NULL,
    enthusiasm_rating tinyint(1) NOT NULL, -- 1-5 scale
    performance_rating tinyint(1) NOT NULL, -- 1-5 scale
    skill_rating tinyint(1) NOT NULL, -- 1-5 scale
    overall_rating decimal(3,2) GENERATED ALWAYS AS ((enthusiasm_rating + performance_rating + skill_rating) / 3) STORED,
    comments text,
    rating_date datetime DEFAULT CURRENT_TIMESTAMP,
    season varchar(20) DEFAULT NULL, -- e.g., '2025', '2024-2025'
    PRIMARY KEY (id),
    KEY player_id (player_id),
    KEY team_id (team_id),
    KEY rater_id (rater_id),
    KEY rating_date (rating_date),
    KEY season (season)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Player statistics/scoring table
CREATE TABLE IF NOT EXISTS wp_bkgt_player_statistics (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default teams
INSERT IGNORE INTO wp_bkgt_teams (name, slug, team_type, description) VALUES
('Herrlag', 'herrlag', 'men', 'Herrarnas representationslag'),
('Damlag', 'damlag', 'women', 'Damernas representationslag'),
('U19', 'u19', 'youth', 'U19 ungdomslag'),
('U17', 'u17', 'youth', 'U17 ungdomslag'),
('U15', 'u15', 'youth', 'U15 ungdomslag'),
('U13', 'u13', 'youth', 'U13 ungdomslag');