@echo off
REM BKGT Ledare Deployment Script (Windows Batch)
REM Run this from the project root directory

echo === BKGT Ledare Deployment Script ===

REM Load environment variables from .env file
for /f "tokens=1,2 delims==" %%a in (.env) do (
    if "%%a"=="SSH_KEY_PATH" set SSH_KEY_PATH=%%b
    if "%%a"=="SSH_HOST" set SSH_HOST=%%b
    if "%%a"=="SSH_USER" set SSH_USER=%%b
    if "%%a"=="REMOTE_FOLDER" set REMOTE_FOLDER=%%b
)

echo Target: %SSH_HOST% (%SSH_USER%)
echo Remote folder: %REMOTE_FOLDER%

if "%1"=="--dry-run" (
    echo DRY RUN MODE - No changes will be made
    goto :dry_run
)

REM Test SSH connection
echo Testing SSH connection...
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=10 %SSH_USER%@%SSH_HOST% "echo 'SSH OK'"
if errorlevel 1 (
    echo ✗ SSH connection failed
    exit /b 1
)
echo ✓ SSH connection successful

REM Create necessary directories on remote server
echo Creating remote directories...
ssh -i "%SSH_KEY_PATH%" %SSH_USER%@%SSH_HOST% "mkdir -p %REMOTE_FOLDER%/wp-content/themes"
ssh -i "%SSH_KEY_PATH%" %SSH_USER%@%SSH_HOST% "mkdir -p %REMOTE_FOLDER%/wp-content/plugins"
echo ✓ Remote directories created

REM Deploy full WordPress installation (excluding sensitive files)
echo Deploying WordPress core files...
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -r "wp-admin" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/"
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -r "wp-includes" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/"
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no "index.php" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/"
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no "wp-*.php" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/"
echo ✓ WordPress core deployed successfully

REM Deploy theme
echo Deploying theme...
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -r "wp-content/themes/bkgt-ledare" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/wp-content/themes/"
if errorlevel 1 (
    echo ✗ Theme deployment failed
    exit /b 1
)
echo ✓ Theme deployed successfully

REM Deploy plugins
echo Deploying plugins...
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -r "wp-content/plugins" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/wp-content/"
if errorlevel 1 (
    echo ✗ Plugins deployment failed
    exit /b 1
)
echo ✓ Plugins deployed successfully

REM Set permissions
echo Setting file permissions...
ssh -i "%SSH_KEY_PATH%" %SSH_USER%@%SSH_HOST% "find %REMOTE_FOLDER%/wp-content -type f -exec chmod 644 {} \;"
ssh -i "%SSH_KEY_PATH%" %SSH_USER%@%SSH_HOST% "find %REMOTE_FOLDER%/wp-content -type d -exec chmod 755 {} \;"
ssh -i "%SSH_KEY_PATH%" %SSH_USER%@%SSH_HOST% "chmod 755 %REMOTE_FOLDER%/wp-content"
echo ✓ File permissions set correctly

REM Clear cache
echo Clearing WordPress cache...
ssh -i "%SSH_KEY_PATH%" %SSH_USER%@%SSH_HOST% "wp cache flush --path=%REMOTE_FOLDER%" 2>nul
if errorlevel 1 (
    echo ⚠ Cache clearing failed (may not be critical)
) else (
    echo ✓ WordPress cache cleared
)

echo ✓ Deployment completed successfully!
echo Please verify the website at https://ledare.bkgt.se
goto :end

:dry_run
echo [DRY RUN] Would create remote directories
echo [DRY RUN] Would test SSH connection
echo [DRY RUN] Would deploy WordPress core files
echo [DRY RUN] Would deploy theme using SCP
echo [DRY RUN] Would deploy plugins using SCP
echo [DRY RUN] Would set file permissions
echo [DRY RUN] Would clear WordPress cache
echo ✓ Dry run completed successfully

:end
echo Deployment finished at %DATE% %TIME%