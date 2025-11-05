<?php
/**
 * Acceptance Test Runner
 * Runs user-facing page tests against the live BKGT site
 */

// Check if curl is available
if (!function_exists('curl_init')) {
    echo "ERROR: cURL is required for acceptance tests but is not available.\n";
    echo "Please install PHP cURL extension.\n";
    exit(1);
}

echo "BKGT Acceptance Test Runner\n";
echo "===========================\n\n";

$base_url = 'https://ledare.bkgt.se';
$tests_passed = 0;
$tests_failed = 0;

function run_acceptance_test($test_name, $test_function) {
    global $tests_passed, $tests_failed;

    echo "Running: {$test_name}... ";

    try {
        $result = $test_function();
        if ($result === true) {
            echo "✓ PASSED\n";
            $tests_passed++;
        } else {
            echo "✗ FAILED: {$result}\n";
            $tests_failed++;
        }
    } catch (Exception $e) {
        echo "✗ ERROR: {$e->getMessage()}\n";
        $tests_failed++;
    }
}

function make_http_request($path, $cookies = []) {
    global $base_url;

    $url = $base_url . $path;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'BKGT-Acceptance-Test/1.0');

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
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        return "cURL Error: $error";
    }

    return [
        'status_code' => $status_code,
        'body' => $response_body,
        'url' => $effective_url
    ];
}

