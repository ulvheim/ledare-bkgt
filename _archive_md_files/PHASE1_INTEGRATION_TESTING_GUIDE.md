# BKGT Phase 1 Integration Testing Guide

## Overview
This guide provides step-by-step instructions for validating that all BKGT Core systems and integrated plugins work correctly together.

## Testing Objectives

1. ✅ Verify BKGT Core loads and initializes correctly
2. ✅ Validate all helper functions are available
3. ✅ Test security controls (nonce, permissions, validation)
4. ✅ Verify logging system captures all events
5. ✅ Test permission system with different user roles
6. ✅ Validate performance (logging overhead, caching)
7. ✅ Ensure all plugins activate/deactivate properly
8. ✅ Test database operations work correctly

---

## Part 1: BKGT Core Activation Testing

### Test 1.1: Plugin Activation
**Objective:** Verify BKGT Core plugin activates without errors

**Steps:**
1. In WordPress Admin: Plugins → All Plugins
2. Find "BKGT Core" plugin
3. Click "Activate"
4. Verify no error messages appear
5. Check that plugin shows as "Active"

**Expected Result:** ✅ Plugin activates successfully, no errors

**Verify:** Check wp-content/bkgt-logs.log for activation log entry:
```
INFO: BKGT Core plugin activated
```

### Test 1.2: Helper Functions Available
**Objective:** Verify all 4 helper functions are defined

**Steps:**
1. Go to WordPress Admin → Tools → Code Snippets (or create temporary test)
2. Create a test snippet with:
```php
<?php
// Test that all helper functions exist
if (function_exists('bkgt_log')) {
    echo "✓ bkgt_log() available\n";
} else {
    echo "✗ bkgt_log() NOT available\n";
}

if (function_exists('bkgt_validate')) {
    echo "✓ bkgt_validate() available\n";
} else {
    echo "✗ bkgt_validate() NOT available\n";
}

if (function_exists('bkgt_can')) {
    echo "✓ bkgt_can() available\n";
} else {
    echo "✗ bkgt_can() NOT available\n";
}

if (function_exists('bkgt_db')) {
    echo "✓ bkgt_db() available\n";
} else {
    echo "✗ bkgt_db() NOT available\n";
}
?>
```
3. Run the snippet
4. Verify all 4 functions show ✓

**Expected Result:** ✅ All 4 helper functions available

### Test 1.3: Plugin Dependencies
**Objective:** Verify dependent plugins require BKGT Core

**Steps:**
1. Go to Plugins → All Plugins
2. Deactivate BKGT Core
3. Verify that dependent plugins show a notice about missing dependency
4. Try to activate a dependent plugin - it should fail or show warning
5. Reactivate BKGT Core
6. Verify dependent plugins can be activated again

**Expected Result:** ✅ Dependent plugins properly require BKGT Core

---

## Part 2: Security Testing

### Test 2.1: Nonce Verification
**Objective:** Verify nonce verification blocks CSRF attacks

**Setup:** Use bkgt-inventory as test case

**Steps:**
1. Log in as admin
2. Open browser console (F12)
3. Find an AJAX request to bkgt-inventory (e.g., delete_item_type)
4. Note the nonce value in the request
5. Try to submit the same AJAX request with an invalid nonce:
```javascript
fetch('/wp-admin/admin-ajax.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=bkgt_delete_item_type&bkgt_nonce=invalid_nonce&id=1'
})
```
6. Check browser console for response (should be error)
7. Check wp-content/bkgt-logs.log for security log entry

**Expected Result:** ✅ Invalid nonce rejected, warning logged

**Verify Log Entry:**
```
WARNING: Inventory AJAX nonce verification failed
```

### Test 2.2: Permission Checking
**Objective:** Verify unauthorized users are blocked

**Setup:** Create test user with "Coach/Tränare" role (limited permissions)

**Steps:**
1. Create new user (if not exists): Users → Add New
2. Set role to Coach/Tränare
3. Log out as admin
4. Log in as test Coach user
5. Try to access admin page that requires higher permissions
6. Verify access is denied
7. Check logs for permission denial

