# BKGT API Plugin Activation Verification Script
# Checks if the plugin is properly installed and activated

param(
    [string]$WordPressUrl = "https://ledare.bkgt.se",
    [string]$SshHost = "md0600@ssh.loopia.se",
    [string]$SshKey = "C:\Users\Olheim\.ssh\id_ecdsa_webhost"
)

Write-Host "BKGT API Plugin - Activation Verification" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Check if plugin files exist
Write-Host "Checking plugin files on server..." -ForegroundColor Yellow
$checkFiles = ssh -i $SshKey -o StrictHostKeyChecking=no $SshHost "test -f ~/ledare.bkgt.se/public_html/wp-content/plugins/bkgt-api/bkgt-api.php && echo 'Main plugin file exists' || echo 'Main plugin file missing'"

if ($checkFiles -match "Main plugin file exists") {
    Write-Host "✅ Plugin files are present on server" -ForegroundColor Green
} else {
    Write-Host "❌ Plugin files are missing from server" -ForegroundColor Red
    exit 1
}

# Check if plugin is in active plugins list (requires WordPress access)
Write-Host ""
Write-Host "Plugin Activation Instructions:" -ForegroundColor Cyan
Write-Host "==============================" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Open your web browser and go to:" -ForegroundColor White
Write-Host "   $WordPressUrl/wp-admin/plugins.php" -ForegroundColor Yellow
Write-Host ""
Write-Host "2. Log in with your WordPress admin credentials" -ForegroundColor White
Write-Host ""
Write-Host "3. Look for 'BKGT API' in the plugins list" -ForegroundColor White
Write-Host ""
Write-Host "4. Click 'Activate' next to the BKGT API plugin" -ForegroundColor White
Write-Host ""
Write-Host "5. After activation, you should see 'BKGT API' in the admin menu" -ForegroundColor White
Write-Host ""
Write-Host "6. Go to BKGT API → Settings to configure:" -ForegroundColor White
Write-Host "   - JWT Secret Key (generate a secure random string)" -ForegroundColor Yellow
Write-Host "   - CORS Origins (add your app domains)" -ForegroundColor Yellow
Write-Host "   - Rate Limiting settings" -ForegroundColor Yellow
Write-Host ""
Write-Host "7. Go to BKGT API → API Keys to generate keys for your apps" -ForegroundColor White
Write-Host ""
Write-Host "8. Test the API endpoints using the admin interface" -ForegroundColor White
Write-Host ""
Write-Host "Manual Verification Commands:" -ForegroundColor Cyan
Write-Host "============================" -ForegroundColor Cyan
Write-Host ""
Write-Host "After activation, you can test the API with:" -ForegroundColor White
Write-Host "curl -X GET '$WordPressUrl/wp-json/bkgt/v1/status' \\" -ForegroundColor Yellow
Write-Host "     -H 'Content-Type: application/json'" -ForegroundColor Yellow
Write-Host ""
Write-Host "Expected response: {'status':'active','version':'1.0.0'}" -ForegroundColor Green
Write-Host ""
Write-Host "Plugin URL: $WordPressUrl/wp-admin/plugins.php" -ForegroundColor Cyan
Write-Host "API Base URL: $WordPressUrl/wp-json/bkgt/v1/" -ForegroundColor Cyan