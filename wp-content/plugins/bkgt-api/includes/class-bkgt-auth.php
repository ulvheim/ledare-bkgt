<?php
/**
 * BKGT API Authentication Class
 *
 * Handles JWT token generation, validation, and refresh tokens
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_API_Auth {

    /**
     * JWT secret key option name
     */
    const JWT_SECRET_OPTION = 'jwt_secret_key';

    /**
     * JWT expiry time option name
     */
    const JWT_EXPIRY_OPTION = 'jwt_expiry';

    /**
     * Refresh token expiry option name
     */
    const REFRESH_EXPIRY_OPTION = 'refresh_token_expiry';

    /**
     * Service API key option name
     */
    const SERVICE_API_KEY_OPTION = 'bkgt_service_api_key';

    /**
     * Service API key rotation interval option name
     */
    const SERVICE_KEY_ROTATION_OPTION = 'bkgt_service_key_rotation_interval';

    /**
     * Service API key last rotation option name
     */
    const SERVICE_KEY_LAST_ROTATION_OPTION = 'bkgt_service_key_last_rotation';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialize authentication components
     */
    public function init() {
        // Ensure JWT secret exists
        $this->ensure_jwt_secret();

        // Ensure service API key exists
        $this->ensure_service_api_key();

        // Clean up expired tokens periodically
        if (!wp_next_scheduled('bkgt_cleanup_expired_tokens')) {
            wp_schedule_event(time(), 'daily', 'bkgt_cleanup_expired_tokens');
        }

        // Schedule service key rotation check
        if (!wp_next_scheduled('bkgt_check_service_key_rotation')) {
            wp_schedule_event(time(), 'hourly', 'bkgt_check_service_key_rotation');
        }

        add_action('bkgt_cleanup_expired_tokens', array($this, 'cleanup_expired_tokens'));
        add_action('bkgt_check_service_key_rotation', array($this, 'check_service_key_rotation'));
        add_action('bkgt_cleanup_old_service_key', array($this, 'cleanup_old_service_key'));
    }

    /**
     * Ensure JWT secret key exists
     */
    private function ensure_jwt_secret() {
        $secret = get_option(self::JWT_SECRET_OPTION);
        if (!$secret) {
            $secret = $this->generate_secret_key();
            update_option(self::JWT_SECRET_OPTION, $secret);
        }
    }

    /**
     * Ensure service API key exists
     */
    private function ensure_service_api_key() {
        $service_key = get_option(self::SERVICE_API_KEY_OPTION);
        if (!$service_key) {
            $service_key = $this->generate_service_api_key();
            update_option(self::SERVICE_API_KEY_OPTION, $service_key);
            update_option(self::SERVICE_KEY_LAST_ROTATION_OPTION, time());
        }
    }

    /**
     * Generate service API key
     */
    private function generate_service_api_key() {
        return 'bkgt_svc_' . $this->generate_secret_key(32);
    }

    /**
     * Generate a secure secret key
     */
    private function generate_secret_key($length = 64) {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length / 2));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length / 2));
        } else {
            return wp_generate_password($length, true, true);
        }
    }

    /**
     * Generate JWT token for user
     */
    public function generate_token($user_id, $custom_claims = array()) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return false;
        }

        $secret = get_option(self::JWT_SECRET_OPTION);
        $expiry = get_option(self::JWT_EXPIRY_OPTION, 900); // 15 minutes default

        $issued_at = time();
        $expiration = $issued_at + $expiry;

        $header = array(
            'alg' => 'HS256',
            'typ' => 'JWT'
        );

        $payload = array_merge(array(
            'iss' => get_site_url(),
            'iat' => $issued_at,
            'exp' => $expiration,
            'user_id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'roles' => $user->roles,
        ), $custom_claims);

        $header_encoded = $this->base64_url_encode(json_encode($header));
        $payload_encoded = $this->base64_url_encode(json_encode($payload));

        $signature = hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, $secret, true);
        $signature_encoded = $this->base64_url_encode($signature);

        $token = $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;

        return array(
            'token' => $token,
            'expires_in' => $expiry,
            'expires_at' => $expiration,
        );
    }

    /**
     * Generate refresh token
     */
    public function generate_refresh_token($user_id) {
        $refresh_token = $this->generate_secret_key(32);
        $hashed_token = wp_hash($refresh_token);

        $expiry = get_option(self::REFRESH_EXPIRY_OPTION, 604800); // 7 days default

        update_user_meta($user_id, 'bkgt_refresh_token', $hashed_token);
        update_user_meta($user_id, 'bkgt_refresh_token_expires', time() + $expiry);

        return $refresh_token;
    }

    /**
     * Validate JWT token
     */
    public function validate_token($token) {
        if (empty($token)) {
            return false;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        $header = $parts[0];
        $payload = $parts[1];
        $signature = $parts[2];

        // Decode payload to check expiration
        $payload_decoded = json_decode($this->base64_url_decode($payload), true);
        if (!$payload_decoded) {
            return false;
        }

        // Check if token is expired
        if (isset($payload_decoded['exp']) && $payload_decoded['exp'] < time()) {
            return false;
        }

        // Verify signature
        $secret = get_option(self::JWT_SECRET_OPTION);
        $expected_signature = hash_hmac('sha256', $header . '.' . $payload, $secret, true);
        $expected_signature_encoded = $this->base64_url_encode($expected_signature);

        if (!hash_equals($signature, $expected_signature_encoded)) {
            return false;
        }

        return $payload_decoded;
    }

    /**
     * Validate refresh token
     */
    public function validate_refresh_token($refresh_token) {
        if (empty($refresh_token)) {
            return false;
        }

        $hashed_token = wp_hash($refresh_token);

        global $wpdb;
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta}
             WHERE meta_key = 'bkgt_refresh_token' AND meta_value = %s",
            $hashed_token
        ));

        if (!$user_id) {
            return false;
        }

        // Check if refresh token is expired
        $expires = get_user_meta($user_id, 'bkgt_refresh_token_expires', true);
        if ($expires && $expires < time()) {
            $this->revoke_refresh_token($user_id);
            return false;
        }

        return $user_id;
    }

    /**
     * Refresh access token using refresh token
     */
    public function refresh_access_token($refresh_token) {
        $user_id = $this->validate_refresh_token($refresh_token);

        if (!$user_id) {
            return false;
        }

        // Generate new tokens
        $token_data = $this->generate_token($user_id);
        $new_refresh_token = $this->generate_refresh_token($user_id);

        return array(
            'token' => $token_data['token'],
            'refresh_token' => $new_refresh_token,
            'expires_in' => $token_data['expires_in'],
        );
    }

    /**
     * Revoke refresh token
     */
    public function revoke_refresh_token($user_id) {
        delete_user_meta($user_id, 'bkgt_refresh_token');
        delete_user_meta($user_id, 'bkgt_refresh_token_expires');
    }

    /**
     * Revoke all tokens for user
     */
    public function revoke_all_tokens($user_id) {
        $this->revoke_refresh_token($user_id);

        // In a production environment, you might want to maintain a blacklist
        // of revoked access tokens. For simplicity, we'll rely on expiration.
    }

    /**
     * Get user ID from token
     */
    public function get_user_from_token($token) {
        $payload = $this->validate_token($token);

        if (!$payload || !isset($payload['user_id'])) {
            return false;
        }

        return $payload['user_id'];
    }

    /**
     * Check if user has required role/permission
     */
    public function check_permissions($user_id, $required_roles = array(), $required_caps = array()) {
        if (empty($required_roles) && empty($required_caps)) {
            return true; // No specific permissions required
        }

        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return false;
        }

        // Check roles
        if (!empty($required_roles)) {
            $user_roles = $user->roles;
            if (empty(array_intersect($required_roles, $user_roles))) {
                return false;
            }
        }

        // Check capabilities
        if (!empty($required_caps)) {
            foreach ($required_caps as $cap) {
                if (!user_can($user, $cap)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Clean up expired tokens
     */
    public function cleanup_expired_tokens() {
        global $wpdb;

        // Remove expired refresh tokens
        $expired_users = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta}
             WHERE meta_key = 'bkgt_refresh_token_expires' AND meta_value < %s",
            time()
        ));

        foreach ($expired_users as $user_id) {
            $this->revoke_refresh_token($user_id);
        }

        // Clean up expired API keys
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}bkgt_api_keys SET is_active = 0
             WHERE expires_at IS NOT NULL AND expires_at < %s",
            current_time('mysql')
        ));
    }

    /**
     * Check if service key rotation is needed
     */
    public function check_service_key_rotation() {
        $rotation_interval = get_option(self::SERVICE_KEY_ROTATION_OPTION, 30 * DAY_IN_SECONDS); // 30 days default
        $last_rotation = get_option(self::SERVICE_KEY_LAST_ROTATION_OPTION, 0);

        if (time() - $last_rotation >= $rotation_interval) {
            $this->rotate_service_api_key();
        }
    }

    /**
     * Rotate service API key
     */
    public function rotate_service_api_key() {
        $new_key = $this->generate_service_api_key();
        $old_key = get_option(self::SERVICE_API_KEY_OPTION);

        // Store old key temporarily for transition period
        update_option('bkgt_service_api_key_old', $old_key);

        // Update to new key
        update_option(self::SERVICE_API_KEY_OPTION, $new_key);
        update_option(self::SERVICE_KEY_LAST_ROTATION_OPTION, time());

        // Log rotation
        error_log('BKGT API: Service API key rotated. Old key will be valid for 24 hours.');

        // Schedule cleanup of old key after 24 hours
        wp_schedule_single_event(time() + DAY_IN_SECONDS, 'bkgt_cleanup_old_service_key');
    }

    /**
     * Validate service API key
     */
    public function validate_service_api_key($api_key) {
        $current_key = get_option(self::SERVICE_API_KEY_OPTION);
        $old_key = get_option('bkgt_service_api_key_old');

        // Check current key
        if ($api_key === $current_key) {
            return true;
        }

        // Check old key (during transition period)
        if ($api_key === $old_key && $old_key) {
            return true;
        }

        return false;
    }

    /**
     * Get current service API key
     */
    public function get_service_api_key() {
        return get_option(self::SERVICE_API_KEY_OPTION);
    }

    /**
     * Set service key rotation interval
     */
    public function set_service_key_rotation_interval($days) {
        $seconds = $days * DAY_IN_SECONDS;
        update_option(self::SERVICE_KEY_ROTATION_OPTION, $seconds);
    }

    /**
     * Get service key rotation interval in days
     */
    public function get_service_key_rotation_interval() {
        $seconds = get_option(self::SERVICE_KEY_ROTATION_OPTION, 30 * DAY_IN_SECONDS);
        return $seconds / DAY_IN_SECONDS;
    }

    /**
     * Clean up old service key
     */
    public function cleanup_old_service_key() {
        delete_option('bkgt_service_api_key_old');
        error_log('BKGT API: Old service API key cleaned up.');
    }

    /**
     * Base64 URL encode
     */
    private function base64_url_encode($data) {
        return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($data));
    }

    /**
     * Base64 URL decode
     */
    private function base64_url_decode($data) {
        return base64_decode(str_replace(array('-', '_'), array('+', '/'), $data));
    }

    /**
     * Get authentication headers from request
     */
    public static function get_auth_headers($request = null) {
        if (!$request) {
            $request = $_SERVER;
        }

        $headers = array();

        // Check for Authorization header
        if (isset($request['HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = $request['HTTP_AUTHORIZATION'];
        } elseif (isset($request['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = $request['REDIRECT_HTTP_AUTHORIZATION'];
        }

        // Check for API key header
        if (isset($request['HTTP_X_API_KEY'])) {
            $headers['x-api-key'] = $request['HTTP_X_API_KEY'];
        }

        return $headers;
    }

    /**
     * Extract token from authorization header
     */
    public static function extract_token($auth_header) {
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return $matches[1];
        }

        return false;
    }

    /**
     * Get current authenticated user from request
     */
    public function get_current_user_from_request($request = null) {
        $headers = self::get_auth_headers($request);

        // Try JWT token first
        if (isset($headers['authorization'])) {
            $token = self::extract_token($headers['authorization']);
            if ($token) {
                $user_id = $this->get_user_from_token($token);
                if ($user_id) {
                    return get_user_by('ID', $user_id);
                }
            }
        }

        // Check for service API key
        if (isset($headers['x-api-key'])) {
            if ($this->validate_service_api_key($headers['x-api-key'])) {
                // Return a virtual service user for internal API calls
                return $this->get_service_user();
            }
        }

        // Fallback to user API key authentication
        if (isset($headers['x-api-key'])) {
            return $this->get_user_from_api_key($headers['x-api-key']);
        }

        return false;
    }

    /**
     * Get virtual service user for internal API calls
     */
    private function get_service_user() {
        // Create a virtual user object for service authentication
        $service_user = new stdClass();
        $service_user->ID = 0;
        $service_user->user_login = 'bkgt_service';
        $service_user->user_email = 'service@' . parse_url(get_site_url(), PHP_URL_HOST);
        $service_user->display_name = 'BKGT Service Account';
        $service_user->roles = array('administrator'); // Service has admin privileges
        $service_user->caps = array('manage_options' => true, 'edit_posts' => true, 'delete_posts' => true);

        return $service_user;
    }

    /**
     * Get user from API key
     */
    private function get_user_from_api_key($api_key) {
        global $wpdb;

        error_log("BKGT API: Checking API key: " . substr($api_key, 0, 8) . "...");
        error_log("BKGT API: Table prefix: " . $wpdb->prefix);
        error_log("BKGT API: Full table name: " . $wpdb->prefix . 'bkgt_api_keys');

        $key_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bkgt_api_keys
             WHERE api_key = %s AND is_active = 1
             AND (expires_at IS NULL OR expires_at > %s)",
            $api_key,
            current_time('mysql')
        ));

        if (!$key_data) {
            error_log("BKGT API: API key not found in database");
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}bkgt_api_keys'");
            error_log("BKGT API: Table exists: " . ($table_exists ? 'yes' : 'no'));

            if ($table_exists) {
                // Check how many keys exist
                $key_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_api_keys");
                error_log("BKGT API: Total keys in table: " . $key_count);
            }
            return false;
        }

        error_log("BKGT API: API key found, created_by user ID: " . $key_data->created_by);

        // Update last used timestamp
        $wpdb->update(
            $wpdb->prefix . 'bkgt_api_keys',
            array('last_used' => current_time('mysql')),
            array('id' => $key_data->id),
            array('%s'),
            array('%d')
        );

        $user = get_user_by('ID', $key_data->created_by);
        error_log("BKGT API: User lookup result: " . ($user ? 'found' : 'not found'));

        return $user;
    }

    /**
     * Create API key for user
     */
    public function create_api_key($user_id, $name = '', $permissions = null, $expires_at = null) {
        global $wpdb;

        $api_key = $this->generate_secret_key(32);
        $api_secret = $this->generate_secret_key(64);

        $result = $wpdb->insert(
            $wpdb->prefix . 'bkgt_api_keys',
            array(
                'api_key' => $api_key,
                'api_secret' => wp_hash($api_secret),
                'name' => $name ?: 'API Key - ' . current_time('mysql'),
                'permissions' => $permissions ? json_encode($permissions) : null,
                'created_by' => $user_id,
                'expires_at' => $expires_at,
                'is_active' => 1,
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s', '%d')
        );

        if ($result) {
            return array(
                'id' => $wpdb->insert_id,
                'api_key' => $api_key,
                'api_secret' => $api_secret,
            );
        }

        return false;
    }

    /**
     * Revoke API key
     */
    public function revoke_api_key($key_id, $user_id = null) {
        global $wpdb;

        $where = array('id' => $key_id);
        $where_format = array('%d');

        if ($user_id) {
            $where['created_by'] = $user_id;
            $where_format[] = '%d';
        }

        return $wpdb->update(
            $wpdb->prefix . 'bkgt_api_keys',
            array('is_active' => 0),
            $where,
            array('%d'),
            $where_format
        );
    }

    /**
     * Delete API key permanently
     */
    public function delete_api_key($key_id, $user_id = null) {
        global $wpdb;

        $where = array('id' => $key_id);
        $where_format = array('%d');

        if ($user_id) {
            $where['created_by'] = $user_id;
            $where_format[] = '%d';
        }

        return $wpdb->delete(
            $wpdb->prefix . 'bkgt_api_keys',
            $where,
            $where_format
        );
    }

    /**
     * Get user's API keys
     */
    public function get_user_api_keys($user_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, api_key, name, permissions, created_at, last_used, expires_at, is_active
             FROM {$wpdb->prefix}bkgt_api_keys
             WHERE created_by = %d
             ORDER BY created_at DESC",
            $user_id
        ));
    }
}