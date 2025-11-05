<?php
/**
 * BKGT API Core Class
 *
 * Handles the main API functionality and initialization
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_API_Core {

    /**
     * API version
     */
    private $version = '1';

    /**
     * Namespace for REST API
     */
    private $namespace = 'bkgt/v1';

    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize the API core
     */
    public function init() {
        add_filter('rest_pre_dispatch', array($this, 'pre_dispatch'), 10, 3);
        add_filter('rest_post_dispatch', array($this, 'post_dispatch'), 10, 3);
    }

    /**
     * Pre-dispatch hook for API requests
     */
    public function pre_dispatch($result, $server, $request) {
        // Log API request
        $this->log_request($request);

        // Rate limiting check
        if (!$this->check_rate_limit($request)) {
            return new WP_Error(
                'rate_limit_exceeded',
                __('Rate limit exceeded. Please try again later.', 'bkgt-api'),
                array('status' => 429)
            );
        }

        return $result;
    }

    /**
     * Post-dispatch hook for API requests
     */
    public function post_dispatch($result, $server, $request) {
        // Add CORS headers
        $this->add_cors_headers();

        // Log API response
        $this->log_response($request, $result);

        return $result;
    }

    /**
     * Handle user login
     */
    public function handle_login($request) {
        $username = $request->get_param('username');
        $password = $request->get_param('password');

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            return new WP_Error(
                'invalid_credentials',
                __('Invalid username or password.', 'bkgt-api'),
                array('status' => 401)
            );
        }

        $token_data = $this->generate_jwt_token($user);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'token' => $token_data['token'],
                'refresh_token' => $token_data['refresh_token'],
                'user' => array(
                    'id' => $user->ID,
                    'username' => $user->user_login,
                    'email' => $user->user_email,
                    'display_name' => $user->display_name,
                    'roles' => $user->roles,
                ),
                'expires_in' => get_option('jwt_expiry', 900),
            ),
        ), 200);
    }

    /**
     * Handle token refresh
     */
    public function handle_refresh_token($request) {
        $refresh_token = $request->get_param('refresh_token');

        $user_id = $this->validate_refresh_token_value($refresh_token);

        if (!$user_id) {
            return new WP_Error(
                'invalid_refresh_token',
                __('Invalid refresh token.', 'bkgt-api'),
                array('status' => 401)
            );
        }

        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                __('User not found.', 'bkgt-api'),
                array('status' => 404)
            );
        }

        $token_data = $this->generate_jwt_token($user);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'token' => $token_data['token'],
                'refresh_token' => $token_data['refresh_token'],
                'expires_in' => get_option('jwt_expiry', 900),
            ),
        ), 200);
    }

    /**
     * Handle user logout
     */
    public function handle_logout($request) {
        // Invalidate the current token (implementation depends on token storage)
        // For JWT, we rely on client-side token deletion

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Successfully logged out.', 'bkgt-api'),
        ), 200);
    }

    /**
     * Get teams
     */
    public function get_teams($request) {
        global $wpdb;

        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $search = $request->get_param('search');

        $offset = ($page - 1) * $per_page;

        $where = "WHERE 1=1";
        $params = array();

        if ($search) {
            $where .= " AND (name LIKE %s OR city LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        $total_query = "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams $where";
        $total = $wpdb->get_var($wpdb->prepare($total_query, $params));

        $teams_query = "SELECT * FROM {$wpdb->prefix}bkgt_teams $where ORDER BY name ASC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $teams = $wpdb->get_results($wpdb->prepare($teams_query, $params));

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'teams' => $teams,
                'pagination' => array(
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => (int) $total,
                    'total_pages' => ceil($total / $per_page),
                ),
            ),
        ), 200);
    }

    /**
     * Get single team
     */
    public function get_team($request) {
        global $wpdb;

        $team_id = $request->get_param('id');

        $team = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bkgt_teams WHERE id = %d",
            $team_id
        ));

        if (!$team) {
            return new WP_Error(
                'team_not_found',
                __('Team not found.', 'bkgt-api'),
                array('status' => 404)
            );
        }

        // Get team players
        $players = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bkgt_players WHERE team_id = %d ORDER BY name ASC",
            $team_id
        ));

        $team->players = $players;

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $team,
        ), 200);
    }

    /**
     * Get players
     */
    public function get_players($request) {
        global $wpdb;

        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $team_id = $request->get_param('team_id');
        $search = $request->get_param('search');

        $offset = ($page - 1) * $per_page;

        $where = "WHERE 1=1";
        $params = array();

        if ($team_id) {
            $where .= " AND team_id = %d";
            $params[] = $team_id;
        }

        if ($search) {
            $where .= " AND (name LIKE %s OR position LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        $total_query = "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players $where";
        $total = $wpdb->get_var($wpdb->prepare($total_query, $params));

        $players_query = "SELECT p.*, t.name as team_name FROM {$wpdb->prefix}bkgt_players p
                         LEFT JOIN {$wpdb->prefix}bkgt_teams t ON p.team_id = t.id
                         $where ORDER BY p.name ASC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $players = $wpdb->get_results($wpdb->prepare($players_query, $params));

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'players' => $players,
                'pagination' => array(
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => (int) $total,
                    'total_pages' => ceil($total / $per_page),
                ),
            ),
        ), 200);
    }

    /**
     * Get single player
     */
    public function get_player($request) {
        global $wpdb;

        $player_id = $request->get_param('id');

        $player = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, t.name as team_name FROM {$wpdb->prefix}bkgt_players p
             LEFT JOIN {$wpdb->prefix}bkgt_teams t ON p.team_id = t.id
             WHERE p.id = %d",
            $player_id
        ));

        if (!$player) {
            return new WP_Error(
                'player_not_found',
                __('Player not found.', 'bkgt-api'),
                array('status' => 404)
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $player,
        ), 200);
    }

    /**
     * Get events
     */
    public function get_events($request) {
        global $wpdb;

        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $team_id = $request->get_param('team_id');
        $start_date = $request->get_param('start_date');
        $end_date = $request->get_param('end_date');

        $offset = ($page - 1) * $per_page;

        $where = "WHERE 1=1";
        $params = array();

        if ($team_id) {
            $where .= " AND team_id = %d";
            $params[] = $team_id;
        }

        if ($start_date) {
            $where .= " AND event_date >= %s";
            $params[] = $start_date;
        }

        if ($end_date) {
            $where .= " AND event_date <= %s";
            $params[] = $end_date;
        }

        $total_query = "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_events $where";
        $total = $wpdb->get_var($wpdb->prepare($total_query, $params));

        $events_query = "SELECT e.*, t.name as team_name FROM {$wpdb->prefix}bkgt_events e
                        LEFT JOIN {$wpdb->prefix}bkgt_teams t ON e.team_id = t.id
                        $where ORDER BY e.event_date DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $events = $wpdb->get_results($wpdb->prepare($events_query, $params));

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'events' => $events,
                'pagination' => array(
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => (int) $total,
                    'total_pages' => ceil($total / $per_page),
                ),
            ),
        ), 200);
    }

    /**
     * Get single event
     */
    public function get_event($request) {
        global $wpdb;

        $event_id = $request->get_param('id');

        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, t.name as team_name FROM {$wpdb->prefix}bkgt_events e
             LEFT JOIN {$wpdb->prefix}bkgt_teams t ON e.team_id = t.id
             WHERE e.id = %d",
            $event_id
        ));

        if (!$event) {
            return new WP_Error(
                'event_not_found',
                __('Event not found.', 'bkgt-api'),
                array('status' => 404)
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $event,
        ), 200);
    }

    /**
     * Get documents
     */
    public function get_documents($request) {
        global $wpdb;

        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $type = $request->get_param('type');
        $search = $request->get_param('search');

        $offset = ($page - 1) * $per_page;

        $where = "WHERE 1=1";
        $params = array();

        if ($type) {
            $where .= " AND type = %s";
            $params[] = $type;
        }

        if ($search) {
            $where .= " AND (title LIKE %s OR description LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        $total_query = "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_documents $where";
        $total = $wpdb->get_var($wpdb->prepare($total_query, $params));

        $documents_query = "SELECT * FROM {$wpdb->prefix}bkgt_documents $where
                           ORDER BY upload_date DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $documents = $wpdb->get_results($wpdb->prepare($documents_query, $params));

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'documents' => $documents,
                'pagination' => array(
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => (int) $total,
                    'total_pages' => ceil($total / $per_page),
                ),
            ),
        ), 200);
    }

    /**
     * Get single document
     */
    public function get_document($request) {
        global $wpdb;

        $document_id = $request->get_param('id');

        $document = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bkgt_documents WHERE id = %d",
            $document_id
        ));

        if (!$document) {
            return new WP_Error(
                'document_not_found',
                __('Document not found.', 'bkgt-api'),
                array('status' => 404)
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $document,
        ), 200);
    }

    /**
     * Download document
     */
    public function download_document($request) {
        global $wpdb;

        $document_id = $request->get_param('id');

        $document = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bkgt_documents WHERE id = %d",
            $document_id
        ));

        if (!$document) {
            return new WP_Error(
                'document_not_found',
                __('Document not found.', 'bkgt-api'),
                array('status' => 404)
            );
        }

        $file_path = wp_get_upload_dir()['basedir'] . '/bkgt/' . $document->file_path;

        if (!file_exists($file_path)) {
            return new WP_Error(
                'file_not_found',
                __('File not found.', 'bkgt-api'),
                array('status' => 404)
            );
        }

        // Log download
        $this->log_download($document_id, get_current_user_id());

        // Return file data
        $file_data = file_get_contents($file_path);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'filename' => $document->title,
                'mime_type' => $document->mime_type,
                'size' => $document->file_size,
                'content' => base64_encode($file_data),
            ),
        ), 200);
    }

    /**
     * Get statistics overview
     */
    public function get_stats_overview($request) {
        global $wpdb;

        $stats = array(
            'teams_count' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams"),
            'players_count' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players"),
            'events_count' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_events"),
            'documents_count' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_documents"),
            'upcoming_events' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_events WHERE event_date >= %s",
                current_time('Y-m-d')
            )),
        );

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $stats,
        ), 200);
    }

    /**
     * Get team statistics
     */
    public function get_team_stats($request) {
        global $wpdb;

        $team_id = $request->get_param('team_id');

        if ($team_id) {
            $stats = array(
                'players_count' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players WHERE team_id = %d",
                    $team_id
                )),
                'events_count' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_events WHERE team_id = %d",
                    $team_id
                )),
                'upcoming_events' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_events WHERE team_id = %d AND event_date >= %s",
                    $team_id, current_time('Y-m-d')
                )),
            );
        } else {
            // Get stats for all teams
            $teams_stats = $wpdb->get_results(
                "SELECT t.id, t.name,
                        COUNT(DISTINCT p.id) as players_count,
                        COUNT(DISTINCT e.id) as events_count
                 FROM {$wpdb->prefix}bkgt_teams t
                 LEFT JOIN {$wpdb->prefix}bkgt_players p ON t.id = p.team_id
                 LEFT JOIN {$wpdb->prefix}bkgt_events e ON t.id = e.team_id
                 GROUP BY t.id, t.name
                 ORDER BY t.name ASC"
            );

            $stats = array('teams' => $teams_stats);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $stats,
        ), 200);
    }

    /**
     * Get user profile
     */
    public function get_user_profile($request) {
        $user = wp_get_current_user();

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'roles' => $user->roles,
                'registered_date' => $user->user_registered,
            ),
        ), 200);
    }

    /**
     * Update user profile
     */
    public function update_user_profile($request) {
        $user_id = get_current_user_id();
        $display_name = $request->get_param('display_name');
        $email = $request->get_param('email');

        $update_data = array();

        if ($display_name) {
            $update_data['display_name'] = $display_name;
        }

        if ($email) {
            $update_data['user_email'] = $email;
        }

        if (!empty($update_data)) {
            $result = wp_update_user(array_merge(array('ID' => $user_id), $update_data));

            if (is_wp_error($result)) {
                return new WP_Error(
                    'update_failed',
                    __('Failed to update profile.', 'bkgt-api'),
                    array('status' => 400)
                );
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Profile updated successfully.', 'bkgt-api'),
        ), 200);
    }

    /**
     * Validate JWT token
     */
    public function validate_token($request) {
        $auth_header = $request->get_header('authorization');

        if (!$auth_header || !preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return new WP_Error(
                'missing_token',
                __('Authorization token is required.', 'bkgt-api'),
                array('status' => 401)
            );
        }

        $token = $matches[1];
        $user_id = $this->validate_jwt_token($token);

        if (!$user_id) {
            return new WP_Error(
                'invalid_token',
                __('Invalid or expired token.', 'bkgt-api'),
                array('status' => 401)
            );
        }

        // Set current user
        wp_set_current_user($user_id);

        return true;
    }

    /**
     * Validate refresh token
     */
    public function validate_refresh_token($request) {
        // Refresh token validation is handled in the endpoint
        return true;
    }

    /**
     * Generate JWT token
     */
    private function generate_jwt_token($user) {
        // This is a simplified JWT implementation
        // In production, use a proper JWT library
        $secret = get_option('jwt_secret_key');
        $expiry = get_option('jwt_expiry', 900);

        $header = json_encode(array(
            'alg' => 'HS256',
            'typ' => 'JWT'
        ));

        $payload = json_encode(array(
            'iss' => get_site_url(),
            'iat' => time(),
            'exp' => time() + $expiry,
            'user_id' => $user->ID,
            'username' => $user->user_login,
        ));

        $header_encoded = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($header));
        $payload_encoded = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($payload));

        $signature = hash_hmac('sha256', $header_encoded . "." . $payload_encoded, $secret, true);
        $signature_encoded = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($signature));

        $token = $header_encoded . "." . $payload_encoded . "." . $signature_encoded;

        // Generate refresh token
        $refresh_token = wp_generate_password(64, false);
        update_user_meta($user->ID, 'bkgt_refresh_token', wp_hash($refresh_token));

        return array(
            'token' => $token,
            'refresh_token' => $refresh_token,
        );
    }

    /**
     * Validate JWT token
     */
    private function validate_jwt_token($token) {
        $secret = get_option('jwt_secret_key');

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        $header = $parts[0];
        $payload = $parts[1];
        $signature = $parts[2];

        $expected_signature = hash_hmac('sha256', $header . "." . $payload, $secret, true);
        $expected_signature_encoded = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($expected_signature));

        if (!hash_equals($signature, $expected_signature_encoded)) {
            return false;
        }

        $payload_decoded = json_decode(base64_decode(str_replace(array('-', '_'), array('+', '/'), $payload)), true);

        if (!$payload_decoded || !isset($payload_decoded['exp']) || $payload_decoded['exp'] < time()) {
            return false;
        }

        return $payload_decoded['user_id'];
    }

    /**
     * Validate refresh token value
     */
    private function validate_refresh_token_value($refresh_token) {
        global $wpdb;

        $hashed_token = wp_hash($refresh_token);

        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'bkgt_refresh_token' AND meta_value = %s",
            $hashed_token
        ));

        return $user_id;
    }

    /**
     * Check rate limiting
     */
    private function check_rate_limit($request) {
        $ip = $this->get_client_ip();
        $route = $request->get_route();

        $transient_key = 'bkgt_api_rate_' . md5($ip . $route);
        $requests = get_transient($transient_key);

        if ($requests === false) {
            $requests = 0;
        }

        $rate_limit = get_option('api_rate_limit', 100);
        $window = get_option('api_rate_limit_window', 60);

        if ($requests >= $rate_limit) {
            return false;
        }

        set_transient($transient_key, $requests + 1, $window);
        return true;
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
     * Add CORS headers
     */
    private function add_cors_headers() {
        $allowed_origins = get_option('cors_allowed_origins', array());

        if (!empty($allowed_origins)) {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if (in_array($origin, $allowed_origins)) {
                header('Access-Control-Allow-Origin: ' . $origin);
            }
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
    }

    /**
     * Log API request
     */
    private function log_request($request) {
        if (!get_option('api_logging_enabled', true)) {
            return;
        }

        global $wpdb;

        $user_id = get_current_user_id();
        $api_key_id = $this->get_api_key_id($request);

        $wpdb->insert(
            $wpdb->prefix . 'bkgt_api_logs',
            array(
                'user_id' => $user_id ?: null,
                'api_key_id' => $api_key_id,
                'method' => $request->get_method(),
                'endpoint' => $request->get_route(),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'request_data' => json_encode($request->get_params()),
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Log API response
     */
    private function log_response($request, $response) {
        if (!get_option('api_logging_enabled', true)) {
            return;
        }

        global $wpdb;

        $log_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bkgt_api_logs
             WHERE endpoint = %s AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
             ORDER BY id DESC LIMIT 1",
            $request->get_route()
        ));

        if ($log_id) {
            $wpdb->update(
                $wpdb->prefix . 'bkgt_api_logs',
                array(
                    'response_code' => $response->get_status(),
                    'response_time' => timer_stop(0, 3),
                ),
                array('id' => $log_id),
                array('%d', '%f'),
                array('%d')
            );
        }
    }

    /**
     * Log document download
     */
    private function log_download($document_id, $user_id) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'bkgt_document_downloads',
            array(
                'document_id' => $document_id,
                'user_id' => $user_id,
                'downloaded_at' => current_time('mysql'),
                'ip_address' => $this->get_client_ip(),
            ),
            array('%d', '%d', '%s', '%s')
        );
    }

    /**
     * Get API key ID from request
     */
    private function get_api_key_id($request) {
        // Check for API key in headers
        $api_key = $request->get_header('x-api-key');

        if (!$api_key) {
            return null;
        }

        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bkgt_api_keys WHERE api_key = %s AND is_active = 1",
            $api_key
        ));
    }
}