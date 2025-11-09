# Quick Deploy Script for BKGT Document Management
# This script uploads the 3 files to production via SFTP

param(
    [string]$SftpServer = "ssh.loopia.se",
    [string]$SftpUser = "ulvheim",
    [string]$RemotePath = "/public_html/wp-content/plugins/bkgt-document-management/"
)

$localPath = "C:\Users\Olheim\Desktop\GH\ledare-bkgt\wp-content\plugins\bkgt-document-management"

Write-Host "BKGT Document Management - Deploy Script" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Files to upload
$files = @(
    @{ local = "$localPath\bkgt-document-management.php"; remote = "bkgt-document-management.php" },
    @{ local = "$localPath\frontend\class-frontend.php"; remote = "frontend/class-frontend.php" },
    @{ local = "$localPath\assets\js\frontend.js"; remote = "assets/js/frontend.js" },
    @{ local = "$localPath\admin\class-admin.php"; remote = "admin/class-admin.php" },
    @{ local = "$localPath\admin\class-export-engine.php"; remote = "admin/class-export-engine.php" },
    @{ local = "$localPath\admin\class-smart-templates.php"; remote = "admin/class-smart-templates.php" }
)

# Verify files exist
Write-Host "Verifying local files..." -ForegroundColor Yellow
foreach ($file in $files) {
    if (Test-Path $file.local) {
        $size = (Get-Item $file.local).Length
        Write-Host "  OK: $($file.remote) - $size bytes" -ForegroundColor Green
    } else {
        Write-Host "  ERROR: $($file.local) not found!" -ForegroundColor Red
        exit 1
    }
}

Write-Host ""
Write-Host "Connection Details:" -ForegroundColor Yellow
Write-Host "  Server: $SftpServer"
Write-Host "  User: $SftpUser"
Write-Host "  Path: $RemotePath"
Write-Host ""

# Create SFTP batch script
$batchFile = "$env:TEMP\sftp_batch_$([System.DateTime]::Now.Ticks).txt"
$sftpCommands = @"
open $SftpServer
$SftpUser
cd $RemotePath
ls -la
binary
put "$($files[0].local)" $($files[0].remote)
put "$($files[1].local)" $($files[1].remote)
put "$($files[2].local)" $($files[2].remote)
put "$($files[3].local)" $($files[3].remote)
put "$($files[4].local)" $($files[4].remote)
put "$($files[5].local)" $($files[5].remote)
ls -la
bye
"@

$sftpCommands | Out-File -FilePath $batchFile -Encoding ASCII

Write-Host "SFTP Batch file created: $batchFile" -ForegroundColor Green
Write-Host ""
Write-Host "To upload files, use one of these methods:" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. FileZilla GUI (Easiest):" -ForegroundColor Yellow
Write-Host "   - Open FileZilla"
Write-Host "   - Host: sftp://ssh.loopia.se"
Write-Host "   - Username: $SftpUser"
Write-Host "   - Navigate to: $RemotePath"
Write-Host "   - Drag & drop 6 files"
Write-Host ""
Write-Host "2. WinSCP:" -ForegroundColor Yellow
Write-Host "   - Protocol: SFTP"
Write-Host "   - Host: $SftpServer"
Write-Host "   - User: $SftpUser"
Write-Host "   - Drag & drop files"
Write-Host ""
Write-Host "3. PowerShell with WinSCP (if installed):" -ForegroundColor Yellow
Write-Host "   See: https://winscp.net/eng/docs/library_powershell"
Write-Host ""
Write-Host "SFTP Batch script saved to:" -ForegroundColor Green
Write-Host $batchFile
Write-Host ""
Write-Host "After uploading, run these SSH commands:" -ForegroundColor Cyan
Write-Host ""
Write-Host "ssh $SftpUser@$SftpServer"
Write-Host "cd $RemotePath"
Write-Host "ls -la"
Write-Host "php -l bkgt-document-management.php"
Write-Host "php -l frontend/class-frontend.php"
Write-Host "php -l admin/class-admin.php"
Write-Host "php -l admin/class-export-engine.php"
Write-Host "php -l admin/class-smart-templates.php"
Write-Host "chmod 755 ."
Write-Host "chmod 644 *.php frontend/*.php admin/*.php assets/js/*.js"
Write-Host "exit"
Write-Host ""
Write-Host "Then activate plugin in WordPress: https://ledare.bkgt.se/wp-admin/"
Write-Host ""
