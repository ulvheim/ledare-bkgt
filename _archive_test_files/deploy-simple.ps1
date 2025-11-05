# Simple Deployment Script for BKGT Ledare
# This script avoids complex PowerShell quoting by using basic commands

param(
    [switch]$DryRun
)

Write-Host "=== BKGT Ledare Simple Deployment ===" -ForegroundColor Green

if ($DryRun) {
    Write-Host "DRY RUN MODE - No changes will be made" -ForegroundColor Yellow
}

# Load environment variables
$envContent = Get-Content ".env"
$SSH_KEY_PATH = ""
$SSH_HOST = ""
$SSH_USER = ""
$REMOTE_FOLDER = ""

foreach ($line in $envContent) {
    if ($line -match '^([^#][^=]+)=(.*)$') {
        $key = $matches[1].Trim()
        $value = $matches[2].Trim()
        switch ($key) {
            "SSH_KEY_PATH" { $SSH_KEY_PATH = $value }
            "SSH_HOST" { $SSH_HOST = $value }
            "SSH_USER" { $SSH_USER = $value }
            "REMOTE_FOLDER" { $REMOTE_FOLDER = $value }
        }
    }
}

Write-Host "Target: $SSH_HOST ($SSH_USER)" -ForegroundColor Blue
Write-Host "Remote folder: $REMOTE_FOLDER" -ForegroundColor Blue

# Test SSH connection
Write-Host "Testing SSH connection..." -ForegroundColor Blue
$sshArgs = @(
    "-i", $SSH_KEY_PATH,
    "-o", "StrictHostKeyChecking=no",
    "-o", "ConnectTimeout=10",
    "$SSH_USER@$SSH_HOST",
    "echo 'SSH OK'"
)

if ($DryRun) {
    Write-Host "[DRY RUN] Would run: ssh $($sshArgs -join ' ')" -ForegroundColor Yellow
} else {
    $result = & ssh @sshArgs 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ SSH connection successful" -ForegroundColor Green
    } else {
        Write-Host "✗ SSH connection failed" -ForegroundColor Red
        exit 1
    }
}

# Deploy theme
Write-Host "Deploying theme..." -ForegroundColor Blue
$rsyncArgs = @(
    "-avz",
    "--delete",
    "--exclude=.git",
    "--exclude=.env",
    "--exclude=node_modules",
    "--exclude=*.log",
    "--exclude=deploy.*",
    "-e", "ssh -i $SSH_KEY_PATH",
    "wp-content/themes/bkgt-ledare/",
    "$SSH_USER@$SSH_HOST`:$REMOTE_FOLDER/wp-content/themes/bkgt-ledare/"
)

if ($DryRun) {
    Write-Host "[DRY RUN] Would run: rsync $($rsyncArgs -join ' ')" -ForegroundColor Yellow
} else {
    $result = & rsync @rsyncArgs 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Theme deployed successfully" -ForegroundColor Green
    } else {
        Write-Host "✗ Theme deployment failed" -ForegroundColor Red
        exit 1
    }
}

# Deploy plugins
Write-Host "Deploying plugins..." -ForegroundColor Blue
$rsyncArgs = @(
    "-avz",
    "--delete",
    "--exclude=.git",
    "--exclude=.env",
    "--exclude=node_modules",
    "--exclude=*.log",
    "--exclude=deploy.*",
    "-e", "ssh -i $SSH_KEY_PATH",
    "wp-content/plugins/",
    "$SSH_USER@$SSH_HOST`:$REMOTE_FOLDER/wp-content/plugins/"
)

if ($DryRun) {
    Write-Host "[DRY RUN] Would run: rsync $($rsyncArgs -join ' ')" -ForegroundColor Yellow
} else {
    $result = & rsync @rsyncArgs 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Plugins deployed successfully" -ForegroundColor Green
    } else {
        Write-Host "✗ Plugins deployment failed" -ForegroundColor Red
        exit 1
    }
}

# Set permissions
Write-Host "Setting file permissions..." -ForegroundColor Blue
$permCommands = @(
    'find $REMOTE_FOLDER/wp-content -type f -exec chmod 644 {} \;',
    'find $REMOTE_FOLDER/wp-content -type d -exec chmod 755 {} \;',
    'chmod 755 $REMOTE_FOLDER/wp-content'
)

foreach ($cmd in $permCommands) {
    $sshArgs = @(
        "-i", $SSH_KEY_PATH,
        "$SSH_USER@$SSH_HOST",
        "$cmd"
    )

    if ($DryRun) {
        Write-Host "[DRY RUN] Would run: ssh $($sshArgs -join ' ')" -ForegroundColor Yellow
    } else {
        $result = & ssh @sshArgs 2>$null
        if ($LASTEXITCODE -ne 0) {
            Write-Host "✗ Permission setting failed: $cmd" -ForegroundColor Red
            exit 1
        }
    }
}

if (!$DryRun) {
    Write-Host "✓ File permissions set correctly" -ForegroundColor Green
}

# Clear cache
Write-Host "Clearing WordPress cache..." -ForegroundColor Blue
$sshArgs = @(
    "-i", $SSH_KEY_PATH,
    "$SSH_USER@$SSH_HOST",
    "wp cache flush --path=$REMOTE_FOLDER"
)

if ($DryRun) {
    Write-Host "[DRY RUN] Would run: ssh $($sshArgs -join ' ')" -ForegroundColor Yellow
} else {
    $result = & ssh @sshArgs 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ WordPress cache cleared" -ForegroundColor Green
    } else {
        Write-Host "⚠ Cache clearing failed (may not be critical)" -ForegroundColor Yellow
    }
}

if ($DryRun) {
    Write-Host "✓ Dry run completed successfully" -ForegroundColor Green
} else {
    Write-Host "✓ Deployment completed successfully!" -ForegroundColor Green
    Write-Host "Please verify the website at https://ledare.bkgt.se" -ForegroundColor Blue
}

Write-Host "Deployment finished at $(Get-Date)" -ForegroundColor Blue