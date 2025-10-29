<?php
/**
 * BKGT Page Setup Script
 * Creates necessary WordPress pages with correct templates
 */

// Bootstrap WordPress
define('WP_USE_THEMES', false);
require_once __DIR__ . '/../../../wp-load.php';

function create_bkgt_page_with_template($title, $slug, $template, $description = '') {
    echo "Creating/updating page: $title ($slug) with template: $template\n";

    // Check if page already exists
    $existing_page = get_page_by_path($slug);

    $page_data = array(
        'post_title' => $title,
        'post_name' => $slug,
        'post_content' => $description,
        'post_status' => 'publish',
        'post_type' => 'page',
        'meta_input' => array(
            '_wp_page_template' => $template
        )
    );

    if ($existing_page) {
        echo "  Page exists (ID: {$existing_page->ID}), updating...\n";
        $page_data['ID'] = $existing_page->ID;
        $result = wp_update_post($page_data);
        if (is_wp_error($result)) {
            echo "  ERROR updating page: " . $result->get_error_message() . "\n";
            return false;
        }
        return $existing_page->ID;
    } else {
        echo "  Creating new page...\n";
        $page_id = wp_insert_post($page_data);

        if (is_wp_error($page_id)) {
            echo "  ERROR creating page: " . $page_id->get_error_message() . "\n";
            return false;
        }

        echo "  Created page with ID: $page_id\n";
        return $page_id;
    }
}

echo "=== BKGT Page Setup Script ===\n\n";

// Create pages with templates
$pages_created = array();

$pages_created[] = create_bkgt_page_with_template(
    'Lagöversikt',
    'lagoversikt',
    'page-team-overview.php',
    'Statistik och översikt över BKGT laget.'
);

$pages_created[] = create_bkgt_page_with_template(
    'Spelare',
    'spelare',
    'page-players.php',
    'Här hittar du alla våra spelare i BKGT.'
);

$pages_created[] = create_bkgt_page_with_template(
    'Matcher & Event',
    'matcher',
    'page-events.php',
    'Kommande matcher och event för BKGT.'
);

echo "\n=== Setup Complete ===\n";
echo "Pages created/updated with templates:\n";

$page_data = array(
    array('title' => 'Lagöversikt', 'slug' => 'lagoversikt', 'template' => 'page-team-overview.php'),
    array('title' => 'Spelare', 'slug' => 'spelare', 'template' => 'page-players.php'),
    array('title' => 'Matcher & Event', 'slug' => 'matcher', 'template' => 'page-events.php')
);

$success_count = 0;
foreach ($pages_created as $index => $page_id) {
    if ($page_id) {
        $page = get_post($page_id);
        $permalink = get_permalink($page_id);
        $template = get_post_meta($page_id, '_wp_page_template', true);
        echo "✅ {$page_data[$index]['title']} - $permalink (Template: $template)\n";
        $success_count++;
    } else {
        echo "❌ {$page_data[$index]['title']} - Failed to create\n";
    }
}

echo "\nSuccessfully created/updated $success_count out of " . count($pages_created) . " pages.\n";

if ($success_count > 0) {
    echo "\n📋 Next Steps:\n";
    echo "1. Visit the pages to verify they display correctly\n";
    echo "2. Check that the team player plugin is activated\n";
    echo "3. Add sample data via the admin dashboard if needed\n";
}

echo "\n=== Script Finished ===\n";