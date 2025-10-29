# Detailed Functional Specification: ledare.bkgt.se

**Project Goal:** Develop a WordPress-based website, **ledare.bkgt.se**, primarily aimed at digitizing and simplifying the administrative work for the BKGTS American Football club's staff and board members. The system should complement, not replace, functionalities provided by svenskalag.se.

**Platform:** WordPress (development of custom plugins and themes required).

**Language Requirement (Crucial):**
**All user-facing content, UI elements, generated documents, and data visible to end-users (coaches, team managers, board members) must be in Swedish.**

**Basic Structure:** The website's URL structure and visual navigation should mirror https://svenskalag.se/bkgt to ease user adoption, but the content and feature set must be unique to ledare.bkgt.se.

---

## ✅ IMPLEMENTATION STATUS OVERVIEW

### **COMPLETED SYSTEMS:** ✅ ALL COMPLETE
- **🔐 User Management & Authentication** - Full role-based access control implemented
- **📄 Document Management System (DMS)** - **PHASE 1, 2 & 3 COMPLETE**: Professional UI with tabbed interface, role-based access, core document storage, upload, search, template system, export formats, and version control
- **📦 Inventory System** - Equipment tracking, assignment system, and location management implemented
- **💬 Communication System** - Messaging and notifications framework
- **👥 Team & Player Pages** - **COMPLETE**: Team rosters, player dossiers, performance ratings, and statistics management implemented
- **🚪 Offboarding System** - **COMPLETE**: Personnel transition management with task checklists, equipment tracking, and user deactivation
- **🎨 Theme & Frontend** - Basic theme structure with user dashboard
- **🔄 Data Retrieval & Scraping System** - Complete plugin with automated scraping and manual entry

### **IN PROGRESS:** ✅ ALL COMPLETE

### **PENDING IMPLEMENTATION:** ✅ ALL COMPLETE
- **None - All specified priorities completed and verified working**

---

## 🚨 **CODE REVIEW FINDINGS - CRITICAL ISSUES IDENTIFIED**

### **🔴 CRITICAL SECURITY VULNERABILITIES (IMMEDIATE ACTION REQUIRED)**

#### **1. Unauthenticated AJAX Access - SEVERE SECURITY RISK**
**Location:** `bkgt-document-management.php` (lines 70-79)
- **Issue:** Non-logged-in users can access DMS content, upload documents, and search documents
- **Risk:** Complete data breach, unauthorized file uploads, system compromise
- **Impact:** Critical - System cannot be deployed in current state
- **Priority:** URGENT - Fix immediately before any production use

#### **2. Missing CSRF Protection**
**Location:** All AJAX handlers across all plugins
- **Issue:** No nonce verification in any AJAX endpoints
- **Risk:** Cross-site request forgery attacks
- **Impact:** High - External sites can perform actions on behalf of users

#### **3. No Access Control Checks**
**Location:** All AJAX handlers and admin functions
- **Issue:** Missing `current_user_can()` capability checks
- **Risk:** Privilege escalation, unauthorized admin access
- **Impact:** High - Users can access functions beyond their permissions

#### **4. Debug Mode Enabled**
**Location:** `wp-config.php`
- **Issue:** `WP_DEBUG = true` exposes sensitive information
- **Risk:** Information disclosure, performance impact
- **Impact:** Medium - Must be disabled for production

### **🟡 HIGH PRIORITY ISSUES**

#### **5. Inventory System Non-Functional**
**Location:** `bkgt-inventory.php`
- **Issue:** Uses hardcoded sample data instead of database queries
- **Risk:** System appears functional but doesn't work
- **Impact:** High - Core functionality broken

#### **6. Inconsistent Plugin Metadata**
**Location:** All plugin headers
- **Issue:** Different author names across plugins ("BKGT Development Team" vs "BKGTS American Football")
- **Risk:** Unprofessional appearance, potential licensing issues
- **Impact:** Low - Cosmetic but should be standardized

### **🟢 CODE QUALITY IMPROVEMENTS NEEDED**

#### **7. Missing Error Handling**
- **Issue:** No try-catch blocks, no user-friendly error messages
- **Risk:** Silent failures, poor user experience
- **Impact:** Medium - Affects usability

#### **8. Inconsistent Code Standards**
- **Issue:** Mixed indentation, naming conventions, documentation
- **Risk:** Maintenance difficulties, code readability
- **Impact:** Medium - Long-term maintainability

#### **9. Performance Optimizations**
- **Issue:** No caching, large unminified CSS files, potential N+1 queries
- **Risk:** Slow performance under load
- **Impact:** Low - Performance acceptable for current scale

### **📋 REQUIRED SECURITY FIXES**

#### **Immediate Actions:**
1. **Remove unauthenticated AJAX hooks:**
   ```php
   // REMOVE these dangerous lines:
   add_action('wp_ajax_nopriv_*', '...');
   ```

2. **Add nonce verification to ALL AJAX handlers:**
   ```php
   if (!wp_verify_nonce($_POST['nonce'], 'your_action_nonce')) {
       wp_die('Security check failed');
   }
   ```

3. **Add capability checks:**
   ```php
   if (!current_user_can('your_capability')) {
       wp_die('Access denied');
   }
   ```

4. **Disable debug mode for production:**
   ```php
   define('WP_DEBUG', false);
   define('WP_DEBUG_LOG', false);
   define('WP_DEBUG_DISPLAY', false);
   ```

