<?php
require_once('wp-load.php');

$active_plugins = get_option('active_plugins');
$new_plugins = array_filter($active_plugins, function($p) {
    return strpos($p, 'bkgt-document-management') === false;
});

update_option('active_plugins', $new_plugins);
echo 'Document management plugin disabled\n';
?>