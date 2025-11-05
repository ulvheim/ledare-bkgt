<?php
require_once('wp-load.php');

echo "WordPress loaded successfully\n";

if (is_plugin_active('bkgt-document-management/bkgt-document-management.php')) {
    echo "DMS Plugin is active\n";
} else {
    echo "DMS Plugin is NOT active\n";
}

// Check for any PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Try to instantiate the plugin class
try {
    if (class_exists('BKGT_Document_Management')) {
        echo "DMS Plugin class exists\n";
        $instance = BKGT_Document_Management::get_instance();
        echo "DMS Plugin instance created successfully\n";
    } else {
        echo "DMS Plugin class does NOT exist\n";
    }
} catch (Exception $e) {
    echo "Error instantiating DMS Plugin: " . $e->getMessage() . "\n";
}
?>