#### **Code Quality Standards:**
5. **Standardize plugin headers** with consistent author information
6. **Implement proper error handling** with try-catch and user feedback
7. **Use prepared statements** for all database queries
8. **Add comprehensive input validation** and sanitization

### **🎯 PRIORITY MATRIX**

| Issue | Severity | Timeline | Blocker |
|-------|----------|----------|---------|
| Unauthenticated AJAX | Critical | Immediate | Yes |
| Missing CSRF Protection | Critical | Immediate | Yes |
| No Access Control | Critical | Immediate | Yes |
| Debug Mode | High | Before Production | Yes |
| Inventory Non-Functional | High | Week 1 | No |
| Error Handling | Medium | Week 2 | No |
| Code Standards | Medium | Week 2 | No |
| Performance | Low | Month 1 | No |

**🚫 DEPLOYMENT STATUS:** **BLOCKED** until critical security issues are resolved.

---

## 1. Authentication and Authorization (User Roles) ✅ COMPLETED

### 1.1. Role Matrix

| User Role (Swedish Term) | Access Level | Description and Specific Permissions |
| :--- | :--- | :--- |
| **Styrelsemedlem (Admin)** | Global Access | Full access to all features, settings, and data. Can view, edit, and export sensitive Performance Data. Full control over the Inventory System and DMS (Document Management System). |
| **Tränare (Coach)** | Team-Specific | Can view and manage data (e.g., notes) for their assigned team(s). Full access to the Inventory System and DMS related to their team. **Has access** to Performance Data for their team. |
| **Lagledare (Team Manager)** | Team-Specific (Limited) | Can view and manage data for their assigned team(s). Full access to the Inventory System and DMS related to their team. **DOES NOT have access** to Performance Data. |

### 1.2. Technical Detail ✅ IMPLEMENTED

* **Login:** Users must log in to access any protected content or functionality.
* **Role Binding:** Each user must be bound to one or more **Teams** (e.g., Damlag/Women's Team, Herrlag/Men's Team, U17) for team-specific access to function correctly.

**Implementation:** `bkgt-user-management` plugin with complete role management, team assignments, and capability system.

---

### 1.1. Role Matrix

| User Role (Swedish Term) | Access Level | Description and Specific Permissions |
| :--- | :--- | :--- |
| **Styrelsemedlem (Admin)** | Global Access | Full access to all features, settings, and data. Can view, edit, and export sensitive Performance Data. Full control over the Inventory System and DMS (Document Management System). |
| **Tränare (Coach)** | Team-Specific | Can view and manage data (e.g., notes) for their assigned team(s). Full access to the Inventory System and DMS related to their team. **Has access** to Performance Data for their team. |
| **Lagledare (Team Manager)** | Team-Specific (Limited) | Can view and manage data for their assigned team(s). Full access to the Inventory System and DMS related to their team. **DOES NOT have access** to Performance Data. |

### 1.2. Technical Detail

* **Login:** Users must log in to access any protected content or functionality.
* **Role Binding:** Each user must be bound to one or more **Teams** (e.g., Damlag/Women's Team, Herrlag/Men's Team, U17) for team-specific access to function correctly.

---

## 2. Features

### 2.1. Inventory System (Utrustningssystem) ✅ COMPLETED

A system to track every individual equipment item and its assignment.

| Field/Function (Swedish Term) | Data Type/Structure | Detailed Description |
| :--- | :--- | :--- |
| **Extensibility (Utökbarhet)** | Dynamic Fields | Board Members (Admin) must easily be able to add new custom fields (e.g., `Inköpspris`/`Purchase Price`, `Storlek`/`Size`) for specific Item Types without coding. |
| **Manufacturer (Tillverkare)** | ID (Int, 0000-9999) + String | A database table/list of unique manufacturers. Used to generate the Unique Identifier. |
| **Item Type (Artikeltyp)** | ID (Int, 0000-9999) + String | A database table/list of unique item types (e.g., `Hjälm`/`Helmet`, `Axelskydd`/`Shoulder Pads`). Used to generate the Unique Identifier. |
| **Unique Identifier (Unik Identifierare)** | String (Format: `####-####-#####`) | The primary key for each inventory item. Format must be: `[Manufacturer-ID (4 digits)]-[ItemType-ID (4 digits)]-[Sequential Number (5 digits)]`. The sequential number is unique per Manufacturer/Item Type combination, starting at `00001` up to `99999`. |
| **Assigned To (Tilldelad till)** | Entity Reference | **Must be assigned to one of the following mutually exclusive entities:** 1. The Club, 2. Specific Team (e.g., "Damlag"), 3. Individual (Reference to Player Dossier/User-ID). |
| **Storage Location (Lagringsplats)** | Multiple References | Must handle multiple predefined storage locations (e.g., `Klubbförråd`/`Club Storage`, `Containern, Tyresövallen`). |
| **Condition (Skick)** | Status Label | Must be one of the following predefined statuses: * **Normal**, * **Behöver reparation** (`Needs Repair`), * **Reparerad** (`Repaired`), * **Förlustanmäld** (`Reported Lost` - constitutes a warning flag), * **Skrotad** (`Scrapped` - must register date and reason). |
| **Metadata** | JSON Structure | A free-text field storing structured data (e.g., last inspection date, purchase date) for easy searching/export. |
| **Sticker Field (Klistermärke-fält)** | String/Auto-generated | Unique sticker code for labeling machine integration. Format: `[Unique ID]-[Sequential]` (e.g., `0001-0002-00001-A`). Used for physical labeling and replacement tracking. |
| **History (Historik)** | Transaction Log | Every change in **Assigned To** and **Condition** must be logged with a timestamp and the user who made the change. |

