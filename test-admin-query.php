<?php
require_once('wp-load.php');

// Simulate admin query
$args = array(
    'post_type' => 'bkgt_inventory_item',
    'post_status' => 'any',
    'posts_per_page' => -1
);

$query = new WP_Query($args);
echo 'Total posts found: ' . $query->found_posts . "\n";

if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $title = get_the_title();
        $status = get_post_status();
        echo "Post ID {$post_id}: {$title} (Status: {$status})\n";
    }
} else {
    echo "No posts found\n";
}
?>