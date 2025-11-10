<?php
/**
 * BKGT Auto-Update API Class
 *
 * Handles automatic updates for BKGT Manager desktop application
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_API_Updates {

    /**
     * Database version
     */
    private $db_version = '1.0';

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->check_db_upgrade();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'upgrade_database'));
        register_activation_hook(BKGT_API_PLUGIN_DIR . 'bkgt-api.php', array($this, 'create_tables'));
    }

    /**
     * Check if database upgrade is needed
     */
    private function check_db_upgrade() {
        $current_version = get_option('bkgt_updates_db_version', '0');

        if (version_compare($current_version, $this->db_version, '<')) {
            $this->upgrade_database();
        }
    }

    /**
     * Upgrade database schema
     */
    public function upgrade_database() {
        $current_version = get_option('bkgt_updates_db_version', '0');

        if (version_compare($current_version, '1.0', '<')) {
            $this->create_update_tables();
            update_option('bkgt_updates_db_version', '1.0');
        }
    }

    /**
     * Create update-related database tables
     */
    public function create_tables() {
        $this->create_update_tables();
    }

    /**
     * Create the update tables
     */
    private function create_update_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Updates table
        $table_updates = $wpdb->prefix . 'bkgt_updates';
        $sql_updates = "CREATE TABLE $table_updates (
            id INT PRIMARY KEY AUTO_INCREMENT,
            version VARCHAR(20) NOT NULL,
            release_date DATETIME NOT NULL,
            changelog TEXT,
            critical BOOLEAN DEFAULT FALSE,
            minimum_version VARCHAR(20),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY version (version),
            KEY status (status),
            KEY release_date (release_date)
        ) $charset_collate;";

        // Update files table
        $table_files = $wpdb->prefix . 'bkgt_update_files';
        $sql_files = "CREATE TABLE $table_files (
            id INT PRIMARY KEY AUTO_INCREMENT,
            update_id INT NOT NULL,
            platform VARCHAR(20) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size BIGINT NOT NULL,
            sha256_hash VARCHAR(64) NOT NULL,
            download_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (update_id) REFERENCES $table_updates(id) ON DELETE CASCADE,
            UNIQUE KEY update_platform (update_id, platform),
            KEY platform (platform),
            KEY sha256_hash (sha256_hash(10))
        ) $charset_collate;";

        // Update status tracking table
        $table_status = $wpdb->prefix . 'bkgt_update_status';
        $sql_status = "CREATE TABLE $table_status (
            id INT PRIMARY KEY AUTO_INCREMENT,
            api_key_hash VARCHAR(64) NOT NULL,
            current_version VARCHAR(20) NOT NULL,
            target_version VARCHAR(20) NOT NULL,
            platform VARCHAR(20) NOT NULL,
            status ENUM('completed', 'failed', 'cancelled') NOT NULL,
            error_message TEXT,
            install_time_seconds INT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY api_key_hash (api_key_hash),
            KEY current_version (current_version),
            KEY target_version (target_version),
            KEY platform (platform),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Execute table creation
        dbDelta($sql_updates);
        dbDelta($sql_files);
        dbDelta($sql_status);

        // Log successful table creation
        error_log('BKGT Updates: Database tables created successfully');
    }

    /**
     * Get latest version information
     */
    public function get_latest_version($platform = null) {
        global $wpdb;

        $table_updates = $wpdb->prefix . 'bkgt_updates';
        $table_files = $wpdb->prefix . 'bkgt_update_files';

        $query = $wpdb->prepare("
            SELECT u.*, GROUP_CONCAT(
                CONCAT(
                    f.platform, ':', f.filename, ':', f.file_size, ':', f.sha256_hash, ':', f.download_count
                ) SEPARATOR ';'
            ) as platform_data
            FROM $table_updates u
            LEFT JOIN $table_files f ON u.id = f.update_id
            WHERE u.status = 'active'
            GROUP BY u.id
            ORDER BY u.release_date DESC
            LIMIT 1
        ");

        $result = $wpdb->get_row($query);

        if (!$result) {
            return null;
        }

        // Parse platform data
        $platforms = array();
        if ($result->platform_data) {
            $platform_entries = explode(';', $result->platform_data);
            foreach ($platform_entries as $entry) {
                list($platform_name, $filename, $size, $hash, $downloads) = explode(':', $entry);
                $platforms[$platform_name] = array(
                    'filename' => $filename,
                    'size' => (int)$size,
                    'hash' => $hash,
                    'downloads' => (int)$downloads
                );
            }
        }

        return array(
            'version' => $result->version,
            'release_date' => $result->release_date,
            'changelog' => $result->changelog,
            'critical' => (bool)$result->critical,
            'platforms' => $platforms,
            'minimum_version' => $result->minimum_version
        );
    }

    /**
     * Check version compatibility
     */
    public function check_compatibility($current_version) {
        $latest = $this->get_latest_version();

        if (!$latest) {
            return array(
                'compatible' => false,
                'reason' => 'No updates available'
            );
        }

        $compatible = version_compare($current_version, $latest['minimum_version'], '>=');

        return array(
            'compatible' => $compatible,
            'latest_compatible_version' => $latest['version'],
            'requires_update' => version_compare($current_version, $latest['version'], '<'),
            'reason' => $compatible ?
                "Version $current_version can update to {$latest['version']}" :
                "Version $current_version requires minimum {$latest['minimum_version']}"
        );
    }

    /**
     * Record update status
     */
    public function record_update_status($data) {
        global $wpdb;

        $table = $wpdb->prefix . 'bkgt_update_status';

        $insert_data = array(
            'api_key_hash' => hash('sha256', $data['api_key'] ?? ''),
            'current_version' => sanitize_text_field($data['current_version']),
            'target_version' => sanitize_text_field($data['target_version']),
            'platform' => sanitize_text_field($data['platform']),
            'status' => sanitize_text_field($data['status']),
            'error_message' => isset($data['error_message']) ? sanitize_textarea_field($data['error_message']) : null,
            'install_time_seconds' => isset($data['install_time_seconds']) ? (int)$data['install_time_seconds'] : null,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null
        );

        $result = $wpdb->insert($table, $insert_data);

        return $result !== false;
    }

    /**
     * Upload update package
     */
    public function upload_update_package($version, $platform, $file, $changelog = '', $critical = false, $minimum_version = null) {
        global $wpdb;

        // Validate inputs
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            return new WP_Error('invalid_version', 'Version must follow semantic versioning (x.y.z)');
        }

        $allowed_platforms = array('win32-x64', 'darwin-x64', 'darwin-arm64', 'linux-x64');
        if (!in_array($platform, $allowed_platforms)) {
            return new WP_Error('invalid_platform', 'Unsupported platform');
        }

        // Check if version exists
        $table_updates = $wpdb->prefix . 'bkgt_updates';
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_updates WHERE version = %s",
            $version
        ));

        $update_id = $existing ? $existing->id : null;

        // If version doesn't exist, create it
        if (!$existing) {
            $result = $wpdb->insert($table_updates, array(
                'version' => $version,
                'release_date' => current_time('mysql'),
                'changelog' => $changelog,
                'critical' => $critical,
                'minimum_version' => $minimum_version
            ));

            if ($result === false) {
                return new WP_Error('db_error', 'Failed to create update record');
            }

            $update_id = $wpdb->insert_id;
        }

        // Handle file upload
        $upload_result = $this->handle_file_upload($file, $version, $platform);
        if (is_wp_error($upload_result)) {
            return $upload_result;
        }

        // Insert file record
        $table_files = $wpdb->prefix . 'bkgt_update_files';
        $result = $wpdb->insert($table_files, array(
            'update_id' => $update_id,
            'platform' => $platform,
            'filename' => $upload_result['filename'],
            'file_path' => $upload_result['file_path'],
            'file_size' => $upload_result['file_size'],
            'sha256_hash' => $upload_result['hash']
        ));

        if ($result === false) {
            // Clean up uploaded file
            @unlink($upload_result['file_path']);
            return new WP_Error('db_error', 'Failed to save file record');
        }

        return array(
            'update_id' => $update_id,
            'version' => $version,
            'platform' => $platform,
            'file_hash' => $upload_result['hash'],
            'download_url' => rest_url("bkgt/v1/updates/download/$version/$platform")
        );
    }

    /**
     * Handle file upload
     */
    private function handle_file_upload($file, $version, $platform) {
        // Validate file
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return new WP_Error('invalid_file', 'No valid file uploaded');
        }

        // Check file size (max 500MB)
        $max_size = 500 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', 'File size exceeds maximum allowed size');
        }

        // Generate secure filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "BKGT-Manager-{$version}-{$platform}.{$extension}";

        // Create upload directory
        $upload_dir = wp_upload_dir();
        $updates_dir = $upload_dir['basedir'] . '/bkgt-updates';

        if (!wp_mkdir_p($updates_dir)) {
            return new WP_Error('upload_error', 'Failed to create upload directory');
        }

        $file_path = $updates_dir . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return new WP_Error('upload_error', 'Failed to save uploaded file');
        }

        // Calculate SHA256 hash
        $hash = hash_file('sha256', $file_path);

        return array(
            'filename' => $filename,
            'file_path' => $file_path,
            'file_size' => filesize($file_path),
            'hash' => $hash
        );
    }

    /**
     * Get update file for download
     */
    public function get_update_file($version, $platform) {
        global $wpdb;

        $table_updates = $wpdb->prefix . 'bkgt_updates';
        $table_files = $wpdb->prefix . 'bkgt_update_files';

        $file = $wpdb->get_row($wpdb->prepare("
            SELECT f.file_path, f.filename, f.file_size, f.sha256_hash, u.status
            FROM $table_files f
            JOIN $table_updates u ON f.update_id = u.id
            WHERE u.version = %s AND f.platform = %s AND u.status = 'active'
        ", $version, $platform));

        if (!$file) {
            return null;
        }

        // Increment download count
        $wpdb->query($wpdb->prepare("
            UPDATE $table_files
            SET download_count = download_count + 1
            WHERE file_path = %s
        ", $file->file_path));

        return array(
            'path' => $file->file_path,
            'filename' => $file->filename,
            'size' => $file->file_size,
            'hash' => $file->sha256_hash
        );
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // Handle comma-separated IPs (like X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get admin update list
     */
    public function get_admin_updates($page = 1, $per_page = 20) {
        global $wpdb;

        $table_updates = $wpdb->prefix . 'bkgt_updates';
        $table_files = $wpdb->prefix . 'bkgt_update_files';

        $offset = ($page - 1) * $per_page;

        $query = $wpdb->prepare("
            SELECT u.*,
                   COUNT(f.id) as platform_count,
                   GROUP_CONCAT(f.platform) as platforms,
                   SUM(f.download_count) as total_downloads
            FROM $table_updates u
            LEFT JOIN $table_files f ON u.id = f.update_id
            GROUP BY u.id
            ORDER BY u.release_date DESC
            LIMIT %d OFFSET %d
        ", $per_page, $offset);

        $updates = $wpdb->get_results($query);

        // Get total count
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_updates");

        return array(
            'updates' => array_map(function($update) {
                return array(
                    'id' => (int)$update->id,
                    'version' => $update->version,
                    'release_date' => $update->release_date,
                    'critical' => (bool)$update->critical,
                    'platforms' => $update->platforms ? explode(',', $update->platforms) : array(),
                    'download_count' => (int)$update->total_downloads,
                    'status' => $update->status
                );
            }, $updates),
            'pagination' => array(
                'page' => (int)$page,
                'per_page' => (int)$per_page,
                'total' => (int)$total,
                'total_pages' => ceil($total / $per_page)
            )
        );
    }

    /**
     * Deactivate update version
     */
    public function deactivate_update($version) {
        global $wpdb;

        $table = $wpdb->prefix . 'bkgt_updates';

        $result = $wpdb->update(
            $table,
            array('status' => 'inactive'),
            array('version' => $version),
            array('%s'),
            array('%s')
        );

        return $result !== false;
    }
}