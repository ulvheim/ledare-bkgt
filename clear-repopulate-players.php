<?php
/**
 * Clear and Repopulate Players Script
 *
 * This script clears all players from the database and repopulates them
 * by scraping from svenskalag.se. Players are treated as atomic reference data.
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

echo "=== BKGT Players Clear and Repopulate Script ===\n\n";

try {
    // Step 1: Clear all players
    echo "Step 1: Clearing all players...\n";

    $players_count_before = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players");
    echo "Players before clear: {$players_count_before}\n";

    $result = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bkgt_players");

    if ($result === false) {
        throw new Exception("Failed to clear players table: " . $wpdb->last_error);
    }

    echo "✅ Players table cleared successfully\n\n";

    // Step 2: Run scraper to repopulate players
    echo "Step 2: Running player scraper...\n";

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

    // Run player scraping
    $scraped_count = $scraper->scrape_players();

    echo "✅ Scraped {$scraped_count} players\n";

    // Step 3: Verify results
    echo "\nStep 3: Verifying results...\n";

    $players_count_after = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_players");
    echo "Players after repopulation: {$players_count_after}\n";

    // Show sample players
    $sample_players = $wpdb->get_results("SELECT first_name, last_name, team_id FROM {$wpdb->prefix}bkgt_players LIMIT 5");

    echo "\nSample players:\n";
    foreach ($sample_players as $player) {
        echo "- {$player->first_name} {$player->last_name} (Team ID: {$player->team_id})\n";
    }

    echo "\n=== Script completed successfully ===\n";
    echo "Players are now populated as atomic reference data from svenskalag.se\n";
    echo "Manual player creation/update/deletion is disabled in the API\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>