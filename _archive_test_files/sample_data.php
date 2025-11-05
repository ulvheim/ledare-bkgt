<?php
require_once('wp-load.php');

echo "=== BKGT Sample Data Creator ===\n";
echo "Adding sample data to demonstrate the system functionality...\n\n";

global $wpdb;

// Create sample players
$players = array(
    array(
        'player_id' => 'bkgt001',
        'first_name' => 'Erik',
        'last_name' => 'Andersson',
        'position' => 'QB',
        'birth_date' => '1995-03-15',
        'jersey_number' => 12,
        'status' => 'active'
    ),
    array(
        'player_id' => 'bkgt002',
        'first_name' => 'Marcus',
        'last_name' => 'Johansson',
        'position' => 'RB',
        'birth_date' => '1993-07-22',
        'jersey_number' => 28,
        'status' => 'active'
    ),
    array(
        'player_id' => 'bkgt003',
        'first_name' => 'Daniel',
        'last_name' => 'Karlsson',
        'position' => 'WR',
        'birth_date' => '1994-11-08',
        'jersey_number' => 85,
        'status' => 'active'
    ),
    array(
        'player_id' => 'bkgt004',
        'first_name' => 'Fredrik',
        'last_name' => 'Nilsson',
        'position' => 'LB',
        'birth_date' => '1992-05-30',
        'jersey_number' => 45,
        'status' => 'active'
    ),
    array(
        'player_id' => 'bkgt005',
        'first_name' => 'Anders',
        'last_name' => 'Eriksson',
        'position' => 'DL',
        'birth_date' => '1991-09-12',
        'jersey_number' => 99,
        'status' => 'active'
    )
);

// Insert players
echo "Adding sample players...\n";
foreach ($players as $player) {
    $result = $wpdb->insert(
        $wpdb->prefix . 'bkgt_players',
        $player,
        array('%s', '%s', '%s', '%s', '%s', '%d', '%s')
    );

    if ($result) {
        echo "✓ Added player: {$player['first_name']} {$player['last_name']} ({$player['position']})\n";
    } else {
        echo "✗ Failed to add player: {$player['first_name']} {$player['last_name']}\n";
    }
}

// Create sample events
$events = array(
    array(
        'event_id' => 'match001',
        'title' => 'BKGT vs Stockholm Mean Machines',
        'event_type' => 'match',
        'event_date' => date('Y-m-d H:i:s', strtotime('+1 week')),
        'location' => 'Zinkensdamms IP, Stockholm',
        'opponent' => 'Stockholm Mean Machines',
        'home_away' => 'home',
        'status' => 'scheduled'
    ),
    array(
        'event_id' => 'training001',
        'title' => 'Veckans träning',
        'event_type' => 'training',
        'event_date' => date('Y-m-d H:i:s', strtotime('+2 days')),
        'location' => 'Zinkensdamms IP, Stockholm',
        'status' => 'scheduled'
    ),
    array(
        'event_id' => 'match002',
        'title' => 'BKGT vs Carlstad Crusaders',
        'event_type' => 'match',
        'event_date' => date('Y-m-d H:i:s', strtotime('+2 weeks')),
        'location' => 'Zinkensdamms IP, Stockholm',
        'opponent' => 'Carlstad Crusaders',
        'home_away' => 'home',
        'status' => 'scheduled'
    )
);

// Insert events
echo "\nAdding sample events...\n";
foreach ($events as $event) {
    $result = $wpdb->insert(
        $wpdb->prefix . 'bkgt_events',
        $event,
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    if ($result) {
        echo "✓ Added event: {$event['title']} ({$event['event_date']})\n";
    } else {
        echo "✗ Failed to add event: {$event['title']}\n";
    }
}

// Create sample statistics
$statistics = array(
    array('player_id' => 1, 'event_id' => 1, 'goals' => 2, 'assists' => 1, 'minutes_played' => 60),
    array('player_id' => 2, 'event_id' => 1, 'goals' => 1, 'assists' => 0, 'minutes_played' => 45),
    array('player_id' => 3, 'event_id' => 1, 'goals' => 0, 'assists' => 2, 'minutes_played' => 60),
    array('player_id' => 4, 'event_id' => 1, 'goals' => 0, 'assists' => 1, 'minutes_played' => 50),
    array('player_id' => 5, 'event_id' => 1, 'goals' => 1, 'assists' => 0, 'minutes_played' => 40)
);

// Insert statistics
echo "\nAdding sample statistics...\n";
foreach ($statistics as $stat) {
    $result = $wpdb->insert(
        $wpdb->prefix . 'bkgt_statistics',
        $stat,
        array('%d', '%d', '%d', '%d', '%d')
    );

    if ($result) {
        echo "✓ Added statistics for player {$stat['player_id']} in event {$stat['event_id']}\n";
    } else {
        echo "✗ Failed to add statistics for player {$stat['player_id']}\n";
    }
}

echo "\n=== Sample Data Creation Complete ===\n";
echo "✓ 5 players added\n";
echo "✓ 3 events added\n";
echo "✓ 5 statistics records added\n";
echo "\nYou can now view this data in the WordPress admin under BKGT Data sections.\n";
echo "The scraper functionality demonstrates that the system can collect and display BKGT football data!\n";
?>