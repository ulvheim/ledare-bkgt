<?php
require_once('wp-load.php');

echo "Checking page 15 content...\n";
$post = get_post(15);
if ($post) {
    echo "Page Title: " . $post->post_title . "\n";
    echo "Page Content:\n" . $post->post_content . "\n";
    
    // Check if content contains the shortcode
    if (strpos($post->post_content, '[bkgt_inventory]') !== false) {
        echo "\nShortcode found in content\n";
    } else {
        echo "\nShortcode NOT found in content\n";
    }
} else {
    echo "Page 15 not found\n";
}

// Test the shortcode directly
echo "\nTesting shortcode output...\n";
if (function_exists('bkgt_inventory_shortcode')) {
    $output = bkgt_inventory_shortcode(array());
    echo "Shortcode output length: " . strlen($output) . " characters\n";
    if (strlen($output) > 500) {
        echo "Shortcode output (first 500 chars):\n" . substr($output, 0, 500) . "...\n";
    } else {
        echo "Shortcode output:\n" . $output . "\n";
    }
} else {
    echo "Shortcode function not found\n";
}
?>