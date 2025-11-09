# PowerShell test script for BKGT API endpoints
$BASE_URL = "https://ledare.bkgt.se/wp-json/bkgt/v1"
$API_KEY = "your-api-key-here"  # Replace with actual API key

Write-Host "Testing BKGT API endpoints..."
Write-Host "=============================="

# Test stats overview
Write-Host "Testing /stats/overview..."
try {
    $response = Invoke-RestMethod -Uri "$BASE_URL/stats/overview" -Headers @{"X-API-Key" = $API_KEY} -Method Get
    $response | ConvertTo-Json
} catch {
    Write-Host "Error: $($_.Exception.Message)"
}

Write-Host "`nTesting /equipment/analytics/overview..."
try {
    $response = Invoke-RestMethod -Uri "$BASE_URL/equipment/analytics/overview" -Headers @{"X-API-Key" = $API_KEY} -Method Get
    $response | ConvertTo-Json
} catch {
    Write-Host "Error: $($_.Exception.Message)"
}

Write-Host "`nTesting /equipment/preview-identifier (should fail without params)..."
try {
    $response = Invoke-RestMethod -Uri "$BASE_URL/equipment/preview-identifier" -Headers @{"X-API-Key" = $API_KEY} -Method Get
    $response | ConvertTo-Json
} catch {
    Write-Host "Error: $($_.Exception.Message)"
}

Write-Host "`nTesting /equipment/preview-identifier (with params)..."
try {
    $response = Invoke-RestMethod -Uri "$BASE_URL/equipment/preview-identifier?manufacturer_id=1&item_type_id=1" -Headers @{"X-API-Key" = $API_KEY} -Method Get
    $response | ConvertTo-Json
} catch {
    Write-Host "Error: $($_.Exception.Message)"
}

Write-Host "`nTesting admin endpoint (should return 403)..."
try {
    $response = Invoke-RestMethod -Uri "$BASE_URL/admin/api-keys" -Headers @{"X-API-Key" = $API_KEY} -Method Get
    $response | ConvertTo-Json
} catch {
    Write-Host "Error: $($_.Exception.Message)"
}

Write-Host "=============================="
Write-Host "Test complete!"