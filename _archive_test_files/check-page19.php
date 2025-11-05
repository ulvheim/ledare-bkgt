<?php
require_once('wp-load.php');

$page = get_post(19);
echo "Page 19 Title: " . $page->post_title . "\n";
echo "Page 19 Content:\n";
echo $page->post_content . "\n";
?>