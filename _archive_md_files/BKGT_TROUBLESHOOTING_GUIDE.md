# BKGT System Troubleshooting Guide

## Quick Diagnostics

### Check BKGT Core Status
```php
// Test in WordPress admin or via code snippet
if (function_exists('bkgt_log')) {
    echo "✓ BKGT Core is active\n";
    bkgt_log('info', 'Diagnostic test');
} else {
    echo "✗ BKGT Core is NOT active\n";
}
```

### Check Log File
```bash
# Via SSH/terminal
tail -100 wp-content/bkgt-logs.log
# See last 100 log lines
```

### Check Database Tables
```sql
-- In phpMyAdmin or MySQL client
SHOW TABLES LIKE 'wp_bkgt_%';
-- Should show all BKGT tables
```

---

## Common Issues & Solutions

### Issue 1: BKGT Core Plugin Not Activating

**Symptoms:**
- BKGT Core shows as inactive
- Activation button reloads page but doesn't activate
- Error message: "Plugin cannot be activated"

**Possible Causes:**
1. Syntax error in BKGT_Core plugin
2. Missing required PHP version (need 8.0+)
3. Memory limit too low
4. Other plugin conflict

**Solutions:**

**Step 1: Check PHP Version**
```php
// Check via code snippet
echo "PHP Version: " . PHP_VERSION . "\n";
// If < 8.0, need to upgrade server
```

**Step 2: Check Memory Limit**
```php
// In wp-config.php, ensure:
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

**Step 3: Check for Syntax Errors**
```bash
# Via SSH, test PHP syntax
php -l wp-content/plugins/bkgt-core/bkgt-core.php
# Should say "No syntax errors detected"
```

**Step 4: Check WordPress Debug Log**
```php
// In wp-config.php, enable:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Then check wp-content/debug.log for errors
```

**Step 5: Increase Memory Limit**
```php
// In wp-config.php, before require_once
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '1024M');
```

---

### Issue 2: Helper Functions Not Available

**Symptoms:**
- "Call to undefined function bkgt_log()"
- "Call to undefined function bkgt_validate()"
- "Call to undefined function bkgt_can()"

**Possible Causes:**
1. BKGT Core plugin not activated
2. Incorrect plugin loading order
3. Functions not defined in proper file

**Solutions:**

**Step 1: Verify BKGT Core is Active**
```php
// Check if function exists
if (function_exists('bkgt_log')) {
    echo "Functions are available\n";
} else {
    echo "Functions NOT available - check if BKGT Core is active\n";
}
```

**Step 2: Check Plugin Load Order**
```php
// Go to Plugins page
// Verify BKGT Core is listed first among BKGT plugins
// Dependent plugins should load AFTER BKGT Core
```

**Step 3: Reactivate BKGT Core**
```
1. Deactivate BKGT Core
2. Wait 2 seconds
3. Reactivate BKGT Core
4. Test functions again
```

**Step 4: Check Plugin Dependencies**
```php
// Go to Plugins page
// Check if dependent plugins show warning about missing BKGT Core
// If yes, manually activate BKGT Core first
```

---

### Issue 3: AJAX Requests Failing

**Symptoms:**
- AJAX request returns error
- Console shows 400 or 403 error
- "Nonce verification failed" or similar

**Possible Causes:**
1. Nonce invalid or expired
2. User doesn't have required permission
3. AJAX handler not registered

**Solutions:**

**Step 1: Check Nonce Validity**
```javascript
// In browser console, check nonce value
console.log('Current nonce:', _wpnonce); // Common nonce variable
```

**Step 2: Verify Permission**
```php
// In WordPress, test permission
if (bkgt_can('required_capability')) {
    echo "User has permission\n";
} else {
    echo "User lacks permission\n";
}
```

**Step 3: Check AJAX Handler Registration**
```php
// In plugin init, verify AJAX action is registered
// Go to plugin file and search for 'wp_ajax_'
// Should have: add_action('wp_ajax_action_name', ...)
```

**Step 4: Test AJAX Manually**
```javascript
// In browser console
fetch('/wp-admin/admin-ajax.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=test_action&nonce=' + document.querySelector('[name="_wpnonce"]').value
}).then(r => r.json()).then(d => console.log(d));
```

**Step 5: Check Plugin Activation**
```
1. Go to Plugins page
2. Verify plugin containing AJAX handler is active
3. If not active, activate it
```

---

### Issue 4: Permissions Not Working

**Symptoms:**
- User should have access but gets denied
- Permission check always returns false
- Users can't perform authorized actions

**Possible Causes:**
1. Role not assigned to user
2. Capability not in role definition
3. BKGT_Permission not initialized

**Solutions:**

**Step 1: Check User Role**
```php
// In WordPress, check user's role
$user = get_user_by('ID', $user_id);
echo "User role: " . $user->roles[0] . "\n";
// Should show one of: Admin, Coach, Team Manager
```

**Step 2: Check Capability in Role**
```php
// Verify capability is assigned to role
$user_role = $user->roles[0];
$has_cap = user_can($user_id, 'required_capability');
echo "Has capability: " . ($has_cap ? 'yes' : 'no') . "\n";
```

**Step 3: Verify bkgt_can() Function**
```php
// Test bkgt_can() function
$can_access = bkgt_can('view_documents');
echo "bkgt_can result: " . ($can_access ? 'yes' : 'no') . "\n";
```

**Step 4: Check BKGT Permission Class**
```php
// Verify BKGT_Permission class is loaded
if (class_exists('BKGT_Permission')) {
    echo "BKGT_Permission class found\n";
} else {
    echo "BKGT_Permission class NOT found\n";
}
```

**Step 5: Re-assign User Role**
```php
// In WordPress admin:
1. Go to Users → Edit User
2. Under "Role", select correct role
3. Save changes
4. Log out and log back in
5. Test access again
```

---

### Issue 5: Logs Not Being Written

**Symptoms:**
- wp-content/bkgt-logs.log doesn't exist or empty
- No database entries in wp_bkgt_logs table
- bkgt_log() calls don't produce output

**Possible Causes:**
1. Log directory not writable
2. File permissions wrong
3. BKGT_Logger not initialized

**Solutions:**

**Step 1: Check Directory Permissions**
```bash
# Via SSH
ls -la wp-content/ | grep bkgt
# Should show writable directory (drwxr-xr-x or similar)

