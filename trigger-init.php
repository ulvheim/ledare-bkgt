<?php
// Load WordPress to trigger the init hook
require_once('wp-load.php');

// This should trigger the ensure_default_data method
do_action('init');

echo "WordPress init triggered. Check debug.log for BKGT Inventory messages.\n";
?>