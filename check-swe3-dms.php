<?php
define('WP_USE_THEMES', false);
require 'wp-load.php';

global $wpdb;

// Count SWE3 documents in DMS
$count = $wpdb->get_var("
    SELECT COUNT(*) FROM {$wpdb->posts}
    WHERE post_type = 'bkgt_document'
    AND post_title LIKE '%SWE3%'
");

echo "SWE3 documents in DMS: $count\n";

// Also show some recent ones
$documents = $wpdb->get_results("
    SELECT ID, post_title, post_date FROM {$wpdb->posts}
    WHERE post_type = 'bkgt_document'
    AND post_title LIKE '%SWE3%'
    ORDER BY post_date DESC
    LIMIT 10
");

echo "\nRecent SWE3 documents in DMS:\n";
foreach ($documents as $doc) {
    echo "- {$doc->ID}: {$doc->post_title} ({$doc->post_date})\n";
}

// Also check for category
$category = get_term_by('name', 'SWE3 Official Documents', 'bkgt_doc_category');
if ($category) {
    echo "\nSWE3 Official Documents category exists (ID: {$category->term_id})\n";
    
    // Count documents in this category
    $cat_count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->term_relationships}
        WHERE term_taxonomy_id = %d
    ", $category->term_id));
    
    echo "Documents in category: $cat_count\n";
}
?>
