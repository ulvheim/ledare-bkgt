# 🤖 AGENTS.md - AI Agent & Automation Guidelines

**Purpose:** Instructions for AI agents, automation scripts, and future developers  
**Version:** 1.0  
**Last Updated:** November 4, 2025  
**Status:** Production Environment Documentation  

---

## 📖 Quick Reference for Agents

This document provides essential information for AI coding agents working on BKGT Ledare codebase.

### For GitHub Copilot & Claude

When asked to work on BKGT Ledare:

1. **Start here:** Read README.md (project overview) then this file
2. **Architecture:** Review SYSTEM_ARCHITECTURE.md for structure
3. **Development:** Follow CONTRIBUTING.md code standards
4. **Deployment:** Use DEPLOYMENT.md for production changes
5. **Debug:** Check `wp-content/debug.log` for errors

---

## 🎯 Core Project Information

### Project
- **Name:** BKGT Ledare
- **URL:** ledare.bkgt.se
- **Type:** WordPress-based admin platform
- **Status:** 75-78% complete, production deployed
- **Language:** Swedish (user-facing), English (code & documentation)

### Tech Stack
- **Platform:** WordPress 6.8+ with custom plugins
- **Language:** PHP 7.4+ (backend), JavaScript (frontend)
- **Database:** MariaDB 5.1+ (Loopia hosting)
- **Hosting:** SSH-accessible server (ledare.bkgt.se)
- **Deployment:** SCP + bash scripts (no Docker in production)

### Repository
- **GitHub:** https://github.com/ulvheim/ledare-bkgt
- **Branch:** main
- **Local Copy:** `c:\Users\Olheim\Desktop\GH\ledare-bkgt\`

---

## 🏗️ Architecture Overview (For Agents)

### Plugin Stack (8 Plugins)

```
BKGT_CORE (Foundation - ACTIVATE FIRST!)
├── Provides: Logger, Validator, Permission, Database helpers
├── Files: bkgt-core/includes/class-*.php
└── Functions: bkgt_log(), bkgt_validate(), bkgt_can(), bkgt_db()

BKGT_INVENTORY (Equipment Management)
├── Files: bkgt-inventory/includes/class-*.php
├── Tables: wp_bkgt_inventory_* (items, assignments, history)
├── Post Type: bkgt_inventory_item (uses post meta for fields)
└── Key Classes: BKGT_Inventory_Database, BKGT_Inventory_Admin, BKGT_Inventory_Analytics

BKGT_DOCUMENT_MANAGEMENT (File Storage)
├── Features: Upload, download, organize documents
├── Post Type: bkgt_document
└── AJAX: save_document, download_document

BKGT_TEAM_PLAYER (Roster & Events)
├── Features: Team roster, player profiles, events
├── Post Types: bkgt_team, bkgt_player, bkgt_event
└── Shortcodes: [bkgt_team_list], [bkgt_events_list]

BKGT_COMMUNICATION (Messaging)
├── Status: Framework complete
└── Features: Team messaging (optional enhancement)

BKGT_OFFBOARDING (Exit Management)
├── Status: 60% (UI done, backend pending)
└── Features: Player exit workflow

BKGT_DATA_SCRAPING (Data Retrieval)
├── Status: Framework complete
└── Features: External data integration

