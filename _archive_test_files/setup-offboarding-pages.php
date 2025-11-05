<?php
/**
 * BKGT Offboarding - Setup Pages Script
 * Creates the necessary pages for the offboarding system
 */

// Include WordPress core
require_once('../../../wp-load.php');

// Check if user is logged in and has admin capabilities
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

$message = '';
$created_pages = array();

// Page configurations
$pages_to_create = array(
    array(
        'title' => 'Starta Avgång',
        'slug' => 'starta-avgang',
        'content' => '[bkgt_start_offboarding]',
        'template' => 'page.php'
    ),
    array(
        'title' => 'Avgångsstatus',
        'slug' => 'avgangsstatus',
        'content' => '[bkgt_offboarding_status]',
        'template' => 'page.php'
    )
);

// Check if pages already exist
$existing_pages = array();
foreach ($pages_to_create as $page_config) {
    $existing_page = get_page_by_path($page_config['slug']);
    if ($existing_page) {
        $existing_pages[] = $page_config['title'];
    }
}

if (!empty($existing_pages)) {
    $message = 'Vissa sidor finns redan: ' . implode(', ', $existing_pages) . '. ';
}

// Create pages
foreach ($pages_to_create as $page_config) {
    $existing_page = get_page_by_path($page_config['slug']);

    if (!$existing_page) {
        $page_id = wp_insert_post(array(
            'post_title' => $page_config['title'],
            'post_name' => $page_config['slug'],
            'post_content' => $page_config['content'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'page_template' => $page_config['template']
        ));

        if ($page_id && !is_wp_error($page_id)) {
            $created_pages[] = $page_config['title'];
        } else {
            $message .= 'Misslyckades att skapa sida: ' . $page_config['title'] . '. ';
        }
    }
}

if (!empty($created_pages)) {
    $message .= 'Skapade sidor: ' . implode(', ', $created_pages) . '.';
}

if (empty($message)) {
    $message = 'Inga nya sidor behövde skapas.';
}

?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BKGT Offboarding - Sidor Setup</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .pages-list {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .pages-list h3 {
            margin-top: 0;
            color: #495057;
        }
        .page-item {
            margin-bottom: 10px;
            padding: 10px;
            background: white;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007cba;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .action-links {
            text-align: center;
            margin-top: 30px;
        }
        .action-btn {
            background-color: #007cba;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0 10px;
        }
        .action-btn:hover {
            background-color: #005a87;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>BKGT Offboarding - Sidor Setup</h1>

        <div class="message">
            <?php echo esc_html($message); ?>
        </div>

        <div class="pages-list">
            <h3>Skapade Sidor</h3>
            <?php foreach ($pages_to_create as $page_config): ?>
                <?php
                $page = get_page_by_path($page_config['slug']);
                $status = $page ? 'Skapad' : 'Finns inte';
                $url = $page ? get_permalink($page->ID) : '#';
                ?>
                <div class="page-item">
                    <strong><?php echo esc_html($page_config['title']); ?></strong>
                    <span style="color: <?php echo $page ? 'green' : 'red'; ?>;">(<?php echo $status; ?>)</span>
                    <?php if ($page): ?>
                        - <a href="<?php echo esc_url($url); ?>" target="_blank">Visa sida</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="action-links">
            <a href="<?php echo admin_url('edit.php?post_type=page'); ?>" class="action-btn">Hantera Sidor</a>
            <a href="<?php echo admin_url('admin.php?page=bkgt-offboarding'); ?>" class="action-btn">Offboarding Admin</a>
        </div>

        <p style="text-align: center;">
            <a href="<?php echo admin_url(); ?>" class="back-link">← Tillbaka till Admin</a>
        </p>
    </div>
</body>
</html>