**Expected Result:** ✅ Unauthorized access blocked, logged

**Verify Log Entry:**
```
WARNING: [Action] denied - insufficient permissions
```

### Test 2.3: Input Sanitization
**Objective:** Verify malicious input is sanitized

**Steps:**
1. In browser console, submit AJAX request with HTML/JS injection attempt:
```javascript
fetch('/wp-admin/admin-ajax.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=bkgt_action&field=<script>alert("XSS")</script>&nonce=valid_nonce'
})
```
2. Submit the request
3. Check the response - script tags should be removed/escaped
4. Check database record - malicious content should be sanitized

**Expected Result:** ✅ Malicious input sanitized, safe content stored

### Test 2.4: SQL Injection Prevention
**Objective:** Verify SQL injection attempts are prevented

**Steps:**
1. Submit AJAX request with SQL injection attempt in search field:
```javascript
// Example: search with injection
fetch('/wp-admin/admin-ajax.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: "action=bkgt_search&q='; DROP TABLE wp_bkgt_logs; --&nonce=valid"
})
```
2. Verify the request is handled safely (no table dropped)
3. Check logs for suspicious input attempt

**Expected Result:** ✅ SQL injection blocked, request handled safely

---

## Part 3: Permission System Testing

### Test 3.1: Admin Role Access
**Objective:** Verify Admin has all permissions

**Steps:**
1. Log in as Admin
2. Go to a restricted admin page (e.g., Inventory)
3. Verify can view, edit, delete items
4. Try all AJAX actions - all should work
5. Verify actions are logged

**Expected Result:** ✅ Admin can access all functions, all logged

### Test 3.2: Coach Role Access
**Objective:** Verify Coach has limited permissions

**Steps:**
1. Create/use Coach user
2. Log in as Coach
3. Attempt to access admin pages that require "manage_options"
4. Verify access is denied or features are hidden
5. Attempt to use AJAX actions beyond Coach permissions
6. Verify errors returned, access denied is logged

**Expected Result:** ✅ Coach access limited appropriately, denials logged

### Test 3.3: Team Manager Role Access
**Objective:** Verify Team Manager has team-scoped permissions

**Steps:**
1. Create/use Team Manager user
2. Assign to specific team (e.g., Team P2013)
3. Log in as Team Manager
4. Verify can access/edit only assigned team's data
5. Try to access different team's data
6. Verify access denied to other teams

**Expected Result:** ✅ Team Manager limited to assigned teams

### Test 3.4: Permission Capability Verification
**Objective:** Verify specific capabilities are enforced

**Steps:**
1. Test each critical capability:
   - view_documents
   - upload_documents
   - edit_player_data
   - send_messages
2. For each capability:
   - Log in as user with capability
   - Verify action succeeds
   - Log in as user without capability
   - Verify action fails
   - Check logs for permission denial

**Expected Result:** ✅ Each capability properly enforced

---

## Part 4: Logging System Testing

### Test 4.1: Log File Creation
**Objective:** Verify logs are written to file

**Steps:**
1. Perform an action that should be logged (e.g., save player note)
2. SSH/FTP into server
3. Navigate to wp-content/bkgt-logs.log
4. Verify file exists and contains recent log entries
5. Check timestamp is recent

**Expected Result:** ✅ Log file created and updated

**Verify Content:**
```
[2025-11-02 14:30:15] INFO: Player note saved successfully
- user_id: 5
- player_id: 12
- note_type: performance
```

### Test 4.2: Database Logging
**Objective:** Verify logs are stored in database

**Steps:**
1. In WordPress, go to wp-admin
2. Run SQL query to check logs table:
```sql
SELECT * FROM wp_bkgt_logs ORDER BY id DESC LIMIT 10;
```
3. Verify recent entries exist
4. Check all columns are populated (timestamp, level, message, context, etc.)

**Expected Result:** ✅ Logs stored in database with full context

### Test 4.3: Log Rotation
**Objective:** Verify old logs are rotated/cleaned

