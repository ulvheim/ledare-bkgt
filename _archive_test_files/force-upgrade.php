<?php
// Force Database Upgrade
define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "Forcing BKGT Database Upgrade\n";
echo "==============================\n\n";

if (class_exists('BKGT_Database')) {
    $db = new BKGT_Database();
    
    // Temporarily set version to allow upgrade
    update_option('bkgt_db_version', '0.1.0');
    
    $result = $db->upgrade_tables();

    if ($result) {
        echo "✓ Database upgrade completed successfully\n";

        // Check results
        global $wpdb;
        $duplicate_teams = $wpdb->get_var("
            SELECT COUNT(*) as duplicates
            FROM (
                SELECT name, season, COUNT(*) as cnt
                FROM wp_bkgt_teams
                GROUP BY name, season
                HAVING cnt > 1
            ) as dupes
        ");

        echo "Remaining duplicates: $duplicate_teams\n";

        $constraint_check = $wpdb->get_results("SHOW INDEX FROM wp_bkgt_teams WHERE Key_name = 'name_season'");
        if (!empty($constraint_check)) {
            echo "✓ Unique constraint added successfully\n";
        } else {
            echo "⚠ Unique constraint still missing\n";
        }

    } else {
        echo "✗ Database upgrade failed\n";
    }
} else {
    echo "✗ BKGT_Database class not found\n";
}

echo "\nUpgrade process completed.\n";
?>