<?php
require_once('wp-load.php');

// Simulate AJAX request
$_POST['action'] = 'bkgt_get_templates';
$_POST['nonce'] = wp_create_nonce('bkgt_document_frontend');

// Simulate logged in user (replace with actual user ID)
wp_set_current_user(1);

echo "Testing AJAX template loading...\n";

try {
    $plugin = new BKGT_Document_Management();
    $frontend = $plugin->get_frontend_class();

    // Call the AJAX method directly
    ob_start();
    $frontend->ajax_get_templates();
    $output = ob_get_clean();

    echo "AJAX Response:\n";
    echo $output . "\n";

    // Also test the default templates directly
    $templates = $frontend->get_default_templates();
    echo "\nDefault templates count: " . count($templates) . "\n";
    foreach ($templates as $template) {
        echo "- " . $template['id'] . ": " . $template['name'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>