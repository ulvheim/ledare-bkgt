# BKGT SYSTEM ARCHITECTURE & OVERVIEW

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                       BKGT PLATFORM                             │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                  HELPER FUNCTIONS LAYER                   │  │
│  │  (Easy access to all BKGT systems)                       │  │
│  │  • bkgt_log()     - Centralized logging                  │  │
│  │  • bkgt_validate() - Input validation & sanitization    │  │
│  │  • bkgt_can()     - Permission checking                 │  │
│  │  • bkgt_db()      - Database operations                 │  │
│  └──────────────────────────────────────────────────────────┘  │
│                             ▲                                    │
│                             │                                    │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                    CORE SYSTEMS LAYER                     │  │
│  │  ┌───────────────┐  ┌──────────────┐  ┌────────────────┐ │  │
│  │  │ BKGT_Logger   │  │BKGT_Validator│  │BKGT_Permission│ │  │
│  │  │  (Logging)    │  │(Validation)  │  │   (Access)    │ │  │
│  │  └───────────────┘  └──────────────┘  └────────────────┘ │  │
│  │  ┌───────────────┐  ┌──────────────┐                      │  │
│  │  │ BKGT_Database │  │ BKGT_Core    │                      │  │
│  │  │  (Database)   │  │ (Bootstrap)  │                      │  │
│  │  └───────────────┘  └──────────────┘                      │  │
│  └──────────────────────────────────────────────────────────┘  │
│                             ▲                                    │
│                             │                                    │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                  7 INTEGRATED PLUGINS                     │  │
│  │  ┌─────────────┐ ┌──────────────┐ ┌────────────────────┐│  │
│  │  │ Inventory   │ │  Document    │ │  Team/Player       ││  │
│  │  │ Management  │ │  Management  │ │  Management        ││  │
│  │  └─────────────┘ └──────────────┘ └────────────────────┘│  │
│  │  ┌─────────────┐ ┌──────────────┐ ┌────────────────────┐│  │
│  │  │    User     │ │              │ │   Offboarding      ││  │
│  │  │  Management │ │Communicatios │ │  & Data Scraping   ││  │
│  │  └─────────────┘ └──────────────┘ └────────────────────┘│  │
│  └──────────────────────────────────────────────────────────┘  │
│                             ▲                                    │
│                             │                                    │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │              WordPress & WordPress Plugins               │  │
│  │  (WooCommerce, Jetpack, Gravity Forms, etc.)            │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔄 Data Flow: AJAX Request

```
┌─────────────────────────────────────────────────────────────┐
│  User Browser                                               │
│  └─ Click button → JavaScript AJAX call                   │
└─────────────────────┬───────────────────────────────────────┘
                      │ POST /wp-admin/admin-ajax.php
                      │ action=my_plugin_action
                      │ nonce=xyz
                      │ data={...}
                      ▼
┌─────────────────────────────────────────────────────────────┐
│  WordPress Admin                                            │
│  └─ Route to plugin's AJAX handler                         │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│  Plugin AJAX Handler                                        │
│  ├─ Step 1: Verify nonce                                  │
│  │  └─ bkgt_validate('nonce', $_POST['_wpnonce'])         │
│  │      ↓ Calls BKGT_Validator.verify_nonce()             │
│  │        ↓ Logs result → BKGT_Logger                     │
│  │          ↓ On failure: wp_die()                        │
│  │                                                          │
│  ├─ Step 2: Check permissions                             │
│  │  └─ bkgt_can('capability')                             │
│  │      ↓ Calls BKGT_Permission.check_capability()        │
│  │        ↓ Logs result → BKGT_Logger                     │
│  │          ↓ On failure: wp_die()                        │
│  │                                                          │
│  ├─ Step 3: Validate input                                │
│  │  └─ bkgt_validate('email', $_POST['email'])            │
│  │      ↓ Calls BKGT_Validator.validate_email()           │
│  │        ↓ Logs result → BKGT_Logger                     │
│  │          ↓ On failure: return error                    │
│  │                                                          │
│  ├─ Step 4: Process data                                  │
│  │  └─ $results = bkgt_db()->get_posts(...)              │
│  │      ↓ Calls BKGT_Database.get_posts()                 │
│  │        ↓ Check cache → cache hit/miss                  │
│  │          ↓ If hit: return cached result                │
│  │            ↓ If miss: query DB                         │
│  │              ↓ Prepared statement                      │
│  │                ↓ Cache result (1 hour)                 │
│  │                  ↓ Logs operation → BKGT_Logger        │
│  │                                                          │
│  ├─ Step 5: Log action                                    │
│  │  └─ bkgt_log('info', 'Action completed', [...])        │
│  │      ↓ Calls BKGT_Logger.log()                         │
│  │        ↓ Write to wp-content/bkgt-logs.log             │
│  │          ↓ Write to wp_bkgt_logs DB table              │
│  │            ↓ Send to admin dashboard                   │
│  │              ↓ Alert if critical                       │
│  │                                                          │
│  └─ Step 6: Return result                                 │
│     └─ wp_send_json_success([...])                         │
└─────────────────────┬───────────────────────────────────────┘
                      │ JSON response
                      │ {success: true, data: {...}}
                      ▼
┌─────────────────────────────────────────────────────────────┐
│  User Browser                                               │
│  └─ Display result to user                                │
└─────────────────────────────────────────────────────────────┘

SECURITY CHECKPOINTS:
✅ Nonce verification (CSRF protection)
✅ Permission checking (Access control)
✅ Input validation & sanitization
✅ Prepared statements (SQL injection prevention)
✅ Output escaping (XSS prevention)
✅ Complete audit logging (Accountability)
```

