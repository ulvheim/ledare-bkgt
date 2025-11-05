    /**
     * Session cookies for authenticated requests
     */
    private $cookies = array();

    /**
     * Login to svenskalag.se
     */
    private function login_to_svenskalag() {
        $username = get_option('bkgt_scraping_username');
        $password = get_option('bkgt_scraping_password');

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
            throw new Exception(__('Failed to fetch URL: ', 'bkgt-data-scraping') . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            throw new Exception(__('Empty response from URL', 'bkgt-data-scraping'));
        }

        // Check if we got redirected to login page (session expired)
        if (strpos($body, 'Du måste logga in') !== false && $authenticated) {
            // Try to re-login and retry the request
            $this->login_to_svenskalag();
            return $this->fetch_url($url, true);
        }

        return $body;
    }