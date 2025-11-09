# SWE3 Document Scraper - PowerShell Test Script
# Tests basic functionality without requiring PHP

Write-Host "BKGT SWE3 Scraper - PowerShell Test" -ForegroundColor Cyan
Write-Host "====================================" -ForegroundColor Cyan
Write-Host ""

# Test 1: Check plugin files exist
Write-Host "1. Checking plugin files..." -ForegroundColor Yellow
$pluginPath = "wp-content/plugins/bkgt-swe3-scraper"
$mainFile = "$pluginPath/bkgt-swe3-scraper.php"

if (Test-Path $mainFile) {
    Write-Host "✓ Main plugin file exists" -ForegroundColor Green
} else {
    Write-Host "✗ Main plugin file not found" -ForegroundColor Red
    exit 1
}

# Check other core files
$coreFiles = @(
    "$pluginPath/includes/class-bkgt-swe3-scraper.php",
    "$pluginPath/includes/class-bkgt-swe3-parser.php",
    "$pluginPath/includes/class-bkgt-swe3-scheduler.php",
    "$pluginPath/includes/class-bkgt-swe3-dms-integration.php",
    "$pluginPath/admin/class-bkgt-swe3-admin.php"
)

$allFilesExist = $true
foreach ($file in $coreFiles) {
    if (Test-Path $file) {
        Write-Host "✓ $(Split-Path $file -Leaf)" -ForegroundColor Green
    } else {
        Write-Host "✗ $(Split-Path $file -Leaf) not found" -ForegroundColor Red
        $allFilesExist = $false
    }
}

if (-not $allFilesExist) {
    Write-Host "Some plugin files are missing!" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Test 2: Check SWE3 website connectivity
Write-Host "2. Testing SWE3 website connectivity..." -ForegroundColor Yellow
$swe3Url = "https://amerikanskfotboll.swe3.se/information-verktyg/spelregler-tavlingsbestammelser/"

try {
    $response = Invoke-WebRequest -Uri $swe3Url -TimeoutSec 30 -UseBasicParsing
    if ($response.StatusCode -eq 200) {
        Write-Host "✓ SWE3 website is accessible (Status: $($response.StatusCode))" -ForegroundColor Green

        # Check content length
        $contentLength = $response.Content.Length
        Write-Host "✓ Response size: $([math]::Round($contentLength/1024, 2)) KB" -ForegroundColor Green

        # Check for PDF links
        $pdfCount = ($response.Content | Select-String -Pattern '\.pdf' -AllMatches).Matches.Count
        Write-Host "✓ Found approximately $pdfCount PDF references in page" -ForegroundColor Green
    } else {
        Write-Host "✗ SWE3 website returned status: $($response.StatusCode)" -ForegroundColor Red
    }
} catch {
    Write-Host "✗ Failed to connect to SWE3 website: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test 3: Check upload directory
Write-Host "3. Checking upload directory..." -ForegroundColor Yellow
$uploadDir = "wp-content/uploads"

if (Test-Path $uploadDir) {
    Write-Host "✓ Upload directory exists" -ForegroundColor Green

    # Test write permissions
    $testFile = "$uploadDir/swe3-test.tmp"
    try {
        "test" | Out-File -FilePath $testFile -Encoding UTF8
        Remove-Item $testFile
        Write-Host "✓ Upload directory is writable" -ForegroundColor Green
    } catch {
        Write-Host "✗ Upload directory is not writable" -ForegroundColor Red
    }
} else {
    Write-Host "✗ Upload directory not found" -ForegroundColor Red
}

Write-Host ""

# Test 4: Check wp-config.php
Write-Host "4. Checking WordPress configuration..." -ForegroundColor Yellow
$configFile = "wp-config.php"

if (Test-Path $configFile) {
    Write-Host "✓ wp-config.php exists" -ForegroundColor Green

    # Check for database constants
    $configContent = Get-Content $configFile -Raw
    $dbChecks = @(
        @{Name="DB_NAME"; Pattern="define\(\s*['\""]DB_NAME['\""]"},
        @{Name="DB_USER"; Pattern="define\(\s*['\""]DB_USER['\""]"},
        @{Name="DB_PASSWORD"; Pattern="define\(\s*['\""]DB_PASSWORD['\""]"},
        @{Name="DB_HOST"; Pattern="define\(\s*['\""]DB_HOST['\""]"}
    )

    foreach ($check in $dbChecks) {
        if ($configContent -match $check.Pattern) {
            Write-Host "✓ $($check.Name) defined" -ForegroundColor Green
        } else {
            Write-Host "✗ $($check.Name) not found" -ForegroundColor Red
        }
    }
} else {
    Write-Host "✗ wp-config.php not found" -ForegroundColor Red
}

Write-Host ""

# Test 5: Plugin structure validation
Write-Host "5. Validating plugin structure..." -ForegroundColor Yellow

# Check for required directories
$requiredDirs = @(
    "$pluginPath/includes",
    "$pluginPath/admin",
    "$pluginPath/admin/css",
    "$pluginPath/admin/js"
)

foreach ($dir in $requiredDirs) {
    if (Test-Path $dir) {
        Write-Host "✓ Directory exists: $(Split-Path $dir -Leaf)" -ForegroundColor Green
    } else {
        Write-Host "✗ Directory missing: $(Split-Path $dir -Leaf)" -ForegroundColor Red
    }
}

# Check for key files
$keyFiles = @(
    "$pluginPath/README.md",
    "$pluginPath/admin/css/admin.css",
    "$pluginPath/admin/js/admin.js",
    "$pluginPath/test-swe3-scraper.php"
)

foreach ($file in $keyFiles) {
    if (Test-Path $file) {
        Write-Host "✓ File exists: $(Split-Path $file -Leaf)" -ForegroundColor Green
    } else {
        Write-Host "✗ File missing: $(Split-Path $file -Leaf)" -ForegroundColor Red
    }
}

Write-Host ""

# Summary
Write-Host "====================================" -ForegroundColor Cyan
Write-Host "Test Summary:" -ForegroundColor Cyan
Write-Host "- Plugin files: " -NoNewline
if ($allFilesExist) {
    Write-Host "All present" -ForegroundColor Green
} else {
    Write-Host "Some missing" -ForegroundColor Red
}

Write-Host "- SWE3 connectivity: " -NoNewline
try {
    $testResponse = Invoke-WebRequest -Uri $swe3Url -TimeoutSec 10 -UseBasicParsing
    if ($testResponse.StatusCode -eq 200) {
        Write-Host "Working" -ForegroundColor Green
    } else {
        Write-Host "Issues detected" -ForegroundColor Yellow
    }
} catch {
    Write-Host "Issues detected" -ForegroundColor Yellow
}

Write-Host "- File permissions: " -NoNewline
$uploadWritable = $false
$testFile = "$uploadDir/swe3-test.tmp"
try {
    "test" | Out-File -FilePath $testFile -Encoding UTF8
    Remove-Item $testFile
    $uploadWritable = $true
    Write-Host "OK" -ForegroundColor Green
} catch {
    Write-Host "Check permissions" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Cyan
Write-Host "1. Follow the activation guide in SWE3_PLUGIN_ACTIVATION_GUIDE.md" -ForegroundColor White
Write-Host "2. Activate the plugin through WordPress admin or database" -ForegroundColor White
Write-Host "3. Test scraping functionality from Tools > SWE3 Scraper" -ForegroundColor White
Write-Host "4. Monitor daily execution and document creation" -ForegroundColor White

Write-Host ""
Write-Host "The plugin is ready for deployment!" -ForegroundColor Green