<?php
require_once('wp-load.php');

// Simulate logged in user
wp_set_current_user(1);

echo "Testing AJAX method directly...\n";

try {
    $plugin = new BKGT_Document_Management();
    $frontend = $plugin->get_frontend_class();

    // Call the method directly without nonce check
    echo "Calling ajax_get_templates method:\n";
    ob_start();
    $frontend->ajax_get_templates();
    $output = ob_get_clean();
    echo "Output: " . $output . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>