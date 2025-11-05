<?php
define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "Credential Decryption Test\n";
echo "=========================\n\n";

// Get encrypted credentials
$encrypted_username = get_option('bkgt_scraping_username');
$encrypted_password = get_option('bkgt_scraping_password');

echo "Encrypted username: " . substr($encrypted_username, 0, 20) . "...\n";
echo "Encrypted password: " . substr($encrypted_password, 0, 20) . "...\n\n";

// Try to decrypt
if (class_exists('BKGT_Database') && class_exists('BKGT_Admin')) {
    $db = new BKGT_Database();
    $admin = new BKGT_Admin($db);

    $username = $admin->decrypt_credential($encrypted_username);
    $password = $admin->decrypt_credential($encrypted_password);

    echo "Decrypted username: " . ($username ? $username : 'EMPTY') . "\n";
    echo "Decrypted password: " . ($password ? 'SET' : 'EMPTY') . "\n";

    if (empty($username) || empty($password)) {
        echo "\n❌ Decryption failed - credentials appear empty\n";
    } else {
        echo "\n✓ Decryption successful\n";
    }
} else {
    echo "❌ Could not initialize admin class\n";
}
?>