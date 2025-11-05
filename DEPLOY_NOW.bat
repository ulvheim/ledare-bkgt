@echo off
REM =========================================
REM DEPLOYMENT INSTRUCTIONS
REM BKGT Document Management v1.0.0
REM =========================================
REM Target: ledare.bkgt.se
REM Date: November 4, 2025

echo.
echo ==========================================
echo BKGT Document Management - Production Deploy
echo ==========================================
echo.
echo FILES TO UPLOAD (3 files):
echo.
echo 1. bkgt-document-management.php (7.4 KB)
echo    Location: c:\Users\Olheim\Desktop\GH\ledare-bkgt\wp-content\plugins\bkgt-document-management\
echo    Upload to: /public_html/wp-content/plugins/bkgt-document-management/
echo.
echo 2. frontend/class-frontend.php (21.5 KB)
echo    Location: c:\Users\Olheim\Desktop\GH\ledare-bkgt\wp-content\plugins\bkgt-document-management\frontend\
echo    Upload to: /public_html/wp-content/plugins/bkgt-document-management/frontend/
echo.
echo 3. assets/js/frontend.js (34 KB)
echo    Location: c:\Users\Olheim\Desktop\GH\ledare-bkgt\wp-content\plugins\bkgt-document-management\assets\js\
echo    Upload to: /public_html/wp-content/plugins/bkgt-document-management/assets/js/
echo.
echo ==========================================
echo DEPLOYMENT METHOD OPTIONS
echo ==========================================
echo.
echo OPTION 1: Using FileZilla or other SFTP Client
echo   - Server: ssh.loopia.se
echo   - User: ulvheim
echo   - Port: 22 (or your configured SSH port)
echo   - Protocol: SFTP
echo   - Navigate to: /public_html/wp-content/plugins/bkgt-document-management/
echo   - Upload the 3 files above
echo.
echo OPTION 2: Using Command Line SFTP
echo   Run in terminal:
echo   sftp ulvheim@ssh.loopia.se
echo   cd /public_html/wp-content/plugins/bkgt-document-management/
echo   put "c:\Users\Olheim\Desktop\GH\ledare-bkgt\wp-content\plugins\bkgt-document-management\bkgt-document-management.php" 
echo   put "c:\Users\Olheim\Desktop\GH\ledare-bkgt\wp-content\plugins\bkgt-document-management\frontend\class-frontend.php" frontend/class-frontend.php
echo   put "c:\Users\Olheim\Desktop\GH\ledare-bkgt\wp-content\plugins\bkgt-document-management\assets\js\frontend.js" assets/js/frontend.js
echo.
echo ==========================================
echo POST-UPLOAD VERIFICATION
echo ==========================================
echo.
echo After uploading files, SSH into the server:
echo   ssh ulvheim@ssh.loopia.se
echo.
echo Then run these commands:
echo.
echo 1. Verify files:
echo    ls -la /public_html/wp-content/plugins/bkgt-document-management/
echo.
echo 2. Check PHP syntax:
echo    php -l /public_html/wp-content/plugins/bkgt-document-management/bkgt-document-management.php
echo    php -l /public_html/wp-content/plugins/bkgt-document-management/frontend/class-frontend.php
echo.
echo 3. Fix permissions if needed:
echo    chmod 755 /public_html/wp-content/plugins/bkgt-document-management/
echo    chmod 755 /public_html/wp-content/plugins/bkgt-document-management/frontend
echo    chmod 755 /public_html/wp-content/plugins/bkgt-document-management/assets
echo    chmod 644 /public_html/wp-content/plugins/bkgt-document-management/*.php
echo    chmod 644 /public_html/wp-content/plugins/bkgt-document-management/assets/js/*.js
echo.
echo ==========================================
echo WORDPRESS ACTIVATION
echo ==========================================
echo.
echo 1. Visit: https://ledare.bkgt.se/wp-admin/
echo 2. Go to: Plugins menu
echo 3. Find: "BKGT Document Management"
echo 4. Click: Deactivate (if active)
echo 5. Wait 10 seconds
echo 6. Click: Activate
echo 7. Check for errors on the plugins page
echo.
echo ==========================================
echo TESTING CHECKLIST
echo ==========================================
echo.
echo Log in as a coach user, then:
echo.
echo [ ] Visit page with [bkgt_documents] shortcode
echo [ ] Dashboard loads with 2 tabs: "Mina dokument" and "Mallar"
echo [ ] Documents appear in "Mina dokument" tab
echo [ ] Templates appear in "Mallar" tab
echo [ ] Can create document from template
echo [ ] Can EDIT document (new feature!)
echo [ ] Can delete document
echo [ ] Can search documents
echo [ ] No errors in browser console (F12)
echo [ ] No PHP errors in /wp-content/debug.log
echo.
echo ==========================================
echo FEATURES DEPLOYED
echo ==========================================
echo.
echo Document Management v1.0.0 includes:
echo.
echo - User dashboard with 2 tabs (My Documents / Templates)
echo - Create documents from templates
echo - EDIT documents (new - coaches can edit team docs)
echo - Delete documents
echo - Download documents
echo - Search/filter documents
echo - Team-based access control
echo - Swedish localization
echo.
echo ==========================================
echo ROLLBACK (if needed)
echo ==========================================
echo.
echo SSH to server:
echo   ssh ulvheim@ssh.loopia.se
echo.
echo If a backup was made:
echo   cp /public_html/wp-content/plugins/bkgt-document-management/bkgt-document-management.php.backup ^
echo      /public_html/wp-content/plugins/bkgt-document-management/bkgt-document-management.php
echo.
echo Then deactivate and reactivate the plugin in WordPress admin.
echo.
echo ==========================================
echo DOCUMENTATION
echo ==========================================
echo.
echo For more details, see:
echo - DEPLOYMENT_FILES.md
echo - DEPLOYMENT_PACKAGE_README.md  
echo - DEPLOYMENT_CHECKLIST.md
echo - COACH_DOCUMENT_EDITING.md
echo - SYSTEM_ARCHITECTURE.md
echo.
echo ==========================================
echo READY TO DEPLOY!
echo ==========================================
echo.
pause
