<?php
require_once('wp-load.php');

echo "Checking AJAX actions...\n";

// Check if DOING_AJAX is defined
echo "DOING_AJAX defined: " . (defined('DOING_AJAX') ? 'YES' : 'NO') . "\n";
echo "is_admin(): " . (is_admin() ? 'YES' : 'NO') . "\n";

// Check if the action is registered
global $wp_filter;
if (isset($wp_filter['wp_ajax_bkgt_get_templates'])) {
    echo "wp_ajax_bkgt_get_templates hook registered: YES\n";
    echo "Number of callbacks: " . count($wp_filter['wp_ajax_bkgt_get_templates']->callbacks) . "\n";
} else {
    echo "wp_ajax_bkgt_get_templates hook registered: NO\n";
}

// Check if the plugin is loaded
if (class_exists('BKGT_Document_Management')) {
    echo "BKGT_Document_Management class loaded: YES\n";
} else {
    echo "BKGT_Document_Management class loaded: NO\n";
}

if (class_exists('BKGT_Document_Frontend')) {
    echo "BKGT_Document_Frontend class loaded: YES\n";
} else {
    echo "BKGT_Document_Frontend class loaded: NO\n";
}
?>