**Steps:**
1. Check wp-content/bkgt-logs.log file size
2. Perform many operations to generate logs
3. Wait for log rotation (daily at midnight or manual trigger)
4. Verify old log is rotated to bkgt-logs.YYYY-MM-DD.log
5. Verify new log started fresh

**Expected Result:** ✅ Logs properly rotated daily

### Test 4.4: Admin Dashboard Display
**Objective:** Verify logs visible in admin dashboard

**Steps:**
1. Go to WordPress Admin
2. If BKGT dashboard available, navigate to it
3. Look for "Recent Logs" or similar section
4. Verify recent operations are listed
5. Click on log entry to see details
6. Verify context data displays correctly

**Expected Result:** ✅ Logs visible and readable in dashboard

---

## Part 5: Database Operations Testing

### Test 5.1: Post CRUD Operations
**Objective:** Verify database create/read/update/delete work

**Steps:**
1. Create a new post:
```php
$post = bkgt_db()->create_post('bkgt_document', array(
    'post_title' => 'Test Document',
    'post_content' => 'Test content'
));
echo "Created: " . $post . "\n";
```
2. Read the post:
```php
$posts = bkgt_db()->get_posts(array('post_type' => 'bkgt_document', 'include' => array($post)));
echo "Read: " . $posts[0]->post_title . "\n";
```
3. Update the post:
```php
bkgt_db()->update_post($post, array('post_title' => 'Updated Title'));
```
4. Delete the post:
```php
bkgt_db()->delete_post($post);
```

**Expected Result:** ✅ All CRUD operations work, no errors

### Test 5.2: Metadata Operations
**Objective:** Verify metadata save/retrieve works

**Steps:**
1. Save metadata:
```php
$result = bkgt_db()->update_metadata($post_id, '_custom_field', 'custom_value');
```
2. Retrieve metadata:
```php
$value = bkgt_db()->get_metadata($post_id, '_custom_field');
echo "Retrieved: " . $value . "\n";
```
3. Verify serialized data works:
```php
$array = array('key1' => 'value1', 'key2' => 'value2');
bkgt_db()->update_metadata($post_id, '_array_field', $array);
$retrieved = bkgt_db()->get_metadata($post_id, '_array_field');
echo "Array retrieved correctly: " . ($retrieved === $array ? 'yes' : 'no') . "\n";
```

**Expected Result:** ✅ Metadata operations work, arrays serialized/deserialized

### Test 5.3: Query Caching
**Objective:** Verify query results are cached

**Steps:**
1. Enable query logging (set WP_DEBUG to true)
2. Run a query twice:
```php
$posts1 = bkgt_db()->get_posts(array('post_type' => 'bkgt_document'));
$posts2 = bkgt_db()->get_posts(array('post_type' => 'bkgt_document'));
```
3. Check database query log
4. Verify second query used cache (only 1 database query executed)
5. Change data and run again - verify new query executed

**Expected Result:** ✅ Query caching reduces database hits

### Test 5.4: Prepared Statements
**Objective:** Verify all queries use prepared statements

**Steps:**
1. Search code for direct SQL queries
2. Verify all use $wpdb->prepare()
3. Try SQL injection in user input
4. Verify injection is prevented (parameters properly escaped)

**Expected Result:** ✅ All queries use prepared statements, injection prevented

---

## Part 6: Plugin Functionality Testing

### Test 6.1: bkgt-inventory Plugin
**Objective:** Verify inventory AJAX methods work with BKGT Core

**Steps:**
1. Log in as user with inventory permissions
2. Test each AJAX method:
   - Delete manufacturer - verify logged
   - Delete item type - verify logged
   - Generate identifier - verify logged
   - Quick assign - verify logged
3. For each, verify:
   - Success message returned
   - Database updated
   - Operation logged
   - User cannot perform without permission

**Expected Result:** ✅ All inventory AJAX methods secured and logged

### Test 6.2: bkgt-document-management Plugin
**Objective:** Verify document management AJAX methods work

**Steps:**
1. Test upload document:
   - Upload valid file
   - Verify file saved
   - Verify metadata saved
   - Verify logged
