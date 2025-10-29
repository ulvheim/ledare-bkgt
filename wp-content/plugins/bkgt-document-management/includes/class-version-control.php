<?php
/**
 * BKGT Document Management - Version Control System
 * Handles document versioning and change history
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Version Control System Class
 */
class BKGT_DM_Version_Control {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize the version control system
     */
    public function init() {
        // Create versions table on activation
        register_activation_hook('bkgt-document-management/bkgt-document-management.php', array($this, 'create_versions_table'));

        // Hook into document save to create versions
        add_action('bkgt_document_pre_save', array($this, 'create_version_on_save'), 10, 2);

        // Add AJAX handlers for version operations
        add_action('wp_ajax_bkgt_get_document_versions', array($this, 'ajax_get_document_versions'));
        add_action('wp_ajax_bkgt_restore_document_version', array($this, 'ajax_restore_document_version'));
        add_action('wp_ajax_bkgt_compare_versions', array($this, 'ajax_compare_versions'));
        add_action('wp_ajax_bkgt_delete_version', array($this, 'ajax_delete_version'));
    }

    /**
     * Create versions database table
     */
    public function create_versions_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_document_versions';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT(11) NOT NULL AUTO_INCREMENT,
            document_id INT(11) NOT NULL,
            version_number INT(11) NOT NULL,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            excerpt TEXT,
            category_id INT(11),
            tags TEXT,
            metadata LONGTEXT,
            file_path VARCHAR(500),
            file_size INT(11),
            mime_type VARCHAR(100),
            created_by INT(11) NOT NULL,
            created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            change_reason TEXT,
            is_current TINYINT(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY version_number (version_number),
            KEY created_date (created_date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create version when document is saved
     */
    public function create_version_on_save($document_data, $document_id = null) {
        global $wpdb;

        // Only create version if this is an update (not a new document)
        if (!$document_id) {
            return $document_data;
        }

        // Get current document data
        $current_document = $this->get_document_current_data($document_id);
        if (!$current_document) {
            return $document_data;
        }

        // Check if content has actually changed
        $content_changed = $current_document['content'] !== $document_data['content'];
        $title_changed = $current_document['title'] !== $document_data['title'];
        $metadata_changed = $this->has_metadata_changed($current_document, $document_data);

        // Only create version if something significant changed
        if (!$content_changed && !$title_changed && !$metadata_changed) {
            return $document_data;
        }

        // Get next version number
        $next_version = $this->get_next_version_number($document_id);

        // Prepare version data
        $version_data = array(
            'document_id' => $document_id,
            'version_number' => $next_version,
            'title' => $current_document['title'],
            'content' => $current_document['content'],
            'excerpt' => $current_document['excerpt'],
            'category_id' => $current_document['category_id'],
            'tags' => $current_document['tags'],
            'metadata' => json_encode($current_document['metadata']),
            'file_path' => $current_document['file_path'],
            'file_size' => $current_document['file_size'],
            'mime_type' => $current_document['mime_type'],
            'created_by' => get_current_user_id(),
            'change_reason' => $this->detect_change_reason($current_document, $document_data),
            'is_current' => 0
        );

        // Insert version
        $table_name = $wpdb->prefix . 'bkgt_document_versions';
        $wpdb->insert($table_name, $version_data);

        // Mark previous current version as not current
        $wpdb->update(
            $table_name,
            array('is_current' => 0),
            array('document_id' => $document_id, 'is_current' => 1),
            array('%d'),
            array('%d', '%d')
        );

        return $document_data;
    }

    /**
     * Get current document data
     */
    private function get_document_current_data($document_id) {
        global $wpdb;

        $documents_table = $wpdb->prefix . 'bkgt_documents';

        $document = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $documents_table WHERE id = %d",
                $document_id
            ),
            ARRAY_A
        );

        if (!$document) {
            return false;
        }

        // Decode metadata
        $document['metadata'] = json_decode($document['metadata'], true) ?: array();

        return $document;
    }

