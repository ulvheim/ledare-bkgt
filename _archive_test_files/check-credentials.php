<?php
define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "Scraping Credentials Check\n";
echo "=========================\n\n";

$username = get_option('bkgt_scraping_username');
$password = get_option('bkgt_scraping_password');

echo "Username: " . ($username ? $username : 'NOT SET') . "\n";
echo "Password: " . ($password ? 'SET' : 'NOT SET') . "\n";

if (!$username || !$password) {
    echo "\n❌ Credentials not configured. Setting as plain text for testing...\n";

    // Set credentials as plain text for now
    update_option('bkgt_scraping_username', 'martin.Olheim');
    update_option('bkgt_scraping_password', 'Zucci1Cantus2');
    echo "✓ Credentials set as plain text\n";
} else {
    echo "\n✓ Credentials are configured\n";
}
?>