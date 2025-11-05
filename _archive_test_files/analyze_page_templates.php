<?php
require_once('wp-load.php');

echo "=== Page Template Analysis ===\n\n";

global $wpdb;

// Get all published pages with their templates
$pages = get_posts(array(
    'post_type' => 'page',
    'post_status' => 'publish',
    'numberposts' => -1
));

echo "Published pages and their templates:\n\n";

foreach ($pages as $page) {
    $template = get_page_template_slug($page->ID);
    $template_display = $template ? $template : 'default';

    echo "Page: {$page->post_title}\n";
    echo "  ID: {$page->ID}\n";
    echo "  Slug: {$page->post_name}\n";
    echo "  Template: $template_display\n";
    echo "  URL: " . get_permalink($page->ID) . "\n";
    echo "\n";
}

// Check what templates exist
$template_dir = get_template_directory() . '/';
$templates = glob($template_dir . 'page-*.php');

echo "Available page templates:\n";
foreach ($templates as $template) {
    $filename = basename($template);
    echo "- $filename\n";
}

echo "\n=== Navigation Analysis ===\n";
echo "Current navigation in header.php expects these pages:\n";
echo "- lag (teams overview)\n";
echo "- spelare (players)\n";
echo "- utvardering (evaluation)\n";
echo "- utrustning (equipment - ID 15)\n";
echo "- dokument (documents - ID 16)\n";
echo "- kommunikation (communication - ID 17)\n";

echo "\n=== Missing Pages ===\n";
$missing_pages = array(
    'lag' => 'Teams overview page',
    'spelare' => 'Players page',
    'utvardering' => 'Evaluation page'
);

foreach ($missing_pages as $slug => $description) {
    $page = get_page_by_path($slug);
    if (!$page) {
        echo "❌ Missing page: $slug ($description)\n";
    } else {
        echo "✅ Found page: $slug (ID: {$page->ID})\n";
    }
}
?>