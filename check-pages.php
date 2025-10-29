<?php
require_once('wp-load.php');

echo "Document-related pages:\n";
$pages = get_pages();
foreach($pages as $page) {
    if (strpos(strtolower($page->post_title), 'dokument') !== false ||
        strpos(strtolower($page->post_content), 'bkgt_documents') !== false) {
        echo $page->ID . ': ' . $page->post_title . ' (' . $page->post_name . ') - ' . get_permalink($page) . "\n";
    }
}

echo "\nAll pages:\n";
foreach($pages as $page) {
    echo $page->ID . ': ' . $page->post_title . ' (' . $page->post_name . ') - ' . get_permalink($page) . "\n";
}
?>