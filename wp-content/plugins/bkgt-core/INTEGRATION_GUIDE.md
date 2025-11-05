# BKGT Core Integration Guide

This guide explains how to use the BKGT Core systems (Logger, Validator, Permission, Database) in your plugins.

## Overview

The BKGT Core plugin provides 4 centralized service classes that should be used by all other BKGT plugins:

1. **BKGT_Logger** - Unified error handling and logging
2. **BKGT_Validator** - Consistent data validation and sanitization
3. **BKGT_Permission** - Role-based access control
4. **BKGT_Database** - Unified database operations with caching

These are accessed via helper functions defined in the core plugin.

---

## 1. Logging (BKGT_Logger)

### Usage

Use the `bkgt_log()` helper function to log messages:

```php
// Simple log
bkgt_log( 'info', 'User accessed inventory' );

// Log with context
bkgt_log( 'error', 'Database query failed', array(
    'sql'    => 'SELECT * FROM items',
    'result' => $wpdb->last_error,
    'user_id' => get_current_user_id(),
) );

// Different severity levels
bkgt_log( 'debug', 'Debug message' );
bkgt_log( 'info', 'Information message' );
bkgt_log( 'warning', 'Warning message' );
bkgt_log( 'error', 'Error message' );
bkgt_log( 'critical', 'Critical error', array(
    'email_alert' => true,  // Send email to admins
) );
```

### Severity Levels

- **debug** - Detailed technical information (dev use only)
- **info** - General information about application flow
- **warning** - Warning conditions that should be reviewed
- **error** - Error conditions that need attention
- **critical** - Critical errors requiring immediate action (triggers email alert)

### What Gets Logged

Each log entry automatically includes:

- Timestamp
- Severity level
- Message
- User ID who triggered the action
- User IP address
- Current page/action
- Stack trace (automatically captured)
- Any additional context you provide

### Admin Dashboard

Admins can view recent logs in **Dashboard → BKGT Settings → Logs**

### Log File Location

Logs are stored in `wp-content/bkgt-logs.log` for server-side access.

---

## 2. Validation (BKGT_Validator)

### Validation Rules

Use the `bkgt_validate()` helper function to validate data:

```php
// Simple validation
if ( ! bkgt_validate( 'required', $email ) ) {
    bkgt_log( 'warning', 'Email validation failed' );
}

// Using multiple validations
$email = $_POST['email'];
$phone = $_POST['phone'];

// Validation returns true if valid, or error message if invalid
$email_valid = bkgt_validate( 'email', $email );
if ( true !== $email_valid ) {
    echo $email_valid;  // Display error message (e.g., "E-postadress är ogiltig")
}

$phone_valid = bkgt_validate( 'phone', $phone );
if ( true !== $phone_valid ) {
    echo $phone_valid;  // Display error message in Swedish
}
```

### Available Validation Rules

**String Validations:**
- `required( $value )` - Value must not be empty
- `email( $value )` - Value must be valid email
- `url( $value )` - Value must be valid URL
- `phone( $value )` - Value must be valid Swedish phone number
- `min_length( $value, $min )` - String must be at least $min characters
- `max_length( $value, $max )` - String must be no more than $max characters

**Numeric Validations:**
- `numeric( $value )` - Value must be numeric
- `integer( $value )` - Value must be integer
- `min_value( $value, $min )` - Number must be >= $min
- `max_value( $value, $max )` - Number must be <= $max

**Other Validations:**
- `date( $value, $format = 'Y-m-d' )` - Value must be valid date
- `in_array( $value, $allowed_array )` - Value must be in array
- `match( $value, $pattern )` - Value must match regex pattern

### Sanitization Methods

Use sanitization to clean data before processing:

```php
// Sanitize different data types
$email = bkgt_validate( 'sanitize_email', $_POST['email'] );
$text = bkgt_validate( 'sanitize_text', $_POST['name'] );
$url = bkgt_validate( 'sanitize_url', $_POST['website'] );
$html = bkgt_validate( 'sanitize_html', $_POST['description'] );
$db = bkgt_validate( 'sanitize_db', $_POST['search'] );

// For output, use escaping
echo bkgt_validate( 'escape_html', $text );
echo '<input value="' . bkgt_validate( 'escape_attr', $value ) . '">';
```

### Security Checks

