<?php
require 'wp-load.php';

// Test the pages
$pages = [
    22 => 'Team Overview',
    20 => 'Players',
    21 => 'Events'
];

foreach ($pages as $page_id => $name) {
    echo "=== $name (page_id=$page_id) ===\n";

    $page = get_post($page_id);
    if ($page) {
        echo "Title: " . $page->post_title . "\n";
        echo "Content length: " . strlen($page->post_content) . "\n";
        echo "Content preview: " . substr($page->post_content, 0, 200) . "\n";

        // Check template
        $template = get_page_template_slug($page_id);
        echo "Template: " . ($template ?: 'default') . "\n";

        // Check if content has shortcodes
        if (strpos($page->post_content, '[bkgt_') !== false) {
            echo "Has BKGT shortcodes: YES\n";
        } else {
            echo "Has BKGT shortcodes: NO\n";
        }
    } else {
        echo "Page not found!\n";
    }

    echo "\n";
}
?>