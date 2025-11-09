<?php
/**
 * Check Teams Table Structure
 */

require_once('wp-load.php');

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

global $wpdb;

echo "=== BKGT Teams Table Structure ===\n\n";

try {
    // Check if table exists
    $table_name = $wpdb->prefix . 'bkgt_teams';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

    if (!$table_exists) {
        echo "❌ Table $table_name does not exist\n";
        exit(1);
    }

    echo "✅ Table $table_name exists\n\n";

    // Get table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");

    echo "Table columns:\n";
    foreach ($columns as $column) {
        echo "  - {$column->Field}: {$column->Type}";
        if ($column->Null == 'NO') echo " NOT NULL";
        if ($column->Key) echo " ({$column->Key})";
        if ($column->Default !== null) echo " DEFAULT '{$column->Default}'";
        if ($column->Extra) echo " {$column->Extra}";
        echo "\n";
    }

    echo "\n";

    // Get sample data
    $sample = $wpdb->get_results("SELECT * FROM $table_name LIMIT 5");

    echo "Sample data (first 5 rows):\n";
    if (empty($sample)) {
        echo "  No data in table\n";
    } else {
        foreach ($sample as $row) {
            echo "  " . json_encode($row) . "\n";
        }
    }

    echo "\nTotal rows: " . $wpdb->get_var("SELECT COUNT(*) FROM $table_name") . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>