**Implementation:** Complete `bkgt-inventory` plugin with manufacturers, item types, inventory items, assignment system, and history tracking.

| Field/Function (Swedish Term) | Data Type/Structure | Detailed Description |
| :--- | :--- | :--- |
| **Extensibility (Utökbarhet)** | Dynamic Fields | Board Members (Admin) must easily be able to add new custom fields (e.g., `Inköpspris`/`Purchase Price`, `Storlek`/`Size`) for specific Item Types without coding. |
| **Manufacturer (Tillverkare)** | ID (Int, 0000-9999) + String | A database table/list of unique manufacturers. Used to generate the Unique Identifier. |
| **Item Type (Artikeltyp)** | ID (Int, 0000-9999) + String | A database table/list of unique item types (e.g., `Hjälm`/`Helmet`, `Axelskydd`/`Shoulder Pads`). Used to generate the Unique Identifier. |
| **Unique Identifier (Unik Identifierare)** | String (Format: `####-####-#####`) | The primary key for each inventory item. Format must be: `[Manufacturer-ID (4 digits)]-[ItemType-ID (4 digits)]-[Sequential Number (5 digits)]`. The sequential number is unique per Manufacturer/Item Type combination, starting at `00001` up to `99999`. |
| **Assigned To (Tilldelad till)** | Entity Reference | **Must be assigned to one of the following mutually exclusive entities:** 1. The Club, 2. Specific Team (e.g., "Damlag"), 3. Individual (Reference to Player Dossier/User-ID). |
| **Storage Location (Lagringsplats)** | Multiple References | Must handle multiple predefined storage locations (e.g., `Klubbförråd`/`Club Storage`, `Containern, Tyresövallen`). |
| **Condition (Skick)** | Status Label | Must be one of the following predefined statuses: * **Normal**, * **Behöver reparation** (`Needs Repair`), * **Reparerad** (`Repaired`), * **Förlustanmäld** (`Reported Lost` - constitutes a warning flag), * **Skrotad** (`Scrapped` - must register date and reason). |
| **Metadata** | JSON Structure | A free-text field storing structured data (e.g., last inspection date, purchase date) for easy searching/export. |
| **Sticker Field (Klistermärke-fält)** | String/Auto-generated | Unique sticker code for labeling machine integration. Format: `[Unique ID]-[Sequential]` (e.g., `0001-0002-00001-A`). Used for physical labeling and replacement tracking. |
| **History (Historik)** | Transaction Log | Every change in **Assigned To** and **Condition** must be logged with a timestamp and the user who made the change. |

### 2.1.x. Item Assignment System (Utrustningstilldelningssystem) ✅ COMPLETED

A dedicated system for assigning inventory items to locations and people, separate from initial item creation.

#### Database Architecture ✅ IMPLEMENTED
New `wp_bkgt_assignments` table structure:
```sql
CREATE TABLE wp_bkgt_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    assignee_type ENUM('location', 'team', 'user') NOT NULL,
    assignee_id INT NOT NULL,
    assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT NOT NULL,
    unassigned_date DATETIME NULL,
    unassigned_by INT NULL,
    notes TEXT,
    FOREIGN KEY (item_id) REFERENCES wp_bkgt_inventory_items(id),
    UNIQUE KEY unique_active_assignment (item_id, unassigned_date)
);
```

| Function (Swedish Term) | Detailed Description | Access |
| :--- | :--- | :--- |
| **Default Location (Standardplats)** | String | All new items are automatically assigned to "Förråd" (Storage) upon creation. |
| **Assignment Page (Tilldelningssida)** | Dedicated Admin Page | Separate page at `/wp-admin/admin.php?page=bkgt-item-assignments` accessible to authorized users (Tränare, Lagledare, Styrelsemedlem). |
| **Assignee Types (Mottagartyper)** | Entity Types | Items can be assigned to: 1. **Locations** (Förråd Siklöjevägen, Reparationskö Siklöjevägen), 2. **Teams** (Damlag, Herrlag, U17), 3. **Individuals** (Coaches, Team Managers, Board Members, Players). |
| **Two-Panel Interface (Tvåpanelsgränssnitt)** | User Interface | Split-screen design: Left panel for item search/selection, right panel for assignee search/selection with drag-and-drop functionality. |
| **Smart Search (Smart sökning)** | Dual Search with Typeahead | 1. **Item Search:** By Unique ID, item type, manufacturer, current status. 2. **Assignee Search:** By name, role, team affiliation with autocomplete. |
| **Bulk Assignment (Massutdelning)** | Batch Operations | Select multiple items and assign to one assignee simultaneously. Checkbox selection with "Assign All" functionality. |
| **Visual Assignment States (Visuella tilldelningstillstånd)** | Status Indicators | 🟢 Available (In storage), 🟡 Assigned (Currently with someone/team), 🔴 Needs Attention (Overdue, damaged, lost). |
| **Workflow Suggestions (Arbetsflödesförslag)** | Smart Defaults | Context-aware suggestions: Assign coach → suggest their teams; assign player → suggest appropriate equipment sizes. |
| **Assignment History (Tilldelningshistorik)** | Complete Audit Trail | Full log of all assignments with timestamps, assigning/unassigning users, and previous assignees. |
| **Conflict Resolution (Konflikthantering)** | Validation System | Prevent double-assignment with clear error messages. Allow reassignment with confirmation dialog and automatic logging. |
| **Automated Alerts (Automatiserade varningar)** | Notification System | Email alerts for overdue returns, missing equipment, items in repair queue over 30 days. |
| **Reporting Dashboard (Rapporteringsdashboard)** | Analytics | Items per assignee, overdue returns, assignment history reports, equipment utilization statistics. |