OTHER_PLUGIN (Reserved for future)
```

### Critical Rules for Agents

⚠️ **MUST FOLLOW:**

1. **Activation Order:** Always activate `bkgt-core` FIRST
   ```bash
   wp plugin activate bkgt-core
   wp plugin activate bkgt-inventory
   wp plugin activate bkgt-document-management
   # ... rest of plugins
   ```

2. **Use Helper Functions:** Every plugin uses these
   ```php
   bkgt_log( 'info', 'Action completed', $context );
   bkgt_validate( 'required', $value );
   bkgt_can( 'capability' );
   bkgt_db()->get_posts( ... );
   ```

3. **Security Checks:** REQUIRED in all AJAX handlers
   ```php
   check_ajax_referer( 'bkgt_nonce', '_wpnonce' );
   if ( ! current_user_can( 'manage_options' ) ) {
       wp_die( 'Access denied' );
   }
   ```

4. **Post Meta Fields:** Stored with underscore prefix
   ```php
   // Store as: _bkgt_field_name (underscore = private)
   '_bkgt_item_type_id' => 5,
   '_bkgt_manufacturer_id' => 3,
   ```

5. **File Organization:** Follows strict pattern
   ```
   plugin-name/
   ├── plugin-name.php (main file - registers hooks)
   ├── includes/class-*.php (core classes)
   ├── admin/class-admin.php (admin UI)
   ├── admin/css/ (admin styles)
   ├── js/ (AJAX & frontend scripts)
   └── templates/ (display templates)
   ```

---

## 🔍 Critical File Locations

### For Each Task - Know Where to Look

| Task | Primary Files | Secondary Files |
|------|---------------|-----------------|
| Add inventory feature | `bkgt-inventory/includes/class-inventory.php` | `bkgt-core/includes/functions.php` |
| Add AJAX endpoint | `bkgt-inventory/bkgt-inventory.php` (lines ~1000+) | `bkgt-inventory/includes/ajax-handlers.php` |
| Fix database issue | `bkgt-inventory/includes/class-database.php` | Debug log: `wp-content/debug.log` |
| Add admin UI | `bkgt-inventory/admin/class-admin.php` | `bkgt-inventory/admin/css/style.css` |
| Fix frontend display | `bkgt-inventory/templates/inventory-item.php` | `bkgt-inventory/js/inventory-list.js` |
| Add new role/permission | `bkgt-core/includes/class-permission.php` | `CONTRIBUTING.md` (permissions section) |
| Fix security issue | `bkgt-core/includes/class-validator.php` | `bkgt-core/includes/functions.php` |
| Modify database table | `bkgt-inventory/includes/class-database.php` | Tables created on plugin activation |

### Key Configuration Files

```
wp-config.php                          # WordPress config (DB credentials, debug mode)
wp-content/debug.log                   # Error log (check first for issues!)
wp-content/plugins/bkgt-core/          # Foundation framework
wp-content/themes/bkgt-ledare/         # Theme files
.env                                   # SSH deployment credentials
deploy.sh                              # Production deployment script
```

---

## 🔧 Common Tasks for Agents

### Task 1: Debug Production Error

```bash
# 1. SSH to production
ssh -i ~/.ssh/id_ecdsa_webhost md0600@ssh.loopia.se

# 2. Check error log
tail -f ~/ledare.bkgt.se/public_html/wp-content/debug.log | grep -i error

# 3. Look for patterns
#    - "Fatal error" = PHP syntax error (usually in plugin)
#    - "Cannot redeclare" = Duplicate class/function (plugin conflict)
#    - "Table doesn't exist" = Database schema mismatch
#    - "Access denied" = Permissions issue

# 4. Check which plugin is active
cd ~/ledare.bkgt.se/public_html
wp plugin list --status=active

# 5. Search for error in code
grep -r "error_message_text" wp-content/plugins/bkgt-*/

# 6. Check recent changes
git log --oneline -10
git diff HEAD~1

# 7. Common fixes:
#    - Activate/deactivate problem plugin
#    - Check database tables exist: mysql -e "SHOW TABLES LIKE 'wp_bkgt%';"
#    - Check PHP syntax: php -l filename.php
```

### Task 2: Add New Feature to Inventory

```php
// File: bkgt-inventory/includes/class-inventory.php
// Step 1: Add method to class

public function add_serial_number_field( $item_id, $serial ) {
    // Validate
    if ( ! bkgt_validate( 'required', $serial ) ) {
        return new WP_Error( 'invalid', 'Serial required' );
    }
    
    // Save
    update_post_meta( $item_id, '_bkgt_serial_number', sanitize_text_field( $serial ) );
    
    // Log
    bkgt_log( 'info', 'Serial added', array( 'item_id' => $item_id ) );
    
    return true;
}

// Step 2: Add AJAX handler
// File: bkgt-inventory/bkgt-inventory.php

add_action( 'wp_ajax_add_serial_number', function() {
    check_ajax_referer( 'bkgt_nonce', '_wpnonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die();
    
    $result = BKGT_Inventory_Manager::add_serial_number_field(
        intval( $_POST['item_id'] ),
        sanitize_text_field( $_POST['serial'] )
    );
    
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }
    
    wp_send_json_success();
} );

// Step 3: Test locally
// Terminal: wp post list --post_type=bkgt_inventory_item

// Step 4: Deploy to production
// Terminal: ./deploy.sh
```

### Task 3: Deploy Changes to Production

```bash
# 1. Verify locally first
wp plugin list --status=active  # All 8 BKGT should be active
grep -i error wp-content/debug.log  # Should be clean

# 2. Test deployment
./deploy.sh --dry-run

# 3. Run full deployment
./deploy.sh

# 4. Verify on production
ssh -i ~/.ssh/id_ecdsa_webhost md0600@ssh.loopia.se
cd ~/ledare.bkgt.se/public_html
wp plugin list
grep -i error wp-content/debug.log
```

### Task 4: Fix Database Schema Issue

```php
// File: bkgt-inventory/includes/class-database.php
// In create_tables() method:

global $wpdb;

// IMPORTANT: Use proper table naming
$table_name = $wpdb->prefix . 'bkgt_inventory_assignments';

