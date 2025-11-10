# BKGT SWE3 Scraper Plugin Deployment Script - SCP Version
# Deploys the complete BKGT SWE3 Scraper plugin to production using SCP

param(
    [string]$SshHost = "md0600@ssh.loopia.se",
    [string]$RemotePath = "~/ledare.bkgt.se/public_html/wp-content/plugins/bkgt-swe3-scraper/"
)

$localPath = "C:\Users\Olheim\Desktop\GH\ledare-bkgt\wp-content\plugins\bkgt-swe3-scraper"
$sshKey = "C:\Users\Olheim\.ssh\id_ecdsa_webhost"

Write-Host "BKGT SWE3 Scraper Plugin - SCP Deploy Script" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

# Files to upload
$files = @(
    @{ local = "$localPath\bkgt-swe3-scraper.php"; remote = "bkgt-swe3-scraper.php" },
    @{ local = "$localPath\includes\class-bkgt-swe3-scraper.php"; remote = "includes/class-bkgt-swe3-scraper.php" },
    @{ local = "$localPath\includes\class-bkgt-swe3-scheduler.php"; remote = "includes/class-bkgt-swe3-scheduler.php" },
    @{ local = "$localPath\includes\class-bkgt-swe3-dms-integration.php"; remote = "includes/class-bkgt-swe3-dms-integration.php" },
    @{ local = "$localPath\admin\class-bkgt-swe3-admin.php"; remote = "admin/class-bkgt-swe3-admin.php" },
    @{ local = "$localPath\manual-scrape.php"; remote = "manual-scrape.php" },
    @{ local = "$localPath\test-swe3-scraper.php"; remote = "test-swe3-scraper.php" },
    @{ local = "$localPath\README.md"; remote = "README.md" }
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
Write-Host ("  Total size: {0:N1} KB" -f ($totalSize / 1024))
Write-Host ""

Write-Host "Connection Details:" -ForegroundColor Yellow
Write-Host "  Host: $SshHost"
Write-Host "  Key: $sshKey"
Write-Host "  Remote Path: $RemotePath"
Write-Host ""

$confirmation = Read-Host "Do you want to proceed with deployment? (y/N)"
if ($confirmation -ne 'y') {
    Write-Host "Deployment cancelled." -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "Starting SCP upload..." -ForegroundColor Green

# Create remote directories first
Write-Host "Creating remote directories..." -ForegroundColor Yellow
$remoteDirs = @(
    "~/ledare.bkgt.se/public_html/wp-content/plugins/bkgt-swe3-scraper/",
    "~/ledare.bkgt.se/public_html/wp-content/plugins/bkgt-swe3-scraper/includes/",
    "~/ledare.bkgt.se/public_html/wp-content/plugins/bkgt-swe3-scraper/admin/"
)

foreach ($dir in $remoteDirs) {
    $sshCmd = "ssh -i '$sshKey' $SshHost 'mkdir -p $dir'"
    Invoke-Expression $sshCmd 2>$null
}

# Upload files
$successCount = 0
foreach ($file in $files) {
    Write-Host "Uploading $($file.remote)..." -NoNewline
    $scpCmd = "scp -i '$sshKey' '$($file.local)' ${SshHost}:$RemotePath$($file.remote)"
    $result = Invoke-Expression $scpCmd 2>$null

    if ($LASTEXITCODE -eq 0) {
        Write-Host " ✓" -ForegroundColor Green
        $successCount++
    } else {
        Write-Host " ✗" -ForegroundColor Red
    }
}

Write-Host ""
if ($successCount -eq $files.Count) {
    Write-Host "DEPLOYMENT SUCCESSFUL!" -ForegroundColor Green
    Write-Host "All $successCount files uploaded successfully."
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "1. Go to WordPress Admin → Plugins"
    Write-Host "2. Activate the 'BKGT SWE3 Scraper' plugin"
    Write-Host "3. Configure settings in SWE3 Scraper → Settings"
    Write-Host "4. Test scraping functionality"
    Write-Host ""
    Write-Host "Plugin URL: https://ledare.bkgt.se/wp-admin/plugins.php" -ForegroundColor Cyan
} else {
    Write-Host "DEPLOYMENT FAILED!" -ForegroundColor Red
    Write-Host "Only $successCount of $($files.Count) files uploaded successfully." -ForegroundColor Red
    exit 1
}
}