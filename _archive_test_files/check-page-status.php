<?php
require_once('wp-load.php');

$post = get_post(16);
echo "Post ID: " . $post->ID . "\n";
echo "Post Status: " . $post->post_status . "\n";
echo "Post Password: " . $post->post_password . "\n";

$visibility = get_post_meta(16, '_visibility', true);
echo "Visibility: " . $visibility . "\n";

$protected = post_password_required(16);
echo "Password Required: " . ($protected ? 'Yes' : 'No') . "\n";
?>