# If not, fix permissions:
chmod 755 wp-content/bkgt-logs/
```

**Step 2: Check File Permissions**
```bash
# Check log file permissions
ls -la wp-content/bkgt-logs.log
# Should be -rw-r--r-- or similar

# If not, fix:
chmod 644 wp-content/bkgt-logs.log
```

**Step 3: Create Log Directory**
```bash
# If bkgt-logs directory doesn't exist
mkdir -p wp-content/bkgt-logs
chmod 755 wp-content/bkgt-logs
```

**Step 4: Test Logging**
```php
// In WordPress code snippet
bkgt_log('info', 'Test log entry', array('test' => true));

// Check if it was written:
// Via SSH: tail -1 wp-content/bkgt-logs.log
// Should show the test entry
```

**Step 5: Check Database Logging**
```sql
-- In phpMyAdmin/MySQL
SELECT * FROM wp_bkgt_logs ORDER BY id DESC LIMIT 1;
-- Should show recent log entry
```

---

### Issue 6: Database Queries Failing

**Symptoms:**
- Database operations return errors
- "Error: [SQL error message]"
- Posts not saving/retrieving

**Possible Causes:**
1. Database tables not created
2. Connection error
3. SQL syntax error

**Solutions:**

**Step 1: Verify Tables Exist**
```sql
-- Check if BKGT tables exist
SHOW TABLES LIKE 'wp_bkgt_%';
-- Should show multiple tables
```

**Step 2: Verify Table Structure**
```sql
-- Check specific table structure
DESCRIBE wp_bkgt_logs;
-- Should show all expected columns
```

**Step 3: Check Database Connection**
```php
// In WordPress code snippet
global $wpdb;
if ($wpdb->check_connection()) {
    echo "Database connection OK\n";
} else {
    echo "Database connection FAILED\n";
}
```

**Step 4: Test Query**
```php
// Try a simple query
global $wpdb;
$result = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}bkgt_logs LIMIT 1"
);
if ($result === false) {
    echo "Query error: " . $wpdb->last_error . "\n";
} else {
    echo "Query successful\n";
}
```

**Step 5: Recreate Tables**
```php
// In WordPress code snippet
// Manually trigger activation to recreate tables
do_action('activate_plugin');

// Or manually:
require_once BKGT_PLUGIN_DIR . 'includes/class-bkgt-database.php';
$db = new BKGT_Database();
$db->create_tables();
```

---

### Issue 7: Performance Slow

**Symptoms:**
- Page loads slowly
- AJAX requests take > 1 second
- High CPU/memory usage

**Possible Causes:**
1. Query caching not working
2. Too many database queries
3. Large log files slowing down
4. Memory limit too low

**Solutions:**

**Step 1: Check Query Count**
```php
// Enable WordPress query debugging
define('SAVEQUERIES', true);

