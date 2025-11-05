# 🤝 Contributing to BKGT Ledare

**Purpose:** Guidelines for developers contributing to BKGT Ledare  
**Version:** 1.0  
**Last Updated:** November 4, 2025  

---

## 📋 Table of Contents

1. [Getting Started](#getting-started)
2. [Project Structure](#project-structure)
3. [Development Setup](#development-setup)
4. [Code Standards](#code-standards)
5. [BKGT Core API](#bkgt-core-api-reference)
6. [Adding Features](#adding-features)
7. [Plugin Architecture](#plugin-architecture)
8. [Database Design](#database-design)
9. [Testing](#testing)
10. [Deployment](#deployment)

---

## Getting Started

### Prerequisites
- PHP 7.4+
- WordPress 6.8+
- MariaDB 5.1+
- Git
- SSH access (for production deployment)

### First Time Setup

1. **Clone repository**
   ```bash
   git clone https://github.com/ulvheim/ledare-bkgt.git
   cd ledare-bkgt
   ```

2. **Copy plugins to local WordPress**
   ```bash
   cp -r wp-content/plugins/bkgt-* /path/to/local/wordpress/wp-content/plugins/
   cp -r wp-content/themes/bkgt-ledare /path/to/local/wordpress/wp-content/themes/
   ```

3. **Activate plugins in correct order**
   ```bash
   wp plugin activate bkgt-core
   wp plugin activate bkgt-inventory
   wp plugin activate bkgt-document-management
   wp plugin activate bkgt-team-player
   wp plugin activate bkgt-communication
   wp plugin activate bkgt-offboarding
   wp plugin activate bkgt-data-scraping
   ```

4. **Verify installation**
   ```bash
   wp plugin list --status=active
   # Should show all 8 BKGT plugins as "active"
   ```

---

## Project Structure

```
ledare-bkgt/
├── README.md                           # Project overview (you are here)
├── CONTRIBUTING.md                     # This file
├── PRIORITIES.md                       # Roadmap & status
├── DEPLOYMENT.md                       # Production deployment guide
├── AGENTS.md                           # AI agent instructions
├── SYSTEM_ARCHITECTURE.md              # Detailed architecture
├── DESIGN_SYSTEM.md                    # UI/UX specifications
│
├── wp-content/
│   ├── plugins/
│   │   ├── bkgt-core/                  # Foundation framework
│   │   │   ├── bkgt-core.php           # Main plugin file
│   │   │   ├── includes/
│   │   │   │   ├── class-logger.php    # Logging system
│   │   │   │   ├── class-validator.php # Input validation
│   │   │   │   ├── class-permission.php# Access control
│   │   │   │   └── functions.php       # Helper functions
│   │   │   ├── admin/
│   │   │   │   └── class-admin.php     # Admin dashboard
│   │   │   ├── js/
│   │   │   │   ├── bkgt-modal.js       # Modal component
│   │   │   │   └── bkgt-form.js        # Form framework
│   │   │   └── css/
│   │   │       └── style.css           # Core styles
│   │   │
│   │   ├── bkgt-inventory/             # Equipment management
│   │   │   ├── bkgt-inventory.php
│   │   │   ├── includes/
│   │   │   │   ├── class-database.php  # Table creation
│   │   │   │   ├── class-inventory.php # Item CRUD
│   │   │   │   ├── class-analytics.php # Reports
│   │   │   │   └── ajax-handlers.php   # AJAX endpoints
│   │   │   ├── admin/
│   │   │   │   ├── class-admin.php     # Admin UI
│   │   │   │   └── css/
│   │   │   ├── js/
│   │   │   │   ├── inventory-list.js   # Frontend display
│   │   │   │   └── admin-modal.js      # Admin modal
│   │   │   └── templates/
│   │   │       └── inventory-item.php  # Display template
│   │   │
│   │   ├── bkgt-document-management/   # File management
│   │   │   ├── bkgt-document-management.php
│   │   │   ├── includes/
│   │   │   ├── admin/
│   │   │   └── js/
│   │   │
│   │   ├── bkgt-team-player/           # Roster & events
│   │   ├── bkgt-communication/         # Messaging
│   │   ├── bkgt-offboarding/           # Exit management
│   │   └── bkgt-data-scraping/         # Data retrieval
│   │
│   └── themes/
│       └── bkgt-ledare/                # Main theme
│           ├── functions.php
│           ├── template-*.php
│           ├── assets/
│           │   ├── css/
│           │   ├── js/
│           │   └── images/
│           └── templates/
│
├── deploy.sh                           # Production deployment script
├── deploy.bat                          # Windows deployment wrapper
└── wp-config.php                       # WordPress configuration
```

---

## Development Setup

### Local WordPress Installation

```bash
# Create local database
mysql -u root -p -e "CREATE DATABASE ledare_bkgt_local;"

# Create WordPress installation directory
mkdir ~/wordpress-local
cd ~/wordpress-local

# Download and setup WordPress
wget https://wordpress.org/latest.tar.gz
tar -xzf latest.tar.gz
mv wordpress/* .
rmdir wordpress

# Copy wp-config
cp wp-config-sample.php wp-config.php
# Edit wp-config.php with your database credentials

# Copy BKGT files
cp -r /path/to/ledare-bkgt/wp-content/plugins/bkgt-* wp-content/plugins/
cp -r /path/to/ledare-bkgt/wp-content/themes/bkgt-ledare wp-content/themes/
```

### Using Docker (Recommended)

```bash
# Start WordPress container with required PHP version
docker run -d \
  --name ledare-bkgt-wp \
  -p 8080:80 \
  -e WORDPRESS_DB_HOST=db \
  -e WORDPRESS_DB_USER=wordpress \
  -e WORDPRESS_DB_PASSWORD=wordpress \
  -e WORDPRESS_DB_NAME=wordpress \
  -v $(pwd)/wp-content/plugins:/var/www/html/wp-content/plugins \
  -v $(pwd)/wp-content/themes:/var/www/html/wp-content/themes \
  wordpress:latest

# Access at http://localhost:8080
```

### Setting Up for Development

1. **Enable debug logging**
   ```php
   // In wp-config.php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   define( 'WP_DEBUG_DISPLAY', false );
   ```

2. **Install WordPress CLI**
   ```bash
   curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
   chmod +x wp-cli.phar
   sudo mv wp-cli.phar /usr/local/bin/wp
   ```

3. **Activate plugins in order**
   ```bash
   wp plugin activate bkgt-core --allow-root
   wp plugin activate bkgt-inventory --allow-root
   # ... rest of plugins
   ```

4. **Create admin user if needed**
   ```bash
   wp user create testadmin testadmin@example.com --role=administrator --user_pass=password
   ```

---

## Code Standards

### PHP Code Style

**Follow WordPress Coding Standards:**

```php
<?php
/**
 * Short description of what this file does.
 *
 * Longer description if needed.
 *
 * @package BKGT
 * @subpackage Inventory
 * @since 1.0.0
 */

// Use define() for constants, not const
define( 'BKGT_INVENTORY_VERSION', '1.0.0' );

// Class names: PascalCase
class BKGT_Inventory_Manager {
    /**
     * Initialize the class.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
    }

    /**
     * Register post type.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_post_type() {
        register_post_type( 'bkgt_inventory_item', array(
            'label' => __( 'Inventory Items', 'bkgt-inventory' ),
            'public' => true,
            'supports' => array( 'title', 'editor', 'thumbnail' ),
        ) );
    }
}

// Function names: snake_case
function bkgt_get_inventory_item( $item_id ) {
    return get_post( $item_id );
}

// Variable names: snake_case
$inventory_manager = new BKGT_Inventory_Manager();

// Use single quotes for regular strings
$message = 'This is a string';

// Use double quotes when translating
$message = __( 'Translatable string', 'bkgt-inventory' );

// Indentation: 4 spaces (not tabs)
if ( condition ) {
    // Code here
}

// Line length: max 100 characters recommended
```

### JavaScript Code Style

```javascript
/**
 * Initialize inventory list with event handlers
 *
 * @since 1.0.0
 */
( function( $ ) {
    'use strict';

    const bkgtInventory = {
        // Configuration
        ajaxUrl: bkgt_data.ajax_url,
        nonce: bkgt_data.nonce,

        // Initialize
        init() {
            this.attachEventHandlers();
        },

        // Event handlers
        attachEventHandlers() {
            $( 'body' ).on( 'click', '.bkgt-modal-open', this.openModal );
        },

        // AJAX helper
        makeRequest( action, data = {} ) {
            return $.ajax( {
                url: this.ajaxUrl,
                type: 'POST',
                data: Object.assign( {}, data, {
                    action: action,
                    nonce: this.nonce,
                } ),
            } );
        },
    };

    $( document ).ready( function() {
        bkgtInventory.init();
    } );
} )( jQuery );
```

### File Organization

Each plugin should follow this structure:
```
plugin-name/
├── plugin-name.php              # Main plugin file
├── readme.txt                   # Plugin readme
├── includes/                    # Core classes
│   ├── class-database.php
│   ├── class-core.php
│   └── functions.php            # Helper functions
├── admin/                       # Admin interfaces
│   ├── class-admin.php
│   └── css/
├── public/                      # Frontend
│   ├── class-public.php
│   └── templates/
├── js/                         # JavaScript files
├── css/                        # Stylesheets
└── languages/                  # Translations
```

---

## BKGT Core API Reference

### Logging: `bkgt_log()`

```php
// Record a log entry
bkgt_log( $level, $message, $context = array() );

// Levels: debug, info, warning, error, critical
bkgt_log( 'info', 'Item assigned successfully', array(
    'item_id' => 123,
    'assigned_to' => 'john@example.com',
) );

// Critical issues trigger email alert
bkgt_log( 'critical', 'Database connection failed', array(
    'email_alert' => true,
) );
```

### Validation: `bkgt_validate()`

```php
// Validate required field
if ( ! bkgt_validate( 'required', $user_email ) ) {
    return new WP_Error( 'invalid_email', 'Email is required' );
}

// Validate email format
if ( ! bkgt_validate( 'email', $user_email ) ) {
    return new WP_Error( 'invalid_format', 'Invalid email format' );
}

// Validate numeric value
if ( ! bkgt_validate( 'numeric', $quantity ) ) {
    return new WP_Error( 'not_numeric', 'Must be a number' );
}

// Sanitize text input
$safe_text = bkgt_validate( 'sanitize_text', $_POST['item_name'] );

// Available validators:
// - required, email, phone, numeric, url
// - min_length, max_length, min_value, max_value
// - sanitize_text, sanitize_email, sanitize_url, escape_html
```

### Permissions: `bkgt_can()`

```php
// Check if user can perform action
if ( ! bkgt_can( 'view_inventory' ) ) {
    wp_die( 'Access denied' );
}

// Check with resource ID
if ( ! bkgt_can( 'edit_team', $team_id ) ) {
    wp_die( 'Access denied' );
}

// Get current user permissions
$permissions = bkgt_can( 'list_all' );
// Returns array of user's capabilities

// Common capabilities:
// - view_inventory, edit_inventory, delete_inventory
// - manage_documents, download_documents
// - view_team, edit_team
// - view_events, create_events
// - admin_dashboard
```

### Database: `bkgt_db()`

```php
// Get posts
$items = bkgt_db()->get_posts( array(
    'post_type' => 'bkgt_inventory_item',
    'posts_per_page' => 20,
    'meta_query' => array(
        array(
            'key' => '_bkgt_item_type_id',
            'value' => $type_id,
            'compare' => '=',
        ),
    ),
) );

// Create post
$item_id = bkgt_db()->create_post( 'bkgt_inventory_item', array(
    'post_title' => 'Item Name',
    'post_content' => 'Description',
    'meta' => array(
        '_bkgt_item_type_id' => 5,
        '_bkgt_manufacturer_id' => 3,
    ),
) );

// Update post
bkgt_db()->update_post( $item_id, array(
    'post_title' => 'New Name',
    'meta' => array(
        '_bkgt_item_type_id' => 6,
    ),
) );

// Delete post
bkgt_db()->delete_post( $item_id );

// Custom SQL query (use prepared statements!)
$results = bkgt_db()->query( 'SELECT * FROM ' . $wpdb->posts . 
    ' WHERE post_type = %s AND post_status = %s',
    array( 'bkgt_inventory_item', 'publish' )
);
```

---

## Adding Features

### Example: Add New Equipment Field

**Step 1: Extend Database**

```php
// File: bkgt-inventory/includes/class-database.php
// Add to item creation:
$meta = array(
    '_bkgt_serial_number' => isset( $args['serial_number'] ) ? $args['serial_number'] : '',
    '_bkgt_warranty_date' => isset( $args['warranty_date'] ) ? $args['warranty_date'] : '',
);
```

**Step 2: Add Admin UI**

```php
// File: bkgt-inventory/admin/class-admin.php
// Add to equipment form:
?>
<label for="serial_number"><?php _e( 'Serial Number', 'bkgt-inventory' ); ?></label>
<input type="text" id="serial_number" name="serial_number" 
       value="<?php echo esc_attr( $item['_bkgt_serial_number'] ); ?>">

<label for="warranty_date"><?php _e( 'Warranty Date', 'bkgt-inventory' ); ?></label>
<input type="date" id="warranty_date" name="warranty_date" 
       value="<?php echo esc_attr( $item['_bkgt_warranty_date'] ); ?>">
```

**Step 3: Add Frontend Display**

```php
// File: bkgt-inventory/templates/inventory-item.php
// Add to item template:
<?php if ( $item['_bkgt_serial_number'] ) : ?>
    <p><strong><?php _e( 'Serial Number:', 'bkgt-inventory' ); ?></strong> 
       <?php echo esc_html( $item['_bkgt_serial_number'] ); ?></p>
<?php endif; ?>
```

**Step 4: Handle AJAX Save**

```php
// File: bkgt-inventory/includes/ajax-handlers.php
add_action( 'wp_ajax_save_inventory_item', function() {
    // 1. Validate nonce
    if ( ! bkgt_validate( 'nonce', $_POST['nonce'] ) ) {
        wp_die( 'Security check failed' );
    }

    // 2. Check permissions
    if ( ! bkgt_can( 'edit_inventory' ) ) {
        wp_die( 'Access denied' );
    }

    // 3. Validate input
    $serial = bkgt_validate( 'sanitize_text', $_POST['serial_number'] );
    $warranty = bkgt_validate( 'sanitize_text', $_POST['warranty_date'] );

    // 4. Update database
    bkgt_db()->update_post( $_POST['item_id'], array(
        'meta' => array(
            '_bkgt_serial_number' => $serial,
            '_bkgt_warranty_date' => $warranty,
        ),
    ) );

    // 5. Log action
    bkgt_log( 'info', 'Equipment updated', array(
        'item_id' => $_POST['item_id'],
        'user_id' => get_current_user_id(),
    ) );

    // 6. Return response
    wp_send_json_success( array(
        'message' => __( 'Equipment updated successfully', 'bkgt-inventory' ),
    ) );
} );
```

**Step 5: Test Locally**

```bash
# Activate plugin and test in admin
wp plugin activate bkgt-inventory --allow-root

# Check debug log for errors
tail wp-content/debug.log
```

**Step 6: Deploy**

```bash
# Run deployment script
./deploy.sh

# Verify on production
ssh -i ~/.ssh/id_ecdsa_webhost user@server "wp plugin list --status=active"
```

---

## Plugin Architecture

### Lifecycle: Plugin Activation

```php
// File: bkgt-inventory/bkgt-inventory.php
register_activation_hook( __FILE__, function() {
    // 1. Check dependencies
    if ( ! function_exists( 'bkgt_log' ) ) {
        wp_die( 'BKGT Core plugin must be activated first' );
    }

    // 2. Create database tables
    BKGT_Inventory_Database::create_tables();

    // 3. Register post types
    BKGT_Inventory_Admin::register_post_type();

    // 4. Create default data
    BKGT_Inventory_Database::create_sample_data();

    // 5. Log activation
    bkgt_log( 'info', 'Inventory plugin activated' );
} );
```

### Lifecycle: AJAX Request

```
1. User clicks button in browser
   ↓
2. JavaScript AJAX call to /wp-admin/admin-ajax.php
   action=save_inventory_item
   nonce=xyz
   data={...}
   ↓
3. WordPress routes to handler
   do_action( 'wp_ajax_save_inventory_item' )
   ↓
4. Plugin handler executes:
   a. wp_verify_nonce() - Check security token
   b. current_user_can() - Check permissions
   c. Input validation - Sanitize & validate data
   d. Database operation - Save data
   e. bkgt_log() - Log the action
   f. wp_send_json_success() - Return response
   ↓
5. Browser receives JSON response
6. JavaScript updates UI
```

### Creating a New AJAX Handler

```php
// File: bkgt-inventory/includes/ajax-handlers.php

add_action( 'wp_ajax_my_action', function() {
    // REQUIRED: Verify nonce (CSRF protection)
    check_ajax_referer( 'bkgt_nonce', '_wpnonce' );

    // REQUIRED: Check user capability
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Access denied' ) );
    }

    // Step 1: Get parameters
    $item_id = isset( $_POST['item_id'] ) ? intval( $_POST['item_id'] ) : 0;
    
    // Step 2: Validate parameters
    if ( ! $item_id ) {
        wp_send_json_error( array( 'message' => 'Missing item ID' ) );
    }

    // Step 3: Process request
    $item = get_post( $item_id );
    if ( ! $item ) {
        wp_send_json_error( array( 'message' => 'Item not found' ) );
    }

    // Step 4: Return success
    wp_send_json_success( array(
        'item' => $item,
        'message' => 'Success',
    ) );
} );
```

---

## Database Design

### Naming Conventions

- **Table prefix:** `wp_bkgt_` (WordPress prefix + BKGT prefix)
- **Table names:** `wp_bkgt_inventory_items` (plural, snake_case)
- **Post meta keys:** `_bkgt_field_name` (underscore prefix makes private)
- **Custom field names:** `_bkgt_item_type_id`, `_bkgt_manufacturer_id`

### Current Tables

```sql
-- Manufacturers
CREATE TABLE wp_bkgt_manufacturers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    manufacturer_id VARCHAR(50),
    contact_info TEXT
);

-- Item Types
CREATE TABLE wp_bkgt_item_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    item_type_id VARCHAR(50),
    description TEXT,
    custom_fields JSON
);

-- Locations
CREATE TABLE wp_bkgt_locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100),
    parent_id INT,
    location_type VARCHAR(50),
    address TEXT,
    contact_info TEXT,
    UNIQUE KEY (slug)
);

-- Assignments
CREATE TABLE wp_bkgt_inventory_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    assignee_id INT,
    assignee_name VARCHAR(255),
    due_date DATE,
    return_date DATE,
    location_id INT,
    FOREIGN KEY (item_id) REFERENCES wp_posts(ID),
    FOREIGN KEY (assignee_id) REFERENCES wp_users(ID),
    FOREIGN KEY (location_id) REFERENCES wp_bkgt_locations(id),
    INDEX (item_id),
    INDEX (assignee_id)
);

-- History
CREATE TABLE wp_bkgt_inventory_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    action VARCHAR(50),
    user_id INT,
    data JSON,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES wp_posts(ID),
    FOREIGN KEY (user_id) REFERENCES wp_users(ID),
    INDEX (item_id),
    INDEX (timestamp)
);
```

### Adding a New Table

```php
// File: bkgt-inventory/includes/class-database.php
public function create_tables() {
    global $wpdb;
    
    $wpdb->hide_errors();
    
    $sql = "CREATE TABLE " . $wpdb->prefix . "bkgt_my_table (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY (name),
        INDEX (created_at)
    ) DEFAULT CHARSET = " . $wpdb->get_charset_collate();
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
    
    if ( ! empty( $wpdb->last_error ) ) {
        bkgt_log( 'error', 'Failed to create table', array(
            'error' => $wpdb->last_error,
        ) );
    }
}
```

---

## Testing

### Manual Testing Checklist

```
[ ] Plugin activates without errors
[ ] All database tables created successfully
[ ] Admin dashboard loads
[ ] Can create new item
[ ] Can edit existing item
[ ] Can delete item (soft delete to history)
[ ] Item appears on frontend
[ ] Search functionality works
[ ] Filters work correctly
[ ] Pagination works
[ ] No JavaScript errors in console (F12)
[ ] No PHP errors in debug.log
```

### WordPress CLI Testing

```bash
# Check plugin status
wp plugin list --status=active

# Check for errors
grep -i 'error\|fatal' wp-content/debug.log

# Query data
wp post list --post_type=bkgt_inventory_item
wp user list

# Create test data
wp post create --post_type=bkgt_inventory_item --post_title="Test Item" --post_status=publish
```

### Browser DevTools Testing

**Console (F12 → Console):**
```javascript
// Check for JavaScript errors (should be empty or minimal)
console.log('BKGT ready');

// Test AJAX request manually
jQuery.post(
    '/wp-admin/admin-ajax.php',
    {
        action: 'get_inventory_items',
        nonce: bkgt_data.nonce
    },
    function(response) {
        console.log('Success:', response);
    }
);
```

**Network Tab (F12 → Network):**
- Check AJAX requests return 200 (success)
- Check response contains valid JSON
- Check no 403 (permission denied) errors

---

## Deployment

### Before Deploying

1. **Test locally** - Verify all changes work
2. **Check debug log** - No errors or warnings
3. **Test all features** - Inventory, documents, team, events
4. **Review code** - Follow style guidelines
5. **Test AJAX** - Check browser console
6. **Database** - Verify queries work

### Deployment Process

**See [DEPLOYMENT.md](DEPLOYMENT.md) for full details**

```bash
# Test deployment (dry-run)
./deploy.sh --dry-run

# Full deployment
./deploy.sh

# Verify deployment
ssh -i ~/.ssh/id_ecdsa_webhost user@server "wp plugin list --status=active"
```

### After Deploying

1. **Verify site loads** - Check HTTP status
2. **Check error log** - `tail wp-content/debug.log`
3. **Test critical features** - Inventory, documents, team
4. **Monitor for issues** - Check logs regularly
5. **Rollback if needed** - Previous version saved in backup

---

## Questions?

1. Check [README.md](README.md) for project overview
2. Check [SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md) for detailed architecture
3. Check [DEPLOYMENT.md](DEPLOYMENT.md) for deployment
4. Check `wp-content/debug.log` for error details
5. Ask in issue tracker or documentation

---

**Last Updated:** November 4, 2025  
**Status:** ✅ Production Ready
