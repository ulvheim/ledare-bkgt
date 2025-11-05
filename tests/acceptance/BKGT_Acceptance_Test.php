<?php
/**
 * Acceptance Tests for BKGT User-Facing Pages
 * Tests actual page content and user experience
 */

class BKGT_Acceptance_Test extends BKGT_TestCase {

    private $base_url = 'https://ledare.bkgt.se';
    private $admin_credentials = [
        'username' => 'admin',
        'password' => 'Anna1Martin2'
    ];

    /**
     * Test homepage loads without errors
     */
    public function test_homepage_loads() {
        $response = $this->makeHttpRequest('/');
        $this->assertEquals(200, $response['status_code']);
        $this->assertStringContains($response['body'], 'BKGT');
        $this->assertStringNotContains($response['body'], 'Fatal error');
        $this->assertStringNotContains($response['body'], 'Parse error');
        $this->assertStringNotContains($response['body'], 'Warning');
    }

    /**
     * Test team overview page content
     */
    public function test_team_overview_page() {
        $response = $this->makeHttpRequest('/team-overview/');
        $this->assertEquals(200, $response['status_code']);
        $this->assertStringContains($response['body'], 'Team Overview');
        $this->assertStringContains($response['body'], 'bkgt_team_overview');
        $this->assertStringNotContains($response['body'], 'Fatal error');
    }

    /**
     * Test players page content
     */
    public function test_players_page() {
        $response = $this->makeHttpRequest('/players/');
        $this->assertEquals(200, $response['status_code']);
        $this->assertStringContains($response['body'], 'Players');
        $this->assertStringContains($response['body'], 'bkgt_players');
        $this->assertStringNotContains($response['body'], 'Fatal error');
    }

    /**
     * Test events page content
     */
    public function test_events_page() {
        $response = $this->makeHttpRequest('/events/');
        $this->assertEquals(200, $response['status_code']);
        $this->assertStringContains($response['body'], 'Events');
        $this->assertStringContains($response['body'], 'bkgt_events');
        $this->assertStringNotContains($response['body'], 'Fatal error');
    }

    /**
     * Test admin login page loads
     */
    public function test_admin_login_page() {
        $response = $this->makeHttpRequest('/wp-login.php');
        $this->assertEquals(200, $response['status_code']);
        $this->assertStringContains($response['body'], 'Log In');
        $this->assertStringContains($response['body'], 'Username');
        $this->assertStringContains($response['body'], 'Password');
    }

    /**
     * Test admin dashboard access (requires login)
     */
    public function test_admin_dashboard_access() {
        // First login to get cookies
        $login_response = $this->loginToWordPress();
        $this->assertEquals(200, $login_response['status_code']);

        // Then access dashboard
        $dashboard_response = $this->makeHttpRequest('/wp-admin/', $login_response['cookies']);
        $this->assertEquals(200, $dashboard_response['status_code']);
        $this->assertStringContains($dashboard_response['body'], 'Dashboard');
        $this->assertStringContains($dashboard_response['body'], 'Welcome to WordPress');
    }

    /**
     * Test data scraping admin page
     */
    public function test_data_scraping_admin_page() {
        $login_response = $this->loginToWordPress();
        $this->assertEquals(200, $login_response['status_code']);

        $response = $this->makeHttpRequest('/wp-admin/admin.php?page=bkgt-data-scraping', $login_response['cookies']);
        $this->assertEquals(200, $response['status_code']);
        $this->assertStringContains($response['body'], 'Datahämtning');
        $this->assertStringContains($response['body'], 'Manuell Inmatning');
    }

    /**
     * Test inventory admin page
     */
    public function test_inventory_admin_page() {
        $login_response = $this->loginToWordPress();
        $this->assertEquals(200, $login_response['status_code']);

        $response = $this->makeHttpRequest('/wp-admin/admin.php?page=bkgt-inventory', $login_response['cookies']);
        $this->assertEquals(200, $response['status_code']);
        $this->assertStringContains($response['body'], 'Utrustning');
    }

    /**
     * Test page content validation - check for broken shortcodes
     */
    public function test_page_content_validation() {
        $pages_to_test = [
            '/team-overview/' => ['bkgt_team_overview'],
            '/players/' => ['bkgt_players'],
            '/events/' => ['bkgt_events']
        ];

        foreach ($pages_to_test as $page => $expected_shortcodes) {
            $response = $this->makeHttpRequest($page);
            $this->assertEquals(200, $response['status_code']);

            // Check that shortcodes are not displayed as raw text
            foreach ($expected_shortcodes as $shortcode) {
                $this->assertStringNotContains($response['body'], "[$shortcode");
                $this->assertStringNotContains($response['body'], "[/$shortcode]");
            }

            // Check for common error indicators
            $this->assertStringNotContains($response['body'], 'Fatal error');
            $this->assertStringNotContains($response['body'], 'Parse error');
            $this->assertStringNotContains($response['body'], 'Warning');
            $this->assertStringNotContains($response['body'], 'Notice');
        }
    }

