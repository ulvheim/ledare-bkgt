<?php
require_once('wp-load.php');

$post = get_post(16);
if ($post) {
    echo "Page ID: " . $post->ID . "\n";
    echo "Page Title: " . $post->post_title . "\n";
    echo "Page Content:\n" . $post->post_content . "\n";
} else {
    echo "Page not found\n";
}
?>