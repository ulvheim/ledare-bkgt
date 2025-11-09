<?php
/**
 * Restore Teams - Re-scrape and clean up properly
 */

require_once('wp-load.php');

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

global $wpdb;

echo "=== Restore Teams from svenskalag.se ===\n\n";

try {
    $table_name = $wpdb->prefix . 'bkgt_teams';

    // Clear all teams first
    $wpdb->query("TRUNCATE TABLE $table_name");
    echo "Cleared all existing teams\n";

    // Load scraper and database
    if (!class_exists('BKGT_Scraper')) {
        $scraper_files = array(
            WP_PLUGIN_DIR . '/bkgt-data-scraping/includes/class-bkgt-scraper.php',
            WP_PLUGIN_DIR . '/bkgt-core/includes/class-bkgt-scraper.php',
            get_template_directory() . '/includes/class-bkgt-scraper.php'
        );

        $loaded = false;
        foreach ($scraper_files as $file) {
            if (file_exists($file)) {
                require_once($file);
                $loaded = true;
                break;
            }
        }

        if (!$loaded) {
            throw new Exception("BKGT_Scraper class not found");
        }
    }

    // Load database class
    if (!class_exists('BKGT_DataScraping_Database')) {
        $db_files = array(
            WP_PLUGIN_DIR . '/bkgt-data-scraping/includes/class-bkgt-database.php',
            WP_PLUGIN_DIR . '/bkgt-core/includes/class-bkgt-database.php',
            get_template_directory() . '/includes/class-bkgt-database.php'
        );

        $loaded = false;
        foreach ($db_files as $file) {
            if (file_exists($file)) {
                require_once($file);
                $loaded = true;
                break;
            }
        }

        if (!$loaded) {
            throw new Exception("BKGT_DataScraping_Database class not found");
        }
    }

    // Create database instance and scraper
    $db = new BKGT_DataScraping_Database();
    $scraper = new BKGT_Scraper($db);
    $scraped_count = $scraper->scrape_teams();

    echo "Scraped $scraped_count teams from svenskalag.se\n\n";

    // Show the teams that were scraped
    $teams = $wpdb->get_results("SELECT id, name, category FROM $table_name ORDER BY name");

    echo "Teams now in database:\n";
    foreach ($teams as $team) {
        echo "  ✅ {$team->name} (Category: {$team->category})\n";
    }

    echo "\nTotal teams: " . count($teams) . "\n";

    if (count($teams) == 8) {
        echo "\n✅ SUCCESS: Exactly 8 teams found, matching svenskalag.se\n";
        echo "The scraper is working correctly!\n";
    } else {
        echo "\n⚠️  WARNING: Expected 8 teams, but found " . count($teams) . "\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>