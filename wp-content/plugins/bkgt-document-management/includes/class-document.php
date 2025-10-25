<?php
/**
 * Document Class
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Document {

    /**
     * Document ID
     */
    private $document_id;

    /**
     * Document data
     */
    private $data;

    /**
     * Constructor
     */
    public function __construct($document_id = null) {
        $this->document_id = $document_id;
        if ($document_id) {
            $this->load_data();
        }
    }

    /**
     * Load document data
     */
    private function load_data() {
        $this->data = get_post($this->document_id);
    }

    /**
     * Create new document
     */
    public static function create($data) {
        $defaults = array(
            'post_title' => '',
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'bkgt_document',
            'post_author' => get_current_user_id(),
        );

        $document_data = wp_parse_args($data, $defaults);
        $document_id = wp_insert_post($document_data);

        if (!is_wp_error($document_id)) {
            // Initialize document object
            $document = new self($document_id);

            // Set default access (creator has full access)
            BKGT_Document_Database::add_document_access($document_id, array(
                'user_id' => get_current_user_id(),
                'access_type' => 'manage',
            ));

            return $document;
        }

        return $document_id; // Return WP_Error
    }

    /**
     * Update document
     */
    public function update($data) {
        if (!$this->document_id) {
            return new WP_Error('invalid_document', __('Ogiltigt dokument-ID.', 'bkgt-document-management'));
        }

        $update_data = array('ID' => $this->document_id);
        $allowed_fields = array('post_title', 'post_content', 'post_status');

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }

        $result = wp_update_post($update_data);

        if ($result) {
            $this->load_data();
            return true;
        }

        return new WP_Error('update_failed', __('Misslyckades att uppdatera dokument.', 'bkgt-document-management'));
    }

    /**
     * Delete document
     */
    public static function delete($document_id) {
        if (!current_user_can('delete_document', $document_id)) {
            return new WP_Error('insufficient_permissions', __('Otillräckliga behörigheter.', 'bkgt-document-management'));
        }

        // Delete file
        $document = new self($document_id);
        $file_path = $document->get_file_path();
        if ($file_path && file_exists($file_path)) {
            unlink($file_path);
        }

        // Delete all versions
        self::delete_all_versions($document_id);

        // Delete access rules
        self::delete_access_rules($document_id);

        // Delete post
        $result = wp_delete_post($document_id, true);

        if ($result) {
            return true;
        }

        return new WP_Error('delete_failed', __('Misslyckades att radera dokument.', 'bkgt-document-management'));
    }

    /**
     * Handle file upload
     */
    public static function handle_file_upload($document_id, $file) {
        $upload_dir = wp_upload_dir();
        $bkgt_upload_dir = $upload_dir['basedir'] . '/bkgt-documents';

        // Create subdirectory for this document
        $document_dir = $bkgt_upload_dir . '/' . $document_id;
        if (!file_exists($document_dir)) {
            wp_mkdir_p($document_dir);
        }

        // Generate unique filename
        $file_info = wp_check_filetype($file['name']);
        $filename = sanitize_file_name($file['name']);
        $unique_filename = wp_unique_filename($document_dir, $filename);
        $file_path = $document_dir . '/' . $unique_filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return new WP_Error('upload_failed', __('Misslyckades att flytta uppladdad fil.', 'bkgt-document-management'));
        }

        // Store file information
        update_post_meta($document_id, '_bkgt_doc_file_path', $file_path);
        update_post_meta($document_id, '_bkgt_doc_file_name', $unique_filename);
        update_post_meta($document_id, '_bkgt_doc_file_size', $file['size']);
        update_post_meta($document_id, '_bkgt_doc_mime_type', $file_info['type']);
        update_post_meta($document_id, '_bkgt_doc_upload_date', current_time('mysql'));

        // Add to versions table
        BKGT_Document_Database::add_document_version(
            $document_id,
            $file_path,
            $unique_filename,
            $file['size'],
            $file_info['type'],
            get_current_user_id(),
            __('Initial uppladdning', 'bkgt-document-management')
        );

        return true;
    }

    /**
     * Get file path
     */
    public function get_file_path() {
        if (!$this->document_id) {
            return false;
        }
        return get_post_meta($this->document_id, '_bkgt_doc_file_path', true);
    }

    /**
     * Get file name
     */
    public function get_file_name() {
        if (!$this->document_id) {
            return false;
        }
        return get_post_meta($this->document_id, '_bkgt_doc_file_name', true);
    }

    /**
     * Get file size
     */
    public function get_file_size() {
        if (!$this->document_id) {
            return false;
        }
        return get_post_meta($this->document_id, '_bkgt_doc_file_size', true);
    }

    /**
     * Get MIME type
     */
    public function get_mime_type() {
        if (!$this->document_id) {
            return false;
        }
        return get_post_meta($this->document_id, '_bkgt_doc_mime_type', true);
    }

    /**
     * Get upload date
     */
    public function get_upload_date() {
        if (!$this->document_id) {
            return false;
        }
        return get_post_meta($this->document_id, '_bkgt_doc_upload_date', true);
    }

    /**
     * Get formatted file size
     */
    public function get_formatted_file_size() {
        $bytes = $this->get_file_size();
        if (!$bytes) {
            return '0 B';
        }

        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get document versions
     */
    public function get_versions($limit = null) {
        if (!$this->document_id) {
            return array();
        }
        return BKGT_Document_Database::get_document_versions($this->document_id, $limit);
    }

    /**
     * Add new version
     */
    public function add_version($file, $change_description = '') {
        if (!$this->document_id) {
            return new WP_Error('invalid_document', __('Ogiltigt dokument.', 'bkgt-document-management'));
        }

        return self::handle_file_upload($this->document_id, $file);
    }

    /**
     * Get access rules
     */
    public function get_access_rules() {
        if (!$this->document_id) {
            return array();
        }
        return BKGT_Document_Database::get_document_access($this->document_id);
    }

    /**
     * Add access rule
     */
    public function add_access_rule($access_data) {
        if (!$this->document_id) {
            return new WP_Error('invalid_document', __('Ogiltigt dokument.', 'bkgt-document-management'));
        }

        return BKGT_Document_Database::add_document_access($this->document_id, $access_data);
    }

    /**
     * Remove access rule
     */
    public function remove_access_rule($access_id) {
        return BKGT_Document_Database::remove_document_access($access_id);
    }

    /**
     * Log download
     */
    public static function log_download($document_id) {
        return BKGT_Document_Database::log_download($document_id);
    }

    /**
     * Get download count
     */
    public function get_download_count($days = 30) {
        if (!$this->document_id) {
            return 0;
        }
        return BKGT_Document_Database::get_download_stats($this->document_id, $days);
    }

    /**
     * Delete all versions
     */
    private static function delete_all_versions($document_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bkgt_document_versions';

        $versions = $wpdb->get_results($wpdb->prepare(
            "SELECT file_path FROM $table WHERE document_id = %d",
            $document_id
        ));

        // Delete files
        foreach ($versions as $version) {
            if (file_exists($version->file_path)) {
                unlink($version->file_path);
            }
        }

        // Delete from database
        $wpdb->delete($table, array('document_id' => $document_id), array('%d'));
    }

    /**
     * Delete access rules
     */
    private static function delete_access_rules($document_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bkgt_document_access';
        $wpdb->delete($table, array('document_id' => $document_id), array('%d'));
    }

    /**
     * Get documents by category
     */
    public static function get_by_category($category_id, $args = array()) {
        $defaults = array(
            'post_type' => 'bkgt_document',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'bkgt_doc_category',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ),
            ),
        );

        $query_args = wp_parse_args($args, $defaults);
        $query = new WP_Query($query_args);

        return $query->posts;
    }

    /**
     * Search documents
     */
    public static function search($search_term, $args = array()) {
        $defaults = array(
            'post_type' => 'bkgt_document',
            'posts_per_page' => -1,
            's' => $search_term,
        );

        $query_args = wp_parse_args($args, $defaults);
        $query = new WP_Query($query_args);

        return $query->posts;
    }

    /**
     * Get recent documents
     */
    public static function get_recent($limit = 10) {
        $args = array(
            'post_type' => 'bkgt_document',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $query = new WP_Query($args);
        return $query->posts;
    }

    /**
     * Get document statistics
     */
    public static function get_statistics() {
        $stats = array(
            'total_documents' => 0,
            'total_size' => 0,
            'total_downloads' => 0,
            'recent_uploads' => 0,
        );

        // Total documents
        $stats['total_documents'] = wp_count_posts('bkgt_document')->publish;

        // Total size
        global $wpdb;
        $stats['total_size'] = $wpdb->get_var(
            "SELECT SUM(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = '_bkgt_doc_file_size'"
        );

        // Total downloads (last 30 days)
        $stats['total_downloads'] = BKGT_Document_Database::get_download_stats(null, 30);

        // Recent uploads (last 7 days)
        $week_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
        $stats['recent_uploads'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bkgt_document' AND post_date >= %s",
            $week_ago
        ));

        return $stats;
    }
}