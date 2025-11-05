<?php
// Test the AJAX endpoint directly
require_once('wp-load.php');

// Simulate the AJAX request
$_REQUEST['action'] = 'bkgt_get_templates';
$_REQUEST['nonce'] = 'c245d2ab6e'; // From the HTML

// Simulate logged in user
wp_set_current_user(1);

echo "Testing AJAX endpoint bkgt_get_templates...\n";

// Call the AJAX handler directly
if (function_exists('do_action')) {
    ob_start();
    do_action('wp_ajax_bkgt_get_templates');
    $output = ob_get_clean();

    echo "AJAX Response:\n";
    echo $output . "\n";
} else {
    echo "do_action not available\n";
}

// Also test the method directly
try {
    $plugin = new BKGT_Document_Management();
    $frontend = $plugin->get_frontend_class();

    echo "\nTesting get_default_templates() directly:\n";
    $templates = $frontend->get_default_templates();
    echo "Number of templates: " . count($templates) . "\n";

    foreach ($templates as $template) {
        echo "- " . $template['id'] . ": " . $template['name'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>