---

## 🔐 Security Layer

```
┌──────────────────────────────────────────────────────────────┐
│                     SECURITY ARCHITECTURE                     │
├──────────────────────────────────────────────────────────────┤
│                                                                │
│  LAYER 1: CSRF PROTECTION (Nonce Verification)               │
│  ├─ Generated: Automatically by WordPress                    │
│  ├─ Sent with: Every AJAX request in form data              │
│  ├─ Verified by: BKGT_Validator.verify_nonce()              │
│  ├─ Logged: Failure logged to bkgt-logs.log                 │
│  └─ Result: ✅ No cross-site request forgery attacks         │
│                                                                │
│  LAYER 2: ACCESS CONTROL (Permission Checking)               │
│  ├─ Defined: 3 roles (Admin, Coach, Team Manager)           │
│  ├─ Capabilities: 15+ capabilities per role                 │
│  ├─ Checked by: bkgt_can('capability')                      │
│  ├─ Scope: Team-based for Team Managers                     │
│  ├─ Logged: All permission checks logged                    │
│  └─ Result: ✅ Users can only access allowed resources       │
│                                                                │
│  LAYER 3: INPUT VALIDATION (Sanitization)                    │
│  ├─ Rules: 13 validation rules                              │
│  ├─ Validated by: BKGT_Validator methods                    │
│  ├─ Sanitization: 5 sanitization methods available          │
│  ├─ Type checking: All parameters type-checked              │
│  ├─ Logged: All validation failures logged                  │
│  └─ Result: ✅ Invalid input rejected before processing      │
│                                                                │
│  LAYER 4: SQL INJECTION PREVENTION (Prepared Statements)    │
│  ├─ Method: All queries use $wpdb->prepare()               │
│  ├─ Parameters: All user input passed as parameters          │
│  ├─ No concatenation: Zero string concatenation             │
│  ├─ Escaping: Automatic by prepare()                        │
│  ├─ Logged: All DB operations logged                        │
│  └─ Result: ✅ No SQL injection possible                     │
│                                                                │
│  LAYER 5: XSS PREVENTION (Output Escaping)                   │
│  ├─ Escaping: All output properly escaped                   │
│  ├─ Functions: esc_html(), esc_attr(), wp_kses_post()       │
│  ├─ User input: Never output directly                       │
│  ├─ Scripts: No inline scripts with user data               │
│  └─ Result: ✅ No script injection possible                  │
│                                                                │
│  LAYER 6: AUDIT LOGGING (Accountability)                     │
│  ├─ Events: 50+ event types logged                          │
│  ├─ Context: User, IP, page, action, timestamp              │
│  ├─ Storage: File + Database + Dashboard                    │
│  ├─ Security checks: All logged for review                  │
│  ├─ Alerts: Critical events trigger alerts                  │
│  └─ Result: ✅ Complete audit trail for accountability       │
│                                                                │
└──────────────────────────────────────────────────────────────┘
```

---

## 📊 Logging Architecture

