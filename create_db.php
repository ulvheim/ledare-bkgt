<?php
require_once('wp-load.php');

echo "Checking all tables...\n";

global $wpdb;

// Check all tables
$all_tables = $wpdb->get_results('SHOW TABLES');
echo "All tables:\n";
foreach($all_tables as $table) {
    $table_name = 'Tables_in_' . $wpdb->dbname;
    $table_value = $table->{$table_name};
    if (strpos($table_value, 'bkgt') !== false) {
        echo $table_value . "\n";
    }
}

// Check specific bkgt tables
$tables = $wpdb->get_results('SHOW TABLES LIKE "wp_bkgt_%"');
echo "\nBKGT Tables with LIKE:\n";
foreach($tables as $table) {
    $table_name = 'Tables_in_' . $wpdb->dbname;
    echo $table->{$table_name} . "\n";
}
?>