<?php
/**
 * Debug Scraper - See what teams the scraper finds
 */

require_once('wp-load.php');

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

global $wpdb;

echo "=== Debug BKGT Scraper ===\n\n";

try {
    // Load scraper and database
    if (!class_exists('BKGT_Scraper')) {
        $scraper_files = array(
            WP_PLUGIN_DIR . '/bkgt-data-scraping/includes/class-bkgt-scraper.php',
            WP_PLUGIN_DIR . '/bkgt-core/includes/class-bkgt-scraper.php',
            get_template_directory() . '/includes/class-bkgt-scraper.php'
        );

        $loaded = false;
        foreach ($scraper_files as $file) {
            if (file_exists($file)) {
                require_once($file);
                $loaded = true;
                break;
            }
        }

        if (!$loaded) {
            throw new Exception("BKGT_Scraper class not found");
        }
    }

    // Load database class
    if (!class_exists('BKGT_DataScraping_Database')) {
        $db_files = array(
            WP_PLUGIN_DIR . '/bkgt-data-scraping/includes/class-bkgt-database.php',
            WP_PLUGIN_DIR . '/bkgt-core/includes/class-bkgt-database.php',
            get_template_directory() . '/includes/class-bkgt-database.php'
        );

        $loaded = false;
        foreach ($db_files as $file) {
            if (file_exists($file)) {
                require_once($file);
                $loaded = true;
                break;
            }
        }

        if (!$loaded) {
            throw new Exception("BKGT_DataScraping_Database class not found");
        }
    }

    // Create database instance and scraper
    $db = new BKGT_DataScraping_Database();
    $scraper = new BKGT_Scraper($db);

    // Try to get the HTML content that the scraper sees
    $source_url = get_option('bkgt_scraping_source_url', 'https://www.svenskalag.se/bkgt');

    echo "Scraping source URL: $source_url\n\n";

    // Check if we can access the scraper methods
    $reflection = new ReflectionClass($scraper);
    $parse_method = $reflection->getMethod('parse_teams_html');
    $parse_method->setAccessible(true);

    // Try to authenticate and fetch HTML
    $login_method = $reflection->getMethod('login_to_svenskalag');
    $login_method->setAccessible(true);
    $login_method->invoke($scraper);

    $fetch_method = $reflection->getMethod('fetch_url');
    $fetch_method->setAccessible(true);
    $html = $fetch_method->invoke($scraper, $source_url, true);

    echo "Fetched HTML length: " . strlen($html) . " characters\n\n";

    // Parse teams
    $teams = $parse_method->invoke($scraper, $html);

    echo "Teams found by scraper: " . count($teams) . "\n\n";

    if (!empty($teams)) {
        foreach ($teams as $i => $team) {
            echo "Team " . ($i + 1) . ": " . json_encode($team) . "\n";
        }
    } else {
        echo "No teams found in HTML.\n\n";

        // Show a sample of the HTML to debug
        echo "HTML sample (first 1000 chars):\n";
        echo substr($html, 0, 1000) . "\n...\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>