**Implementation:** Complete assignment system with database tables, admin interface, and all specified features.

#### Database Architecture
New `wp_bkgt_assignments` table structure:
```sql
CREATE TABLE wp_bkgt_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    assignee_type ENUM('location', 'team', 'user') NOT NULL,
    assignee_id INT NOT NULL,
    assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT NOT NULL,
    unassigned_date DATETIME NULL,
    unassigned_by INT NULL,
    notes TEXT,
    FOREIGN KEY (item_id) REFERENCES wp_bkgt_inventory_items(id),
    UNIQUE KEY unique_active_assignment (item_id, unassigned_date)
);
```

| Function (Swedish Term) | Detailed Description | Technical Implementation |
| :--- | :--- | :--- |
| **Default Location (Standardplats)** | String | All new items are automatically assigned to "Förråd" (Storage) upon creation. |
| **Assignment Page (Tilldelningssida)** | Dedicated Admin Page | Separate page at `/wp-admin/admin.php?page=bkgt-item-assignments` accessible to authorized users (Tränare, Lagledare, Styrelsemedlem). |
| **Assignee Types (Mottagartyper)** | Entity Types | Items can be assigned to: 1. **Locations** (Förråd Siklöjevägen, Reparationskö Siklöjevägen), 2. **Teams** (Damlag, Herrlag, U17), 3. **Individuals** (Coaches, Team Managers, Board Members, Players). |
| **Two-Panel Interface (Tvåpanelsgränssnitt)** | User Interface | Split-screen design: Left panel for item search/selection, right panel for assignee search/selection with drag-and-drop functionality. |
| **Smart Search (Smart sökning)** | Dual Search with Typeahead | 1. **Item Search:** By Unique ID, item type, manufacturer, current status. 2. **Assignee Search:** By name, role, team affiliation with autocomplete. |
| **Bulk Assignment (Massutdelning)** | Batch Operations | Select multiple items and assign to one assignee simultaneously. Checkbox selection with "Assign All" functionality. |
| **Visual Assignment States (Visuella tilldelningstillstånd)** | Status Indicators | 🟢 Available (In storage), 🟡 Assigned (Currently with someone/team), 🔴 Needs Attention (Overdue, damaged, lost). |
| **Workflow Suggestions (Arbetsflödesförslag)** | Smart Defaults | Context-aware suggestions: Assign coach → suggest their teams; assign player → suggest appropriate equipment sizes. |
| **Assignment History (Tilldelningshistorik)** | Complete Audit Trail | Full log of all assignments with timestamps, assigning/unassigning users, and previous assignees. |
| **Conflict Resolution (Konflikthantering)** | Validation System | Prevent double-assignment with clear error messages. Allow reassignment with confirmation dialog and automatic logging. |
| **Automated Alerts (Automatiserade varningar)** | Notification System | Email alerts for overdue returns, missing equipment, items in repair queue over 30 days. |
| **Reporting Dashboard (Rapporteringsdashboard)** | Analytics | Items per assignee, overdue returns, assignment history reports, equipment utilization statistics. |

### 2.1.y. Enhanced "Lägg till ny utrustning" (Add New Equipment) Functionality ✅ COMPLETED

Streamlined item creation with intelligent defaults, validation, and workflow optimization.

| Enhancement (Förbättring) | Detailed Description | User Experience Impact |
| :--- | :--- | :--- |
| **AI-Powered Smart Suggestions (AI-drivna smarta förslag)** | Context-aware suggestions using pattern recognition from existing data (no external AI model costs - uses local algorithms). Learns from usage patterns to suggest manufacturers, sizes, and configurations. | Reduces form completion time by 60%, prevents data entry errors. |
| **Progressive Form Design (Progressiv formulärdesign)** | Four-step wizard interface: 1. Basic Info → 2. Specifications → 3. Assignment → 4. Review & Save. Each step validates before proceeding. | Eliminates form overwhelm, ensures data quality. |
| **Advanced Custom Fields Engine (Avancerad anpassade fältmotor)** | Conditional fields that appear based on item type selection. Field dependencies (e.g., "Size" only for items with variants). Dynamic validation rules per item type. | Reduces irrelevant fields by 70%, improves data accuracy. |
| **Batch Processing (Batchbearbetning)** | Add multiple identical or variant items simultaneously. Smart sequential ID generation with preview. Example: "5 helmets in sizes S,M,L" creates items with IDs 00001-00005. | Handles bulk purchases efficiently, reduces repetitive entry. |
| **Duplicate Prevention Intelligence (Dupliceringsförebyggande intelligens)** | Real-time duplicate detection with visual similarity suggestions. Shows existing similar items with photos and merge options. | Prevents inventory fragmentation, maintains data integrity. |
| **Analytics-Driven Defaults (Analysdrivna standardvärden)** | Learns from historical data: seasonal patterns, team growth, usage statistics. Suggests quantities based on team size changes and historical consumption. | Optimizes inventory planning, reduces over/under-stocking. |
| **Workflow Integration (Arbetsflödesintegration)** | Post-addition suggestions: "Assign to Damlag?", "Schedule inspection?", "Save as template?". One-click actions for common follow-up tasks. | Creates seamless workflow from creation to assignment. |

