#!/bin/bash
# BKGT API Authentication Test Script
# Run this after deployment to verify API authentication is working

API_URL="https://ledare.bkgt.se"
API_KEY="f1d0f6be40b1d78d3ac876b7be41e792"

echo "🔍 BKGT API Authentication Test"
echo "================================"
echo "API URL: $API_URL"
echo "API Key: $API_KEY"
echo ""

# Function to test endpoint
test_endpoint() {
    local endpoint=$1
    local description=$2

    echo "Testing: $description"
    echo "Endpoint: $endpoint"

    response=$(curl -s -w "\nHTTPSTATUS:%{http_code}" \
        -H "X-API-Key: $API_KEY" \
        "$API_URL$endpoint")

    http_code=$(echo "$response" | tr -d '\n' | sed -e 's/.*HTTPSTATUS://')
    body=$(echo "$response" | sed -e 's/HTTPSTATUS:.*//g')

    echo "Status Code: $http_code"

    if [ "$http_code" = "200" ]; then
        echo "✅ SUCCESS"
        # Show first 200 chars of response
        echo "Response preview: $(echo "$body" | head -c 200)..."
    else
        echo "❌ FAILED"
        echo "Response: $body"
    fi
    echo "---"
}

# Test endpoints
test_endpoint "/wp-json/bkgt/v1/teams" "Teams endpoint"
test_endpoint "/wp-json/bkgt/v1/equipment/manufacturers" "Equipment manufacturers"
test_endpoint "/wp-json/bkgt/v1/equipment/types" "Equipment types"
test_endpoint "/wp-json/bkgt/v1/stats/overview" "Statistics overview"

# Test without authentication (should fail)
echo "Testing: Authentication required (should fail)"
echo "Endpoint: /wp-json/bkgt/v1/teams"
echo "Headers: None"

response=$(curl -s -w "\nHTTPSTATUS:%{http_code}" \
    "$API_URL/wp-json/bkgt/v1/teams")

http_code=$(echo "$response" | tr -d '\n' | sed -e 's/.*HTTPSTATUS://')

echo "Status Code: $http_code"

if [ "$http_code" = "401" ]; then
    echo "✅ SUCCESS - Authentication properly required"
else
    echo "❌ FAILED - Authentication not enforced"
fi

echo ""
echo "🎯 Test Complete"
echo "If all tests show SUCCESS, the API authentication is working correctly."