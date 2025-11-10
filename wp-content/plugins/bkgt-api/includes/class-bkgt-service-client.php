<?php
/**
 * BKGT API Service Client
 *
 * Handles service-to-service API calls using the service API key
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_API_Service_Client {

    /**
     * Service API key
     */
    private $service_key;

    /**
     * API base URL
     */
    private $api_base_url;

    /**
     * Constructor
     */
    public function __construct() {
        $this->service_key = get_option('bkgt_service_api_key');
        // Delay API base URL initialization until first use
        // $this->api_base_url = rest_url('bkgt/v1');
    }

    /**
     * Make an authenticated API call using service key
     */
    public function call($endpoint, $method = 'GET', $data = array(), $headers = array()) {
        // Initialize API base URL if not already done
        if (empty($this->api_base_url)) {
            // Construct REST URL manually to avoid initialization issues
            $site_url = get_site_url();
            $this->api_base_url = rtrim($site_url, '/') . '/wp-json/bkgt/v1';
        }

        $url = $this->api_base_url . ltrim($endpoint, '/');

        $default_headers = array(
            'X-API-Key' => $this->service_key,
            'Content-Type' => 'application/json',
        );

        $headers = array_merge($default_headers, $headers);

        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30,
        );

        if (!empty($data) && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = wp_json_encode($data);
        } elseif (!empty($data) && $method === 'GET') {
            $url = add_query_arg($data, $url);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            error_log('BKGT API Service Call Error: ' . $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        // Decode JSON response
        $decoded_body = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $decoded_body = $response_body;
        }

        return array(
            'code' => $response_code,
            'body' => $decoded_body,
            'raw_body' => $response_body,
            'headers' => wp_remote_retrieve_headers($response),
        );
    }

    /**
     * GET request
     */
    public function get($endpoint, $params = array()) {
        return $this->call($endpoint, 'GET', $params);
    }

    /**
     * POST request
     */
    public function post($endpoint, $data = array()) {
        return $this->call($endpoint, 'POST', $data);
    }

    /**
     * PUT request
     */
    public function put($endpoint, $data = array()) {
        return $this->call($endpoint, 'PUT', $data);
    }

    /**
     * DELETE request
     */
    public function delete($endpoint, $data = array()) {
        return $this->call($endpoint, 'DELETE', $data);
    }

    /**
     * Get equipment list
     */
    public function get_equipment($params = array()) {
        return $this->get('/equipment', $params);
    }

    /**
     * Get single equipment item
     */
    public function get_equipment_item($id) {
        return $this->get('/equipment/' . $id);
    }

    /**
     * Create equipment item
     */
    public function create_equipment($data) {
        return $this->post('/equipment', $data);
    }

    /**
     * Update equipment item
     */
    public function update_equipment($id, $data) {
        return $this->put('/equipment/' . $id, $data);
    }

    /**
     * Delete equipment item
     */
    public function delete_equipment($id) {
        return $this->delete('/equipment/' . $id);
    }

    /**
     * Get equipment assignment
     */
    public function get_equipment_assignment($id) {
        return $this->get('/equipment/' . $id . '/assignment');
    }

    /**
     * Assign equipment
     */
    public function assign_equipment($id, $assignment_data) {
        return $this->post('/equipment/' . $id . '/assignment', $assignment_data);
    }

    /**
     * Unassign equipment
     */
    public function unassign_equipment($id) {
        return $this->delete('/equipment/' . $id . '/assignment');
    }

    /**
     * Bulk equipment operations
     */
    public function bulk_equipment_operation($operation, $data) {
        return $this->post('/equipment/bulk', array_merge($data, array('operation' => $operation)));
    }

    /**
     * Get health status
     */
    public function get_health_status() {
        return $this->get('/health');
    }

    /**
     * Test service key validity
     */
    public function test_service_key() {
        $response = $this->get_health_status();

        if (is_wp_error($response)) {
            return false;
        }

        return isset($response['code']) && $response['code'] === 200 &&
               isset($response['body']['status']) && $response['body']['status'] === 'healthy';
    }
}