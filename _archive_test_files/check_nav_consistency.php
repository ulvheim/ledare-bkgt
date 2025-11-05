<?php
require_once('wp-load.php');

echo "=== Navigation URL Consistency Check ===\n\n";

// Check what pages exist for the navigation slugs
$nav_slugs = array('lag', 'spelare', 'utvardering');
$nav_pages = array();

foreach ($nav_slugs as $slug) {
    $page = get_page_by_path($slug);
    if ($page) {
        $nav_pages[$slug] = $page;
        echo "Found page for slug '$slug':\n";
        echo "  ID: {$page->ID}\n";
        echo "  Title: {$page->post_title}\n";
        echo "  Current URL: " . get_permalink($page->ID) . "\n";
        echo "  page_id URL: " . home_url("?page_id={$page->ID}") . "\n";
        echo "\n";
    } else {
        echo "❌ No page found for slug '$slug'\n\n";
    }
}

// Check current navigation in header.php
echo "=== Current Navigation Links ===\n";
echo "From header.php analysis:\n";
echo "- Dashboard: home_url('/') ✅\n";
echo "- Lag: home_url('/lag') " . (isset($nav_pages['lag']) ? "→ should be ?page_id={$nav_pages['lag']->ID}" : "❌ page not found") . "\n";
echo "- Spelare: home_url('/spelare') " . (isset($nav_pages['spelare']) ? "→ should be ?page_id={$nav_pages['spelare']->ID}" : "❌ page not found") . "\n";
echo "- Utrustning: home_url('/?page_id=15') ✅\n";
echo "- Dokument: home_url('/?page_id=16') ✅\n";
echo "- Kommunikation: home_url('/?page_id=17') ✅\n";
echo "- Utvardering: home_url('/utvardering') " . (isset($nav_pages['utvardering']) ? "→ should be ?page_id={$nav_pages['utvardering']->ID}" : "❌ page not found") . "\n";

echo "\n=== Recommended Changes ===\n";
if (isset($nav_pages['lag'])) {
    echo "Change: home_url('/lag') → home_url('/?page_id={$nav_pages['lag']->ID}')\n";
}
if (isset($nav_pages['spelare'])) {
    echo "Change: home_url('/spelare') → home_url('/?page_id={$nav_pages['spelare']->ID}')\n";
}
if (isset($nav_pages['utvardering'])) {
    echo "Change: home_url('/utvardering') → home_url('/?page_id={$nav_pages['utvardering']->ID}')\n";
}
?>