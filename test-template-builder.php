<?php
/**
 * Simple test script to check if the template builder changes are working
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
    require_once ABSPATH . 'wp-load.php';
}

echo "<h1>Template Builder Test</h1>";

// Check if the admin class exists
if (class_exists('BKGT_Document_Admin')) {
    echo "<p style='color: green;'>✓ BKGT_Document_Admin class exists</p>";

    // Try to instantiate the class
    try {
        $admin = new BKGT_Document_Admin();
        echo "<p style='color: green;'>✓ BKGT_Document_Admin can be instantiated</p>";

        // Check if the new methods exist
        if (method_exists($admin, 'api_diagnostic_page')) {
            echo "<p style='color: green;'>✓ api_diagnostic_page method exists</p>";
        } else {
            echo "<p style='color: red;'>✗ api_diagnostic_page method missing</p>";
        }

        if (method_exists($admin, 'template_builder_page')) {
            echo "<p style='color: green;'>✓ template_builder_page method exists</p>";
        } else {
            echo "<p style='color: red;'>✗ template_builder_page method missing</p>";
        }

        if (method_exists($admin, 'ajax_save_template_builder')) {
            echo "<p style='color: green;'>✓ ajax_save_template_builder method exists</p>";
        } else {
            echo "<p style='color: red;'>✗ ajax_save_template_builder method missing</p>";
        }

    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error instantiating BKGT_Document_Admin: " . $e->getMessage() . "</p>";
    }

} else {
    echo "<p style='color: red;'>✗ BKGT_Document_Admin class not found</p>";
}

// Check if the template system class exists
if (class_exists('BKGT_Template_System')) {
    echo "<p style='color: green;'>✓ BKGT_Template_System class exists</p>";
} else {
    echo "<p style='color: red;'>✗ BKGT_Template_System class not found</p>";
}

// Check database table
global $wpdb;
$table_name = $wpdb->prefix . 'bkgt_document_templates';
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
    echo "<p style='color: green;'>✓ Database table wp_bkgt_document_templates exists</p>";
} else {
    echo "<p style='color: red;'>✗ Database table wp_bkgt_document_templates missing</p>";
}

echo "<hr>";
echo "<p><a href='" . admin_url('admin.php?page=bkgt-api-diagnostic') . "'>Go to API Diagnostics</a></p>";
echo "<p><a href='" . admin_url('admin.php?page=bkgt-template-builder') . "'>Go to Template Builder</a></p>";
?>