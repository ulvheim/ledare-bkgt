@echo off
REM BKGT Ledare Production Deployment Script
REM Uses SSH and tar for deployment to Loopia hosting

setlocal enabledelayedexpansion

echo.
echo === BKGT Ledare Production Deployment ===
echo.

REM Load environment variables
for /f "tokens=1,* delims==" %%A in (.env) do (
    if not "%%A"=="" (
        if not "%%A:~0,1%"=="#" (
            set "%%A=%%B"
        )
    )
)

echo Target: %SSH_HOST% (%SSH_USER%)
echo Remote folder: %REMOTE_FOLDER%
echo.

REM Test SSH connection
echo Testing SSH connection...
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "echo SSH OK" >nul 2>&1
if errorlevel 1 (
    echo ERROR: SSH connection failed
    exit /b 1
)
echo [OK] SSH connection successful

echo.
echo Deploying modified plugin files...
echo.

REM Create temporary tar archive of plugins directory
echo Creating deployment archive...
cd /d "%~dp0"
if exist deploy-temp.tar del deploy-temp.tar

REM Deploy bkgt-inventory plugin (the one with modified forms)
echo Uploading bkgt-inventory plugin...
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "mkdir -p %REMOTE_FOLDER%/wp-content/plugins/bkgt-inventory"

REM Upload specific modified file
echo Uploading modified class-admin.php...
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 "wp-content\plugins\bkgt-inventory\admin\class-admin.php" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/wp-content/plugins/bkgt-inventory/admin/class-admin.php"
if errorlevel 1 (
    echo ERROR: Failed to upload bkgt-inventory admin class
    exit /b 1
)
echo [OK] bkgt-inventory plugin updated

echo.
echo Uploading bkgt-team-player plugin...
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "mkdir -p %REMOTE_FOLDER%/wp-content/plugins/bkgt-team-player"

REM Upload specific modified file
echo Uploading modified bkgt-team-player.php...
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 "wp-content\plugins\bkgt-team-player\bkgt-team-player.php" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/wp-content/plugins/bkgt-team-player/bkgt-team-player.php"
if errorlevel 1 (
    echo ERROR: Failed to upload bkgt-team-player
    exit /b 1
)
echo [OK] bkgt-team-player plugin updated

echo.
echo Setting file permissions...
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "chmod 644 %REMOTE_FOLDER%/wp-content/plugins/bkgt-inventory/admin/class-admin.php"
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "chmod 644 %REMOTE_FOLDER%/wp-content/plugins/bkgt-team-player/bkgt-team-player.php"
echo [OK] Permissions set

echo.
echo Verifying deployment on remote server...
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "ls -lh %REMOTE_FOLDER%/wp-content/plugins/bkgt-inventory/admin/class-admin.php"
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "ls -lh %REMOTE_FOLDER%/wp-content/plugins/bkgt-team-player/bkgt-team-player.php"

echo.
echo Clearing WordPress cache on remote server...
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no %SSH_USER%@%SSH_HOST% "cd %REMOTE_FOLDER% && wp cache flush 2>/dev/null" || echo [WARNING] Cache flush may not be available

echo.
echo ===================================
echo [SUCCESS] Deployment completed!
echo ===================================
echo.
echo Website: https://ledare.bkgt.se
echo.
echo Changes deployed:
echo - bkgt-inventory: Updated Manufacturer, Item Type, and Equipment forms
echo - bkgt-team-player: Updated Event form
echo.
echo Forms now include:
echo   + Real-time JavaScript validation
echo   + Professional error handling and display
echo   + BKGT_Sanitizer integration
echo   + BKGT_Validator integration
echo   + Enhanced security verification
echo.
echo Next step: Test forms in production environment at https://ledare.bkgt.se/wp-admin
echo.

endlocal