// Then check in template:
echo "Queries: " . count($GLOBALS['wpdb']->queries) . "\n";
// Should be < 50 for most pages
```

**Step 2: Optimize Queries**
```php
// Verify query caching is working
// Run same query twice and check query count
// Should only increment by 0-1 (cached query)
```

**Step 3: Check Log File Size**
```bash
# Check log file size
ls -lh wp-content/bkgt-logs.log
# If > 100MB, logs are too large

# Rotate manually:
mv wp-content/bkgt-logs.log wp-content/bkgt-logs.$(date +%Y%m%d).log
touch wp-content/bkgt-logs.log
chmod 644 wp-content/bkgt-logs.log
```

**Step 4: Increase Memory Limit**
```php
// In wp-config.php
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '1024M');
```

**Step 5: Disable Unused Plugins**
```
1. Go to Plugins page
2. Deactivate any unused plugins
3. Keep only BKGT Core + necessary plugins active
```

---

### Issue 8: Plugin Dependency Conflicts

**Symptoms:**
- Dependent plugin won't activate
- "Cannot activate, required plugin not found"
- Dependency check failing

**Possible Causes:**
1. BKGT Core not activated
2. BKGT Core deactivated after dependent plugin activated
3. Plugin header incorrect

**Solutions:**

**Step 1: Activate BKGT Core First**
```
1. Go to Plugins page
2. Find BKGT Core plugin
3. Click "Activate"
4. Wait for completion
5. Then activate dependent plugins
```

**Step 2: Check Plugin Header**
```php
// In plugin file, verify header has:
// Requires Plugins: bkgt-core
```

**Step 3: Reactivate Plugins in Order**
```
1. Deactivate all BKGT plugins
2. Activate BKGT Core first
3. Wait 1 second
4. Activate each dependent plugin
5. Verify no errors
```

**Step 4: Check Activation Hooks**
```php
// Verify BKGT Core registers activation hooks properly
// In wp-content/plugins/bkgt-core/bkgt-core.php
// Should have: register_activation_hook()
```

---

### Issue 9: Security Warnings

**Symptoms:**
- CSRF attack attempt logged
- SQL injection attempt logged
- XSS attack attempt logged
- Unauthorized access logged

**Possible Causes:**
1. Legitimate user with wrong nonce
2. Session expired
3. Actual security attack

**Solutions:**

**Step 1: Review Log Entry**
```sql
-- Check the security log entry
SELECT * FROM wp_bkgt_logs 
WHERE level = 'warning' OR level = 'error'
ORDER BY id DESC LIMIT 5;
-- Review the context to understand issue
```

**Step 2: Check User Session**
```php
// If user's session expired:
1. Ask user to refresh browser
2. Log out and log back in
3. Retry operation
```

**Step 3: Verify Nonce in Frontend**
```javascript
// Check if nonce is current in frontend
if (document.querySelector('[name="_wpnonce"]')) {
    console.log('Nonce field found');
} else {
    console.log('Nonce field missing - page needs refresh');
}
```

**Step 4: Analyze Attack Pattern**
```sql
-- If actual attack, check pattern:
SELECT * FROM wp_bkgt_logs 
WHERE level = 'warning' AND message LIKE '%nonce%'
AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY created_at;
```

**Step 5: Take Action**
```php
// If actual attack:
1. Check failed login attempts
2. Consider blocking IP if repeat offender
3. Review access logs for suspicious patterns
4. Notify security team if severe
```

---

### Issue 10: Role Permissions Not Updating

**Symptoms:**
- Changed user role but permissions unchanged
- User still has old permissions
- Permission change took hours to take effect

**Possible Causes:**
1. User session not refreshed
2. Permission cache not cleared
3. Role definition not reloaded

**Solutions:**

**Step 1: Clear User Session**
```php
// Force user to log out and log back in
// In WordPress admin:
1. Users → Edit User
2. Note the username
3. Go to Users main page
4. Find user and note their edit URL
5. Ask user to log out
6. Ask user to log back in
```

**Step 2: Clear WordPress Cache**
```php
// In WordPress code snippet
wp_cache_flush();
echo "Cache cleared\n";

// Or via dashboard:
// If using W3 Total Cache or similar, go to Purge All Caches
```

**Step 3: Verify Role Update**
```php
// Check user's current role:
$user = get_user_by('ID', $user_id);
echo "Current role: " . $user->roles[0] . "\n";

