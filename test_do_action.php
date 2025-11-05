<?php
require_once('wp-load.php');

// Simulate AJAX request
$_REQUEST['action'] = 'bkgt_get_templates';

// Simulate logged in user
wp_set_current_user(1);

echo "Testing do_action for AJAX...\n";

ob_start();
do_action('wp_ajax_bkgt_get_templates');
$output = ob_get_clean();

echo "Output: " . $output . "\n";
?>