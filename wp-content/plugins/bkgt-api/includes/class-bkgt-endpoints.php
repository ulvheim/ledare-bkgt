<?php
/**
 * BKGT API Endpoints Class
 *
 * Registers and manages all REST API endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_API_Endpoints {

    /**
     * API namespace
     */
    private $namespace = 'bkgt/v1';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register all API routes
     */
    public function register_routes() {
        $this->register_auth_routes();
        $this->register_team_routes();
        $this->register_player_routes();
        $this->register_event_routes();
        $this->register_document_routes();
        $this->register_equipment_routes();
        $this->register_stats_routes();
        $this->register_user_routes();
        $this->register_admin_routes();
    }

    /**
     * Register authentication routes
     */
    private function register_auth_routes() {
        register_rest_route($this->namespace, '/auth/login', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_login'),
            'permission_callback' => '__return_true',
            'args' => array(
                'username' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_required'),
                ),
                'password' => array(
                    'required' => true,
                    'type' => 'string',
                    'validate_callback' => array($this, 'validate_required'),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/auth/refresh', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_refresh_token'),
            'permission_callback' => '__return_true',
            'args' => array(
                'refresh_token' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_required'),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/auth/logout', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_logout'),
            'permission_callback' => array($this, 'validate_token'),
        ));

        register_rest_route($this->namespace, '/auth/me', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_current_user'),
            'permission_callback' => array($this, 'validate_token'),
        ));
    }

    /**
     * Register team routes
     */
    private function register_team_routes() {
        register_rest_route($this->namespace, '/teams', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_teams'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => $this->get_pagination_args(array(
                'search' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'city' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            )),
        ));

        register_rest_route($this->namespace, '/teams/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_team'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/teams/(?P<id>\d+)/players', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_team_players'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array_merge(array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
            ), $this->get_pagination_args()),
        ));

        register_rest_route($this->namespace, '/teams/(?P<id>\d+)/events', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_team_events'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array_merge(array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
            ), $this->get_pagination_args(array(
                'start_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                    'validate_callback' => array($this, 'validate_date'),
                ),
                'end_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                    'validate_callback' => array($this, 'validate_date'),
                ),
            ))),
        ));
    }

    /**
     * Register player routes
     */
    private function register_player_routes() {
        register_rest_route($this->namespace, '/players', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_players'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => $this->get_pagination_args(array(
                'team_id' => array(
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
                'search' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'position' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            )),
        ));

        register_rest_route($this->namespace, '/players/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_player'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
            ),
        ));
    }

    /**
     * Register event routes
     */
    private function register_event_routes() {
        register_rest_route($this->namespace, '/events', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_events'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => $this->get_pagination_args(array(
                'team_id' => array(
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
                'start_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                    'validate_callback' => array($this, 'validate_date'),
                ),
                'end_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                    'validate_callback' => array($this, 'validate_date'),
                ),
                'type' => array(
                    'type' => 'string',
                    'enum' => array('match', 'training', 'meeting', 'other'),
                ),
            )),
        ));

        register_rest_route($this->namespace, '/events/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_event'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/events/upcoming', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_upcoming_events'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'limit' => array(
                    'type' => 'integer',
                    'default' => 10,
                    'minimum' => 1,
                    'maximum' => 50,
                ),
                'team_id' => array(
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
            ),
        ));
    }

    /**
     * Register document routes
     */
    private function register_document_routes() {
        register_rest_route($this->namespace, '/documents', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_documents'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => $this->get_pagination_args(array(
                'type' => array(
                    'type' => 'string',
                    'enum' => array('rulebook', 'minutes', 'financial', 'other'),
                ),
                'search' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'year' => array(
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
            )),
        ));

        register_rest_route($this->namespace, '/documents/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_document'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/documents/(?P<id>\d+)/download', array(
            'methods' => 'GET',
            'callback' => array($this, 'download_document'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/documents/types', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_document_types'),
            'permission_callback' => array($this, 'validate_token'),
        ));
    }

    /**
     * Register equipment routes
     */
    private function register_equipment_routes() {
        // Equipment items
        register_rest_route($this->namespace, '/equipment', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_equipment'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => $this->get_pagination_args(array(
                'manufacturer_id' => array(
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
                'item_type_id' => array(
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
                'condition_status' => array(
                    'type' => 'string',
                    'enum' => array('normal', 'needs_repair', 'repaired', 'reported_lost', 'scrapped'),
                ),
                'assigned_to' => array(
                    'type' => 'string',
                    'enum' => array('club', 'team', 'individual'),
                ),
                'location_id' => array(
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
                'search' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            )),
        ));

        register_rest_route($this->namespace, '/equipment/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_equipment_item'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/equipment', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_equipment_item'),
            'permission_callback' => array($this, 'validate_admin_token'),
            'args' => array(
                'manufacturer_id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
                'item_type_id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
                'title' => array(
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_required'),
                ),
                'storage_location' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'sticker_code' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        register_rest_route($this->namespace, '/equipment/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_equipment_item'),
            'permission_callback' => array($this, 'validate_admin_token'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
                'title' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'condition_status' => array(
                    'type' => 'string',
                    'enum' => array('normal', 'needs_repair', 'repaired', 'reported_lost', 'scrapped'),
                ),
                'condition_reason' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'storage_location' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'sticker_code' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        register_rest_route($this->namespace, '/equipment/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_equipment_item'),
            'permission_callback' => array($this, 'validate_admin_token'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
            ),
        ));

        // Manufacturers
        register_rest_route($this->namespace, '/equipment/manufacturers', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_manufacturers'),
            'permission_callback' => array($this, 'validate_token'),
        ));

        register_rest_route($this->namespace, '/equipment/manufacturers/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_manufacturer'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
            ),
        ));

        // Item Types
        register_rest_route($this->namespace, '/equipment/types', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_item_types'),
            'permission_callback' => array($this, 'validate_token'),
        ));

        register_rest_route($this->namespace, '/equipment/types/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_item_type'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
            ),
        ));

        // Assignments
        register_rest_route($this->namespace, '/equipment/(?P<id>\d+)/assign', array(
            'methods' => 'POST',
            'callback' => array($this, 'assign_equipment'),
            'permission_callback' => array($this, 'validate_admin_token'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
                'assignment_type' => array(
                    'type' => 'string',
                    'required' => true,
                    'enum' => array('individual', 'team', 'club'),
                    'validate_callback' => array($this, 'validate_required'),
                ),
                'assignee_id' => array(
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
                'due_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                ),
                'notes' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        register_rest_route($this->namespace, '/equipment/(?P<id>\d+)/return', array(
            'methods' => 'POST',
            'callback' => array($this, 'return_equipment'),
            'permission_callback' => array($this, 'validate_admin_token'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
                'return_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                ),
                'condition_status' => array(
                    'type' => 'string',
                    'enum' => array('normal', 'needs_repair', 'repaired', 'reported_lost', 'scrapped'),
                ),
                'notes' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // Locations
        register_rest_route($this->namespace, '/equipment/locations', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_locations'),
            'permission_callback' => array($this, 'validate_token'),
        ));

        register_rest_route($this->namespace, '/equipment/locations/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_location'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
            ),
        ));

        // Preview unique identifier
        register_rest_route($this->namespace, '/equipment/preview-identifier', array(
            'methods' => 'GET',
            'callback' => array($this, 'preview_equipment_identifier'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'manufacturer_id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
                'item_type_id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/equipment/analytics/usage', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_equipment_usage_analytics'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'start_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                    'validate_callback' => array($this, 'validate_date'),
                ),
                'end_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                    'validate_callback' => array($this, 'validate_date'),
                ),
            ),
        ));
    }

    /**
     * Register statistics routes
     */
    private function register_stats_routes() {
        register_rest_route($this->namespace, '/stats/overview', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_stats_overview'),
            'permission_callback' => array($this, 'validate_token'),
        ));

        register_rest_route($this->namespace, '/stats/teams', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_team_stats'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'team_id' => array(
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
                'season' => array(
                    'type' => 'string',
                    'validate_callback' => array($this, 'validate_season'),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/stats/players', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_player_stats'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'player_id' => array(
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
                'season' => array(
                    'type' => 'string',
                    'validate_callback' => array($this, 'validate_season'),
                ),
            ),
        ));
    }

    /**
     * Register user routes
     */
    private function register_user_routes() {
        register_rest_route($this->namespace, '/user/profile', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_profile'),
            'permission_callback' => array($this, 'validate_token'),
        ));

        register_rest_route($this->namespace, '/user/profile', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_user_profile'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'display_name' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_display_name'),
                ),
                'email' => array(
                    'type' => 'string',
                    'format' => 'email',
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => array($this, 'validate_email_unique'),
                ),
                'first_name' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'last_name' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        register_rest_route($this->namespace, '/user/api-keys', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_api_keys'),
            'permission_callback' => array($this, 'validate_token'),
        ));

        register_rest_route($this->namespace, '/user/api-keys', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_api_key'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'name' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_required'),
                ),
                'permissions' => array(
                    'type' => 'array',
                    'items' => array(
                        'type' => 'string',
                        'enum' => array('read', 'write', 'admin'),
                    ),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/user/api-keys/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'revoke_api_key'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
            ),
        ));
    }

    /**
     * Register admin routes
     */
    private function register_admin_routes() {
        register_rest_route($this->namespace, '/admin/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_admin_stats'),
            'permission_callback' => array($this, 'validate_admin_token'),
        ));

        register_rest_route($this->namespace, '/admin/logs', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_api_logs'),
            'permission_callback' => array($this, 'validate_admin_token'),
            'args' => $this->get_pagination_args(array(
                'start_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                    'validate_callback' => array($this, 'validate_date'),
                ),
                'end_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                    'validate_callback' => array($this, 'validate_date'),
                ),
                'endpoint' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'method' => array(
                    'type' => 'string',
                    'enum' => array('GET', 'POST', 'PUT', 'DELETE'),
                ),
            )),
        ));

        register_rest_route($this->namespace, '/admin/api-keys', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_all_api_keys'),
            'permission_callback' => array($this, 'validate_admin_token'),
            'args' => $this->get_pagination_args(array(
                'user_id' => array(
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
                'is_active' => array(
                    'type' => 'boolean',
                ),
            )),
        ));

        register_rest_route($this->namespace, '/admin/settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_api_settings'),
            'permission_callback' => array($this, 'validate_admin_token'),
        ));

        register_rest_route($this->namespace, '/admin/settings', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_api_settings'),
            'permission_callback' => array($this, 'validate_admin_token'),
            'args' => array(
                'jwt_expiry' => array(
                    'type' => 'integer',
                    'minimum' => 60,
                    'maximum' => 86400,
                ),
                'refresh_token_expiry' => array(
                    'type' => 'integer',
                    'minimum' => 3600,
                    'maximum' => 2592000,
                ),
                'api_rate_limit' => array(
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
                'cors_allowed_origins' => array(
                    'type' => 'array',
                    'items' => array('type' => 'string'),
                ),
                'api_debug_mode' => array(
                    'type' => 'boolean',
                ),
                'api_logging_enabled' => array(
                    'type' => 'boolean',
                ),
            ),
        ));
    }

    /**
     * Get pagination arguments
     */
    private function get_pagination_args($additional_args = array()) {
        return array_merge(array(
            'page' => array(
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'type' => 'integer',
                'default' => 20,
                'minimum' => 1,
                'maximum' => 100,
                'sanitize_callback' => 'absint',
            ),
        ), $additional_args);
    }

    /**
     * Authentication endpoint handlers
     */
    public function handle_login($request) {
        $auth = new BKGT_API_Auth();
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

        $token_data = $auth->generate_token($user->ID);
        $refresh_token = $auth->generate_refresh_token($user->ID);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'token' => $token_data['token'],
                'refresh_token' => $refresh_token,
                'expires_in' => $token_data['expires_in'],
                'user' => $this->prepare_user_data($user),
            ),
        ), 200);
    }

    public function handle_refresh_token($request) {
        $auth = new BKGT_API_Auth();
        $refresh_token = $request->get_param('refresh_token');

        $token_data = $auth->refresh_access_token($refresh_token);

        if (!$token_data) {
            return new WP_Error(
                'invalid_refresh_token',
                __('Invalid or expired refresh token.', 'bkgt-api'),
                array('status' => 401)
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $token_data,
        ), 200);
    }

    public function handle_logout($request) {
        $auth = new BKGT_API_Auth();
        $user_id = get_current_user_id();

        $auth->revoke_all_tokens($user_id);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Successfully logged out.', 'bkgt-api'),
        ), 200);
    }

    public function get_current_user($request) {
        $user = wp_get_current_user();

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $this->prepare_user_data($user),
        ), 200);
    }

    /**
     * Team endpoint handlers
     */
    public function get_teams($request) {
        global $wpdb;

        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $search = $request->get_param('search');
        $city = $request->get_param('city');

        $offset = ($page - 1) * $per_page;

        $where = "WHERE 1=1";
        $params = array();

        if ($search) {
            $where .= " AND (name LIKE %s OR city LIKE %s OR league LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        if ($city) {
            $where .= " AND city LIKE %s";
            $params[] = '%' . $wpdb->esc_like($city) . '%';
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
                'pagination' => $this->prepare_pagination_data($page, $per_page, $total),
            ),
        ), 200);
    }

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

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $team,
        ), 200);
    }

    public function get_team_players($request) {
        global $wpdb;

        $team_id = $request->get_param('id');
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');

        $offset = ($page - 1) * $per_page;

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players WHERE team_id = %d",
            $team_id
        ));

        $players = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bkgt_players WHERE team_id = %d ORDER BY name ASC LIMIT %d OFFSET %d",
            $team_id, $per_page, $offset
        ));

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'players' => $players,
                'pagination' => $this->prepare_pagination_data($page, $per_page, $total),
            ),
        ), 200);
    }

    public function get_team_events($request) {
        global $wpdb;

        $team_id = $request->get_param('id');
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $start_date = $request->get_param('start_date');
        $end_date = $request->get_param('end_date');

        $offset = ($page - 1) * $per_page;

        $where = "WHERE team_id = %d";
        $params = array($team_id);

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

        $events_query = "SELECT * FROM {$wpdb->prefix}bkgt_events $where ORDER BY event_date DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $events = $wpdb->get_results($wpdb->prepare($events_query, $params));

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'events' => $events,
                'pagination' => $this->prepare_pagination_data($page, $per_page, $total),
            ),
        ), 200);
    }

    /**
     * Player endpoint handlers
     */
    public function get_players($request) {
        global $wpdb;

        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $team_id = $request->get_param('team_id');
        $search = $request->get_param('search');
        $position = $request->get_param('position');

        $offset = ($page - 1) * $per_page;

        $where = "WHERE 1=1";
        $params = array();

        if ($team_id) {
            $where .= " AND team_id = %d";
            $params[] = $team_id;
        }

        if ($search) {
            $where .= " AND name LIKE %s";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        if ($position) {
            $where .= " AND position LIKE %s";
            $params[] = '%' . $wpdb->esc_like($position) . '%';
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
                'pagination' => $this->prepare_pagination_data($page, $per_page, $total),
            ),
        ), 200);
    }

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
     * Event endpoint handlers
     */
    public function get_events($request) {
        global $wpdb;

        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $team_id = $request->get_param('team_id');
        $start_date = $request->get_param('start_date');
        $end_date = $request->get_param('end_date');
        $type = $request->get_param('type');

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

        if ($type) {
            $where .= " AND type = %s";
            $params[] = $type;
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
                'pagination' => $this->prepare_pagination_data($page, $per_page, $total),
            ),
        ), 200);
    }

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

    public function get_upcoming_events($request) {
        global $wpdb;

        $limit = $request->get_param('limit');
        $team_id = $request->get_param('team_id');

        $where = "WHERE event_date >= %s";
        $params = array(current_time('Y-m-d'));

        if ($team_id) {
            $where .= " AND team_id = %d";
            $params[] = $team_id;
        }

        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*, t.name as team_name FROM {$wpdb->prefix}bkgt_events e
             LEFT JOIN {$wpdb->prefix}bkgt_teams t ON e.team_id = t.id
             $where ORDER BY e.event_date ASC LIMIT %d",
            array_merge($params, array($limit))
        ));

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $events,
        ), 200);
    }

    /**
     * Document endpoint handlers
     */
    public function get_documents($request) {
        global $wpdb;

        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $type = $request->get_param('type');
        $search = $request->get_param('search');
        $year = $request->get_param('year');

        $offset = ($page - 1) * $per_page;

        $where = "WHERE 1=1";
        $params = array();

        if ($type) {
            $where .= " AND type = %s";
            $params[] = $type;
        }

        if ($search) {
            $where .= " AND (title LIKE %s OR description LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        if ($year) {
            $where .= " AND YEAR(upload_date) = %d";
            $params[] = $year;
        }

        $total_query = "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_documents $where";
        $total = $wpdb->get_var($wpdb->prepare($total_query, $params));

        $documents_query = "SELECT * FROM {$wpdb->prefix}bkgt_documents $where ORDER BY upload_date DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $documents = $wpdb->get_results($wpdb->prepare($documents_query, $params));

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'documents' => $documents,
                'pagination' => $this->prepare_pagination_data($page, $per_page, $total),
            ),
        ), 200);
    }

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
        $this->log_document_download($document_id);

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

    public function get_document_types($request) {
        $types = array(
            array('value' => 'rulebook', 'label' => __('Rulebook', 'bkgt-api')),
            array('value' => 'minutes', 'label' => __('Meeting Minutes', 'bkgt-api')),
            array('value' => 'financial', 'label' => __('Financial Reports', 'bkgt-api')),
            array('value' => 'other', 'label' => __('Other Documents', 'bkgt-api')),
        );

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $types,
        ), 200);
    }

    /**
     * Statistics endpoint handlers
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
            'active_teams' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT team_id) FROM {$wpdb->prefix}bkgt_events WHERE event_date >= %s",
                date('Y-m-d', strtotime('-30 days'))
            )),
        );

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $stats,
        ), 200);
    }

    public function get_team_stats($request) {
        global $wpdb;

        $team_id = $request->get_param('team_id');
        $season = $request->get_param('season');

        if ($team_id) {
            $where = "WHERE team_id = %d";
            $params = array($team_id);

            if ($season) {
                $where .= " AND YEAR(event_date) = %d";
                $params[] = $season;
            }

            $stats = array(
                'players_count' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players WHERE team_id = %d",
                    $team_id
                )),
                'events_count' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_events $where",
                    $params
                )),
                'upcoming_events' => (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_events WHERE team_id = %d AND event_date >= %s",
                    $team_id, current_time('Y-m-d')
                )),
            );
        } else {
            // Get stats for all teams
            $where = "WHERE 1=1";
            $params = array();

            if ($season) {
                $where .= " AND YEAR(e.event_date) = %d";
                $params[] = $season;
            }

            $teams_stats = $wpdb->get_results($wpdb->prepare(
                "SELECT t.id, t.name,
                        COUNT(DISTINCT p.id) as players_count,
                        COUNT(DISTINCT e.id) as events_count
                 FROM {$wpdb->prefix}bkgt_teams t
                 LEFT JOIN {$wpdb->prefix}bkgt_players p ON t.id = p.team_id
                 LEFT JOIN {$wpdb->prefix}bkgt_events e ON t.id = e.team_id $where
                 GROUP BY t.id, t.name
                 ORDER BY t.name ASC",
                $params
            ));

            $stats = array('teams' => $teams_stats);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $stats,
        ), 200);
    }

    public function get_player_stats($request) {
        // Placeholder for player statistics
        // This would be expanded based on available player performance data
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'message' => __('Player statistics not yet implemented.', 'bkgt-api'),
            ),
        ), 200);
    }

    /**
     * User endpoint handlers
     */
    public function get_user_profile($request) {
        $user = wp_get_current_user();

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $this->prepare_user_data($user),
        ), 200);
    }

    public function update_user_profile($request) {
        $user_id = get_current_user_id();
        $display_name = $request->get_param('display_name');
        $email = $request->get_param('email');
        $first_name = $request->get_param('first_name');
        $last_name = $request->get_param('last_name');

        $update_data = array();

        if ($display_name) {
            $update_data['display_name'] = $display_name;
        }

        if ($email) {
            $update_data['user_email'] = $email;
        }

        if ($first_name) {
            $update_data['first_name'] = $first_name;
        }

        if ($last_name) {
            $update_data['last_name'] = $last_name;
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

    public function get_user_api_keys($request) {
        $auth = new BKGT_API_Auth();
        $user_id = get_current_user_id();

        $api_keys = $auth->get_user_api_keys($user_id);

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $api_keys,
        ), 200);
    }

    public function create_api_key($request) {
        $auth = new BKGT_API_Auth();
        $user_id = get_current_user_id();
        $name = $request->get_param('name');
        $permissions = $request->get_param('permissions');

        $api_key = $auth->create_api_key($user_id, $name, $permissions);

        if (!$api_key) {
            return new WP_Error(
                'creation_failed',
                __('Failed to create API key.', 'bkgt-api'),
                array('status' => 500)
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $api_key,
            'message' => __('API key created successfully. Please save the secret key securely.', 'bkgt-api'),
        ), 201);
    }

    public function revoke_api_key($request) {
        $auth = new BKGT_API_Auth();
        $user_id = get_current_user_id();
        $key_id = $request->get_param('id');

        $result = $auth->revoke_api_key($key_id, $user_id);

        if (!$result) {
            return new WP_Error(
                'revoke_failed',
                __('Failed to revoke API key.', 'bkgt-api'),
                array('status' => 500)
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('API key revoked successfully.', 'bkgt-api'),
        ), 200);
    }

    /**
     * Admin endpoint handlers
     */
    public function get_admin_stats($request) {
        global $wpdb;

        $stats = array(
            'total_requests' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_api_logs"),
            'requests_today' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_api_logs WHERE DATE(created_at) = %s",
                current_time('Y-m-d')
            )),
            'active_api_keys' => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_api_keys WHERE is_active = 1"
            ),
            'unique_users' => (int) $wpdb->get_var(
                "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}bkgt_api_logs WHERE user_id IS NOT NULL"
            ),
            'error_rate' => $this->calculate_error_rate(),
        );

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $stats,
        ), 200);
    }

    public function get_api_logs($request) {
        global $wpdb;

        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $start_date = $request->get_param('start_date');
        $end_date = $request->get_param('end_date');
        $endpoint = $request->get_param('endpoint');
        $method = $request->get_param('method');

        $offset = ($page - 1) * $per_page;

        $where = "WHERE 1=1";
        $params = array();

        if ($start_date) {
            $where .= " AND DATE(created_at) >= %s";
            $params[] = $start_date;
        }

        if ($end_date) {
            $where .= " AND DATE(created_at) <= %s";
            $params[] = $end_date;
        }

        if ($endpoint) {
            $where .= " AND endpoint LIKE %s";
            $params[] = '%' . $wpdb->esc_like($endpoint) . '%';
        }

        if ($method) {
            $where .= " AND method = %s";
            $params[] = $method;
        }

        $total_query = "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_api_logs $where";
        $total = $wpdb->get_var($wpdb->prepare($total_query, $params));

        $logs_query = "SELECT l.*, u.display_name FROM {$wpdb->prefix}bkgt_api_logs l
                      LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
                      $where ORDER BY l.created_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $logs = $wpdb->get_results($wpdb->prepare($logs_query, $params));

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'logs' => $logs,
                'pagination' => $this->prepare_pagination_data($page, $per_page, $total),
            ),
        ), 200);
    }

    public function get_all_api_keys($request) {
        global $wpdb;

        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $user_id = $request->get_param('user_id');
        $is_active = $request->get_param('is_active');

        $offset = ($page - 1) * $per_page;

        $where = "WHERE 1=1";
        $params = array();

        if ($user_id) {
            $where .= " AND created_by = %d";
            $params[] = $user_id;
        }

        if (isset($is_active)) {
            $where .= " AND is_active = %d";
            $params[] = $is_active ? 1 : 0;
        }

        $total_query = "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_api_keys $where";
        $total = $wpdb->get_var($wpdb->prepare($total_query, $params));

        $keys_query = "SELECT k.*, u.display_name as created_by_name FROM {$wpdb->prefix}bkgt_api_keys k
                      LEFT JOIN {$wpdb->users} u ON k.created_by = u.ID
                      $where ORDER BY k.created_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $keys = $wpdb->get_results($wpdb->prepare($keys_query, $params));

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'api_keys' => $keys,
                'pagination' => $this->prepare_pagination_data($page, $per_page, $total),
            ),
        ), 200);
    }

    public function get_api_settings($request) {
        $settings = array(
            'jwt_expiry' => get_option('jwt_expiry', 900),
            'refresh_token_expiry' => get_option('refresh_token_expiry', 604800),
            'api_rate_limit' => get_option('api_rate_limit', 100),
            'api_rate_limit_window' => get_option('api_rate_limit_window', 60),
            'cors_allowed_origins' => get_option('cors_allowed_origins', array()),
            'api_debug_mode' => get_option('api_debug_mode', false),
            'api_logging_enabled' => get_option('api_logging_enabled', true),
            'api_cache_enabled' => get_option('api_cache_enabled', true),
            'api_cache_ttl' => get_option('api_cache_ttl', 300),
        );

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $settings,
        ), 200);
    }

    public function update_api_settings($request) {
        $settings = array(
            'jwt_expiry',
            'refresh_token_expiry',
            'api_rate_limit',
            'cors_allowed_origins',
            'api_debug_mode',
            'api_logging_enabled',
        );

        foreach ($settings as $setting) {
            if ($request->has_param($setting)) {
                update_option($setting, $request->get_param($setting));
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('API settings updated successfully.', 'bkgt-api'),
        ), 200);
    }

    /**
     * Validation methods
     */
    public function validate_token($request) {
        $auth = new BKGT_API_Auth();
        $headers = BKGT_API_Auth::get_auth_headers();

        // Try JWT token first
        if (isset($headers['authorization'])) {
            $token = BKGT_API_Auth::extract_token($headers['authorization']);
            if ($token) {
                $payload = $auth->validate_token($token);
                if ($payload) {
                    wp_set_current_user($payload['user_id']);
                    return true;
                }
            }
        }

        // Fallback to API key
        if (isset($headers['x-api-key'])) {
            $user = $auth->get_user_from_api_key($headers['x-api-key']);
            if ($user) {
                wp_set_current_user($user->ID);
                return true;
            }
        }

        return new WP_Error(
            'authentication_required',
            __('Authentication required.', 'bkgt-api'),
            array('status' => 401)
        );
    }

    public function validate_admin_token($request) {
        $result = $this->validate_token($request);

        if (is_wp_error($result)) {
            return $result;
        }

        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'insufficient_permissions',
                __('Admin permissions required.', 'bkgt-api'),
                array('status' => 403)
            );
        }

        return true;
    }

    public function validate_required($value, $request, $param) {
        if (empty($value)) {
            return new WP_Error(
                'missing_required_param',
                sprintf(__('The %s parameter is required.', 'bkgt-api'), $param),
                array('status' => 400)
            );
        }
        return true;
    }

    public function validate_numeric($value, $request, $param) {
        if (!is_numeric($value) || $value <= 0) {
            return new WP_Error(
                'invalid_numeric_param',
                sprintf(__('The %s parameter must be a positive number.', 'bkgt-api'), $param),
                array('status' => 400)
            );
        }
        return true;
    }

    public function validate_date($value, $request, $param) {
        $date = date_create($value);
        if (!$date) {
            return new WP_Error(
                'invalid_date_param',
                sprintf(__('The %s parameter must be a valid date.', 'bkgt-api'), $param),
                array('status' => 400)
            );
        }
        return true;
    }

    public function validate_season($value, $request, $param) {
        if (!preg_match('/^\d{4}$/', $value) || $value < 2000 || $value > date('Y') + 1) {
            return new WP_Error(
                'invalid_season_param',
                sprintf(__('The %s parameter must be a valid year.', 'bkgt-api'), $param),
                array('status' => 400)
            );
        }
        return true;
    }

    public function validate_display_name($value, $request, $param) {
        if (strlen($value) < 2 || strlen($value) > 50) {
            return new WP_Error(
                'invalid_display_name',
                __('Display name must be between 2 and 50 characters.', 'bkgt-api'),
                array('status' => 400)
            );
        }
        return true;
    }

    public function validate_email_unique($value, $request, $param) {
        $user_id = get_current_user_id();
        if (email_exists($value) && email_exists($value) != $user_id) {
            return new WP_Error(
                'email_exists',
                __('This email address is already registered.', 'bkgt-api'),
                array('status' => 400)
            );
        }
        return true;
    }

    /**
     * Helper methods
     */
    private function prepare_user_data($user) {
        return array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'roles' => $user->roles,
            'registered_date' => $user->user_registered,
        );
    }

    private function prepare_pagination_data($page, $per_page, $total) {
        return array(
            'page' => (int) $page,
            'per_page' => (int) $per_page,
            'total' => (int) $total,
            'total_pages' => ceil($total / $per_page),
        );
    }

    private function calculate_error_rate() {
        global $wpdb;

        $total_requests = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_api_logs");
        if (!$total_requests) {
            return 0;
        }

        $error_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_api_logs WHERE response_code >= %d",
            400
        ));

        return round(($error_requests / $total_requests) * 100, 2);
    }

    private function log_document_download($document_id) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'bkgt_document_downloads',
            array(
                'document_id' => $document_id,
                'user_id' => get_current_user_id(),
                'downloaded_at' => current_time('mysql'),
                'ip_address' => $this->get_client_ip(),
            ),
            array('%d', '%d', '%s', '%s')
        );
    }

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
     * Equipment endpoint handlers
     */
    public function get_equipment($request) {
        global $wpdb;

        $page = $request->get_param('page') ?: 1;
        $per_page = min($request->get_param('per_page') ?: 10, 100);
        $manufacturer_id = $request->get_param('manufacturer_id');
        $item_type_id = $request->get_param('item_type_id');
        $condition_status = $request->get_param('condition_status');
        $assigned_to = $request->get_param('assigned_to');
        $location_id = $request->get_param('location_id');
        $search = $request->get_param('search');

        $offset = ($page - 1) * $per_page;

        // Base query
        $query = "SELECT SQL_CALC_FOUND_ROWS
            i.id, i.unique_identifier, i.title, i.manufacturer_id, i.item_type_id,
            i.storage_location, i.condition_status, i.condition_date, i.condition_reason,
            i.sticker_code, i.created_at, i.updated_at,
            m.name as manufacturer_name,
            it.name as item_type_name,
            a.assignment_type, a.assignee_id, a.assignee_name, a.assignment_date, a.due_date
        FROM {$wpdb->prefix}bkgt_inventory_items i
        LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
        LEFT JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
        LEFT JOIN {$wpdb->prefix}bkgt_inventory_assignments a ON i.id = a.item_id AND a.return_date IS NULL";

        $where = " WHERE 1=1";
        $params = array();

        if ($manufacturer_id) {
            $where .= " AND i.manufacturer_id = %d";
            $params[] = $manufacturer_id;
        }

        if ($item_type_id) {
            $where .= " AND i.item_type_id = %d";
            $params[] = $item_type_id;
        }

        if ($condition_status) {
            $where .= " AND i.condition_status = %s";
            $params[] = $condition_status;
        }

        if ($assigned_to) {
            $where .= " AND a.assignment_type = %s";
            $params[] = $assigned_to;
        }

        if ($location_id) {
            $where .= " AND a.location_id = %d";
            $params[] = $location_id;
        }

        if ($search) {
            $where .= " AND (i.title LIKE %s OR i.unique_identifier LIKE %s OR i.sticker_code LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $query .= $where . " ORDER BY i.created_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $items = $wpdb->get_results($wpdb->prepare($query, $params));
        $total = $wpdb->get_var("SELECT FOUND_ROWS()");

        $formatted_items = array();
        foreach ($items as $item) {
            $formatted_items[] = $this->format_equipment_item($item);
        }

        return new WP_REST_Response(array(
            'equipment' => $formatted_items,
            'total' => (int) $total,
            'page' => (int) $page,
            'per_page' => (int) $per_page,
            'total_pages' => ceil($total / $per_page),
        ), 200);
    }

    public function get_equipment_item($request) {
        global $wpdb;

        $id = $request->get_param('id');

        $query = "SELECT
            i.id, i.unique_identifier, i.title, i.manufacturer_id, i.item_type_id,
            i.storage_location, i.condition_status, i.condition_date, i.condition_reason,
            i.sticker_code, i.created_at, i.updated_at,
            m.name as manufacturer_name,
            it.name as item_type_name,
            a.assignment_type, a.assignee_id, a.assignee_name, a.assignment_date, a.due_date
        FROM {$wpdb->prefix}bkgt_inventory_items i
        LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
        LEFT JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
        LEFT JOIN {$wpdb->prefix}bkgt_inventory_assignments a ON i.id = a.item_id AND a.return_date IS NULL
        WHERE i.id = %d";

        $item = $wpdb->get_row($wpdb->prepare($query, $id));

        if (!$item) {
            return new WP_Error('equipment_not_found', __('Equipment item not found.', 'bkgt-api'), array('status' => 404));
        }

        return new WP_REST_Response(array(
            'equipment' => $this->format_equipment_item($item),
        ), 200);
    }

    public function create_equipment_item($request) {
        global $wpdb;

        $manufacturer_id = $request->get_param('manufacturer_id');
        $item_type_id = $request->get_param('item_type_id');
        $title = $request->get_param('title');
        $storage_location = $request->get_param('storage_location');
        $sticker_code = $request->get_param('sticker_code');

        // Generate unique identifier
        $unique_identifier = BKGT_Inventory_Item::generate_unique_identifier($manufacturer_id, $item_type_id);

        if (!$unique_identifier) {
            return new WP_Error('invalid_manufacturer_or_type', __('Invalid manufacturer or item type.', 'bkgt-api'), array('status' => 400));
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'bkgt_inventory_items',
            array(
                'unique_identifier' => $unique_identifier,
                'manufacturer_id' => $manufacturer_id,
                'item_type_id' => $item_type_id,
                'title' => $title,
                'storage_location' => $storage_location,
                'sticker_code' => $sticker_code,
            ),
            array('%s', '%d', '%d', '%s', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create equipment item.', 'bkgt-api'), array('status' => 500));
        }

        $item_id = $wpdb->insert_id;

        // Get the created item
        $request->set_param('id', $item_id);
        return $this->get_equipment_item($request);
    }

    public function update_equipment_item($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $title = $request->get_param('title');
        $condition_status = $request->get_param('condition_status');
        $condition_reason = $request->get_param('condition_reason');
        $storage_location = $request->get_param('storage_location');
        $sticker_code = $request->get_param('sticker_code');

        $update_data = array();
        $update_format = array();

        if ($title !== null) {
            $update_data['title'] = $title;
            $update_format[] = '%s';
        }

        if ($condition_status !== null) {
            $update_data['condition_status'] = $condition_status;
            $update_format[] = '%s';
            $update_data['condition_date'] = current_time('mysql');
            $update_format[] = '%s';
        }

        if ($condition_reason !== null) {
            $update_data['condition_reason'] = $condition_reason;
            $update_format[] = '%s';
        }

        if ($storage_location !== null) {
            $update_data['storage_location'] = $storage_location;
            $update_format[] = '%s';
        }

        if ($sticker_code !== null) {
            $update_data['sticker_code'] = $sticker_code;
            $update_format[] = '%s';
        }

        if (empty($update_data)) {
            return new WP_Error('no_updates', __('No valid fields to update.', 'bkgt-api'), array('status' => 400));
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'bkgt_inventory_items',
            $update_data,
            array('id' => $id),
            $update_format,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update equipment item.', 'bkgt-api'), array('status' => 500));
        }

        return $this->get_equipment_item($request);
    }

    public function delete_equipment_item($request) {
        global $wpdb;

        $id = $request->get_param('id');

        // Check if item exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bkgt_inventory_items WHERE id = %d",
            $id
        ));

        if (!$exists) {
            return new WP_Error('equipment_not_found', __('Equipment item not found.', 'bkgt-api'), array('status' => 404));
        }

        // Check if item is currently assigned
        $assigned = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bkgt_inventory_assignments WHERE item_id = %d AND return_date IS NULL",
            $id
        ));

        if ($assigned) {
            return new WP_Error('item_assigned', __('Cannot delete item that is currently assigned.', 'bkgt-api'), array('status' => 409));
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'bkgt_inventory_items',
            array('id' => $id),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to delete equipment item.', 'bkgt-api'), array('status' => 500));
        }

        return new WP_REST_Response(array(
            'message' => __('Equipment item deleted successfully.', 'bkgt-api'),
        ), 200);
    }

    public function get_manufacturers($request) {
        global $wpdb;

        $manufacturers = $wpdb->get_results(
            "SELECT id, manufacturer_id, name, contact_info, created_at, updated_at
             FROM {$wpdb->prefix}bkgt_manufacturers
             ORDER BY name ASC"
        );

        return new WP_REST_Response(array(
            'manufacturers' => $manufacturers,
        ), 200);
    }

    public function get_manufacturer($request) {
        global $wpdb;

        $id = $request->get_param('id');

        $manufacturer = $wpdb->get_row($wpdb->prepare(
            "SELECT id, manufacturer_id, name, contact_info, created_at, updated_at
             FROM {$wpdb->prefix}bkgt_manufacturers
             WHERE id = %d",
            $id
        ));

        if (!$manufacturer) {
            return new WP_Error('manufacturer_not_found', __('Manufacturer not found.', 'bkgt-api'), array('status' => 404));
        }

        return new WP_REST_Response(array(
            'manufacturer' => $manufacturer,
        ), 200);
    }

    public function get_item_types($request) {
        global $wpdb;

        $item_types = $wpdb->get_results(
            "SELECT id, item_type_id, name, description, custom_fields, created_at, updated_at
             FROM {$wpdb->prefix}bkgt_item_types
             ORDER BY name ASC"
        );

        return new WP_REST_Response(array(
            'types' => $item_types,
        ), 200);
    }

    public function get_item_type($request) {
        global $wpdb;

        $id = $request->get_param('id');

        $item_type = $wpdb->get_row($wpdb->prepare(
            "SELECT id, item_type_id, name, description, custom_fields, created_at, updated_at
             FROM {$wpdb->prefix}bkgt_item_types
             WHERE id = %d",
            $id
        ));

        if (!$item_type) {
            return new WP_Error('item_type_not_found', __('Item type not found.', 'bkgt-api'), array('status' => 404));
        }

        return new WP_REST_Response(array(
            'type' => $item_type,
        ), 200);
    }

    public function assign_equipment($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $assignment_type = $request->get_param('assignment_type');
        $assignee_id = $request->get_param('assignee_id');
        $due_date = $request->get_param('due_date');
        $notes = $request->get_param('notes');

        // Validate assignment type requirements
        if (in_array($assignment_type, array('individual', 'team')) && !$assignee_id) {
            return new WP_Error('assignee_required', __('Assignee ID is required for individual and team assignments.', 'bkgt-api'), array('status' => 400));
        }

        // Use BKGT Assignment class for the actual assignment
        $result = false;

        switch ($assignment_type) {
            case 'individual':
                $result = BKGT_Assignment::assign_to_individual($id, $assignee_id);
                break;
            case 'team':
                $result = BKGT_Assignment::assign_to_team($id, $assignee_id);
                break;
            case 'club':
                $result = BKGT_Assignment::assign_to_club($id);
                break;
        }

        if (is_wp_error($result)) {
            return $result;
        }

        // Update due date and notes if provided
        if ($due_date || $notes) {
            $update_data = array();
            $update_format = array();

            if ($due_date) {
                $update_data['due_date'] = $due_date;
                $update_format[] = '%s';
            }

            if ($notes) {
                $update_data['notes'] = $notes;
                $update_format[] = '%s';
            }

            $wpdb->update(
                $wpdb->prefix . 'bkgt_inventory_assignments',
                $update_data,
                array('item_id' => $id, 'return_date' => null),
                $update_format,
                array('%d')
            );
        }

        return new WP_REST_Response(array(
            'message' => __('Equipment assigned successfully.', 'bkgt-api'),
        ), 200);
    }

    public function return_equipment($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $return_date = $request->get_param('return_date') ?: current_time('Y-m-d');
        $condition_status = $request->get_param('condition_status');
        $notes = $request->get_param('notes');

        // Find the active assignment
        $assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bkgt_inventory_assignments
             WHERE item_id = %d AND return_date IS NULL
             ORDER BY assignment_date DESC LIMIT 1",
            $id
        ));

        if (!$assignment) {
            return new WP_Error('no_active_assignment', __('No active assignment found for this equipment.', 'bkgt-api'), array('status' => 404));
        }

        // Update the assignment with return information
        $update_data = array('return_date' => $return_date);
        $update_format = array('%s');

        if ($notes) {
            $update_data['notes'] = $notes;
            $update_format[] = '%s';
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'bkgt_inventory_assignments',
            $update_data,
            array('id' => $assignment->id),
            $update_format,
            array('%d')
        );

        // Update item condition if provided
        if ($condition_status) {
            $wpdb->update(
                $wpdb->prefix . 'bkgt_inventory_items',
                array(
                    'condition_status' => $condition_status,
                    'condition_date' => current_time('mysql'),
                ),
                array('id' => $id),
                array('%s', '%s'),
                array('%d')
            );
        }

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to return equipment.', 'bkgt-api'), array('status' => 500));
        }

        return new WP_REST_Response(array(
            'message' => __('Equipment returned successfully.', 'bkgt-api'),
        ), 200);
    }

    public function get_locations($request) {
        global $wpdb;

        $locations = $wpdb->get_results(
            "SELECT id, name, slug, location_type, address, contact_person,
                    contact_phone, contact_email, capacity, is_active,
                    created_at, updated_at
             FROM {$wpdb->prefix}bkgt_locations
             WHERE is_active = 1
             ORDER BY name ASC"
        );

        return new WP_REST_Response(array(
            'locations' => $locations,
        ), 200);
    }

    public function get_location($request) {
        global $wpdb;

        $id = $request->get_param('id');

        $location = $wpdb->get_row($wpdb->prepare(
            "SELECT id, name, slug, location_type, address, contact_person,
                    contact_phone, contact_email, capacity, is_active,
                    created_at, updated_at
             FROM {$wpdb->prefix}bkgt_locations
             WHERE id = %d AND is_active = 1",
            $id
        ));

        if (!$location) {
            return new WP_Error('location_not_found', __('Location not found.', 'bkgt-api'), array('status' => 404));
        }

        return new WP_REST_Response(array(
            'location' => $location,
        ), 200);
    }

    public function preview_equipment_identifier($request) {
        $manufacturer_id = $request->get_param('manufacturer_id');
        $item_type_id = $request->get_param('item_type_id');

        $unique_identifier = BKGT_Inventory_Item::generate_unique_identifier($manufacturer_id, $item_type_id);

        if (!$unique_identifier) {
            return new WP_Error('invalid_manufacturer_or_type', __('Invalid manufacturer or item type.', 'bkgt-api'), array('status' => 400));
        }

        return new WP_REST_Response(array(
            'unique_identifier' => $unique_identifier,
            'manufacturer_id' => $manufacturer_id,
            'item_type_id' => $item_type_id,
        ), 200);
    }

    public function get_equipment_analytics_overview($request) {
        global $wpdb;

        // Get total items count
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_inventory_items");

        // Get items by condition
        $condition_stats = $wpdb->get_results(
            "SELECT condition_status, COUNT(*) as count
             FROM {$wpdb->prefix}bkgt_inventory_items
             GROUP BY condition_status",
            OBJECT_K
        );

        // Get items by type
        $type_stats = $wpdb->get_results(
            "SELECT it.name, COUNT(*) as count
             FROM {$wpdb->prefix}bkgt_inventory_items i
             JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
             GROUP BY it.id, it.name
             ORDER BY count DESC",
            OBJECT_K
        );

        // Get assignment stats
        $assigned_count = $wpdb->get_var(
            "SELECT COUNT(DISTINCT item_id)
             FROM {$wpdb->prefix}bkgt_inventory_assignments
             WHERE return_date IS NULL"
        );

        $overdue_count = $wpdb->get_var(
            "SELECT COUNT(DISTINCT item_id)
             FROM {$wpdb->prefix}bkgt_inventory_assignments
             WHERE return_date IS NULL AND due_date < CURDATE()"
        );

        // Get maintenance needed (items needing repair)
        $maintenance_needed = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_inventory_items
             WHERE condition_status = 'needs_repair'"
        );

        return new WP_REST_Response(array(
            'total_items' => (int) $total_items,
            'items_by_condition' => array_map('intval', (array) $condition_stats),
            'items_by_type' => array_map('intval', (array) $type_stats),
            'assignment_stats' => array(
                'assigned' => (int) $assigned_count,
                'available' => (int) ($total_items - $assigned_count),
                'overdue' => (int) $overdue_count,
            ),
            'maintenance_needed' => (int) $maintenance_needed,
        ), 200);
    }

    public function get_equipment_usage_analytics($request) {
        global $wpdb;

        $start_date = $request->get_param('start_date');
        $end_date = $request->get_param('end_date');

        $where_clause = "";
        $params = array();

        if ($start_date) {
            $where_clause .= " AND assignment_date >= %s";
            $params[] = $start_date;
        }

        if ($end_date) {
            $where_clause .= " AND assignment_date <= %s";
            $params[] = $end_date;
        }

        $usage_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT
                it.name as item_type,
                COUNT(a.id) as total_assignments,
                AVG(DATEDIFF(COALESCE(a.return_date, CURDATE()), a.assignment_date)) as avg_assignment_duration_days,
                (SELECT assignee_name FROM {$wpdb->prefix}bkgt_inventory_assignments
                 WHERE item_type_id = it.id $where_clause
                 GROUP BY assignee_name ORDER BY COUNT(*) DESC LIMIT 1) as most_assigned_to,
                (SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_inventory_items
                 WHERE item_type_id = it.id AND condition_date >= %s AND condition_date <= %s) as condition_changes
             FROM {$wpdb->prefix}bkgt_inventory_assignments a
             JOIN {$wpdb->prefix}bkgt_inventory_items i ON a.item_id = i.id
             JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
             WHERE 1=1 $where_clause
             GROUP BY it.id, it.name
             ORDER BY total_assignments DESC",
            array_merge($params, $params, array($start_date ?: '1970-01-01', $end_date ?: '2038-01-19'))
        ));

        return new WP_REST_Response(array(
            'usage_stats' => $usage_stats,
        ), 200);
    }

    /**
     * Helper method to format equipment item data
     */
    private function format_equipment_item($item) {
        return array(
            'id' => (int) $item->id,
            'unique_identifier' => $item->unique_identifier,
            'title' => $item->title,
            'manufacturer_id' => (int) $item->manufacturer_id,
            'manufacturer_name' => $item->manufacturer_name,
            'item_type_id' => (int) $item->item_type_id,
            'item_type_name' => $item->item_type_name,
            'storage_location' => $item->storage_location,
            'condition_status' => $item->condition_status,
            'condition_date' => $item->condition_date,
            'condition_reason' => $item->condition_reason,
            'sticker_code' => $item->sticker_code,
            'assignment_type' => $item->assignment_type,
            'assigned_to_id' => $item->assignee_id ? (int) $item->assignee_id : null,
            'assigned_to_name' => $item->assignee_name,
            'assignment_date' => $item->assignment_date,
            'due_date' => $item->due_date,
            'created_date' => $item->created_at,
            'updated_date' => $item->updated_at,
        );
    }
}