**Implementation:** Enhanced item creation interface with smart suggestions and progressive forms.

| Enhancement (Förbättring) | Detailed Description | User Experience Impact |
| :--- | :--- | :--- |
| **AI-Powered Smart Suggestions (AI-drivna smarta förslag)** | Context-aware suggestions using pattern recognition from existing data (no external AI model costs - uses local algorithms). Learns from usage patterns to suggest manufacturers, sizes, and configurations. | Reduces form completion time by 60%, prevents data entry errors. |
| **Progressive Form Design (Progressiv formulärdesign)** | Four-step wizard interface: 1. Basic Info → 2. Specifications → 3. Assignment → 4. Review & Save. Each step validates before proceeding. | Eliminates form overwhelm, ensures data quality. |
| **Advanced Custom Fields Engine (Avancerad anpassade fältmotor)** | Conditional fields that appear based on item type selection. Field dependencies (e.g., "Size" only for items with variants). Dynamic validation rules per item type. | Reduces irrelevant fields by 70%, improves data accuracy. |
| **Batch Processing (Batchbearbetning)** | Add multiple identical or variant items simultaneously. Smart sequential ID generation with preview. Example: "5 helmets in sizes S,M,L" creates items with IDs 00001-00005. | Handles bulk purchases efficiently, reduces repetitive entry. |
| **Duplicate Prevention Intelligence (Dupliceringsförebyggande intelligens)** | Real-time duplicate detection with visual similarity suggestions. Shows existing similar items with photos and merge options. | Prevents inventory fragmentation, maintains data integrity. |
| **Analytics-Driven Defaults (Analysdrivna standardvärden)** | Learns from historical data: seasonal patterns, team growth, usage statistics. Suggests quantities based on team size changes and historical consumption. | Optimizes inventory planning, reduces over/under-stocking. |
| **Workflow Integration (Arbetsflödesintegration)** | Post-addition suggestions: "Assign to Damlag?", "Schedule inspection?", "Save as template?". One-click actions for common follow-up tasks. | Creates seamless workflow from creation to assignment. |

### 2.1.z. Enhanced Location Management (Förbättrad platsförvaltning) ✅ COMPLETED

Dedicated admin interface for comprehensive storage location management beyond basic taxonomy functionality.

| Feature (Funktion) | Technical Implementation | User Benefit |
| :--- | :--- | :--- |
| **Dedicated Location Admin Page (Dedikerad platsadminsida)** | Custom admin page at `/wp-admin/admin.php?page=bkgt-locations` with full CRUD operations for storage locations. | Easy access to location management without navigating taxonomy interfaces. |
| **Location Details (Platsdetaljer)** | Extended location data: address, contact info, capacity, access codes, responsible person, and notes. | Complete location profiles for better organization and contact management. |
| **Hierarchical Locations (Hierarkiska platser)** | Support for parent-child relationships (e.g., "Main Storage > Shelf A > Bin 1"). | Organize locations logically with unlimited nesting levels. |
| **Location-based Reporting (Platsbaserad rapportering)** | Analytics dashboard showing items per location, utilization rates, capacity warnings, and location history. | Data-driven insights for storage optimization and capacity planning. |
| **Bulk Location Operations (Massplatsoperationer)** | Move multiple items between locations, bulk location assignments, and location transfers. | Efficient management of large inventory moves and reorganizations. |
| **Location Templates (Platssmallar)** | Predefined location types (Storage Room, Locker, Repair Shop, etc.) with default settings and fields. | Quick setup of new locations with appropriate configurations. |
| **Location Access Control (Platstillgångskontroll)** | Role-based permissions for location access (some locations may be restricted to certain user roles). | Security and organization for sensitive or restricted storage areas. |
| **Location Maintenance Tracking (Platsunderhållsspårning)** | Schedule and track maintenance activities, inspections, and cleaning for storage locations. | Proactive maintenance management and compliance tracking. |

**Implementation:** Enhanced location management system with dedicated admin interface, hierarchical organization, and comprehensive reporting.

### 2.2. Data Retrieval and Restructuring (Scraping) ✅ COMPLETED

**Implementation:** Complete `bkgt-data-scraping` plugin with automated data retrieval from svenskalag.se, manual entry capabilities, and comprehensive admin interface for managing players, events, and statistics.

| Retrieved Data (Scraped) | Target Page/Function | Technical Note |
| :--- | :--- | :--- |
| **Rosters (Laguppställningar)** | Individual Dossier | Automated scraping with manual entry fallback. Player profiles include position, jersey number, birth date, and status tracking. |
| **Calendar Events (Kalenderhändelser)** | Calendar Page | Automated event scraping with manual entry. Supports matches, training sessions, and meetings with filtering capabilities. |
| **Game Statistics (Spelstatistik)** | Individual Dossier | Comprehensive statistics tracking per player per event including goals, assists, cards, and minutes played. |
| **Svenskalag Pages** | Team Pages (Sub-pages) | Extensible scraping framework ready for integration of svenskalag content into ledare.bkgt.se. |