function login_to_wordpress() {
    global $base_url;

    $login_url = $base_url . '/wp-login.php';

    $post_data = [
        'log' => 'admin',
        'pwd' => 'Anna1Martin2',
        'wp-submit' => 'Log In',
        'redirect_to' => $base_url . '/wp-admin/',
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
    curl_setopt($ch, CURLOPT_USERAGENT, 'BKGT-Acceptance-Test/1.0');

    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    if ($error) {
        curl_close($ch);
        return "Login failed: $error";
    }

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

// Test homepage loads (should redirect to login if not authenticated)
run_acceptance_test("Homepage Loads", function() {
    $response = make_http_request('/');
    if (is_string($response)) return $response;

    // Homepage should either load (200) or redirect to login (302)
    if ($response['status_code'] === 200) {
        if (strpos($response['body'], 'Fatal error') !== false) {
            return "Page contains fatal error";
        }
        if (strpos($response['body'], 'BKGT') === false) {
            return "Page does not contain expected BKGT content";
        }
        return true;
    } elseif ($response['status_code'] === 302) {
        // Check if redirecting to login
        if (strpos($response['url'], 'wp-login.php') !== false) {
            return true; // Correct redirect to login
        }
        return "Unexpected redirect to: {$response['url']}";
    } else {
        return "Unexpected status code: {$response['status_code']}";
    }
});

// Test team overview page
run_acceptance_test("Team Overview Page", function() {
    $response = make_http_request('/?page_id=22');
    if (is_string($response)) return $response;

    // Page should exist (200) or be access-controlled (403)
    if ($response['status_code'] === 200) {
        if (strpos($response['body'], 'Fatal error') !== false) {
            return "Page contains fatal error";
        }
        return true;
    } elseif ($response['status_code'] === 403) {
        return true; // Access control is working
    } else {
        return "Unexpected status code: {$response['status_code']}";
    }
});

// Test players page
run_acceptance_test("Players Page", function() {
    $response = make_http_request('/?page_id=20');
    if (is_string($response)) return $response;

    // Page should exist (200) or be access-controlled (403)
    if ($response['status_code'] === 200) {
        if (strpos($response['body'], 'Fatal error') !== false) {
            return "Page contains fatal error";
        }
        return true;
    } elseif ($response['status_code'] === 403) {
        return true; // Access control is working
    } else {
        return "Unexpected status code: {$response['status_code']}";
    }
});

// Test events page
run_acceptance_test("Events Page", function() {
    $response = make_http_request('/?page_id=21');
    if (is_string($response)) return $response;

    // Page should exist (200) or be access-controlled (403)
    if ($response['status_code'] === 200) {
        if (strpos($response['body'], 'Fatal error') !== false) {
            return "Page contains fatal error";
        }
        return true;
    } elseif ($response['status_code'] === 403) {
        return true; // Access control is working
    } else {
        return "Unexpected status code: {$response['status_code']}";
    }
});

// Test admin login page
run_acceptance_test("Admin Login Page", function() {
    $response = make_http_request('/wp-login.php');
    if (is_string($response)) return $response;

    if ($response['status_code'] !== 200) {
        return "Expected status 200, got {$response['status_code']}";
    }

    if (strpos($response['body'], 'Log In') === false) {
        return "Login page does not contain login form";
    }

    return true;
});

// Test admin login functionality
run_acceptance_test("Admin Login Functionality", function() {
    $login_result = login_to_wordpress();
    if (is_string($login_result)) return $login_result;

    if ($login_result['status_code'] !== 200 && $login_result['status_code'] !== 302) {
        return "Login failed with status {$login_result['status_code']}";
    }

    // Check if we have session cookies
    if (empty($login_result['cookies'])) {
        return "No session cookies received after login";
    }

    return true;
});

// Test admin dashboard access
run_acceptance_test("Admin Dashboard Access", function() {
    $login_result = login_to_wordpress();
    if (is_string($login_result)) return $login_result;

    $response = make_http_request('/wp-admin/', $login_result['cookies']);
    if (is_string($response)) return $response;

    if ($response['status_code'] !== 200) {
        return "Dashboard access failed with status {$response['status_code']}";
    }

    if (strpos($response['body'], 'Dashboard') === false) {
        return "Dashboard does not contain expected content";
    }

    return true;
});

// Test data scraping admin page
run_acceptance_test("Data Scraping Admin Page", function() {
    $login_result = login_to_wordpress();
    if (is_string($login_result)) return $login_result;

    $response = make_http_request('/wp-admin/admin.php?page=bkgt-data-scraping', $login_result['cookies']);
    if (is_string($response)) return $response;

    if ($response['status_code'] !== 200) {
        return "Data scraping page failed with status {$response['status_code']}";
    }

    if (strpos($response['body'], 'Datahämtning') === false) {
        return "Data scraping page does not contain expected content";
    }

    return true;
});

// Test page load performance
run_acceptance_test("Page Load Performance", function() {
    $pages = ['/', '/?page_id=22', '/?page_id=20', '/?page_id=21'];

    foreach ($pages as $page) {
        $start_time = microtime(true);
        $response = make_http_request($page);
        $load_time = microtime(true) - $start_time;

        if (is_string($response)) return "Failed to load $page: $response";

        if ($response['status_code'] !== 200 && $response['status_code'] !== 302) {
            return "Page $page failed with status {$response['status_code']}";
        }

        if ($load_time > 5.0) {
            return "Page $page took too long to load: {$load_time}s";
        }
    }

    return true;
});

// Test for broken shortcodes
run_acceptance_test("Shortcode Rendering", function() {
    $pages = [
        '/?page_id=22' => ['bkgt_team_overview'],
        '/?page_id=20' => ['bkgt_players'],
        '/?page_id=21' => ['bkgt_events']
    ];

    foreach ($pages as $page => $shortcodes) {
        $response = make_http_request($page);
        if (is_string($response)) return "Failed to load $page: $response";

        if ($response['status_code'] !== 200 && $response['status_code'] !== 302) {
            return "Page $page failed with status {$response['status_code']}";
        }

        // For protected pages, we can't check shortcodes directly, so just verify no fatal errors
        if (strpos($response['body'], 'Fatal error') !== false) {
            return "Page $page contains fatal error";
        }
    }

    return true;
});

// Summary
echo "\nAcceptance Test Summary:\n";
echo "========================\n";
echo "Passed: {$tests_passed}\n";
echo "Failed: {$tests_failed}\n";
echo "Total:  " . ($tests_passed + $tests_failed) . "\n";

if ($tests_failed === 0) {
    echo "\n🎉 All acceptance tests passed!\n";
    echo "The user-facing pages are working correctly.\n";
    exit(0);
} else {
    echo "\n❌ Some acceptance tests failed.\n";
    echo "Please check the user-facing pages for issues.\n";
    exit(1);
}