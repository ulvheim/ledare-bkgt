<?php
require_once('wp-load.php');

echo "=== Creating Missing Navigation Pages ===\n\n";

// Define the pages that need to be created
$pages_to_create = array(
    array(
        'title' => 'Lag',
        'slug' => 'lag',
        'template' => 'page-team-overview.php',
        'content' => '<!-- Team overview content will be loaded by the page template -->'
    ),
    array(
        'title' => 'Spelare',
        'slug' => 'spelare',
        'template' => 'page-players.php',
        'content' => '<!-- Players content will be loaded by the page template -->'
    ),
    array(
        'title' => 'Utvärdering',
        'slug' => 'utvardering',
        'template' => 'page-events.php',
        'content' => '<!-- Events/evaluation content will be loaded by the page template -->'
    )
);

$created_pages = array();

foreach ($pages_to_create as $page_data) {
    // Check if page already exists
    $existing_page = get_page_by_path($page_data['slug']);
    if ($existing_page) {
        echo "⚠️ Page '{$page_data['title']}' already exists (ID: {$existing_page->ID})\n";
        $created_pages[$page_data['slug']] = $existing_page->ID;
        continue;
    }

    // Create the page
    $page_id = wp_insert_post(array(
        'post_title' => $page_data['title'],
        'post_name' => $page_data['slug'],
        'post_content' => $page_data['content'],
        'post_status' => 'publish',
        'post_type' => 'page',
        'page_template' => $page_data['template']
    ));

    if (is_wp_error($page_id)) {
        echo "❌ Failed to create page '{$page_data['title']}': " . $page_id->get_error_message() . "\n";
    } else {
        echo "✅ Created page '{$page_data['title']}' (ID: $page_id, Slug: {$page_data['slug']})\n";
        $created_pages[$page_data['slug']] = $page_id;

        // Set the page template
        update_post_meta($page_id, '_wp_page_template', $page_data['template']);
    }
}

echo "\n=== Page IDs for Navigation Update ===\n";
foreach ($created_pages as $slug => $id) {
    echo "$slug: $id → ?page_id=$id\n";
}

echo "\n=== Navigation Update Required ===\n";
echo "Update header.php to use these ?page_id= URLs:\n";
foreach ($created_pages as $slug => $id) {
    echo "- Change: home_url('/$slug') → home_url('/?page_id=$id')\n";
}

echo "\n=== Verification ===\n";
echo "After updating navigation, all pages should use ?page_id= format:\n";
foreach ($created_pages as $slug => $id) {
    echo "- https://ledare.bkgt.se/?page_id=$id ($slug)\n";
}
?>