```
┌──────────────────────────────────────────────────────────┐
│                   LOGGING ARCHITECTURE                    │
├──────────────────────────────────────────────────────────┤
│                                                            │
│  ENTRY POINT:                                            │
│  └─ bkgt_log('level', 'message', ['context' => 'data']) │
│     ↓ BKGT_Logger class processes                        │
│                                                            │
│  SEVERITY LEVELS:                                        │
│  ├─ 'debug'    - Development/diagnostic info             │
│  ├─ 'info'     - Informational messages                  │
│  ├─ 'warning'  - Warning conditions                      │
│  ├─ 'error'    - Error conditions                        │
│  └─ 'critical' - Critical conditions (alerts sent)       │
│                                                            │
│  STORAGE DESTINATIONS:                                   │
│  │                                                         │
│  ├─ FILE LOG                                             │
│  │  └─ wp-content/bkgt-logs.log                          │
│  │     ├─ Format: [date] [level] [message] [context]    │
│  │     ├─ Rotated: Daily (old logs archived)             │
│  │     └─ Readable: Plain text for troubleshooting       │
│  │                                                         │
│  ├─ DATABASE LOG                                         │
│  │  └─ wp_bkgt_logs table                                │
│  │     ├─ Columns: id, timestamp, level, message...      │
│  │     ├─ Indexed: Queryable for analysis                │
│  │     └─ Retention: Kept for audit trail                │
│  │                                                         │
│  ├─ ADMIN DASHBOARD                                      │
│  │  └─ WordPress Admin → BKGT Logs                       │
│  │     ├─ Real-time view of logs                         │
│  │     ├─ Filterable by level & date                     │
│  │     └─ Quick troubleshooting access                   │
│  │                                                         │
│  └─ EMAIL ALERTS                                         │
│     └─ Critical events trigger emails                    │
│        ├─ Admin notified immediately                     │
│        └─ Emergency response enabled                     │
│                                                            │
│  CONTEXT CAPTURED:                                       │
│  ├─ User ID & login                                      │
│  ├─ IP address                                           │
│  ├─ Current page URL                                     │
│  ├─ Action/operation performed                           │
│  ├─ Timestamp (microsecond precision)                    │
│  ├─ Stack trace (for errors)                             │
│  └─ Custom context data                                  │
│                                                            │
│  EXAMPLES:                                               │
│  ├─ User login (info)                                    │
│  ├─ Permission check passed/failed (info/warning)        │
│  ├─ Validation error (warning)                           │
│  ├─ Database error (error)                               │
│  ├─ Security threat (critical/alert)                     │
│  ├─ Plugin activation/deactivation (info)                │
│  └─ AJAX operation (info)                                │
│                                                            │
└──────────────────────────────────────────────────────────┘
```

---

## 🗂️ Database Schema

```
┌─────────────────────────────────────────────────────────┐
│                   DATABASE TABLES                        │
├─────────────────────────────────────────────────────────┤
│                                                           │
│  EXISTING WORDPRESS TABLES:                             │
│  ├─ wp_posts (Posts, pages, custom post types)          │
│  ├─ wp_postmeta (Post metadata)                         │
│  ├─ wp_users (User accounts)                            │
│  ├─ wp_usermeta (User metadata)                         │
│  ├─ wp_options (WordPress options/settings)             │
│  └─ wp_capabilities (User roles & capabilities)         │
│                                                           │
│  BKGT TABLES (New):                                     │
│                                                           │
│  wp_bkgt_logs                                           │
│  ├─ id (Primary key)                                    │
│  ├─ timestamp (When logged)                             │
│  ├─ level (debug/info/warning/error/critical)           │
│  ├─ message (Log message)                               │
│  ├─ user_id (Who performed action)                      │
│  ├─ user_login (Username)                               │
│  ├─ ip_address (Client IP)                              │
│  ├─ page_url (What page)                                │
│  ├─ action (What action)                                │
│  ├─ context (JSON context data)                         │
│  └─ indexes (On timestamp, level, user_id for speed)    │
│                                                           │
│  CACHING (In-memory):                                   │
│  └─ Query cache (MD5 key, 1 hour TTL)                   │
│     └─ Stores: Recent query results                     │
│        └─ Improves: Performance on repeated queries      │
│                                                           │
└─────────────────────────────────────────────────────────┘
```

---

## 👥 Permission Model