    /**
     * Test responsive design - check for mobile-friendly elements
     */
    public function test_responsive_design() {
        $response = $this->makeHttpRequest('/team-overview/');
        $this->assertEquals(200, $response['status_code']);

        // Check for viewport meta tag
        $this->assertStringContains($response['body'], 'viewport');

        // Check for responsive CSS classes
        $this->assertStringContains($response['body'], 'class=');

        // Check for Bootstrap or similar responsive framework
        $this->assertTrue(
            strpos($response['body'], 'bootstrap') !== false ||
            strpos($response['body'], 'flex') !== false ||
            strpos($response['body'], 'grid') !== false
        );
    }

    /**
     * Test page load performance
     */
    public function test_page_load_performance() {
        $pages_to_test = ['/', '/team-overview/', '/players/', '/events/'];

        foreach ($pages_to_test as $page) {
            $start_time = microtime(true);
            $response = $this->makeHttpRequest($page);
            $load_time = microtime(true) - $start_time;

            $this->assertEquals(200, $response['status_code']);
            $this->assertLessThan(5.0, $load_time, "Page $page took too long to load: {$load_time}s");
        }
    }

    /**
     * Test for broken links and images
     */
    public function test_broken_links_and_images() {
        $response = $this->makeHttpRequest('/team-overview/');
        $this->assertEquals(200, $response['status_code']);

        // Extract all links and images
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $response['body'], $links);
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $response['body'], $images);

        // Check that links don't point to obvious error pages
        foreach ($links[1] as $link) {
            if (strpos($link, 'http') === 0) { // External links only
                $this->assertStringNotContains($link, '404');
                $this->assertStringNotContains($link, 'error');
            }
        }

        // Check that images exist (basic check)
        foreach ($images[1] as $image) {
            if (strpos($image, 'http') === 0 && strpos($image, $this->base_url) === 0) {
                // Could implement HEAD request to check if image exists
                // For now, just ensure it's not obviously broken
                $this->assertStringNotContains($image, 'broken');
                $this->assertStringNotContains($image, '404');
            }
        }
    }

    /**
     * Test user role access control
     */
    public function test_user_role_access_control() {
        // Test public pages (should work without login)
        $public_pages = ['/', '/team-overview/', '/players/', '/events/'];
        foreach ($public_pages as $page) {
            $response = $this->makeHttpRequest($page);
            $this->assertEquals(200, $response['status_code']);
        }

        // Test admin pages (should redirect to login)
        $admin_pages = ['/wp-admin/', '/wp-admin/admin.php?page=bkgt-data-scraping'];
        foreach ($admin_pages as $page) {
            $response = $this->makeHttpRequest($page);
            // Should redirect to login page
            $this->assertTrue(
                $response['status_code'] == 302 ||
                strpos($response['body'], 'login') !== false
            );
        }
    }

    /**
     * Test data display accuracy
     */
    public function test_data_display_accuracy() {
        // First ensure we have test data
        $this->setupTestData();

        // Test team overview page shows team data
        $response = $this->makeHttpRequest('/team-overview/');
        $this->assertEquals(200, $response['status_code']);

        // Should contain team information
        $this->assertStringContains($response['body'], 'Team');

        // Test players page shows player data
        $response = $this->makeHttpRequest('/players/');
        $this->assertEquals(200, $response['status_code']);

        // Should contain player information
        $this->assertStringContains($response['body'], 'Player');

        // Clean up
        $this->cleanupTestData();
    }

    /**
     * Helper method to make HTTP requests
     */
    private function makeHttpRequest($path, $cookies = []) {
        $url = $this->base_url . $path;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if (!empty($cookies)) {
            $cookie_string = '';
            foreach ($cookies as $name => $value) {
                $cookie_string .= "$name=$value; ";
            }
            curl_setopt($ch, CURLOPT_COOKIE, rtrim($cookie_string, '; '));
        }

        $response_body = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        curl_close($ch);

        return [
            'status_code' => $status_code,
            'body' => $response_body,
            'url' => $effective_url
        ];
    }

    /**
     * Helper method to login to WordPress
     */
    private function loginToWordPress() {
        $login_url = $this->base_url . '/wp-login.php';

        $post_data = [
            'log' => $this->admin_credentials['username'],
            'pwd' => $this->admin_credentials['password'],
            'wp-submit' => 'Log In',
            'redirect_to' => $this->base_url . '/wp-admin/',
            'testcookie' => '1'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $login_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Extract cookies from response
        $cookies = [];
        preg_match_all('/Set-Cookie: ([^;]+)/', $response, $matches);
        foreach ($matches[1] as $cookie) {
            list($name, $value) = explode('=', $cookie, 2);
            $cookies[$name] = $value;
        }

        curl_close($ch);

        return [
            'status_code' => $status_code,
            'cookies' => $cookies,
            'response' => $response
        ];
    }
}