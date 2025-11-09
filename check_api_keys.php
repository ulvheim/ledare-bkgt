<?php
require_once('wp-load.php');
global $wpdb;

echo "CHECKING FOR ACTIVE API KEYS:\n";
echo "=============================\n";

$keys = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkgt_api_keys WHERE is_active = 1");

if (empty($keys)) {
    echo "No active API keys found.\n";
    echo "Generating a test key...\n\n";

    // Generate a test key
    if (class_exists('BKGT_API_Auth')) {
        $auth = new BKGT_API_Auth();
        $test_key = $auth->generate_api_key('Test Key - Equipment Verification', 1, array('read', 'write'));

        if ($test_key) {
            echo "✅ Generated test API key: $test_key\n";
            echo "You can use this key for testing.\n";
        } else {
            echo "❌ Failed to generate test key.\n";
        }
    } else {
        echo "❌ BKGT_API_Auth class not found.\n";
    }
} else {
    echo "Found " . count($keys) . " active API key(s):\n";
    foreach ($keys as $key) {
        echo "- Key: {$key->api_key} (Created by user: {$key->created_by})\n";
    }
}
?>