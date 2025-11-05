<?php
/**
 * BKGT Data Scraping Shortcodes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display players list shortcode
 */
function bkgt_players_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => -1,
        'team' => '',
        'orderby' => 'name',
        'order' => 'ASC'
    ), $atts);

    $db = new BKGT_Database();
    $players = $db->get_players($atts);

    if (empty($players)) {
        return '<p>Inga spelare hittades.</p>';
    }

    $output = '<div class="bkgt-players">';
    $output .= '<h3>Våra Spelare</h3>';
    $output .= '<div class="players-grid">';

    foreach ($players as $player) {
        $output .= '<div class="player-card">';
        $output .= '<h4>' . esc_html($player->name) . '</h4>';
        $output .= '<p><strong>Position:</strong> ' . esc_html($player->position) . '</p>';
        $output .= '<p><strong>Ålder:</strong> ' . esc_html($player->age) . '</p>';
        if (!empty($player->email)) {
            $output .= '<p><strong>Email:</strong> <a href="mailto:' . esc_attr($player->email) . '">' . esc_html($player->email) . '</a></p>';
        }
        $output .= '</div>';
    }

    $output .= '</div>';
    $output .= '</div>';

    return $output;
}
add_shortcode('bkgt_players', 'bkgt_players_shortcode');

/**
 * Display events list shortcode
 */
function bkgt_events_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 10,
        'type' => '',
        'future_only' => true
    ), $atts);

    $db = new BKGT_Database();
    $events = $db->get_events($atts);

    if (empty($events)) {
        return '<p>Inga evenemang hittades.</p>';
    }

    $output = '<div class="bkgt-events">';
    $output .= '<h3>Kommande Evenemang</h3>';
    $output .= '<ul class="events-list">';

    foreach ($events as $event) {
        $date = date('Y-m-d H:i', strtotime($event->event_date));
        $output .= '<li>';
        $output .= '<strong>' . esc_html($event->title) . '</strong><br>';
        $output .= '<span>' . esc_html($date) . '</span><br>';
        $output .= '<span>' . esc_html($event->location) . '</span>';
        $output .= '</li>';
    }

    $output .= '</ul>';
    $output .= '</div>';

    return $output;
}
add_shortcode('bkgt_events', 'bkgt_events_shortcode');

/**
 * Display team overview shortcode
 */
function bkgt_team_overview_shortcode($atts) {
    $atts = shortcode_atts(array(
        'show_stats' => true
    ), $atts);

    $db = new BKGT_Database();
    $stats = $db->get_team_stats();

    $output = '<div class="bkgt-team-overview">';
    $output .= '<h3>Lagöversikt</h3>';

    if ($atts['show_stats'] && !empty($stats)) {
        $output .= '<div class="team-stats">';
        $output .= '<p><strong>Totalt antal spelare:</strong> ' . esc_html($stats->total_players) . '</p>';
        $output .= '<p><strong>Aktiva spelare:</strong> ' . esc_html($stats->active_players) . '</p>';
        $output .= '<p><strong>Kommande matcher:</strong> ' . esc_html($stats->upcoming_matches) . '</p>';
        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('bkgt_team_overview', 'bkgt_team_overview_shortcode');