# BKGT Team & Player Management Plugin

This WordPress plugin provides comprehensive team and player management functionality for BKGTS American Football club.

## Features

### Team Pages
- Display team rosters with player information
- Show team statistics and performance data
- Team-specific pages with customizable layouts

### Player Dossiers
- Individual player profiles with detailed information
- Performance ratings and evaluation history (confidential)
- Player statistics and game performance data
- Notes and comments system for coaches and staff

### Performance Management
- Confidential performance rating system (coaches and board members only)
- Multi-criteria evaluation (enthusiasm, performance, skill)
- Historical performance tracking
- Team-based access control

### Role-Based Access Control
- **Board Members (Styrelsemedlem)**: Full access to all features
- **Coaches (Tränare)**: Access to team-specific data and performance ratings
- **Team Leaders (Lagledare)**: Limited access to team rosters and basic information
- **Public Users**: View-only access to public team and player information

## Installation

1. Upload the `bkgt-team-player` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin dashboard
3. The plugin will automatically create necessary database tables on activation

## Database Tables

The plugin creates and manages the following database tables:

- `wp_bkgt_teams` - Team information (extends existing data scraping table)
- `wp_bkgt_players` - Player profiles and information
- `wp_bkgt_player_notes` - Confidential notes and dossiers
- `wp_bkgt_performance_ratings` - Performance evaluation data
- `wp_bkgt_player_statistics` - Game statistics and performance data

## Shortcodes

### [bkgt_team_page]
Display team information and rosters.

**Parameters:**
- `team` - Team slug (e.g., 'herrlag', 'u19')
- `show_roster` - Show team roster (true/false, default: true)
- `show_stats` - Show team statistics (true/false, default: true)

**Examples:**
```
[bkgt_team_page]  // Shows all teams
[bkgt_team_page team="herrlag"]  // Shows specific team
[bkgt_team_page team="u19" show_stats="false"]  // Team without stats
```

### [bkgt_player_dossier]
Display individual player profiles.

**Parameters:**
- `player` - Player ID (required)
- `show_stats` - Show player statistics (true/false, default: true)
- `show_notes` - Show player notes (true/false, default: false, requires permissions)

**Examples:**
```
[bkgt_player_dossier player="123"]
[bkgt_player_dossier player="456" show_notes="true"]
```

### [bkgt_performance_page]
Performance management interface (coaches and board members only).

**Parameters:**
- `team` - Team slug for filtering
- `action` - Action to perform ('view', 'add', 'edit')

**Examples:**
```
[bkgt_performance_page]  // Performance dashboard
[bkgt_performance_page team="herrlag"]  // Team-specific ratings
[bkgt_performance_page action="add"]  // Add new rating form
```

## Admin Interface

Access the admin interface at **Dashboard > Teams & Players**.

### Features:
- Team management overview
- Player management overview
- Performance ratings administration
- Confidential data access controls

## Security & Privacy

- Performance ratings and confidential notes are only visible to authorized users
- Role-based access control ensures data privacy
- All AJAX requests include nonce verification
- SQL injection protection through prepared statements

## Swedish Language Support

The plugin includes Swedish translations for all user-facing text. Language files are located in the `languages/` directory.

## Integration

This plugin integrates with:
- **BKGT Data Scraping Plugin**: Uses existing team data from svenskalag.se
- **BKGT Document Management**: Links to player documents and contracts
- **BKGT Inventory**: Connects to equipment assignments
- **WordPress User System**: Links players to WordPress user accounts

## Development

### File Structure
```
bkgt-team-player/
├── bkgt-team-player.php          # Main plugin file
├── includes/
│   └── class-database.php        # Database setup and management
├── assets/
│   ├── css/
│   │   └── frontend.css          # Frontend styling
│   └── js/
│       └── frontend.js           # Frontend JavaScript
├── languages/                    # Translation files
└── README.md                     # This file
```

### Hooks & Filters

The plugin provides several WordPress hooks for customization:

- `bkgt_team_page_content` - Filter team page content
- `bkgt_player_dossier_content` - Filter player dossier content
- `bkgt_performance_rating_saved` - Action after saving performance rating
- `bkgt_player_note_saved` - Action after saving player note

## Changelog

### Version 1.0.0
- Initial release
- Team pages with rosters
- Player dossiers with performance data
- Confidential performance rating system
- Role-based access control
- Database table creation and management

## Support

For support or feature requests, please contact the BKGT development team.