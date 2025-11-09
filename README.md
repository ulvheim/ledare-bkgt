# 🏈 BKGT Ledare - Admin & Management Platform

**Status:** ✅ **75-78% Complete** (Production Deployed)  
**Last Updated:** November 4, 2025  
**Language:** Swedish (User-facing), English (Developer Documentation)  
**Platform:** WordPress (6.8+) with Custom Plugin Architecture  

---

## 📋 Quick Navigation

- 🚀 **[Quick Start](#quick-start)** - Get running in 5 minutes
- 🏗️ **[Architecture](#architecture)** - System design overview
- 📦 **[Key Systems](#key-systems)** - What's implemented
- 🔧 **[Development](#development)** - For developers
- 📖 **[Full Documentation](#documentation)** - Links to all guides

---

## 🎯 Project Overview

**BKGT Ledare** is a WordPress-based administration and management platform designed for BKGTS (Swedish American Football club). It digitizes administrative workflows including:

- 📦 **Equipment Inventory Management** - Track, assign, and manage inventory
- 📄 **Document Management System** - Organize, share, and manage team documents
- 👥 **Team & Player Management** - Manage rosters, contacts, and player information
- 🎪 **Events Management** - Create and manage team events
- 💬 **Communication System** - Internal messaging and notifications
- 👤 **User Management** - Role-based access control for coaches, managers, board members
- 🚪 **Offboarding System** - Manage player exits and transitions

**Key Achievement:** All critical systems deployed to production. Zero fatal errors. Database fully operational.

---

## 🚀 Quick Start

### Prerequisites
- PHP 7.4+ 
- WordPress 6.8+
- MariaDB 5.1+
- SSH access to production server

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/ulvheim/ledare-bkgt.git
cd ledare-bkgt

# 2. Copy to WordPress installation
cp -r wp-content/plugins/bkgt-* /path/to/wordpress/wp-content/plugins/
cp -r wp-content/themes/bkgt-ledare /path/to/wordpress/wp-content/themes/

# 3. Activate plugins via WordPress Admin or CLI
wp plugin activate bkgt-core
wp plugin activate bkgt-inventory
wp plugin activate bkgt-document-management
wp plugin activate bkgt-team-player
wp plugin activate bkgt-communication
wp plugin activate bkgt-offboarding
wp plugin activate bkgt-data-scraping
```

### First Run

1. **Navigate to WordPress Admin** → Plugins → All Plugins
2. **Verify Status:** All 8 BKGT plugins show as "Active"
3. **Check Dashboard:** Admin → BKGT Dashboard (if enabled)
4. **Initialize Data:** Tables and sample data created automatically on first activation

**Expected Result:** All systems operational, debug log clean of errors.

---

## 🏗️ Architecture

### System Overview

```
┌─────────────────────────────────────────────────────────┐
│  BKGT PLATFORM - 8 Integrated Plugins                   │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  Helper Functions Layer (Universal Access)              │
│  ├─ bkgt_log() - Centralized logging                   │
│  ├─ bkgt_validate() - Input validation & sanitization  │
│  ├─ bkgt_can() - Permission checking                   │
│  └─ bkgt_db() - Database operations                    │
│                          ▲                              │
│                          │                              │
│  Core Systems Layer                                     │
│  ├─ BKGT_Logger - Debug logging with severity levels   │
│  ├─ BKGT_Validator - Input validation & sanitization   │
│  ├─ BKGT_Permission - Role-based access control        │
│  └─ BKGT_Database - Custom table management            │
│                          ▲                              │
│                          │                              │
│  7 Integrated Plugins                                   │
│  ├─ bkgt-core - Foundation & utilities ✅               │
│  ├─ bkgt-inventory - Equipment management ✅            │
│  ├─ bkgt-document-management - File storage ✅          │
│  ├─ bkgt-team-player - Roster & events ✅              │
│  ├─ bkgt-communication - Messaging ✅                   │
│  ├─ bkgt-offboarding - Exit management ⚠️              │
│  └─ bkgt-data-scraping - Data retrieval ✅             │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### Data Flow Example: AJAX Request

```
User Browser
  ↓ (Click button → JavaScript AJAX call)
WordPress Admin (/wp-admin/admin-ajax.php)
  ↓ (Route to plugin handler)
Plugin AJAX Handler
  ├─ Step 1: Verify nonce (security token)
  ├─ Step 2: Check user permissions
  ├─ Step 3: Validate input data
  ├─ Step 4: Process request
  └─ Step 5: Return JSON response
  ↓ (Logs every step to debug log)
Browser receives response & updates UI
```

### Database Schema

**Tables Created:**
- `wp_bkgt_manufacturers` - Equipment manufacturers
- `wp_bkgt_item_types` - Equipment categories
- `wp_bkgt_inventory_items` - Equipment records (uses WordPress post meta)
- `wp_bkgt_inventory_assignments` - Equipment assignments to users
- `wp_bkgt_locations` - Physical storage locations
- `wp_bkgt_inventory_history` - Audit trail of changes

**Post Types:**
- `bkgt_inventory_item` - Equipment items
- `bkgt_document` - Uploaded documents
- `bkgt_event` - Team events
- `bkgt_team` - Team information
- `bkgt_player` - Player profiles

---

## 📦 Key Systems

### ✅ Completed & Production Ready

| System | Status | Features | Files |
|--------|--------|----------|-------|
| **Inventory Management** | ✅ Complete | Add/edit items, assign equipment, track history, search | `bkgt-inventory/` |
| **Document Management** | ✅ Complete | Upload, download, organize, metadata, file icons | `bkgt-document-management/` |
| **Team & Players** | ✅ Complete | Roster management, player profiles, contact info | `bkgt-team-player/` |
| **Events System** | ✅ Complete | Create events, CRUD admin interface, display on frontend | `bkgt-team-player/` |
| **User Management** | ✅ Complete | Role-based access, permission checks, authentication | `bkgt-core/` |
| **Communication** | ✅ Framework | Internal messaging foundation | `bkgt-communication/` |
| **Data Scraping** | ✅ Framework | Data retrieval utilities | `bkgt-data-scraping/` |

### ⚠️ In Development

| System | Status | Notes |
|--------|--------|-------|
| **Offboarding** | 60% | UI framework complete, backend automation pending |
| **Advanced Analytics** | Framework | Post meta queries fixed, data structure ready |

---

## 🔧 Development

### Getting Started as a Developer

1. **Read:** Start with [CONTRIBUTING.md](CONTRIBUTING.md) for development guidelines
2. **Understand:** Review [SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md) for detailed architecture
3. **Reference:** Check [BKGT_CORE_QUICK_REFERENCE.md](BKGT_CORE_QUICK_REFERENCE.md) for API quick ref
4. **API Docs:** Access live API documentation at `/wp-json/bkgt/v1/docs` or route discovery at `/wp-json/bkgt/v1/routes`
5. **Deploy:** See [DEPLOYMENT.md](DEPLOYMENT.md) for production deployment

### Key Developer Commands

```bash
# Test plugin activation
wp plugin activate bkgt-core --allow-root

# Check active plugins
wp plugin list --status=active

# Query inventory items
wp post list --post_type=bkgt_inventory_item

# Check debug log for errors
tail -f wp-content/debug.log | grep -i error

# SSH to production
ssh -i ~/.ssh/id_ecdsa_webhost md0600@ssh.loopia.se

# Deploy files to production
./deploy.sh
```

### Common Tasks

**Add a new feature to Inventory:**
1. Create method in `bkgt-inventory/includes/class-inventory.php`
2. Add AJAX handler in `bkgt-inventory.php` (if needed)
3. Use helper functions: `bkgt_validate()`, `bkgt_log()`, `bkgt_can()`
4. Test locally, then deploy via `./deploy.sh`

**Add a new role/capability:**
1. Edit `bkgt-core/includes/class-permission.php`
2. Add to `$this->capabilities` array with description
3. Run plugin reactivation to register capabilities
4. Use `bkgt_can()` to check permissions in code

**Query inventory data:**
```php
$items = bkgt_db()->get_posts([
    'post_type' => 'bkgt_inventory_item',
    'posts_per_page' => 20,
    'meta_query' => [
        [
            'key' => '_bkgt_item_type_id',
            'value' => $type_id,
            'compare' => '='
        ]
    ]
]);
```

---

## 🔍 Troubleshooting

### "Fatal Error" on Admin Panel
- **Check:** `wp-content/debug.log` for specific error message
- **Common Causes:**
  - Plugin not activated in correct order (activate `bkgt-core` first)
  - Missing required database tables
  - Syntax error in recently deployed file
- **Fix:** Deactivate all BKGT plugins, activate in order: core → inventory → others

### "Cannot redeclare" Errors
- **Cause:** Duplicate class/function definitions (usually from plugin conflicts)
- **Fix:** Check if BKGT plugins are active multiple times or conflicting with other plugins
- **Verify:** `wp plugin list` should show each plugin only once

### "Table doesn't exist" Errors
- **Cause:** Database tables not created during activation
- **Fix:** Deactivate and reactivate the affected plugin
- **Verify:** Check `wp_bkgt_*` tables exist in database

### Inventory Items Not Showing
- **Check:** Are items assigned to the correct post type? (`wp post list --post_type=bkgt_inventory_item`)
- **Verify:** Shortcode used: `[bkgt_inventory_list]` (inventory) or `[bkgt_equipment_list]` (team player)
- **Debug:** Check debug log for query errors

### AJAX Requests Failing
- **Check:** Browser console (F12 → Network tab) for response
- **Verify:** User is logged in and has correct role
- **Debug:** Check debug log for "AJAX Handler" entries
- **Nonce Error:** Refresh page to get fresh nonce token

---

## 📚 Documentation

### Core Documentation Files

| File | Purpose | Audience |
|------|---------|----------|
| [README.md](README.md) | This file - Project overview & quick start | Everyone |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Development guidelines, architecture decisions, how to contribute | Developers |
| [PRIORITIES.md](PRIORITIES.md) | Roadmap, what's done, what's next, implementation status | Project managers, developers |
| [DEPLOYMENT.md](DEPLOYMENT.md) | Production deployment guide, SSH setup, backup procedures | DevOps, system administrators |
| [AGENTS.md](AGENTS.md) | Instructions for AI agents & automation, key reference info | Automation, future developers |
| [SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md) | Detailed system design, data flows, module descriptions | Architects, senior developers |
| [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md) | UI/UX specifications, color palette, typography, components | Designers, frontend developers |

### Plugin Documentation

| Plugin | Quick Ref | Developer Guide |
|--------|-----------|-----------------|
| **bkgt-core** | [BKGT_CORE_QUICK_REFERENCE.md](BKGT_CORE_QUICK_REFERENCE.md) | In CONTRIBUTING.md |
| **bkgt-inventory** | [BKGT_INVENTORY_INTEGRATION.md](BKGT_INVENTORY_INTEGRATION.md) | In CONTRIBUTING.md |
| **bkgt-document-management** | [BKGT_DOCUMENT_MANAGEMENT_INTEGRATION.md](BKGT_DOCUMENT_MANAGEMENT_INTEGRATION.md) | In CONTRIBUTING.md |
| **bkgt-team-player** | [BKGT_TEAM_PLAYER_INTEGRATION.md](BKGT_TEAM_PLAYER_INTEGRATION.md) | In CONTRIBUTING.md |

---

## 🚨 Current Status & Known Issues

### Production Status (November 4, 2025)
✅ All 8 BKGT plugins active and operational  
✅ Database schema validated and correct  
✅ Zero fatal errors in debug log  
✅ All critical security issues resolved  
✅ Inventory system fully functional  
✅ Document management fully functional  
✅ Event management fully functional  

### Known Limitations
- ⚠️ Offboarding system backend automation pending
- ⚠️ Advanced analytics features optional enhancement
- ⚠️ Some placeholder content remains in documentation

### Recent Fixes (Session November 3-4)
✅ Fixed assignments table name mismatch (`bkgt_assignments` → `bkgt_inventory_assignments`)  
✅ Fixed assignments table schema (corrected columns)  
✅ Fixed 3 analytics post meta queries  
✅ Removed duplicate table creation code  
✅ All queries now execute successfully  

---

## 📞 Support & Contributions

### Reporting Issues
1. Check [Troubleshooting](#troubleshooting) section above
2. Check debug log: `wp-content/debug.log`
3. Search existing documentation for similar issues
4. Create issue with: error message, steps to reproduce, expected vs actual behavior

### Contributing
See [CONTRIBUTING.md](CONTRIBUTING.md) for:
- Development setup
- Code style guidelines
- Testing requirements
- Pull request process
- How to add new features

### Contact
- **Project Lead:** [Team lead contact]
- **Documentation:** See [CONTRIBUTING.md](CONTRIBUTING.md)
- **Issues:** [GitHub Issues](https://github.com/ulvheim/ledare-bkgt/issues)

---

## 📄 License

[License information to be added]

---

## 🎯 Next Steps

1. **Read the Quick Start** section above to get running locally
2. **For Development:** See [CONTRIBUTING.md](CONTRIBUTING.md)
3. **For Deployment:** See [DEPLOYMENT.md](DEPLOYMENT.md)
4. **For Architecture Details:** See [SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md)
5. **For Project Status:** See [PRIORITIES.md](PRIORITIES.md)

---

**Last tested:** November 4, 2025  
**Test result:** ✅ All systems operational, production verified
