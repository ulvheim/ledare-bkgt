    /**
     * Enhanced scraping methods with authentication
     */
    private function scrape_players() {
        // Ensure we're logged in
        if (empty($this->cookies)) {
            $this->login_to_svenskalag();
        }

        $source_url = get_option('bkgt_scraping_source_url', 'https://www.svenskalag.se/bkgt');

        // Get all teams first
        $teams = $this->db->get_teams('all');
        $total_players = 0;

        foreach ($teams as $team) {
            // Generate team-specific URL based on team name
            $team_url = $this->generate_team_url($team->name);
            $players_url = $team_url . '/truppen';

            // Start logging for this team's players
            $log_id = $this->db->start_scraping_log('players', $players_url, $team->id);

            try {
                // Use authenticated request
                $html = $this->fetch_url($players_url, true);
                $players = $this->parse_players_html($html, $team->id);

                $count = 0;
                foreach ($players as $player_data) {
                    if ($this->db->insert_player($player_data)) {
                        $count++;
                    }
                }

                // Update log with success
                $this->db->update_scraping_log($log_id, 'completed', $count);

                $total_players += $count;

            } catch (Exception $e) {
                $this->db->update_scraping_log($log_id, 'failed', 0, $e->getMessage());
                error_log('BKGT Player scraping failed for team ' . $team->name . ': ' . $e->getMessage());
            }
        }

        return $total_players;
    }

    /**
     * Enhanced events scraping with authentication
     */
    private function scrape_events() {
        // Ensure we're logged in
        if (empty($this->cookies)) {
            $this->login_to_svenskalag();
        }

        $source_url = get_option('bkgt_scraping_source_url', 'https://www.svenskalag.se/bkgt');
        $events_url = $source_url . '/matcher'; // Adjust URL as needed

        // Start logging
        $log_id = $this->db->start_scraping_log('events', $events_url);

        try {
            // Use authenticated request
            $html = $this->fetch_url($events_url, true);
            $events = $this->parse_events_html($html);

            $count = 0;
            foreach ($events as $event_data) {
                if ($this->db->insert_event($event_data)) {
                    $count++;
                }
            }

            // Update log with success
            $this->db->update_scraping_log($log_id, 'completed', $count);

            return $count;

        } catch (Exception $e) {
            $this->db->update_scraping_log($log_id, 'failed', 0, $e->getMessage());
            throw $e;
        }
    }