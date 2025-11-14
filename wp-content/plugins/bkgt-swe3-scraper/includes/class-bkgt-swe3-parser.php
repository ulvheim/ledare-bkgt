<?php
/**
 * BKGT SWE3 Parser Class
 *
 * Handles HTML parsing and document extraction from SWE3 website
 * Supports both static HTML regex parsing and JavaScript-rendered content via browser
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_SWE3_Parser {

    /**
     * Browser instance for JavaScript-heavy pages
     * 
     * @var BKGT_SWE3_Browser|null
     */
    private $browser = null;

    /**
     * Initialize parser
     */
    public function __construct() {
        // Try to initialize browser for JS-rendered content
        try {
            if ( $this->should_use_browser() ) {
                require_once dirname( __FILE__ ) . '/class-bkgt-swe3-browser.php';
                $this->browser = new BKGT_SWE3_Browser();
            }
        } catch ( Exception $e ) {
            // Browser not available, will use regex fallback
            error_log( 'SWE3 Browser not available: ' . $e->getMessage() );
        }
    }

    /**
     * Check if browser should be used for scraping
     * 
     * @return bool
     */
    private function should_use_browser() {
        // Only check if WordPress is loaded
        if ( ! function_exists( 'get_option' ) ) {
            return false;
        }
        
        // Check if browser is enabled via option
        $use_browser = get_option( 'bkgt_swe3_use_browser', true );
        
        // Check if server has required Python/Selenium
        if ( $use_browser ) {
            exec( 'which python3 2>/dev/null', $output, $return_code );
            return $return_code === 0;
        }
        
        return false;
    }

    /**
     * Parse HTML content and extract document links
     * Uses SWE3 REST API for reliable document fetching (no JS rendering needed)
     *
     * @param string $html_content Raw HTML content from SWE3 page
     * @param string $url Optional: URL being parsed (not needed for REST API approach)
     * @return array Array of document data
     */
    public function parse_documents($html_content, $url = null) {
        $documents = array();

        // Use REST API to fetch documents (most reliable)
        if ( $this->browser ) {
            $documents = $this->parse_documents_with_rest_api();
            if ( ! empty( $documents ) ) {
                return $documents;
            }
        }

        // Fallback to regex-based parsing for static HTML
        return $this->parse_documents_regex( $html_content );
    }

    /**
     * Parse documents using REST API (primary method)
     * Fetches from SWE3 WordPress Media Library
     * 
     * @return array Array of document data
     */
    private function parse_documents_with_rest_api() {
        if ( ! $this->browser ) {
            return array();
        }

        try {
            $result = $this->browser->scrape_url();
            
            if ( ! isset( $result['success'] ) || ! $result['success'] ) {
                if ( function_exists( 'error_log' ) ) {
                    error_log( 'SWE3 REST API scrape failed: ' . ( $result['error'] ?? 'Unknown error' ) );
                }
                return array();
            }

            // Convert REST API result to document format
            $documents = array();
            if ( isset( $result['documents'] ) && is_array( $result['documents'] ) ) {
                foreach ( $result['documents'] as $doc ) {
                    $documents[] = array(
                        'swe3_id' => 'swe3_' . md5( $doc['url'] ),
                        'title' => $doc['title'] ?? 'Document',
                        'document_type' => 'pdf',
                        'swe3_url' => $doc['url'],
                        'version' => '1.0',
                        'publication_date' => null,
                        'scraped_date' => function_exists( 'current_time' ) ? current_time( 'mysql' ) : date( 'Y-m-d H:i:s' ),
                    );
                }
            }
            
            return $documents;
        } catch ( Exception $e ) {
            if ( function_exists( 'error_log' ) ) {
                error_log( 'SWE3 REST API parsing error: ' . $e->getMessage() );
            }
            return array();
        }
    }

    /**
     * Parse documents using regex (fallback)
     * 
     * @param string $html_content Raw HTML content
     * @return array Array of document data
     */
    private function parse_documents_regex( $html_content ) {
        $documents = array();

        // Simple regex-based parsing for PDF links
        if (preg_match_all('/href=["\']([^"\']*\.pdf[^"\']*)["\'][^>]*>([^<]*)</i', $html_content, $matches)) {
            foreach ($matches[1] as $i => $url) {
                $title = trim($matches[2][$i]);

                if (!empty($title) && !empty($url)) {
                    $current_time = function_exists( 'current_time' ) ? current_time( 'mysql' ) : date( 'Y-m-d H:i:s' );
                    
                    $documents[] = array(
                        'swe3_id' => 'swe3_' . md5($url),
                        'title' => $title,
                        'document_type' => 'other',
                        'swe3_url' => $this->normalize_url($url),
                        'version' => '1.0',
                        'publication_date' => null,
                        'scraped_date' => $current_time,
                    );
                }
            }
        }

        return $documents;
    }

    /**
     * Normalize URL to absolute URL
     *
     * @param string $url URL to normalize
     * @return string Normalized URL
     */
    private function normalize_url($url) {
        // Handle relative URLs
        if (strpos($url, 'http') !== 0) {
            $base_url = 'https://amerikanskfotboll.swe3.se';
            $url = $base_url . '/' . ltrim($url, '/');
        }

        return $url;
    }

    /**
     * Extract text content from HTML
     *
     * @param string $html HTML content
     * @return string Plain text
     */
    public function extract_text_content($html) {
        // Remove HTML tags and decode entities
        $text = wp_strip_all_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($text);
    }

    /**
     * Validate document data
     *
     * @param array $document Document data
     * @return bool True if valid
     */
    public function validate_document($document) {
        $required_fields = array('swe3_id', 'title', 'document_type', 'swe3_url');

        foreach ($required_fields as $field) {
            if (empty($document[$field])) {
                return false;
            }
        }

        // Validate URL
        if (!filter_var($document['swe3_url'], FILTER_VALIDATE_URL)) {
            return false;
        }

        return true;
    }
}