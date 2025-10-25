<?php
/**
 * Document Management Database
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Document_Database {

    /**
     * Constructor
     */
    public function __construct() {
        // Hook into WordPress database upgrade
        add_action('wp_upgrade', array($this, 'upgrade_database'));
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Document versions table
        $table_versions = $wpdb->prefix . 'bkgt_document_versions';
        $sql_versions = "CREATE TABLE $table_versions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            document_id bigint(20) NOT NULL,
            version_number int(11) NOT NULL DEFAULT 1,
            file_path varchar(500) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_size bigint(20) NOT NULL,
            mime_type varchar(100) NOT NULL,
            uploaded_by bigint(20) NOT NULL,
            uploaded_at datetime NOT NULL,
            change_description text,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY version_number (version_number)
        ) $charset_collate;";

        // Document access table
        $table_access = $wpdb->prefix . 'bkgt_document_access';
        $sql_access = "CREATE TABLE $table_access (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            document_id bigint(20) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            role varchar(50) DEFAULT NULL,
            team_id bigint(20) DEFAULT NULL,
            access_type varchar(20) NOT NULL DEFAULT 'view',
            granted_by bigint(20) NOT NULL,
            granted_at datetime NOT NULL,
            expires_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY user_id (user_id),
            KEY role (role),
            KEY team_id (team_id)
        ) $charset_collate;";

        // Document downloads log
        $table_downloads = $wpdb->prefix . 'bkgt_document_downloads';
        $sql_downloads = "CREATE TABLE $table_downloads (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            document_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            downloaded_at datetime NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY user_id (user_id),
            KEY downloaded_at (downloaded_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_versions);
        dbDelta($sql_access);
        dbDelta($sql_downloads);

        update_option('bkgt_doc_db_version', '1.0.0');
    }

    /**
     * Upgrade database
     */
    public function upgrade_database() {
        $current_version = get_option('bkgt_doc_db_version', '1.0.0');

        if (version_compare($current_version, '1.0.0', '<')) {
            $this->create_tables();
        }
    }

    /**
     * Get document versions
     */
    public static function get_document_versions($document_id, $limit = null) {
        global $wpdb;

        $table = $wpdb->prefix . 'bkgt_document_versions';
        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE document_id = %d ORDER BY version_number DESC",
            $document_id
        );

        if ($limit) {
            $query .= $wpdb->prepare(" LIMIT %d", $limit);
        }

        return $wpdb->get_results($query);
    }

    /**
     * Add document version
     */
    public static function add_document_version($document_id, $file_path, $file_name, $file_size, $mime_type, $uploaded_by, $change_description = '') {
        global $wpdb;

        $table = $wpdb->prefix . 'bkgt_document_versions';

        // Get next version number
        $latest_version = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(version_number) FROM $table WHERE document_id = %d",
            $document_id
        ));

        $version_number = $latest_version ? $latest_version + 1 : 1;

        $result = $wpdb->insert(
            $table,
            array(
                'document_id' => $document_id,
                'version_number' => $version_number,
                'file_path' => $file_path,
                'file_name' => $file_name,
                'file_size' => $file_size,
                'mime_type' => $mime_type,
                'uploaded_by' => $uploaded_by,
                'uploaded_at' => current_time('mysql'),
                'change_description' => $change_description,
            ),
            array('%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s')
        );

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get document access rules
     */
    public static function get_document_access($document_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'bkgt_document_access';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE document_id = %d ORDER BY granted_at DESC",
            $document_id
        ));
    }

    /**
     * Add document access rule
     */
    public static function add_document_access($document_id, $access_data) {
        global $wpdb;

        $table = $wpdb->prefix . 'bkgt_document_access';

        $defaults = array(
            'user_id' => null,
            'role' => null,
            'team_id' => null,
            'access_type' => 'view',
            'granted_by' => get_current_user_id(),
            'granted_at' => current_time('mysql'),
            'expires_at' => null,
        );

        $data = wp_parse_args($access_data, $defaults);

        return $wpdb->insert(
            $table,
            array(
                'document_id' => $document_id,
                'user_id' => $data['user_id'],
                'role' => $data['role'],
                'team_id' => $data['team_id'],
                'access_type' => $data['access_type'],
                'granted_by' => $data['granted_by'],
                'granted_at' => $data['granted_at'],
                'expires_at' => $data['expires_at'],
            ),
            array('%d', '%d', '%s', '%d', '%s', '%d', '%s', '%s')
        );
    }

    /**
     * Remove document access rule
     */
    public static function remove_document_access($access_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'bkgt_document_access';
        return $wpdb->delete($table, array('id' => $access_id), array('%d'));
    }

    /**
     * Log document download
     */
    public static function log_download($document_id, $user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $table = $wpdb->prefix . 'bkgt_document_downloads';

        return $wpdb->insert(
            $table,
            array(
                'document_id' => $document_id,
                'user_id' => $user_id,
                'downloaded_at' => current_time('mysql'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }

    /**
     * Get download statistics
     */
    public static function get_download_stats($document_id = null, $days = 30) {
        global $wpdb;

        $table = $wpdb->prefix . 'bkgt_document_downloads';
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $where = "downloaded_at >= %s";
        $params = array($date_limit);

        if ($document_id) {
            $where .= " AND document_id = %d";
            $params[] = $document_id;
        }

        $query = $wpdb->prepare("SELECT COUNT(*) as total_downloads FROM $table WHERE $where", $params);

        return $wpdb->get_var($query);
    }

    /**
     * Clean up expired access rules
     */
    public static function cleanup_expired_access() {
        global $wpdb;

        $table = $wpdb->prefix . 'bkgt_document_access';
        $current_time = current_time('mysql');

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE expires_at IS NOT NULL AND expires_at < %s",
            $current_time
        ));
    }

    /**
     * Get user accessible documents
     */
    public static function get_user_accessible_documents($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return array();
        }

        global $wpdb;
        $access_table = $wpdb->prefix . 'bkgt_document_access';

        // Build complex query for user access
        $conditions = array();

        // Direct user access
        $conditions[] = $wpdb->prepare("user_id = %d", $user_id);

        // Role-based access
        foreach ($user->roles as $role) {
            $conditions[] = $wpdb->prepare("role = %s", $role);
        }

        // Team-based access (if user management plugin is active)
        if (function_exists('bkgt_get_user_team')) {
            $team_id = bkgt_get_user_team($user_id);
            if ($team_id) {
                $conditions[] = $wpdb->prepare("team_id = %d", $team_id);
            }
        }

        if (empty($conditions)) {
            return array();
        }

        $where_clause = implode(' OR ', $conditions);

        $accessible_docs = $wpdb->get_col(
            "SELECT DISTINCT document_id FROM $access_table WHERE ($where_clause) AND (expires_at IS NULL OR expires_at > NOW())"
        );

        return array_map('intval', $accessible_docs);
    }
}