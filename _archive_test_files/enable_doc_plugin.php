<?php
require_once('wp-load.php');

$active_plugins = get_option('active_plugins');
$active_plugins[] = 'bkgt-document-management/bkgt-document-management.php';
$active_plugins = array_unique($active_plugins);

update_option('active_plugins', $active_plugins);
echo 'Document management plugin re-enabled\n';
?>