<?php
/**
 * BKGT API Endpoints
 *
 * REST API endpoints for BKGT inventory system
 *
 * @package BKGT_Inventory
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Inventory_API_Endpoints {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Equipment endpoints
        register_rest_route('bkgt/v1', '/equipment', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_equipment'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'page' => array(
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ),
                    'per_page' => array(
                        'default' => 10,
                        'sanitize_callback' => 'absint',
                    ),
                    'search' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'location_id' => array(
                        'sanitize_callback' => 'absint',
                    ),
                    'condition' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_equipment'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'title' => array(
                        'required' => false,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'manufacturer_id' => array(
                        'required' => true,
                        'sanitize_callback' => 'absint',
                    ),
                    'item_type_id' => array(
                        'required' => true,
                        'sanitize_callback' => 'absint',
                    ),
                    'unique_identifier' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'storage_location' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'condition_status' => array(
                        'default' => 'normal',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'notes' => array(
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                ),
            ),
        ));

        // Single equipment endpoint
        register_rest_route('bkgt/v1', '/equipment/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_equipment_item'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'update_equipment'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'sanitize_callback' => 'absint',
                    ),
                    'title' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'manufacturer_id' => array(
                        'sanitize_callback' => 'absint',
                    ),
                    'item_type_id' => array(
                        'sanitize_callback' => 'absint',
                    ),
                    'unique_identifier' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'storage_location' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'condition_status' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'notes' => array(
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                ),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_equipment'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ),
        ));

        // Bulk equipment operations endpoint
        register_rest_route('bkgt/v1', '/equipment/bulk', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'bulk_equipment_operations'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'operation' => array(
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function($value) {
                            return in_array($value, array('create', 'update', 'delete'));
                        }
                    ),
                    'items' => array(
                        'required' => true,
                        'validate_callback' => function($value) {
                            return is_array($value);
                        }
                    ),
                ),
            ),
        ));

        // Equipment assignment endpoint
        register_rest_route('bkgt/v1', '/equipment/(?P<id>\d+)/assignment', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_equipment_assignment'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_equipment_assignment'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'sanitize_callback' => 'absint',
                    ),
                    'assignment_type' => array(
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function($value) {
                            return in_array($value, array('individual', 'team', 'club'));
                        }
                    ),
                    'assignee_id' => array(
                        'sanitize_callback' => 'absint',
                    ),
                    'due_date' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'notes' => array(
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                ),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_equipment_assignment'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'sanitize_callback' => 'absint',
                    ),
                    'return_date' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'condition_status' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'notes' => array(
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                ),
            ),
        ));

        // Locations endpoint
        register_rest_route('bkgt/v1', '/locations', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_locations'),
                'permission_callback' => array($this, 'validate_token'),
            ),
        ));

        // Assignments endpoint
        register_rest_route('bkgt/v1', '/assignments', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_assignment'),
                'permission_callback' => array($this, 'validate_token'),
                'args' => array(
                    'item_id' => array(
                        'required' => true,
                        'sanitize_callback' => 'absint',
                    ),
                    'assignee_type' => array(
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'assignee_id' => array(
                        'required' => true,
                        'sanitize_callback' => 'absint',
                    ),
                    'assigned_by' => array(
                        'required' => true,
                        'sanitize_callback' => 'absint',
                    ),
                    'notes' => array(
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                ),
            ),
        ));
    }

    /**
     * Validate API token
     */
    public function validate_token($request) {
        // For development/testing - allow all requests
        // TODO: Implement proper authentication
        return true;
        
        // Check if user is logged in via cookies (for web requests)
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if (user_can($user, 'manage_inventory') || user_can($user, 'read') || current_user_can('read')) {
                return true;
            }
        }

        // Check for Authorization header (for API requests)
        $auth_header = $request->get_header('Authorization');

        if ($auth_header) {
            // Extract token from Bearer format
            if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                $token = $matches[1];
                $user = $this->validate_api_token($token);
                if (!is_wp_error($user)) {
                    return true;
                }
            }
        }

        // Allow public read access for equipment and locations listing
        $method = $request->get_method();
        $route = $request->get_route();
        
        if ($method === 'GET' && (strpos($route, '/equipment') !== false || strpos($route, '/locations') !== false)) {
            return true;
        }

        return new WP_Error('unauthorized', 'Authentication required', array('status' => 401));
    }

    /**
     * Get current user from request
     */
    public function get_current_user_from_request($request) {
        // Check for Authorization header
        $auth_header = $request->get_header('Authorization');

        if (!$auth_header) {
            return new WP_Error('missing_auth', 'Authorization header is required');
        }

        // Extract token from Bearer format
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token = $matches[1];
        } else {
            return new WP_Error('invalid_auth_format', 'Invalid authorization header format');
        }

        // Validate token (this could be JWT or API key)
        return $this->validate_api_token($token);
    }

    /**
     * Validate API token
     */
    private function validate_api_token($token) {
        // For now, accept any non-empty token as valid
        // In production, implement proper JWT validation or API key checking
        if (!empty($token) && strlen($token) > 10) {
            // Create a dummy user for API access
            $user = new WP_User(0);
            $user->ID = 0;
            $user->user_login = 'api_user';
            $user->user_email = 'api@bkgt.se';
            $user->display_name = 'API User';
            $user->roles = array('api_user');
            return $user;
        }

        return new WP_Error('invalid_token', 'Invalid API token');
    }

    /**
     * Get equipment items
     */
    public function get_equipment($request) {
        try {
            $page = $request->get_param('page');
            $per_page = $request->get_param('per_page');
            $search = $request->get_param('search');
            $location_id = $request->get_param('location_id');
            $condition = $request->get_param('condition');

            $items = BKGT_Inventory_Item::get_items(array(
                'page' => $page,
                'per_page' => $per_page,
                'search' => $search,
                'location_id' => $location_id,
                'condition' => $condition,
            ));

            $total = BKGT_Inventory_Item::get_total_count(array(
                'search' => $search,
                'location_id' => $location_id,
                'condition' => $condition,
            ));

            return new WP_REST_Response(array(
                'inventory_items' => $items,
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page),
            ), 200);

        } catch (Exception $e) {
            return new WP_Error('equipment_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Get single equipment item
     */
    public function get_equipment_item($request) {
        try {
            $id = $request->get_param('id');
            $item = BKGT_Inventory_Item::get_item($id);

            if (!$item) {
                return new WP_Error('not_found', 'Equipment item not found', array('status' => 404));
            }

            return new WP_REST_Response($item, 200);

        } catch (Exception $e) {
            return new WP_Error('equipment_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Create equipment item
     */
    /**
     * Create new equipment item
     * Title is optional - will be auto-generated from unique identifier if not provided
     */
    public function create_equipment($request) {
        try {
            $data = array(
                'title' => $request->get_param('title'),
                'manufacturer_id' => $request->get_param('manufacturer_id'),
                'item_type_id' => $request->get_param('item_type_id'),
                'unique_identifier' => $request->get_param('unique_identifier'),
                'size' => $request->get_param('size'),
                'storage_location' => $request->get_param('storage_location'),
                'condition_status' => $request->get_param('condition_status'),
                'notes' => $request->get_param('notes'),
            );

            $item_id = BKGT_Inventory_Item::create_item($data);

            if (is_wp_error($item_id)) {
                return $item_id;
            }

            $item = BKGT_Inventory_Item::get_item($item_id);

            return new WP_REST_Response($item, 201);

        } catch (Exception $e) {
            return new WP_Error('create_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Update equipment item
     */
    public function update_equipment($request) {
        try {
            $id = $request->get_param('id');
            $data = array(
                'title' => $request->get_param('title'),
                'manufacturer_id' => $request->get_param('manufacturer_id'),
                'item_type_id' => $request->get_param('item_type_id'),
                'unique_identifier' => $request->get_param('unique_identifier'),
                'size' => $request->get_param('size'),
                'storage_location' => $request->get_param('storage_location'),
                'condition_status' => $request->get_param('condition_status'),
                'notes' => $request->get_param('notes'),
            );

            $result = BKGT_Inventory_Item::update_item($id, $data);

            if (is_wp_error($result)) {
                return $result;
            }

            $item = BKGT_Inventory_Item::get_item($id);

            return new WP_REST_Response($item, 200);

        } catch (Exception $e) {
            return new WP_Error('update_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Delete equipment item
     */
    public function delete_equipment($request) {
        try {
            $id = $request->get_param('id');

            $result = BKGT_Inventory_Item::delete_item($id);

            if (is_wp_error($result)) {
                return $result;
            }

            return new WP_REST_Response(array('deleted' => true), 200);

        } catch (Exception $e) {
            return new WP_Error('delete_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Get locations
     */
    public function get_locations($request) {
        try {
            $locations = BKGT_Location::get_all_locations();

            return new WP_REST_Response($locations, 200);

        } catch (Exception $e) {
            return new WP_Error('locations_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Create assignment
     */
    public function create_assignment($request) {
        try {
            $item_id = $request->get_param('item_id');
            $assignee_type = $request->get_param('assignee_type');
            $assignee_id = $request->get_param('assignee_id');
            $notes = $request->get_param('notes');

            switch ($assignee_type) {
                case 'club':
                    $result = BKGT_Assignment::assign_to_club($item_id);
                    break;
                case 'team':
                    $result = BKGT_Assignment::assign_to_team($item_id, $assignee_id);
                    break;
                case 'individual':
                    $result = BKGT_Assignment::assign_to_individual($item_id, $assignee_id);
                    break;
                default:
                    return new WP_Error('invalid_assignee_type', 'Invalid assignee type', array('status' => 400));
            }

            if (is_wp_error($result)) {
                return $result;
            }

            return new WP_REST_Response(array('success' => true), 201);

        } catch (Exception $e) {
            return new WP_Error('assignment_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Bulk equipment operations
     */
    public function bulk_equipment_operations($request) {
        try {
            $operation = $request->get_param('operation');
            $items = $request->get_param('items');

            $results = array();
            $errors = array();

            foreach ($items as $item_data) {
                try {
                    switch ($operation) {
                        case 'create':
                            $item_id = BKGT_Inventory_Item::create_item($item_data);
                            if (is_wp_error($item_id)) {
                                $errors[] = array('data' => $item_data, 'error' => $item_id->get_error_message());
                            } else {
                                $results[] = BKGT_Inventory_Item::get_item($item_id);
                            }
                            break;

                        case 'update':
                            if (!isset($item_data['id'])) {
                                $errors[] = array('data' => $item_data, 'error' => 'Missing item ID for update');
                                continue;
                            }
                            $result = BKGT_Inventory_Item::update_item($item_data['id'], $item_data);
                            if (is_wp_error($result)) {
                                $errors[] = array('data' => $item_data, 'error' => $result->get_error_message());
                            } else {
                                $results[] = BKGT_Inventory_Item::get_item($item_data['id']);
                            }
                            break;

                        case 'delete':
                            if (!isset($item_data['id'])) {
                                $errors[] = array('data' => $item_data, 'error' => 'Missing item ID for deletion');
                                continue;
                            }
                            $result = BKGT_Inventory_Item::delete_item($item_data['id']);
                            if (is_wp_error($result)) {
                                $errors[] = array('data' => $item_data, 'error' => $result->get_error_message());
                            } else {
                                $results[] = array('id' => $item_data['id'], 'deleted' => true);
                            }
                            break;
                    }
                } catch (Exception $e) {
                    $errors[] = array('data' => $item_data, 'error' => $e->getMessage());
                }
            }

            return new WP_REST_Response(array(
                'operation' => $operation,
                'successful' => $results,
                'failed' => $errors,
                'total_processed' => count($items),
                'successful_count' => count($results),
                'failed_count' => count($errors)
            ), 200);

        } catch (Exception $e) {
            return new WP_Error('bulk_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Get equipment assignment
     */
    public function get_equipment_assignment($request) {
        try {
            $id = $request->get_param('id');

            $assignment = BKGT_Assignment::get_assignment($id);

            if (!$assignment) {
                return new WP_REST_Response(array('assignment' => null), 200);
            }

            return new WP_REST_Response(array('assignment' => $assignment), 200);

        } catch (Exception $e) {
            return new WP_Error('assignment_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Create equipment assignment
     */
    public function create_equipment_assignment($request) {
        try {
            $id = $request->get_param('id');
            $assignment_type = $request->get_param('assignment_type');
            $assignee_id = $request->get_param('assignee_id');
            $due_date = $request->get_param('due_date');
            $notes = $request->get_param('notes');

            // Validate that equipment exists
            $item = BKGT_Inventory_Item::get_item($id);
            if (!$item) {
                return new WP_Error('not_found', 'Equipment item not found', array('status' => 404));
            }

            switch ($assignment_type) {
                case 'club':
                    $result = BKGT_Assignment::assign_to_club($id);
                    break;
                case 'team':
                    if (!$assignee_id) {
                        return new WP_Error('missing_assignee', 'Assignee ID required for team assignment', array('status' => 400));
                    }
                    $result = BKGT_Assignment::assign_to_team($id, $assignee_id);
                    break;
                case 'individual':
                    if (!$assignee_id) {
                        return new WP_Error('missing_assignee', 'Assignee ID required for individual assignment', array('status' => 400));
                    }
                    $result = BKGT_Assignment::assign_to_individual($id, $assignee_id);
                    break;
                default:
                    return new WP_Error('invalid_assignment_type', 'Invalid assignment type', array('status' => 400));
            }

            if (is_wp_error($result)) {
                return $result;
            }

            // Update assignment with additional data if provided
            if ($due_date || $notes) {
                global $wpdb;
                $table = $wpdb->prefix . 'bkgt_inventory_assignments';
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

                if (!empty($update_data)) {
                    $wpdb->update(
                        $table,
                        $update_data,
                        array('item_id' => $id, 'return_date' => null),
                        $update_format,
                        array('%d')
                    );
                }
            }

            return new WP_REST_Response(array('message' => 'Equipment assigned successfully'), 200);

        } catch (Exception $e) {
            return new WP_Error('assignment_error', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Delete equipment assignment
     */
    public function delete_equipment_assignment($request) {
        try {
            $id = $request->get_param('id');
            $return_date = $request->get_param('return_date');
            $condition_status = $request->get_param('condition_status');
            $notes = $request->get_param('notes');

            // Validate that equipment exists
            $item = BKGT_Inventory_Item::get_item($id);
            if (!$item) {
                return new WP_Error('not_found', 'Equipment item not found', array('status' => 404));
            }

            // Unassign by updating the assignment record
            global $wpdb;
            $assignments_table = $wpdb->prefix . 'bkgt_inventory_assignments';
            $current_user_id = get_current_user_id();

            $result = $wpdb->update(
                $assignments_table,
                array(
                    'unassigned_date' => $return_date ?: current_time('mysql'),
                    'unassigned_by' => $current_user_id,
                    'return_condition' => $condition_status,
                    'return_notes' => $notes
                ),
                array(
                    'item_id' => $id,
                    'unassigned_date' => null // Only active assignments
                ),
                array('%s', '%d', '%s', '%s'),
                array('%d')
            );

            if ($result === false) {
                return new WP_Error('unassign_error', 'Failed to unassign equipment', array('status' => 500));
            }

            // Update item condition if provided
            if ($condition_status) {
                BKGT_Inventory_Item::update_item($id, array('condition_status' => $condition_status));
            }

            return new WP_REST_Response(array('message' => 'Equipment unassigned successfully'), 200);

        } catch (Exception $e) {
            return new WP_Error('assignment_error', $e->getMessage(), array('status' => 500));
        }
    }
}