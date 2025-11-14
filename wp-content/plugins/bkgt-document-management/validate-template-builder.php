<?php
/**
 * Template Builder Validation Script
 * Simple script to validate template builder functionality
 */

// Define WordPress environment
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_USE_THEMES', false);

// Include WordPress core
require_once '../../../wp-load.php';

// Check if plugin is active
if (!is_plugin_active('bkgt-document-management/bkgt-document-management.php')) {
    echo "❌ Plugin is not active\n";
    exit(1);
}

echo "✅ Plugin is active\n";

// Check if template system class exists
if (!class_exists('BKGT_Template_System')) {
    echo "❌ BKGT_Template_System class not found\n";
    exit(1);
}

echo "✅ Template system class exists\n";

// Check if admin class exists
if (!class_exists('BKGT_Document_Admin')) {
    echo "❌ BKGT_Document_Admin class not found\n";
    exit(1);
}

echo "✅ Admin class exists\n";

// Check if template builder method exists
$admin = new BKGT_Document_Admin();
if (!method_exists($admin, 'template_builder_page')) {
    echo "❌ template_builder_page method not found\n";
    exit(1);
}

echo "✅ Template builder method exists\n";

// Check if AJAX handler is registered
if (!has_action('wp_ajax_bkgt_save_template_builder')) {
    echo "❌ AJAX handler not registered\n";
    exit(1);
}

echo "✅ AJAX handler is registered\n";

// Check template system functionality
$template_system = new BKGT_Template_System();
$variables = $template_system->get_available_variables();

if (empty($variables)) {
    echo "❌ No template variables found\n";
    exit(1);
}

echo "✅ Template variables loaded (" . count($variables) . " variables)\n";

// Check database table exists
global $wpdb;
$table_name = $wpdb->prefix . 'bkgt_document_templates';
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    echo "❌ Template database table not found\n";
    exit(1);
}

echo "✅ Template database table exists\n";

echo "\n🎉 All validation checks passed! Template builder should be working.\n";
echo "\nTo test manually:\n";
echo "1. Go to WordPress Admin > Dokument > Mallbyggare\n";
echo "2. Try dragging components to the canvas\n";
echo "3. Try saving a template\n";
echo "4. Check browser console for JavaScript errors\n";
?>