// CORRECT: Use WordPress dbDelta for compatibility
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

$sql = "CREATE TABLE $table_name (
    id INT NOT NULL AUTO_INCREMENT,
    item_id INT NOT NULL,
    assignee_id INT,
    assignee_name VARCHAR(255),
    due_date DATE,
    return_date DATE,
    location_id INT,
    PRIMARY KEY (id),
    KEY (item_id),
    KEY (assignee_id)
) " . $wpdb->get_charset_collate() . ";";

dbDelta( $sql );

// Log result
if ( ! empty( $wpdb->last_error ) ) {
    bkgt_log( 'error', 'Table creation failed', array(
        'error' => $wpdb->last_error,
        'table' => $table_name,
    ) );
}

// Verify tables
// Terminal: mysql -e "DESCRIBE wp_bkgt_inventory_assignments;"
```

### Task 5: Add New Capability/Role

```php
// File: bkgt-core/includes/class-permission.php

public function __construct() {
    // Define all capabilities
    $this->capabilities = array(
        'view_inventory' => 'Can view inventory items',
        'edit_inventory' => 'Can edit inventory items',
        'delete_inventory' => 'Can delete inventory items',
        'download_documents' => 'Can download documents',  // NEW
        'upload_documents' => 'Can upload documents',      // NEW
        'manage_team' => 'Can manage team roster',
    );
    
    // Assign to roles
    $role_manager = get_role( 'editor' );
    $role_manager->add_cap( 'download_documents' );
    $role_manager->add_cap( 'upload_documents' );
}

// Then in code, check permission:
// if ( ! bkgt_can( 'download_documents' ) ) wp_die( 'Access denied' );
```

---

## 🚨 Error Patterns & Solutions

### Pattern: "Fatal error: Call to undefined function bkgt_log()"

**Cause:** Core plugin not activated first  
**Solution:**
```bash
wp plugin activate bkgt-core --allow-root
wp plugin activate bkgt-inventory --allow-root
# Activate others...
```

### Pattern: "Cannot redeclare class BKGT_Inventory"

**Cause:** Two plugins defining same class, or plugin activated twice  
**Solution:**
```bash
# Check if plugin appears twice
wp plugin list | grep bkgt-inventory

# If duplicate, deactivate all and reactivate in order
wp plugin deactivate bkgt-inventory
wp plugin activate bkgt-core
wp plugin activate bkgt-inventory
```

### Pattern: "Table 'wp_bkgt_inventory_items' doesn't exist"

**Cause:** Database tables not created during activation  
**Solution:**
```bash
# Reactivate plugin to trigger table creation
wp plugin deactivate bkgt-inventory
wp plugin activate bkgt-inventory

# Or manually verify/create in MySQL
mysql -e "SHOW TABLES LIKE 'wp_bkgt%';"

# Check class-database.php create_tables() method
```

### Pattern: "Nonce verification failed"

**Cause:** Security token expired or AJAX call missing nonce  
**Solution:**
```javascript
// In JavaScript, ensure nonce is included:
jQuery.post(
    '/wp-admin/admin-ajax.php',
    {
        action: 'my_action',
        nonce: bkgt_data.nonce,  // REQUIRED
        data: {...}
    },
    function(response) { ... }
);

// On page load, refresh nonce with JavaScript
// Or use wp_localize_script to pass fresh nonce each page load
```

---

## 📊 Production Status (For Agents)

### Current Deployment (November 4, 2025)

**Server:** ledare.bkgt.se (Loopia hosting)  
**Status:** ✅ Production Operational  

**Active Plugins:** 8
- ✅ bkgt-core
- ✅ bkgt-inventory
- ✅ bkgt-document-management
- ✅ bkgt-team-player
- ✅ bkgt-communication
- ✅ bkgt-offboarding
- ✅ bkgt-data-scraping
- ✅ wordfence (security)

**Database Status:** ✅ All tables exist and operational  

**Recent Fixes:**
- ✅ Fixed assignments table name (`bkgt_assignments` → `bkgt_inventory_assignments`)
- ✅ Fixed assignments schema (corrected columns)
- ✅ Fixed 3 analytics post meta queries
- ✅ Removed duplicate table creation code

**Known Limitations:**
- ⚠️ Offboarding system backend automation pending
- ⚠️ Some placeholder content remains

### Access Info (For Authorized Agents Only)

```bash
# SSH access
Host: ssh.loopia.se
User: md0600
Key: ~/.ssh/id_ecdsa_webhost
Path: ~/ledare.bkgt.se/public_html

# WordPress CLI
wp plugin list
wp plugin activate bkgt-core
wp db tables

