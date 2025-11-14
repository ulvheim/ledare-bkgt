<?php
/**
 * Test REST endpoint directly by loading WordPress and the plugin
 */

define('WP_USE_THEMES', false);
require dirname(__FILE__) . '/wp-load.php';

// Check if plugin is active
$active = get_option('active_plugins', array());
echo "Active plugins count: " . count($active) . "\n";
echo "SWE3 scraper active: " . (in_array('bkgt-swe3-scraper/bkgt-swe3-scraper.php', $active) ? 'YES' : 'NO') . "\n";

// Load the plugin manually if not active
if (!in_array('bkgt-swe3-scraper/bkgt-swe3-scraper.php', $active)) {
    echo "Loading plugin manually...\n";
    require dirname(__FILE__) . '/wp-content/plugins/bkgt-swe3-scraper/bkgt-swe3-scraper.php';
    do_action('plugins_loaded');
    echo "Plugin loaded\n";
}

// Fire rest_api_init to register routes
do_action('rest_api_init');

// Now check if route is registered
global $wp_rest_server;
if ($wp_rest_server) {
    $routes = $wp_rest_server->get_routes();
    $swe3_routes = array_filter($routes, function($route) {
        return strpos($route, 'swe3') !== false;
    }, ARRAY_FILTER_USE_KEY);
    
    echo "Found " . count($swe3_routes) . " SWE3 routes\n";
    foreach ($swe3_routes as $route => $data) {
        echo "  - $route\n";
    }
} else {
    echo "REST server not initialized\n";
}

// Check for API version routes
$bkgt_routes = array_filter($routes ?? array(), function($route) {
    return strpos($route, 'bkgt/v1') !== false;
}, ARRAY_FILTER_USE_KEY);

echo "Found " . count($bkgt_routes) . " BKGT API routes\n";
foreach ($bkgt_routes as $route => $data) {
    echo "  - $route\n";
}

echo "\n✓ Test complete\n";
?>
