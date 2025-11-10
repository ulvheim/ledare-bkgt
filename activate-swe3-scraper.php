<?php
// Activate BKGT SWE3 Scraper Plugin
define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "Activating BKGT SWE3 Scraper Plugin...\n";
echo "======================================\n\n";

$plugin_path = 'bkgt-swe3-scraper/bkgt-swe3-scraper.php';

// Check if plugin is already active
if (is_plugin_active($plugin_path)) {
    echo "✓ Plugin is already active!\n";
} else {
    // Attempt to activate the plugin
    $result = activate_plugin($plugin_path);

    if (is_wp_error($result)) {
        echo "✗ Plugin activation failed:\n";
        echo "Error: " . $result->get_error_message() . "\n";
        exit(1);
    }

    if (is_plugin_active($plugin_path)) {
        echo "✓ Plugin activated successfully!\n";
    } else {
        echo "✗ Plugin activation failed for unknown reason!\n";
        exit(1);
    }
}

// Test that the plugin is working
echo "\nTesting plugin functionality...\n";

try {
    $plugin = bkgt_swe3_scraper();
    echo "✓ Plugin instance created successfully\n";

    if (isset($plugin->scraper)) {
        echo "✓ Scraper component loaded\n";
    } else {
        echo "✗ Scraper component missing\n";
        exit(1);
    }

    if (isset($plugin->parser)) {
        echo "✓ Parser component loaded\n";
    } else {
        echo "✗ Parser component missing\n";
        exit(1);
    }

    if (isset($plugin->scheduler)) {
        echo "✓ Scheduler component loaded\n";
    } else {
        echo "✗ Scheduler component missing\n";
        exit(1);
    }

    if (isset($plugin->dms_integration)) {
        echo "✓ DMS integration component loaded\n";
    } else {
        echo "✗ DMS integration component missing\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "✗ Plugin test failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✓ All plugin components loaded successfully!\n";
echo "\nNow running manual SWE3 scrape...\n";
echo "=================================\n";

// Run manual scrape
try {
    $scraper = $plugin->scraper;
    echo "Directly calling scraper->execute_scrape()...\n";
    $result = $scraper->execute_scrape();

    if ($result) {
        echo "✓ Scraping completed successfully!\n";
    } else {
        echo "✗ Scraping failed!\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Scraping error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\nChecking results...\n";

// Check how many documents are now in DMS and SWE3 tracking
global $wpdb;
$dms_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bkgt_document'");
$swe3_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_swe3_documents");

echo "DMS documents: $dms_count\n";
echo "SWE3 tracked documents: $swe3_count\n";

if ($swe3_count > 0) {
    echo "\n✓ SWE3 scraper is working! Documents found and processed.\n";

    // Show some details about the documents
    $documents = $wpdb->get_results("SELECT title, document_type, status FROM {$wpdb->prefix}bkgt_swe3_documents ORDER BY scraped_date DESC LIMIT 5");
    echo "\nRecent documents:\n";
    foreach ($documents as $doc) {
        echo "- {$doc->title} ({$doc->document_type}) - {$doc->status}\n";
    }
} else {
    echo "\n⚠ No SWE3 documents found. This could be normal if no documents were available or if there were connection issues.\n";
}

echo "\n✓ SWE3 Scraper activation and test completed!\n";
?>