2. Test search documents:
   - Search with valid query
   - Verify results returned
   - Verify filters work
   - Verify logged
3. Test load content:
   - Load document content
   - Verify content returned
   - Verify permission check
   - Verify logged

**Expected Result:** ✅ All document management features secured and logged

### Test 6.3: bkgt-team-player Plugin
**Objective:** Verify team & player AJAX methods work

**Steps:**
1. Test save player note:
   - Save note for player
   - Verify database updated
   - Verify logged
2. Test performance rating:
   - Save rating (1-5 scale)
   - Verify validation (reject non-1-5 values)
   - Verify logged
3. Test get stats:
   - Retrieve player statistics
   - Verify correct data returned
   - Verify logged

**Expected Result:** ✅ All team/player features working, validated, and logged

### Test 6.4: bkgt-communication Plugin
**Objective:** Verify communication AJAX methods work

**Steps:**
1. Test send message:
   - Send message to recipient
   - Verify message saved
   - Verify logged
2. Test get notifications:
   - Retrieve notifications for user
   - Verify correct notifications returned
   - Verify logged

**Expected Result:** ✅ All communication features working and logged

---

## Part 7: Performance Testing

### Test 7.1: Logging Overhead
**Objective:** Measure performance impact of logging

**Steps:**
1. Create test script:
```php
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    bkgt_log('info', 'Test log entry ' . $i);
}
$duration = microtime(true) - $start;
echo "100 log operations: " . ($duration * 1000) . " ms\n";
echo "Average per log: " . ($duration * 1000 / 100) . " ms\n";
```
2. Run test
3. Verify average is < 10ms per operation (acceptable overhead)

**Expected Result:** ✅ Logging overhead < 10ms per operation

### Test 7.2: Query Cache Performance
**Objective:** Measure query cache effectiveness

**Steps:**
1. Create test script:
```php
// Without cache (clear first)
wp_cache_flush();
$start = microtime(true);
$posts = bkgt_db()->get_posts(array('post_type' => 'bkgt_document'));
$first_query = microtime(true) - $start;

// With cache (same query)
$start = microtime(true);
$posts = bkgt_db()->get_posts(array('post_type' => 'bkgt_document'));
$cached_query = microtime(true) - $start;

echo "First query: " . ($first_query * 1000) . " ms\n";
echo "Cached query: " . ($cached_query * 1000) . " ms\n";
echo "Speedup: " . round($first_query / $cached_query, 2) . "x\n";
```
2. Run test
3. Verify cached queries are at least 2x faster

**Expected Result:** ✅ Query caching provides 2x+ performance improvement

### Test 7.3: AJAX Response Time
**Objective:** Measure AJAX endpoint response times

**Steps:**
1. Test a simple AJAX endpoint (e.g., get_player_stats)
2. Measure response time with browser dev tools
3. Verify response time < 500ms
4. Run multiple times to check for consistency

**Expected Result:** ✅ AJAX responses < 500ms consistently

### Test 7.4: Database Table Optimization
**Objective:** Verify database is optimized

**Steps:**
1. Run SQL optimization:
```sql
OPTIMIZE TABLE wp_bkgt_logs;
OPTIMIZE TABLE wp_bkgt_performance_ratings;
OPTIMIZE TABLE wp_posts;
OPTIMIZE TABLE wp_postmeta;
```
2. Check for any errors
3. Verify table integrity

**Expected Result:** ✅ Database tables optimized, no errors

---

## Testing Checklist

### Core System Tests
- [ ] Test 1.1: BKGT Core activation
- [ ] Test 1.2: Helper functions available
- [ ] Test 1.3: Plugin dependencies

### Security Tests
- [ ] Test 2.1: Nonce verification
- [ ] Test 2.2: Permission checking
- [ ] Test 2.3: Input sanitization
- [ ] Test 2.4: SQL injection prevention

### Permission Tests
- [ ] Test 3.1: Admin access
- [ ] Test 3.2: Coach access
- [ ] Test 3.3: Team Manager access
- [ ] Test 3.4: Capability verification

