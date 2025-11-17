<?php
/**
 * SWE3 Integration Diagnostic Script
 * Check what's happening with the SWE3 to DMS integration
 */

define('WP_USE_THEMES', false);
require 'wp-load.php';

global $wpdb;

echo "=== SWE3 Integration Diagnostic ===\n\n";

// 1. Check if plugins are active
echo "1. Plugin Status:\n";
$active_plugins = get_option('active_plugins', array());
$swe3_active = in_array('bkgt-swe3-scraper/bkgt-swe3-scraper.php', $active_plugins);
$dms_active = in_array('bkgt-document-management/bkgt-document-management.php', $active_plugins);

echo "   SWE3 Scraper: " . ($swe3_active ? "ACTIVE" : "INACTIVE") . "\n";
echo "   DMS Plugin: " . ($dms_active ? "ACTIVE" : "INACTIVE") . "\n";

if (!$swe3_active || !$dms_active) {
    echo "\n❌ ISSUE: One or both plugins are not active!\n";
    exit(1);
}

// 2. Check database tables
echo "\n2. Database Tables:\n";
$swe3_table = $wpdb->prefix . 'bkgt_swe3_documents';
$dms_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bkgt_document'");

$swe3_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$swe3_table'") === $swe3_table;
echo "   SWE3 tracking table: " . ($swe3_table_exists ? "EXISTS" : "MISSING") . "\n";
echo "   DMS documents: $dms_posts found\n";

// 3. Check SWE3 scraper status
echo "\n3. SWE3 Scraper Status:\n";
$scrape_enabled = get_option('bkgt_swe3_scrape_enabled', 'yes');
$last_scrape = get_option('bkgt_swe3_last_scrape', 'Never');
$last_success = get_option('bkgt_swe3_last_successful_scrape', 'Never');

echo "   Scraping enabled: " . ($scrape_enabled === 'yes' ? "YES" : "NO") . "\n";
echo "   Last scrape: $last_scrape\n";
echo "   Last successful scrape: $last_success\n";

// 4. Check cron jobs
echo "\n4. Cron Jobs:\n";
$cron_jobs = get_option('cron', array());
$swe3_cron_found = false;
foreach ($cron_jobs as $timestamp => $jobs) {
    if (isset($jobs['bkgt_swe3_daily_scrape'])) {
        $next_run = date('Y-m-d H:i:s', $timestamp);
        echo "   SWE3 daily scrape scheduled: $next_run\n";
        $swe3_cron_found = true;
        break;
    }
}
if (!$swe3_cron_found) {
    echo "   SWE3 daily scrape: NOT SCHEDULED\n";
}

// 5. Check recent SWE3 documents in tracking table
echo "\n5. SWE3 Tracking Table:\n";
if ($swe3_table_exists) {
    $tracked_docs = $wpdb->get_results("SELECT swe3_id, title, status, scraped_date, dms_document_id FROM $swe3_table ORDER BY scraped_date DESC LIMIT 5");

    if (empty($tracked_docs)) {
        echo "   No documents tracked yet\n";
    } else {
        echo "   Recent tracked documents:\n";
        foreach ($tracked_docs as $doc) {
            $dms_status = $doc->dms_document_id ? "DMS ID: {$doc->dms_document_id}" : "No DMS document";
            echo "     - {$doc->swe3_id}: {$doc->title} ({$doc->status}) - $dms_status\n";
        }
    }
}

// 6. Test SWE3 website connectivity
echo "\n6. SWE3 Website Connectivity:\n";
$swe3_url = 'https://amerikanskfotboll.swe3.se/wp-json/wp/v2/media';
$response = wp_remote_get($swe3_url, array('timeout' => 10));

if (is_wp_error($response)) {
    echo "   ❌ Cannot connect to SWE3 API: " . $response->get_error_message() . "\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    echo "   SWE3 API response: HTTP $status_code\n";

    if ($status_code === 200) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (is_array($data)) {
            $pdf_count = count(array_filter($data, function($item) {
                return isset($item['mime_type']) && $item['mime_type'] === 'application/pdf';
            }));
            echo "   Found $pdf_count PDF documents available\n";
        }
    }
}

// 7. Check for errors in recent logs
echo "\n7. Error Analysis:\n";
if ($swe3_table_exists) {
    $error_count = $wpdb->get_var("SELECT COUNT(*) FROM $swe3_table WHERE status = 'error'");
    echo "   Documents with errors: $error_count\n";

    if ($error_count > 0) {
        $errors = $wpdb->get_results("SELECT title, error_message FROM $swe3_table WHERE status = 'error' LIMIT 3");
        echo "   Recent errors:\n";
        foreach ($errors as $error) {
            echo "     - {$error->title}: {$error->error_message}\n";
        }
    }
}

// 8. Recommendations
echo "\n8. Recommendations:\n";

$issues = array();

if (!$swe3_cron_found) {
    $issues[] = "SWE3 scraper is not scheduled to run daily";
}

if ($dms_posts == 0) {
    $issues[] = "No documents found in DMS - scraper may not be running";
}

if ($scrape_enabled !== 'yes') {
    $issues[] = "SWE3 scraping is disabled in settings";
}

if (empty($issues)) {
    echo "   ✅ No obvious issues found\n";
    echo "   Try running a manual scrape to test the integration\n";
} else {
    echo "   ⚠️  Issues found:\n";
    foreach ($issues as $issue) {
        echo "     - $issue\n";
    }
}

echo "\n=== Diagnostic Complete ===\n";
?>