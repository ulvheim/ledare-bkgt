<?php
/**
 * BKGT Offboarding System - Test Page
 * This page tests the offboarding plugin functionality
 */

// Include WordPress core
require_once('wp-load.php');

// Check if user is logged in and has admin capabilities
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

$message = '';
$test_results = array();

// Test 1: Check if plugin file exists
$plugin_file = WP_PLUGIN_DIR . '/bkgt-offboarding/bkgt-offboarding.php';
$test_results['plugin_file'] = array(
    'name' => 'Plugin File Exists',
    'status' => file_exists($plugin_file) ? 'PASS' : 'FAIL',
    'details' => file_exists($plugin_file) ? 'File found at: ' . $plugin_file : 'File not found'
);

// Test 2: Check if plugin is active
$test_results['plugin_active'] = array(
    'name' => 'Plugin Active',
    'status' => is_plugin_active('bkgt-offboarding/bkgt-offboarding.php') ? 'PASS' : 'FAIL',
    'details' => is_plugin_active('bkgt-offboarding/bkgt-offboarding.php') ? 'Plugin is active' : 'Plugin is not active'
);

// Test 3: Check database tables
global $wpdb;
$tables = array(
    'wp_bkgt_offboarding_tasks',
    'wp_bkgt_offboarding_equipment',
    'wp_bkgt_offboarding_notifications'
);

$table_status = array();
foreach ($tables as $table) {
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    $table_status[$table] = $table_exists ? 'PASS' : 'FAIL';
}

$test_results['database_tables'] = array(
    'name' => 'Database Tables',
    'status' => !in_array('FAIL', $table_status) ? 'PASS' : 'FAIL',
    'details' => 'Tasks: ' . $table_status['wp_bkgt_offboarding_tasks'] .
                ', Equipment: ' . $table_status['wp_bkgt_offboarding_equipment'] .
                ', Notifications: ' . $table_status['wp_bkgt_offboarding_notifications']
);

// Test 4: Check shortcodes
$shortcodes = array('bkgt_start_offboarding', 'bkgt_offboarding_status');
$shortcode_status = array();

foreach ($shortcodes as $shortcode) {
    $shortcode_status[$shortcode] = shortcode_exists($shortcode) ? 'PASS' : 'FAIL';
}

$test_results['shortcodes'] = array(
    'name' => 'Shortcodes Registered',
    'status' => !in_array('FAIL', $shortcode_status) ? 'PASS' : 'FAIL',
    'details' => 'Start Offboarding: ' . $shortcode_status['bkgt_start_offboarding'] .
                ', Status: ' . $shortcode_status['bkgt_offboarding_status']
);

// Test 5: Check AJAX endpoints
$ajax_actions = array('bkgt_update_offboarding_task', 'bkgt_complete_offboarding');
$ajax_status = array();

foreach ($ajax_actions as $action) {
    $ajax_status[$action] = has_action('wp_ajax_' . $action) ? 'PASS' : 'FAIL';
}

$test_results['ajax_endpoints'] = array(
    'name' => 'AJAX Endpoints',
    'status' => !in_array('FAIL', $ajax_status) ? 'PASS' : 'FAIL',
    'details' => 'Update Task: ' . $ajax_status['bkgt_update_offboarding_task'] .
                ', Complete: ' . $ajax_status['bkgt_complete_offboarding']
);

// Test 6: Check CSS/JS files
$asset_files = array(
    'assets/css/frontend.css',
    'assets/css/admin.css',
    'assets/js/frontend.js',
    'assets/js/admin.js'
);

$asset_status = array();
foreach ($asset_files as $file) {
    $full_path = WP_PLUGIN_DIR . '/bkgt-offboarding/' . $file;
    $asset_status[$file] = file_exists($full_path) ? 'PASS' : 'FAIL';
}

$test_results['assets'] = array(
    'name' => 'Asset Files',
    'status' => !in_array('FAIL', $asset_status) ? 'PASS' : 'FAIL',
    'details' => 'Frontend CSS: ' . $asset_status['assets/css/frontend.css'] .
                ', Admin CSS: ' . $asset_status['assets/css/admin.css'] .
                ', Frontend JS: ' . $asset_status['assets/js/frontend.js'] .
                ', Admin JS: ' . $asset_status['assets/js/admin.js']
);

// Calculate overall status
$overall_status = 'PASS';
foreach ($test_results as $test) {
    if ($test['status'] === 'FAIL') {
        $overall_status = 'FAIL';
        break;
    }
}

?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BKGT Offboarding - Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        .overall-status {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-size: 18px;
            font-weight: bold;
        }
        .status-pass {
            background-color: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        .status-fail {
            background-color: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        .test-result {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        .test-header {
            background-color: #f8f9fa;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .test-name {
            font-weight: bold;
            margin: 0;
        }
        .test-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
        }
        .status-PASS {
            background-color: #d4edda;
            color: #155724;
        }
        .status-FAIL {
            background-color: #f8d7da;
            color: #721c24;
        }
        .test-details {
            padding: 15px;
            background-color: #ffffff;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007cba;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        .action-btn {
            background-color: #007cba;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0 10px;
        }
        .action-btn:hover {
            background-color: #005a87;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>BKGT Offboarding System - Testresultat</h1>

        <div class="overall-status status-<?php echo strtolower($overall_status); ?>">
            Övergripande Status: <?php echo $overall_status; ?>
        </div>

        <?php foreach ($test_results as $test): ?>
            <div class="test-result">
                <div class="test-header">
                    <h3 class="test-name"><?php echo esc_html($test['name']); ?></h3>
                    <span class="test-status status-<?php echo $test['status']; ?>">
                        <?php echo $test['status']; ?>
                    </span>
                </div>
                <div class="test-details">
                    <?php echo esc_html($test['details']); ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="actions">
            <a href="<?php echo admin_url('plugins.php'); ?>" class="action-btn">Plugins</a>
            <a href="<?php echo admin_url('admin.php?page=bkgt-offboarding'); ?>" class="action-btn">Offboarding Admin</a>
            <a href="<?php echo site_url('/offboarding-test/'); ?>" class="action-btn">Frontend Test</a>
        </div>

        <p style="text-align: center;">
            <a href="<?php echo admin_url(); ?>" class="back-link">← Tillbaka till Admin</a>
        </p>
    </div>
</body>
</html>