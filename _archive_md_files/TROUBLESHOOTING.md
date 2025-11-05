# Troubleshooting Guide - 500 Error Fix

## Problem Identified
The main page was showing a generic 500 error due to **missing class checks** in the BKGT Inventory plugin. The plugin was trying to use classes from the BKGT User Management plugin without verifying they exist first, causing a fatal PHP error.

## What Was Fixed

### 1. Added Class Existence Checks in `class-assignment.php`
The following methods now check if required classes exist before using them:
- `assign_to_team()` - Checks for `BKGT_Team` class
- `user_can_access_item()` - Checks for `BKGT_Team` and `BKGT_User_Team_Assignment` classes  
- `get_user_items()` - Conditionally builds queries based on class availability

**Impact:** The inventory plugin will now work even if the User Management plugin isn't activated, though some team-related features will be limited.

### 2. Enabled WordPress Debug Mode
Updated `wp-config.php` to enable proper error logging:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

**Impact:** Errors will be logged to `wp-content/debug.log` without displaying them to visitors.

### 3. Added Dependency Warning
Added an admin notice in the Inventory plugin that warns administrators if the User Management plugin is not active.

**Impact:** Administrators will see a friendly warning instead of getting a broken site.

## How to Diagnose Future Issues

### Method 1: Use the Diagnostic Test File
1. Access the diagnostic file directly in your browser:
   ```
   http://ledare.bkgt.se/diagnostic-test.php
   ```
2. This will show:
   - PHP version
   - WordPress loading status
   - Database connection
   - Active plugins and their status
   - Theme information
   - Class availability
   - Homepage loading test

### Method 2: Check the Debug Log
1. Enable debug mode (already done in `wp-config.php`)
2. Look for the debug log at:
   ```
   wp-content/debug.log
   ```
3. The log will contain detailed PHP errors and warnings

### Method 3: Check Server Error Logs
If you have SSH access to your server:
```bash
tail -f /var/log/apache2/error.log
# or for nginx:
tail -f /var/log/nginx/error.log
```

## Plugin Dependencies

### Current Plugin Structure
```
bkgt-user-management (Base - no dependencies)
├── Defines: BKGT_Team
├── Defines: BKGT_User_Team_Assignment
└── Defines: BKGT_Capabilities

bkgt-inventory (Depends on: bkgt-user-management)
├── Uses: BKGT_Team (with safety checks)
├── Uses: BKGT_User_Team_Assignment (with safety checks)
└── Defines: BKGT_Assignment

bkgt-document-management (No cross-plugin dependencies)
└── Standalone plugin
```

### Recommended Plugin Activation Order
1. **BKGT User Management** (activate first)
2. **BKGT Inventory System**
3. **BKGT Document Management**

## Common Issues and Solutions

### Issue: 500 Error on Homepage
**Cause:** Plugin dependency not met or fatal PHP error  
**Solution:** 
1. Run diagnostic-test.php
2. Check debug.log
3. Verify all plugins are activated in correct order

### Issue: "Class not found" Errors
**Cause:** Plugin not activated or loading in wrong order  
**Solution:**
1. Deactivate all BKGT plugins
2. Reactivate in order: User Management → Inventory → Documents

### Issue: Admin Panel Works but Frontend Doesn't
**Cause:** Theme error or missing template  
**Solution:**
1. Check theme files in `wp-content/themes/bkgt-ledare/`
2. Temporarily switch to a default WordPress theme
3. Check debug.log for theme-related errors

### Issue: Database Connection Errors
**Cause:** Incorrect database credentials in wp-config.php  
**Solution:**
1. Verify credentials in wp-config.php:
   - DB_NAME: bkgt_se_db_1
   - DB_USER: dbaadmin@b383837
   - DB_HOST: mysql513.loopia.se
2. Test connection using diagnostic-test.php

## After Fixing

### Steps to Verify Fix
1. ✅ Access diagnostic-test.php and ensure all tests pass
2. ✅ Check homepage loads without errors
3. ✅ Verify admin panel is accessible
4. ✅ Test each plugin's functionality
5. ✅ Check that team assignments work (if User Management is active)

### Disable Debug Mode (Production)
Once issues are resolved, you may want to disable debug display for production:
```php
define( 'WP_DEBUG', false );  // or keep true for logging only
define( 'WP_DEBUG_LOG', true );  // keep logging enabled
define( 'WP_DEBUG_DISPLAY', false );  // don't show errors to visitors
```

## Contact Information
If issues persist:
1. Check the debug log at `wp-content/debug.log`
2. Run the diagnostic test at `/diagnostic-test.php`
3. Document any error messages
4. Review this guide for similar issues

## Deployment History

### October 24, 2025 - Fixed 500 Error
**Issue:** Main page throwing generic 500 error due to missing class checks in BKGT Inventory plugin.

**Root Cause:** The inventory plugin was calling classes from the user management plugin without verifying they exist, causing fatal PHP errors.

**Fixes Applied:**
- ✅ Added class existence checks in `class-assignment.php` for `BKGT_Team` and `BKGT_User_Team_Assignment`
- ✅ Enabled WordPress debug logging in `wp-config.php`
- ✅ Added admin dependency warning for missing plugins
- ✅ Created diagnostic tools for troubleshooting

**Deployment Method:** Manual SCP commands (PowerShell script had parsing issues in deployment environment)
- SSH connection tested successfully
- Theme, plugins, and config deployed via SCP
- File permissions set correctly
- WordPress cache flush attempted (wp-cli not available on server)

**Files Deployed:**
- `wp-content/themes/bkgt-ledare/` (complete theme)
- `wp-content/plugins/bkgt-inventory/` (fixed inventory plugin)
- `wp-content/plugins/bkgt-user-management/` (user management plugin)
- `wp-content/plugins/bkgt-document-management/` (document management plugin)
- `wp-config.php` (with debug logging enabled)

**Post-Deployment Status:** Site should now load without 500 errors. Debug logging enabled for monitoring.