# Database access (on server)
mysql -u <user> -p <database_name>
SHOW TABLES LIKE 'wp_bkgt%';
```

---

## 📋 Pre-Deployment Checklist (For Agents)

Before deploying ANY changes:

```
[ ] Feature tested locally in WordPress
[ ] wp-content/debug.log is clean (no errors)
[ ] All 8 BKGT plugins active and working
[ ] AJAX requests tested in browser console
[ ] Database queries tested and working
[ ] No JavaScript console errors (F12)
[ ] Code follows WordPress standards (see CONTRIBUTING.md)
[ ] Helper functions used: bkgt_log(), bkgt_validate(), bkgt_can()
[ ] AJAX handlers have nonce verification
[ ] AJAX handlers have capability check
[ ] Input data sanitized with bkgt_validate()
[ ] Changes don't break existing features
[ ] Updated documentation if needed
[ ] Ready for: ./deploy.sh
```

---

## 📚 Key Documentation for Agents

**Read in this order:**

1. **README.md** (5 min) - Project overview, quick start
2. **AGENTS.md** (This file, 10 min) - You are here
3. **SYSTEM_ARCHITECTURE.md** (15 min) - Architecture details
4. **CONTRIBUTING.md** (20 min) - Code standards, adding features
5. **DEPLOYMENT.md** (10 min) - Production deployment
6. **DESIGN_SYSTEM.md** (Optional) - UI/UX specifications

---

## 🆘 Emergency Procedures

### If Production Is Down

```bash
# 1. SSH to server
ssh -i ~/.ssh/id_ecdsa_webhost md0600@ssh.loopia.se

# 2. Check plugin status
cd ~/ledare.bkgt.se/public_html
wp plugin list

# 3. Check for fatal errors
tail -50 wp-content/debug.log | grep -i fatal

# 4. If plugin causing issue, deactivate it
wp plugin deactivate bkgt-inventory  # (or whichever)

# 5. Check database tables
mysql -e "SHOW TABLES LIKE 'wp_bkgt%';" <database_name>

# 6. If tables missing, reactivate plugin
wp plugin activate bkgt-inventory

# 7. Monitor for new errors
tail -f wp-content/debug.log

# 8. If still broken, check git log for recent changes
git log --oneline -5
```

### If Deployment Failed

```bash
# 1. SSH to server
ssh -i ~/.ssh/id_ecdsa_webhost md0600@ssh.loopia.se

# 2. Check file permissions
ls -la ~/ledare.bkgt.se/public_html/wp-content/plugins/bkgt-inventory/

# 3. If permissions wrong, fix them
chmod -R 755 ~/ledare.bkgt.se/public_html/wp-content/plugins/bkgt-*/

# 4. Try reactivating plugins
wp plugin deactivate bkgt-inventory
wp plugin activate bkgt-inventory

# 5. Check debug log
tail wp-content/debug.log
```

---

## 🎓 Learning Resources

### For WordPress Development
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress AJAX Reference](https://developer.wordpress.org/plugins/javascript/ajax/)

### For BKGT
- **Architecture:** SYSTEM_ARCHITECTURE.md
- **Code Style:** CONTRIBUTING.md (Code Standards section)
- **API Reference:** CONTRIBUTING.md (BKGT Core API section)
- **Examples:** Look at existing plugins: `bkgt-inventory/includes/`

### For Production
- **Deployment:** DEPLOYMENT.md
- **Troubleshooting:** README.md (Troubleshooting section)
- **Emergency:** See above (Emergency Procedures)

---

## ✅ Final Checklist for Agents

Before starting work on BKGT Ledare:

```
[ ] Read this document (AGENTS.md) - you understand agent guidelines
[ ] Read README.md - you know the project
[ ] Read SYSTEM_ARCHITECTURE.md - you understand the structure
[ ] You have access to production server (SSH key configured)
[ ] You've checked: wp-content/debug.log for current issues
[ ] You know all 8 plugins must activate in specific order (core first!)
[ ] You use helper functions: bkgt_log(), bkgt_validate(), bkgt_can()
[ ] You understand: Post meta fields use _bkgt_ prefix
[ ] You understand: Security = nonce + capability check in AJAX
[ ] You can test locally before deploying
[ ] You can deploy via: ./deploy.sh
[ ] You can rollback if needed (backup created before each deploy)
```

---

## 📞 Questions?

1. Check documentation: README.md → SYSTEM_ARCHITECTURE.md → CONTRIBUTING.md
2. Check code examples in existing plugins
3. Check debug log for specific errors: `wp-content/debug.log`
4. Check git history for similar changes: `git log -p --grep="search_term"`

---

**Last Updated:** November 4, 2025  
**Status:** ✅ Production Ready  
**Next Update:** When new major features added or processes change
