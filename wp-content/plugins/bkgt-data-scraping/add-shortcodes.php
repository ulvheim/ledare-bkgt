<?php
/**
 * Script to add BKGT shortcodes to WordPress pages
 * Access this file via: http://your-site.com/wp-content/plugins/bkgt-data-scraping/add-shortcodes.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once '../../../wp-load.php';
}

// Check if user is logged in and has admin privileges
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Function to create or update a page
function create_bkgt_page($title, $slug, $description = '') {
    // Check if page already exists
    $existing_page = get_page_by_path($slug);

    if ($existing_page) {
        // Update existing page
        wp_update_post(array(
            'ID' => $existing_page->ID,
            'post_content' => $description,
            'post_status' => 'publish'
        ));
        return $existing_page->ID;
    } else {
        // Create new page
        $page_id = wp_insert_post(array(
            'post_title' => $title,
            'post_name' => $slug,
            'post_content' => $description,
            'post_status' => 'publish',
            'post_type' => 'page'
        ));
        return $page_id;
    }
}

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

// Display results
echo '<!DOCTYPE html><html><head><title>BKGT Pages Created</title><style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .page-link{color:#007cba;text-decoration:none;} .page-link:hover{text-decoration:underline;}</style></head><body>';
echo '<h1 class="success">BKGT Pages Created Successfully! ✅</h1>';
echo '<p>The following pages have been created/updated and will automatically display BKGT content:</p>';
echo '<ul>';

$page_data = array(
    array('title' => 'Spelare', 'slug' => 'spelare', 'content' => 'Player list with filters'),
    array('title' => 'Matcher & Event', 'slug' => 'matcher', 'content' => 'Upcoming matches and events'),
    array('title' => 'Lagöversikt', 'slug' => 'lagoversikt', 'content' => 'Team statistics and overview')
);

foreach ($pages_created as $index => $page_id) {
    if ($page_id) {
        $page = get_post($page_id);
        $permalink = get_permalink($page_id);
        echo '<li><strong><a href="' . $permalink . '" target="_blank" class="page-link">' . $page_data[$index]['title'] . '</a></strong> - ' . $page_data[$index]['content'] . ' (ID: ' . $page_id . ')</li>';
    }
}

echo '</ul>';
echo '<div style="background:#f0f8ff;border:1px solid #007cba;padding:15px;margin:20px 0;border-radius:5px;">';
echo '<h3>📋 Next Steps:</h3>';
echo '<ol>';
echo '<li><strong>Visit each page</strong> using the links above to see the BKGT content</li>';
echo '<li><strong>Check the admin interface</strong> to ensure the BKGT Data Scraping plugin has player and event data</li>';
echo '<li><strong>Add sample data</strong> if needed using the plugin\'s admin interface</li>';
echo '<li><strong>Customize the pages</strong> by editing them in WordPress Admin → Pages</li>';
echo '</ol>';
echo '</div>';

echo '<p><a href="' . home_url() . '" style="background:#007cba;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">← Back to Homepage</a></p>';
echo '<p><em>Note: Make sure the BKGT Data Scraping plugin is activated and has data for the shortcodes to display content properly.</em></p>';
echo '</body></html>';
?>