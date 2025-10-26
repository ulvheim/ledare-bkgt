<?php
/**
 * AJAX handlers for BKGT Data Scraping plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Additional AJAX handlers can be added here if needed
// Most AJAX handlers are in the admin class, but this file can contain
// frontend or additional admin AJAX handlers

/**
 * AJAX: Get players for frontend display
 */
function bkgt_get_players_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'bkgt_frontend_nonce')) {
        wp_die(__('Security check failed', 'bkgt-data-scraping'));
    }

    $db = bkgt_data_scraping()->db;
    $players = $db->get_players('active');

    wp_send_json_success(array(
        'players' => $players
    ));
}
add_action('wp_ajax_bkgt_get_players', 'bkgt_get_players_ajax');
add_action('wp_ajax_nopriv_bkgt_get_players', 'bkgt_get_players_ajax');

/**
 * AJAX: Get events for frontend display
 */
function bkgt_get_events_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'bkgt_frontend_nonce')) {
        wp_die(__('Security check failed', 'bkgt-data-scraping'));
    }

    $db = bkgt_data_scraping()->db;
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : null;
    $events = $db->get_events('all', $limit);

    wp_send_json_success(array(
        'events' => $events
    ));
}
add_action('wp_ajax_bkgt_get_events', 'bkgt_get_events_ajax');
add_action('wp_ajax_nopriv_bkgt_get_events', 'bkgt_get_events_ajax');

/**
 * AJAX: Get player statistics for frontend
 */
function bkgt_get_player_statistics_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'bkgt_frontend_nonce')) {
        wp_die(__('Security check failed', 'bkgt-data-scraping'));
    }

    $player_id = (int)$_POST['player_id'];
    $db = bkgt_data_scraping()->db;
    $stats = $db->get_player_statistics($player_id);

    wp_send_json_success(array(
        'statistics' => $stats
    ));
}
add_action('wp_ajax_bkgt_get_player_statistics', 'bkgt_get_player_statistics_ajax');
add_action('wp_ajax_nopriv_bkgt_get_player_statistics', 'bkgt_get_player_statistics_ajax');