```php
// Verify nonce for forms
if ( ! bkgt_validate( 'verify_nonce', $_REQUEST['_wpnonce'], 'my_action' ) ) {
    wp_die( 'Säkerhetskontroll misslyckades' );  // Security check failed
}

// Check user capability
if ( ! bkgt_validate( 'check_capability', 'manage_bkgt_inventory' ) ) {
    wp_die( 'Du har inte behörighet för denna åtgärd' );  // You do not have permission
}
```

### Error Messages

All error messages are in Swedish and can be translated. Example error messages:

- "Fältet är obligatoriskt" - The field is required
- "E-postadress är ogiltig" - Email is invalid
- "Webadress är ogiltig" - URL is invalid
- "Telefonnumret är ogiltigt" - Phone number is invalid

---

## 3. Permissions (BKGT_Permission)

### Role System

There are 3 primary roles in the BKGT system:

1. **Styrelsemedlem** (Admin) - Full access to all features
2. **Tränare** (Coach) - Team-specific access + performance data
3. **Lagledare** (Team Manager) - Limited team access, NO performance data

### Checking Permissions

Use the `bkgt_can()` helper function:

```php
// Check inventory access
if ( bkgt_can( 'view_inventory' ) ) {
    echo 'Can view inventory';
}

if ( bkgt_can( 'edit_inventory' ) ) {
    echo 'Can edit inventory';
}

// Check document access
if ( bkgt_can( 'view_documents' ) ) {
    echo 'Can view documents';
}

if ( bkgt_can( 'upload_documents' ) ) {
    echo 'Can upload documents';
}

// Check performance data access
if ( bkgt_can( 'view_performance_data' ) ) {
    echo 'Can view performance data';
}

// Check team access (with team ID)
if ( bkgt_can( 'access_team', $team_id ) ) {
    echo 'Can access this team';
}

// Check if admin
if ( bkgt_can( 'manage_settings' ) ) {
    echo 'Is admin';
}
```

### Available Permission Checks

**Inventory:**
- `view_inventory()` - Can view inventory items
- `edit_inventory()` - Can add/edit/delete items
- `manage_inventory_categories()` - Can manage categories

**Documents:**
- `view_documents()` - Can view documents
- `upload_documents()` - Can upload documents
- `delete_documents()` - Can delete documents

**Performance Data:**
- `view_performance_data()` - Can view performance data

**Teams & Players:**
- `access_team( $team_id )` - Can access specific team
- `manage_team( $team_id )` - Can manage team settings
- `manage_players( $team_id )` - Can manage team players

**Admin:**
- `manage_settings()` - Can manage system settings
- `view_logs()` - Can view system logs
- `manage_users()` - Can manage user accounts
- `manage_roles()` - Can manage user roles

### Requiring Permissions

For protection in AJAX or admin pages:

```php
// Require permission, die if denied
BKGT_Permission::require_capability( 'edit_inventory' );

// Require admin
BKGT_Permission::require_admin();

// Require team access
BKGT_Permission::require_team_access( $team_id );

// If the user doesn't have permission, execution stops and error is logged
```

### Role Functions

```php
// Check user role
if ( BKGT_Permission::is_admin( $user_id ) ) {
    echo 'User is admin';
}

if ( BKGT_Permission::is_coach( $user_id ) ) {
    echo 'User is coach';
}

if ( BKGT_Permission::is_team_manager( $user_id ) ) {
    echo 'User is team manager';
}

// Get user's teams (returns array of team IDs)
$user_teams = BKGT_Permission::get_user_teams( $user_id );
```

---

## 4. Database Operations (BKGT_Database)

### Accessing the Database Service

Use the `bkgt_db()` helper function:

```php
// Get the database service
$db = bkgt_db();

// All database operations go through this service
```

### Reading Posts

```php
// Get multiple posts with smart caching
$items = bkgt_db()->get_posts( array(
    'post_type'      => 'inventory_item',
    'posts_per_page' => 50,
    'orderby'        => 'title',
    'order'          => 'ASC',
) );

// Get single post
$item = bkgt_db()->get_post( $post_id );

// Automatically logs errors and handles cache invalidation
if ( $item ) {
    echo $item->post_title;
}
```

### Creating Posts

```php
// Create new post
$post_id = bkgt_db()->create_post( 'inventory_item', array(
    'post_title'   => 'New Item',
    'post_content' => 'Item description',
    'post_status'  => 'publish',
    'meta_input'   => array(
        'quantity'   => 10,
        'category'   => 'Equipment',
        'location'   => 'Storage Room',
    ),
) );

if ( $post_id ) {
    bkgt_log( 'info', 'Inventory item created', array(
        'post_id' => $post_id,
    ) );
} else {
    bkgt_log( 'error', 'Failed to create inventory item' );
}
```

