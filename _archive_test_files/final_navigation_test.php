<?php
require_once('wp-load.php');

echo "=== Final Navigation URL Test ===\n\n";

// Simulate logged-in user
wp_set_current_user(1); // Admin user

// Test URLs that should show real data
$test_urls = array(
    'Equipment' => '?page_id=15',
    'Teams' => '?page_id=51',
    'Players' => '?page_id=52',
    'Documents' => '?page_id=53',
    'Communication' => '?page_id=17',
    'Evaluation' => '?page_id=16'
);

foreach ($test_urls as $page_name => $url) {
    echo "Testing $page_name ($url):\n";

    // Parse the URL to get page_id
    $query_string = parse_url($url, PHP_URL_QUERY);
    parse_str($query_string, $params);
    $page_id = $params['page_id'];

    // Get the page content
    $page = get_post($page_id);
    if (!$page) {
        echo "  ❌ Page $page_id not found\n";
        continue;
    }

    echo "  ✅ Page found: {$page->post_title}\n";

    // Check if page has shortcodes
    $content = $page->post_content;
    if (strpos($content, '[') !== false) {
        echo "  ✅ Has shortcodes\n";

        // Test shortcode output
        $shortcode_output = do_shortcode($content);

        // Check for database errors
        if (strpos($shortcode_output, 'wpdberror') !== false) {
            echo "  ❌ Contains database errors\n";
        } else {
            echo "  ✅ No database errors\n";
        }

        // Check for sample/mock data indicators
        $sample_indicators = array(
            'Sample Equipment Data',
            'Sample Team Data',
            'Sample Player Data',
            'Sample Document Data',
            'Sample Communication Data',
            'Sample Evaluation Data',
            'Mock Data',
            'Placeholder'
        );

        $has_sample_data = false;
        foreach ($sample_indicators as $indicator) {
            if (strpos($shortcode_output, $indicator) !== false) {
                $has_sample_data = true;
                echo "  ⚠️  Contains sample data: $indicator\n";
                break;
            }
        }

        if (!$has_sample_data) {
            echo "  ✅ No sample/mock data detected\n";
        }

        // Show content length as indicator of real data
        echo "  📊 Content length: " . strlen($shortcode_output) . " characters\n";

    } else {
        echo "  ⚠️  No shortcodes found\n";
    }

    echo "\n";
}

// Test inventory specifically
echo "=== Inventory Shortcode Test ===\n";
$inventory_output = do_shortcode('[bkgt_inventory]');
if (strpos($inventory_output, 'wpdberror') !== false) {
    echo "❌ Inventory has database errors\n";
} elseif (strpos($inventory_output, 'Sample Equipment Data') !== false) {
    echo "⚠️  Inventory still showing sample data\n";
} else {
    echo "✅ Inventory showing real data\n";
    echo "📊 Inventory content length: " . strlen($inventory_output) . " characters\n";
}