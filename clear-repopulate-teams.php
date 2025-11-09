<?php
/**
 * Clear and Repopulate Teams Script
 *
 * This script clears all teams from the database and repopulates them
 * by scraping from svenskalag.se. Teams are treated as atomic reference data.
 */

require_once('wp-load.php');

if (!defined('ABSPATH')) {
    die('Direct access not allowed');
}

// Check if user has admin privileges
if (!current_user_can('manage_options')) {
    die('Admin privileges required');
}

global $wpdb;

echo "=== BKGT Teams Clear and Repopulate Script ===\n\n";

try {
    // Step 1: Clear all teams
    echo "Step 1: Clearing all teams...\n";

    $teams_count_before = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");
    echo "Teams before clear: {$teams_count_before}\n";

    $result = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bkgt_teams");

    if ($result === false) {
        throw new Exception("Failed to clear teams table: " . $wpdb->last_error);
    }

    echo "✅ Teams table cleared successfully\n\n";

    // Step 2: Run scraper to repopulate teams
    echo "Step 2: Running team scraper...\n";

    if (!class_exists('BKGT_Scraper')) {
        // Try to load the scraper class
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

    // Initialize scraper
    $scraper = new BKGT_Scraper();

    // Run team scraping
    $scraped_count = $scraper->scrape_teams();

    echo "✅ Scraped {$scraped_count} teams\n";

    // Step 3: Verify results
    echo "\nStep 3: Verifying results...\n";

    $teams_count_after = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_teams");
    echo "Teams after repopulation: {$teams_count_after}\n";

    // Show sample teams
    $sample_teams = $wpdb->get_results("SELECT name, source_id, source_url FROM {$wpdb->prefix}bkgt_teams LIMIT 5");

    echo "\nSample teams:\n";
    foreach ($sample_teams as $team) {
        echo "- {$team->name} (ID: {$team->source_id})\n";
    }

    echo "\n=== Script completed successfully ===\n";
    echo "Teams are now populated as atomic reference data from svenskalag.se\n";
    echo "Manual team creation/update/deletion is disabled in the API\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>