    /**
     * Get next version number for document
     */
    private function get_next_version_number($document_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_document_versions';

        $max_version = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MAX(version_number) FROM $table_name WHERE document_id = %d",
                $document_id
            )
        );

        return $max_version ? $max_version + 1 : 1;
    }

    /**
     * Detect what changed in the document
     */
    private function detect_change_reason($old_data, $new_data) {
        $changes = array();

        if ($old_data['title'] !== $new_data['title']) {
            $changes[] = 'titel';
        }

        if ($old_data['content'] !== $new_data['content']) {
            $changes[] = 'innehåll';
        }

        if ($old_data['category_id'] != $new_data['category_id']) {
            $changes[] = 'kategori';
        }

        if ($old_data['excerpt'] !== $new_data['excerpt']) {
            $changes[] = 'sammanfattning';
        }

        if ($this->has_metadata_changed($old_data, $new_data)) {
            $changes[] = 'metadata';
        }

        return !empty($changes) ? 'Ändrat: ' . implode(', ', $changes) : 'Okänd ändring';
    }

    /**
     * Check if metadata has changed
     */
    private function has_metadata_changed($old_data, $new_data) {
        $old_metadata = isset($old_data['metadata']) ? $old_data['metadata'] : array();
        $new_metadata = isset($new_data['metadata']) ? $new_data['metadata'] : array();

        return json_encode($old_metadata) !== json_encode($new_metadata);
    }

    /**
     * Get all versions for a document
     */
    public function get_document_versions($document_id, $limit = null) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_document_versions';

        $limit_clause = $limit ? $wpdb->prepare('LIMIT %d', $limit) : '';

        $versions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT v.*, u.display_name as author_name
                 FROM $table_name v
                 LEFT JOIN {$wpdb->users} u ON v.created_by = u.ID
                 WHERE v.document_id = %d
                 ORDER BY v.version_number DESC
                 $limit_clause",
                $document_id
            ),
            ARRAY_A
        );

        // Decode metadata for each version
        foreach ($versions as &$version) {
            $version['metadata'] = json_decode($version['metadata'], true) ?: array();
            $version['tags'] = $version['tags'] ? explode(',', $version['tags']) : array();
        }

        return $versions;
    }

    /**
     * Get specific version
     */
    public function get_version($version_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_document_versions';

        $version = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT v.*, u.display_name as author_name
                 FROM $table_name v
                 LEFT JOIN {$wpdb->users} u ON v.created_by = u.ID
                 WHERE v.id = %d",
                $version_id
            ),
            ARRAY_A
        );

        if (!$version) {
            return false;
        }

        // Decode metadata
        $version['metadata'] = json_decode($version['metadata'], true) ?: array();
        $version['tags'] = $version['tags'] ? explode(',', $version['tags']) : array();

        return $version;
    }

    /**
     * Restore document to specific version
     */
    public function restore_version($document_id, $version_id) {
        global $wpdb;

        // Get the version to restore
        $version = $this->get_version($version_id);
        if (!$version || $version['document_id'] != $document_id) {
            return new WP_Error('version_not_found', 'Version not found or does not belong to this document.');
        }

        // Update current document with version data
        $documents_table = $wpdb->prefix . 'bkgt_documents';

        $update_data = array(
            'title' => $version['title'],
            'content' => $version['content'],
            'excerpt' => $version['excerpt'],
            'category_id' => $version['category_id'],
            'tags' => $version['tags'],
            'metadata' => json_encode($version['metadata']),
            'file_path' => $version['file_path'],
            'file_size' => $version['file_size'],
            'mime_type' => $version['mime_type'],
            'modified_date' => current_time('mysql')
        );

        $result = $wpdb->update(
            $documents_table,
            $update_data,
            array('id' => $document_id),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('restore_failed', 'Failed to restore document version.');
        }

        // Create a new version with the restored content
        $this->create_version_on_save($update_data, $document_id);

        return true;
    }

    /**
     * Compare two versions
     */
    public function compare_versions($version_id_1, $version_id_2) {
        $version1 = $this->get_version($version_id_1);
        $version2 = $this->get_version($version_id_2);

        if (!$version1 || !$version2) {
            return new WP_Error('versions_not_found', 'One or both versions not found.');
        }

        if ($version1['document_id'] !== $version2['document_id']) {
            return new WP_Error('different_documents', 'Versions belong to different documents.');
        }

        return array(
            'document_id' => $version1['document_id'],
            'version1' => $version1,
            'version2' => $version2,
            'differences' => array(
                'title' => $this->compare_text($version1['title'], $version2['title']),
                'content' => $this->compare_text($version1['content'], $version2['content']),
                'metadata' => $this->compare_metadata($version1['metadata'], $version2['metadata'])
            )
        );
    }

    /**
     * Compare two text strings and return differences
     */
    private function compare_text($text1, $text2) {
        if ($text1 === $text2) {
            return array('changed' => false);
        }

        // Simple diff - in a full implementation, you'd use a proper diff library
        return array(
            'changed' => true,
            'old' => $text1,
            'new' => $text2,
            'diff' => $this->simple_diff($text1, $text2)
        );
    }

    /**
     * Simple text diff (basic implementation)
     */
    private function simple_diff($old_text, $new_text) {
        $old_lines = explode("\n", $old_text);
        $new_lines = explode("\n", $new_text);

        $diff = array();

        $max_lines = max(count($old_lines), count($new_lines));

        for ($i = 0; $i < $max_lines; $i++) {
            $old_line = isset($old_lines[$i]) ? $old_lines[$i] : '';
            $new_line = isset($new_lines[$i]) ? $new_lines[$i] : '';

            if ($old_line !== $new_line) {
                $diff[] = array(
                    'line' => $i + 1,
                    'old' => $old_line,
                    'new' => $new_line
                );
            }
        }

        return $diff;
    }

    /**
     * Compare metadata arrays
     */
    private function compare_metadata($meta1, $meta2) {
        $differences = array();

        $all_keys = array_unique(array_merge(array_keys($meta1), array_keys($meta2)));

        foreach ($all_keys as $key) {
            $value1 = isset($meta1[$key]) ? $meta1[$key] : null;
            $value2 = isset($meta2[$key]) ? $meta2[$key] : null;

            if ($value1 !== $value2) {
                $differences[$key] = array(
                    'old' => $value1,
                    'new' => $value2
                );
            }
        }

        return $differences;
    }

    /**
     * Delete a specific version
     */
    public function delete_version($version_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_document_versions';

        // Don't allow deleting the current version
        $is_current = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT is_current FROM $table_name WHERE id = %d",
                $version_id
            )
        );

        if ($is_current) {
            return new WP_Error('cannot_delete_current', 'Cannot delete the current version.');
        }

        $result = $wpdb->delete($table_name, array('id' => $version_id), array('%d'));

        return $result !== false;
    }

    /**
     * Get version statistics for a document
     */
    public function get_version_stats($document_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_document_versions';

        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COUNT(*) as total_versions,
                    MAX(created_date) as last_modified,
                    COUNT(DISTINCT created_by) as contributors
                 FROM $table_name
                 WHERE document_id = %d",
                $document_id
            ),
            ARRAY_A
        );

        return $stats ?: array(
            'total_versions' => 0,
            'last_modified' => null,
            'contributors' => 0
        );
    }

    /**
     * AJAX: Get document versions
     */
    public function ajax_get_document_versions() {
        // Verify nonce and capabilities
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_version_nonce') ||
            !current_user_can('bkgt_view_documents')) {
            wp_die('Security check failed');
        }

        $document_id = intval($_POST['document_id']);
        $versions = $this->get_document_versions($document_id);

        wp_send_json_success(array('versions' => $versions));
    }

    /**
     * AJAX: Restore document version
     */
    public function ajax_restore_document_version() {
        // Verify nonce and capabilities
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_version_nonce') ||
            !current_user_can('bkgt_manage_documents')) {
            wp_die('Security check failed');
        }

        $document_id = intval($_POST['document_id']);
        $version_id = intval($_POST['version_id']);

        $result = $this->restore_version($document_id, $version_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success();
        }
    }

    /**
     * AJAX: Compare versions
     */
    public function ajax_compare_versions() {
        // Verify nonce and capabilities
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_version_nonce') ||
            !current_user_can('bkgt_view_documents')) {
            wp_die('Security check failed');
        }

        $version_id_1 = intval($_POST['version_id_1']);
        $version_id_2 = intval($_POST['version_id_2']);

        $result = $this->compare_versions($version_id_1, $version_id_2);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * AJAX: Delete version
     */
    public function ajax_delete_version() {
        // Verify nonce and capabilities
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_version_nonce') ||
            !current_user_can('bkgt_manage_documents')) {
            wp_die('Security check failed');
        }

        $version_id = intval($_POST['version_id']);

        $result = $this->delete_version($version_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success();
        }
    }
}

// Initialize version control system
new BKGT_DM_Version_Control();