// Check specific capability:
echo "Has cap: " . user_can($user_id, 'view_documents') . "\n";
```

**Step 4: Revert and Re-assign Role**
```php
// In WordPress admin:
1. Users → Edit User
2. Change role to "Subscriber" (neutral)
3. Save
4. Go back and change to desired role
5. Save
6. User logs out and back in
```

---

## Diagnostic Script

Save this as `wp-content/bkgt-diagnostics.php` and run via admin:

```php
<?php
/**
 * BKGT System Diagnostics
 * Run this to get a complete system status report
 */

// Only run in admin
if (!defined('ABSPATH') || !is_admin()) {
    die('Access denied');
}

echo "<h2>BKGT System Diagnostics</h2>\n";
echo "<pre>\n";

// 1. Check BKGT Core
echo "=== BKGT Core Plugin ===\n";
if (is_plugin_active('bkgt-core/bkgt-core.php')) {
    echo "✓ BKGT Core is active\n";
} else {
    echo "✗ BKGT Core is NOT active\n";
}

// 2. Check helper functions
echo "\n=== Helper Functions ===\n";
foreach (['bkgt_log', 'bkgt_validate', 'bkgt_can', 'bkgt_db'] as $func) {
    if (function_exists($func)) {
        echo "✓ $func() exists\n";
    } else {
        echo "✗ $func() missing\n";
    }
}

// 3. Check database tables
echo "\n=== Database Tables ===\n";
global $wpdb;
$tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}bkgt_%'");
if (empty($tables)) {
    echo "✗ No BKGT tables found\n";
} else {
    foreach ($tables as $table) {
        echo "✓ $table exists\n";
    }
}

// 4. Check log file
echo "\n=== Log File ===\n";
$log_file = WP_CONTENT_DIR . '/bkgt-logs.log';
if (file_exists($log_file)) {
    echo "✓ Log file exists\n";
    echo "  Size: " . size_format(filesize($log_file)) . "\n";
    echo "  Writable: " . (is_writable($log_file) ? 'yes' : 'no') . "\n";
} else {
    echo "✗ Log file not found\n";
}

// 5. Check log directory
echo "\n=== Log Directory ===\n";
$log_dir = WP_CONTENT_DIR . '/bkgt-logs';
if (is_dir($log_dir)) {
    echo "✓ Log directory exists\n";
    echo "  Writable: " . (is_writable($log_dir) ? 'yes' : 'no') . "\n";
} else {
    echo "✗ Log directory not found\n";
}

// 6. Check PHP version
echo "\n=== PHP Configuration ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "s\n";

// 7. Check active plugins
echo "\n=== Active BKGT Plugins ===\n";
$active = get_option('active_plugins');
foreach ($active as $plugin) {
    if (strpos($plugin, 'bkgt-') !== false) {
        echo "✓ " . $plugin . "\n";
    }
}

// 8. Recent logs
echo "\n=== Recent Logs (Last 5) ===\n";
$recent = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}bkgt_logs 
    ORDER BY id DESC LIMIT 5
");
if (empty($recent)) {
    echo "No logs found\n";
} else {
    foreach ($recent as $log) {
        echo "[$log->level] $log->message\n";
    }
}

echo "</pre>\n";
echo "<p><a href='" . admin_url('plugins.php') . "'>Back to Plugins</a></p>\n";
```

To run: `www.yoursite.com/wp-admin/admin.php?page=bkgt-diagnostics`

---

## Contact Support

If issues persist after trying solutions above:

1. **Gather Diagnostics**
   - Run diagnostic script above
   - Collect last 50 log lines from bkgt-logs.log
   - Note WordPress version and PHP version
   - Note exact error messages

2. **Prepare Support Ticket**
   - Issue title
   - Steps to reproduce
   - Expected behavior
   - Actual behavior
   - Diagnostic output
   - Relevant log entries

3. **Provide Details**
   - Which plugin is affected
   - When did issue start
   - What changed before issue
   - Number of affected users

---

## Prevention Tips

To avoid issues:

1. **Regular Backups**
   - Daily database backups
   - Tested restoration procedure
   - Offsite backup storage

2. **Monitoring**
   - Daily log review
   - Error rate tracking
   - Performance monitoring
   - Security event monitoring

3. **Maintenance**
   - Monthly log cleanup
   - Quarterly database optimization
   - Yearly security audit
   - Regular WordPress/PHP updates

4. **Testing**
   - Test updates in staging first
   - Full regression testing
   - Performance baseline comparison
   - Security scanning

---

**Need Help?** Review relevant sections above or contact development team.
