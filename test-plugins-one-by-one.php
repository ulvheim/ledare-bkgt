<?php
require_once('wp-load.php');

// List of plugins to test (based on previous active plugins)
$plugins_to_test = [
    'bkgt-data-scraping/bkgt-data-scraping.php',
    'bkgt-communication/bkgt-communication.php',
    'bkgt-document-management/bkgt-document-management.php',
    'bkgt-inventory/bkgt-inventory.php',
    'bkgt-user-management/bkgt-user-management.php'
];

echo "Testing plugins one by one...\n\n";

foreach ($plugins_to_test as $plugin) {
    echo "Testing plugin: $plugin\n";

    // Enable this plugin
    $active_plugins = get_option('active_plugins', array());
    $active_plugins[] = $plugin;
    update_option('active_plugins', $active_plugins);

    // Test site load
    $url = 'https://ledare.bkgt.se';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'user_agent' => 'Test Script'
        ]
    ]);

    $response = file_get_contents($url, false, $context);

    if ($response !== false) {
        if (strpos($response, 'critical error') !== false || strpos($response, 'fatal error') !== false) {
            echo "❌ FAILED: Plugin $plugin causes critical error!\n\n";
            // Remove this plugin and stop
            $active_plugins = array_diff($active_plugins, [$plugin]);
            update_option('active_plugins', $active_plugins);
            break;
        } else {
            echo "✅ OK: Plugin $plugin loads successfully\n\n";
        }
    } else {
        echo "❌ FAILED: Plugin $plugin prevents site from loading!\n\n";
        // Remove this plugin and stop
        $active_plugins = array_diff($active_plugins, [$plugin]);
        update_option('active_plugins', $active_plugins);
        break;
    }
}

echo "Final active plugins: " . count(get_option('active_plugins')) . "\n";
?>