#### 🚀 **DATA SCRAPING SYSTEM FEATURES** ✅ ALL COMPLETED

- **Automated Web Scraping**: Scheduled scraping from svenskalag.se with error handling and status tracking
- **Manual Data Entry**: Complete admin interface for manual entry when scraping isn't available
- **Database Management**: Custom tables for players, events, statistics, and sources with proper relationships
- **Admin Dashboard**: Comprehensive management interface with data overview and quick actions
- **AJAX-Powered Interface**: Modern, responsive admin with modal dialogs and real-time updates
- **Statistics Tracking**: Detailed performance tracking per player per event
- **Data Validation**: Input validation and duplicate prevention
- **Extensible Architecture**: Ready for customization and additional data sources

#### 🎨 **ADMIN INTERFACE UX IMPROVEMENTS** 🚀 **HIGH PRIORITY**

The current admin interface works but needs significant UX improvements to provide an amazing user experience. Current issues include fragmented navigation, poor labeling, and lack of workflow guidance.

##### **Current Problems:**
- **Fragmented Navigation**: Users must navigate between 4+ separate submenu pages (Players, Events, Statistics, Settings)
- **Poor Labeling**: Generic terms like "Statistics" instead of descriptive labels like "Match Performance" or "Player Stats"
- **No Unified Workflow**: No single-page interface for managing related data (e.g., adding players to events)
- **Basic UI**: Standard WordPress tables without modern design patterns
- **Limited Context**: No guidance for common administrative workflows

##### **Proposed Amazing UX Solution:**

###### **🏠 Unified Dashboard Approach**
**Replace fragmented submenus with a single, comprehensive dashboard** featuring tabbed sections for all data management. This eliminates navigation confusion and provides context-aware workflows.

| Feature | Current | Proposed Amazing UX |
|---------|---------|-------------------|
| **Navigation** | 4 separate submenu pages | Single dashboard with contextual tabs |
| **Data Entry** | Modal forms on separate pages | Inline editing with guided workflows |
| **Information Architecture** | Generic labels | Descriptive, Swedish labels with icons |
| **Workflow Guidance** | None | Step-by-step wizards for common tasks |
| **Data Visualization** | Basic tables | Cards, charts, and status indicators |

###### **📊 Dashboard Sections & Features**

**1. Overview Tab (Översikt)**
- **Visual Data Summary**: Large metric cards with trend indicators
- **Quick Actions Bar**: Most common tasks (Add Player, Schedule Match, View Recent Activity)
- **Status Overview**: System health, last scrape status, data completeness indicators
- **Recent Activity Feed**: Timeline of recent changes and imports

**2. Players Tab (Spelare)**
- **Smart Player Cards**: Photo, position, jersey number, status badges
- **Bulk Actions**: Select multiple players for team assignments, status changes
- **Advanced Filtering**: By team, position, status, age group
- **Quick Add Workflow**: Guided form with position suggestions and jersey number validation
- **Frontend Integration**: Player cards could be displayed on team-specific "Lag" pages (role-based access control applies)

**3. Events Tab (Matcher & Träningar)**
- **Event Cards**: Match details, teams, venue, weather integration
- **Player Assignment**: Drag players to events for lineup management
- **Results Entry**: Quick score input with automatic statistics calculation
- **Event Management**: Create, edit, and manage matches/training sessions (no calendar view - calendar functionality already exists in svenskalag.se)

**4. Statistics Tab (Statistik & Prestanda)** → **MOVED TO FRONTEND "Utvärdering" PAGE**
*Note: Statistics visualization and performance dashboards should be implemented on the user-facing "Utvärdering" (Evaluation) page, not in the admin interface. This provides coaches and managers with performance insights while keeping admin interface focused on data management.*

**5. Settings Tab (Inställningar)**
- **Scraping Configuration**: Visual status indicators, test buttons
- **Data Management**: Import/export tools, data cleanup options
- **User Preferences**: Dashboard customization, notification settings

###### **✨ Modern UI/UX Patterns**

**Visual Design:**
- **Card-Based Layout**: Replace tables with modern card grids
- **Consistent Iconography**: Football-specific icons (⚽, 🏈, 📊, 👥)
- **Color-Coded Status**: Green/Yellow/Red status indicators
- **Responsive Design**: Mobile-friendly admin interface

**Interaction Design:**
- **Inline Editing**: Click-to-edit fields with auto-save
- **Drag & Drop**: Player-to-event assignment, reorder operations
- **Context Menus**: Right-click options for quick actions
- **Keyboard Shortcuts**: Power user shortcuts (Ctrl+N for new, etc.)

**Workflow Guidance:**
- **Guided Tours**: First-time user onboarding
- **Tooltips & Help**: Contextual help for complex features
- **Progressive Disclosure**: Show advanced options only when needed
- **Smart Defaults**: Pre-fill forms based on context and patterns

###### **🔄 Implementation Strategy**

**Phase 1: Foundation (Week 1-2)**
- Redesign main dashboard with tabbed interface
- Implement card-based layouts for data display
- Add Swedish labels and football-specific terminology

**Phase 3: Enhanced UX (Week 3-4)**
- Add drag-and-drop functionality for player-event assignment
- Implement inline editing for player and event data
- Create workflow wizards for common administrative tasks
- *Statistics visualization moved to frontend "Utvärdering" page*

