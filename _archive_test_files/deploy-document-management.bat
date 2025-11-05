@echo off
REM =============================================
REM BKGT Document Management Deployment Script
REM Deploys to ledare.bkgt.se on Loopia hosting
REM =============================================

setlocal enabledelayedexpansion

echo.
echo === BKGT Document Management v1.0.0 Deployment ===
echo.

REM Load environment variables from .env
if not exist ".env" (
    echo ERROR: .env file not found
    exit /b 1
)

for /f "usebackq tokens=1,* delims==" %%A in (".env") do (
    set "%%A=%%B"
)

echo Target: %SSH_HOST% (%SSH_USER%)
echo Remote: %REMOTE_FOLDER%/wp-content/plugins/bkgt-document-management/
echo.

REM Test SSH connection
echo Testing SSH connection...
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=10 %SSH_USER%@%SSH_HOST% "echo OK" >nul 2>&1
if errorlevel 1 (
    echo ERROR: SSH connection failed. Verify credentials in .env
    exit /b 1
)
echo [OK] SSH connection successful
echo.

REM Create remote plugin directory
echo Creating remote directories...
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "mkdir -p %REMOTE_FOLDER%/wp-content/plugins/bkgt-document-management/frontend"
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "mkdir -p %REMOTE_FOLDER%/wp-content/plugins/bkgt-document-management/assets/js"
echo [OK] Directories created
echo.

REM Deploy main plugin file
echo Uploading bkgt-document-management.php...
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 "..\wp-content\plugins\bkgt-document-management\bkgt-document-management.php" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/wp-content/plugins/bkgt-document-management/bkgt-document-management.php"
if errorlevel 1 (
    echo ERROR: Failed to upload main plugin file
    exit /b 1
)
echo [OK] Main plugin file uploaded
echo.

REM Deploy frontend class
echo Uploading frontend/class-frontend.php...
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 "..\wp-content\plugins\bkgt-document-management\frontend\class-frontend.php" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/wp-content/plugins/bkgt-document-management/frontend/class-frontend.php"
if errorlevel 1 (
    echo ERROR: Failed to upload frontend class
    exit /b 1
)
echo [OK] Frontend class uploaded
echo.

REM Deploy JavaScript
echo Uploading assets/js/frontend.js...
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 "..\wp-content\plugins\bkgt-document-management\assets\js\frontend.js" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/wp-content/plugins/bkgt-document-management/assets/js/frontend.js"
if errorlevel 1 (
    echo ERROR: Failed to upload JavaScript file
    exit /b 1
)
echo [OK] JavaScript file uploaded
echo.

REM Verify PHP syntax
echo Verifying PHP syntax on server...
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "php -l %REMOTE_FOLDER%/wp-content/plugins/bkgt-document-management/bkgt-document-management.php" >nul 2>&1
if errorlevel 1 (
    echo WARNING: PHP syntax check failed for main file
) else (
    echo [OK] Main file syntax valid
)

ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "php -l %REMOTE_FOLDER%/wp-content/plugins/bkgt-document-management/frontend/class-frontend.php" >nul 2>&1
if errorlevel 1 (
    echo WARNING: PHP syntax check failed for frontend class
) else (
    echo [OK] Frontend class syntax valid
)
echo.

REM Fix permissions
echo Setting file permissions...
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "chmod 755 %REMOTE_FOLDER%/wp-content/plugins/bkgt-document-management/"
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "chmod 755 %REMOTE_FOLDER%/wp-content/plugins/bkgt-document-management/frontend"
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "chmod 755 %REMOTE_FOLDER%/wp-content/plugins/bkgt-document-management/assets"
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "chmod 755 %REMOTE_FOLDER%/wp-content/plugins/bkgt-document-management/assets/js"
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "chmod 644 %REMOTE_FOLDER%/wp-content/plugins/bkgt-document-management/*.php"
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "chmod 644 %REMOTE_FOLDER%/wp-content/plugins/bkgt-document-management/frontend/*.php"
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "chmod 644 %REMOTE_FOLDER%/wp-content/plugins/bkgt-document-management/assets/js/*.js"
echo [OK] Permissions set
echo.

REM Verify files uploaded
echo Verifying uploaded files...
echo Files on server:
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "ls -lh %REMOTE_FOLDER%/wp-content/plugins/bkgt-document-management/*.php"
echo.

echo === DEPLOYMENT COMPLETE ===
echo.
echo NEXT STEPS:
echo 1. Visit WordPress admin: https://ledare.bkgt.se/wp-admin/
echo 2. Go to: Plugins menu
echo 3. Find: BKGT Document Management
echo 4. Click: Deactivate (if active)
echo 5. Wait 10 seconds
echo 6. Click: Activate
echo 7. Test on [bkgt_documents] shortcode page as coach user
echo.
echo Check browser console (F12) for any errors
echo Check /wp-content/debug.log for PHP errors
echo.

exit /b 0
