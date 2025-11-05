<?php
require_once('wp-load.php');

echo "=== WordPress Permalink Structure Analysis ===\n\n";

global $wpdb;

// Check current permalink structure
$permalink_structure = get_option('permalink_structure');
echo "Current permalink structure: '$permalink_structure'\n";

if (empty($permalink_structure)) {
    echo "✅ Using default (?page_id=) format\n";
} else {
    echo "⚠️ Using custom permalink structure: $permalink_structure\n";
    echo "This might cause inconsistent URLs\n";
}

echo "\n=== Page URL Analysis ===\n";

// Get all published pages
$pages = get_posts(array(
    'post_type' => 'page',
    'post_status' => 'publish',
    'numberposts' => -1
));

echo "Found " . count($pages) . " published pages:\n\n";

foreach ($pages as $page) {
    $page_id_url = home_url("?page_id={$page->ID}");
    $permalink = get_permalink($page->ID);

    echo "Page: {$page->post_title}\n";
    echo "  ID: {$page->ID}\n";
    echo "  Slug: {$page->post_name}\n";
    echo "  page_id URL: $page_id_url\n";
    echo "  Permalink: $permalink\n";

    if ($permalink !== $page_id_url) {
        echo "  ⚠️ URLs don't match!\n";
    } else {
        echo "  ✅ URLs match\n";
    }
    echo "\n";
}

echo "=== Custom Post Types ===\n";

// Check for custom post types that might have pages
$custom_post_types = get_post_types(array(
    'public' => true,
    '_builtin' => false
), 'objects');

foreach ($custom_post_types as $cpt) {
    echo "Custom Post Type: {$cpt->name} ({$cpt->label})\n";
    echo "  Rewrite: " . ($cpt->rewrite ? 'enabled' : 'disabled') . "\n";
    if ($cpt->rewrite && isset($cpt->rewrite['slug'])) {
        echo "  Slug: {$cpt->rewrite['slug']}\n";
    }
    echo "\n";
}

echo "=== Recommendations ===\n";
if (!empty($permalink_structure)) {
    echo "To ensure uniform ?page_id= URLs:\n";
    echo "1. Go to WordPress Admin > Settings > Permalinks\n";
    echo "2. Select 'Plain' permalink structure\n";
    echo "3. Save changes\n";
    echo "This will force all pages to use ?page_id= format\n";
} else {
    echo "Permalink structure is already set to use ?page_id= format\n";
    echo "If some pages still show different URLs, check for:\n";
    echo "- Custom rewrite rules in theme functions.php\n";
    echo "- Plugin conflicts\n";
    echo "- Page-specific redirects\n";
}
?>