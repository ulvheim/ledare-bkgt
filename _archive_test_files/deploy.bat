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

@echo off
setlocal enabledelayedexpansion

REM BKGT Ledare Deployment Script (Windows Batch) - Ultra-Robust with Advanced Error Handling
REM Run this from the project root directory

echo === BKGT Ledare Deployment Script ===
echo Ultra-Robust with Advanced Error Handling
echo ==========================================

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

REM Test SSH connection with enhanced timeout and retry logic
echo Testing SSH connection...
set SSH_RETRY_COUNT=0
set SSH_MAX_RETRIES=3

:ssh_test_loop
timeout /t 1 /nobreak >nul 2>&1
ssh -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=15 -o ServerAliveInterval=10 -o ServerAliveCountMax=3 %SSH_USER%@%SSH_HOST% "echo 'SSH OK'" 2>nul
if %errorlevel%==0 (
    echo ✓ SSH connection successful
) else (
    set /a SSH_RETRY_COUNT+=1
    if !SSH_RETRY_COUNT! lss %SSH_MAX_RETRIES% (
        echo ⚠ SSH connection failed (attempt !SSH_RETRY_COUNT!/%SSH_MAX_RETRIES%), retrying in 5 seconds...
        timeout /t 5 /nobreak >nul 2>&1
        goto :ssh_test_loop
    ) else (
        echo ✗ SSH connection failed after %SSH_MAX_RETRIES% attempts
        exit /b 1
    )
)

REM Create necessary directories on remote server with timeout protection
echo Creating remote directories...
set DIR_RETRY_COUNT=0
set DIR_MAX_RETRIES=3

:dir_create_loop
ssh -i "%SSH_KEY_PATH%" -o ConnectTimeout=15 -o ServerAliveInterval=10 %SSH_USER%@%SSH_HOST% "mkdir -p %REMOTE_FOLDER%/wp-content/themes 2>/dev/null && mkdir -p %REMOTE_FOLDER%/wp-content/plugins 2>/dev/null && echo 'Directories created'" 2>nul
if %errorlevel%==0 (
    echo ✓ Remote directories created
) else (
    set /a DIR_RETRY_COUNT+=1
    if !DIR_RETRY_COUNT! lss %DIR_MAX_RETRIES% (
        echo ⚠ Directory creation failed (attempt !DIR_RETRY_COUNT!/%DIR_MAX_RETRIES%), retrying in 3 seconds...
        timeout /t 3 /nobreak >nul 2>&1
        goto :dir_create_loop
    ) else (
        echo ✗ Directory creation failed after %DIR_MAX_RETRIES% attempts
        exit /b 1
    )
)

if %USE_RSYNC%==1 (
    goto :deploy_rsync
) else (
    goto :deploy_scp
)

:deploy_rsync
REM Deploy using rsync (efficient incremental sync) with enhanced timeout and error handling
echo Deploying files using rsync (this may take a few minutes)...
set RSYNC_RETRY_COUNT=0
set RSYNC_MAX_RETRIES=2

:rsync_loop
rsync -avz --delete --no-perms --no-owner --no-group --exclude=".git" --exclude=".gitignore" --exclude=".env" --exclude="node_modules" --exclude=".DS_Store" --exclude="*.log" --exclude="deploy.sh" --exclude="deploy.bat" --exclude="README.md" --exclude=".vscode" --exclude="*.tmp" --exclude="wp-config-sample.php" --timeout=300 --contimeout=30 -e "ssh -i %SSH_KEY_PATH% -o StrictHostKeyChecking=no -o ConnectTimeout=30 -o ServerAliveInterval=15 -o ServerAliveCountMax=3" "./" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/" 2>nul
if %errorlevel%==0 (
    echo ✓ Files deployed successfully using rsync
    goto :post_deploy
) else (
    set /a RSYNC_RETRY_COUNT+=1
    if !RSYNC_RETRY_COUNT! lss %RSYNC_MAX_RETRIES% (
        echo ⚠ rsync deployment failed (attempt !RSYNC_RETRY_COUNT!/%RSYNC_MAX_RETRIES%), retrying in 10 seconds...
        timeout /t 10 /nobreak >nul 2>&1
        goto :rsync_loop
    ) else (
        echo ⚠ rsync failed after %RSYNC_MAX_RETRIES% attempts, falling back to scp...
        goto :deploy_scp
    )
)

