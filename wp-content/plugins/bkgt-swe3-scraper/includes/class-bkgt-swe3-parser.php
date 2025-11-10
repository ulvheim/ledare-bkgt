<?php
/**
 * BKGT SWE3 Parser Class
 *
 * Handles HTML parsing and document extraction from SWE3 website
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_SWE3_Parser {

    /**
     * Parse HTML content and extract document links
     *
     * @param string $html_content Raw HTML content from SWE3 page
     * @return array Array of document data
     */
    public function parse_documents($html_content) {
        $documents = array();

        // Simple regex-based parsing for PDF links
        if (preg_match_all('/href=["\']([^"\']*\.pdf[^"\']*)["\'][^>]*>([^<]*)</i', $html_content, $matches)) {
            foreach ($matches[1] as $i => $url) {
                $title = trim($matches[2][$i]);

                if (!empty($title) && !empty($url)) {
                    $documents[] = array(
                        'swe3_id' => 'swe3_' . md5($url),
                        'title' => $title,
                        'document_type' => 'other',
                        'swe3_url' => $this->normalize_url($url),
                        'version' => '1.0',
                        'publication_date' => null,
                        'scraped_date' => current_time('mysql'),
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