# BKGT Core - Quick Reference

Fast lookup guide for the 4 core systems in BKGT framework.

## Helper Functions

```php
// Logging
bkgt_log( 'info', 'message', array() );

// Validation & Sanitization
bkgt_validate( 'required', $value );
bkgt_validate( 'sanitize_text', $value );

// Permissions
bkgt_can( 'view_inventory' );
bkgt_can( 'access_team', $team_id );

// Database
$items = bkgt_db()->get_posts( array( 'post_type' => 'inventory_item' ) );
$id = bkgt_db()->create_post( 'inventory_item', array() );
```

## 1. Logger - Severity Levels

| Level | Usage | Email Alert |
|-------|-------|-------------|
| `debug` | Dev details only | No |
| `info` | General flow | No |
| `warning` | Review needed | No |
| `error` | Attention needed | No |
| `critical` | Immediate action | **YES** |

```php
bkgt_log( 'critical', 'Payment system down', array( 'email_alert' => true ) );
```

## 2. Validator - Key Methods

```php
// Validation (returns true or error message)
bkgt_validate( 'required', $value );
bkgt_validate( 'email', $email );
bkgt_validate( 'phone', $phone );
bkgt_validate( 'numeric', $number );
bkgt_validate( 'min_length', $text, 3 );
bkgt_validate( 'max_length', $text, 50 );

// Sanitization (returns cleaned value)
bkgt_validate( 'sanitize_text', $text );
bkgt_validate( 'sanitize_email', $email );
bkgt_validate( 'sanitize_html', $html );
bkgt_validate( 'sanitize_db', $search_term );

// Escaping (for output)
echo bkgt_validate( 'escape_html', $text );
echo '<input value="' . bkgt_validate( 'escape_attr', $attr ) . '">';
```

## 3. Permission - Common Checks

```php
// Access checks
bkgt_can( 'view_inventory' );              // Can view
bkgt_can( 'edit_inventory' );              // Can edit
bkgt_can( 'view_documents' );              // Can view docs
bkgt_can( 'upload_documents' );            // Can upload

// Team access (with ID)
bkgt_can( 'access_team', $team_id );       // Can access team
bkgt_can( 'manage_team', $team_id );       // Can manage team

// Admin
bkgt_can( 'manage_settings' );             // Is admin
bkgt_can( 'view_logs' );                   // Can view logs

// Require permission (die if denied)
BKGT_Permission::require_capability( 'edit_inventory' );
BKGT_Permission::require_admin();
BKGT_Permission::require_team_access( $team_id );
```

## 4. Database - Common Operations

```php
$db = bkgt_db();

// READ
$items = $db->get_posts( array( 'post_type' => 'inventory_item' ) );
$item = $db->get_post( $id );
$value = $db->get_post_meta( $id, 'quantity' );

// CREATE
$id = $db->create_post( 'inventory_item', array(
    'post_title' => 'Name',
    'meta_input' => array( 'quantity' => 10 ),
) );

// UPDATE
$db->update_post( $id, array( 'post_title' => 'New Name' ) );
$db->update_post_meta( $id, 'quantity', 20 );

// DELETE
$db->delete_post( $id );
$db->delete_post_meta( $id, 'quantity' );

// QUERY
$results = $db->query( $sql );
$row = $db->query_row( $sql );
$value = $db->query_var( $sql );

// CACHE
$db->clear_cache();
$stats = $db->get_cache_stats();
```

## Common Patterns

### Form with Validation

