# =========================================
# PRODUCTION DEPLOYMENT SCRIPT
# BKGT Document Management v1.0.0
# =========================================
# Deploy to: ledare.bkgt.se
# Date: November 4, 2025

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "BKGT Document Management - Production Deployment" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$sourceDir = "c:\Users\Olheim\Desktop\GH\ledare-bkgt\wp-content\plugins\bkgt-document-management"
$sftpServer = "ssh.loopia.se"
$sftpUser = "ulvheim"  # Change if needed
$remotePath = "/public_html/wp-content/plugins/bkgt-document-management/"

# Files to deploy
$filesToDeploy = @(
    @{
        "localPath" = "$sourceDir\bkgt-document-management.php"
        "remotePath" = "bkgt-document-management.php"
        "size" = "7,457 bytes"
        "description" = "Main plugin file"
    },
    @{
        "localPath" = "$sourceDir\frontend\class-frontend.php"
        "remotePath" = "frontend/class-frontend.php"
        "size" = "21,548 bytes"
        "description" = "Frontend class with document editing"
    },
    @{
        "localPath" = "$sourceDir\assets\js\frontend.js"
        "remotePath" = "assets/js/frontend.js"
        "size" = "34,097 bytes"
        "description" = "Dashboard JavaScript with edit functionality"
    }
)

Write-Host "📋 DEPLOYMENT CHECKLIST" -ForegroundColor Yellow
Write-Host "=====================================" -ForegroundColor Yellow
Write-Host ""

# Pre-deployment verification
Write-Host "1️⃣  Verifying local files..." -ForegroundColor Cyan
$allFilesExist = $true
foreach ($file in $filesToDeploy) {
    if (Test-Path $file.localPath) {
        $actualSize = (Get-Item $file.localPath).Length
        Write-Host "   ✅ $($file.remotePath) - $actualSize bytes" -ForegroundColor Green
    } else {
        Write-Host "   ❌ $($file.remotePath) - NOT FOUND!" -ForegroundColor Red
        $allFilesExist = $false
    }
}