### Logging Tests
- [ ] Test 4.1: Log file creation
- [ ] Test 4.2: Database logging
- [ ] Test 4.3: Log rotation
- [ ] Test 4.4: Admin dashboard

### Database Tests
- [ ] Test 5.1: CRUD operations
- [ ] Test 5.2: Metadata operations
- [ ] Test 5.3: Query caching
- [ ] Test 5.4: Prepared statements

### Plugin Tests
- [ ] Test 6.1: bkgt-inventory
- [ ] Test 6.2: bkgt-document-management
- [ ] Test 6.3: bkgt-team-player
- [ ] Test 6.4: bkgt-communication

### Performance Tests
- [ ] Test 7.1: Logging overhead
- [ ] Test 7.2: Query cache performance
- [ ] Test 7.3: AJAX response time
- [ ] Test 7.4: Database optimization

---

## Test Results Summary

After completing all tests, fill in results:

**Date Tested:** ___________
**Environment:** Staging / Production / Local
**WordPress Version:** ___________
**PHP Version:** ___________

### Results
| Test | Status | Notes |
|------|--------|-------|
| 1.1 - BKGT Core activation | ✅ / ⚠️ / ❌ | |
| 1.2 - Helper functions | ✅ / ⚠️ / ❌ | |
| 1.3 - Dependencies | ✅ / ⚠️ / ❌ | |
| 2.1 - Nonce verification | ✅ / ⚠️ / ❌ | |
| 2.2 - Permission checking | ✅ / ⚠️ / ❌ | |
| 2.3 - Input sanitization | ✅ / ⚠️ / ❌ | |
| 2.4 - SQL injection | ✅ / ⚠️ / ❌ | |
| 3.1 - Admin access | ✅ / ⚠️ / ❌ | |
| 3.2 - Coach access | ✅ / ⚠️ / ❌ | |
| 3.3 - Team Manager access | ✅ / ⚠️ / ❌ | |
| 3.4 - Capabilities | ✅ / ⚠️ / ❌ | |
| 4.1 - Log file creation | ✅ / ⚠️ / ❌ | |
| 4.2 - Database logging | ✅ / ⚠️ / ❌ | |
| 4.3 - Log rotation | ✅ / ⚠️ / ❌ | |
| 4.4 - Admin dashboard | ✅ / ⚠️ / ❌ | |
| 5.1 - CRUD operations | ✅ / ⚠️ / ❌ | |
| 5.2 - Metadata operations | ✅ / ⚠️ / ❌ | |
| 5.3 - Query caching | ✅ / ⚠️ / ❌ | |
| 5.4 - Prepared statements | ✅ / ⚠️ / ❌ | |
| 6.1 - Inventory plugin | ✅ / ⚠️ / ❌ | |
| 6.2 - Document management | ✅ / ⚠️ / ❌ | |
| 6.3 - Team & player | ✅ / ⚠️ / ❌ | |
| 6.4 - Communication | ✅ / ⚠️ / ❌ | |
| 7.1 - Logging overhead | ✅ / ⚠️ / ❌ | |
| 7.2 - Query cache | ✅ / ⚠️ / ❌ | |
| 7.3 - AJAX response time | ✅ / ⚠️ / ❌ | |
| 7.4 - Database optimization | ✅ / ⚠️ / ❌ | |

### Overall Status
- **✅ All Tests Passed** - System ready for production
- **⚠️ Some Tests Failed** - Issues need to be addressed
- **❌ Critical Tests Failed** - Deployment blocked

### Issues Found
1. ___________
2. ___________
3. ___________

### Sign-Off
- Tested by: ___________
- Date: ___________
- Approved for deployment: Yes / No

---

## Next Steps

1. ✅ Complete all tests above
2. ✅ Document any issues found
3. ✅ Create bug fix tickets for failures
4. ✅ Retest after fixes
5. ✅ Get approval for deployment
6. ✅ Deploy to production
7. ✅ Monitor logs in production
8. ✅ Begin PHASE 2 frontend work
