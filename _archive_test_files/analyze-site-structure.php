<?php
/**
 * Script to analyze svenskalag.se HTML structure
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "Analyzing svenskalag.se HTML structure\n";
echo "=====================================\n\n";

// Initialize scraper to get authenticated access
if (class_exists('BKGT_Database') && class_exists('BKGT_Scraper')) {
    $db = new BKGT_Database();
    $scraper = new BKGT_Scraper($db);

    // Test URLs
    $urls = array(
        'main' => 'https://www.svenskalag.se/bkgt',
        'team' => 'https://www.svenskalag.se/bkgt-p2013',
        'roster' => 'https://www.svenskalag.se/bkgt-p2013/truppen'
    );

    foreach ($urls as $name => $url) {
        echo "Fetching $name page: $url\n";
        echo str_repeat("-", 50) . "\n";

        try {
            // Use reflection to access private methods for testing
            $reflection = new ReflectionClass($scraper);
            $loginMethod = $reflection->getMethod('login_to_svenskalag');
            $loginMethod->setAccessible(true);
            $loginMethod->invoke($scraper);

            $fetchMethod = $reflection->getMethod('fetch_url');
            $fetchMethod->setAccessible(true);
            $html = $fetchMethod->invoke($scraper, $url, true);

            echo "HTML Length: " . strlen($html) . " characters\n";

            // Look for team-related content
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            // Look for various team indicators
            $team_indicators = array(
                "//a[contains(@href, 'lag')]",
                "//a[contains(@href, 'team')]",
                "//a[contains(@href, 'p20')]", // Year-based team links
                "//a[contains(@href, 'bkgt-')]", // BKGT team links
                "//div[contains(@class, 'team')]",
                "//div[contains(@class, 'lag')]",
                "//nav//a", // Navigation links
                "//ul//a", // List links
                "//li//a", // List item links
                "//div[contains(@class, 'menu')]", // Menu containers
                "//div[contains(@class, 'nav')]", // Navigation containers
                "//a[contains(text(), 'P20')]", // Links containing P20xx
                "//a[contains(text(), 'Herrar')]", // Men's team links
                "//a[contains(text(), 'Damer')]", // Women's team links
                "//h1",
                "//h2",
                "//h3",
                "//title"
            );

            foreach ($team_indicators as $selector) {
                $elements = $xpath->query($selector);
                if ($elements->length > 0) {
                    echo "Found " . $elements->length . " elements with selector: $selector\n";
                    // Show first few matches
                    $count = 0;
                    foreach ($elements as $element) {
                        if ($count >= 3) break;
                        $text = trim($element->textContent);
                        $href = $element->getAttribute('href');
                        if (!empty($text) || !empty($href)) {
                            echo "  - " . substr($text, 0, 50) . (!empty($href) ? " (href: $href)" : "") . "\n";
                            $count++;
                        }
                    }
                }
            }

            // Look for team names in text
            if (stripos($html, 'BKGT') !== false) {
                echo "Found BKGT references in HTML\n";
            }

            // Look for team patterns in HTML
            if (preg_match_all('/bkgt-[a-z0-9-]+/i', $html, $matches)) {
                echo "Found BKGT team slugs: " . implode(', ', array_unique($matches[0])) . "\n";
            }

            // Look for year-based team patterns
            if (preg_match_all('/P20[0-9]{2}/', $html, $matches)) {
                echo "Found year-based teams: " . implode(', ', array_unique($matches[0])) . "\n";
            }

        } catch (Exception $e) {
            echo "Error fetching $name page: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

} else {
    echo "❌ Could not initialize scraper classes\n";
}
?>