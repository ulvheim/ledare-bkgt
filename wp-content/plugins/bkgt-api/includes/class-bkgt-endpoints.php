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
        error_log('BKGT API: Registering all routes');
        $this->register_equipment_routes();
        $this->register_auth_routes();
        $this->register_health_routes();
        $this->register_team_routes();
        $this->register_player_routes();
        $this->register_event_routes();
        $this->register_document_routes();
        $this->register_stats_routes();
        $this->register_user_routes();
        $this->register_admin_routes();
        $this->register_diagnostic_routes();
        $this->register_docs_routes();
        $this->register_update_routes();
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
     * Register health check routes
     */
    private function register_health_routes() {
        register_rest_route($this->namespace, '/health', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_health_status'),
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
     * Get pagination arguments for REST API endpoints
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
     * Register equipment routes
     */
    public function register_equipment_routes() {
        // Search equipment route
        register_rest_route($this->namespace, '/equipment/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'search_equipment'),
            'permission_callback' => '__return_true',
            'args' => array(
                'q' => array(
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'limit' => array(
                    'type' => 'integer',
                    'default' => 20,
                ),
                'fields' => array(
                    'type' => 'string',
                    'default' => 'id,unique_identifier,title',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // Bulk operations routes
        register_rest_route($this->namespace, '/equipment/bulk', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'bulk_equipment_operation'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'operation' => array(
                        'type' => 'string',
                        'required' => true,
                        'enum' => array('delete', 'export'),
                    ),
                    'item_ids' => array(
                        'type' => 'array',
                        'required' => true,
                        'items' => array(
                            'type' => 'integer',
                        ),
                    ),
                ),
            ),
        ));

        // Equipment locations - Full CRUD
        register_rest_route($this->namespace, '/equipment/locations', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_locations'),
                'permission_callback' => array($this, 'validate_token'),
            ),
        ));

        register_rest_route($this->namespace, '/equipment/locations/(?P<id>\d+)', array(
            array(
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
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'update_location'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'id' => array(
                        'type' => 'integer',
                        'required' => true,
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                    'name' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'description' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                    'address' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                    'capacity' => array(
                        'type' => 'integer',
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                ),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_location'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'id' => array(
                        'type' => 'integer',
                        'required' => true,
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
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

        // Manufacturers endpoint
        register_rest_route($this->namespace, '/equipment/manufacturers', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_manufacturers'),
            'permission_callback' => array($this, 'validate_token'),
        ));

        // Item types endpoint
        register_rest_route($this->namespace, '/equipment/types', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_item_types'),
            'permission_callback' => array($this, 'validate_token'),
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

        register_rest_route($this->namespace, '/equipment/analytics/overview', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_equipment_analytics_overview'),
            'permission_callback' => array($this, 'validate_token'),
        ));

        // Main equipment CRUD endpoints - READ ONLY (CREATE/UPDATE/DELETE handled by bkgt-inventory plugin)
        register_rest_route($this->namespace, '/equipment', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_equipment'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'page' => array(
                        'type' => 'integer',
                        'default' => 1,
                        'minimum' => 1,
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                    'per_page' => array(
                        'type' => 'integer',
                        'default' => 10,
                        'minimum' => 1,
                        'maximum' => 100,
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                    'manufacturer_id' => array(
                        'type' => 'integer',
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                    'item_type_id' => array(
                        'type' => 'integer',
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                    'location_id' => array(
                        'type' => 'integer',
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                    'condition_status' => array(
                        'type' => 'string',
                        'enum' => array('normal', 'needs_repair', 'repaired', 'reported_lost', 'scrapped'),
                    ),
                    'assignment_status' => array(
                        'type' => 'string',
                        'enum' => array('assigned', 'available', 'overdue'),
                    ),
                    'search' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'orderby' => array(
                        'type' => 'string',
                        'enum' => array('id', 'unique_identifier', 'created_at', 'updated_at'),
                        'default' => 'id',
                    ),
                    'order' => array(
                        'type' => 'string',
                        'enum' => array('asc', 'desc'),
                        'default' => 'desc',
                    ),
                ),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_equipment_item'),
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
                    'storage_location' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'size' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'location_id' => array(
                        'type' => 'integer',
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                    'purchase_date' => array(
                        'type' => 'string',
                        'format' => 'date',
                        'validate_callback' => array($this, 'validate_date'),
                    ),
                    'purchase_price' => array(
                        'type' => 'number',
                    ),
                    'warranty_expiry' => array(
                        'type' => 'string',
                        'format' => 'date',
                        'validate_callback' => array($this, 'validate_date'),
                    ),
                    'condition_status' => array(
                        'type' => 'string',
                        'enum' => array('normal', 'needs_repair', 'repaired', 'reported_lost', 'scrapped'),
                        'default' => 'normal',
                    ),
                    'condition_date' => array(
                        'type' => 'string',
                        'format' => 'date',
                        'validate_callback' => array($this, 'validate_date'),
                    ),
                    'condition_reason' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'sticker_code' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'notes' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                ),
            ),
        ));

        // Individual equipment item - READ ONLY (UPDATE/DELETE handled by bkgt-inventory plugin)
        register_rest_route($this->namespace, '/equipment/(?P<id>\d+)', array(
            array(
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
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'update_equipment_item'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'id' => array(
                        'type' => 'integer',
                        'required' => true,
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                    // Immutable fields - allowed in request but ignored (for frontend compatibility)
                    'manufacturer_id' => array(
                        'type' => 'integer',
                    ),
                    'item_type_id' => array(
                        'type' => 'integer',
                    ),
                    'unique_identifier' => array(
                        'type' => 'string',
                    ),
                    'sticker_code' => array(
                        'type' => 'string',
                    ),
                    // Mutable fields
                    'title' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'storage_location' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'size' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'location_id' => array(
                        'type' => 'integer',
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                    'purchase_date' => array(
                        'type' => 'string',
                        'format' => 'date',
                        'validate_callback' => array($this, 'validate_date'),
                    ),
                    'purchase_price' => array(
                        'type' => 'number',
                    ),
                    'warranty_expiry' => array(
                        'type' => 'string',
                        'format' => 'date',
                        'validate_callback' => array($this, 'validate_date'),
                    ),
                    'condition_status' => array(
                        'type' => 'string',
                        'enum' => array('normal', 'needs_repair', 'repaired', 'reported_lost', 'scrapped'),
                    ),
                    'condition_date' => array(
                        'type' => 'string',
                        'format' => 'date',
                        'validate_callback' => array($this, 'validate_date'),
                    ),
                    'condition_reason' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'notes' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                ),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_equipment_item'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'id' => array(
                        'type' => 'integer',
                        'required' => true,
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                ),
            ),
        ));

        // Equipment assignment routes
        register_rest_route($this->namespace, '/equipment/(?P<id>\d+)/assignment', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_equipment_assignment'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'id' => array(
                        'type' => 'integer',
                        'required' => true,
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                ),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'assign_equipment'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'id' => array(
                        'type' => 'integer',
                        'required' => true,
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                    'assignment_type' => array(
                        'type' => 'string',
                        'required' => true,
                        'enum' => array('club', 'team', 'individual'),
                        'validate_callback' => array($this, 'validate_required'),
                    ),
                    'assignee_id' => array(
                        'type' => 'integer',
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                    'due_date' => array(
                        'type' => 'string',
                        'format' => 'date',
                        'validate_callback' => array($this, 'validate_date'),
                    ),
                    'notes' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'unassign_equipment'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'id' => array(
                        'type' => 'integer',
                        'required' => true,
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                ),
            ),
        ));

        // Search equipment route - commented out for testing
        // register_rest_route($this->namespace, '/equipment/search', array(
        //     'methods' => 'GET',
        //     'callback' => array($this, 'get_health_status'),
        //     'permission_callback' => '__return_true',
        // ));

        // Test route
        register_rest_route($this->namespace, '/equipment/test', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_health_status'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'operation' => array(
                        'type' => 'string',
                        'required' => true,
                        'enum' => array('delete', 'export'),
                        'validate_callback' => array($this, 'validate_required'),
                    ),
                    'item_ids' => array(
                        'type' => 'array',
                        'required' => true,
                        'items' => array(
                            'type' => 'integer',
                            'validate_callback' => array($this, 'validate_numeric'),
                        ),
                        'validate_callback' => array($this, 'validate_required'),
                    ),
                ),
            ),
        ));

        // Search equipment route
        register_rest_route($this->namespace, '/equipment/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'search_equipment'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'q' => array(
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'limit' => array(
                    'type' => 'integer',
                    'default' => 20,
                    'minimum' => 1,
                    'maximum' => 100,
                ),
                'fields' => array(
                    'type' => 'string',
                    'default' => 'id,unique_identifier,title',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // Bulk operations routes
        register_rest_route($this->namespace, '/equipment/bulk', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'bulk_equipment_operation'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'operation' => array(
                        'type' => 'string',
                        'required' => true,
                        'enum' => array('delete', 'export'),
                        'validate_callback' => array($this, 'validate_required'),
                    ),
                    'item_ids' => array(
                        'type' => 'array',
                        'required' => true,
                        'items' => array(
                            'type' => 'integer',
                            'validate_callback' => array($this, 'validate_numeric'),
                        ),
                        'validate_callback' => array($this, 'validate_required'),
                    ),
                ),
            ),
        ));
    }

    /**
     * Register statistics routes
     */
    private function register_stats_routes() {
        // Stats overview
        register_rest_route($this->namespace, '/stats/overview', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_stats_overview'),
            'permission_callback' => array($this, 'validate_token'),
        ));

        // Team statistics
        register_rest_route($this->namespace, '/stats/teams', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_team_stats'),
            'permission_callback' => array($this, 'validate_token'),
        ));

        // Player statistics
        register_rest_route($this->namespace, '/stats/players', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_player_stats'),
            'permission_callback' => array($this, 'validate_token'),
            'args' => array(
                'season' => array(
                    'type' => 'string',
                    'description' => 'Filter by season (YYYY-YYYY)',
                    'validate_callback' => array($this, 'validate_season_format'),
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

        // User Management
        register_rest_route($this->namespace, '/admin/users', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_users'),
                'permission_callback' => array($this, 'validate_admin_token'),
                'args' => $this->get_pagination_args(array(
                    'search' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'role' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'orderby' => array(
                        'type' => 'string',
                        'enum' => array('ID', 'user_login', 'user_email', 'user_registered', 'display_name'),
                        'default' => 'user_registered',
                    ),
                    'order' => array(
                        'type' => 'string',
                        'enum' => array('asc', 'desc'),
                        'default' => 'desc',
                    ),
                )),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_user'),
                'permission_callback' => array($this, 'validate_admin_token'),
                'args' => array(
                    'username' => array(
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_user',
                        'validate_callback' => array($this, 'validate_username_unique'),
                    ),
                    'email' => array(
                        'type' => 'string',
                        'required' => true,
                        'format' => 'email',
                        'sanitize_callback' => 'sanitize_email',
                        'validate_callback' => array($this, 'validate_email_unique'),
                    ),
                    'password' => array(
                        'type' => 'string',
                        'required' => true,
                        'validate_callback' => array($this, 'validate_password_strength'),
                    ),
                    'first_name' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'last_name' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'display_name' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'role' => array(
                        'type' => 'string',
                        'default' => 'subscriber',
                        'validate_callback' => array($this, 'validate_role_exists'),
                    ),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/admin/users/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_user'),
                'permission_callback' => array($this, 'validate_admin_token'),
                'args' => array(
                    'id' => array(
                        'type' => 'integer',
                        'required' => true,
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                ),
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'update_user'),
                'permission_callback' => array($this, 'validate_admin_token'),
                'args' => array(
                    'id' => array(
                        'type' => 'integer',
                        'required' => true,
                        'validate_callback' => array($this, 'validate_numeric'),
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
                    'display_name' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'role' => array(
                        'type' => 'string',
                        'validate_callback' => array($this, 'validate_role_exists'),
                    ),
                ),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_user'),
                'permission_callback' => array($this, 'validate_admin_token'),
                'args' => array(
                    'id' => array(
                        'type' => 'integer',
                        'required' => true,
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                    'reassign' => array(
                        'type' => 'integer',
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                ),
            ),
        ));

        // Role Management
        register_rest_route($this->namespace, '/admin/roles', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_roles'),
                'permission_callback' => array($this, 'validate_admin_token'),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_role'),
                'permission_callback' => array($this, 'validate_admin_token'),
                'args' => array(
                    'role' => array(
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_key',
                        'validate_callback' => array($this, 'validate_role_unique'),
                    ),
                    'display_name' => array(
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => array($this, 'validate_required'),
                    ),
                    'capabilities' => array(
                        'type' => 'object',
                        'default' => array(),
                    ),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/admin/roles/(?P<role>[a-zA-Z0-9_-]+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_role'),
                'permission_callback' => array($this, 'validate_admin_token'),
                'args' => array(
                    'role' => array(
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'update_role'),
                'permission_callback' => array($this, 'validate_admin_token'),
                'args' => array(
                    'role' => array(
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_key',
                    ),
                    'display_name' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'capabilities' => array(
                        'type' => 'object',
                    ),
                ),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_role'),
                'permission_callback' => array($this, 'validate_admin_token'),
                'args' => array(
                    'role' => array(
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            ),
        ));

        // User-Role Assignment
        register_rest_route($this->namespace, '/admin/users/(?P<user_id>\d+)/roles', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_user_roles'),
                'permission_callback' => array($this, 'validate_admin_token'),
                'args' => array(
                    'user_id' => array(
                        'type' => 'integer',
                        'required' => true,
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                ),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'add_user_role'),
                'permission_callback' => array($this, 'validate_admin_token'),
                'args' => array(
                    'user_id' => array(
                        'type' => 'integer',
                        'required' => true,
                        'validate_callback' => array($this, 'validate_numeric'),
                    ),
                    'role' => array(
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_key',
                        'validate_callback' => array($this, 'validate_role_exists'),
                    ),
                ),
            ),
        ));

        register_rest_route($this->namespace, '/admin/users/(?P<user_id>\d+)/roles/(?P<role>[a-zA-Z0-9_-]+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'remove_user_role'),
            'permission_callback' => array($this, 'validate_admin_token'),
            'args' => array(
                'user_id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => array($this, 'validate_numeric'),
                    ),
                'role' => array(
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_key',
                ),
            ),
        ));

        /**
         * Players Management (Admin only - for atomic data management)
         */
        register_rest_route($this->namespace, '/admin/players/clear-repopulate', array(
            'methods' => 'POST',
            'callback' => array($this, 'clear_and_repopulate_players'),
            'permission_callback' => array($this, 'validate_admin_token'),
        ));

        /**
         * Teams Management (Admin only - for atomic data management)
         */
        register_rest_route($this->namespace, '/admin/teams/clear-repopulate', array(
            'methods' => 'POST',
            'callback' => array($this, 'clear_and_repopulate_teams'),
            'permission_callback' => array($this, 'validate_admin_token'),
        ));

        /**
         * BKGT Dashboard Endpoints
         */
        register_rest_route($this->namespace, '/admin/dashboard', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_bkgt_dashboard'),
            'permission_callback' => array($this, 'validate_admin_token'),
        ));

        /**
         * BKGT Error Logs Endpoints
         */
        register_rest_route($this->namespace, '/admin/error-logs', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_error_logs'),
                'permission_callback' => array($this, 'validate_admin_token'),
                'args' => array(
                'limit' => array(
                    'type' => 'integer',
                    'default' => 50,
                    'minimum' => 1,
                    'maximum' => 500,
                    'validate_callback' => array($this, 'validate_numeric'),
                ),
                'level' => array(
                    'type' => 'string',
                    'enum' => array('critical', 'error', 'warning', 'info', 'debug'),
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
            ),
        ),
        array(
            'methods' => 'DELETE',
            'callback' => array($this, 'clear_error_logs'),
            'permission_callback' => array($this, 'validate_admin_token'),
        ),
    ));

    register_rest_route($this->namespace, '/admin/error-statistics', array(
        'methods' => 'GET',
        'callback' => array($this, 'get_error_statistics'),
        'permission_callback' => array($this, 'validate_admin_token'),
    ));

    register_rest_route($this->namespace, '/admin/system-health', array(
        'methods' => 'GET',
        'callback' => array($this, 'get_system_health'),
        'permission_callback' => array($this, 'validate_admin_token'),
    ));
}

    /**
     * Register diagnostic routes
     */
    private function register_diagnostic_routes() {
        register_rest_route($this->namespace, '/diagnostic', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_diagnostic_info'),
            'permission_callback' => array($this, 'validate_token'),
        ));
    }

    /**
     * Register documentation routes
     */
    private function register_docs_routes() {
        register_rest_route($this->namespace, '/docs', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_api_documentation'),
            'permission_callback' => '__return_true',
            'args' => array(
                'format' => array(
                    'type' => 'string',
                    'default' => 'html',
                    'enum' => array('html', 'markdown', 'json'),
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        register_rest_route($this->namespace, '/routes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_api_routes'),
            'permission_callback' => '__return_true',
            'args' => array(
                'namespace' => array(
                    'type' => 'string',
                    'default' => 'bkgt/v1',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'detailed' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
            ),
        ));
    }
    /**
     * Validate authentication token (JWT or API Key)
     */
    public function validate_token($request) {
        // Check for API key in header
        $api_key = $request->get_header('X-API-Key');

        if ($api_key) {
            // First check if it's a service API key
            $auth = new BKGT_API_Auth();
            if ($auth->validate_service_api_key($api_key)) {
                // Service authentication successful
                $request->set_param('_bkgt_authenticated', true);
                $request->set_param('_bkgt_user_id', 0); // Service user ID
                $request->set_param('_bkgt_service_auth', true);
                $request->set_param('_bkgt_api_key', 'service');

                return true;
            }

            // Check for regular API key
            global $wpdb;
            $table_name = $wpdb->prefix . 'bkgt_api_keys';

            $key_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE api_key = %s AND is_active = 1",
                $api_key
            ));

            if ($key_data) {
                // Update last used timestamp
                $wpdb->update(
                    $table_name,
                    array('last_used' => current_time('mysql')),
                    array('id' => $key_data->id)
                );

                // Store user info in request for later use
                $request->set_param('_bkgt_authenticated', true);
                $request->set_param('_bkgt_user_id', $key_data->created_by);
                $request->set_param('_bkgt_api_key', $key_data);

                return true;
            }
        }
        
        // Check for JWT token
        $auth_header = $request->get_header('Authorization');
        if ($auth_header && strpos($auth_header, 'Bearer ') === 0) {
            $token = str_replace('Bearer ', '', $auth_header);
            
            $auth = new BKGT_API_Auth();
            $user_id = $auth->validate_token($token);
            
            if ($user_id) {
                $request->set_param('_bkgt_authenticated', true);
                $request->set_param('_bkgt_user_id', $user_id);
                $request->set_param('_bkgt_jwt_token', $token);
                
                return true;
            }
        }
        
        return new WP_Error(
            'authentication_required',
            __('Authentication required.', 'bkgt-api'),
            array('status' => 401)
        );
    }

    /**
     * Get health status
     */
    public function get_health_status($request) {
        $is_service_auth = $request->get_param('_bkgt_service_auth');
        $user_id = $request->get_param('_bkgt_user_id');

        $status = array(
            'status' => 'healthy',
            'timestamp' => current_time('mysql'),
            'version' => BKGT_API_VERSION,
            'authentication' => array(
                'type' => $is_service_auth ? 'service' : 'user',
                'user_id' => $user_id,
            ),
            'database' => $this->check_database_health(),
            'services' => array(
                'wordpress' => $this->check_wordpress_health(),
                'api' => $this->check_api_health(),
            ),
        );

        return new WP_REST_Response($status, 200);
    }

    /**
     * Check database health
     */
    private function check_database_health() {
        global $wpdb;

        $start_time = microtime(true);

        // Simple query to test database connection
        $result = $wpdb->get_var("SELECT 1");

        $query_time = microtime(true) - $start_time;

        return array(
            'status' => $result === '1' ? 'healthy' : 'unhealthy',
            'response_time' => round($query_time * 1000, 2) . 'ms',
        );
    }

    /**
     * Check WordPress health
     */
    private function check_wordpress_health() {
        return array(
            'status' => 'healthy',
            'version' => get_bloginfo('version'),
            'plugins_loaded' => did_action('plugins_loaded') > 0,
        );
    }

    /**
     * Check API health
     */
    private function check_api_health() {
        return array(
            'status' => 'healthy',
            'namespace' => $this->namespace,
            'endpoints_registered' => true,
        );
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
            "SELECT * FROM {$wpdb->prefix}bkgt_players WHERE team_id = %d ORDER BY id ASC LIMIT %d OFFSET %d",
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
                         $where ORDER BY p.id ASC LIMIT %d OFFSET %d";
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

        $events_query = "SELECT e.* FROM {$wpdb->prefix}bkgt_events e
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
            "SELECT e.* FROM {$wpdb->prefix}bkgt_events e
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

        $where = "WHERE event_date >= %s";
        $params = array(current_time('Y-m-d'));

        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT e.* FROM {$wpdb->prefix}bkgt_events e
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
            'equipment_count' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_inventory_items"),
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
     * User Management Handlers
     */
    public function get_users($request) {
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $search = $request->get_param('search');
        $role = $request->get_param('role');
        $orderby = $request->get_param('orderby');
        $order = $request->get_param('order');

        $args = array(
            'number' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'orderby' => $orderby,
            'order' => $order,
            'count_total' => true,
        );

        if ($search) {
            $args['search'] = '*' . esc_attr($search) . '*';
            $args['search_columns'] = array('user_login', 'user_email', 'display_name');
        }

        if ($role) {
            $args['role'] = $role;
        }

        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        $total = $user_query->get_total();

        $user_data = array();
        foreach ($users as $user) {
            $user_data[] = $this->format_user_data($user);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'users' => $user_data,
                'pagination' => $this->prepare_pagination_data($page, $per_page, $total),
            ),
        ), 200);
    }

    public function get_user($request) {
        $user_id = $request->get_param('id');
        $user = get_user_by('ID', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $this->format_user_data($user),
        ), 200);
    }

    public function create_user($request) {
        $username = $request->get_param('username');
        $email = $request->get_param('email');
        $password = $request->get_param('password');
        $first_name = $request->get_param('first_name');
        $last_name = $request->get_param('last_name');
        $display_name = $request->get_param('display_name');
        $role = $request->get_param('role');

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Update user meta
        if ($first_name) {
            update_user_meta($user_id, 'first_name', $first_name);
        }
        if ($last_name) {
            update_user_meta($user_id, 'last_name', $last_name);
        }
        if ($display_name) {
            wp_update_user(array('ID' => $user_id, 'display_name' => $display_name));
        }

        // Set role
        if ($role) {
            $user = new WP_User($user_id);
            $user->set_role($role);
        }

        $user = get_user_by('ID', $user_id);
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $this->format_user_data($user),
            'message' => 'User created successfully',
        ), 201);
    }

    public function update_user($request) {
        $user_id = $request->get_param('id');
        $user = get_user_by('ID', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $update_data = array('ID' => $user_id);

        if ($request->has_param('email')) {
            $update_data['user_email'] = $request->get_param('email');
        }
        if ($request->has_param('first_name')) {
            update_user_meta($user_id, 'first_name', $request->get_param('first_name'));
        }
        if ($request->has_param('last_name')) {
            update_user_meta($user_id, 'last_name', $request->get_param('last_name'));
        }
        if ($request->has_param('display_name')) {
            $update_data['display_name'] = $request->get_param('display_name');
        }

        if (!empty($update_data) && count($update_data) > 1) {
            $result = wp_update_user($update_data);
            if (is_wp_error($result)) {
                return $result;
            }
        }

        // Update role if provided
        if ($request->has_param('role')) {
            $user_obj = new WP_User($user_id);
            $user_obj->set_role($request->get_param('role'));
        }

        $user = get_user_by('ID', $user_id);
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $this->format_user_data($user),
            'message' => 'User updated successfully',
        ), 200);
    }

    public function delete_user($request) {
        $user_id = $request->get_param('id');
        $reassign = $request->get_param('reassign');

        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        // Prevent deletion of current user
        if ($user_id == get_current_user_id()) {
            return new WP_Error('cannot_delete_self', 'Cannot delete your own account', array('status' => 400));
        }

        $result = wp_delete_user($user_id, $reassign);

        if (!$result) {
            return new WP_Error('delete_failed', 'Failed to delete user', array('status' => 500));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'User deleted successfully',
        ), 200);
    }

    /**
     * Role Management Handlers
     */
    public function get_roles($request) {
        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        $roles = array();
        foreach ($wp_roles->roles as $role_key => $role) {
            $roles[$role_key] = array(
                'name' => $role['name'],
                'capabilities' => $role['capabilities'],
                'user_count' => count(get_users(array('role' => $role_key))),
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $roles,
        ), 200);
    }

    public function get_role($request) {
        $role_key = $request->get_param('role');
        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        if (!isset($wp_roles->roles[$role_key])) {
            return new WP_Error('role_not_found', 'Role not found', array('status' => 404));
        }

        $role = $wp_roles->roles[$role_key];
        $role_data = array(
            'name' => $role['name'],
            'capabilities' => $role['capabilities'],
            'user_count' => count(get_users(array('role' => $role_key))),
        );

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $role_data,
        ), 200);
    }

    public function create_role($request) {
        $role_key = $request->get_param('role');
        $display_name = $request->get_param('display_name');
        $capabilities = $request->get_param('capabilities');

        if (get_role($role_key)) {
            return new WP_Error('role_exists', 'Role already exists', array('status' => 400));
        }

        $result = add_role($role_key, $display_name, $capabilities);

        if (!$result) {
            return new WP_Error('role_creation_failed', 'Failed to create role', array('status' => 500));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'role' => $role_key,
                'name' => $display_name,
                'capabilities' => $capabilities,
            ),
            'message' => 'Role created successfully',
        ), 201);
    }

    public function update_role($request) {
        $role_key = $request->get_param('role');
        $role = get_role($role_key);

        if (!$role) {
            return new WP_Error('role_not_found', 'Role not found', array('status' => 404));
        }

        if ($request->has_param('display_name')) {
            $display_name = $request->get_param('display_name');
            // Update role display name by modifying the roles array
            global $wp_roles;
            if (isset($wp_roles->roles[$role_key])) {
                $wp_roles->roles[$role_key]['name'] = $display_name;
                update_option($wp_roles->role_key . '_user_roles', $wp_roles->roles);
            }
        }

        if ($request->has_param('capabilities')) {
            $capabilities = $request->get_param('capabilities');
            // Remove all existing capabilities
            foreach ($role->capabilities as $cap => $grant) {
                $role->remove_cap($cap);
            }
            // Add new capabilities
            foreach ($capabilities as $cap => $grant) {
                $role->add_cap($cap, $grant);
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'role' => $role_key,
                'name' => $role->name,
                'capabilities' => $role->capabilities,
            ),
            'message' => 'Role updated successfully',
        ), 200);
    }

    public function delete_role($request) {
        $role_key = $request->get_param('role');
        $role = get_role($role_key);

        if (!$role) {
            return new WP_Error('role_not_found', 'Role not found', array('status' => 404));
        }

        // Prevent deletion of core roles
        $core_roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');
        if (in_array($role_key, $core_roles)) {
            return new WP_Error('cannot_delete_core_role', 'Cannot delete core WordPress roles', array('status' => 400));
        }

        remove_role($role_key);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Role deleted successfully',
        ), 200);
    }

    /**
     * User-Role Assignment Handlers
     */
    public function get_user_roles($request) {
        $user_id = $request->get_param('user_id');
        $user = get_user_by('ID', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $roles = $user->roles;
        $role_data = array();

        foreach ($roles as $role_key) {
            $role = get_role($role_key);
            if ($role) {
                $role_data[$role_key] = array(
                    'name' => $role->name,
                    'capabilities' => $role->capabilities,
                );
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $role_data,
        ), 200);
    }

    public function add_user_role($request) {
        $user_id = $request->get_param('user_id');
        $role_key = $request->get_param('role');

        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        if (!get_role($role_key)) {
            return new WP_Error('role_not_found', 'Role not found', array('status' => 404));
        }

        $user->add_role($role_key);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Role added to user successfully',
        ), 200);
    }

    public function remove_user_role($request) {
        $user_id = $request->get_param('user_id');
        $role_key = $request->get_param('role');

        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        if (!get_role($role_key)) {
            return new WP_Error('role_not_found', 'Role not found', array('status' => 404));
        }

        $user->remove_role($role_key);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Role removed from user successfully',
        ), 200);
    }

    /**
     * Clear and repopulate players from svenskalag.se (Admin only)
     */
    public function clear_and_repopulate_players($request) {
        global $wpdb;

        try {
            // Step 1: Clear all players
            $players_count_before = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players");

            $result = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bkgt_players");

            if ($result === false) {
                throw new Exception("Failed to clear players table: " . $wpdb->last_error);
            }

            // Step 2: Run scraper to repopulate players
            if (!class_exists('BKGT_Scraper')) {
                // Try to load the scraper class
                $scraper_files = array(
                    WP_PLUGIN_DIR . '/bkgt-data-scraping/includes/class-bkgt-scraper.php',
                    WP_PLUGIN_DIR . '/bkgt-core/includes/class-bkgt-scraper.php',
                    get_template_directory() . '/includes/class-bkgt-scraper.php'
                );

                $loaded = false;
                foreach ($scraper_files as $file) {
                    if (file_exists($file)) {
                        require_once($file);
                        $loaded = true;
                        break;
                    }
                }

                if (!$loaded) {
                    throw new Exception("BKGT_Scraper class not found. Please ensure the data scraping plugin is active.");
                }
            }

            // Initialize scraper
            $scraper = new BKGT_Scraper();

            // Run player scraping
            $scraped_count = $scraper->scrape_players();

            // Step 3: Enhanced validation and cleanup
            $this->validate_and_cleanup_players();

            // Step 4: Verify results
            $players_count_after = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players");

            // Get sample players for verification
            $sample_players = $wpdb->get_results("SELECT first_name, last_name, team_id FROM {$wpdb->prefix}bkgt_players LIMIT 3");

            return new WP_REST_Response(array(
                'success' => true,
                'message' => sprintf(
                    'Players cleared and repopulated successfully. Removed %d players, added %d players after validation.',
                    $players_count_before,
                    $players_count_after
                ),
                'data' => array(
                    'players_cleared' => $players_count_before,
                    'players_added' => $players_count_after,
                    'sample_players' => $sample_players
                ),
            ), 200);

        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Failed to clear and repopulate players: ' . $e->getMessage(),
            ), 500);
        }
    }

    /**
     * Enhanced player validation and cleanup
     */
    private function validate_and_cleanup_players() {
        global $wpdb;

        // Remove players without valid team associations
        $wpdb->query("
            DELETE p FROM {$wpdb->prefix}bkgt_players p
            LEFT JOIN {$wpdb->prefix}bkgt_teams t ON p.team_id = t.id
            WHERE t.id IS NULL
        ");

        // Remove players with invalid data
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}bkgt_players
            WHERE first_name = '' OR last_name = ''
            OR first_name IS NULL OR last_name IS NULL
        ");
    }

    /**
     * Clear and repopulate teams from svenskalag.se (Admin only)
     */
    public function clear_and_repopulate_teams($request) {
        global $wpdb;

        try {
            // Step 1: Clear all teams
            $teams_count_before = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");

            $result = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bkgt_teams");

            if ($result === false) {
                throw new Exception("Failed to clear teams table: " . $wpdb->last_error);
            }

            // Step 2: Run scraper to repopulate teams
            if (!class_exists('BKGT_Scraper')) {
                // Try to load the scraper class
                $scraper_files = array(
                    WP_PLUGIN_DIR . '/bkgt-data-scraping/includes/class-bkgt-scraper.php',
                    WP_PLUGIN_DIR . '/bkgt-core/includes/class-bkgt-scraper.php',
                    get_template_directory() . '/includes/class-bkgt-scraper.php'
                );

                $loaded = false;
                foreach ($scraper_files as $file) {
                    if (file_exists($file)) {
                        require_once($file);
                        $loaded = true;
                        break;
                    }
                }

                if (!$loaded) {
                    throw new Exception("BKGT_Scraper class not found. Please ensure the data scraping plugin is active.");
                }
            }

            // Initialize scraper
            $scraper = new BKGT_Scraper();

            // Run team scraping
            $scraped_count = $scraper->scrape_teams();

            // Step 3: Enhanced validation and cleanup
            $this->validate_and_cleanup_teams();

            // Step 4: Verify results
            $teams_count_after = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");

            // Get sample teams for verification
            $sample_teams = $wpdb->get_results("SELECT name, source_id, source_url FROM {$wpdb->prefix}bkgt_teams LIMIT 3");

            // Check if we have more than expected teams
            if ($teams_count_after > 8) {
                error_log("BKGT API: Warning - Found {$teams_count_after} teams but user reported only 8 exist on svenskalag.se");
            }

            return new WP_REST_Response(array(
                'success' => true,
                'message' => sprintf(
                    'Teams cleared and repopulated successfully. Removed %d teams, added %d teams after validation.',
                    $teams_count_before,
                    $teams_count_after
                ),
                'data' => array(
                    'teams_cleared' => $teams_count_before,
                    'teams_added' => $teams_count_after,
                    'sample_teams' => $sample_teams,
                    'warning' => ($teams_count_after > 8) ? 'More teams found than expected (8). Please verify scraper accuracy.' : null
                ),
            ), 200);

        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Failed to clear and repopulate teams: ' . $e->getMessage(),
            ), 500);
        }
    }

    /**
     * Enhanced team validation and cleanup
     */
    private function validate_and_cleanup_teams() {
        global $wpdb;

        // Function to check if a team is real (has proper svenskalag.se source data)
        $is_real_team = function($team_name, $source_id, $source_url) {
            // Only consider teams real if they have:
            // 1. A proper source_id (P2013 format)
            // 2. A svenskalag.se source_url
            // 3. URL contains the team code
            if (empty($source_id) || empty($source_url)) {
                return false;
            }

            // Source ID should be in P2013 format
            if (!preg_match('/^P\d{4}$/', $source_id)) {
                return false;
            }

            // Source URL should be from svenskalag.se
            if (stripos($source_url, 'svenskalag.se') === false) {
                return false;
            }

            // URL should contain the team code (e.g., /bkgt-p2013)
            $expected_path = '/bkgt-' . strtolower($source_id);
            if (stripos($source_url, $expected_path) === false) {
                return false;
            }

            return true;
        };

        // Get all teams
        $all_teams = $wpdb->get_results("SELECT id, name, source_id, source_url FROM {$wpdb->prefix}bkgt_teams");

        // Find and remove invalid teams
        foreach ($all_teams as $team) {
            if (!$is_real_team($team->name, $team->source_id, $team->source_url)) {
                $wpdb->delete($wpdb->prefix . 'bkgt_teams', array('id' => $team->id));
            }
        }

        // Remove duplicates
        $duplicates = $wpdb->get_results("
            SELECT source_id, COUNT(*) as count, GROUP_CONCAT(id) as ids
            FROM {$wpdb->prefix}bkgt_teams
            WHERE source_id IS NOT NULL AND source_id != ''
            GROUP BY source_id
            HAVING count > 1
        ");

        foreach ($duplicates as $dup) {
            $ids = explode(',', $dup->ids);
            // Keep the first ID, delete the rest
            $keep_id = array_shift($ids);
            $delete_ids = implode(',', $ids);

            if (!empty($delete_ids)) {
                $wpdb->query("DELETE FROM {$wpdb->prefix}bkgt_teams WHERE id IN ($delete_ids)");
            }
        }

        // Remove old teams (older than 10 years)
        $current_year = (int)date('Y');
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->prefix}bkgt_teams
            WHERE source_id REGEXP '^P[0-9]{4}$'
            AND CAST(SUBSTRING(source_id, 2) AS UNSIGNED) < %d
        ", $current_year - 10));
    }

    /**
     * BKGT Dashboard Handlers
     */
    public function get_bkgt_dashboard($request) {
        // Get system information
        $system_info = array(
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'bkgt_core_version' => defined('BKGT_CORE_VERSION') ? BKGT_CORE_VERSION : 'Unknown',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_version' => $this->get_database_version(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'debug_log' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
        );

        // Get plugin information
        $plugins = $this->get_bkgt_plugins_info();

        // Get system status
        $system_status = array(
            'database_connection' => $this->check_database_connection(),
            'file_permissions' => $this->check_file_permissions(),
            'memory_usage' => $this->get_memory_usage(),
            'disk_space' => $this->get_disk_space(),
        );

        // Get recent activity (from API logs if available)
        $recent_activity = $this->get_recent_activity();

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'system_info' => $system_info,
                'plugins' => $plugins,
                'system_status' => $system_status,
                'recent_activity' => $recent_activity,
                'generated_at' => current_time('mysql'),
            ),
        ), 200);
    }

    /**
     * BKGT Error Logs Handlers
     */
    public function get_error_logs($request) {
        $limit = $request->get_param('limit') ?: 50;
        $level = $request->get_param('level');
        $start_date = $request->get_param('start_date');
        $end_date = $request->get_param('end_date');

        // Get logs from BKGT logger if available
        if (class_exists('BKGT_Logger')) {
            $logs = BKGT_Logger::get_recent_logs($limit);
        } else {
            $logs = array();
        }

        // Filter logs by level if specified
        if ($level) {
            $logs = array_filter($logs, function($log) use ($level) {
                return strpos($log, '[' . $level . ']') !== false;
            });
        }

        // Filter by date range if specified
        if ($start_date || $end_date) {
            $logs = array_filter($logs, function($log) use ($start_date, $end_date) {
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $log, $matches)) {
                    $log_date = $matches[1];
                    if ($start_date && strtotime($log_date) < strtotime($start_date)) {
                        return false;
                    }
                    if ($end_date && strtotime($log_date) > strtotime($end_date . ' 23:59:59')) {
                        return false;
                    }
                    return true;
                }
                return false;
            });
        }

        // Parse and format logs
        $formatted_logs = array();
        foreach ($logs as $log) {
            $parsed = $this->parse_log_entry($log);
            if ($parsed) {
                $formatted_logs[] = $parsed;
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(
                'logs' => array_values($formatted_logs),
                'total' => count($formatted_logs),
                'limit' => $limit,
            ),
        ), 200);
    }

    public function clear_error_logs($request) {
        if (class_exists('BKGT_Logger')) {
            $log_file = WP_CONTENT_DIR . '/bkgt-logs.log';
            if (file_exists($log_file)) {
                $result = file_put_contents($log_file, '', LOCK_EX);
                if ($result !== false) {
                    return new WP_REST_Response(array(
                        'success' => true,
                        'message' => 'Error logs cleared successfully',
                    ), 200);
                }
            }
        }

        return new WP_Error('clear_failed', 'Failed to clear error logs', array('status' => 500));
    }

    public function get_error_statistics($request) {
        if (class_exists('BKGT_Error_Recovery')) {
            $stats = BKGT_Error_Recovery::get_error_statistics();
        } else {
            $stats = array(
                'total_errors' => 0,
                'critical' => 0,
                'errors' => 0,
                'warnings' => 0,
                'by_type' => array(),
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $stats,
        ), 200);
    }

    public function get_system_health($request) {
        $health = array(
            'status' => 'healthy',
            'checks' => array(
                'database' => $this->check_database_connection(),
                'filesystem' => $this->check_file_permissions(),
                'memory' => $this->check_memory_health(),
                'disk_space' => $this->check_disk_space_health(),
            ),
            'timestamp' => current_time('mysql'),
        );

        // Determine overall status
        $failed_checks = array_filter($health['checks'], function($check) {
            return $check['status'] !== 'healthy';
        });

        if (!empty($failed_checks)) {
            $health['status'] = 'warning';
            if (count($failed_checks) > 2) {
                $health['status'] = 'critical';
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $health,
        ), 200);
    }

    /**
     * Get diagnostic information (equivalent to admin diagnostic page)
     */
    public function get_diagnostic_info($request) {
        global $wpdb;

        $diagnostic = array(
            'plugin_status' => array(),
            'database_tables' => array(),
            'api_endpoints' => array(),
            'inventory_items' => array(),
            'class_availability' => array(),
            'generated_at' => current_time('mysql'),
        );

        // Plugin Status
        $plugins = array(
            'bkgt-core/bkgt-core.php' => 'BKGT Core',
            'bkgt-data-scraping/bkgt-data-scraping.php' => 'BKGT Data Scraping',
            'bkgt-inventory/bkgt-inventory.php' => 'BKGT Inventory',
            'bkgt-api/bkgt-api.php' => 'BKGT API'
        );

        foreach ($plugins as $file => $name) {
            $active = is_plugin_active($file);
            $diagnostic['plugin_status'][] = array(
                'plugin' => $name,
                'status' => $active ? 'ACTIVE' : 'INACTIVE',
                'active' => $active,
            );
        }

        // Database Tables
        $tables = array(
            'bkgt_inventory_items',
            'bkgt_manufacturers',
            'bkgt_item_types',
            'bkgt_inventory_assignments',
            'bkgt_locations'
        );

        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            $diagnostic['database_tables'][] = array(
                'table' => $table,
                'status' => $exists ? 'EXISTS' : 'MISSING',
                'exists' => $exists,
            );
        }

        // API Endpoints
        $endpoints = array(
            'wp-json/bkgt/v1/equipment' => 'Requires authentication',
            'wp-json/bkgt/v1/equipment/preview-identifier' => 'Requires authentication',
            'wp-json/bkgt/v1/equipment/manufacturers' => 'Requires authentication',
            'wp-json/bkgt/v1/equipment/types' => 'Requires authentication'
        );

        foreach ($endpoints as $endpoint => $description) {
            $url = home_url($endpoint);
            $response = wp_remote_head($url);
            $status = wp_remote_retrieve_response_code($response);
            $is_expected = ($status == 401);
            $diagnostic['api_endpoints'][] = array(
                'endpoint' => $endpoint,
                'description' => $description,
                'status_code' => $status,
                'expected' => $is_expected,
            );
        }

        // Recent Inventory Items
        $items = $wpdb->get_results(
            "SELECT i.id, i.unique_identifier, i.title, m.name as manufacturer_name, it.name as item_type_name, i.condition_status
             FROM {$wpdb->prefix}bkgt_inventory_items i
             LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
             LEFT JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
             ORDER BY i.id DESC LIMIT 10"
        );

        foreach ($items as $item) {
            $diagnostic['inventory_items'][] = array(
                'id' => $item->id,
                'identifier' => $item->unique_identifier,
                'title' => $item->title,
                'manufacturer' => $item->manufacturer_name ?: 'Unknown',
                'type' => $item->item_type_name ?: 'Unknown',
                'status' => $item->condition_status,
            );
        }

        // Class Availability
        $classes = array(
            'BKGT_Inventory_Item',
            'BKGT_Manufacturer',
            'BKGT_Item_Type',
            'BKGT_Assignment'
        );

        foreach ($classes as $class) {
            $exists = class_exists($class);
            $diagnostic['class_availability'][] = array(
                'class' => $class,
                'status' => $exists ? 'EXISTS' : 'MISSING',
                'available' => $exists,
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $diagnostic,
        ), 200);
    }

    /**
     * Dashboard and Error Log Helper Methods
     */
    private function get_database_version() {
        global $wpdb;
        return $wpdb->get_var("SELECT VERSION()");
    }

    private function get_bkgt_plugins_info() {
        $plugins = array();

        // Check for BKGT plugins
        $bkgt_plugins = array(
            'bkgt-core' => 'BKGT Core',
            'bkgt-api' => 'BKGT API',
            'bkgt-team-player' => 'BKGT Team Player',
            'bkgt-inventory' => 'BKGT Inventory',
            'bkgt-document-management' => 'BKGT Document Management',
            'bkgt-data-scraping' => 'BKGT Data Scraping',
            'bkgt-communication' => 'BKGT Communication',
            'bkgt-user-management' => 'BKGT User Management',
            'bkgt-offboarding' => 'BKGT Offboarding',
        );

        foreach ($bkgt_plugins as $slug => $name) {
            $plugin_file = WP_PLUGIN_DIR . '/' . $slug . '/' . $slug . '.php';
            if (file_exists($plugin_file)) {
                $plugin_data = get_plugin_data($plugin_file);
                $plugins[$slug] = array(
                    'name' => $name,
                    'version' => $plugin_data['Version'] ?? 'Unknown',
                    'active' => is_plugin_active($slug . '/' . $slug . '.php'),
                    'path' => $slug . '/' . $slug . '.php',
                );
            }
        }

        return $plugins;
    }

    private function check_database_connection() {
        global $wpdb;
        $result = $wpdb->check_connection();
        return array(
            'status' => $result ? 'healthy' : 'error',
            'message' => $result ? 'Database connection is healthy' : 'Database connection failed',
        );
    }

    private function check_file_permissions() {
        $wp_content = WP_CONTENT_DIR;
        $writable = wp_is_writable($wp_content);

        return array(
            'status' => $writable ? 'healthy' : 'error',
            'message' => $writable ? 'File permissions are correct' : 'wp-content directory is not writable',
        );
    }

    private function get_memory_usage() {
        $memory_limit = ini_get('memory_limit');
        $memory_used = memory_get_peak_usage(true);
        $memory_limit_bytes = wp_convert_hr_to_bytes($memory_limit);

        return array(
            'used' => size_format($memory_used, 2),
            'limit' => $memory_limit,
            'percentage' => $memory_limit_bytes > 0 ? round(($memory_used / $memory_limit_bytes) * 100, 1) : 0,
        );
    }

    private function get_disk_space() {
        $upload_dir = wp_upload_dir();
        $free_space = disk_free_space($upload_dir['basedir']);
        $total_space = disk_total_space($upload_dir['basedir']);

        return array(
            'free' => size_format($free_space, 2),
            'total' => size_format($total_space, 2),
            'percentage' => $total_space > 0 ? round((($total_space - $free_space) / $total_space) * 100, 1) : 0,
        );
    }

    private function get_recent_activity() {
        global $wpdb;

        // Get recent API requests
        $recent_requests = $wpdb->get_results($wpdb->prepare(
            "SELECT endpoint, method, response_code, created_at
             FROM {$wpdb->prefix}bkgt_api_logs
             WHERE created_at >= %s
             ORDER BY created_at DESC
             LIMIT 10",
            date('Y-m-d H:i:s', strtotime('-24 hours'))
        ), ARRAY_A);

        return array(
            'api_requests_last_24h' => count($recent_requests),
            'recent_requests' => $recent_requests,
        );
    }

    private function check_memory_health() {
        $memory_usage = $this->get_memory_usage();
        $status = 'healthy';
        $message = 'Memory usage is normal';

        if ($memory_usage['percentage'] > 80) {
            $status = 'warning';
            $message = 'High memory usage detected';
        }
        if ($memory_usage['percentage'] > 95) {
            $status = 'error';
            $message = 'Critical memory usage - consider increasing memory limit';
        }

        return array(
            'status' => $status,
            'message' => $message,
            'details' => $memory_usage,
        );
    }

    private function check_disk_space_health() {
        $disk_space = $this->get_disk_space();
        $status = 'healthy';
        $message = 'Disk space is adequate';

        if ($disk_space['percentage'] > 85) {
            $status = 'warning';
            $message = 'Low disk space available';
        }
        if ($disk_space['percentage'] > 95) {
            $status = 'error';
            $message = 'Critical disk space - immediate action required';
        }

        return array(
            'status' => $status,
            'message' => $message,
            'details' => $disk_space,
        );
    }

    private function parse_log_entry($log) {
        $parsed = array(
            'timestamp' => '',
            'level' => '',
            'message' => '',
            'user' => '',
            'context' => null,
        );

        // Extract timestamp
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $log, $matches)) {
            $parsed['timestamp'] = $matches[1];
        }

        // Extract level
        if (preg_match('/\[(\w+)\]/', $log, $matches)) {
            $parsed['level'] = strtolower($matches[1]);
        }

        // Extract message
        if (preg_match('/\] ([^|]+) \|/', $log, $matches)) {
            $parsed['message'] = trim($matches[1]);
        }

        // Extract user
        if (preg_match('/User: ([^|]+)/', $log, $matches)) {
            $parsed['user'] = trim($matches[1]);
        }

        // Extract context
        if (preg_match('/Context: ({.*?})(?: \||$)/', $log, $matches)) {
            $parsed['context'] = json_decode($matches[1], true);
        }

        return $parsed;
    }

    /**
     * Helper Methods
     */
    private function format_user_data($user) {
        return array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->display_name,
            'roles' => $user->roles,
            'capabilities' => $user->allcaps,
            'registered_date' => $user->user_registered,
            'last_login' => get_user_meta($user->ID, 'last_login', true),
        );
    }

    /**
     * Validation methods
     */
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
        // Allow empty/null values for optional numeric fields
        if (empty($value) || $value === null) {
            return true;
        }
        
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
        // Allow empty/null values for optional date fields
        if (empty($value)) {
            return true;
        }
        
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

    public function validate_username_unique($value, $request, $param) {
        if (username_exists($value)) {
            return new WP_Error(
                'username_exists',
                __('This username is already taken.', 'bkgt-api'),
                array('status' => 400)
            );
        }
        return true;
    }

    public function validate_password_strength($value, $request, $param) {
        if (strlen($value) < 8) {
            return new WP_Error(
                'weak_password',
                __('Password must be at least 8 characters long.', 'bkgt-api'),
                array('status' => 400)
            );
        }
        return true;
    }

    public function validate_role_exists($value, $request, $param) {
        if (!get_role($value)) {
            return new WP_Error(
                'role_not_found',
                __('The specified role does not exist.', 'bkgt-api'),
                array('status' => 400)
            );
        }
        return true;
    }

    public function validate_role_unique($value, $request, $param) {
        if (get_role($value)) {
            return new WP_Error(
                'role_exists',
                __('This role already exists.', 'bkgt-api'),
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

        // Base query - UPDATED to include size, location_id, location_name
        $query = "SELECT SQL_CALC_FOUND_ROWS
            i.id, i.unique_identifier, i.title, i.manufacturer_id, i.item_type_id,
            i.storage_location, i.condition_status, i.condition_date, i.condition_reason,
            i.sticker_code, i.notes, i.created_at, i.updated_at, i.size,
            m.name as manufacturer_name,
            it.name as item_type_name,
            a.assignee_id, a.assignee_name, a.assignment_date, a.due_date, a.location_id,
            l.name as location_name
        FROM {$wpdb->prefix}bkgt_inventory_items i
        LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
        LEFT JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
        LEFT JOIN {$wpdb->prefix}bkgt_inventory_assignments a ON i.id = a.item_id AND a.return_date IS NULL
        LEFT JOIN {$wpdb->prefix}bkgt_locations l ON a.location_id = l.id";

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
            $where .= " AND a.assignee_name LIKE %s";
            $params[] = '%' . $wpdb->esc_like($assigned_to) . '%';
        }

        if ($location_id) {
            $where .= " AND a.location_id = %d";
            $params[] = $location_id;
        }

        if ($search) {
            $where .= " AND (i.title LIKE %s OR i.unique_identifier LIKE %s OR i.sticker_code LIKE %s OR i.notes LIKE %s OR m.name LIKE %s OR it.name LIKE %s OR a.assignee_name LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
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
            i.sticker_code, i.notes, i.purchase_date, i.purchase_price, i.warranty_expiry, i.created_at, i.updated_at, i.size, i.location_id,
            m.name as manufacturer_name,
            it.name as item_type_name,
            a.assignee_id, a.assignee_name, a.assignment_date, a.due_date,
            l.name as location_name
        FROM {$wpdb->prefix}bkgt_inventory_items i
        LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
        LEFT JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
        LEFT JOIN {$wpdb->prefix}bkgt_inventory_assignments a ON i.id = a.item_id AND a.return_date IS NULL
        LEFT JOIN {$wpdb->prefix}bkgt_locations l ON i.location_id = l.id
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
        $storage_location = $request->get_param('storage_location');
        $sticker_code = $request->get_param('sticker_code');
        $size = $request->get_param('size');
        $location_id = $request->get_param('location_id');
        $purchase_date = $request->get_param('purchase_date');
        $purchase_price = $request->get_param('purchase_price');
        $warranty_expiry = $request->get_param('warranty_expiry');
        $condition_status = $request->get_param('condition_status') ?: 'normal';
        $condition_date = $request->get_param('condition_date');
        $condition_reason = $request->get_param('condition_reason');
        $notes = $request->get_param('notes');

        // Generate unique identifier
        if (class_exists('BKGT_Inventory_Item') && method_exists('BKGT_Inventory_Item', 'generate_unique_identifier')) {
            $unique_identifier = BKGT_Inventory_Item::generate_unique_identifier($manufacturer_id, $item_type_id);
        } else {
            // Fallback: generate a simple unique identifier
            $unique_identifier = sprintf('%04d-%04d-%05d', $manufacturer_id, $item_type_id, time() % 100000);
        }

        if (!$unique_identifier) {
            return new WP_Error('invalid_manufacturer_or_type', __('Invalid manufacturer or item type.', 'bkgt-api'), array('status' => 400));
        }

        // Auto-generate sticker code if not provided
        if (empty($sticker_code)) {
            if (class_exists('BKGT_Inventory_Item') && method_exists('BKGT_Inventory_Item', 'generate_sticker_code')) {
                $sticker_code = BKGT_Inventory_Item::generate_sticker_code($unique_identifier);
            } else {
                // Fallback: generate sticker code by removing leading zeros from unique identifier
                $parts = explode('-', $unique_identifier);
                if (count($parts) === 3) {
                    $manufacturer = intval($parts[0]);
                    $item_type = intval($parts[1]);
                    $sequential = intval($parts[2]);
                    $sticker_code = sprintf('%d-%d-%d', $manufacturer, $item_type, $sequential);
                } else {
                    $sticker_code = $unique_identifier;
                }
            }
        }

        // Generate meaningful title from manufacturer + item type + size
        $manufacturer = $wpdb->get_row($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}bkgt_manufacturers WHERE id = %d",
            $manufacturer_id
        ));
        $item_type = $wpdb->get_row($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}bkgt_item_types WHERE id = %d",
            $item_type_id
        ));

        $title = $manufacturer ? $manufacturer->name : '';
        if ($item_type && $item_type->name) {
            $title .= ' ' . $item_type->name;
        }
        if ($size) {
            $title .= ' - ' . $size;
        }
        // Fallback to unique identifier if no meaningful title could be generated
        if (empty(trim($title))) {
            $title = $unique_identifier;
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
                'size' => $size,
                'location_id' => $location_id,
                'purchase_date' => $purchase_date,
                'purchase_price' => $purchase_price,
                'warranty_expiry' => $warranty_expiry,
                'condition_status' => $condition_status,
                'condition_date' => $condition_date,
                'condition_reason' => $condition_reason,
                'notes' => $notes,
            ),
            array('%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%f', '%s', '%s', '%s', '%s', '%s')
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
        
        // Note: manufacturer_id, item_type_id, unique_identifier, sticker_code are allowed in request
        // but will be silently ignored. They cannot actually be modified.

        $title = $request->get_param('title');
        $condition_status = $request->get_param('condition_status');
        $condition_reason = $request->get_param('condition_reason');
        $condition_date = $request->get_param('condition_date');
        $storage_location = $request->get_param('storage_location');
        $size = $request->get_param('size');
        $location_id = $request->get_param('location_id');
        $purchase_date = $request->get_param('purchase_date');
        $purchase_price = $request->get_param('purchase_price');
        $warranty_expiry = $request->get_param('warranty_expiry');
        $notes = $request->get_param('notes');

        $update_data = array();
        $update_format = array();

        if ($title !== null) {
            $update_data['title'] = $title;
            $update_format[] = '%s';
        }

        if ($condition_status !== null) {
            $update_data['condition_status'] = $condition_status;
            $update_format[] = '%s';
            if (!$condition_date) {
                $update_data['condition_date'] = current_time('mysql');
                $update_format[] = '%s';
            }
        }

        if ($condition_date !== null) {
            $update_data['condition_date'] = $condition_date;
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

        if ($size !== null) {
            $update_data['size'] = $size;
            $update_format[] = '%s';
        }

        if ($location_id !== null) {
            $update_data['location_id'] = $location_id;
            $update_format[] = '%d';
        }

        if ($purchase_date !== null) {
            $update_data['purchase_date'] = $purchase_date;
            $update_format[] = '%s';
        }

        if ($purchase_price !== null) {
            $update_data['purchase_price'] = $purchase_price;
            $update_format[] = '%f';
        }

        if ($warranty_expiry !== null) {
            $update_data['warranty_expiry'] = $warranty_expiry;
            $update_format[] = '%s';
        }

        if ($notes !== null) {
            $update_data['notes'] = $notes;
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

    /**
     * Create manufacturer
     */
    public function create_manufacturer($request) {
        global $wpdb;

        $name = $request->get_param('name');
        $description = $request->get_param('description');
        $website = $request->get_param('website');
        $contact_email = $request->get_param('contact_email');

        // Generate unique manufacturer ID
        $manufacturer_id = 'MFG-' . strtoupper(substr(md5(uniqid()), 0, 8));

        $contact_info = array();
        if ($website) $contact_info['website'] = $website;
        if ($contact_email) $contact_info['email'] = $contact_email;
        if ($description) $contact_info['description'] = $description;

        $result = $wpdb->insert(
            $wpdb->prefix . 'bkgt_manufacturers',
            array(
                'manufacturer_id' => $manufacturer_id,
                'name' => $name,
                'contact_info' => json_encode($contact_info),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create manufacturer.', 'bkgt-api'), array('status' => 500));
        }

        $manufacturer_id = $wpdb->insert_id;

        // Log the action
        if (class_exists('BKGT_History')) {
            BKGT_History::log_action('manufacturer_created', $manufacturer_id, array(
                'name' => $name,
                'manufacturer_id' => $manufacturer_id,
            ));
        }

        return new WP_REST_Response(array(
            'message' => __('Manufacturer created successfully.', 'bkgt-api'),
            'manufacturer' => array(
                'id' => $manufacturer_id,
                'manufacturer_id' => $manufacturer_id,
                'name' => $name,
                'contact_info' => $contact_info,
            ),
        ), 201);
    }

    /**
     * Update manufacturer
     */
    public function update_manufacturer($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $name = $request->get_param('name');
        $description = $request->get_param('description');
        $website = $request->get_param('website');
        $contact_email = $request->get_param('contact_email');

        // Check if manufacturer exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bkgt_manufacturers WHERE id = %d",
            $id
        ));

        if (!$existing) {
            return new WP_Error('manufacturer_not_found', __('Manufacturer not found.', 'bkgt-api'), array('status' => 404));
        }

        $update_data = array('updated_at' => current_time('mysql'));
        $update_format = array('%s');

        if ($name !== null) {
            $update_data['name'] = $name;
            $update_format[] = '%s';
        }

        // Update contact info
        $current_contact = $wpdb->get_var($wpdb->prepare(
            "SELECT contact_info FROM {$wpdb->prefix}bkgt_manufacturers WHERE id = %d",
            $id
        ));
        $contact_info = json_decode($current_contact, true) ?: array();

        if ($description !== null) $contact_info['description'] = $description;
        if ($website !== null) $contact_info['website'] = $website;
        if ($contact_email !== null) $contact_info['email'] = $contact_email;

        $update_data['contact_info'] = json_encode($contact_info);
        $update_format[] = '%s';

        $result = $wpdb->update(
            $wpdb->prefix . 'bkgt_manufacturers',
            $update_data,
            array('id' => $id),
            $update_format,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update manufacturer.', 'bkgt-api'), array('status' => 500));
        }

        // Log the action
        if (class_exists('BKGT_History')) {
            BKGT_History::log_action('manufacturer_updated', $id, array(
                'name' => $name,
            ));
        }

        return new WP_REST_Response(array(
            'message' => __('Manufacturer updated successfully.', 'bkgt-api'),
        ), 200);
    }

    /**
     * Delete manufacturer
     */
    public function delete_manufacturer($request) {
        global $wpdb;

        $id = $request->get_param('id');

        // Check if manufacturer exists
        $manufacturer = $wpdb->get_row($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}bkgt_manufacturers WHERE id = %d",
            $id
        ));

        if (!$manufacturer) {
            return new WP_Error('manufacturer_not_found', __('Manufacturer not found.', 'bkgt-api'), array('status' => 404));
        }

        // Check if manufacturer is used by any equipment
        $usage_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_inventory_items WHERE manufacturer_id = %d",
            $id
        ));

        if ($usage_count > 0) {
            return new WP_Error('manufacturer_in_use', __('Cannot delete manufacturer that is assigned to equipment items.', 'bkgt-api'), array('status' => 409));
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'bkgt_manufacturers',
            array('id' => $id),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to delete manufacturer.', 'bkgt-api'), array('status' => 500));
        }

        // Log the action
        if (class_exists('BKGT_History')) {
            BKGT_History::log_action('manufacturer_deleted', $id, array(
                'name' => $manufacturer->name,
            ));
        }

        return new WP_REST_Response(array(
            'message' => __('Manufacturer deleted successfully.', 'bkgt-api'),
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

    /**
     * Create item type
     */
    public function create_item_type($request) {
        global $wpdb;

        $name = $request->get_param('name');
        $description = $request->get_param('description');
        $custom_fields = $request->get_param('custom_fields');

        // Generate unique item type ID
        $item_type_id = 'TYPE-' . strtoupper(substr(md5(uniqid()), 0, 8));

        $custom_fields_json = $custom_fields ? json_encode($custom_fields) : null;

        $result = $wpdb->insert(
            $wpdb->prefix . 'bkgt_item_types',
            array(
                'item_type_id' => $item_type_id,
                'name' => $name,
                'description' => $description,
                'custom_fields' => $custom_fields_json,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create item type.', 'bkgt-api'), array('status' => 500));
        }

        $item_type_id = $wpdb->insert_id;

        // Log the action
        if (class_exists('BKGT_History')) {
            BKGT_History::log_action('item_type_created', $item_type_id, array(
                'name' => $name,
                'item_type_id' => $item_type_id,
            ));
        }

        return new WP_REST_Response(array(
            'message' => __('Item type created successfully.', 'bkgt-api'),
            'type' => array(
                'id' => $item_type_id,
                'item_type_id' => $item_type_id,
                'name' => $name,
                'description' => $description,
                'custom_fields' => $custom_fields,
            ),
        ), 201);
    }

    /**
     * Update item type
     */
    public function update_item_type($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $name = $request->get_param('name');
        $description = $request->get_param('description');
        $custom_fields = $request->get_param('custom_fields');

        // Check if item type exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bkgt_item_types WHERE id = %d",
            $id
        ));

        if (!$existing) {
            return new WP_Error('item_type_not_found', __('Item type not found.', 'bkgt-api'), array('status' => 404));
        }

        $update_data = array('updated_at' => current_time('mysql'));
        $update_format = array('%s');

        if ($name !== null) {
            $update_data['name'] = $name;
            $update_format[] = '%s';
        }

        if ($description !== null) {
            $update_data['description'] = $description;
            $update_format[] = '%s';
        }

        if ($custom_fields !== null) {
            $update_data['custom_fields'] = json_encode($custom_fields);
            $update_format[] = '%s';
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'bkgt_item_types',
            $update_data,
            array('id' => $id),
            $update_format,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update item type.', 'bkgt-api'), array('status' => 500));
        }

        // Log the action
        if (class_exists('BKGT_History')) {
            BKGT_History::log_action('item_type_updated', $id, array(
                'name' => $name,
            ));
        }

        return new WP_REST_Response(array(
            'message' => __('Item type updated successfully.', 'bkgt-api'),
        ), 200);
    }

    /**
     * Delete item type
     */
    public function delete_item_type($request) {
        global $wpdb;

        $id = $request->get_param('id');

        // Check if item type exists
        $item_type = $wpdb->get_row($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}bkgt_item_types WHERE id = %d",
            $id
        ));

        if (!$item_type) {
            return new WP_Error('item_type_not_found', __('Item type not found.', 'bkgt-api'), array('status' => 404));
        }

        // Check if item type is used by any equipment
        $usage_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_inventory_items WHERE item_type_id = %d",
            $id
        ));

        if ($usage_count > 0) {
            return new WP_Error('item_type_in_use', __('Cannot delete item type that is assigned to equipment items.', 'bkgt-api'), array('status' => 409));
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'bkgt_item_types',
            array('id' => $id),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to delete item type.', 'bkgt-api'), array('status' => 500));
        }

        // Log the action
        if (class_exists('BKGT_History')) {
            BKGT_History::log_action('item_type_deleted', $id, array(
                'name' => $item_type->name,
            ));
        }

        return new WP_REST_Response(array(
            'message' => __('Item type deleted successfully.', 'bkgt-api'),
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

        // Check if BKGT Assignment class is available
        if (!class_exists('BKGT_Assignment')) {
            return new WP_Error('inventory_plugin_required', __('BKGT Inventory plugin is required for equipment assignments.', 'bkgt-api'), array('status' => 500));
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

    /**
     * Get equipment assignment
     */
    public function get_equipment_assignment($request) {
        $id = $request->get_param('id');

        // Verify item exists
        global $wpdb;
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bkgt_inventory_items WHERE id = %d",
            $id
        ));

        if (!$item) {
            return new WP_Error('item_not_found', __('Equipment item not found.', 'bkgt-api'), array('status' => 404));
        }

        // Check if BKGT Assignment class is available
        if (!class_exists('BKGT_Assignment')) {
            return new WP_Error('inventory_plugin_required', __('BKGT Inventory plugin is required for equipment assignments.', 'bkgt-api'), array('status' => 500));
        }

        // Get assignment using BKGT Assignment class
        $assignment = BKGT_Assignment::get_assignment($id);

        return new WP_REST_Response(array(
            'assignment' => $assignment,
        ), 200);
    }

    /**
     * Unassign equipment
     */
    public function unassign_equipment($request) {
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
            return new WP_Error('no_active_assignment', __('No active assignment found for this equipment.', 'bkgt-api'), array('status' => 400));
        }

        // Update the assignment with return information
        $update_data = array(
            'return_date' => $return_date,
            'unassigned_date' => current_time('mysql'),
            'unassigned_by' => get_current_user_id(),
        );

        if ($notes) {
            $update_data['notes'] = $notes;
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'bkgt_inventory_assignments',
            $update_data,
            array('id' => $assignment->id),
            array('%s', '%s', '%d', '%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to unassign equipment.', 'bkgt-api'), array('status' => 500));
        }

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

        // Log the unassignment
        if (class_exists('BKGT_History')) {
            BKGT_History::log($id, 'assignment_changed', get_current_user_id(), array(
                'action' => 'unassigned',
                'return_date' => $return_date,
            ));
        }

        return new WP_REST_Response(array(
            'message' => __('Equipment unassigned successfully.', 'bkgt-api'),
        ), 200);
    }

    /**
     * Bulk equipment operations
     */
    public function bulk_equipment_operation($request) {
        $operation = $request->get_param('operation');
        $item_ids = $request->get_param('item_ids');

        if (empty($item_ids)) {
            return new WP_Error('no_items', __('No items specified for bulk operation.', 'bkgt-api'), array('status' => 400));
        }

        // Enforce maximum bulk operation limit to prevent DOS attacks
        $max_bulk_operations = apply_filters('bkgt_api_max_bulk_operations', 500);
        if (count($item_ids) > $max_bulk_operations) {
            return new WP_Error(
                'too_many_items',
                sprintf(__('Maximum %d items allowed per bulk operation. You requested %d items.', 'bkgt-api'), $max_bulk_operations, count($item_ids)),
                array('status' => 413)
            );
        }

        // Temporarily remove BKGT class check for testing
        // if (!class_exists('BKGT_History')) {
        //     return new WP_Error('inventory_plugin_required', __('BKGT Inventory plugin is required for bulk operations.', 'bkgt-api'), array('status' => 500));
        // }

        switch ($operation) {
            case 'delete':
                return $this->bulk_delete_equipment($item_ids);
            case 'export':
                return $this->bulk_export_equipment($item_ids);
            default:
                return new WP_Error('invalid_operation', __('Invalid bulk operation.', 'bkgt-api'), array('status' => 400));
        }
    }

    /**
     * Bulk delete equipment
     */
    private function bulk_delete_equipment($item_ids) {
        global $wpdb;
        $deleted_count = 0;
        $errors = array();

        // Temporarily remove BKGT class check for testing
        // if (!class_exists('BKGT_History')) {
        //     return new WP_Error('inventory_plugin_required', __('BKGT Inventory plugin is required for bulk operations.', 'bkgt-api'), array('status' => 500));
        // }

        foreach ($item_ids as $item_id) {
            // Check if item exists
            $item = $wpdb->get_row($wpdb->prepare(
                "SELECT title FROM {$wpdb->prefix}bkgt_inventory_items WHERE id = %d",
                $item_id
            ));

            if (!$item) {
                $errors[] = sprintf(__('Item ID %d not found.', 'bkgt-api'), $item_id);
                continue;
            }

            // Log deletion - temporarily disabled
            // BKGT_History::log($item_id, 'item_deleted', get_current_user_id(), array(
            //     'title' => $item->title,
            // ));

            // Delete assignments first
            $wpdb->delete(
                $wpdb->prefix . 'bkgt_inventory_assignments',
                array('item_id' => $item_id),
                array('%d')
            );

            // Delete item
            $result = $wpdb->delete(
                $wpdb->prefix . 'bkgt_inventory_items',
                array('id' => $item_id),
                array('%d')
            );

            if ($result) {
                $deleted_count++;
            } else {
                $errors[] = sprintf(__('Failed to delete item ID %d.', 'bkgt-api'), $item_id);
            }
        }

        return new WP_REST_Response(array(
            'message' => sprintf(__('Bulk delete completed. %d items deleted.', 'bkgt-api'), $deleted_count),
            'deleted_count' => $deleted_count,
            'errors' => $errors,
        ), 200);
    }

    /**
     * Bulk export equipment
     */
    private function bulk_export_equipment($item_ids) {
        global $wpdb;

        // Build query for selected items
        $placeholders = str_repeat('%d,', count($item_ids) - 1) . '%d';
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT i.*, m.name as manufacturer_name, it.name as item_type_name,
                        a.assignee_name, a.assignment_date, a.due_date
                 FROM {$wpdb->prefix}bkgt_inventory_items i
                 LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
                 LEFT JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
                 LEFT JOIN {$wpdb->prefix}bkgt_inventory_assignments a ON i.id = a.item_id AND a.return_date IS NULL
                 WHERE i.id IN ({$placeholders})",
                $item_ids
            )
        );

        // Generate CSV content
        $csv_data = array();
        $csv_data[] = array(
            'Unik Identifierare',
            'Artikelnamn',
            'Tillverkare',
            'Artikeltyp',
            'Serienummer',
            'Skick',
            'Tilldelad till',
            'Plats',
            'Inköpsdatum',
            'Inköpspris',
            'Garanti utgångsdatum'
        );

        foreach ($items as $item) {
            $csv_data[] = array(
                $item->unique_identifier,
                $item->title,
                $item->manufacturer_name ?: '',
                $item->item_type_name ?: '',
                $item->serial_number ?: '',
                $item->condition_status ?: '',
                $item->assignee_name ?: '',
                $item->storage_location ?: '',
                $item->purchase_date ?: '',
                $item->purchase_price ?: '',
                $item->warranty_expiry ?: ''
            );
        }

        return new WP_REST_Response(array(
            'message' => __('Bulk export completed.', 'bkgt-api'),
            'item_count' => count($items),
            'csv_data' => $csv_data,
        ), 200);
    }

    /**
     * Search equipment
     */
    public function search_equipment($request) {
        global $wpdb;

        $query = $request->get_param('q');
        $limit = min($request->get_param('limit') ?: 20, 100);
        $fields_param = $request->get_param('fields') ?: 'id,unique_identifier,title';
        $fields = is_array($fields_param) ? $fields_param : explode(',', $fields_param);

        if (empty($query)) {
            return new WP_Error('empty_query', __('Search query cannot be empty.', 'bkgt-api'), array('status' => 400));
        }

        // Temporarily remove BKGT class check for testing
        // if (!class_exists('BKGT_History')) {
        //     return new WP_Error('inventory_plugin_required', __('BKGT Inventory plugin is required for search functionality.', 'bkgt-api'), array('status' => 500));
        // }

        // Build SELECT clause based on requested fields
        $select_fields = array();
        $field_map = array(
            'id' => 'i.id',
            'unique_identifier' => 'i.unique_identifier',
            'title' => 'i.title',
            'manufacturer_name' => 'm.name as manufacturer_name',
            'item_type_name' => 'it.name as item_type_name',
            'condition_status' => 'i.condition_status',
            'assignee_name' => 'a.assignee_name'
        );

        foreach ($fields as $field) {
            if (isset($field_map[$field])) {
                $select_fields[] = $field_map[$field];
            }
        }

        if (empty($select_fields)) {
            $select_fields = array('i.id', 'i.unique_identifier', 'i.title');
        }

        $select_clause = implode(', ', $select_fields);

        // Build search query
        $search_query = "SELECT {$select_clause}
        FROM {$wpdb->prefix}bkgt_inventory_items i
        LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
        LEFT JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
        LEFT JOIN {$wpdb->prefix}bkgt_inventory_assignments a ON i.id = a.item_id AND a.return_date IS NULL
        WHERE (i.title LIKE %s
               OR i.unique_identifier LIKE %s
               OR i.sticker_code LIKE %s
               OR i.notes LIKE %s
               OR m.name LIKE %s
               OR it.name LIKE %s
               OR a.assignee_name LIKE %s)
        ORDER BY i.title ASC
        LIMIT %d";

        $search_term = '%' . $wpdb->esc_like($query) . '%';
        $params = array_fill(0, 7, $search_term);
        $params[] = $limit;

        $results = $wpdb->get_results($wpdb->prepare($search_query, $params));

        // Format results
        $formatted_results = array();
        foreach ($results as $result) {
            $formatted_result = array();
            foreach ($fields as $field) {
                if (property_exists($result, $field)) {
                    $formatted_result[$field] = $result->$field;
                }
            }
            $formatted_results[] = $formatted_result;
        }

        return new WP_REST_Response(array(
            'query' => $query,
            'results' => $formatted_results,
            'total' => count($formatted_results),
            'limit' => $limit,
            'fields' => $fields,
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

    /**
     * Create location
     */
    public function create_location($request) {
        global $wpdb;

        $name = $request->get_param('name');
        $location_type = $request->get_param('location_type');
        $address = $request->get_param('address');
        $contact_person = $request->get_param('contact_person');
        $contact_phone = $request->get_param('contact_phone');
        $contact_email = $request->get_param('contact_email');
        $capacity = $request->get_param('capacity');

        // Generate slug from name
        $slug = sanitize_title($name);

        // Ensure unique slug
        $original_slug = $slug;
        $counter = 1;
        while ($wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bkgt_locations WHERE slug = %s",
            $slug
        ))) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'bkgt_locations',
            array(
                'name' => $name,
                'slug' => $slug,
                'location_type' => $location_type,
                'address' => $address,
                'contact_person' => $contact_person,
                'contact_phone' => $contact_phone,
                'contact_email' => $contact_email,
                'capacity' => $capacity,
                'is_active' => 1,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create location.', 'bkgt-api'), array('status' => 500));
        }

        $location_id = $wpdb->insert_id;

        // Log the action
        if (class_exists('BKGT_History')) {
            BKGT_History::log_action('location_created', $location_id, array(
                'name' => $name,
                'location_type' => $location_type,
            ));
        }

        return new WP_REST_Response(array(
            'message' => __('Location created successfully.', 'bkgt-api'),
            'location' => array(
                'id' => $location_id,
                'name' => $name,
                'slug' => $slug,
                'location_type' => $location_type,
                'address' => $address,
                'contact_person' => $contact_person,
                'contact_phone' => $contact_phone,
                'contact_email' => $contact_email,
                'capacity' => $capacity,
                'is_active' => 1,
            ),
        ), 201);
    }

    /**
     * Update location
     */
    public function update_location($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $name = $request->get_param('name');
        $location_type = $request->get_param('location_type');
        $address = $request->get_param('address');
        $contact_person = $request->get_param('contact_person');
        $contact_phone = $request->get_param('contact_phone');
        $contact_email = $request->get_param('contact_email');
        $capacity = $request->get_param('capacity');

        // Check if location exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bkgt_locations WHERE id = %d",
            $id
        ));

        if (!$existing) {
            return new WP_Error('location_not_found', __('Location not found.', 'bkgt-api'), array('status' => 404));
        }

        $update_data = array('updated_at' => current_time('mysql'));
        $update_format = array('%s');

        if ($name !== null) {
            $update_data['name'] = $name;
            $update_format[] = '%s';

            // Update slug if name changed
            $slug = sanitize_title($name);
            $original_slug = $slug;
            $counter = 1;
            while ($wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}bkgt_locations WHERE slug = %s AND id != %d",
                $slug, $id
            ))) {
                $slug = $original_slug . '-' . $counter;
                $counter++;
            }
            $update_data['slug'] = $slug;
            $update_format[] = '%s';
        }

        if ($location_type !== null) {
            $update_data['location_type'] = $location_type;
            $update_format[] = '%s';
        }

        if ($address !== null) {
            $update_data['address'] = $address;
            $update_format[] = '%s';
        }

        if ($contact_person !== null) {
            $update_data['contact_person'] = $contact_person;
            $update_format[] = '%s';
        }

        if ($contact_phone !== null) {
            $update_data['contact_phone'] = $contact_phone;
            $update_format[] = '%s';
        }

        if ($contact_email !== null) {
            $update_data['contact_email'] = $contact_email;
            $update_format[] = '%s';
        }

        if ($capacity !== null) {
            $update_data['capacity'] = $capacity;
            $update_format[] = '%d';
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'bkgt_locations',
            $update_data,
            array('id' => $id),
            $update_format,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update location.', 'bkgt-api'), array('status' => 500));
        }

        // Log the action
        if (class_exists('BKGT_History')) {
            BKGT_History::log_action('location_updated', $id, array(
                'name' => $name,
            ));
        }

        return new WP_REST_Response(array(
            'message' => __('Location updated successfully.', 'bkgt-api'),
        ), 200);
    }

    /**
     * Delete location
     */
    public function delete_location($request) {
        global $wpdb;

        $id = $request->get_param('id');

        // Check if location exists
        $location = $wpdb->get_row($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}bkgt_locations WHERE id = %d",
            $id
        ));

        if (!$location) {
            return new WP_Error('location_not_found', __('Location not found.', 'bkgt-api'), array('status' => 404));
        }

        // Check if location is used by any equipment
        $usage_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_inventory_items WHERE location_id = %d",
            $id
        ));

        if ($usage_count > 0) {
            return new WP_Error('location_in_use', __('Cannot delete location that contains equipment items.', 'bkgt-api'), array('status' => 409));
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'bkgt_locations',
            array('is_active' => 0, 'updated_at' => current_time('mysql')),
            array('id' => $id),
            array('%d', '%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to delete location.', 'bkgt-api'), array('status' => 500));
        }

        // Log the action
        if (class_exists('BKGT_History')) {
            BKGT_History::log_action('location_deleted', $id, array(
                'name' => $location->name,
            ));
        }

        return new WP_REST_Response(array(
            'message' => __('Location deleted successfully.', 'bkgt-api'),
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
            'sticker_code' => $item->sticker_code ?: null,
            'size' => $item->size ?? null,
            'purchase_date' => $item->purchase_date ?? null,
            'purchase_price' => $item->purchase_price ? (float) $item->purchase_price : null,
            'warranty_expiry' => $item->warranty_expiry ?? null,
            'notes' => $item->notes ?? null,
            'assigned_to_id' => $item->assignee_id ? (int) $item->assignee_id : null,
            'assigned_to_name' => $item->assignee_name,
            'assignment_date' => $item->assignment_date,
            'due_date' => $item->due_date,
            'location_id' => $item->location_id ? (int) $item->location_id : null,
            'location_name' => $item->location_name,
            'created_at' => $item->created_at ?: null,
            'updated_at' => $item->updated_at ?: null,
        );
    }

    // ===== DOCUMENT MANAGEMENT API HANDLERS =====

    /**
     * Get single document
     */
    /**
     * Create new document
     */
    public function create_document($request) {
        $title = $request->get_param('title');
        $content = $request->get_param('content');
        $category_id = $request->get_param('category_id');
        $status = $request->get_param('status') ?: 'draft';
        $tags = $request->get_param('tags') ?: array();
        $metadata = $request->get_param('metadata') ?: array();

        // Create post
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => $status,
            'post_type' => 'bkgt_document',
            'post_author' => get_current_user_id(),
        );

        $post_id = wp_insert_post($post_data, true);
        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Set category
        if ($category_id) {
            wp_set_post_terms($post_id, array($category_id), 'bkgt_document_category');
        }

        // Set tags
        if (!empty($tags)) {
            wp_set_post_terms($post_id, $tags, 'bkgt_document_tag');
        }

        // Save metadata
        if (!empty($metadata)) {
            foreach ($metadata as $key => $value) {
                update_post_meta($post_id, '_bkgt_' . $key, $value);
            }
        }

        // Create initial version
        $this->create_document_version($post_id, 'Initial version');

        $post = get_post($post_id);
        return new WP_REST_Response($this->format_document_data($post), 201);
    }

    /**
     * Update document
     */
    public function update_document($request) {
        $document_id = $request->get_param('id');
        $title = $request->get_param('title');
        $content = $request->get_param('content');
        $category_id = $request->get_param('category_id');
        $status = $request->get_param('status');
        $tags = $request->get_param('tags');
        $metadata = $request->get_param('metadata');

        // Check access permissions
        if (!$this->check_document_access($document_id, get_current_user_id(), 'write')) {
            return new WP_Error('access_denied', 'Access denied', array('status' => 403));
        }

        $post = get_post($document_id);
        if (!$post || $post->post_type !== 'bkgt_document') {
            return new WP_Error('document_not_found', 'Document not found', array('status' => 404));
        }

        // Create version before update
        $this->create_document_version($document_id, 'Updated document');

        // Update post
        $post_data = array('ID' => $document_id);
        if ($title !== null) $post_data['post_title'] = $title;
        if ($content !== null) $post_data['post_content'] = $content;
        if ($status !== null) $post_data['post_status'] = $status;

        $result = wp_update_post($post_data, true);
        if (is_wp_error($result)) {
            return $result;
        }

        // Update category
        if ($category_id !== null) {
            wp_set_post_terms($document_id, $category_id ? array($category_id) : array(), 'bkgt_document_category');
        }

        // Update tags
        if ($tags !== null) {
            wp_set_post_terms($document_id, $tags, 'bkgt_document_tag');
        }

        // Update metadata
        if ($metadata !== null) {
            foreach ($metadata as $key => $value) {
                update_post_meta($document_id, '_bkgt_' . $key, $value);
            }
        }

        $post = get_post($document_id);
        return new WP_REST_Response($this->format_document_data($post), 200);
    }

    /**
     * Delete document
     */
    public function delete_document($request) {
        $document_id = $request->get_param('id');

        // Check access permissions
        if (!$this->check_document_access($document_id, get_current_user_id(), 'manage')) {
            return new WP_Error('access_denied', 'Access denied', array('status' => 403));
        }

        $post = get_post($document_id);
        if (!$post || $post->post_type !== 'bkgt_document') {
            return new WP_Error('document_not_found', 'Document not found', array('status' => 404));
        }

        $result = wp_delete_post($document_id, true);
        if (!$result) {
            return new WP_Error('delete_failed', 'Failed to delete document', array('status' => 500));
        }

        return new WP_REST_Response(array('message' => 'Document deleted successfully'), 200);
    }

    /**
     * Get document categories
     */
    public function get_document_categories($request) {
        $parent = $request->get_param('parent');
        $hide_empty = $request->get_param('hide_empty') ?: false;

        $args = array(
            'taxonomy' => 'bkgt_document_category',
            'hide_empty' => $hide_empty,
            'hierarchical' => true,
        );

        if ($parent !== null) {
            $args['parent'] = $parent;
        }

        $categories = get_terms($args);

        if (is_wp_error($categories)) {
            return $categories;
        }

        $formatted_categories = array();
        foreach ($categories as $category) {
            $formatted_categories[] = $this->format_category_data($category);
        }

        return new WP_REST_Response(array('categories' => $formatted_categories), 200);
    }

    /**
     * Get single document category
     */
    public function get_document_category($request) {
        $category_id = $request->get_param('id');

        $category = get_term($category_id, 'bkgt_document_category');
        if (is_wp_error($category) || !$category) {
            return new WP_Error('category_not_found', 'Category not found', array('status' => 404));
        }

        return new WP_REST_Response($this->format_category_data($category), 200);
    }

    /**
     * Create document category
     */
    public function create_document_category($request) {
        $name = $request->get_param('name');
        $description = $request->get_param('description');
        $parent = $request->get_param('parent') ?: 0;

        $result = wp_insert_term($name, 'bkgt_document_category', array(
            'description' => $description,
            'parent' => $parent,
        ));

        if (is_wp_error($result)) {
            return $result;
        }

        $category = get_term($result['term_id'], 'bkgt_document_category');
        return new WP_REST_Response($this->format_category_data($category), 201);
    }

    /**
     * Update document category
     */
    public function update_document_category($request) {
        $category_id = $request->get_param('id');
        $name = $request->get_param('name');
        $description = $request->get_param('description');
        $parent = $request->get_param('parent');

        $args = array();
        if ($name !== null) $args['name'] = $name;
        if ($description !== null) $args['description'] = $description;
        if ($parent !== null) $args['parent'] = $parent;

        $result = wp_update_term($category_id, 'bkgt_document_category', $args);
        if (is_wp_error($result)) {
            return $result;
        }

        $category = get_term($category_id, 'bkgt_document_category');
        return new WP_REST_Response($this->format_category_data($category), 200);
    }

    /**
     * Delete document category
     */
    public function delete_document_category($request) {
        $category_id = $request->get_param('id');

        $result = wp_delete_term($category_id, 'bkgt_document_category');
        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(array('message' => 'Category deleted successfully'), 200);
    }

    /**
     * Get document templates
     */
    public function get_document_templates($request) {
        global $wpdb;

        $category = $request->get_param('category');
        $search = $request->get_param('search');

        $where_clauses = array("meta_key = '_bkgt_template_data'");

        if ($category) {
            $where_clauses[] = $wpdb->prepare("meta_value LIKE %s", '%"category":"' . $wpdb->esc_like($category) . '"%');
        }

        if ($search) {
            $where_clauses[] = $wpdb->prepare("(meta_value LIKE %s OR post_title LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

        $query = "SELECT post_id, meta_value FROM {$wpdb->postmeta} {$where_sql}";
        $results = $wpdb->get_results($query);

        $templates = array();
        foreach ($results as $result) {
            $template_data = json_decode($result->meta_value, true);
            if ($template_data) {
                $template_data['id'] = $result->post_id;
                $templates[] = $template_data;
            }
        }

        return new WP_REST_Response(array('templates' => $templates), 200);
    }

    /**
     * Get single document template
     */
    public function get_document_template($request) {
        $template_id = $request->get_param('id');

        $template_data = get_post_meta($template_id, '_bkgt_template_data', true);
        if (!$template_data) {
            return new WP_Error('template_not_found', 'Template not found', array('status' => 404));
        }

        $template = json_decode($template_data, true);
        $template['id'] = $template_id;

        return new WP_REST_Response($template, 200);
    }

    /**
     * Create document template
     */
    public function create_document_template($request) {
        $name = $request->get_param('name');
        $description = $request->get_param('description');
        $category = $request->get_param('category');
        $content = $request->get_param('content');
        $variables = $request->get_param('variables') ?: array();

        $template_data = array(
            'name' => $name,
            'description' => $description,
            'category' => $category,
            'content' => $content,
            'variables' => $variables,
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id(),
        );

        // Create a post to store the template
        $post_data = array(
            'post_title' => $name,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'bkgt_template',
            'post_author' => get_current_user_id(),
        );

        $post_id = wp_insert_post($post_data, true);
        if (is_wp_error($post_id)) {
            return $post_id;
        }

        update_post_meta($post_id, '_bkgt_template_data', wp_json_encode($template_data));

        $template_data['id'] = $post_id;
        return new WP_REST_Response($template_data, 201);
    }

    /**
     * Update document template
     */
    public function update_document_template($request) {
        $template_id = $request->get_param('id');
        $name = $request->get_param('name');
        $description = $request->get_param('description');
        $category = $request->get_param('category');
        $content = $request->get_param('content');
        $variables = $request->get_param('variables');

        $existing_data = get_post_meta($template_id, '_bkgt_template_data', true);
        if (!$existing_data) {
            return new WP_Error('template_not_found', 'Template not found', array('status' => 404));
        }

        $template_data = json_decode($existing_data, true);

        if ($name !== null) $template_data['name'] = $name;
        if ($description !== null) $template_data['description'] = $description;
        if ($category !== null) $template_data['category'] = $category;
        if ($content !== null) $template_data['content'] = $content;
        if ($variables !== null) $template_data['variables'] = $variables;

        $template_data['updated_at'] = current_time('mysql');

        update_post_meta($template_id, '_bkgt_template_data', wp_json_encode($template_data));

        // Update post content if provided
        if ($content !== null) {
            wp_update_post(array('ID' => $template_id, 'post_content' => $content));
        }
        if ($name !== null) {
            wp_update_post(array('ID' => $template_id, 'post_title' => $name));
        }

        $template_data['id'] = $template_id;
        return new WP_REST_Response($template_data, 200);
    }

    /**
     * Delete document template
     */
    public function delete_document_template($request) {
        $template_id = $request->get_param('id');

        $result = wp_delete_post($template_id, true);
        if (!$result) {
            return new WP_Error('delete_failed', 'Failed to delete template', array('status' => 500));
        }

        return new WP_REST_Response(array('message' => 'Template deleted successfully'), 200);
    }

    /**
     * Create document from template
     */
    public function create_document_from_template($request) {
        $template_id = $request->get_param('id');
        $variables = $request->get_param('variables') ?: array();
        $title = $request->get_param('title');

        $template_data = get_post_meta($template_id, '_bkgt_template_data', true);
        if (!$template_data) {
            return new WP_Error('template_not_found', 'Template not found', array('status' => 404));
        }

        $template = json_decode($template_data, true);
        $content = $this->process_template_variables($template['content'], $variables);

        if (!$title) {
            $title = $template['name'] . ' - ' . current_time('Y-m-d H:i:s');
        }

        // Create document
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'draft',
            'post_type' => 'bkgt_document',
            'post_author' => get_current_user_id(),
        );

        $post_id = wp_insert_post($post_data, true);
        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Store template reference
        update_post_meta($post_id, '_bkgt_template_id', $template_id);
        update_post_meta($post_id, '_bkgt_template_variables', wp_json_encode($variables));

        // Create initial version
        $this->create_document_version($post_id, 'Created from template: ' . $template['name']);

        $post = get_post($post_id);
        return new WP_REST_Response($this->format_document_data($post), 201);
    }

    /**
     * Get document versions
     */
    public function get_document_versions($request) {
        global $wpdb;

        $document_id = $request->get_param('id');

        $versions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bkgt_document_versions
             WHERE document_id = %d ORDER BY version_number DESC",
            $document_id
        ));

        $formatted_versions = array();
        foreach ($versions as $version) {
            $formatted_versions[] = array(
                'id' => (int) $version->id,
                'version_number' => (int) $version->version_number,
                'title' => $version->title,
                'content' => $version->content,
                'created_at' => $version->created_at,
                'created_by' => (int) $version->created_by,
                'change_summary' => $version->change_summary,
            );
        }

        return new WP_REST_Response(array('versions' => $formatted_versions), 200);
    }

    /**
     * Restore document version
     */
    public function restore_document_version($request) {
        $document_id = $request->get_param('document_id');
        $version_id = $request->get_param('version_id');

        // Check access permissions
        if (!$this->check_document_access($document_id, get_current_user_id(), 'write')) {
            return new WP_Error('access_denied', 'Access denied', array('status' => 403));
        }

        global $wpdb;
        $version = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bkgt_document_versions WHERE id = %d AND document_id = %d",
            $version_id, $document_id
        ));

        if (!$version) {
            return new WP_Error('version_not_found', 'Version not found', array('status' => 404));
        }

        // Update document with version content
        wp_update_post(array(
            'ID' => $document_id,
            'post_title' => $version->title,
            'post_content' => $version->content,
        ));

        // Create new version with restore note
        $this->create_document_version($document_id, 'Restored to version ' . $version->version_number);

        $post = get_post($document_id);
        return new WP_REST_Response($this->format_document_data($post), 200);
    }

    /**
     * Get document access permissions
     */
    public function get_document_access($request) {
        global $wpdb;

        $document_id = $request->get_param('id');

        $permissions = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, u.display_name, u.user_email
             FROM {$wpdb->prefix}bkgt_document_permissions p
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
             WHERE p.document_id = %d",
            $document_id
        ));

        $formatted_permissions = array();
        foreach ($permissions as $perm) {
            $formatted_permissions[] = array(
                'id' => (int) $perm->id,
                'user_id' => (int) $perm->user_id,
                'user_name' => $perm->display_name,
                'user_email' => $perm->user_email,
                'access_type' => $perm->access_type,
                'granted_at' => $perm->granted_at,
                'granted_by' => (int) $perm->granted_by,
            );
        }

        return new WP_REST_Response(array('permissions' => $formatted_permissions), 200);
    }

    /**
     * Grant document access
     */
    public function grant_document_access($request) {
        global $wpdb;

        $document_id = $request->get_param('id');
        $user_id = $request->get_param('user_id');
        $access_type = $request->get_param('access_type');

        // Check if user has manage permissions
        if (!$this->check_document_access($document_id, get_current_user_id(), 'manage')) {
            return new WP_Error('access_denied', 'Access denied', array('status' => 403));
        }

        // Check if permission already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bkgt_document_permissions
             WHERE document_id = %d AND user_id = %d",
            $document_id, $user_id
        ));

        if ($existing) {
            // Update existing permission
            $wpdb->update(
                $wpdb->prefix . 'bkgt_document_permissions',
                array('access_type' => $access_type, 'granted_at' => current_time('mysql')),
                array('id' => $existing)
            );
        } else {
            // Insert new permission
            $wpdb->insert(
                $wpdb->prefix . 'bkgt_document_permissions',
                array(
                    'document_id' => $document_id,
                    'user_id' => $user_id,
                    'access_type' => $access_type,
                    'granted_by' => get_current_user_id(),
                    'granted_at' => current_time('mysql'),
                )
            );
        }

        return new WP_REST_Response(array('message' => 'Access granted successfully'), 200);
    }

    /**
     * Update document access
     */
    public function update_document_access($request) {
        global $wpdb;

        $document_id = $request->get_param('document_id');
        $user_id = $request->get_param('user_id');
        $access_type = $request->get_param('access_type');

        // Check if user has manage permissions
        if (!$this->check_document_access($document_id, get_current_user_id(), 'manage')) {
            return new WP_Error('access_denied', 'Access denied', array('status' => 403));
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'bkgt_document_permissions',
            array('access_type' => $access_type),
            array('document_id' => $document_id, 'user_id' => $user_id)
        );

        if ($result === false) {
            return new WP_Error('update_failed', 'Failed to update access', array('status' => 500));
        }

        return new WP_REST_Response(array('message' => 'Access updated successfully'), 200);
    }

    /**
     * Revoke document access
     */
    public function revoke_document_access($request) {
        global $wpdb;

        $document_id = $request->get_param('document_id');
        $user_id = $request->get_param('user_id');

        // Check if user has manage permissions
        if (!$this->check_document_access($document_id, get_current_user_id(), 'manage')) {
            return new WP_Error('access_denied', 'Access denied', array('status' => 403));
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'bkgt_document_permissions',
            array('document_id' => $document_id, 'user_id' => $user_id)
        );

        if ($result === false) {
            return new WP_Error('delete_failed', 'Failed to revoke access', array('status' => 500));
        }

        return new WP_REST_Response(array('message' => 'Access revoked successfully'), 200);
    }

    /**
     * Export document
     */
    public function export_document($request) {
        $document_id = $request->get_param('id');
        $format = $request->get_param('format') ?: 'pdf';

        // Check access permissions
        if (!$this->check_document_access($document_id, get_current_user_id(), 'read')) {
            return new WP_Error('access_denied', 'Access denied', array('status' => 403));
        }

        $post = get_post($document_id);
        if (!$post || $post->post_type !== 'bkgt_document') {
            return new WP_Error('document_not_found', 'Document not found', array('status' => 404));
        }

        // Generate export based on format
        switch ($format) {
            case 'pdf':
                $result = $this->export_document_pdf($post);
                break;
            case 'docx':
                $result = $this->export_document_docx($post);
                break;
            case 'html':
                $result = $this->export_document_html($post);
                break;
            default:
                return new WP_Error('invalid_format', 'Invalid export format', array('status' => 400));
        }

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(array(
            'download_url' => $result['url'],
            'filename' => $result['filename'],
            'format' => $format,
        ), 200);
    }

    /**
     * Get export formats
     */
    public function get_export_formats($request) {
        return new WP_REST_Response(array(
            'formats' => array(
                array('id' => 'pdf', 'name' => 'PDF Document', 'extension' => 'pdf'),
                array('id' => 'docx', 'name' => 'Word Document', 'extension' => 'docx'),
                array('id' => 'html', 'name' => 'HTML Document', 'extension' => 'html'),
            ),
        ), 200);
    }

    // ===== HELPER METHODS =====

    /**
     * Format document data for API response
     */
    private function format_document_data($post) {
        $categories = wp_get_post_terms($post->ID, 'bkgt_document_category', array('fields' => 'names'));
        $tags = wp_get_post_terms($post->ID, 'bkgt_document_tag', array('fields' => 'names'));

        return array(
            'id' => (int) $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'status' => $post->post_status,
            'created_at' => $post->post_date,
            'updated_at' => $post->post_modified,
            'author_id' => (int) $post->post_author,
            'author_name' => get_the_author_meta('display_name', $post->post_author),
            'categories' => $categories,
            'tags' => $tags,
            'metadata' => $this->get_document_metadata($post->ID),
            'versions_count' => $this->get_document_versions_count($post->ID),
            'permissions' => $this->get_document_permissions_summary($post->ID),
        );
    }

    /**
     * Format category data for API response
     */
    private function format_category_data($category) {
        return array(
            'id' => (int) $category->term_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'parent' => (int) $category->parent,
            'count' => (int) $category->count,
        );
    }

    /**
     * Get document metadata
     */
    private function get_document_metadata($post_id) {
        $metadata = array();
        $meta_keys = get_post_custom_keys($post_id);

        if ($meta_keys) {
            foreach ($meta_keys as $key) {
                if (strpos($key, '_bkgt_') === 0) {
                    $clean_key = str_replace('_bkgt_', '', $key);
                    $metadata[$clean_key] = get_post_meta($post_id, $key, true);
                }
            }
        }

        return $metadata;
    }

    /**
     * Get document versions count
     */
    private function get_document_versions_count($post_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_document_versions WHERE document_id = %d",
            $post_id
        ));
    }

    /**
     * Get document permissions summary
     */
    private function get_document_permissions_summary($post_id) {
        global $wpdb;
        $permissions = $wpdb->get_results($wpdb->prepare(
            "SELECT access_type, COUNT(*) as count
             FROM {$wpdb->prefix}bkgt_document_permissions
             WHERE document_id = %d GROUP BY access_type",
            $post_id
        ));

        $summary = array('read' => 0, 'write' => 0, 'manage' => 0);
        foreach ($permissions as $perm) {
            $summary[$perm->access_type] = (int) $perm->count;
        }

        return $summary;
    }

    /**
     * Check document access permissions
     */
    private function check_document_access($document_id, $user_id, $required_access = 'read') {
        // Document author always has manage access
        $post = get_post($document_id);
        if ($post && $post->post_author == $user_id) {
            return true;
        }

        // Check if user is admin
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        global $wpdb;
        $access_type = $wpdb->get_var($wpdb->prepare(
            "SELECT access_type FROM {$wpdb->prefix}bkgt_document_permissions
             WHERE document_id = %d AND user_id = %d",
            $document_id, $user_id
        ));

        if (!$access_type) {
            return false;
        }

        $access_levels = array('read' => 1, 'write' => 2, 'manage' => 3);
        $required_level = $access_levels[$required_access] ?? 1;
        $user_level = $access_levels[$access_type] ?? 0;

        return $user_level >= $required_level;
    }

    /**
     * Create document version
     */
    private function create_document_version($document_id, $change_summary = '') {
        global $wpdb;

        $post = get_post($document_id);
        if (!$post) return false;

        // Get next version number
        $version_number = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(version_number) FROM {$wpdb->prefix}bkgt_document_versions WHERE document_id = %d",
            $document_id
        )) + 1;

        $result = $wpdb->insert(
            $wpdb->prefix . 'bkgt_document_versions',
            array(
                'document_id' => $document_id,
                'version_number' => $version_number,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'change_summary' => $change_summary,
            )
        );

        return $result !== false;
    }

    /**
     * Process template variables
     */
    private function process_template_variables($content, $variables) {
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, $value, $content);
        }
        return $content;
    }

    /**
     * Export document as PDF
     */
    private function export_document_pdf($post) {
        // This would require a PDF library like TCPDF or DomPDF
        // For now, return a placeholder
        $filename = sanitize_file_name($post->post_title) . '.pdf';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;

        // Create a simple HTML file for now (would need PDF conversion)
        $html_content = '<html><body><h1>' . esc_html($post->post_title) . '</h1>' .
                       wpautop($post->post_content) . '</body></html>';

        file_put_contents($file_path, $html_content);

        return array(
            'url' => $upload_dir['url'] . '/' . $filename,
            'filename' => $filename,
        );
    }

    /**
     * Export document as DOCX
     */
    private function export_document_docx($post) {
        // This would require a DOCX library like PhpOffice/PhpWord
        // For now, return a placeholder
        $filename = sanitize_file_name($post->post_title) . '.docx';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;

        // Create a simple HTML file for now (would need DOCX conversion)
        $html_content = '<html><body><h1>' . esc_html($post->post_title) . '</h1>' .
                       wpautop($post->post_content) . '</body></html>';

        file_put_contents($file_path, $html_content);

        return array(
            'url' => $upload_dir['url'] . '/' . $filename,
            'filename' => $filename,
        );
    }

    /**
     * Export document as HTML
     */
    private function export_document_html($post) {
        $filename = sanitize_file_name($post->post_title) . '.html';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;

        $html_content = '<html><head><title>' . esc_html($post->post_title) . '</title></head><body>' .
                       '<h1>' . esc_html($post->post_title) . '</h1>' .
                       wpautop($post->post_content) . '</body></html>';

        file_put_contents($file_path, $html_content);

        return array(
            'url' => $upload_dir['url'] . '/' . $filename,
            'filename' => $filename,
        );
    }

    /**
     * Get API documentation
     */
    public function get_api_documentation($request) {
        $format = $request->get_param('format');
        $readme_path = plugin_dir_path(dirname(__FILE__)) . 'README.md';

        if (!file_exists($readme_path)) {
            return new WP_Error('documentation_not_found', 'API documentation file not found', array('status' => 404));
        }

        $content = file_get_contents($readme_path);

        switch ($format) {
            case 'json':
                return new WP_REST_Response(array(
                    'documentation' => $content,
                    'format' => 'markdown',
                    'last_updated' => filemtime($readme_path),
                ), 200);

            case 'markdown':
                return new WP_REST_Response($content, 200, array(
                    'Content-Type' => 'text/markdown',
                ));

            case 'html':
            default:
                // Convert markdown to HTML (basic conversion)
                $html = $this->markdown_to_html($content);
                return new WP_REST_Response($html, 200, array(
                    'Content-Type' => 'text/html',
                ));
        }
    }

    /**
     * Get API routes information
     */
    public function get_api_routes($request) {
        $namespace = $request->get_param('namespace');
        $detailed = $request->get_param('detailed');

        if (function_exists('rest_get_server')) {
            $server = rest_get_server();
            $routes = $server->get_routes();

            $bkgt_routes = array();
            foreach ($routes as $route => $route_config) {
                if (strpos($route, $namespace) === 0) {
                    if ($detailed) {
                        $bkgt_routes[$route] = $route_config;
                    } else {
                        // Simplified view
                        $methods = array();
                        if (is_array($route_config)) {
                            foreach ($route_config as $config) {
                                if (isset($config['methods'])) {
                                    $methods = array_merge($methods, (array) $config['methods']);
                                }
                            }
                        }
                        $bkgt_routes[$route] = array_unique($methods);
                    }
                }
            }

            return new WP_REST_Response(array(
                'namespace' => $namespace,
                'routes' => $bkgt_routes,
                'total_routes' => count($bkgt_routes),
                'detailed' => $detailed,
                'generated_at' => current_time('mysql'),
            ), 200);
        }

        return new WP_Error('rest_server_not_available', 'REST server not available', array('status' => 500));
    }

    /**
     * Convert basic markdown to HTML
     */
    private function markdown_to_html($markdown) {
        // Basic markdown to HTML conversion
        $html = $markdown;

        // Headers
        $html = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $html);

        // Bold
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);

        // Italic
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);

        // Code blocks
        $html = preg_replace('/```(.*?)```/s', '<pre><code>$1</code></pre>', $html);

        // Inline code
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);

        // Links
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);

        // Lists
        $html = preg_replace('/^\* (.*)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/^\d+\. (.*)$/m', '<li>$1</li>', $html);

        // Wrap in basic HTML structure
        $html = '<!DOCTYPE html><html><head><title>BKGT API Documentation</title><style>body{font-family:Arial,sans-serif;max-width:800px;margin:0 auto;padding:20px;}pre{background:#f4f4f4;padding:10px;border-radius:4px;}code{background:#f4f4f4;padding:2px 4px;border-radius:2px;}</style></head><body>' . $html . '</body></html>';

        return $html;
    }

    /**
     * Register update routes
     */
    private function register_update_routes() {
        // Get latest version
        register_rest_route($this->namespace, '/updates/latest', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_latest_version'),
            'permission_callback' => array($this, 'validate_api_key'),
            'args' => array(
                'platform' => array(
                    'type' => 'string',
                    'enum' => array('win32-x64', 'darwin-x64', 'darwin-arm64', 'linux-x64'),
                    'required' => false,
                ),
            ),
        ));

        // Download update package
        register_rest_route($this->namespace, '/updates/download/(?P<version>[^/]+)/(?P<platform>[^/]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'download_update'),
            'permission_callback' => array($this, 'validate_api_key'),
            'args' => array(
                'version' => array(
                    'type' => 'string',
                    'pattern' => '^\d+\.\d+\.\d+$',
                ),
                'platform' => array(
                    'type' => 'string',
                    'enum' => array('win32-x64', 'darwin-x64', 'darwin-arm64', 'linux-x64'),
                ),
            ),
        ));

        // Check compatibility
        register_rest_route($this->namespace, '/updates/compatibility/(?P<current_version>[^/]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'check_compatibility'),
            'permission_callback' => array($this, 'validate_api_key'),
            'args' => array(
                'current_version' => array(
                    'type' => 'string',
                    'pattern' => '^\d+\.\d+\.\d+$',
                ),
            ),
        ));

        // Report update status
        register_rest_route($this->namespace, '/updates/status', array(
            'methods' => 'POST',
            'callback' => array($this, 'report_update_status'),
            'permission_callback' => array($this, 'validate_api_key'),
            'args' => array(
                'current_version' => array(
                    'required' => true,
                    'type' => 'string',
                    'pattern' => '^\d+\.\d+\.\d+$',
                ),
                'target_version' => array(
                    'required' => true,
                    'type' => 'string',
                    'pattern' => '^\d+\.\d+\.\d+$',
                ),
                'platform' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('win32-x64', 'darwin-x64', 'darwin-arm64', 'linux-x64'),
                ),
                'status' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('completed', 'failed', 'cancelled'),
                ),
                'error_message' => array(
                    'type' => 'string',
                    'required' => false,
                ),
                'install_time_seconds' => array(
                    'type' => 'integer',
                    'minimum' => 0,
                    'required' => false,
                ),
            ),
        ));

        // Upload update package (admin only)
        register_rest_route($this->namespace, '/updates/upload', array(
            'methods' => 'POST',
            'callback' => array($this, 'upload_update_package'),
            'permission_callback' => array($this, 'validate_admin_api_key'),
        ));

        // Admin list updates
        register_rest_route($this->namespace, '/updates/admin/list', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_admin_updates'),
            'permission_callback' => array($this, 'validate_admin_api_key'),
            'args' => array(
                'page' => array(
                    'type' => 'integer',
                    'minimum' => 1,
                    'default' => 1,
                ),
                'per_page' => array(
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                    'default' => 20,
                ),
            ),
        ));

        // Deactivate update (admin only)
        register_rest_route($this->namespace, '/updates/(?P<version>[^/]+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'deactivate_update'),
            'permission_callback' => array($this, 'validate_admin_api_key'),
            'args' => array(
                'version' => array(
                    'type' => 'string',
                    'pattern' => '^\d+\.\d+\.\d+$',
                ),
            ),
        ));
    }

    /**
     * Get latest version
     */
    public function get_latest_version($request) {
        $updates = bkgt_api()->updates;

        if (!$updates) {
            return new WP_Error('updates_unavailable', 'Updates service unavailable', array('status' => 503));
        }

        $platform = $request->get_param('platform');
        $latest = $updates->get_latest_version($platform);

        if (!$latest) {
            return new WP_Error('no_updates', 'No updates available', array('status' => 404));
        }

        // Add download URLs
        foreach ($latest['platforms'] as $platform_key => &$platform_data) {
            $platform_data['download_url'] = rest_url("bkgt/v1/updates/download/{$latest['version']}/$platform_key");
        }

        return new WP_REST_Response($latest, 200);
    }

    /**
     * Download update package
     */
    public function download_update($request) {
        $updates = bkgt_api()->updates;

        if (!$updates) {
            return new WP_Error('updates_unavailable', 'Updates service unavailable', array('status' => 503));
        }

        $version = $request->get_param('version');
        $platform = $request->get_param('platform');

        $file = $updates->get_update_file($version, $platform);

        if (!$file) {
            return new WP_Error('update_not_found', 'Update package not found', array('status' => 404));
        }

        // Check if file exists
        if (!file_exists($file['path'])) {
            return new WP_Error('file_missing', 'Update file is missing', array('status' => 404));
        }

        // Set headers for file download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
        header('Content-Length: ' . $file['size']);
        header('X-File-Hash: sha256:' . $file['hash']);
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output file
        readfile($file['path']);
        exit;
    }

    /**
     * Check version compatibility
     */
    public function check_compatibility($request) {
        $updates = bkgt_api()->updates;

        if (!$updates) {
            return new WP_Error('updates_unavailable', 'Updates service unavailable', array('status' => 503));
        }

        $current_version = $request->get_param('current_version');
        $compatibility = $updates->check_compatibility($current_version);

        return new WP_REST_Response($compatibility, 200);
    }

    /**
     * Report update status
     */
    public function report_update_status($request) {
        $updates = bkgt_api()->updates;

        if (!$updates) {
            return new WP_Error('updates_unavailable', 'Updates service unavailable', array('status' => 503));
        }

        $data = array(
            'api_key' => $this->get_api_key_from_request($request),
            'current_version' => $request->get_param('current_version'),
            'target_version' => $request->get_param('target_version'),
            'platform' => $request->get_param('platform'),
            'status' => $request->get_param('status'),
            'error_message' => $request->get_param('error_message'),
            'install_time_seconds' => $request->get_param('install_time_seconds'),
        );

        $recorded = $updates->record_update_status($data);

        if (!$recorded) {
            return new WP_Error('status_record_failed', 'Failed to record update status', array('status' => 500));
        }

        return new WP_REST_Response(array(
            'recorded' => true,
            'message' => 'Update status recorded successfully'
        ), 200);
    }

    /**
     * Upload update package (admin)
     */
    public function upload_update_package($request) {
        $updates = bkgt_api()->updates;

        if (!$updates) {
            return new WP_Error('updates_unavailable', 'Updates service unavailable', array('status' => 503));
        }

        // Check if file was uploaded
        if (empty($_FILES['file'])) {
            return new WP_Error('no_file', 'No file uploaded', array('status' => 400));
        }

        $file = $_FILES['file'];
        $version = $request->get_param('version');
        $platform = $request->get_param('platform');
        $changelog = $request->get_param('changelog') ?: '';
        $critical = $request->get_param('critical') ? true : false;
        $minimum_version = $request->get_param('minimum_version');

        $result = $updates->upload_update_package($version, $platform, $file, $changelog, $critical, $minimum_version);

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(array_merge($result, array(
            'message' => 'Update package uploaded successfully'
        )), 201);
    }

    /**
     * Get admin updates list
     */
    public function get_admin_updates($request) {
        $updates = bkgt_api()->updates;

        if (!$updates) {
            return new WP_Error('updates_unavailable', 'Updates service unavailable', array('status' => 503));
        }

        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;

        $result = $updates->get_admin_updates($page, $per_page);

        return new WP_REST_Response($result, 200);
    }

    /**
     * Deactivate update version (admin)
     */
    public function deactivate_update($request) {
        $updates = bkgt_api()->updates;

        if (!$updates) {
            return new WP_Error('updates_unavailable', 'Updates service unavailable', array('status' => 503));
        }

        $version = $request->get_param('version');
        $deactivated = $updates->deactivate_update($version);

        if (!$deactivated) {
            return new WP_Error('deactivation_failed', 'Failed to deactivate update', array('status' => 500));
        }

        return new WP_REST_Response(array(
            'message' => "Update version $version deactivated successfully"
        ), 200);
    }

    /**
     * Validate API key from request headers
     */
    public function validate_api_key($request) {
        $api_key = $this->get_api_key_from_request($request);

        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'API key is required', array('status' => 401));
        }

        // Validate API key against stored keys
        global $wpdb;
        $table = $wpdb->prefix . 'bkgt_api_keys';

        $key_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE api_key = %s AND status = 'active'",
            $api_key
        ));

        if (!$key_data) {
            return new WP_Error('invalid_api_key', 'Invalid or inactive API key', array('status' => 401));
        }

        // Check if key has expired
        if (!empty($key_data->expires_at) && strtotime($key_data->expires_at) < time()) {
            return new WP_Error('expired_api_key', 'API key has expired', array('status' => 401));
        }

        // Store key data for later use
        $request->bkgt_api_key_data = $key_data;

        return true;
    }

    /**
     * Validate admin API key (for update management)
     */
    public function validate_admin_api_key($request) {
        $validation = $this->validate_api_key($request);

        if (is_wp_error($validation)) {
            return $validation;
        }

        $key_data = $request->bkgt_api_key_data;

        // Check if key has admin permissions
        if (empty($key_data->permissions) || !in_array('admin', json_decode($key_data->permissions, true))) {
            return new WP_Error('insufficient_permissions', 'Admin permissions required', array('status' => 403));
        }

        return true;
    }

    /**
     * Get API key from request headers
     */
    private function get_api_key_from_request($request) {
        // Check X-API-Key header first
        $api_key = $request->get_header('x_api_key');

        if (empty($api_key)) {
            // Check Authorization header for Bearer token format
            $auth_header = $request->get_header('authorization');
            if (!empty($auth_header) && strpos($auth_header, 'Bearer ') === 0) {
                $api_key = substr($auth_header, 7);
            }
        }

        return $api_key;
    }
}
