<?php
/**
 * SWE3 document parser class
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_SWE3_Parser {

    /**
     * Parse HTML content for document information
     */
    public function parse_html_content($html) {
        $documents = array();

        // Use DOMDocument for robust HTML parsing
        $dom = new DOMDocument();
        @$dom->loadHTML($html); // Suppress warnings for malformed HTML

        $xpath = new DOMXPath($dom);

        // Find all document links
        $document_links = $this->find_document_links($xpath);

        foreach ($document_links as $link_data) {
            $document = $this->extract_document_info($link_data, $xpath);

            if ($document) {
                $documents[] = $document;
            }
        }

        return $documents;
    }

    /**
     * Find all document links in the HTML
     */
    private function find_document_links($xpath) {
        $links = array();

        // Common patterns for document links
        $patterns = array(
            '//a[contains(@href, ".pdf")]',
            '//a[contains(@href, ".doc")]',
            '//a[contains(@href, ".docx")]',
            '//a[contains(@href, "wp-content/uploads")]',
            '//a[contains(@href, "download")]',
        );

        foreach ($patterns as $pattern) {
            $elements = $xpath->query($pattern);

            foreach ($elements as $element) {
                $href = $element->getAttribute('href');
                $text = trim($element->textContent);

                if (!empty($href) && !empty($text)) {
                    $links[] = array(
                        'element' => $element,
                        'href' => $href,
                        'text' => $text,
                    );
                }
            }
        }

        return $links;
    }

    /**
     * Extract document information from link data
     */
    private function extract_document_info($link_data, $xpath) {
        $href = $link_data['href'];
        $text = $link_data['text'];

        // Convert to absolute URL if needed
        $absolute_url = $this->make_absolute_url($href);

        // Skip non-document URLs
        if (!$this->is_document_url($absolute_url)) {
            return null;
        }

        // Clean and normalize the title
        $title = $this->clean_title($text);

        // Determine document type
        $type = $this->determine_document_type($title, $absolute_url);

        // Extract additional metadata
        $metadata = $this->extract_metadata_from_context($link_data['element'], $xpath);

        return array_merge(array(
            'url' => $absolute_url,
            'title' => $title,
            'type' => $type,
            'original_text' => $text,
        ), $metadata);
    }

    /**
     * Make URL absolute
     */
    private function make_absolute_url($url) {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $base_url = 'https://amerikanskfotboll.swe3.se';
        return rtrim($base_url, '/') . '/' . ltrim($url, '/');
    }

    /**
     * Check if URL points to a document
     */
    private function is_document_url($url) {
        $document_extensions = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx');
        $path = parse_url($url, PHP_URL_PATH);

        if (!$path) {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, $document_extensions);
    }

    /**
     * Clean and normalize document title
     */
    private function clean_title($text) {
        // Remove extra whitespace
        $title = trim(preg_replace('/\s+/', ' ', $text));

        // Remove common prefixes/suffixes that aren't part of the title
        $patterns_to_remove = array(
            '/^Ladda ner\s+/i',     // "Ladda ner" (Download)
            '/\s*\([^)]*\)$/',       // Content in parentheses at end
            '/\s*\[[^\]]*\]$/',      // Content in brackets at end
            '/\s*-\s*$/',            // Trailing dash
        );

        foreach ($patterns_to_remove as $pattern) {
            $title = preg_replace($pattern, '', $title);
        }

        return trim($title);
    }

    /**
     * Determine document type from title and URL
     */
    private function determine_document_type($title, $url) {
        $title_lower = strtolower($title);
        $url_lower = strtolower($url);

        // Competition regulations
        if (strpos($title_lower, 'tävlingsbestämmelse') !== false ||
            strpos($url_lower, 'tavlingsbestammelse') !== false ||
            strpos($title_lower, 'competition regulation') !== false) {
            return 'competition-regulations';
        }

        // Game rules
        if (strpos($title_lower, 'spelregel') !== false ||
            strpos($url_lower, 'spelregel') !== false ||
            strpos($title_lower, 'game rule') !== false) {
            return 'game-rules';
        }

        // Referee guidelines
        if (strpos($title_lower, 'domar') !== false ||
            strpos($title_lower, 'referee') !== false ||
            strpos($title_lower, 'judge') !== false) {
            return 'referee-guidelines';
        }

        // Development series / youth
        if (strpos($title_lower, 'utveckling') !== false ||
            strpos($title_lower, 'u-serie') !== false ||
            strpos($title_lower, 'youth') !== false ||
            strpos($title_lower, 'junior') !== false) {
            return 'development-series';
        }

        // Safety and medical
        if (strpos($title_lower, 'säkerhet') !== false ||
            strpos($title_lower, 'medicinsk') !== false ||
            strpos($title_lower, 'safety') !== false ||
            strpos($title_lower, 'medical') !== false ||
            strpos($title_lower, 'first aid') !== false) {
            return 'safety-medical';
        }

        // Easy Football
        if (strpos($title_lower, 'easy football') !== false ||
            strpos($title_lower, 'enkelt') !== false) {
            return 'easy-football';
        }

        // Competition formats
        if (strpos($title_lower, 'tävlingsformat') !== false ||
            strpos($title_lower, 'competition format') !== false ||
            strpos($title_lower, 'series') !== false) {
            return 'competition-formats';
        }

        // Default
        return 'general';
    }

    /**
     * Extract additional metadata from HTML context
     */
    private function extract_metadata_from_context($element, $xpath) {
        $metadata = array(
            'description' => null,
            'publication_date' => null,
            'version' => null,
            'file_size' => null,
        );

        // Try to find description in nearby elements
        $description = $this->find_nearby_description($element, $xpath);
        if ($description) {
            $metadata['description'] = $description;
        }

        // Extract version from title or URL
        $version = $this->extract_version($element->textContent, $element->getAttribute('href'));
        if ($version) {
            $metadata['version'] = $version;
        }

        // Try to extract publication date
        $pub_date = $this->extract_publication_date($element, $xpath);
        if ($pub_date) {
            $metadata['publication_date'] = $pub_date;
        }

        return $metadata;
    }

    /**
     * Find description text near the link
     */
    private function find_nearby_description($element, $xpath) {
        // Look for nearby paragraph or div elements
        $parent = $element->parentNode;

        // Check parent element for text content
        if ($parent && $parent->textContent) {
            $full_text = trim($parent->textContent);
            $link_text = trim($element->textContent);

            // Remove the link text and clean up
            $description = str_replace($link_text, '', $full_text);
            $description = trim(preg_replace('/\s+/', ' ', $description));

            if (!empty($description) && strlen($description) > 10) {
                return $description;
            }
        }

        return null;
    }

    /**
     * Extract version information
     */
    private function extract_version($title, $url) {
        // Look for year patterns (e.g., 2026, 2025)
        if (preg_match('/\b(20\d{2})\b/', $title, $matches)) {
            return $matches[1];
        }

        // Look for version patterns in URL
        if (preg_match('/[\/\-]v?(\d+(?:\.\d+)*)/i', $url, $matches)) {
            return $matches[1];
        }

        // Look for revision patterns
        if (preg_match('/rev\.?\s*(\d+(?:\.\d+)*)/i', $title, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract publication date from context
     */
    private function extract_publication_date($element, $xpath) {
        // Look for date patterns in nearby text
        $context_text = $this->get_context_text($element, $xpath);

        // Common Swedish date patterns
        $date_patterns = array(
            '/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\b/',  // DD/MM/YYYY or DD-MM-YYYY
            '/\b(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})\b/',  // YYYY/MM/DD or YYYY-MM-DD
            '/\b(\d{1,2})\s+(januari|februari|mars|april|maj|juni|juli|augusti|september|oktober|november|december)\s+(\d{4})\b/i',
        );

        foreach ($date_patterns as $pattern) {
            if (preg_match($pattern, $context_text, $matches)) {
                try {
                    if (count($matches) >= 4) {
                        if (strlen($matches[1]) == 4) {
                            // YYYY-MM-DD format
                            $date = sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
                        } else {
                            // DD-MM-YYYY format
                            $date = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
                        }

                        // Validate date
                        if (strtotime($date) !== false) {
                            return $date;
                        }
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Get context text around element
     */
    private function get_context_text($element, $xpath) {
        $context_parts = array();

        // Get text from parent elements
        $current = $element;
        for ($i = 0; $i < 3 && $current; $i++) {
            if ($current->textContent) {
                $context_parts[] = $current->textContent;
            }
            $current = $current->parentNode;
        }

        return implode(' ', $context_parts);
    }

    /**
     * Parse PDF metadata (if PDF parsing library is available)
     */
    public function parse_pdf_metadata($file_path) {
        $metadata = array(
            'title' => null,
            'author' => null,
            'subject' => null,
            'creator' => null,
            'producer' => null,
            'creation_date' => null,
            'modification_date' => null,
            'pages' => null,
        );

        // TODO: Implement PDF parsing if a suitable library is available
        // This could use libraries like TCPDF, FPDI, or PDF parsers

        // For now, return basic file information
        if (file_exists($file_path)) {
            $metadata['file_size'] = filesize($file_path);

            // Try to get basic info using getID3 if available
            if (class_exists('getID3')) {
                $getID3 = new getID3();
                $file_info = $getID3->analyze($file_path);

                if (isset($file_info['filesize'])) {
                    $metadata['file_size'] = $file_info['filesize'];
                }
            }
        }

        return $metadata;
    }

    /**
     * Validate document information
     */
    public function validate_document($document) {
        $errors = array();

        // Check required fields
        if (empty($document['url'])) {
            $errors[] = 'Missing document URL';
        }

        if (empty($document['title'])) {
            $errors[] = 'Missing document title';
        }

        // Validate URL format
        if (!empty($document['url']) && !filter_var($document['url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid document URL format';
        }

        // Check if URL is accessible (optional, might be slow)
        if (!empty($document['url']) && defined('BKGT_SWE3_VALIDATE_URLS') && BKGT_SWE3_VALIDATE_URLS) {
            $headers = @get_headers($document['url'], 1);
            if (!$headers || strpos($headers[0], '200') === false) {
                $errors[] = 'Document URL is not accessible';
            }
        }

        return $errors;
    }
}