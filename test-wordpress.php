<?php
// Simple test page to verify WordPress is working
require_once('wp-load.php');

echo "<h1>BKGT Website Test</h1>";
echo "<p>WordPress Version: " . get_bloginfo('version') . "</p>";
echo "<p>Site URL: " . get_bloginfo('url') . "</p>";
echo "<p>Theme: " . get_option('stylesheet') . "</p>";
echo "<p>Active Plugins: " . count(get_option('active_plugins')) . "</p>";

echo "<h2>Plugin Test</h2>";
if (function_exists('bkgt_shortcode_players')) {
    echo "<p>✅ BKGT Data Scraping plugin loaded successfully</p>";
} else {
    echo "<p>❌ BKGT Data Scraping plugin NOT loaded</p>";
}

echo "<h2>Shortcode Test</h2>";
echo do_shortcode('[bkgt_players]');
?>