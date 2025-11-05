<?php
require_once('wp-load.php');
global $wpdb;

$tables = $wpdb->get_results("SHOW TABLES LIKE 'wp_bkgt_%'");
echo "BKGT Tables:\n";
foreach($tables as $table) {
    echo '- ' . reset($table) . "\n";
}
?>