<?php
require_once('wp-load.php');

$result = activate_plugin('bkgt-data-scraping/bkgt-data-scraping.php');

if (is_wp_error($result)) {
    echo 'Activation failed: ' . $result->get_error_message() . PHP_EOL;
} else {
    echo 'Plugin activated successfully!' . PHP_EOL;
}

// Check if it's now active
$active_plugins = get_option('active_plugins');
if (in_array('bkgt-data-scraping/bkgt-data-scraping.php', $active_plugins)) {
    echo 'Plugin is now in active plugins list.' . PHP_EOL;
} else {
    echo 'Plugin is not in active plugins list.' . PHP_EOL;
}
?>