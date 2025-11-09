<?php
/**
 * Core SWE3 scraper class
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_SWE3_Scraper {

    /**
     * SWE3 rules page URL
     */
    const SWE3_RULES_URL = 'https://amerikanskfotboll.swe3.se/information-verktyg/spelregler-tavlingsbestammelser/';

    /**
     * User agent for requests
     */
    const USER_AGENT = 'BKGT SWE3 Scraper/1.0 (https://github.com/bkgt/ledare-bkgt)';

    /**
     * Request timeout in seconds
     */
    const REQUEST_TIMEOUT = 30;

    /**
     * Delay between requests in seconds
     */
    const REQUEST_DELAY = 2;

    /**
     * Maximum retry attempts
     */
    const MAX_RETRIES = 3;

    /**
     * Execute the scraping process
     */
    public function execute_scrape() {
        $this->log('info', 'Starting SWE3 document scraping process');

        try {
            // Scrape the rules page
            $documents = $this->scrape_rules_page();

            if (empty($documents)) {
                $this->log('warning', 'No documents found on SWE3 rules page');
                return false;
            }

            $this->log('info', sprintf('Found %d documents to process', count($documents)));

            // Process each document
            $processed_count = 0;
            foreach ($documents as $document) {
                if ($this->process_document($document)) {
                    $processed_count++;
                }

                // Respectful delay between requests
                sleep(self::REQUEST_DELAY);
            }

            $this->log('info', sprintf('Successfully processed %d out of %d documents', $processed_count, count($documents)));

            // Update last scrape timestamp
            update_option('bkgt_swe3_last_scrape', current_time('mysql'));

            return true;

        } catch (Exception $e) {
            $this->log('error', 'Scraping process failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Scrape the SWE3 rules page for documents
     */
    public function scrape_rules_page() {
        $this->log('info', 'Scraping SWE3 rules page: ' . self::SWE3_RULES_URL);

        $response = $this->make_request(self::SWE3_RULES_URL);

        if (is_wp_error($response)) {
            $this->log('error', 'Failed to fetch rules page: ' . $response->get_error_message());
            return array();
        }

        $html = wp_remote_retrieve_body($response);

        if (empty($html)) {
            $this->log('error', 'Empty response from rules page');
            return array();
        }

        // Parse HTML for document links
        $documents = $this->parse_document_links($html);

        $this->log('info', sprintf('Parsed %d document links from rules page', count($documents)));

        return $documents;
    }

    /**
     * Parse HTML for document links
     */
    private function parse_document_links($html) {
        $documents = array();

        // Use DOMDocument for parsing
        $dom = new DOMDocument();
        @$dom->loadHTML($html); // Suppress warnings for malformed HTML

        $xpath = new DOMXPath($dom);

        // Look for PDF links and document links
        $link_patterns = array(
            '//a[contains(@href, ".pdf")]',           // PDF files
            '//a[contains(@href, "wp-content/uploads")]', // Uploaded files
        );

        foreach ($link_patterns as $pattern) {
            $links = $xpath->query($pattern);

            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                $text = trim($link->textContent);

                if (empty($href) || empty($text)) {
                    continue;
                }

                // Convert relative URLs to absolute
                $absolute_url = $this->make_absolute_url($href, self::SWE3_RULES_URL);

                // Skip if not a document URL
                if (!$this->is_document_url($absolute_url)) {
                    continue;
                }

                $documents[] = array(
                    'url' => $absolute_url,
                    'title' => $text,
                    'type' => $this->determine_document_type($text, $absolute_url),
                );
            }
        }

        // Remove duplicates based on URL
        $documents = array_unique($documents, SORT_REGULAR);

        return $documents;
    }

    /**
     * Process a single document
     */
    public function process_document($document) {
        try {
            $this->log('info', sprintf('Processing document: %s', $document['title']));

            // Check if document already exists
            $existing_doc = $this->get_existing_document($document['url']);

            // Download the document
            $download_result = $this->download_document($document['url']);

            if (!$download_result) {
                $this->log('error', sprintf('Failed to download document: %s', $document['url']));
                $this->update_document_status($document['url'], 'error', 'Download failed');
                return false;
            }

            $file_path = $download_result['path'];
            $file_hash = $download_result['hash'];

            // Check if document has changed
            if ($existing_doc && $existing_doc->file_hash === $file_hash) {
                $this->log('info', sprintf('Document unchanged: %s', $document['title']));
                $this->update_document_last_checked($document['url']);
                return true;
            }

            // Extract metadata
            $metadata = $this->extract_document_metadata($file_path, $document);

            // Create or update DMS document
            $dms_result = $this->create_or_update_dms_document($document, $file_path, $metadata, $file_hash);

            if (!$dms_result) {
                $this->log('error', sprintf('Failed to create/update DMS document: %s', $document['title']));
                return false;
            }

            // Update database record
            $this->update_database_record($document, $metadata, $file_path, $file_hash, $dms_result);

            $this->log('info', sprintf('Successfully processed document: %s', $document['title']));

            return true;

        } catch (Exception $e) {
            $this->log('error', sprintf('Error processing document %s: %s', $document['title'], $e->getMessage()));
            $this->update_document_status($document['url'], 'error', $e->getMessage());
            return false;
        }
    }

    /**
     * Download a document from URL
     */
    private function download_document($url) {
        $this->log('info', sprintf('Downloading document: %s', $url));

        $response = $this->make_request($url);

        if (is_wp_error($response)) {
            $this->log('error', 'Download request failed: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);

        if (empty($body)) {
            $this->log('error', 'Empty response body for download');
            return false;
        }

        // Generate filename
        $filename = $this->generate_filename($url);
        $upload_dir = wp_upload_dir();
        $target_path = $upload_dir['path'] . '/' . $filename;

        // Ensure directory exists
        wp_mkdir_p(dirname($target_path));

        // Write file
        if (file_put_contents($target_path, $body) === false) {
            $this->log('error', 'Failed to write downloaded file');
            return false;
        }

        // Calculate hash
        $file_hash = md5_file($target_path);

        return array(
            'path' => $target_path,
            'hash' => $file_hash,
            'size' => filesize($target_path),
        );
    }

    /**
     * Make HTTP request with retry logic
     */
    private function make_request($url, $args = array()) {
        $default_args = array(
            'timeout' => self::REQUEST_TIMEOUT,
            'user-agent' => self::USER_AGENT,
            'redirection' => 5,
            'sslverify' => true,
        );

        $args = wp_parse_args($args, $default_args);

        $retries = 0;
        while ($retries < self::MAX_RETRIES) {
            $response = wp_remote_get($url, $args);

            if (!is_wp_error($response)) {
                $response_code = wp_remote_retrieve_response_code($response);
                if ($response_code >= 200 && $response_code < 300) {
                    return $response;
                }
            }

            $retries++;
            if ($retries < self::MAX_RETRIES) {
                $delay = pow(2, $retries); // Exponential backoff
                $this->log('warning', sprintf('Request failed, retrying in %d seconds (attempt %d/%d)', $delay, $retries, self::MAX_RETRIES));
                sleep($delay);
            }
        }

        return $response; // Return last response even if failed
    }

    /**
     * Generate filename from URL
     */
    private function generate_filename($url) {
        $parsed_url = parse_url($url);
        $path = $parsed_url['path'];
        $filename = basename($path);

        // If no extension, add .pdf
        if (!pathinfo($filename, PATHINFO_EXTENSION)) {
            $filename .= '.pdf';
        }

        // Sanitize filename
        $filename = sanitize_file_name($filename);

        // Add timestamp to avoid conflicts
        $timestamp = current_time('timestamp');
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $filename = sprintf('%s-%d.%s', $name, $timestamp, $ext);

        return 'swe3-' . $filename;
    }

    /**
     * Make relative URL absolute
     */
    private function make_absolute_url($url, $base_url) {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url; // Already absolute
        }

        return rtrim($base_url, '/') . '/' . ltrim($url, '/');
    }

    /**
     * Check if URL is a document URL
     */
    private function is_document_url($url) {
        $document_extensions = array('pdf', 'doc', 'docx', 'xls', 'xlsx');
        $path = parse_url($url, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, $document_extensions);
    }

    /**
     * Determine document type from title/URL
     */
    private function determine_document_type($title, $url) {
        $title_lower = strtolower($title);
        $url_lower = strtolower($url);

        if (strpos($title_lower, 'tävlingsbestämmelse') !== false || strpos($url_lower, 'tavlingsbestammelse') !== false) {
            return 'competition-regulations';
        }

        if (strpos($title_lower, 'spelregel') !== false || strpos($url_lower, 'spelregel') !== false) {
            return 'game-rules';
        }

        if (strpos($title_lower, 'domar') !== false) {
            return 'referee-guidelines';
        }

        if (strpos($title_lower, 'utveckling') !== false || strpos($title_lower, 'u-serie') !== false) {
            return 'development-series';
        }

        if (strpos($title_lower, 'säkerhet') !== false || strpos($title_lower, 'medicinsk') !== false) {
            return 'safety-medical';
        }

        return 'general';
    }

    /**
     * Extract document metadata
     */
    private function extract_document_metadata($file_path, $document) {
        // Basic metadata extraction
        $metadata = array(
            'title' => $document['title'],
            'type' => $document['type'],
            'version' => $this->extract_version_from_title($document['title']),
            'publication_date' => $this->extract_date_from_title($document['title']),
        );

        // TODO: Add PDF parsing for more detailed metadata extraction
        // This would require a PDF parsing library

        return $metadata;
    }

    /**
     * Extract version from title
     */
    private function extract_version_from_title($title) {
        // Look for year patterns (e.g., 2026, 2025)
        if (preg_match('/\b(20\d{2})\b/', $title, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract date from title
     */
    private function extract_date_from_title($title) {
        // Look for date patterns
        // This is a simplified implementation
        return null;
    }

    /**
     * Create or update DMS document
     */
    private function create_or_update_dms_document($document, $file_path, $metadata, $file_hash) {
        // This will be implemented in the DMS integration class
        $dms_integration = bkgt_swe3_scraper()->dms_integration;
        return $dms_integration->create_or_update_document($document, $file_path, $metadata, $file_hash);
    }

    /**
     * Get existing document from database
     */
    private function get_existing_document($url) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bkgt_swe3_documents';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE swe3_url = %s",
            $url
        ));
    }

    /**
     * Update document status
     */
    private function update_document_status($url, $status, $error_message = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bkgt_swe3_documents';

        $wpdb->update(
            $table_name,
            array(
                'status' => $status,
                'error_message' => $error_message,
                'last_checked' => current_time('mysql'),
            ),
            array('swe3_url' => $url),
            array('%s', '%s', '%s'),
            array('%s')
        );
    }

    /**
     * Update document last checked timestamp
     */
    private function update_document_last_checked($url) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bkgt_swe3_documents';

        $wpdb->update(
            $table_name,
            array('last_checked' => current_time('mysql')),
            array('swe3_url' => $url),
            array('%s'),
            array('%s')
        );
    }

    /**
     * Update database record
     */
    private function update_database_record($document, $metadata, $file_path, $file_hash, $dms_document_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bkgt_swe3_documents';

        $data = array(
            'swe3_id' => md5($document['url']),
            'title' => $metadata['title'],
            'document_type' => $metadata['type'],
            'swe3_url' => $document['url'],
            'local_path' => $file_path,
            'file_hash' => $file_hash,
            'version' => $metadata['version'],
            'publication_date' => $metadata['publication_date'],
            'dms_document_id' => $dms_document_id,
            'status' => 'active',
            'last_checked' => current_time('mysql'),
            'error_message' => null,
        );

        $existing_doc = $this->get_existing_document($document['url']);

        if ($existing_doc) {
            $wpdb->update(
                $table_name,
                $data,
                array('swe3_url' => $document['url']),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
                array('%s')
            );
        } else {
            $wpdb->insert(
                $table_name,
                $data,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
    }

    /**
     * Log message
     */
    private function log($level, $message) {
        $log_levels = array('debug', 'info', 'warning', 'error');

        if (!in_array($level, $log_levels)) {
            $level = 'info';
        }

        $current_level = get_option('bkgt_swe3_log_level', 'info');
        $current_level_index = array_search($current_level, $log_levels);
        $message_level_index = array_search($level, $log_levels);

        if ($message_level_index < $current_level_index) {
            return; // Don't log messages below current level
        }

        $log_message = sprintf(
            '[%s] [%s] %s',
            current_time('Y-m-d H:i:s'),
            strtoupper($level),
            $message
        );

        // Write to WordPress debug log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($log_message);
        }

        // TODO: Implement custom log file writing
    }
}