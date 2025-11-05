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
     * Session cookies for authenticated requests
     */
    private $cookies = array();

    /**
     * Constructor
     */
    public function __construct($db) {
        if (!$db instanceof BKGT_DataScraping_Database) {
            throw new Exception('Invalid database instance provided');
        }

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
            // Authenticate first
            $this->login_to_svenskalag();

            $html = $this->fetch_url($teams_url, true); // Use authenticated request
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

        // Authenticate first
        $this->login_to_svenskalag();

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
                $html = $this->fetch_url($players_url, true); // Use authenticated request
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
                    $html = $this->fetch_url($general_players_url, true); // Use authenticated request
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

        // Authenticate first
        $this->login_to_svenskalag();

        // Start logging
        $log_id = $this->db->start_scraping_log('events', $events_url);

        try {
            // Try the main events page first
            $html = $this->fetch_url($events_url, true); // Use authenticated request
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
     * Login to svenskalag.se
     */
    private function login_to_svenskalag() {
        $stored_username = get_option('bkgt_scraping_username');
        $stored_password = get_option('bkgt_scraping_password');

        // Check if credentials are encrypted (base64 encoded) or plain text
        $admin = new BKGT_Admin($this->db);

        // If it looks like base64 (has padding =), try to decrypt
        if (strpos($stored_username, '=') !== false && strpos($stored_username, '/') !== false) {
            $username = $admin->decrypt_credential($stored_username);
            $password = $admin->decrypt_credential($stored_password);
        } else {
            // Assume plain text
            $username = $stored_username;
            $password = $stored_password;
        }

        if (empty($username) || empty($password)) {
            throw new Exception(__('Scraping credentials not configured', 'bkgt-data-scraping'));
        }

        // First, get the login page to extract any CSRF tokens or form data
        $login_page_url = 'https://www.svenskalag.se/login';
        $response = wp_remote_get($login_page_url, array(
            'timeout' => 30,
            'user-agent' => 'BKGT Data Scraping Plugin/1.0.0'
        ));

        if (is_wp_error($response)) {
            throw new Exception(__('Failed to access login page: ', 'bkgt-data-scraping') . $response->get_error_message());
        }

        $login_html = wp_remote_retrieve_body($response);

        // Extract form fields (this might need adjustment based on actual form structure)
        $csrf_token = $this->extract_csrf_token($login_html);

        // Prepare login data
        $login_data = array(
            'username' => $username,
            'password' => $password,
        );

        if ($csrf_token) {
            $login_data['_token'] = $csrf_token; // Adjust field name as needed
        }

        // Submit login form
        $login_response = wp_remote_post($login_page_url, array(
            'timeout' => 30,
            'user-agent' => 'BKGT Data Scraping Plugin/1.0.0',
            'body' => $login_data,
            'cookies' => array() // Start with empty cookies
        ));

        if (is_wp_error($login_response)) {
            throw new Exception(__('Login failed: ', 'bkgt-data-scraping') . $login_response->get_error_message());
        }

        // Store cookies from login response
        $this->cookies = wp_remote_retrieve_cookies($login_response);

        // Check if login was successful by looking for redirect or success indicators
        $login_body = wp_remote_retrieve_body($login_response);
        if (strpos($login_body, 'login') !== false && strpos($login_body, 'error') !== false) {
            throw new Exception(__('Login credentials incorrect or login failed', 'bkgt-data-scraping'));
        }

        return true;
    }

    /**
     * Extract CSRF token from login form
     */
    private function extract_csrf_token($html) {
        // Look for common CSRF token patterns
        $patterns = array(
            '/name="_token" value="([^"]+)"/i',
            '/name="csrf_token" value="([^"]+)"/i',
            '/name="_csrf" value="([^"]+)"/i'
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                return $matches[1];
            }
        }

        return null; // No CSRF token found
    }

    /**
     * Enhanced fetch_url with authentication support
     */
    private function fetch_url($url, $authenticated = false) {
        $args = array(
            'timeout' => 30,
            'user-agent' => 'BKGT Data Scraping Plugin/1.0.0'
        );

        // Add cookies if we have authenticated session
        if ($authenticated && !empty($this->cookies)) {
            $args['cookies'] = $this->cookies;
        }

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            throw new Exception(__('Failed to fetch URL: ', 'bkgt-data-scraping') . $url . ' - ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);

        // Check if we got redirected to login page (session expired)
        if ($authenticated && strpos($body, 'login') !== false) {
            // Try to re-authenticate
            $this->login_to_svenskalag();
            // Retry the request with new session
            $args['cookies'] = $this->cookies;
            $response = wp_remote_get($url, $args);

            if (is_wp_error($response)) {
                throw new Exception(__('Failed to fetch URL after re-authentication: ', 'bkgt-data-scraping') . $url);
            }

            $body = wp_remote_retrieve_body($response);
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

        // Look for team links in navigation menus
        // Teams are listed as links like /bkgt-p2013, /bkgt-p2014, etc.
        $team_links = $xpath->query("//a[contains(@href, '/bkgt-p20')]");

        foreach ($team_links as $link) {
            $href = $link->getAttribute('href');
            $text = trim($link->textContent);

            // Extract team info from URL and text
            if (preg_match('/\/bkgt-(p\d{4})/i', $href, $matches)) {
                $team_code = strtoupper($matches[1]); // e.g., P2013

                $team_data = array(
                    'name' => $text ?: $team_code,
                    'source_id' => $team_code,
                    'source_url' => 'https://www.svenskalag.se' . $href,
                    'category' => $this->determine_team_category($team_code),
                    'season' => date('Y')
                );

                // Avoid duplicates
                $duplicate = false;
                foreach ($teams as $existing_team) {
                    if ($existing_team['source_id'] === $team_code) {
                        $duplicate = true;
                        break;
                    }
                }

                if (!$duplicate) {
                    $teams[] = $team_data;
                }
            }
        }

        // If no teams found, return empty array (don't create synthetic data)
        return $teams;
    }

    /**
     * Determine team category based on team code
     */
    private function determine_team_category($team_code) {
        // Extract year from team code (e.g., P2013 -> 2013)
        if (preg_match('/P(\d{4})/', $team_code, $matches)) {
            $birth_year = (int)$matches[1];
            $current_year = (int)date('Y');
            $age = $current_year - $birth_year;

            // Categorization based on age:
            // Barn: age < 14 (born after current_year - 14)
            // Ungdom: 14 <= age < 21 (born between current_year - 21 and current_year - 14)
            // Senior: age >= 21 (born before current_year - 21)
            if ($age < 14) {
                return 'Barn';
            } elseif ($age < 21) {
                return 'Ungdom';
            } else {
                return 'Senior';
            }
        }

        return 'Senior';
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
     * Extracts only essential player data: player_id (unique key), first_name, last_name, jersey_number (if assigned)
     */
    private function parse_players_html($html, $team_id = null) {
        $players = array();

        // Use DOMDocument for HTML parsing
        $dom = new DOMDocument();
        @$dom->loadHTML($html); // Suppress warnings for malformed HTML

        $xpath = new DOMXPath($dom);

        // Look for player listing elements (table rows, player cards, etc.)
        $player_elements = $xpath->query("//tr[contains(@class, 'player')] | //div[contains(@class, 'player')] | //tbody/tr | //ul/li[contains(@class, 'player')]");

        foreach ($player_elements as $element) {
            $player_data = $this->extract_player_data($element, $xpath, $team_id);

            // Only add if we have valid required data
            if (!empty($player_data) && !empty($player_data['first_name']) && !empty($player_data['last_name'])) {
                $players[] = $player_data;
            }
        }

        return $players;
    }

    /**
     * Extract player data from HTML element
     * Only extracts: player_id (unique key), first_name, last_name, jersey_number (if assigned)
     */
    private function extract_player_data($element, $xpath, $team_id = null) {
        $player_data = array(
            'player_id' => '',
            'team_id' => $team_id,
            'first_name' => '',
            'last_name' => '',
            'position' => '', // Not needed but keep for database compatibility
            'birth_date' => null, // Not needed
            'jersey_number' => null,
            'status' => 'active'
        );

        // Extract player ID (unique key from URL)
        $link = $xpath->query(".//a", $element)->item(0);
        if ($link) {
            $href = $link->getAttribute('href');
            // Extract ID from URL like /spelare/123 or ?player=123
            if (preg_match('/\/spelare\/(\d+)/', $href, $matches) ||
                preg_match('/\?player=(\d+)/', $href, $matches)) {
                $player_data['player_id'] = $matches[1];
            }
        }

        // Extract first and last name (required)
        $name_element = $xpath->query(".//h3 | .//*[contains(@class, 'name')] | .//td[1]", $element)->item(0);
        if ($name_element) {
            $full_name = trim($name_element->textContent);
            $name_parts = explode(' ', $full_name, 2);
            $player_data['first_name'] = $name_parts[0];
            $player_data['last_name'] = isset($name_parts[1]) ? $name_parts[1] : '';
        }

        // Extract jersey number (if assigned)
        $jersey_element = $xpath->query(".//*[contains(@class, 'jersey')] | .//td[2] | .//td[3]", $element)->item(0);
        if ($jersey_element) {
            $jersey_text = trim($jersey_element->textContent);
            // Remove any non-numeric characters and check if it's a valid jersey number
            $jersey_clean = preg_replace('/[^\d]/', '', $jersey_text);
            if (is_numeric($jersey_clean) && $jersey_clean > 0 && $jersey_clean <= 99) {
                $player_data['jersey_number'] = (int)$jersey_clean;
            }
        }

        // Validate required fields
        if (empty($player_data['first_name']) || empty($player_data['last_name'])) {
            return array(); // Return empty array if required fields missing
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