```
┌────────────────────────────────────────────────────────────┐
│                  PERMISSION STRUCTURE                       │
├────────────────────────────────────────────────────────────┤
│                                                              │
│  ROLE 1: ADMIN / STYRELSEMEDLEM (Board Member)             │
│  ├─ Capabilities:                                          │
│  │  ├─ view_inventory ✅                                  │
│  │  ├─ edit_inventory ✅                                  │
│  │  ├─ delete_inventory ✅                                │
│  │  ├─ upload_documents ✅                                │
│  │  ├─ edit_documents ✅                                  │
│  │  ├─ delete_documents ✅                                │
│  │  ├─ view_team_data ✅                                  │
│  │  ├─ edit_team_data ✅                                  │
│  │  ├─ admin_access ✅                                    │
│  │  ├─ manage_users ✅                                    │
│  │  ├─ manage_roles ✅                                    │
│  │  ├─ view_reports ✅                                    │
│  │  └─ send_messages ✅                                   │
│  │                                                          │
│  └─ Access Scope: ALL TEAMS                               │
│                                                              │
│  ROLE 2: COACH / TRÄNARE (Coach/Trainer)                   │
│  ├─ Capabilities:                                          │
│  │  ├─ view_inventory ✅                                  │
│  │  ├─ upload_documents ✅                                │
│  │  ├─ view_team_data ✅                                  │
│  │  ├─ edit_team_data ✅ (own team only)                  │
│  │  ├─ view_reports ✅                                    │
│  │  ├─ send_messages ✅                                   │
│  │  ├─ edit_documents ✅ (own team only)                  │
│  │  ├─ delete_documents ❌ (no)                           │
│  │  ├─ admin_access ❌ (no)                               │
│  │  └─ manage_users ❌ (no)                               │
│  │                                                          │
│  └─ Access Scope: ASSIGNED TEAMS ONLY                     │
│                                                              │
│  ROLE 3: TEAM MANAGER / LAGLEDARE (Team Manager)           │
│  ├─ Capabilities:                                          │
│  │  ├─ view_inventory ✅                                  │
│  │  ├─ edit_inventory ✅ (own team)                       │
│  │  ├─ upload_documents ✅                                │
│  │  ├─ view_team_data ✅                                  │
│  │  ├─ edit_team_data ✅ (own team)                       │
│  │  ├─ send_messages ✅                                   │
│  │  ├─ view_reports ✅                                    │
│  │  ├─ edit_documents ✅ (own team)                       │
│  │  ├─ delete_documents ❌ (no)                           │
│  │  ├─ admin_access ❌ (no)                               │
│  │  └─ manage_users ❌ (no)                               │
│  │                                                          │
│  └─ Access Scope: ASSIGNED TEAMS ONLY                     │
│                                                              │
│  ENFORCEMENT:                                              │
│  └─ All operations: bkgt_can('capability_name')           │
│     ├─ Returns: true if allowed, false if denied          │
│     ├─ Logs: All checks logged                            │
│     ├─ Scope: Team-aware for Team Managers/Coaches        │
│     └─ Speed: Cached for performance                      │
│                                                              │
└────────────────────────────────────────────────────────────┘
```

---

## 🔀 Data Flow: Complete Request Lifecycle

```
TIME: 0ms
├─ User clicks button in browser

TIME: 1ms
├─ JavaScript sends AJAX request with:
│  ├─ action: plugin_action_name
│  ├─ nonce: auto-generated security token
│  ├─ data: form data {...}

TIME: 5ms
├─ WordPress routes to plugin's AJAX handler
├─ Handler function triggered

TIME: 6ms
├─ VERIFY SECURITY (First thing, always)
│  ├─ bkgt_validate('nonce', $_POST['_wpnonce'])
│  │  └─ BKGT_Validator::verify_nonce()
│  │     └─ bkgt_log('info', 'Nonce verified')
│  │        └─ Stored to: file log + DB + dashboard
│  │
│  ├─ bkgt_can('required_capability')
│  │  └─ BKGT_Permission::check_capability()
│  │     └─ bkgt_log('info', 'Permission check: PASS')
│  │        └─ Stored to: file log + DB + dashboard

TIME: 8ms
├─ VALIDATE INPUT (Second, before processing)
│  ├─ $email = bkgt_validate('email', $_POST['email'])
│  │  └─ BKGT_Validator::validate_email()
│  │     └─ bkgt_log('info', 'Email validated')

TIME: 10ms
├─ PROCESS DATA (Now safe to process)
│  ├─ $results = bkgt_db()->get_posts(...)
│  │  └─ BKGT_Database::get_posts()
│  │     ├─ Check cache → MD5('get_posts_...')
│  │     ├─ Cache hit → return cached (1ms)
│  │     └─ Cache miss → query DB (100ms)
│  │        ├─ $wpdb->prepare("SELECT ... WHERE id = %d", $id)
│  │        ├─ Execute query
│  │        ├─ Return results
│  │        ├─ Cache results
│  │        └─ bkgt_log('info', 'Query completed')

TIME: 110ms
├─ PROCESS RESULTS (Transform if needed)
│  ├─ Format data for response
│  ├─ Escape output if needed
│  ├─ Prepare JSON response

TIME: 112ms
├─ LOG COMPLETION
│  ├─ bkgt_log('info', 'AJAX action completed', [
│  │     'action' => 'plugin_action_name',
│  │     'result' => 'success',
│  │     'records' => count($results),
│  │     'duration_ms' => 112
│  │ ])
│  │  ├─ Written to: bkgt-logs.log
│  │  ├─ Written to: wp_bkgt_logs table
│  │  ├─ Displayed in: admin dashboard
│  │  └─ Alert sent: (if critical)

TIME: 115ms
├─ RETURN RESULT
│  ├─ wp_send_json_success([
│  │     'message' => 'Operation completed',
│  │     'data' => $results,
│  │     'count' => count($results)
│  │ ])

TIME: 120ms
└─ Browser receives response
   ├─ Parse JSON
   ├─ Update DOM
   ├─ Display to user
   └─ Complete

TOTAL TIME: 120ms (from user click to display)
SECURITY CHECKS: ✅ 6 (nonce, permission, validation, escaping, logging, audit)
LOG ENTRIES: 4 (nonce, permission, operation, completion)
```

