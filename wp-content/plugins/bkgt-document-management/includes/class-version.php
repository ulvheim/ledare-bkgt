<?php
/**
 * Document Version Class
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Document_Version {

    /**
     * Version ID
     */
    private $version_id;

    /**
     * Version data
     */
    private $data;

    /**
     * Constructor
     */
    public function __construct($version_id = null) {
        $this->version_id = $version_id;
        if ($version_id) {
            $this->load_data();
        }
    }

    /**
     * Load version data
     */
    private function load_data() {
        global $wpdb;
        $table = $wpdb->prefix . 'bkgt_document_versions';

        $this->data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $this->version_id
        ));
    }

    /**
     * Create new version
     */
    public static function create($document_id, $file_path, $file_name, $file_size, $mime_type, $user_id, $change_description = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'bkgt_document_versions';

        $result = $wpdb->insert(
            $table,
            array(
                'document_id' => $document_id,
                'file_path' => $file_path,
                'file_name' => $file_name,
                'file_size' => $file_size,
                'mime_type' => $mime_type,
                'uploaded_by' => $user_id,
                'change_description' => $change_description,
                'upload_date' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s')
        );

        if ($result) {
            $version_id = $wpdb->insert_id;
            return new self($version_id);
        }

        return new WP_Error('version_creation_failed', __('Misslyckades att skapa version.', 'bkgt-document-management'));
    }

    /**
     * Delete version
     */
    public static function delete($version_id) {
        $version = new self($version_id);
        if (!$version->data) {
            return new WP_Error('invalid_version', __('Ogiltig version.', 'bkgt-document-management'));
        }

        // Don't allow deletion of the only version
        $versions = self::get_document_versions($version->data->document_id);
        if (count($versions) <= 1) {
            return new WP_Error('cannot_delete_last_version', __('Kan inte radera sista versionen av dokumentet.', 'bkgt-document-management'));
        }

        // Delete file
        if (file_exists($version->data->file_path)) {
            unlink($version->data->file_path);
        }

        // Delete from database
        global $wpdb;
        $table = $wpdb->prefix . 'bkgt_document_versions';
        $result = $wpdb->delete($table, array('id' => $version_id), array('%d'));

        if ($result) {
            return true;
        }

        return new WP_Error('version_deletion_failed', __('Misslyckades att radera version.', 'bkgt-document-management'));
    }

    /**
     * Get version ID
     */
    public function get_id() {
        return $this->version_id;
    }

    /**
     * Get document ID
     */
    public function get_document_id() {
        if (!$this->data) {
            return null;
        }
        return $this->data->document_id;
    }

    /**
     * Get file path
     */
    public function get_file_path() {
        if (!$this->data) {
            return null;
        }
        return $this->data->file_path;
    }

    /**
     * Get file name
     */
    public function get_file_name() {
        if (!$this->data) {
            return null;
        }
        return $this->data->file_name;
    }

    /**
     * Get file size
     */
    public function get_file_size() {
        if (!$this->data) {
            return null;
        }
        return $this->data->file_size;
    }

    /**
     * Get MIME type
     */
    public function get_mime_type() {
        if (!$this->data) {
            return null;
        }
        return $this->data->mime_type;
    }

    /**
     * Get upload date
     */
    public function get_upload_date() {
        if (!$this->data) {
            return null;
        }
        return $this->data->upload_date;
    }

    /**
     * Get uploaded by user ID
     */
    public function get_uploaded_by() {
        if (!$this->data) {
            return null;
        }
        return $this->data->uploaded_by;
    }

    /**
     * Get uploaded by user name
     */
    public function get_uploaded_by_name() {
        $user_id = $this->get_uploaded_by();
        if (!$user_id) {
            return __('Okänd', 'bkgt-document-management');
        }

        $user = get_userdata($user_id);
        return $user ? $user->display_name : __('Okänd', 'bkgt-document-management');
    }

    /**
     * Get change description
     */
    public function get_change_description() {
        if (!$this->data) {
            return null;
        }
        return $this->data->change_description;
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
     * Get formatted upload date
     */
    public function get_formatted_upload_date($format = 'Y-m-d H:i') {
        $date = $this->get_upload_date();
        if (!$date) {
            return '';
        }

        return date_i18n($format, strtotime($date));
    }

    /**
     * Get all versions for a document
     */
    public static function get_document_versions($document_id, $limit = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'bkgt_document_versions';

        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE document_id = %d ORDER BY upload_date DESC",
            $document_id
        );

        if ($limit) {
            $query .= $wpdb->prepare(" LIMIT %d", $limit);
        }

        $versions = $wpdb->get_results($query);

        $version_objects = array();
        foreach ($versions as $version_data) {
            $version = new self();
            $version->data = $version_data;
            $version->version_id = $version_data->id;
            $version_objects[] = $version;
        }

        return $version_objects;
    }

    /**
     * Get latest version for a document
     */
    public static function get_latest_version($document_id) {
        $versions = self::get_document_versions($document_id, 1);
        return !empty($versions) ? $versions[0] : null;
    }

    /**
     * Restore version (make it current)
     */
    public function restore() {
        if (!$this->data) {
            return new WP_Error('invalid_version', __('Ogiltig version.', 'bkgt-document-management'));
        }

        $document_id = $this->get_document_id();

        // Update document meta to point to this version
        update_post_meta($document_id, '_bkgt_doc_file_path', $this->get_file_path());
        update_post_meta($document_id, '_bkgt_doc_file_name', $this->get_file_name());
        update_post_meta($document_id, '_bkgt_doc_file_size', $this->get_file_size());
        update_post_meta($document_id, '_bkgt_doc_mime_type', $this->get_mime_type());
        update_post_meta($document_id, '_bkgt_doc_upload_date', $this->get_upload_date());

        // Log the restoration
        BKGT_Document_Database::log_download($document_id, 'restore', get_current_user_id());

        return true;
    }

    /**
     * Download version
     */
    public function download() {
        if (!$this->data) {
            return new WP_Error('invalid_version', __('Ogiltig version.', 'bkgt-document-management'));
        }

        $file_path = $this->get_file_path();
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', __('Fil hittades inte.', 'bkgt-document-management'));
        }

        // Log the download
        BKGT_Document_Database::log_download($this->get_document_id(), 'download', get_current_user_id());

        // Set headers for download
        header('Content-Type: ' . $this->get_mime_type());
        header('Content-Disposition: attachment; filename="' . $this->get_file_name() . '"');
        header('Content-Length: ' . $this->get_file_size());
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Output file
        readfile($file_path);
        exit;
    }

    /**
     * Get version statistics
     */
    public static function get_statistics($document_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'bkgt_document_versions';

        $stats = array(
            'total_versions' => 0,
            'total_size' => 0,
            'oldest_version' => null,
            'newest_version' => null,
        );

        $where_clause = '';
        if ($document_id) {
            $where_clause = $wpdb->prepare('WHERE document_id = %d', $document_id);
        }

        // Total versions
        $stats['total_versions'] = $wpdb->get_var("SELECT COUNT(*) FROM $table $where_clause");

        // Total size
        $stats['total_size'] = $wpdb->get_var("SELECT SUM(file_size) FROM $table $where_clause");

        // Oldest version
        $stats['oldest_version'] = $wpdb->get_var("SELECT MIN(upload_date) FROM $table $where_clause");

        // Newest version
        $stats['newest_version'] = $wpdb->get_var("SELECT MAX(upload_date) FROM $table $where_clause");

        return $stats;
    }

    /**
     * Clean up old versions (keep only N most recent)
     */
    public static function cleanup_old_versions($document_id, $keep_versions = 5) {
        $versions = self::get_document_versions($document_id);

        if (count($versions) <= $keep_versions) {
            return true; // Nothing to clean up
        }

        // Keep the most recent versions, delete the rest
        $versions_to_delete = array_slice($versions, $keep_versions);

        foreach ($versions_to_delete as $version) {
            self::delete($version->get_id());
        }

        return true;
    }
}