<?php
require_once('wp-load.php');

echo "Checking page 15 details...\n";
$post = get_post(15);
if ($post) {
    echo "ID: " . $post->ID . "\n";
    echo "Title: " . $post->post_title . "\n";
    echo "Slug: " . $post->post_name . "\n";
    echo "Content length: " . strlen($post->post_content) . "\n";
    echo "Content preview: " . substr($post->post_content, 0, 100) . "\n";
    
    // Check if the conditional in page.php would trigger
    $page_slug = $post->post_name;
    $page_title = $post->post_title;
    
    $would_trigger = false;
    if ($page_slug === 'utrustning' || strpos(strtolower($page_title), 'utrustning') !== false) {
        $would_trigger = true;
    }
    
    echo "Would trigger theme shortcode: " . ($would_trigger ? "YES" : "NO") . "\n";
    
    // Test rendering the page content with shortcodes
    echo "\nRendering page content with shortcodes...\n";
    $rendered = do_shortcode($post->post_content);
    echo "Rendered content length: " . strlen($rendered) . "\n";
    if (strlen($rendered) > 200) {
        echo "Rendered content preview: " . substr($rendered, 0, 200) . "...\n";
    } else {
        echo "Rendered content: " . $rendered . "\n";
    }
} else {
    echo "Page not found\n";
}
?>