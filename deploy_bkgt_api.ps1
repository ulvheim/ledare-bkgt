# BKGT API Plugin Deployment Script
# Deploys the complete BKGT API plugin to production

param(
    [string]$SftpServer = "ssh.loopia.se",
    [string]$SftpUser = "md0600",
    [string]$RemotePath = "/public_html/wp-content/plugins/bkgt-api/"
)

$localPath = "C:\Users\Olheim\Desktop\GH\ledare-bkgt\wp-content\plugins\bkgt-api"

Write-Host "BKGT API Plugin - Deploy Script" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan
Write-Host ""

# Files to upload (main plugin file)
$files = @(
    @{ local = "$localPath\bkgt-api.php"; remote = "bkgt-api.php" }
)

# Include files
$includeFiles = @(
    @{ local = "$localPath\includes\class-bkgt-api.php"; remote = "includes/class-bkgt-api.php" },
    @{ local = "$localPath\includes\class-bkgt-auth.php"; remote = "includes/class-bkgt-auth.php" },
    @{ local = "$localPath\includes\class-bkgt-endpoints.php"; remote = "includes/class-bkgt-endpoints.php" },
    @{ local = "$localPath\includes\class-bkgt-security.php"; remote = "includes/class-bkgt-security.php" },
    @{ local = "$localPath\includes\class-bkgt-notifications.php"; remote = "includes/class-bkgt-notifications.php" }
)

# Admin files
$adminFiles = @(
    @{ local = "$localPath\admin\class-bkgt-api-admin.php"; remote = "admin/class-bkgt-api-admin.php" },
    @{ local = "$localPath\admin\css\admin.css"; remote = "admin/css/admin.css" },
    @{ local = "$localPath\admin\js\admin.js"; remote = "admin/js/admin.js" }
)

# Documentation
$docFiles = @(
    @{ local = "$localPath\README.md"; remote = "README.md" }
)

# Combine all files
$allFiles = $files + $includeFiles + $adminFiles + $docFiles

# Verify files exist
Write-Host "Verifying local files..." -ForegroundColor Yellow
$totalSize = 0
foreach ($file in $allFiles) {
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
Write-Host "  Total files: $($allFiles.Count)"
Write-Host "  Total size: $([math]::Round($totalSize / 1024, 1)) KB"
Write-Host ""

Write-Host "Connection Details:" -ForegroundColor Yellow
Write-Host "  Server: $SftpServer"
Write-Host "  User: $SftpUser"
Write-Host "  Path: $RemotePath"
Write-Host ""

# Confirm deployment
$confirmation = Read-Host "Do you want to proceed with deployment? (y/N)"
if ($confirmation -ne 'y' -and $confirmation -ne 'Y') {
    Write-Host "Deployment cancelled." -ForegroundColor Yellow
    exit 0
}

# Create SFTP batch script
$batchFile = "$env:TEMP\sftp_batch_$([System.DateTime]::Now.Ticks).txt"
$sftpCommands = @"
open $SftpServer
$SftpUser
cd $RemotePath
ls -la
binary
"@

foreach ($file in $allFiles) {
    $sftpCommands += "`nput `"$($file.local)`" $($file.remote)"
}

$sftpCommands += @"

ls -la
bye
"@

$sftpCommands | Out-File -FilePath $batchFile -Encoding ASCII

Write-Host "Starting SFTP upload..." -ForegroundColor Yellow

# Execute SFTP
$sftpProcess = Start-Process -FilePath "sftp" -ArgumentList "-b $batchFile" -NoNewWindow -Wait -PassThru

if ($sftpProcess.ExitCode -eq 0) {
    Write-Host ""
    Write-Host "✅ DEPLOYMENT SUCCESSFUL!" -ForegroundColor Green
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
    Write-Host ""
    Write-Host "❌ DEPLOYMENT FAILED!" -ForegroundColor Red
    Write-Host "Exit code: $($sftpProcess.ExitCode)"
    Write-Host ""
    Write-Host "Check the SFTP batch file for details: $batchFile" -ForegroundColor Yellow
}

# Clean up
Remove-Item $batchFile -ErrorAction SilentlyContinue