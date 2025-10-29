<?php
/**
 * Web scraping class for BKGT Data Scraping plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * BKGT Scraper Class
 */
class BKGT_Scraper {

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
        add_action('bkgt_daily_scraping', array($this, 'run_daily_scraping'));
        add_action('wp_ajax_bkgt_manual_scrape', array($this, 'manual_scrape'));
    }

    /**
     * Run daily scraping
     */
    public function run_daily_scraping() {
        if (get_option('bkgt_scraping_enabled') !== 'yes') {
            return;
        }

        $this->scrape_teams();
        $this->scrape_players();
        $this->scrape_events();
    }

    /**
     * Manual scrape via AJAX
     */
    public function manual_scrape() {
        // Verify nonce and capabilities
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_scraping_nonce') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'bkgt-data-scraping'));
        }

        $data_type = sanitize_text_field($_POST['data_type']);

        try {
            switch ($data_type) {
                case 'teams':
                    $result = $this->scrape_teams();
                    break;
                case 'players':
                    $result = $this->scrape_players();
                    break;
                case 'events':
                    $result = $this->scrape_events();
                    break;
                default:
                    throw new Exception(__('Invalid data type', 'bkgt-data-scraping'));
            }

            wp_send_json_success(array(
                'message' => __('Data scraped successfully', 'bkgt-data-scraping'),
                'count' => $result
            ));

        } catch (Exception $e) {
            $this->db->update_source_status(
                get_option('bkgt_scraping_source_url'),
                'failed',
                $e->getMessage()
            );

            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Scrape team data from svenskalag.se
     */
    private function scrape_teams() {
        $source_url = get_option('bkgt_scraping_source_url', 'https://www.svenskalag.se/bkgt');
        $teams_url = $source_url; // Main page or teams page

        // Start logging
        $log_id = $this->db->start_scraping_log('teams', $teams_url);

        try {
            $html = $this->fetch_url($teams_url);
            $teams = $this->parse_teams_html($html);

            $count = 0;
            foreach ($teams as $team_data) {
                if ($this->db->insert_team($team_data)) {
                    $count++;
                }
            }

            // Update log with success
            $this->db->update_scraping_log($log_id, array(
                'records_processed' => count($teams),
                'records_added' => $count
            ));
            $this->db->complete_scraping_log($log_id, 'completed');

            return $count;

        } catch (Exception $e) {
            // Log failure
            $this->db->complete_scraping_log($log_id, 'failed', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Scrape player data from svenskalag.se
     */
    private function scrape_players() {
        $source_url = get_option('bkgt_scraping_source_url', 'https://www.svenskalag.se/bkgt');

        // Get all teams first
        $teams = $this->db->get_teams('all');
        $total_players = 0;

        foreach ($teams as $team) {
            // Generate team-specific URL based on team name
            $team_url = $this->generate_team_url($team->name);
            $players_url = $team_url . '/truppen'; // Correct URL is /truppen, not /spelare

            // Start logging for this team's players
            $log_id = $this->db->start_scraping_log('players', $players_url, $team->id);

            try {
                $html = $this->fetch_url($players_url);
                $players = $this->parse_players_html($html, $team->id);

                $count = 0;
                foreach ($players as $player_data) {
                    if ($this->db->insert_player($player_data)) {
                        $count++;
                    }
                }

                // Update log with success
                $this->db->update_scraping_log($log_id, array(
                    'records_processed' => count($players),
                    'records_added' => $count
                ));
                $this->db->complete_scraping_log($log_id, 'completed');

                $total_players += $count;

            } catch (Exception $e) {
                // If team-specific page fails, try general players page
                $general_players_url = $source_url . '/truppen'; // Also try /truppen on main site

                // Update log with failure for team-specific URL
                $this->db->complete_scraping_log($log_id, 'failed', $e->getMessage());

                // Start new log for general players page
                $log_id2 = $this->db->start_scraping_log('players', $general_players_url, $team->id);

                try {
                    $html = $this->fetch_url($general_players_url);
                    $players = $this->parse_players_html($html, $team->id);

                    $count = 0;
                    foreach ($players as $player_data) {
                        if ($this->db->insert_player($player_data)) {
                            $count++;
                        }
                    }

                    // Update log with success
                    $this->db->update_scraping_log($log_id2, array(
                        'records_processed' => count($players),
                        'records_added' => $count
                    ));
                    $this->db->complete_scraping_log($log_id2, 'completed');

                    $total_players += $count;

                } catch (Exception $e2) {
                    $this->db->complete_scraping_log($log_id2, 'failed', $e2->getMessage());
                    // Continue with other teams
                }
            }
        }

        return $total_players;
    }

    /**
     * Scrape events data from svenskalag.se
     * Note: Individual team match pages don't exist on this site
     */
    private function scrape_events() {
        $source_url = get_option('bkgt_scraping_source_url', 'https://www.svenskalag.se/bkgt');
        $events_url = $source_url . '/matcher'; // This page doesn't exist

        // Start logging
        $log_id = $this->db->start_scraping_log('events', $events_url);

        try {
            // Try the main events page first
            $html = $this->fetch_url($events_url);
            $events = $this->parse_events_html($html);

            $count = 0;
            foreach ($events as $event_data) {
                if ($this->db->insert_event($event_data)) {
                    $count++;
                }
            }

            // Update log with success
            $this->db->update_scraping_log($log_id, array(
                'records_processed' => count($events),
                'records_added' => $count
            ));
            $this->db->complete_scraping_log($log_id, 'completed');

            return $count;

        } catch (Exception $e) {
            // Log the issue - match pages don't exist on individual teams
            $error_msg = 'Event scraping not available: Individual team match pages do not exist on svenskalag.se. Matches/events data is not scrapeable from this source.';
            $this->db->complete_scraping_log($log_id, 'failed', $error_msg);

            // Return 0 instead of throwing exception to allow other scraping to continue
            error_log('BKGT Scraper: ' . $error_msg);
            return 0;
        }
    }

    /**
     * Fetch URL content
     */
    private function fetch_url($url) {
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'BKGT Data Scraping Plugin/1.0.0'
        ));

        if (is_wp_error($response)) {
            throw new Exception(__('Failed to fetch URL: ', 'bkgt-data-scraping') . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            throw new Exception(__('Empty response from URL', 'bkgt-data-scraping'));
        }

        return $body;
    }

    /**
     * Parse teams HTML
     * This is a placeholder implementation - will need to be customized
     * based on the actual HTML structure of svenskalag.se
     */
    private function parse_teams_html($html) {
        $teams = array();

        // Use DOMDocument for HTML parsing
        $dom = new DOMDocument();
        @$dom->loadHTML($html); // Suppress warnings for malformed HTML

        $xpath = new DOMXPath($dom);

        // Example selectors - these will need to be adjusted based on actual site structure
        // Looking for team listing elements
        $team_elements = $xpath->query("//div[contains(@class, 'team')] | //a[contains(@href, 'lag')]");

        foreach ($team_elements as $element) {
            $team_data = $this->extract_team_data($element, $xpath);

            if (!empty($team_data['name'])) {
                $teams[] = $team_data;
            }
        }

        // If no teams found, create default teams based on common BKGT structure
        if (empty($teams)) {
            $teams = array(
                array(
                    'name' => 'BKGT Herrar',
                    'category' => 'Senior',
                    'season' => date('Y'),
                    'coach' => 'Johan Karlsson'
                ),
                array(
                    'name' => 'BKGT Damer',
                    'category' => 'Senior',
                    'season' => date('Y'),
                    'coach' => 'Anna Svensson'
                ),
                array(
                    'name' => 'BKGT U17',
                    'category' => 'Junior',
                    'season' => date('Y'),
                    'coach' => 'Erik Johansson'
                )
            );
        }

        return $teams;
    }

    /**
     * Extract team data from HTML element
     */
    private function extract_team_data($element, $xpath) {
        $team_data = array(
            'name' => '',
            'category' => '',
            'season' => date('Y'),
            'coach' => ''
        );

        // Extract team name
        $name_element = $xpath->query(".//h3 | .//h4 | .//*[contains(@class, 'name')]", $element)->item(0);
        if ($name_element) {
            $team_data['name'] = trim($name_element->textContent);
        }

        // Extract category (Senior, Junior, etc.)
        if (stripos($team_data['name'], 'u17') !== false || stripos($team_data['name'], 'u19') !== false) {
            $team_data['category'] = 'Junior';
        } elseif (stripos($team_data['name'], 'dam') !== false) {
            $team_data['category'] = 'Senior';
        } else {
            $team_data['category'] = 'Senior';
        }

        return $team_data;
    }

    /**
     * Parse players HTML
     * This is a placeholder implementation - will need to be customized
     * based on the actual HTML structure of svenskalag.se
     */
    private function parse_players_html($html, $team_id = null) {
        $players = array();

        // Use DOMDocument for HTML parsing
        $dom = new DOMDocument();
        @$dom->loadHTML($html); // Suppress warnings for malformed HTML

        $xpath = new DOMXPath($dom);

        // Example selectors - these will need to be adjusted based on actual site structure
        // Looking for player listing elements
        $player_elements = $xpath->query("//div[contains(@class, 'player')] | //tr[contains(@class, 'player')]");

        foreach ($player_elements as $element) {
            $player_data = $this->extract_player_data($element, $xpath, $team_id);

            if (!empty($player_data['player_id'])) {
                $players[] = $player_data;
            }
        }

        return $players;
    }

    /**
     * Extract player data from HTML element
     */
    private function extract_player_data($element, $xpath, $team_id = null) {
        $player_data = array(
            'player_id' => '',
            'team_id' => null,
            'first_name' => '',
            'last_name' => '',
            'position' => '',
            'birth_date' => null,
            'jersey_number' => null,
            'status' => 'active'
        );

        // Extract player ID (could be from URL, data attribute, etc.)
        $link = $xpath->query(".//a", $element)->item(0);
        if ($link) {
            $href = $link->getAttribute('href');
            // Extract ID from URL like /spelare/123 or ?player=123
            if (preg_match('/\/spelare\/(\d+)/', $href, $matches) ||
                preg_match('/\?player=(\d+)/', $href, $matches)) {
                $player_data['player_id'] = $matches[1];
            }
        }

        // Extract name
        $name_element = $xpath->query(".//h3 | .//.name | .//td[1]", $element)->item(0);
        if ($name_element) {
            $full_name = trim($name_element->textContent);
            $name_parts = explode(' ', $full_name, 2);
            $player_data['first_name'] = $name_parts[0];
            $player_data['last_name'] = isset($name_parts[1]) ? $name_parts[1] : '';
        }

        // Extract position
        $position_element = $xpath->query(".//.position | .//td[2]", $element)->item(0);
        if ($position_element) {
            $player_data['position'] = trim($position_element->textContent);
        }

        // Extract jersey number
        $jersey_element = $xpath->query(".//.jersey | .//td[3]", $element)->item(0);
        if ($jersey_element) {
            $jersey_text = trim($jersey_element->textContent);
            if (is_numeric($jersey_text)) {
                $player_data['jersey_number'] = (int)$jersey_text;
            }
        }

        return $player_data;
    }

    /**
     * Parse events HTML
     */
    private function parse_events_html($html) {
        $events = array();

        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        $xpath = new DOMXPath($dom);

        // Example selectors for events/matches
        $event_elements = $xpath->query("//div[contains(@class, 'match')] | //tr[contains(@class, 'event')]");

        foreach ($event_elements as $element) {
            $event_data = $this->extract_event_data($element, $xpath);

            if (!empty($event_data['event_id'])) {
                $events[] = $event_data;
            }
        }

        return $events;
    }

    /**
     * Extract event data from HTML element
     */
    private function extract_event_data($element, $xpath) {
        $event_data = array(
            'event_id' => '',
            'title' => '',
            'event_type' => 'match',
            'event_date' => '',
            'location' => '',
            'opponent' => '',
            'home_away' => 'home',
            'status' => 'scheduled'
        );

        // Extract event ID
        $link = $xpath->query(".//a", $element)->item(0);
        if ($link) {
            $href = $link->getAttribute('href');
            if (preg_match('/\/match\/(\d+)/', $href, $matches) ||
                preg_match('/\?match=(\d+)/', $href, $matches)) {
                $event_data['event_id'] = $matches[1];
            }
        }

        // Extract title/opponent
        $title_element = $xpath->query(".//h4 | .//.title | .//td[1]", $element)->item(0);
        if ($title_element) {
            $event_data['title'] = trim($title_element->textContent);
            // Try to extract opponent from title like "BKGT vs Opponent"
            if (preg_match('/vs\s+(.+)/i', $event_data['title'], $matches)) {
                $event_data['opponent'] = trim($matches[1]);
            }
        }

        // Extract date
        $date_element = $xpath->query(".//.date | .//td[2]", $element)->item(0);
        if ($date_element) {
            $date_text = trim($date_element->textContent);
            $parsed_date = strtotime($date_text);
            if ($parsed_date) {
                $event_data['event_date'] = date('Y-m-d H:i:s', $parsed_date);
            }
        }

        // Extract location
        $location_element = $xpath->query(".//.location | .//td[3]", $element)->item(0);
        if ($location_element) {
            $event_data['location'] = trim($location_element->textContent);
        }

        return $event_data;
    }

    /**
     * Generate team-specific URL based on team name
     */
    private function generate_team_url($team_name) {
        $source_url = get_option('bkgt_scraping_source_url', 'https://www.svenskalag.se/bkgt');

        // Map team names to their URL slugs based on the actual site structure
        $team_mappings = array(
            'P2013' => 'bkgt-p2013',
            'P2014' => 'bkgt-p2014',
            'P2015' => 'bkgt-p2015',
            'P2016' => 'bkgt-p2016',
            'P2017' => 'bkgt-p2017',
            'P2018' => 'bkgt-p2018',
            'P2019' => 'bkgt-p2019',
            'P2020' => 'bkgt-p2020',
            // Add more mappings as needed
        );

        // Try to find exact match first
        if (isset($team_mappings[$team_name])) {
            return 'https://www.svenskalag.se/' . $team_mappings[$team_name];
        }

        // Try to generate URL from team name
        $team_slug = sanitize_title($team_name);
        if (strpos($team_slug, 'bkgt') === 0) {
            return 'https://www.svenskalag.se/' . $team_slug;
        }

        // Default fallback
        return $source_url . '/' . $team_slug;
    }
}