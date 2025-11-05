<?php
/**
 * BKGT API Notifications Class
 *
 * Handles notifications for API events, security alerts, and system monitoring
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_API_Notifications {

    /**
     * Notification types
     */
    const NOTIFICATION_TYPES = array(
        'security_alert' => 'Security Alert',
        'api_error' => 'API Error',
        'rate_limit_exceeded' => 'Rate Limit Exceeded',
        'high_error_rate' => 'High Error Rate',
        'system_maintenance' => 'System Maintenance',
        'api_key_created' => 'API Key Created',
        'api_key_revoked' => 'API Key Revoked',
    );

    /**
     * Notification channels
     */
    const NOTIFICATION_CHANNELS = array(
        'email' => 'Email',
        'admin_dashboard' => 'Admin Dashboard',
        'log' => 'Log File',
    );

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialize notifications
     */
    public function init() {
        // Hook into various API events
        add_action('bkgt_api_security_event', array($this, 'handle_security_event'), 10, 2);
        add_action('bkgt_api_error', array($this, 'handle_api_error'), 10, 2);
        add_action('bkgt_api_rate_limit_exceeded', array($this, 'handle_rate_limit_exceeded'), 10, 1);
        add_action('bkgt_api_key_created', array($this, 'handle_api_key_created'), 10, 2);
        add_action('bkgt_api_key_revoked', array($this, 'handle_api_key_revoked'), 10, 2);

        // Schedule monitoring tasks
        if (!wp_next_scheduled('bkgt_api_monitoring_check')) {
            wp_schedule_event(time(), 'hourly', 'bkgt_api_monitoring_check');
        }

        add_action('bkgt_api_monitoring_check', array($this, 'perform_monitoring_checks'));

        // Clean up old notifications
        if (!wp_next_scheduled('bkgt_cleanup_notifications')) {
            wp_schedule_event(time(), 'daily', 'bkgt_cleanup_notifications');
        }

        add_action('bkgt_cleanup_notifications', array($this, 'cleanup_old_notifications'));
    }

    /**
     * Handle security events
     */
    public function handle_security_event($event_type, $event_data) {
        $severity = $event_data['severity'] ?? 'medium';

        // Always log security events
        $this->log_notification('security_alert', array(
            'event_type' => $event_type,
            'severity' => $severity,
            'details' => $event_data,
        ));

        // Send notifications based on severity
        if (in_array($severity, array('high', 'critical'))) {
            $this->send_notification('security_alert', array(
                'title' => sprintf(__('Critical Security Alert: %s', 'bkgt-api'), $event_type),
                'message' => $this->format_security_alert_message($event_type, $event_data),
                'severity' => $severity,
                'channels' => array('email', 'admin_dashboard'),
            ));
        }
    }

    /**
     * Handle API errors
     */
    public function handle_api_error($error_code, $error_data) {
        // Log all API errors
        $this->log_notification('api_error', array(
            'error_code' => $error_code,
            'details' => $error_data,
        ));

        // Check for error rate spikes
        $this->check_error_rate_threshold();
    }

    /**
     * Handle rate limit exceeded
     */
    public function handle_rate_limit_exceeded($request_data) {
        $this->log_notification('rate_limit_exceeded', $request_data);

        // Send notification if this is a frequent occurrence
        $recent_rate_limits = $this->get_recent_notifications('rate_limit_exceeded', 3600); // Last hour

        if (count($recent_rate_limits) >= 10) { // More than 10 rate limit events in an hour
            $this->send_notification('rate_limit_exceeded', array(
                'title' => __('High Rate of Rate Limit Violations', 'bkgt-api'),
                'message' => sprintf(
                    __('Detected %d rate limit violations in the last hour. This may indicate an attack or misconfigured client.', 'bkgt-api'),
                    count($recent_rate_limits)
                ),
                'channels' => array('email'),
            ));
        }
    }

    /**
     * Handle API key creation
     */
    public function handle_api_key_created($user_id, $key_data) {
        $user = get_user_by('ID', $user_id);

        $this->send_notification('api_key_created', array(
            'title' => __('New API Key Created', 'bkgt-api'),
            'message' => sprintf(
                __('User %s (%s) has created a new API key: %s', 'bkgt-api'),
                $user->display_name,
                $user->user_email,
                $key_data['name']
            ),
            'channels' => array('admin_dashboard'),
        ));
    }

    /**
     * Handle API key revocation
     */
    public function handle_api_key_revoked($user_id, $key_data) {
        $user = get_user_by('ID', $user_id);

        $this->send_notification('api_key_revoked', array(
            'title' => __('API Key Revoked', 'bkgt-api'),
            'message' => sprintf(
                __('User %s (%s) has revoked an API key: %s', 'bkgt-api'),
                $user->display_name,
                $user->user_email,
                $key_data['name']
            ),
            'channels' => array('admin_dashboard'),
        ));
    }

    /**
     * Perform monitoring checks
     */
    public function perform_monitoring_checks() {
        // Check API error rate
        $this->check_error_rate_threshold();

        // Check for unusual activity patterns
        $this->check_unusual_activity();

        // Check API key usage
        $this->check_api_key_usage();

        // Check system health
        $this->check_system_health();
    }

    /**
     * Check API error rate threshold
     */
    private function check_error_rate_threshold() {
        global $wpdb;

        $hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $total_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_api_logs WHERE created_at >= %s",
            $hour_ago
        ));

        if ($total_requests < 10) {
            return; // Not enough data
        }

        $error_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_api_logs WHERE created_at >= %s AND response_code >= 400",
            $hour_ago
        ));

        $error_rate = ($error_requests / $total_requests) * 100;

        if ($error_rate > 20) { // More than 20% error rate
            $this->send_notification('high_error_rate', array(
                'title' => __('High API Error Rate Detected', 'bkgt-api'),
                'message' => sprintf(
                    __('API error rate is %.1f%% over the last hour (%d errors out of %d requests). This may indicate a problem with the API or client applications.', 'bkgt-api'),
                    $error_rate,
                    $error_requests,
                    $total_requests
                ),
                'channels' => array('email', 'admin_dashboard'),
            ));
        }
    }

    /**
     * Check for unusual activity patterns
     */
    private function check_unusual_activity() {
        global $wpdb;

        // Check for IPs with high request volumes
        $high_volume_ips = $wpdb->get_results($wpdb->prepare(
            "SELECT ip_address, COUNT(*) as request_count
             FROM {$wpdb->prefix}bkgt_api_logs
             WHERE created_at >= %s
             GROUP BY ip_address
             HAVING request_count > 1000
             ORDER BY request_count DESC
             LIMIT 5",
            date('Y-m-d H:i:s', strtotime('-1 hour'))
        ));

        if (!empty($high_volume_ips)) {
            $message = __('High request volumes detected from the following IPs:' . "\n\n", 'bkgt-api');
            foreach ($high_volume_ips as $ip_data) {
                $message .= sprintf("%s: %d requests\n", $ip_data->ip_address, $ip_data->request_count);
            }

            $this->send_notification('unusual_activity', array(
                'title' => __('Unusual API Activity Detected', 'bkgt-api'),
                'message' => $message,
                'channels' => array('email'),
            ));
        }
    }

    /**
     * Check API key usage patterns
     */
    private function check_api_key_usage() {
        global $wpdb;

        // Check for API keys with no recent usage
        $unused_keys = $wpdb->get_results($wpdb->prepare(
            "SELECT k.id, k.name, k.created_at, u.display_name as created_by
             FROM {$wpdb->prefix}bkgt_api_keys k
             LEFT JOIN {$wpdb->users} u ON k.created_by = u.ID
             WHERE k.is_active = 1
             AND k.last_used IS NULL
             AND k.created_at < %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));

        if (!empty($unused_keys)) {
            $message = __('The following API keys have not been used in the last 30 days:' . "\n\n", 'bkgt-api');
            foreach ($unused_keys as $key) {
                $message .= sprintf("- %s (created by %s on %s)\n", $key->name, $key->created_by, $key->created_at);
            }

            $this->send_notification('unused_api_keys', array(
                'title' => __('Unused API Keys Detected', 'bkgt-api'),
                'message' => $message,
                'channels' => array('admin_dashboard'),
            ));
        }
    }

    /**
     * Check system health
     */
    private function check_system_health() {
        // Check database connectivity
        global $wpdb;
        $db_healthy = true;

        try {
            $wpdb->check_connection();
        } catch (Exception $e) {
            $db_healthy = false;
        }

        if (!$db_healthy) {
            $this->send_notification('system_health', array(
                'title' => __('Database Connection Issue', 'bkgt-api'),
                'message' => __('Unable to connect to the database. API functionality may be affected.', 'bkgt-api'),
                'channels' => array('email', 'admin_dashboard'),
            ));
        }

        // Check disk space (if available)
        if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
            $free_space = disk_free_space(__DIR__);
            $total_space = disk_total_space(__DIR__);

            if ($total_space > 0) {
                $free_percentage = ($free_space / $total_space) * 100;

                if ($free_percentage < 10) { // Less than 10% free space
                    $this->send_notification('system_health', array(
                        'title' => __('Low Disk Space Warning', 'bkgt-api'),
                        'message' => sprintf(
                            __('Disk space is running low: %.1f%% remaining (%s free out of %s total).', 'bkgt-api'),
                            $free_percentage,
                            size_format($free_space),
                            size_format($total_space)
                        ),
                        'channels' => array('email', 'admin_dashboard'),
                    ));
                }
            }
        }
    }

    /**
     * Send notification
     */
    public function send_notification($type, $notification_data) {
        $channels = $notification_data['channels'] ?? array('log');

        foreach ($channels as $channel) {
            switch ($channel) {
                case 'email':
                    $this->send_email_notification($type, $notification_data);
                    break;
                case 'admin_dashboard':
                    $this->send_dashboard_notification($type, $notification_data);
                    break;
                case 'log':
                default:
                    $this->log_notification($type, $notification_data);
                    break;
            }
        }
    }

    /**
     * Send email notification
     */
    private function send_email_notification($type, $notification_data) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');

        $subject = sprintf('[%s] %s', $site_name, $notification_data['title']);
        $message = $this->format_email_message($type, $notification_data);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . $admin_email . '>',
        );

        wp_mail($admin_email, $subject, $message, $headers);
    }

    /**
     * Send dashboard notification
     */
    private function send_dashboard_notification($type, $notification_data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_notifications';

        // Create notifications table if it doesn't exist
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                id int(11) NOT NULL AUTO_INCREMENT,
                type varchar(50) NOT NULL,
                title varchar(255) NOT NULL,
                message text NOT NULL,
                severity enum('low','medium','high','critical') DEFAULT 'medium',
                is_read tinyint(1) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY type (type),
                KEY severity (severity),
                KEY is_read (is_read),
                KEY created_at (created_at)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        $wpdb->insert(
            $table_name,
            array(
                'type' => $type,
                'title' => $notification_data['title'],
                'message' => $notification_data['message'],
                'severity' => $notification_data['severity'] ?? 'medium',
                'is_read' => 0,
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s')
        );
    }

    /**
     * Log notification
     */
    private function log_notification($type, $data) {
        $log_message = sprintf(
            '[%s] %s: %s',
            current_time('Y-m-d H:i:s'),
            $type,
            json_encode($data)
        );

        error_log($log_message . "\n", 3, WP_CONTENT_DIR . '/bkgt-api-notifications.log');
    }

    /**
     * Format security alert message
     */
    private function format_security_alert_message($event_type, $event_data) {
        $message = sprintf(__('Security event detected: %s' . "\n\n", 'bkgt-api'), $event_type);

        if (isset($event_data['ip'])) {
            $message .= sprintf(__('IP Address: %s' . "\n", 'bkgt-api'), $event_data['ip']);
        }

        if (isset($event_data['user_id'])) {
            $user = get_user_by('ID', $event_data['user_id']);
            if ($user) {
                $message .= sprintf(__('User: %s (%s)' . "\n", 'bkgt-api'), $user->display_name, $user->user_email);
            }
        }

        if (isset($event_data['endpoint'])) {
            $message .= sprintf(__('Endpoint: %s' . "\n", 'bkgt-api'), $event_data['endpoint']);
        }

        if (isset($event_data['details'])) {
            $message .= "\n" . __('Details:', 'bkgt-api') . "\n";
            $message .= json_encode($event_data['details'], JSON_PRETTY_PRINT);
        }

        return $message;
    }

    /**
     * Format email message
     */
    private function format_email_message($type, $notification_data) {
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('url');

        $html = '<html><body>';
        $html .= '<h2>' . esc_html($notification_data['title']) . '</h2>';
        $html .= '<p>' . nl2br(esc_html($notification_data['message'])) . '</p>';
        $html .= '<hr>';
        $html .= '<p><small>';
        $html .= sprintf(__('This notification was sent from %s at %s', 'bkgt-api'), $site_name, $site_url);
        $html .= '<br>';
        $html .= __('Time: ', 'bkgt-api') . current_time('Y-m-d H:i:s');
        $html .= '</small></p>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Get recent notifications
     */
    private function get_recent_notifications($type, $time_window_seconds) {
        global $wpdb;

        $since = date('Y-m-d H:i:s', time() - $time_window_seconds);

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bkgt_notifications
             WHERE type = %s AND created_at >= %s
             ORDER BY created_at DESC",
            $type,
            $since
        ));
    }

    /**
     * Get unread notifications for dashboard
     */
    public function get_unread_notifications($limit = 50) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_notifications';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return array();
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name
             WHERE is_read = 0
             ORDER BY created_at DESC
             LIMIT %d",
            $limit
        ));
    }

    /**
     * Mark notification as read
     */
    public function mark_notification_read($notification_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_notifications';

        return $wpdb->update(
            $table_name,
            array('is_read' => 1),
            array('id' => $notification_id),
            array('%d'),
            array('%d')
        );
    }

    /**
     * Clean up old notifications
     */
    public function cleanup_old_notifications() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_notifications';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return;
        }

        // Delete notifications older than 90 days
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            date('Y-m-d H:i:s', strtotime('-90 days'))
        ));

        // Delete read notifications older than 30 days
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE is_read = 1 AND created_at < %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
    }

    /**
     * Get notification statistics
     */
    public function get_notification_stats() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_notifications';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return array(
                'total' => 0,
                'unread' => 0,
                'by_type' => array(),
                'by_severity' => array(),
            );
        }

        $stats = array(
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'unread' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_read = 0"),
            'by_type' => $this->get_notification_counts_by_field($table_name, 'type'),
            'by_severity' => $this->get_notification_counts_by_field($table_name, 'severity'),
        );

        return $stats;
    }

    /**
     * Get notification counts by field
     */
    private function get_notification_counts_by_field($table_name, $field) {
        global $wpdb;

        $results = $wpdb->get_results("SELECT $field, COUNT(*) as count FROM $table_name GROUP BY $field");

        $counts = array();
        foreach ($results as $result) {
            $counts[$result->$field] = (int) $result->count;
        }

        return $counts;
    }
}