---

## 🎯 Integration Points

```
BKGT System Integration

┌─ Plugin AJAX Handler
│  └─ Step 1: Nonce Verification
│     └─ Calls: bkgt_validate('nonce', $_POST['_wpnonce'])
│
├─ Permission Enforcement  
│  └─ Step 2: Permission Check
│     └─ Calls: bkgt_can('required_capability')
│
├─ Input Processing
│  └─ Step 3: Input Validation
│     └─ Calls: bkgt_validate('type', $_POST['value'])
│
├─ Database Operations
│  └─ Step 4: Process Data
│     └─ Calls: bkgt_db()->method(...)
│
└─ Audit Logging
   └─ Step 5: Log Everything
      └─ Calls: bkgt_log('level', 'message', [...])

Standard Pattern (Used in all 7 plugins):
├─ All AJAX handlers follow same pattern
├─ All operations go through security layers
├─ All data logged for audit trail
└─ All errors handled gracefully
```

---

## 📦 Deployment Architecture

```
┌────────────────────────────────────────────────────────┐
│              PRODUCTION DEPLOYMENT LAYERS              │
├────────────────────────────────────────────────────────┤
│                                                         │
│  LAYER 1: DATABASE                                     │
│  ├─ Backed up before deployment                        │
│  ├─ BKGT tables created                                │
│  ├─ Optimized with indexes                             │
│  └─ Ready for logging                                  │
│                                                         │
│  LAYER 2: BKGT CORE                                    │
│  ├─ Deployed to wp-content/plugins/bkgt-core/          │
│  ├─ Activated first                                    │
│  ├─ All core systems loaded                            │
│  └─ Helper functions available                         │
│                                                         │
│  LAYER 3: 7 PLUGINS                                    │
│  ├─ bkgt-inventory                                     │
│  ├─ bkgt-document-management                           │
│  ├─ bkgt-team-player                                   │
│  ├─ bkgt-user-management                               │
│  ├─ bkgt-communication                                 │
│  ├─ bkgt-offboarding                                   │
│  └─ bkgt-data-scraping                                 │
│                                                         │
│  LAYER 4: VERIFICATION                                 │
│  ├─ All systems tested                                 │
│  ├─ Logs verified working                              │
│  ├─ Permissions enforced                               │
│  └─ Performance baseline met                           │
│                                                         │
│  LAYER 5: MONITORING                                   │
│  ├─ Real-time log monitoring                           │
│  ├─ Performance tracking                               │
│  ├─ Error alert setup                                  │
│  └─ User feedback collection                           │
│                                                         │
└────────────────────────────────────────────────────────┘
```

---

## 🎓 Knowledge Hierarchy

```
Level 1: User
└─ Read: PHASE1_PRODUCTION_READY.md (5 min)
   └─ Understand: What's been built

Level 2: Support/Ops
└─ Read: +BKGT_TROUBLESHOOTING_GUIDE.md (20 min)
   └─ Understand: How to fix problems

Level 3: QA/Tester
└─ Read: +PHASE1_INTEGRATION_TESTING_GUIDE.md (1 hour)
   └─ Understand: How to test system

Level 4: Junior Developer
└─ Read: +BKGT_CORE_QUICK_REFERENCE.md (30 min)
└─ Read: +INTEGRATION_GUIDE.md (20 min)
   └─ Understand: How to use/extend system

Level 5: Senior Developer
└─ Read: +BKGT_CORE_IMPLEMENTATION.md (30 min)
└─ Read: +Plugin integration docs (1 hour)
   └─ Understand: Architecture & design patterns

Level 6: Architect
└─ Read: +PHASE1_COMPLETE_FINAL_SUMMARY.md (30 min)
└─ Read: +IMPLEMENTATION_AUDIT.md (20 min)
   └─ Understand: Strategic decisions & roadmap
```

---

**System is ready for deployment! 🚀**
