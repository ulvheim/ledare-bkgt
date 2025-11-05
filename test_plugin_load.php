<?php
require_once('wp-load.php');

// Test if the plugin is loaded
echo "WordPress loaded: " . (defined('ABSPATH') ? 'YES' : 'NO') . "\n";
echo "Current user: " . get_current_user_id() . "\n";

// Check if the plugin class exists
if (class_exists('BKGT_Document_Management')) {
    echo "BKGT_Document_Management class exists: YES\n";

    try {
        $plugin = new BKGT_Document_Management();
        echo "Plugin instance created: YES\n";

        if (method_exists($plugin, 'get_frontend_class')) {
            $frontend = $plugin->get_frontend_class();
            echo "Frontend class obtained: YES\n";

            if (method_exists($frontend, 'ajax_get_templates')) {
                echo "ajax_get_templates method exists: YES\n";

                // Test the method directly
                echo "\nCalling ajax_get_templates method:\n";
                ob_start();
                $frontend->ajax_get_templates();
                $output = ob_get_clean();
                echo "Method output: " . $output . "\n";

            } else {
                echo "ajax_get_templates method exists: NO\n";
            }
        } else {
            echo "get_frontend_class method exists: NO\n";
        }

    } catch (Exception $e) {
        echo "Error creating plugin: " . $e->getMessage() . "\n";
    }

} else {
    echo "BKGT_Document_Management class exists: NO\n";
}

// Check if AJAX actions are registered
echo "\nChecking AJAX hooks:\n";
global $wp_filter;
if (isset($wp_filter['wp_ajax_bkgt_get_templates'])) {
    echo "wp_ajax_bkgt_get_templates hook registered: YES\n";
} else {
    echo "wp_ajax_bkgt_get_templates hook registered: NO\n";
}
?>