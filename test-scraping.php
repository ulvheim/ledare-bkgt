<?php
require_once '../../../wp-load.php';

if (!defined('ABSPATH')) {
    die('WordPress not found' . PHP_EOL);
}

echo "Testing SWE3 website scraping...\n";

$scraper = bkgt_swe3_scraper()->scraper;
$url = 'https://amerikanskfotboll.swe3.se/information-verktyg/spelregler-tavlingsbestammelser/';

echo "Fetching URL: $url\n";

$response = wp_remote_get($url, array(
    'timeout' => 30,
    'user-agent' => 'BKGT SWE3 Scraper/1.0'
));

if (is_wp_error($response)) {
    echo "Error fetching page: " . $response->get_error_message() . "\n";
    exit;
}

$body = wp_remote_retrieve_body($response);
echo "HTML content length: " . strlen($body) . " characters\n";

if (empty($body)) {
    echo "Empty response body\n";
    exit;
}

// Show first 1000 characters of HTML
echo "First 1000 characters of HTML:\n";
echo substr($body, 0, 1000) . "\n\n";

// Check for document-related keywords
$keywords = ['spelregler', 'tavlingsbestammelser', 'regler', 'bestammelser', 'pdf'];
foreach ($keywords as $keyword) {
    if (strpos(strtolower($body), $keyword) !== false) {
        echo "Found keyword '$keyword' in HTML\n";
    } else {
        echo "Keyword '$keyword' not found\n";
    }
}

// Look for any links containing document-related words
if (preg_match_all('/href=["\']([^"\']*)["\'][^>]*>([^<]*)</i', $body, $matches)) {
    echo "\nChecking " . count($matches[1]) . " links for document keywords...\n";
    $doc_links = array();
    for ($i = 0; $i < count($matches[1]); $i++) {
        $href = $matches[1][$i];
        $text = strtolower($matches[2][$i]);
        if (strpos($text, 'spelregler') !== false || strpos($text, 'tavlingsbestammelser') !== false || strpos($text, 'regler') !== false) {
            $doc_links[] = array('url' => $href, 'text' => $matches[2][$i]);
        }
    }
    if (!empty($doc_links)) {
        echo "Found " . count($doc_links) . " potentially relevant links:\n";
        foreach ($doc_links as $link) {
            echo "  - " . $link['text'] . ": " . $link['url'] . "\n";
        }
    } else {
        echo "No document-related links found\n";
    }
}

// Test parser
$parser = bkgt_swe3_scraper()->parser;
$documents = $parser->parse_documents($body);

echo "Documents found: " . count($documents) . "\n";

if (count($documents) > 0) {
    echo "First document:\n";
    print_r($documents[0]);
} else {
    echo "No documents found. Checking for PDF links manually...\n";
    if (preg_match_all('/href=["\']([^"\']*\.pdf[^"\']*)["\'][^>]*>([^<]*)</i', $body, $matches)) {
        echo "Found " . count($matches[1]) . " PDF links manually:\n";
        for ($i = 0; $i < min(3, count($matches[1])); $i++) {
            echo "  - " . trim($matches[2][$i]) . ": " . $matches[1][$i] . "\n";
        }
    } else {
        echo "No PDF links found in HTML\n";
    }
}
?>