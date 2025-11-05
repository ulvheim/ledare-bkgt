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

    // Simulate a request to the page
    $url = home_url("/?page_id=$page_id");
    echo "URL: $url\n";

    // Check if user is logged in
    $is_logged_in = is_user_logged_in();
    echo "User logged in: " . ($is_logged_in ? 'YES' : 'NO') . "\n";

    if (!$is_logged_in) {
        echo "Expected: Should show login required message\n";
    }

    // Get page content
    $page = get_post($page_id);
    if ($page) {
        echo "Page exists: YES\n";
        echo "Page template: " . get_page_template_slug($page_id) . "\n";

        // Check page content for shortcodes
        $content = $page->post_content;
        if (strpos($content, '[bkgt_') !== false) {
            echo "Contains BKGT shortcodes: YES\n";
        } else {
            echo "Contains BKGT shortcodes: NO\n";
        }
    } else {
        echo "Page exists: NO\n";
    }

    echo "\n";
}
?>