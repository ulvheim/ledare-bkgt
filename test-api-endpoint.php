<?php
/**
 * Test API Endpoint Directly
 */

require_once('wp-load.php');

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

// Simulate admin user
wp_set_current_user(1);

echo "Testing clear_and_repopulate_teams API endpoint...\n\n";

try {
    // Load the endpoints class
    if (!class_exists('BKGT_API_Endpoints')) {
        $files = array(
            WP_PLUGIN_DIR . '/bkgt-api/includes/class-bkgt-endpoints.php',
            WP_PLUGIN_DIR . '/bkgt-inventory/includes/class-api-endpoints.php',
            get_template_directory() . '/includes/class-bkgt-endpoints.php'
        );

        $loaded = false;
        foreach ($files as $file) {
            if (file_exists($file)) {
                require_once($file);
                $loaded = true;
                break;
            }
        }

        if (!$loaded) {
            throw new Exception("BKGT_API_Endpoints class not found");
        }
    }

    // Create instance and call the method
    $endpoints = new BKGT_API_Endpoints();
    $request = new WP_REST_Request('POST', '/bkgt/v1/admin/teams/clear-repopulate');

    $result = $endpoints->clear_and_repopulate_teams($request);

    echo "API Result: " . json_encode($result) . "\n\n";

    // Check teams after
    global $wpdb;
    $teams = $wpdb->get_results("SELECT COUNT(*) as count FROM {$wpdb->prefix}bkgt_teams");
    echo "Teams in database after: " . $teams[0]->count . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>