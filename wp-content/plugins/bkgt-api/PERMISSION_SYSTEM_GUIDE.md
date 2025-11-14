# Permission System Implementation Guide

## Overview

The BKGT Permission Matrix System has been successfully implemented with the following components:

### 1. Database Layer

Created 5 new database tables:

#### `wp_bkgt_role_permissions`
Stores role-based permissions with default settings.

```sql
CREATE TABLE wp_bkgt_role_permissions (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    role_slug varchar(64) NOT NULL,        -- 'coach', 'team_manager', 'admin'
    resource varchar(128) NOT NULL,         -- 'inventory', 'teams', 'players', etc.
    permission varchar(64) NOT NULL,        -- 'view', 'create', 'edit', 'delete'
    granted tinyint(1) NOT NULL DEFAULT 1,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_role_perm (role_slug, resource, permission)
);
```

#### `wp_bkgt_user_permissions`
Stores user-specific permission overrides with optional expiry.

```sql
CREATE TABLE wp_bkgt_user_permissions (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    resource varchar(128) NOT NULL,
    permission varchar(64) NOT NULL,
    granted tinyint(1) NOT NULL DEFAULT 1,
    expires_at datetime NULL,               -- Optional: temporary permissions
    reason text,                            -- Why override was granted
    granted_by bigint(20),                  -- Admin who granted it
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_perm (user_id, resource, permission),
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE
);
```

#### `wp_bkgt_permission_resources`
Defines available resources in the system.

```sql
CREATE TABLE wp_bkgt_permission_resources (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    resource_slug varchar(128) NOT NULL,
    display_name varchar(255),
    description text,
    category varchar(64),                   -- 'inventory', 'teams', 'documents', etc.
    required_for_frontend tinyint(1),      -- Show in UI
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY resource_slug (resource_slug)
);
```

#### `wp_bkgt_permissions`
Defines available permission actions.

```sql
CREATE TABLE wp_bkgt_permissions (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    permission_slug varchar(64) NOT NULL,  -- 'view', 'create', 'edit', 'delete'
    display_name varchar(255),
    description text,
    category varchar(64),                   -- 'read', 'write', 'delete', 'admin'
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY permission_slug (permission_slug)
);
```

#### `wp_bkgt_permission_audit_log`
Records all permission changes for compliance.

```sql
CREATE TABLE wp_bkgt_permission_audit_log (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    action varchar(64) NOT NULL,            -- 'role_permission', 'user_override'
    user_id bigint(20) NULL,                -- For user overrides
    resource varchar(128),
    permission varchar(64),
    granted tinyint(1),
    reason text,
    changed_by bigint(20),                  -- Admin who made change
    changed_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_changed_at (changed_at)
);
```

### 2. Core Classes

#### `class-bkgt-permissions.php`
Main permission checking logic with caching and audit trails.

**Key Methods:**
- `has_permission($user_id, $resource, $permission)` - Check if user has permission
- `get_user_permissions($user_id)` - Get all permissions for a user
- `grant_user_override()` - Grant temporary permission override
- `revoke_user_override()` - Revoke override
- `update_role_permission()` - Update default role permissions
- `get_audit_log()` - Retrieve audit trail
- `clear_cache()` - Clear permission cache

**Permission Check Priority:**
1. Admin users always have access
2. User-specific overrides (if not expired)
3. Role-based permissions
4. Default deny (secure by default)

#### `class-bkgt-permissions-database.php`
Database initialization and migration.

**Key Methods:**
- `create_tables()` - Initialize all permission tables
- `initialize_permission_definitions()` - Set up standard permissions
- `initialize_resource_definitions()` - Set up available resources
- `initialize_role_permissions()` - Set up default role permissions

### 3. REST API Endpoints

#### `class-bkgt-permissions-endpoints.php`
REST endpoints for permission management.

**User Endpoints (Public):**

```
GET /wp-json/bkgt/v1/user/permissions
Response: {
    "user_id": 123,
    "permissions": {
        "inventory": {"view": false, "create": false, "edit": false, "delete": false},
        "teams": {"view": true, "create": false, ...},
        ...
    }
}

POST /wp-json/bkgt/v1/user/check-permission
Body: {"resource": "inventory", "permission": "view"}
Response: {"has_permission": false}
```

**Admin Endpoints (Requires manage_options):**

```
GET /wp-json/bkgt/v1/admin/permissions/roles
Response: {
    "coach": {...},
    "team_manager": {...}
}

PUT /wp-json/bkgt/v1/admin/permissions/roles/{role}/{resource}/{permission}
Body: {"granted": true}

GET /wp-json/bkgt/v1/admin/permissions/users/{user_id}
Response: [{"resource": "inventory", "permission": "edit", "granted": true, ...}]

POST /wp-json/bkgt/v1/admin/permissions/users/{user_id}
Body: {
    "resource": "inventory",
    "permission": "edit",
    "granted": true,
    "expires_at": "2025-12-31 23:59:59",
    "reason": "Temporary inventory manager"
}

DELETE /wp-json/bkgt/v1/admin/permissions/users/{user_id}/{resource}/{permission}

GET /wp-json/bkgt/v1/admin/permissions/audit-log
Response: [{"action": "...", "resource": "...", "changed_at": "...", ...}]
```

### 4. Default Role Permissions

#### COACH Role
- **Inventory**: No access (view=false)
- **Teams**: View own team only
- **Players**: View, create, edit own team players
- **Documents**: View, create, edit
- **Events**: View, create, edit own team events

