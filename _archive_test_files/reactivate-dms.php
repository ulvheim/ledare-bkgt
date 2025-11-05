<?php
// Deactivate and reactivate the DMS plugin to trigger database setup
require_once('wp-load.php');
require_once('wp-admin/includes/plugin.php');

// Deactivate first
deactivate_plugins('bkgt-document-management/bkgt-document-management.php');
echo "Plugin deactivated.\n";

// Reactivate to trigger activation hook
activate_plugin('bkgt-document-management/bkgt-document-management.php');
echo "Plugin reactivated. Database tables should be created.\n";
?>