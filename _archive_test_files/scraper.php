<?php
require_once('wp-load.php');

echo "Starting BKGT Data Scraper...\n";

try {
    require_once(WP_PLUGIN_DIR . '/bkgt-data-scraping-disabled/includes/class-bkgt-database.php');
    require_once(WP_PLUGIN_DIR . '/bkgt-data-scraping-disabled/includes/class-bkgt-scraper.php');

    $db = new BKGT_Database();
    $scraper = new BKGT_Scraper($db);

    echo "Scraping players...\n";
    $scraper->scrape_players();
    echo "Players scraped successfully!\n";

    echo "Scraping events...\n";
    $scraper->scrape_events();
    echo "Events scraped successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>