:deploy_scp
REM Deploy full WordPress installation (excluding sensitive files) using SCP with enhanced timeout
echo Deploying WordPress core files...
set SCP_RETRY_COUNT=0
set SCP_MAX_RETRIES=2

:scp_core_loop
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 -o ServerAliveInterval=15 "wp-admin" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/" 2>nul && scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 -o ServerAliveInterval=15 "wp-includes" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/" 2>nul && scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 -o ServerAliveInterval=15 "index.php" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/" 2>nul && scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 -o ServerAliveInterval=15 "wp-*.php" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/" 2>nul
if %errorlevel%==0 (
    echo ✓ WordPress core deployed successfully
) else (
    set /a SCP_RETRY_COUNT+=1
    if !SCP_RETRY_COUNT! lss %SCP_MAX_RETRIES% (
        echo ⚠ WordPress core deployment failed (attempt !SCP_RETRY_COUNT!/%SCP_MAX_RETRIES%), retrying in 5 seconds...
        timeout /t 5 /nobreak >nul 2>&1
        goto :scp_core_loop
    ) else (
        echo ✗ WordPress core deployment failed after %SCP_MAX_RETRIES% attempts
        exit /b 1
    )
)

REM Deploy theme with enhanced timeout
echo Deploying theme...
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 -o ServerAliveInterval=15 -r "wp-content/themes/bkgt-ledare" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/wp-content/themes/" 2>nul
if %errorlevel%==0 (
    echo ✓ Theme deployed successfully
) else (
    echo ✗ Theme deployment failed
    exit /b 1
)

REM Deploy plugins with enhanced timeout
echo Deploying plugins...
scp -i "%SSH_KEY_PATH%" -o StrictHostKeyChecking=no -o ConnectTimeout=30 -o ServerAliveInterval=15 -r "wp-content/plugins" "%SSH_USER%@%SSH_HOST%:%REMOTE_FOLDER%/wp-content/" 2>nul
if %errorlevel%==0 (
    echo ✓ Plugins deployed successfully
) else (
    echo ✗ Plugins deployment failed
    exit /b 1
)

:post_deploy

REM Set permissions with enhanced timeout protection
echo Setting file permissions (this may take a moment)...
ssh -i "%SSH_KEY_PATH%" -o ConnectTimeout=30 -o ServerAliveInterval=15 %SSH_USER%@%SSH_HOST% "find %REMOTE_FOLDER%/wp-content -type f -exec chmod 644 {} \; 2>/dev/null && echo 'File permissions set'" 2>nul
if %errorlevel%==0 (
    echo ✓ File permissions set correctly
) else (
    echo ⚠ File permissions setting failed (may not be critical)
)

ssh -i "%SSH_KEY_PATH%" -o ConnectTimeout=30 -o ServerAliveInterval=15 %SSH_USER%@%SSH_HOST% "find %REMOTE_FOLDER%/wp-content -type d -exec chmod 755 {} \; 2>/dev/null && echo 'Directory permissions set'" 2>nul
if %errorlevel%==0 (
    echo ✓ Directory permissions set correctly
) else (
    echo ⚠ Directory permissions setting failed (may not be critical)
)

ssh -i "%SSH_KEY_PATH%" -o ConnectTimeout=30 -o ServerAliveInterval=15 %SSH_USER%@%SSH_HOST% "chmod 755 %REMOTE_FOLDER%/wp-content 2>/dev/null && echo 'Content directory permissions set'" 2>nul
if %errorlevel%==0 (
    echo ✓ Content directory permissions set correctly
) else (
    echo ⚠ Content directory permissions setting failed (may not be critical)
)

REM Clear cache with enhanced timeout
echo Clearing WordPress cache...
ssh -i "%SSH_KEY_PATH%" -o ConnectTimeout=30 -o ServerAliveInterval=15 %SSH_USER%@%SSH_HOST% "timeout 60 wp cache flush --path=%REMOTE_FOLDER% 2>/dev/null && echo 'Cache cleared'" 2>nul
if %errorlevel%==0 (
    echo ✓ WordPress cache cleared
) else (
    echo ⚠ Cache clearing failed (may not be critical - WP-CLI may not be available)
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