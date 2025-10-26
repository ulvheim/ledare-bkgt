# BKGT Data Scraping - Frontend Shortcodes

This plugin provides several shortcodes to display BKGT football club data on your website.

## Available Shortcodes

### 1. Players Display
Display a list or grid of players with optional filtering and statistics.

```
[bkgt_players layout="grid" status="active" show_stats="true" show_filters="true"]
```

**Parameters:**
- `layout`: "grid" or "list" (default: "grid")
- `status`: "active", "inactive", or "all" (default: "active")
- `position`: Filter by position (optional)
- `limit`: Number of players to show (default: -1 for all)
- `show_stats`: "true" or "false" (default: "false")
- `show_filters`: "true" or "false" (default: "false")

### 2. Events Display
Display upcoming or past events/matches.

```
[bkgt_events type="all" upcoming="true" limit="10" show_players="true" show_filters="true"]
```

**Parameters:**
- `type`: "all", "match", "training", or "meeting" (default: "all")
- `upcoming`: "true" or "false" (default: "true")
- `limit`: Number of events to show (default: 10)
- `show_players`: "true" or "false" - show assigned players (default: "false")
- `show_filters`: "true" or "false" (default: "false")

### 3. Team Overview
Display team statistics and upcoming events.

```
[bkgt_team_overview show_stats="true" show_upcoming="true" upcoming_limit="3"]
```

**Parameters:**
- `show_stats`: "true" or "false" (default: "true")
- `show_upcoming`: "true" or "false" (default: "true")
- `upcoming_limit`: Number of upcoming events to show (default: 3)

### 4. Player Profile
Display detailed information about a specific player.

```
[bkgt_player_profile player_id="1" show_stats="true" show_events="true"]
```

**Parameters:**
- `player_id`: The ID of the player to display (required)
- `show_stats`: "true" or "false" (default: "true")
- `show_events`: "true" or "false" (default: "true")

## Sample Page Content

### Team Page
```
<h1>Vårt Lag</h1>

[bkgt_team_overview]

<h2>Spelare</h2>
[bkgt_players layout="grid" show_stats="true" show_filters="true"]

<h2>Kommande Matcher</h2>
[bkgt_events type="match" upcoming="true" limit="5"]
```

### Player Profile Page
```
<h1>Spelarprofil</h1>

[bkgt_player_profile player_id="1"]
```

### Events Calendar Page
```
<h1>Matcher & Träningar</h1>

[bkgt_events show_filters="true" show_players="true"]
```

## Styling

The plugin includes responsive CSS that works well on all devices. The design follows WordPress conventions and can be customized by overriding the CSS classes:

- `.bkgt-players-grid` - Player grid container
- `.bkgt-player-card` - Individual player card
- `.bkgt-events-list` - Events list container
- `.bkgt-event-item` - Individual event item
- `.bkgt-team-overview` - Team overview container
- `.bkgt-player-profile` - Player profile container

## Requirements

- WordPress 5.0+
- The BKGT Data Scraping plugin must be active
- Data must be imported via the admin interface

## Support

For support or feature requests, please contact the BKGT development team.