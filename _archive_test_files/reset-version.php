<?php
// Reset Database Version
define('WP_USE_THEMES', false);
require_once('wp-load.php');

delete_option('bkgt_db_version');
echo "Database version reset to allow re-upgrade\n";
?>