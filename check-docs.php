<?php
// Bootstrap WordPress
require_once 'wp-load.php';

global $wpdb;

echo "DMS documents:" . PHP_EOL;
$docs = $wpdb->get_results("SELECT ID, post_title, post_date FROM {$wpdb->posts} WHERE post_type = 'bkgt_document' ORDER BY post_date DESC");
foreach ($docs as $doc) {
    echo "- {$doc->ID}: {$doc->post_title} ({$doc->post_date})" . PHP_EOL;
}

echo PHP_EOL . "SWE3 tracked documents: 0" . PHP_EOL;

// Check if any DMS documents contain SWE3 in title
$swe3_in_title = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bkgt_document' AND post_title LIKE '%SWE3%'");
echo "DMS documents with SWE3 in title: $swe3_in_title" . PHP_EOL;
?>