#### TEAM_MANAGER Role
- **Inventory**: Full access (view, create, edit, delete)
- **Manufacturers**: Full access
- **Item Types**: Full access
- **Assignments**: Full access
- **Locations**: Full access
- **Teams**: Full access
- **Players**: Full access
- **Documents**: Full access
- **Events**: Full access

#### ADMIN Role
- **All Resources**: All permissions (system enforced)

## Integration with Existing Plugins

### BKGT API Plugin
- Permission classes loaded on plugin activation
- Database tables created automatically
- Permission endpoints registered on REST API init
- Activation hook initializes permission data

### BKGT Inventory Plugin
- Uses permissions from bkgt-api
- API endpoints should be updated to use permission callbacks
- Future: Admin interface for managing permissions

## Frontend Integration

### Getting User Permissions (JavaScript)
```javascript
// Fetch all user permissions
fetch('/wp-json/bkgt/v1/user/permissions')
    .then(r => r.json())
    .then(data => {
        const permissions = data.permissions;
        
        // Show/hide inventory UI
        if (permissions.inventory.view) {
            document.getElementById('inventory-section').style.display = 'block';
        }
    });

// Check specific permission
fetch('/wp-json/bkgt/v1/user/check-permission', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        resource: 'inventory',
        permission: 'create'
    })
})
    .then(r => r.json())
    .then(data => {
        if (data.has_permission) {
            // Show create button
        }
    });
```

### Using Permission Callbacks in REST Routes
```php
register_rest_route('bkgt/v1', '/equipment', array(
    'methods' => 'GET',
    'callback' => 'get_equipment',
    'permission_callback' => BKGT_Permissions::rest_permission_callback('inventory', 'view')
));
```

## Admin Management Interface

### Features to Build
1. **Permission Matrix Table**
   - Rows: Roles
   - Columns: Resources
   - Cells: Toggleable permissions (view, create, edit, delete)
   - Edit in place

2. **User Override Management**
   - Search users
   - View their overrides
   - Add/edit/remove overrides
   - Set expiry dates
   - Add reasons/notes

3. **Audit Log Viewer**
   - Filter by date range
   - Filter by action type
   - Search by user/resource
   - Export to CSV

4. **Permission Templates**
   - Save common permission sets
   - Apply to multiple roles
   - Version control

## Security Considerations

### Default Deny
All permissions default to false/denied. No user can access anything unless:
1. They are an admin, OR
2. They have explicit permission via role or override

### Permission Expiry
User-specific overrides can have expiry dates for temporary access.

### Audit Trail
All permission changes are logged with:
- Who made the change
- What changed
- When it changed
- Why (reason field)

### Caching
Permissions are cached in WordPress transients to minimize database queries.

### Role-Based Hierarchy
- Coaches: Limited access to own team
- Team Managers: Full operational access
- Admins: Full system access

## Migration Path

### Step 1: Activation
When bkgt-api plugin is activated:
1. Permission tables are created
2. Predefined roles and resources are initialized
3. Default role permissions are set

### Step 2: Update Existing Endpoints
Existing API endpoints should be updated to use permission checks:

```php
// Before (accept all authenticated users)
'permission_callback' => array($this, 'validate_token')

// After (check specific permission)
'permission_callback' => BKGT_Permissions::rest_permission_callback('inventory', 'view')
```

### Step 3: Admin Interface
Build admin pages for managing permissions (separate phase).

### Step 4: Frontend Integration
Update frontend app to use permission endpoints and show/hide UI.

## Testing

### Unit Tests
- Permission checks for each role
- User override priority
- Expiry date handling
- Cache invalidation

### Integration Tests
- API endpoints with different user roles
- Permission changes reflected immediately
- Audit log entries correct
- Frontend permission fetching

### Performance Tests
- Permission check response time (<50ms)
- Cache effectiveness
- Bulk permission updates
- Large audit logs

## Monitoring

### Audit Log Queries
```sql
-- Recent permission changes
SELECT * FROM wp_bkgt_permission_audit_log 
ORDER BY changed_at DESC LIMIT 50;

-- Changes by admin
SELECT * FROM wp_bkgt_permission_audit_log 
WHERE changed_by = {user_id} 
ORDER BY changed_at DESC;

-- Expired overrides
SELECT * FROM wp_bkgt_user_permissions 
WHERE expires_at IS NOT NULL 
AND expires_at < NOW()
ORDER BY expires_at DESC;
```

## Troubleshooting

### Permissions Not Working
1. Check if tables exist: `SHOW TABLES LIKE '%bkgt_role%';`
2. Verify role permissions: `SELECT * FROM wp_bkgt_role_permissions;`
3. Check for expired overrides: `SELECT * FROM wp_bkgt_user_permissions WHERE expires_at < NOW();`
4. Clear cache: `BKGT_Permissions::clear_all_cache();`

### Performance Issues
1. Check if permission tables are indexed
2. Verify transient caching is working
3. Monitor slow query log for permission checks
4. Consider adding dedicated permission cache layer

### Lost Permissions After Update
1. Check audit log for what changed
2. Verify role definitions not overwritten
3. Check for expired temporary overrides
4. Restore from audit trail if needed

## Future Enhancements

1. **Resource-Level Permissions**: Control access to specific items (e.g., Team A inventory)
2. **Dynamic Permission Groups**: Create custom permission sets
3. **SAML/LDAP Integration**: Sync permissions from external directory
4. **Webhook Notifications**: Alert admins to permission changes
5. **Permission Analytics**: Track permission usage patterns
6. **Scheduled Permission Expiry**: Auto-remove expired overrides
7. **Approval Workflow**: Require approval for permission changes
