<?php
require_once('wp-load.php');

$post = get_post(15);
echo "Page 15 content: '" . $post->post_content . "'\n";
echo "Page slug: " . $post->post_name . "\n";
echo "Page title: " . $post->post_title . "\n";
?>