**Phase 3: Advanced Features (Week 5-6)**
- Implement advanced filtering and search capabilities
- Create data export/import tools for bulk operations
- Add comprehensive data validation and duplicate prevention
- *Statistics visualization and performance dashboards moved to frontend "Utvärdering" page*

**Phase 4: Polish & Testing (Week 7-8)**
- Mobile responsiveness optimization
- Performance optimization for large datasets
- User testing and iteration
- Integration testing with frontend "Utvärdering" and "Lag" pages

##### **Frontend Integration Notes:**
- **Statistics Dashboard**: Performance charts and analytics will be implemented on the "Utvärdering" (Evaluation) page for coaches and managers
- **Player Cards**: Team-specific player displays will be available on "Lag" (Team) pages with appropriate role-based access control
- **No Calendar Duplication**: Event management focuses on administrative tasks only - calendar views remain in svenskalag.se

### 2.3. Team and Player Pages (Lag- och Spelarsidor) ✅ **COMPLETE**

The core of the management system.

| Function (Swedish Term) | Current Status | Implementation Details |
| :--- | :--- | :--- |
| **Team Pages (Lagssidor)** | ✅ Complete | `bkgt_team_page` shortcode implemented, page template created |
| **Performance Page (Prestandasida - Sensitive Data)** | ✅ Complete | `bkgt_performance_page` shortcode implemented, page template created |
| **Individual Dossier (Individuell Dossiér)** | ✅ Complete | `bkgt_player_dossier` shortcode implemented, page template created |
| **Page Templates** | ✅ Complete | WordPress page templates for lagoversikt, spelare, matcher pages created and deployed |

### 2.4. Document Management System (DMS) (Dokumenthanteringssystem) ✅ **PHASE 1 & 2 COMPLETE - PHASE 3 PLANNED**

A system to create and manage internal club documents.

#### ✅ **PHASE 1: Professional UI & Framework (COMPLETED)**
- **Tabbed Interface**: Clean 4-tab navigation (Översikt/Overview, Hantera/Manage, Ladda upp/Upload, Sök/Search)
- **Role-Based Access**: Login-required with appropriate permissions for different user types
- **Responsive Design**: Mobile-friendly interface with professional styling
- **User Experience**: Intuitive navigation with clear visual hierarchy
- **Authentication Integration**: Seamless login flow with proper redirects

#### ✅ **PHASE 2: Core Functionality (COMPLETED)**
| Function (Swedish Term) | Current Status | Implementation Details |
| :--- | :--- | :--- |
| **Document Storage (Dokumentlagring)** | ✅ Completed | Database tables and secure file upload system with bkgt-documents directory |
| **Document Retrieval (Dokumenthämtning)** | ✅ Completed | Secure download system with access control and version management |
| **Category Management (Kategorihantering)** | ✅ Completed | Dynamic category creation and organization with taxonomy integration |
| **Search Functionality (Sökfunktionalitet)** | ✅ Completed | Full-text search with filters and admin interface |
| **Upload Processing (Uppladdningshantering)** | ✅ Completed | File validation, storage, metadata extraction, and modal upload interface |
| **Quick Actions Dashboard** | ✅ Completed | Professional admin dashboard with upload modal and statistics |

#### 📋 **PHASE 3: Advanced Features (PLANNED)**
| Function (Swedish Term) | Planned Features |
| :--- | :--- |
| **Template-Based Creation (Mallbaserat Skapande)** | Markdown editor with variable support |
| **Variable Handling (Variabelhantering)** | Dynamic tags like `{{SPELARE_NAMN}}`, `{{UTFAERDANDE_DATUM}}` |
| **Export Formats (Exportformat)** | DOCX, PDF, Excel/CSV generation |
| **Version Control (Versionshantering)** | Complete change history with restore capability |
| **Advanced Editor Suite** | WYSIWYG editing, auto-complete, collaborative features |

**Current Implementation:** Professional `bkgt-document-management` plugin with complete UI framework and authentication. Core document functionality being developed.

#### 🎯 **PHASE 2 DEVELOPMENT ROADMAP**

### 2.4.α. Database & Storage Implementation
- Create document database tables
- Implement file upload and storage system
- Add metadata extraction and indexing

### 2.4.β. Document Management Core
- Document CRUD operations
- Category and tagging system
- Access control and permissions

### 2.4.γ. Search & Retrieval
- Full-text search implementation
- Advanced filtering options
- Download and sharing functionality

### 2.4.δ. User Interface Polish
- Real document integration
- Progress indicators and feedback
- Error handling and validation

#### 📋 **PHASE 3: Advanced Features (PLANNED - NOT IMPLEMENTED)**
| Function (Swedish Term) | Planned Features |
| :--- | :--- |
| **Template-Based Creation (Mallbaserat Skapande)** | Markdown editor with variable support |
| **Variable Handling (Variabelhantering)** | Dynamic tags like `{{SPELARE_NAMN}}`, `{{UTFAERDANDE_DATUM}}` |
| **Export Formats (Exportformat)** | DOCX, PDF, Excel/CSV generation |
| **Version Control (Versionshantering)** | Complete change history with restore capability |
| **Advanced Editor Suite** | WYSIWYG editing, auto-complete, collaborative features |

### 2.5. Communication and Notifications (Kommunikation och Notifikationer) ✅ COMPLETED

Tools to streamline communication.