### Updating Posts

```php
// Update post
$success = bkgt_db()->update_post( $post_id, array(
    'post_title'   => 'Updated Title',
    'post_content' => 'Updated content',
) );

// Update post metadata
bkgt_db()->update_post_meta( $post_id, 'quantity', 20 );
bkgt_db()->update_post_meta( $post_id, 'location', 'New Location' );

if ( $success ) {
    bkgt_log( 'info', 'Post updated', array( 'post_id' => $post_id ) );
}
```

### Deleting Posts

```php
// Delete post (moves to trash by default)
$success = bkgt_db()->delete_post( $post_id );

if ( $success ) {
    bkgt_log( 'info', 'Post deleted', array( 'post_id' => $post_id ) );
}
```

### Reading Post Metadata

```php
// Get single meta value
$quantity = bkgt_db()->get_post_meta( $post_id, 'quantity' );

// Get all metadata for post
$all_meta = get_post_meta( $post_id );

// Get metadata with default value
$location = bkgt_db()->get_post_meta( $post_id, 'location', 'Unknown' );
```

### Custom SQL Queries

```php
// Get multiple rows
$results = bkgt_db()->query( $sql );
foreach ( $results as $row ) {
    // Process row
}

// Get single row
$row = bkgt_db()->query_row( $sql );

// Get single value
$count = bkgt_db()->query_var( $sql );

// Example with prepared statement (ALWAYS use prepared statements!)
$sql = $wpdb->prepare(
    'SELECT * FROM ' . $wpdb->posts . ' WHERE post_type = %s AND ID = %d',
    'inventory_item',
    $post_id
);
$result = bkgt_db()->query_row( $sql );
```

### Custom Tables

```php
// Insert into custom table
$success = bkgt_db()->insert( 'custom_table', array(
    'column1' => 'value1',
    'column2' => 'value2',
    'timestamp' => current_time( 'mysql' ),
) );

// Update custom table
$success = bkgt_db()->update( 'custom_table', array(
    'column1' => 'new_value',
), array( 'id' => 123 ) );

// Delete from custom table
$success = bkgt_db()->delete( 'custom_table', array(
    'id' => 123,
) );
```

### Query Caching

The database service automatically caches queries for performance:

```php
// Queries are cached automatically
$items = bkgt_db()->get_posts( array( 'post_type' => 'inventory_item' ) );

// On subsequent calls with same parameters, cached result is returned
// Cache expires after 1 hour or when data is modified

// Clear specific cache
bkgt_db()->clear_cache( 'cache_key' );

// Clear all caches
bkgt_db()->clear_cache();

// Get cache statistics (admin only)
$stats = bkgt_db()->get_cache_stats();
// Returns: array(
//     'cached_queries' => 42,
//     'total_size'     => 256000,  // bytes
//     'cache_enabled'  => true,
// )
```

### Error Handling

All database operations automatically log errors:

```php
// If query fails, error is automatically logged
$result = bkgt_db()->query( 'INVALID SQL' );

// Check admin logs: Dashboard → BKGT Settings → Logs
// You'll see entry like:
// [ERROR] Database error: INVALID SQL
// Stack trace and context included

// For custom handling:
if ( null === $result ) {
    bkgt_log( 'error', 'Database query failed', array(
        'operation' => 'custom_query',
    ) );
}
```

---

## Integration Checklist

When updating a plugin to use BKGT Core systems:

### Step 1: Dependencies
- [ ] Plugin includes `wp-content/plugins/bkgt-core/bkgt-core.php` as required plugin
- [ ] Add to plugin header: `Requires Plugins: bkgt-core`

### Step 2: Logging
- [ ] Replace all `error_log()` calls with `bkgt_log()`
- [ ] Replace all `wp_die()` generic errors with `bkgt_log()` + user message
- [ ] Wrap error conditions in try-catch with logging
- [ ] Use severity levels appropriately (debug, info, warning, error, critical)

### Step 3: Validation
- [ ] Use `bkgt_validate()` for all form input
- [ ] Use sanitization methods before database operations
- [ ] Use escaping methods before output
- [ ] Add nonce verification for all forms
- [ ] Check capabilities for all privileged operations

### Step 4: Permissions
- [ ] Replace `current_user_can()` with `bkgt_can()` for BKGT capabilities
- [ ] Add `bkgt_can()` checks to all admin pages and AJAX endpoints
- [ ] Use `BKGT_Permission::require_*()` functions to protect endpoints
- [ ] Test with different user roles (Admin, Coach, Team Manager)

