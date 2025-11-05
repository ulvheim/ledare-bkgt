<?php
// Disable the problematic plugin
require_once('wp-load.php');
require_once('wp-admin/includes/plugin.php');

// Deactivate the document management plugin
deactivate_plugins('bkgt-document-management/bkgt-document-management.php');

echo "Plugin deactivated. Please check the site now.";
?>