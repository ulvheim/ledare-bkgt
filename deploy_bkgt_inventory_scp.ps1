# BKGT Inventory Plugin Deployment Script - SCP Version
# Deploys the complete BKGT Inventory plugin to production using SCP

param(
    [string]$SshHost = "md0600@ssh.loopia.se",
    [string]$RemotePath = "~/ledare.bkgt.se/public_html/wp-content/plugins/bkgt-inventory/"
)

$localPath = "C:\Users\Olheim\Desktop\GH\ledare-bkgt\wp-content\plugins\bkgt-inventory"
$sshKey = "C:\Users\Olheim\.ssh\id_ecdsa_webhost"

Write-Host "BKGT Inventory Plugin - SCP Deploy Script" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Files to upload
$files = @(
    @{ local = "$localPath\bkgt-inventory.php"; remote = "bkgt-inventory.php" },
    @{ local = "$localPath\includes\class-analytics.php"; remote = "includes/class-analytics.php" },
    @{ local = "$localPath\includes\class-api-endpoints.php"; remote = "includes/class-api-endpoints.php" },
    @{ local = "$localPath\includes\class-assignment.php"; remote = "includes/class-assignment.php" },
    @{ local = "$localPath\includes\class-database.php"; remote = "includes/class-database.php" },
    @{ local = "$localPath\includes\class-history.php"; remote = "includes/class-history.php" },
    @{ local = "$localPath\includes\class-inventory-item.php"; remote = "includes/class-inventory-item.php" },
    @{ local = "$localPath\includes\class-item-type.php"; remote = "includes/class-item-type.php" },
    @{ local = "$localPath\includes\class-location.php"; remote = "includes/class-location.php" },
    @{ local = "$localPath\includes\class-manufacturer.php"; remote = "includes/class-manufacturer.php" },
    @{ local = "$localPath\admin\class-admin.php"; remote = "admin/class-admin.php" },
    @{ local = "$localPath\admin\class-item-admin.php"; remote = "admin/class-item-admin.php" },
    @{ local = "$localPath\assets\admin.css"; remote = "assets/admin.css" },
    @{ local = "$localPath\assets\admin.js"; remote = "assets/admin.js" },
    @{ local = "$localPath\assets\frontend.css"; remote = "assets/frontend.css" },
    @{ local = "$localPath\templates\archive-bkgt_inventory_item.php"; remote = "templates/archive-bkgt_inventory_item.php" },
    @{ local = "$localPath\templates\locations-page.php"; remote = "templates/locations-page.php" },
    @{ local = "$localPath\templates\single-bkgt_inventory_item.php"; remote = "templates/single-bkgt_inventory_item.php" }
)

# Verify files exist
Write-Host "Verifying local files..." -ForegroundColor Yellow
$totalSize = 0
foreach ($file in $files) {
    if (Test-Path $file.local) {
        $size = (Get-Item $file.local).Length
        $totalSize += $size
        Write-Host "  OK: $($file.remote) - $size bytes" -ForegroundColor Green
    } else {
        Write-Host "  ERROR: $($file.local) not found!" -ForegroundColor Red
        exit 1
    }
}

Write-Host ""
Write-Host "Summary:" -ForegroundColor Yellow
Write-Host "  Total files: $($files.Count)"
Write-Host "  Total size: $([math]::Round($totalSize / 1024, 1)) KB"
Write-Host ""

Write-Host "Connection Details:" -ForegroundColor Yellow
Write-Host "  Host: $SshHost"
Write-Host "  Key: $sshKey"
Write-Host "  Remote Path: $RemotePath"
Write-Host ""

# Confirm deployment
#$confirmation = Read-Host "Do you want to proceed with deployment? (y/N)"
#if ($confirmation -ne 'y' -and $confirmation -ne 'Y') {
#    Write-Host "Deployment cancelled." -ForegroundColor Yellow
#    exit 0
#}

Write-Host "Starting SCP upload..." -ForegroundColor Yellow

# Create remote directory structure first
Write-Host "Creating remote directories..." -ForegroundColor Yellow
ssh -i $sshKey -o StrictHostKeyChecking=no $SshHost "mkdir -p $RemotePath/includes $RemotePath/admin/css $RemotePath/admin/js"

$successCount = 0
$failCount = 0

foreach ($file in $files) {
    Write-Host "Uploading $($file.remote)..." -NoNewline

    $scpCommand = "scp -i `"$sshKey`" -o StrictHostKeyChecking=no `"$($file.local)`" ${SshHost}:${RemotePath}$($file.remote)"
    $result = Invoke-Expression $scpCommand

    if ($LASTEXITCODE -eq 0) {
        Write-Host " ✅" -ForegroundColor Green
        $successCount++
    } else {
        Write-Host " ❌" -ForegroundColor Red
        $failCount++
    }
}

Write-Host ""
if ($failCount -eq 0) {
    Write-Host "✅ DEPLOYMENT SUCCESSFUL!" -ForegroundColor Green
    Write-Host "All $successCount files uploaded successfully." -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "1. Go to WordPress Admin → Plugins"
    Write-Host "2. Activate the 'BKGT API' plugin"
    Write-Host "3. Configure settings in BKGT API → Settings"
    Write-Host "4. Generate API keys in BKGT API → API Keys"
    Write-Host "5. Test endpoints using the admin interface"
    Write-Host ""
    Write-Host "Plugin URL: https://ledare.bkgt.se/wp-admin/plugins.php" -ForegroundColor Cyan
} else {
    Write-Host "❌ DEPLOYMENT PARTIALLY FAILED!" -ForegroundColor Red
    Write-Host "Successfully uploaded: $successCount files" -ForegroundColor Yellow
    Write-Host "Failed to upload: $failCount files" -ForegroundColor Red
}