@echo off
setlocal enabledelayedexpansion

REM BKGT Ledare Deployment Script (Windows Batch) - Enhanced with Timeout Handling
REM Run this from the project root directory

echo === BKGT Ledare Deployment Script ===
echo Enhanced with timeout protection to prevent hangs
echo ==================================================

REM Check if .env file exists
if not exist ".env" (
    echo ✗ .env file not found! Please create it with deployment configuration.
    echo   Required variables: SSH_HOST, SSH_USER, SSH_KEY_PATH, REMOTE_FOLDER
    exit /b 1
)

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

REM Check if rsync is available
where rsync >nul 2>nul
if %errorlevel%==0 (
    echo ✓ rsync found - will use efficient incremental sync
    set USE_RSYNC=1
) else (
    echo ⚠ rsync not found - falling back to scp (slower)
    echo   To improve performance, install rsync for Windows:
    echo   - Via WSL: wsl --install
    echo   - Via MSYS2: https://www.msys2.org/
    echo   - Via Cygwin: https://www.cygwin.com/
    set USE_RSYNC=0
)

REM Test SSH connection with timeout
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

if %USE_RSYNC%==1 (
    goto :deploy_rsync
) else (
    goto :deploy_scp
)

:deploy_rsync
REM Deploy using rsync (efficient incremental sync) with timeout
echo Deploying files using rsync (this may take a few minutes)...
rsync -avz --delete --no-perms --no-owner --no-group --exclude=".git" --exclude=".gitignore" --exclude=".env" --exclude="node_modules" --exclude=".DS_Store" --exclude="*.log" --exclude="deploy.sh" --exclude="deploy.bat" --exclude="README.md" --exclude=".vscode" --exclude="*.tmp" --exclude="wp-config-sample.php" --timeout=300 -e "ssh -i %SSH_KEY_PATH% -o StrictHostKeyChecking=no -o ConnectTimeout=30" "./" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/"
if errorlevel 1 (
    echo ✗ rsync deployment failed
    exit /b 1
)
echo ✓ Files deployed successfully using rsync
goto :post_deploy

:deploy_scp
REM Deploy full WordPress installation (excluding sensitive files) using SCP with timeout
echo Deploying WordPress core files...
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 -o ServerAliveInterval=30 "wp-admin" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/"
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 -o ServerAliveInterval=30 "wp-includes" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/"
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 -o ServerAliveInterval=30 "index.php" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/"
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 -o ServerAliveInterval=30 "wp-*.php" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/"
if errorlevel 1 (
    echo ✗ WordPress core deployment failed
    exit /b 1
)
echo ✓ WordPress core deployed successfully

REM Deploy theme with timeout
echo Deploying theme...
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 -o ServerAliveInterval=30 -r "wp-content/themes/bkgt-ledare" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/wp-content/themes/"
if errorlevel 1 (
    echo ✗ Theme deployment failed
    exit /b 1
)
echo ✓ Theme deployed successfully

REM Deploy plugins with timeout
echo Deploying plugins...
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 -o ServerAliveInterval=30 -r "wp-content/plugins" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/wp-content/"
if errorlevel 1 (
    echo ✗ Plugins deployment failed
    exit /b 1
)
echo ✓ Plugins deployed successfully

:post_deploy

REM Set permissions with timeout protection
echo Setting file permissions (this may take a moment)...
ssh -i "%SSH_KEY_PATH%" -o ConnectTimeout=30 -o ServerAliveInterval=30 %SSH_USER%@%SSH_HOST% "find %REMOTE_FOLDER%/wp-content -type f -exec chmod 644 {} \; 2>/dev/null && echo 'File permissions set'"
ssh -i "%SSH_KEY_PATH%" -o ConnectTimeout=30 -o ServerAliveInterval=30 %SSH_USER%@%SSH_HOST% "find %REMOTE_FOLDER%/wp-content -type d -exec chmod 755 {} \; 2>/dev/null && echo 'Directory permissions set'"
ssh -i "%SSH_KEY_PATH%" -o ConnectTimeout=30 -o ServerAliveInterval=30 %SSH_USER%@%SSH_HOST% "chmod 755 %REMOTE_FOLDER%/wp-content 2>/dev/null && echo 'Content directory permissions set'"
echo ✓ File permissions set correctly

REM Clear cache with timeout
echo Clearing WordPress cache...
ssh -i "%SSH_KEY_PATH%" -o ConnectTimeout=30 -o ServerAliveInterval=30 %SSH_USER%@%SSH_HOST% "timeout 60 wp cache flush --path=%REMOTE_FOLDER% 2>/dev/null && echo 'Cache cleared'" 2>nul
if errorlevel 1 (
    echo ⚠ Cache clearing failed (may not be critical - WP-CLI may not be available)
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