### Step 5: Database
- [ ] Replace all `wp_insert_post()` with `bkgt_db()->create_post()`
- [ ] Replace all `wp_update_post()` with `bkgt_db()->update_post()`
- [ ] Replace all `wp_delete_post()` with `bkgt_db()->delete_post()`
- [ ] Replace all `get_post_meta()` with `bkgt_db()->get_post_meta()`
- [ ] Replace all direct `$wpdb->query()` with `bkgt_db()->query()`
- [ ] Ensure all queries use prepared statements (`$wpdb->prepare()`)

### Step 6: Testing
- [ ] Test with each user role
- [ ] Verify logs are being written
- [ ] Check error messages display correctly
- [ ] Verify no silent failures occur
- [ ] Test with invalid data inputs

---

## Example Plugin Update

Here's a minimal example of updating a plugin to use BKGT Core:

### Before (without BKGT Core)

```php
<?php
// Old way - inconsistent, no logging, hard to maintain
class Inventory_Plugin {
    public function get_items() {
        $items = get_posts( array( 'post_type' => 'inventory_item' ) );
        return $items;
    }
    
    public function create_item() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Access denied' );
        }
        
        $item_id = wp_insert_post( array(
            'post_title' => $_POST['title'],
            'post_type'  => 'inventory_item',
        ) );
        
        if ( ! $item_id ) {
            error_log( 'Failed to create item' );
        }
        
        return $item_id;
    }
}
```

### After (with BKGT Core)

```php
<?php
// New way - consistent, logged, maintainable
class Inventory_Plugin {
    public function get_items() {
        // Automatic caching, error logging, consistent pattern
        $items = bkgt_db()->get_posts( array( 'post_type' => 'inventory_item' ) );
        return $items;
    }
    
    public function create_item() {
        // Centralized permission check
        if ( ! bkgt_can( 'edit_inventory' ) ) {
            bkgt_log( 'warning', 'Unauthorized inventory creation attempt' );
            return false;
        }
        
        // Centralized validation and sanitization
        $title = bkgt_validate( 'sanitize_text', $_POST['title'] );
        if ( true !== bkgt_validate( 'required', $title ) ) {
            bkgt_log( 'warning', 'Invalid item title provided' );
            return false;
        }
        
        // Centralized database operation with automatic error handling
        $item_id = bkgt_db()->create_post( 'inventory_item', array(
            'post_title' => $title,
        ) );
        
        if ( $item_id ) {
            bkgt_log( 'info', 'Inventory item created', array( 'post_id' => $item_id ) );
        } else {
            bkgt_log( 'error', 'Failed to create inventory item', array(
                'title' => $title,
            ) );
        }
        
        return $item_id;
    }
}
```

---

## Best Practices

1. **Always use helper functions** - Use `bkgt_log()`, `bkgt_validate()`, `bkgt_can()`, `bkgt_db()` instead of directly accessing classes
2. **Log everything** - Use appropriate severity levels
3. **Validate all input** - Never trust user input
4. **Check permissions early** - Fail fast if user lacks permission
5. **Use database service** - Consistent error handling and caching
6. **Handle errors gracefully** - Log errors, show user-friendly messages
7. **Test with different roles** - Verify permission system works

---

## Troubleshooting

### Logs not appearing

1. Check if BKGT Core plugin is activated
2. Verify file permissions on `wp-content/` directory (must be writable)
3. Check admin logs in Dashboard → BKGT Settings → Logs
4. Look for errors in server error log

### Database queries slow

1. Check cache statistics: `bkgt_db()->get_cache_stats()`
2. Use `bkgt_log()` with 'debug' level to see query times
3. Verify indexes on heavily queried columns

### Permission checks always fail

1. Verify user has correct role (check user profile)
2. Verify team assignments (if team-based access)
3. Check logs for permission denied messages
4. Test with admin user first

### Validation not working

1. Verify you're using `bkgt_validate()` not just `BKGT_Validator::`
2. Check validation rule name is spelled correctly
3. Verify return value - can be `true` or error message string
4. Check Swedish error messages are displaying

---

## Support

For issues or questions:

1. Check the logs: Dashboard → BKGT Settings → Logs
2. Review this integration guide
3. Look at existing plugin implementations
4. Check IMPLEMENTATION_AUDIT.md for system architecture