if (-not $allFilesExist) {
    Write-Host ""
    Write-Host "❌ DEPLOYMENT ABORTED: Some files not found!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "2️⃣  Connection Details:" -ForegroundColor Cyan
Write-Host "   Server: $sftpServer" -ForegroundColor Yellow
Write-Host "   User: $sftpUser" -ForegroundColor Yellow
Write-Host "   Remote Path: $remotePath" -ForegroundColor Yellow
Write-Host ""

Write-Host "📦 DEPLOYMENT PACKAGE:" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
foreach ($file in $filesToDeploy) {
    Write-Host "   📄 $($file.remotePath)"
    Write-Host "      Description: $($file.description)"
    Write-Host "      Size: $($file.size)"
    Write-Host ""
}

Write-Host ""
Write-Host "⚠️  DEPLOYMENT INSTRUCTIONS:" -ForegroundColor Yellow
Write-Host "=====================================" -ForegroundColor Yellow
Write-Host ""
Write-Host "Option 1: Using SFTP (Recommended)" -ForegroundColor Cyan
Write-Host "--------"
Write-Host "1. Open terminal/SFTP client"
Write-Host "2. Connect: sftp $sftpUser@$sftpServer"
Write-Host "3. Navigate: cd $remotePath"
Write-Host "4. Create backup: cp bkgt-document-management.php bkgt-document-management.php.backup"
Write-Host ""
Write-Host "5. Upload files:"
foreach ($file in $filesToDeploy) {
    Write-Host "   put `"$($file.localPath)`" $($file.remotePath)"
}
Write-Host ""

Write-Host "Option 2: Create SFTP Batch File" -ForegroundColor Cyan
Write-Host "--------"
$batchFile = "c:\Users\Olheim\Desktop\GH\ledare-bkgt\deploy-sftp-batch.txt"
@"
open ssh.loopia.se
$sftpUser
cd $remotePath
ls
binary
put "$($filesToDeploy[0].localPath)" $($filesToDeploy[0].remotePath)
put "$($filesToDeploy[1].localPath)" $($filesToDeploy[1].remotePath)
put "$($filesToDeploy[2].localPath)" $($filesToDeploy[2].remotePath)
ls -la
bye
"@ | Out-File -Encoding UTF8 $batchFile
Write-Host "Created SFTP batch file: $batchFile"
Write-Host ""

Write-Host "Option 3: Command Line with PuTTY (if installed)" -ForegroundColor Cyan
Write-Host "--------"
Write-Host "pscp -l $sftpUser -r `"$sourceDir\*`" `"$($sftpUser)@$($sftpServer):$($remotePath)`""
Write-Host ""

Write-Host ""
Write-Host "🔍 POST-DEPLOYMENT VERIFICATION:" -ForegroundColor Yellow
Write-Host "=====================================" -ForegroundColor Yellow
Write-Host ""
Write-Host "After uploading files, run these checks:"
Write-Host ""
Write-Host "1. SSH into server:"
Write-Host "   ssh -l $sftpUser $sftpServer"
Write-Host ""
Write-Host "2. Verify files uploaded:"
Write-Host "   ls -la $remotePath"
Write-Host ""
Write-Host "3. Check PHP syntax:"
Write-Host "   php -l ${remotePath}bkgt-document-management.php"
Write-Host "   php -l ${remotePath}frontend/class-frontend.php"
Write-Host ""
Write-Host "4. Set permissions:"
Write-Host "   chmod 755 $remotePath"
Write-Host "   chmod 755 ${remotePath}frontend"
Write-Host "   chmod 755 ${remotePath}assets"
Write-Host "   chmod 755 ${remotePath}assets/js"
Write-Host "   chmod 644 ${remotePath}bkgt-document-management.php"
Write-Host "   chmod 644 ${remotePath}frontend/class-frontend.php"
Write-Host "   chmod 644 ${remotePath}assets/js/frontend.js"
Write-Host ""

Write-Host ""
Write-Host "🌐 WORDPRESS ACTIVATION:" -ForegroundColor Yellow
Write-Host "=====================================" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Log into: https://ledare.bkgt.se/wp-admin"
Write-Host "2. Navigate to: Plugins"
Write-Host "3. Find: BKGT Document Management"
Write-Host "4. Click: Deactivate"
Write-Host "5. Wait 10 seconds"
Write-Host "6. Click: Activate"
Write-Host "7. Check for errors"
Write-Host ""

Write-Host ""
Write-Host "✅ TESTING CHECKLIST:" -ForegroundColor Yellow
Write-Host "=====================================" -ForegroundColor Yellow
Write-Host ""
Write-Host "As a coach user, verify:"
Write-Host ""
Write-Host "1. ✅ Dashboard loads on [bkgt_documents] page"
Write-Host "2. ✅ 2 tabs visible: 'Mina dokument' and 'Mallar'"
Write-Host "3. ✅ Documents appear in 'Mina dokument'"
Write-Host "4. ✅ Templates appear in 'Mallar' tab"
Write-Host "5. ✅ Can create document from template"
Write-Host "6. ✅ Can edit document (NEW feature)"
Write-Host "7. ✅ Can delete document"
Write-Host "8. ✅ Can search documents"
Write-Host "9. ✅ No JavaScript errors (F12 console)"
Write-Host "10. ✅ No PHP errors in debug.log"
Write-Host ""

Write-Host ""
Write-Host "📋 FILE DETAILS:" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "bkgt-document-management.php (7,457 bytes)"
Write-Host "  - Main plugin file"
Write-Host "  - Delegates shortcode to frontend class"
Write-Host "  - Delegates AJAX to frontend methods"
Write-Host ""
Write-Host "frontend/class-frontend.php (21,548 bytes)"
Write-Host "  - User-facing dashboard class"
Write-Host "  - 7 AJAX handlers"
Write-Host "  - NEW: ajax_edit_user_document() method"
Write-Host "  - Team-based access control"
Write-Host ""
Write-Host "assets/js/frontend.js (34,097 bytes)"
Write-Host "  - Dashboard JavaScript"
Write-Host "  - Tab navigation"
Write-Host "  - Template loading"
Write-Host "  - Document creation modal"
Write-Host "  - NEW: Document editing with modal"
Write-Host "  - Document deletion"
Write-Host ""

Write-Host ""
Write-Host "🔐 SECURITY NOTES:" -ForegroundColor Yellow
Write-Host "=====================================" -ForegroundColor Yellow
Write-Host ""
Write-Host "✅ All AJAX calls require nonce verification"
Write-Host "✅ All AJAX calls require user to be logged in"
Write-Host "✅ Document editing restricted to:"
Write-Host "   - Authors (can always edit own documents)"
Write-Host "   - Coaches with edit_documents capability (team only)"
Write-Host "   - Team Managers with edit_documents capability (team only)"
Write-Host "✅ All user input is sanitized"
Write-Host "✅ All output is escaped"
Write-Host ""

Write-Host ""
Write-Host "💾 ROLLBACK PROCEDURE (if needed):" -ForegroundColor Yellow
Write-Host "=====================================" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. SSH into server: ssh -l $sftpUser $sftpServer"
Write-Host "2. Restore backup:"
Write-Host "   cp ${remotePath}bkgt-document-management.php.backup ${remotePath}bkgt-document-management.php"
Write-Host "3. Log into WordPress admin"
Write-Host "4. Deactivate and reactivate plugin"
Write-Host ""

Write-Host ""
Write-Host "🚀 DEPLOYMENT STATUS:" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green
Write-Host ""
Write-Host "✅ All files verified"
Write-Host "✅ Ready to deploy"
Write-Host "✅ Documentation complete"
Write-Host ""
Write-Host "Timestamp: $(Get-Date -Format ""yyyy-MM-dd HH:mm:ss"")" -ForegroundColor Cyan
Write-Host ""

Write-Host "📖 For detailed instructions, see:" -ForegroundColor Cyan
Write-Host "   - DEPLOYMENT_FILES.md"
Write-Host "   - DEPLOYMENT_PACKAGE_README.md"
Write-Host "   - DEPLOYMENT_CHECKLIST.md"
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Ready to proceed with deployment!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
