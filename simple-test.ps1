# SWE3 Document Scraper - Simple PowerShell Test
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

# Check core files
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

Write-Host ""

# Test 2: Check SWE3 website connectivity
Write-Host "2. Testing SWE3 website connectivity..." -ForegroundColor Yellow
$swe3Url = "https://amerikanskfotboll.swe3.se/information-verktyg/spelregler-tavlingsbestammelser/"

try {
    $response = Invoke-WebRequest -Uri $swe3Url -TimeoutSec 30 -UseBasicParsing -ErrorAction Stop
    if ($response.StatusCode -eq 200) {
        Write-Host "✓ SWE3 website is accessible (Status: $($response.StatusCode))" -ForegroundColor Green
        $contentLength = $response.Content.Length
        Write-Host "✓ Response size: $([math]::Round($contentLength/1024, 2)) KB" -ForegroundColor Green
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
    $testFile = "$uploadDir/swe3-test.tmp"
    try {
        "test" | Out-File -FilePath $testFile -Encoding UTF8 -ErrorAction Stop
        Remove-Item $testFile -ErrorAction Stop
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
    $configContent = Get-Content $configFile -Raw -ErrorAction SilentlyContinue
    if ($configContent -match "define\(\s*['\""]DB_NAME['\""]") {
        Write-Host "✓ Database configuration found" -ForegroundColor Green
    } else {
        Write-Host "✗ Database configuration not found" -ForegroundColor Red
    }
} else {
    Write-Host "✗ wp-config.php not found" -ForegroundColor Red
}

Write-Host ""

# Summary
Write-Host "====================================" -ForegroundColor Cyan
Write-Host "Plugin Status: " -NoNewline
if ($allFilesExist) {
    Write-Host "READY FOR DEPLOYMENT" -ForegroundColor Green
} else {
    Write-Host "ISSUES DETECTED" -ForegroundColor Red
}

Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Cyan
Write-Host "1. Run the SQL script: swe3-plugin-activation.sql" -ForegroundColor White
Write-Host "2. Activate plugin in WordPress admin" -ForegroundColor White
Write-Host "3. Test from Tools > SWE3 Scraper" -ForegroundColor White
Write-Host "4. Monitor daily execution" -ForegroundColor White