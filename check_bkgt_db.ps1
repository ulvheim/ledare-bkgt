# BKGT API Database Check Script
# Checks if the required database tables exist

param(
    [string]$WordPressUrl = "https://ledare.bkgt.se",
    [string]$SshHost = "md0600@ssh.loopia.se",
    [string]$SshKey = "C:\Users\Olheim\.ssh\id_ecdsa_webhost"
)

Write-Host "BKGT API Database Check" -ForegroundColor Cyan
Write-Host "=======================" -ForegroundColor Cyan
Write-Host ""

# Check if tables exist
Write-Host "Checking database tables..." -ForegroundColor Yellow

$checkTablesScript = @"
<?php
require_once('wp-load.php');
global `$wpdb;

`$tables = array(
    'bkgt_api_keys',
    'bkgt_api_logs'
);

foreach (`$tables as `$table) {
    `$table_name = `$wpdb->prefix . `$table;
    `$exists = `$wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (`$exists) {
        echo "✅ `$table_name exists\n";
    } else {
        echo "❌ `$table_name missing\n";
    }
}
?>
"@

# Write the PHP script to a temp file
$tempScript = [System.IO.Path]::GetTempFileName() + ".php"
$checkTablesScript | Out-File -FilePath $tempScript -Encoding UTF8

# Upload and execute the script
Write-Host "Uploading check script..." -ForegroundColor Yellow
scp -i $SshKey -o StrictHostKeyChecking=no $tempScript ${SshHost}:~/ledare.bkgt.se/public_html/check_tables.php

Write-Host "Executing check script..." -ForegroundColor Yellow
ssh -i $SshKey -o StrictHostKeyChecking=no $SshHost "cd ~/ledare.bkgt.se/public_html && php check_tables.php"

# Clean up
ssh -i $SshKey -o StrictHostKeyChecking=no $SshHost "rm ~/ledare.bkgt.se/public_html/check_tables.php"
Remove-Item $tempScript

Write-Host ""
Write-Host "Test API Key Creation..." -ForegroundColor Cyan
Write-Host "========================" -ForegroundColor Cyan

# Test the AJAX endpoint
$testAjaxScript = @"
<?php
require_once('wp-load.php');

// Simulate admin user
wp_set_current_user(1);

`$data = array(
    'action' => 'bkgt_api_create_key',
    'key_name' => 'test_key',
    'key_permissions' => array('read', 'write'),
    'nonce' => wp_create_nonce('bkgt_api_admin_nonce')
);

`$result = wp_ajax_bkgt_api_create_key();
echo "AJAX Response: " . json_encode(`$result) . "\n";
?>
"@

$tempAjaxScript = [System.IO.Path]::GetTempFileName() + ".php"
$testAjaxScript | Out-File -FilePath $tempAjaxScript -Encoding UTF8

Write-Host "Uploading AJAX test script..." -ForegroundColor Yellow
scp -i $SshKey -o StrictHostKeyChecking=no $tempAjaxScript ${SshHost}:~/ledare.bkgt.se/public_html/test_ajax.php

Write-Host "Executing AJAX test..." -ForegroundColor Yellow
ssh -i $SshKey -o StrictHostKeyChecking=no $SshHost "cd ~/ledare.bkgt.se/public_html && php test_ajax.php"

# Clean up
ssh -i $SshKey -o StrictHostKeyChecking=no $SshHost "rm ~/ledare.bkgt.se/public_html/test_ajax.php"
Remove-Item $tempAjaxScript

Write-Host ""
Write-Host "If tables are missing, try reactivating the plugin." -ForegroundColor Yellow
Write-Host "If AJAX fails, check PHP error logs." -ForegroundColor Yellow