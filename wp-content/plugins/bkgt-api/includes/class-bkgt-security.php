<?php
/**
 * BKGT API Security Class
 *
 * Handles security measures including rate limiting, CORS, input validation, and security headers
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_API_Security {

    /**
     * Rate limiting cache group
     */
    const RATE_LIMIT_CACHE_GROUP = 'bkgt_api_rate_limits';

    /**
     * Security log table name
     */
    const SECURITY_LOG_TABLE = 'bkgt_security_logs';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialize security measures
     */
    public function init() {
        $this->add_security_headers();
        $this->setup_cors();
        $this->init_security_logging();

        // Clean up expired rate limit data
        if (!wp_next_scheduled('bkgt_cleanup_rate_limits')) {
            wp_schedule_event(time(), 'hourly', 'bkgt_cleanup_rate_limits');
        }

        add_action('bkgt_cleanup_rate_limits', array($this, 'cleanup_rate_limits'));
    }

    /**
     * Add security headers to API responses
     */
    public function add_security_headers() {
        if (!$this->is_api_request()) {
            return;
        }

        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // API-specific headers
        header('X-API-Version: ' . BKGT_API_VERSION);
        header('X-Powered-By: BKGT API');

        // HSTS for HTTPS
        if (is_ssl()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    /**
     * Setup CORS handling
     */
    public function setup_cors() {
        add_action('rest_pre_serve_request', array($this, 'handle_cors'), 10, 4);
        add_filter('rest_pre_dispatch', array($this, 'handle_preflight_request'), 10, 3);
    }

    /**
     * Handle CORS headers
     */
    public function handle_cors($served, $result, $request, $server) {
        if (!$this->is_api_request()) {
            return $served;
        }

        $allowed_origins = get_option('cors_allowed_origins', array());
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Check if origin is allowed
        if (!empty($allowed_origins) && !in_array($origin, $allowed_origins)) {
            return $served;
        }

        // Set CORS headers
        if (!empty($allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } else {
            header('Access-Control-Allow-Origin: *');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-API-Key, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400'); // 24 hours

        return $served;
    }

    /**
     * Handle preflight OPTIONS requests
     */
    public function handle_preflight_request($result, $server, $request) {
        if ($request->get_method() === 'OPTIONS') {
            $allowed_origins = get_option('cors_allowed_origins', array());
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

            if (!empty($allowed_origins) && !in_array($origin, $allowed_origins)) {
                return new WP_Error(
                    'cors_not_allowed',
                    __('Origin not allowed by CORS policy.', 'bkgt-api'),
                    array('status' => 403)
                );
            }

            // Return empty response for OPTIONS requests
            return new WP_REST_Response(null, 200);
        }

        return $result;
    }

    /**
     * Check rate limiting
     */
    public function check_rate_limit($request) {
        $ip = $this->get_client_ip();
        $endpoint = $request->get_route();
        $method = $request->get_method();
        $user_id = get_current_user_id();

        // Use user ID for authenticated requests, IP for anonymous
        $identifier = $user_id ?: $ip;
        $cache_key = md5($identifier . $endpoint . $method);

        $rate_limit = get_option('api_rate_limit', 100);
        $window = get_option('api_rate_limit_window', 60); // seconds

        // Get current request count
        $requests = wp_cache_get($cache_key, self::RATE_LIMIT_CACHE_GROUP);
        if ($requests === false) {
            $requests = 0;
        }

        // Check if limit exceeded
        if ($requests >= $rate_limit) {
            $this->log_security_event('rate_limit_exceeded', array(
                'ip' => $ip,
                'user_id' => $user_id,
                'endpoint' => $endpoint,
                'method' => $method,
                'requests' => $requests,
            ));

            return false;
        }

        // Increment counter
        wp_cache_set($cache_key, $requests + 1, self::RATE_LIMIT_CACHE_GROUP, $window);

        return true;
    }

    /**
     * Get rate limit headers for response
     */
    public function get_rate_limit_headers($request) {
        $ip = $this->get_client_ip();
        $endpoint = $request->get_route();
        $method = $request->get_method();
        $user_id = get_current_user_id();

        $identifier = $user_id ?: $ip;
        $cache_key = md5($identifier . $endpoint . $method);

        $rate_limit = get_option('api_rate_limit', 100);
        $window = get_option('api_rate_limit_window', 60);

        $requests = wp_cache_get($cache_key, self::RATE_LIMIT_CACHE_GROUP) ?: 0;
        $remaining = max(0, $rate_limit - $requests);
        $reset_time = time() + $window;

        return array(
            'X-RateLimit-Limit' => $rate_limit,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => $reset_time,
        );
    }

    /**
     * Validate and sanitize input data
     */
    public function validate_input($data, $rules) {
        $validated = array();
        $errors = array();

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;

            // Check required fields
            if (isset($rule['required']) && $rule['required'] && (is_null($value) || $value === '')) {
                $errors[$field] = __('This field is required.', 'bkgt-api');
                continue;
            }

            // Skip validation if field is not required and empty
            if (!$rule['required'] && (is_null($value) || $value === '')) {
                continue;
            }

            // Type validation
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field] = __('This field must be a string.', 'bkgt-api');
                            continue 2;
                        }
                        break;
                    case 'integer':
                        if (!is_numeric($value) || intval($value) != $value) {
                            $errors[$field] = __('This field must be an integer.', 'bkgt-api');
                            continue 2;
                        }
                        $value = intval($value);
                        break;
                    case 'float':
                        if (!is_numeric($value)) {
                            $errors[$field] = __('This field must be a number.', 'bkgt-api');
                            continue 2;
                        }
                        $value = floatval($value);
                        break;
                    case 'boolean':
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                        if (is_null($value)) {
                            $errors[$field] = __('This field must be a boolean.', 'bkgt-api');
                            continue 2;
                        }
                        break;
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = __('This field must be a valid email address.', 'bkgt-api');
                            continue 2;
                        }
                        break;
                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[$field] = __('This field must be a valid URL.', 'bkgt-api');
                            continue 2;
                        }
                        break;
                    case 'date':
                        if (!strtotime($value)) {
                            $errors[$field] = __('This field must be a valid date.', 'bkgt-api');
                            continue 2;
                        }
                        break;
                }
            }

            // Length validation
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = sprintf(
                    __('This field must be at least %d characters long.', 'bkgt-api'),
                    $rule['min_length']
                );
                continue;
            }

            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = sprintf(
                    __('This field must be no more than %d characters long.', 'bkgt-api'),
                    $rule['max_length']
                );
                continue;
            }

            // Range validation for numbers
            if (isset($rule['min']) && $value < $rule['min']) {
                $errors[$field] = sprintf(
                    __('This field must be at least %d.', 'bkgt-api'),
                    $rule['min']
                );
                continue;
            }

            if (isset($rule['max']) && $value > $rule['max']) {
                $errors[$field] = sprintf(
                    __('This field must be no more than %d.', 'bkgt-api'),
                    $rule['max']
                );
                continue;
            }

            // Enum validation
            if (isset($rule['enum']) && !in_array($value, $rule['enum'])) {
                $errors[$field] = __('This field has an invalid value.', 'bkgt-api');
                continue;
            }

            // Custom validation
            if (isset($rule['validate_callback']) && is_callable($rule['validate_callback'])) {
                $callback_result = call_user_func($rule['validate_callback'], $value, $field);
                if ($callback_result !== true) {
                    $errors[$field] = is_string($callback_result) ? $callback_result : __('Validation failed.', 'bkgt-api');
                    continue;
                }
            }

            // Sanitization
            if (isset($rule['sanitize_callback']) && is_callable($rule['sanitize_callback'])) {
                $value = call_user_func($rule['sanitize_callback'], $value);
            } elseif ($rule['type'] === 'string') {
                $value = sanitize_text_field($value);
            }

            $validated[$field] = $value;
        }

        if (!empty($errors)) {
            return new WP_Error(
                'validation_failed',
                __('Input validation failed.', 'bkgt-api'),
                array('errors' => $errors, 'status' => 400)
            );
        }

        return $validated;
    }

    /**
     * Sanitize SQL input to prevent injection
     */
    public function sanitize_sql($input) {
        global $wpdb;

        if (is_array($input)) {
            return array_map(array($this, 'sanitize_sql'), $input);
        }

        return $wpdb->_real_escape($input);
    }

    /**
     * Check for suspicious activity
     */
    public function detect_suspicious_activity($request) {
        $ip = $this->get_client_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $endpoint = $request->get_route();

        // Check for common attack patterns
        $suspicious_patterns = array(
            '/\bunion\b.*\bselect\b/i',  // SQL injection
            '/\bscript\b/i',             // XSS attempts
            '/\beval\s*\(/i',            // Code injection
            '/\bexec\s*\(/i',            // Command injection
            '/\.\./',                    // Directory traversal
            '/\bselect\b.*\bfrom\b.*\binformation_schema\b/i', // Database enumeration
        );

        $request_data = json_encode($request->get_params());

        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $request_data)) {
                $this->log_security_event('suspicious_activity_detected', array(
                    'ip' => $ip,
                    'user_agent' => $user_agent,
                    'endpoint' => $endpoint,
                    'pattern' => $pattern,
                    'request_data' => $request_data,
                ));

                return true;
            }
        }

        return false;
    }

    /**
     * Block suspicious requests
     */
    public function block_suspicious_request($request) {
        $ip = $this->get_client_ip();

        // Add to temporary block list
        $blocked_ips = get_option('bkgt_blocked_ips', array());
        $blocked_ips[$ip] = array(
            'blocked_at' => time(),
            'reason' => 'suspicious_activity',
            'expires_at' => time() + 3600, // 1 hour
        );

        update_option('bkgt_blocked_ips', $blocked_ips);

        $this->log_security_event('ip_blocked', array(
            'ip' => $ip,
            'reason' => 'suspicious_activity',
            'duration' => '1 hour',
        ));

        return new WP_Error(
            'access_denied',
            __('Access denied due to suspicious activity.', 'bkgt-api'),
            array('status' => 403)
        );
    }

    /**
     * Check if IP is blocked
     */
    public function is_ip_blocked($ip = null) {
        if (!$ip) {
            $ip = $this->get_client_ip();
        }

        $blocked_ips = get_option('bkgt_blocked_ips', array());

        if (!isset($blocked_ips[$ip])) {
            return false;
        }

        $block_data = $blocked_ips[$ip];

        // Check if block has expired
        if ($block_data['expires_at'] < time()) {
            unset($blocked_ips[$ip]);
            update_option('bkgt_blocked_ips', $blocked_ips);
            return false;
        }

        return true;
    }

    /**
     * Initialize security logging
     */
    private function init_security_logging() {
        global $wpdb;

        $table_name = $wpdb->prefix . self::SECURITY_LOG_TABLE;

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                id int(11) NOT NULL AUTO_INCREMENT,
                event_type varchar(50) NOT NULL,
                severity enum('low','medium','high','critical') DEFAULT 'medium',
                ip_address varchar(45) NOT NULL,
                user_id bigint(20) unsigned DEFAULT NULL,
                user_agent text,
                event_data longtext,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY event_type (event_type),
                KEY severity (severity),
                KEY ip_address (ip_address),
                KEY user_id (user_id),
                KEY created_at (created_at)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    /**
     * Log security events
     */
    public function log_security_event($event_type, $data = array(), $severity = 'medium') {
        if (!get_option('api_logging_enabled', true)) {
            return;
        }

        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . self::SECURITY_LOG_TABLE,
            array(
                'event_type' => $event_type,
                'severity' => $severity,
                'ip_address' => $this->get_client_ip(),
                'user_id' => get_current_user_id() ?: null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'event_data' => json_encode($data),
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );
    }

    /**
     * Get security logs
     */
    public function get_security_logs($filters = array(), $page = 1, $per_page = 50) {
        global $wpdb;

        $offset = ($page - 1) * $per_page;

        $where = "WHERE 1=1";
        $params = array();

        if (!empty($filters['event_type'])) {
            $where .= " AND event_type = %s";
            $params[] = $filters['event_type'];
        }

        if (!empty($filters['severity'])) {
            $where .= " AND severity = %s";
            $params[] = $filters['severity'];
        }

        if (!empty($filters['ip_address'])) {
            $where .= " AND ip_address = %s";
            $params[] = $filters['ip_address'];
        }

        if (!empty($filters['user_id'])) {
            $where .= " AND user_id = %d";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['start_date'])) {
            $where .= " AND created_at >= %s";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where .= " AND created_at <= %s";
            $params[] = $filters['end_date'];
        }

        $total_query = "SELECT COUNT(*) FROM {$wpdb->prefix}" . self::SECURITY_LOG_TABLE . " $where";
        $total = $wpdb->get_var($wpdb->prepare($total_query, $params));

        $logs_query = "SELECT l.*, u.display_name FROM {$wpdb->prefix}" . self::SECURITY_LOG_TABLE . " l
                      LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
                      $where ORDER BY l.created_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $logs = $wpdb->get_results($wpdb->prepare($logs_query, $params));

        return array(
            'logs' => $logs,
            'total' => (int) $total,
            'total_pages' => ceil($total / $per_page),
            'page' => (int) $page,
            'per_page' => (int) $per_page,
        );
    }

    /**
     * Clean up expired rate limits
     */
    public function cleanup_rate_limits() {
        // Rate limits are handled by cache expiration, but we can clean up blocked IPs
        $blocked_ips = get_option('bkgt_blocked_ips', array());
        $current_time = time();

        foreach ($blocked_ips as $ip => $data) {
            if ($data['expires_at'] < $current_time) {
                unset($blocked_ips[$ip]);
            }
        }

        if (!empty($blocked_ips)) {
            update_option('bkgt_blocked_ips', $blocked_ips);
        } else {
            delete_option('bkgt_blocked_ips');
        }
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
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Check if current request is an API request
     */
    private function is_api_request() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($request_uri, '/wp-json/bkgt/') !== false;
    }

    /**
     * Generate security nonce for forms
     */
    public function generate_nonce($action = 'bkgt_api_nonce') {
        return wp_create_nonce($action);
    }

    /**
     * Verify security nonce
     */
    public function verify_nonce($nonce, $action = 'bkgt_api_nonce') {
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Get security statistics
     */
    public function get_security_stats() {
        global $wpdb;

        $table_name = $wpdb->prefix . self::SECURITY_LOG_TABLE;

        $stats = array(
            'total_events' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'events_today' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s",
                current_time('Y-m-d')
            )),
            'blocked_ips' => count(get_option('bkgt_blocked_ips', array())),
            'high_severity_events' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE severity IN ('high', 'critical') AND created_at >= %s",
                date('Y-m-d H:i:s', strtotime('-7 days'))
            )),
        );

        return $stats;
    }
}