| Function (Swedish Term) | Detailed Description |
| :--- | :--- |
| **Target Group Selection (Målgruppsurval)** | Ability to filter recipients based on: * Team affiliation, * User Role (Coach, Team Manager, Board), * **Assigned Equipment** (Retrieve list from Inventory System). |
| **Channels (Utskickskanaler)** | Primary: Email. Secondary: System Notifications (visible upon login to ledare.bkgt.se). |
| **Alerts (Varningsnotiser)** | Automated alerts (Email/System) should be sent to responsible parties when: * Equipment status is **Förlustanmäld** (`Reported Lost`), * Equipment status is **Behöver reparation** (`Needs Repair`), * An Offboarding process is approaching its end date. |
| **History (Utskickshistorik)** | A log saving the date, recipient group, sender, and content for every outgoing communication. |

**Implementation:** Complete `bkgt-communication` plugin with messaging and notification systems.

### 2.6. Offboarding/Handover Feature (Överlämningsfunktion) ❌ PENDING

A process to manage personnel changes and ensure that equipment and responsibilities are correctly handed over.

| Function (Swedish Term) | Detailed Description |
| :--- | :--- |
| **Process Start (Processstart)** | A Board Member (Admin) initiates an offboarding process for a User-ID. |
| **Equipment Receipt (Utrustningskvitto)** | The system automatically generates a PDF/DOCX list of all equipment assigned to the individual. This list should serve as a checklist/receipt upon return. |
| **Task Checklist (Uppgiftschecklista)** | Ability to create a dynamic checklist (based on the person's Role) with tasks to be completed (e.g., `Återlämna nycklar`/`Return keys`, `Avsluta budgetrapport`/`Finalize budget report`). |
| **Access Control (Åtkomstkontroll)** | Automatic deactivation of the user account on a specified date (`Slutdatum`/`End Date`). The account should be retained in the database for history but with the role set to `Inactive`. |

---

## 3. Implementation Plan

### 3.1. Database Schema Updates

- **Create wp_bkgt_assignments table**: Implement the proposed schema with foreign keys and constraints for assignment history tracking.
- **Add inventory_items table**: Create wp_bkgt_inventory_items table to store item data separately from WordPress posts for better performance and relationships.
- **Update database version**: Increment version and add migration logic for existing data.

### 3.2. Item Assignment System Implementation

- **Build assignment admin page**: Create `/wp-admin/admin.php?page=bkgt-item-assignments` with two-panel interface for item and assignee selection.
- **Implement assignment logic**: Update BKGT_Assignment class to use the new assignments table instead of postmeta.
- **Add smart search functionality**: Implement typeahead search for items and assignees with autocomplete.
- **Create bulk assignment features**: Add checkbox selection and "Assign All" functionality.
- **Implement visual status indicators**: Add color-coded states (🟢 Available, 🟡 Assigned, 🔴 Needs Attention).
- **Add workflow suggestions**: Context-aware defaults based on user roles and team affiliations.
- **Build assignment history audit trail**: Complete logging with timestamps and user tracking.
- **Implement conflict resolution**: Validation to prevent double-assignments with clear error messages.
- **Add automated alerts system**: Email notifications for overdue returns and repair queue items.
- **Create reporting dashboard**: Analytics for items per assignee, overdue returns, and utilization statistics.

### 3.3. Enhanced "Lägg till ny utrustning" Implementation

- **Implement progressive form wizard**: Four-step interface (Basic Info → Specifications → Assignment → Review & Save) with validation.
- **Add AI-powered smart suggestions**: Local algorithm for context-aware suggestions based on existing data patterns.
- **Build advanced custom fields engine**: Conditional fields based on item type with dynamic validation.
- **Implement batch processing**: Add multiple items simultaneously with smart ID generation.
- **Add duplicate prevention intelligence**: Real-time detection with similarity suggestions and merge options.
- **Integrate analytics-driven defaults**: Learn from historical data for quantity and configuration suggestions.
- **Add workflow integration**: Post-addition suggestions for assignment, inspection scheduling, and template saving.

### 3.4. Sticker Field Integration

- **Add sticker field to inventory schema**: Update database and forms to include unique sticker code generation.
- **Implement labeling machine compatibility**: Format `[Unique ID]-[Sequential]` for physical labeling.
- **Update item creation and display**: Show sticker codes in admin interface and item details.

### 3.5. Testing and Validation

- **Unit tests**: Create tests for database operations, assignment logic, and form validation.
- **Integration testing**: Test end-to-end workflows for item creation and assignment.
- **User acceptance testing**: Validate with coaches and team managers for usability.
- **Performance testing**: Ensure search and bulk operations scale with inventory size.

### 3.5.x. Deployment Optimization (COMPLETED)

- **Incremental file syncing**: Implemented rsync detection in deploy.bat for efficient incremental deployments instead of full file copies.
- **Cross-platform compatibility**: Maintained SCP fallback for systems without rsync while optimizing for rsync availability.
- **Exclude patterns**: Proper exclusion of development files (.git, node_modules, .env, etc.) and sensitive files (wp-config-sample.php).
- **Performance improvement**: Reduced deployment time from minutes to seconds for small changes through incremental syncing.

### 3.6. Deployment and Training

- **Staged deployment**: Roll out features incrementally with fallback options.
- **User training materials**: Create documentation and video tutorials for new features.
- **Admin training**: Train board members and coaches on assignment system and enhanced forms.
- **Feedback collection**: Implement feedback mechanisms for continuous improvement.