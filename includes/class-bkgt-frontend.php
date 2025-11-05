<?php
/**
 * Frontend class for BKGT Data Scraping plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * BKGT Frontend Class
 */
class BKGT_Frontend {

    /**
     * Database instance
     */
    private $db;

    /**
     * Constructor
     */
    public function __construct($db) {
        $this->db = $db;
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('init', array($this, 'register_shortcodes'));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Always enqueue on singular pages (posts, pages) to be safe
        if (is_singular()) {
            wp_enqueue_script(
                'bkgt-frontend-js',
                BKGT_DATA_SCRAPING_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                BKGT_DATA_SCRAPING_VERSION,
                true
            );

            wp_enqueue_style(
                'bkgt-frontend-css',
                BKGT_DATA_SCRAPING_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                BKGT_DATA_SCRAPING_VERSION
            );

            wp_localize_script('bkgt-frontend-js', 'bkgt_frontend', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bkgt_frontend_nonce'),
                'strings' => array(
                    'loading' => __('Laddar...', 'bkgt-data-scraping'),
                    'error' => __('Ett fel uppstod', 'bkgt-data-scraping'),
                    'no_data' => __('Ingen data tillgänglig', 'bkgt-data-scraping')
                )
            ));
        }
    }

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('bkgt_players', array($this, 'shortcode_players'));
        add_shortcode('bkgt_events', array($this, 'shortcode_events'));
        add_shortcode('bkgt_team_overview', array($this, 'shortcode_team_overview'));
        add_shortcode('bkgt_player_profile', array($this, 'shortcode_player_profile'));

        // Debug: Log that shortcodes are registered
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BKGT Frontend: Shortcodes registered');
        }
    }

    /**
     * Check if current page has BKGT shortcodes
     * Note: This method is kept for potential future use but not currently used
     * due to timing issues with wp_enqueue_scripts hook
     */
    private function has_bkgt_shortcodes() {
        global $post;
        if (!is_a($post, 'WP_Post')) {
            return false;
        }

        $shortcodes = array('bkgt_players', 'bkgt_events', 'bkgt_team_overview', 'bkgt_player_profile');
        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Players shortcode
     */
    public function shortcode_players($atts) {
        $atts = shortcode_atts(array(
            'limit' => -1,
            'status' => 'active',
            'position' => '',
            'layout' => 'grid',
            'show_stats' => 'false',
            'show_filters' => 'false'
        ), $atts);

        // Check if database is available
        if (!$this->db) {
            return '<div class="bkgt-error">' . __('Databasanslutning misslyckades', 'bkgt-data-scraping') . '</div>';
        }

        $players = $this->db->get_players($atts['status'], $atts['limit'], $atts['position']);

        // Check if players data is retrieved
        if (!is_array($players)) {
            return '<div class="bkgt-error">' . __('Kunde inte hämta spelardata', 'bkgt-data-scraping') . '</div>';
        }

        // If no players found, show message
        if (empty($players)) {
            return '<div class="bkgt-no-data">' . __('Inga spelare hittades', 'bkgt-data-scraping') . '</div>';
        }

        // Prepare player stats if needed
        $players_with_stats = array();
        if ($atts['show_stats'] === 'true') {
            foreach ($players as $player) {
                $player['stats'] = $this->db->get_player_stats($player['id']);
                $players_with_stats[] = $player;
            }
            $players = $players_with_stats;
        }

        ob_start();
        include BKGT_DATA_SCRAPING_PLUGIN_DIR . 'templates/frontend/players.php';
        return ob_get_clean();
    }

    /**
     * Events shortcode
     */
    public function shortcode_events($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'type' => 'all',
            'upcoming' => 'true',
            'layout' => 'list',
            'show_players' => 'false'
        ), $atts);

        // Check if database is available
        if (!$this->db) {
            return '<div class="bkgt-error">' . __('Databasanslutning misslyckades', 'bkgt-data-scraping') . '</div>';
        }

        $events = $this->db->get_events($atts['type'], $atts['limit'], $atts['upcoming'] === 'true');

        // Check if events data is retrieved
        if (!is_array($events)) {
            return '<div class="bkgt-error">' . __('Kunde inte hämta eventdata', 'bkgt-data-scraping') . '</div>';
        }

        // If no events found, show message
        if (empty($events)) {
            return '<div class="bkgt-no-data">' . __('Inga event hittades', 'bkgt-data-scraping') . '</div>';
        }

        ob_start();
        include BKGT_DATA_SCRAPING_PLUGIN_DIR . 'templates/frontend/events.php';
        return ob_get_clean();
    }

    /**
     * Team overview shortcode
     */
    public function shortcode_team_overview($atts) {
        $atts = shortcode_atts(array(
            'show_stats' => 'true',
            'show_upcoming' => 'true',
            'upcoming_limit' => 3
        ), $atts);

        // Check if database is available
        if (!$this->db) {
            return '<div class="bkgt-error">' . __('Databasanslutning misslyckades', 'bkgt-data-scraping') . '</div>';
        }

        $stats = array(
            'total_players' => count($this->db->get_players('active')),
            'total_events' => count($this->db->get_events('all')),
            'upcoming_events' => $this->db->get_events('all', $atts['upcoming_limit'], true)
        );

        // Calculate additional stats if needed
        if ($atts['show_stats'] === 'true') {
            $all_players = $this->db->get_players('active');
            $total_goals = 0;
            $total_games = 0;

            foreach ($all_players as $player) {
                $player_stats = $this->db->get_player_stats($player['id']);
                $total_goals += array_sum(array_column($player_stats, 'goals'));
                $total_games += count($player_stats);
            }

            $stats['total_goals'] = $total_goals;
            $stats['total_games'] = $total_games;
        }

        ob_start();
        include BKGT_DATA_SCRAPING_PLUGIN_DIR . 'templates/frontend/team-overview.php';
        return ob_get_clean();
    }

    /**
     * Player profile shortcode
     */
    public function shortcode_player_profile($atts) {
        $atts = shortcode_atts(array(
            'player_id' => '',
            'show_stats' => 'true',
            'show_events' => 'true'
        ), $atts);

        if (empty($atts['player_id'])) {
            return '<p>' . __('Ingen spelare vald', 'bkgt-data-scraping') . '</p>';
        }

        // Check if database is available
        if (!$this->db) {
            return '<div class="bkgt-error">' . __('Databasanslutning misslyckades', 'bkgt-data-scraping') . '</div>';
        }

        $player = $this->db->get_player($atts['player_id']);
        if (!$player) {
            return '<p>' . __('Spelare hittades inte', 'bkgt-data-scraping') . '</p>';
        }

        $stats = $atts['show_stats'] === 'true' ? $this->db->get_player_stats($atts['player_id']) : array();
        $events = $atts['show_events'] === 'true' ? $this->db->get_player_events($atts['player_id']) : array();

        ob_start();
        include BKGT_DATA_SCRAPING_PLUGIN_DIR . 'templates/frontend/player-profile.php';
        return ob_get_clean();
    }
}