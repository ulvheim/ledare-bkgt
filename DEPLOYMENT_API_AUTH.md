# BKGT API Authentication Deployment Instructions

## 🚀 Deployment Status: Ready for Production

The BKGT API has been updated with proper authentication requirements. All endpoints now require authentication using either JWT tokens or API keys.

## 📋 Files to Deploy

Upload the following files to your WordPress server:

### Target Directory: `/wp-content/plugins/bkgt-api/`

#### Core Plugin Files:
- `bkgt-api.php` (20.2 KB)
- `includes/class-bkgt-endpoints.php` (84.0 KB) - **UPDATED: Authentication logic**
- `includes/class-bkgt-auth.php` (14.2 KB)
- `includes/class-bkgt-api.php` (29.1 KB)
- `includes/class-bkgt-security.php` (22.2 KB)
- `includes/class-bkgt-notifications.php` (20.5 KB)

#### Admin Interface:
- `admin/class-bkgt-api-admin.php` (52.1 KB)
- `admin/css/admin.css` (11.0 KB)
- `admin/js/admin.js` (29.0 KB)

#### Documentation:
- `README.md` (49.7 KB) - **UPDATED: Developer guide**

#### Test Files:
- `test-production-api.php` (NEW: Production test suite)
- `test-api-key-auth.php` (API key testing)

## 🔧 Deployment Method

### Option 1: SFTP/SCP Upload
```bash
# Using SCP
scp wp-content/plugins/bkgt-api/* user@server:/path/to/wp-content/plugins/bkgt-api/

# Using SFTP
sftp user@server
cd /path/to/wp-content/plugins/bkgt-api/
put wp-content/plugins/bkgt-api/*
```

### Option 2: File Manager
Use your hosting control panel's file manager to upload the files.

## ✅ Post-Deployment Verification

1. **Activate Plugin** (if not already active):
   - Go to WordPress Admin → Plugins
   - Activate "BKGT API"

2. **Test Authentication**:
   - Visit: `https://ledare.bkgt.se/wp-content/plugins/bkgt-api/test-production-api.php`
   - Should show 5/5 tests passing

3. **Manual API Test**:
   ```bash
   curl -H "X-API-Key: f1d0f6be40b1d78d3ac876b7be41e792" \
        "https://ledare.bkgt.se/wp-json/bkgt/v1/teams"
   ```
   Should return HTTP 200 with team data.

## 🔐 Security Changes

### What Changed:
- **All endpoints now require authentication**
- Removed public access exceptions for equipment/stats endpoints
- Updated `validate_token()` method to enforce authentication
- Updated documentation to reflect authentication requirements

### API Key for Production:
**`f1d0f6be40b1d78d3ac876b7be41e792`**

### Authentication Methods:
1. **API Key**: `X-API-Key: f1d0f6be40b1d78d3ac876b7be41e792`
2. **JWT Token**: `Authorization: Bearer <token>`

## 📱 For App Developers

See the updated README.md for:
- Quick start guide
- Authentication examples
- Error handling
- Common endpoints
- Testing instructions

## 🧪 Testing URLs

- **Production Test Suite**: https://ledare.bkgt.se/wp-content/plugins/bkgt-api/test-production-api.php
- **API Documentation**: https://ledare.bkgt.se/wp-content/plugins/bkgt-api/README.md

## 🚨 Rollback Plan

If issues occur:
1. Restore previous `class-bkgt-endpoints.php`
2. Deactivate/reactivate plugin
3. Clear any caching

## 📞 Support

- Test suite URL: https://ledare.bkgt.se/wp-content/plugins/bkgt-api/test-production-api.php
- Check error logs in WordPress admin
- Verify API key is correct in requests