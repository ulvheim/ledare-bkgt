# Test BKGT API Key Creation Fix
# Tests the AJAX endpoint directly

param(
    [string]$WordPressUrl = "https://ledare.bkgt.se",
    [string]$SshHost = "md0600@ssh.loopia.se",
    [string]$SshKey = "C:\Users\Olheim\.ssh\id_ecdsa_webhost"
)

Write-Host "Testing BKGT API Key Creation Fix" -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
Write-Host ""

# Test the AJAX endpoint directly
$testAjaxScript = @"
<?php
require_once('wp-load.php');

// Simulate AJAX context
if (!defined('DOING_AJAX')) {
    define('DOING_AJAX', true);
}
if (!defined('WP_ADMIN')) {
    define('WP_ADMIN', true);
}

// Set current user to admin
wp_set_current_user(1);

// Initialize the admin class (this should register the AJAX hooks)
if (class_exists('BKGT_API_Admin')) {
    `$admin = new BKGT_API_Admin();
    echo "Admin class initialized\n";
} else {
    echo "Admin class not found\n";
    exit;
}

// Create nonce
`$nonce = wp_create_nonce('bkgt_api_admin_nonce');

echo "Testing AJAX endpoint...\n";
echo "Nonce: `$nonce\n";

// Simulate AJAX request
`$post_data = array(
    'action' => 'bkgt_api_create_key',
    'key_name' => 'test_api_key_' . time(),
    'key_permissions' => array('read', 'write'),
    'nonce' => `$nonce
);

// Set up POST data
`$`REQUEST = `$post_data;
`$`POST = `$post_data;

// Start output buffering
ob_start();

// Call the AJAX handler directly
try {
    // Manually set up the request
    `$`POST['action'] = 'bkgt_api_create_key';
    `$`POST['key_name'] = 'test_api_key_' . time();
    `$`POST['key_permissions'] = array('read', 'write');
    `$`POST['nonce'] = `$nonce;
    
    `$`REQUEST = `$`POST;
    
    echo "Calling ajax_create_api_key...\n";
    
    // Test the auth class directly
    `$auth = new BKGT_API_Auth();
    `$api_key = `$auth->create_api_key(1, 'test_key_direct', array('read', 'write'));
    echo "Direct API key creation result: " . (`$api_key ? 'SUCCESS: ' . `$api_key : 'FAILED') . "\n";
    
    // Now try the AJAX method
    `$admin->ajax_create_api_key();
    `$response = ob_get_clean();
    echo "Raw response: '" . `$response . "'\n";
} catch (Exception `$e) {
    ob_end_clean();
    echo "Exception: " . `$e->getMessage() . "\n";
}

echo "Test completed.\n";
?>
"@

$tempScript = [System.IO.Path]::GetTempFileName() + ".php"
$testAjaxScript | Out-File -FilePath $tempScript -Encoding UTF8

Write-Host "Uploading test script..." -ForegroundColor Yellow
scp -i $SshKey -o StrictHostKeyChecking=no $tempScript ${SshHost}:~/ledare.bkgt.se/public_html/test_api_fix.php

Write-Host "Running test..." -ForegroundColor Yellow
ssh -i $SshKey -o StrictHostKeyChecking=no $SshHost "cd ~/ledare.bkgt.se/public_html && php test_api_fix.php"

# Clean up
ssh -i $SshKey -o StrictHostKeyChecking=no $SshHost "rm ~/ledare.bkgt.se/public_html/test_api_fix.php"
Remove-Item $tempScript

Write-Host ""
Write-Host "If the test shows a successful response, the fix should work." -ForegroundColor Green
Write-Host "Try creating an API key again in the WordPress admin." -ForegroundColor Green