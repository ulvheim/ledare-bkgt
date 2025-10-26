<?php
/**
 * Command-line script to set up BKGT pages
 * Run with: php setup-pages.php
 */

// Bootstrap WordPress
define('WP_USE_THEMES', false);
require_once __DIR__ . '/../../../wp-load.php';

// Function to create or update a page
function create_bkgt_page($title, $slug, $description = '') {
    echo "Creating/updating page: $title ($slug)\n";

    // Check if page already exists
    $existing_page = get_page_by_path($slug);

    if ($existing_page) {
        echo "  Page exists (ID: {$existing_page->ID}), updating...\n";
        // Update existing page
        $result = wp_update_post(array(
            'ID' => $existing_page->ID,
            'post_content' => $description,
            'post_status' => 'publish'
        ));
        if (is_wp_error($result)) {
            echo "  ERROR updating page: " . $result->get_error_message() . "\n";
            return false;
        }
        return $existing_page->ID;
    } else {
        echo "  Creating new page...\n";
        // Create new page
        $page_id = wp_insert_post(array(
            'post_title' => $title,
            'post_name' => $slug,
            'post_content' => $description,
            'post_status' => 'publish',
            'post_type' => 'page'
        ));

        if (is_wp_error($page_id)) {
            echo "  ERROR creating page: " . $page_id->get_error_message() . "\n";
            return false;
        }

        echo "  Created page with ID: $page_id\n";
        return $page_id;
    }
}

echo "=== BKGT Pages Setup Script ===\n\n";

// Create the pages
$pages_created = array();

$pages_created[] = create_bkgt_page(
    'Spelare',
    'spelare',
    'Här hittar du alla våra spelare i BKGT.'
);

$pages_created[] = create_bkgt_page(
    'Matcher & Event',
    'matcher',
    'Kommande matcher och event för BKGT.'
);

$pages_created[] = create_bkgt_page(
    'Lagöversikt',
    'lagoversikt',
    'Statistik och översikt över BKGT laget.'
);

echo "\n=== Setup Complete ===\n";
echo "Pages created/updated:\n";

$page_data = array(
    array('title' => 'Spelare', 'slug' => 'spelare'),
    array('title' => 'Matcher & Event', 'slug' => 'matcher'),
    array('title' => 'Lagöversikt', 'slug' => 'lagoversikt')
);

$success_count = 0;
foreach ($pages_created as $index => $page_id) {
    if ($page_id) {
        $page = get_post($page_id);
        $permalink = get_permalink($page_id);
        echo "✅ {$page_data[$index]['title']} - $permalink\n";
        $success_count++;
    } else {
        echo "❌ {$page_data[$index]['title']} - Failed to create\n";
    }
}

echo "\nSuccessfully created/updated $success_count out of " . count($pages_created) . " pages.\n";

if ($success_count > 0) {
    echo "\n📋 Next Steps:\n";
    echo "1. Visit your WordPress site to see the new pages\n";
    echo "2. Check that the BKGT Data Scraping plugin is activated\n";
    echo "3. Add player/event data via the plugin's admin interface if needed\n";
    echo "4. The pages will automatically display BKGT content based on their slugs\n";
}

echo "\n=== Script Finished ===\n";
?>