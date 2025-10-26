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
     * Scrape player data from svenskalag.se
     */
    private function scrape_players() {
        $source_url = get_option('bkgt_scraping_source_url', 'https://www.svenskalag.se/bkgt');
        $players_url = $source_url . '/spelare'; // Assuming this is the players page

        try {
            $html = $this->fetch_url($players_url);
            $players = $this->parse_players_html($html);

            $count = 0;
            foreach ($players as $player_data) {
                if ($this->db->insert_player($player_data)) {
                    $count++;
                }
            }

            $this->db->update_source_status($players_url, 'success');
            return $count;

        } catch (Exception $e) {
            $this->db->update_source_status($players_url, 'failed', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Scrape events data from svenskalag.se
     */
    private function scrape_events() {
        $source_url = get_option('bkgt_scraping_source_url', 'https://www.svenskalag.se/bkgt');
        $events_url = $source_url . '/matcher'; // Assuming this is the matches/events page

        try {
            $html = $this->fetch_url($events_url);
            $events = $this->parse_events_html($html);

            $count = 0;
            foreach ($events as $event_data) {
                if ($this->db->insert_event($event_data)) {
                    $count++;
                }
            }

            $this->db->update_source_status($events_url, 'success');
            return $count;

        } catch (Exception $e) {
            $this->db->update_source_status($events_url, 'failed', $e->getMessage());
            throw $e;
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
     * Parse players HTML
     * This is a placeholder implementation - will need to be customized
     * based on the actual HTML structure of svenskalag.se
     */
    private function parse_players_html($html) {
        $players = array();

        // Use DOMDocument for HTML parsing
        $dom = new DOMDocument();
        @$dom->loadHTML($html); // Suppress warnings for malformed HTML

        $xpath = new DOMXPath($dom);

        // Example selectors - these will need to be adjusted based on actual site structure
        // Looking for player listing elements
        $player_elements = $xpath->query("//div[contains(@class, 'player')] | //tr[contains(@class, 'player')]");

        foreach ($player_elements as $element) {
            $player_data = $this->extract_player_data($element, $xpath);

            if (!empty($player_data['player_id'])) {
                $players[] = $player_data;
            }
        }

        return $players;
    }

    /**
     * Extract player data from HTML element
     */
    private function extract_player_data($element, $xpath) {
        $player_data = array(
            'player_id' => '',
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
}