```php
// Check nonce
if ( ! bkgt_validate( 'verify_nonce', $_REQUEST['_wpnonce'], 'my_action' ) ) {
    bkgt_log( 'warning', 'Nonce verification failed' );
    return false;
}

// Check permission
if ( ! bkgt_can( 'edit_inventory' ) ) {
    bkgt_log( 'warning', 'Unauthorized access' );
    return false;
}

// Validate input
$title = bkgt_validate( 'sanitize_text', $_POST['title'] );
if ( true !== bkgt_validate( 'required', $title ) ) {
    bkgt_log( 'warning', 'Title is required' );
    return false;
}

// Create record
$id = bkgt_db()->create_post( 'inventory_item', array(
    'post_title' => $title,
) );

if ( $id ) {
    bkgt_log( 'info', 'Item created', array( 'post_id' => $id ) );
    return $id;
} else {
    bkgt_log( 'error', 'Failed to create item' );
    return false;
}
```

### AJAX Endpoint

```php
add_action( 'wp_ajax_get_inventory_items', 'bkgt_ajax_get_items' );
function bkgt_ajax_get_items() {
    // Check security
    BKGT_Permission::require_capability( 'view_inventory' );
    
    // Get data
    $items = bkgt_db()->get_posts( array( 'post_type' => 'inventory_item' ) );
    
    // Log action
    bkgt_log( 'info', 'Inventory items retrieved via AJAX', array(
        'count' => count( $items ),
    ) );
    
    // Return JSON
    wp_send_json_success( $items );
}
```

### With Error Handling

```php
try {
    // Get items
    $items = bkgt_db()->get_posts( array( 'post_type' => 'inventory_item' ) );
    
    if ( ! $items ) {
        bkgt_log( 'warning', 'No inventory items found' );
        return array();
    }
    
    return $items;
    
} catch ( Exception $e ) {
    bkgt_log( 'error', 'Failed to retrieve items', array(
        'error'      => $e->getMessage(),
        'error_code' => $e->getCode(),
    ) );
    return false;
}
```

## User Roles

| Role | Swedish | Capabilities |
|------|---------|--------------|
| Admin | Styrelsemedlem | Everything |
| Coach | Tränare | Teams, performance data |
| Team Manager | Lagledare | Own team only (no performance) |

## Directory Structure

```
wp-content/plugins/bkgt-core/
├── bkgt-core.php              # Main plugin file
├── includes/
│   ├── class-logger.php       # BKGT_Logger
│   ├── class-validator.php    # BKGT_Validator
│   ├── class-permission.php   # BKGT_Permission
│   └── class-database.php     # BKGT_Database
├── admin/
│   └── class-admin.php        # Admin dashboard
├── languages/
│   └── bkgt.pot               # Translation strings
├── INTEGRATION_GUIDE.md       # Full integration guide
└── README.md                  # Plugin documentation
```

## Files

- **Integration Guide**: `wp-content/plugins/bkgt-core/INTEGRATION_GUIDE.md`
- **Log File**: `wp-content/bkgt-logs.log`
- **Admin Logs**: Dashboard → BKGT Settings → Logs
- **Audit Report**: Root → `IMPLEMENTATION_AUDIT.md`
- **Improvement Plan**: Root → `PRIORITIES.md`

## Common Errors

| Error | Solution |
|-------|----------|
| "Undefined function bkgt_log()" | BKGT Core plugin not active |
| "Permission denied" | User missing required role/capability |
| "Database error" | Check `wp-content/bkgt-logs.log` for details |
| "Validation failed" | Check validation rule name and parameters |

## Debug Tips

```php
// Print current user's capabilities
bkgt_log( 'debug', 'User caps', array(
    'user_id' => get_current_user_id(),
    'caps' => get_user_meta( get_current_user_id(), 'wp_capabilities', true ),
) );

// Check cache stats
$stats = bkgt_db()->get_cache_stats();
bkgt_log( 'debug', 'Cache stats', $stats );

// See all logs
bkgt_log( 'debug', 'View logs at: Dashboard → BKGT Settings → Logs' );
```

## Next Steps

1. **Read INTEGRATION_GUIDE.md** for detailed documentation
2. **Update existing plugins** to use new systems
3. **Test with all user roles** to verify permissions work
4. **Check logs** if anything doesn't work
5. **Standardize plugin structure** to match BKGT_Core pattern

