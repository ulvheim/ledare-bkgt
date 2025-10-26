# BKGT Data Scraping & Management Plugin

A comprehensive WordPress plugin for automated data retrieval and manual entry system for BKGT football club management. Scrapes player data, events, and statistics from svenskalag.se with fallback manual entry capabilities.

## Features

- **Automated Data Scraping**: Automatically scrape player rosters, match schedules, and statistics from svenskalag.se
- **Manual Data Entry**: Full manual entry capabilities for all data types when scraping isn't available
- **Player Management**: Complete player profiles with positions, jersey numbers, and status tracking
- **Event Management**: Track matches, training sessions, and other club events
- **Statistics Tracking**: Record goals, assists, cards, and playing time for each player per event
- **Admin Dashboard**: Comprehensive dashboard with data overview and quick actions
- **Frontend Display**: Shortcodes for displaying data on public pages
- **AJAX-Powered Interface**: Modern, responsive admin interface with modal dialogs
- **Custom Database Tables**: Optimized database structure with proper indexing and relationships
- **Responsive Design**: Mobile-friendly admin and frontend interfaces

## Installation

1. Download the plugin files
2. Upload the `bkgt-data-scraping` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the WordPress admin dashboard
4. Configure settings under **BKGT Data > Settings**

## Configuration

### Basic Settings

- **Enable Automatic Scraping**: Turn on/off daily automatic data scraping
- **Scraping Interval**: Set how often to run automatic scraping (daily, twice daily, hourly)
- **Data Source URL**: Configure the base URL for svenskalag.se (default: https://www.svenskalag.se/bkgt)

### Database Setup

The plugin automatically creates the following custom tables on activation:

- `wp_bkgt_players`: Player information and profiles
- `wp_bkgt_events`: Matches, training sessions, and events
- `wp_bkgt_statistics`: Player performance statistics per event
- `wp_bkgt_sources`: Tracking of data scraping sources and status

## Usage

### Dashboard

The main dashboard provides:
- Data overview with record counts
- Quick action buttons for scraping
- Recent activity feed
- Data source status monitoring

### Managing Players

1. Go to **BKGT Data > Players**
2. Click "Add New Player" to create manually
3. Click "Scrape Players from Source" to import from svenskalag.se
4. Edit existing players by clicking the "Edit" button
5. Delete players with the "Delete" button

### Managing Events

1. Go to **BKGT Data > Events**
2. Click "Add New Event" to create manually
3. Click "Scrape Events from Source" to import from svenskalag.se
4. Edit existing events by clicking the "Edit" button
5. Delete events with the "Delete" button

### Managing Statistics

1. Go to **BKGT Data > Statistics**
2. Select a player and event from the dropdowns
3. Click "Load Player Statistics" to view existing stats
4. Click "Add Statistics" to record new performance data
5. Edit existing statistics from the player stats table

### Frontend Display

The plugin provides shortcodes to display BKGT data on public pages:

#### Players Display
```
[bkgt_players layout="grid" status="active" show_stats="true" show_filters="true"]
```

#### Events Display
```
[bkgt_events type="all" upcoming="true" limit="10" show_players="true"]
```

#### Team Overview
```
[bkgt_team_overview show_stats="true" show_upcoming="true" upcoming_limit="3"]
```

#### Player Profile
```
[bkgt_player_profile player_id="1" show_stats="true" show_events="true"]
```

See `FRONTEND_SHORTCODES.md` for complete documentation and sample page content.

## Data Scraping

### How It Works

The scraper uses DOM parsing to extract data from svenskalag.se. It looks for:
- Player listings with names, positions, and jersey numbers
- Event schedules with dates, opponents, and locations
- Match results and statistics

### Customization

The scraping logic is contained in `includes/class-bkgt-scraper.php`. To customize for different website structures:

1. Modify the `parse_players_html()` method for player data extraction
2. Modify the `parse_events_html()` method for event data extraction
3. Update CSS selectors in `extract_player_data()` and `extract_event_data()` methods

### Manual Scraping

You can trigger manual scraping at any time:
- From the main dashboard quick actions
- From individual management pages
- From the settings page

## API Reference

### PHP Classes

- `BKGT_Data_Scraping`: Main plugin class
- `BKGT_Database`: Database operations and table management
- `BKGT_Scraper`: Web scraping functionality
- `BKGT_Admin`: Admin interface and AJAX handlers

### AJAX Endpoints

- `bkgt_manual_scrape`: Trigger manual data scraping
- `bkgt_save_player`: Save player data
- `bkgt_delete_player`: Delete player
- `bkgt_save_event`: Save event data
- `bkgt_delete_event`: Delete event
- `bkgt_get_player_stats`: Retrieve player statistics
- `bkgt_save_statistics`: Save statistics data

### Database Tables

#### Players Table
```sql
CREATE TABLE wp_bkgt_players (
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
    UNIQUE KEY player_id (player_id)
);
```

#### Events Table
```sql
CREATE TABLE wp_bkgt_events (
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
    UNIQUE KEY event_id (event_id)
);
```

#### Statistics Table
```sql
CREATE TABLE wp_bkgt_statistics (
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
    FOREIGN KEY (player_id) REFERENCES wp_bkgt_players(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES wp_bkgt_events(id) ON DELETE CASCADE
);
```

## Troubleshooting

### Scraping Issues

- **No data scraped**: Check that the source URL is correct and accessible
- **Wrong data format**: The website structure may have changed; update the parsing logic
- **Timeout errors**: Increase PHP timeout limits or reduce scraping frequency

### Database Issues

- **Tables not created**: Deactivate and reactivate the plugin
- **Data not saving**: Check database permissions and available space

### Performance Issues

- **Slow loading**: Ensure proper database indexing
- **Memory issues**: Process data in smaller batches during scraping

## Development

### File Structure
```
bkgt-data-scraping/
├── bkgt-data-scraping.php          # Main plugin file
├── includes/
│   ├── class-bkgt-database.php     # Database operations
│   ├── class-bkgt-scraper.php      # Scraping functionality
│   └── ajax-handlers.php           # Additional AJAX endpoints
├── admin/
│   ├── class-bkgt-admin.php        # Admin interface
│   ├── js/
│   │   └── admin.js                # Admin JavaScript
│   └── css/
│       └── admin.css               # Admin styles
└── templates/                      # Admin page templates
    ├── admin-dashboard.php
    ├── admin-players.php
    ├── admin-events.php
    ├── admin-statistics.php
    └── admin-settings.php
```

### Hooks and Filters

The plugin provides several WordPress hooks for customization:

- `bkgt_daily_scraping`: Cron hook for daily scraping
- AJAX actions for all CRUD operations
- Admin menu and submenu filters

## Changelog

### Version 1.0.0
- Initial release
- Basic scraping functionality for svenskalag.se
- Complete admin interface for data management
- Custom database tables with proper relationships
- AJAX-powered CRUD operations
- Responsive admin design

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support and feature requests, please contact the BKGT development team.