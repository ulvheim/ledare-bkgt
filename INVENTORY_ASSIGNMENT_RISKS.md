# Equipment Assignment System - Risk Analysis & Remediation Plan

**Date:** November 14, 2025  
**Status:** CRITICAL - Issues Must Be Fixed Before Production Equipment Assignment  
**Risk Level:** 🔴 HIGH - Data Loss & System Failures Possible

---

## Executive Summary

The equipment assignment system has **critical database schema mismatches** that will cause failures when:
- Equipment is assigned to coaches/players from svenskalag sync
- Coaches/players are renamed or deleted in svenskalag
- Teams are modified or deleted from team management

**The schema defined in class-database.php does NOT match what class-assignment.php expects**, creating a **SQL errors** when assignment code runs.

---

## Critical Issues Identified

### 1. ⚠️ DATABASE SCHEMA MISMATCH (BLOCKER)

**Location:** `wp-content/plugins/bkgt-inventory/includes/class-database.php` (Lines 111-127)

**Current Schema:**
```sql
CREATE TABLE wp_bkgt_inventory_assignments (
    id int(11) NOT NULL AUTO_INCREMENT,
    item_id bigint(20) NOT NULL,
    assignee_id bigint(20) DEFAULT NULL,
    assignee_name varchar(255) DEFAULT NULL,
    assignment_date datetime DEFAULT CURRENT_TIMESTAMP,
    due_date date DEFAULT NULL,
    return_date date DEFAULT NULL,
    location_id int(11) DEFAULT NULL,
    notes text,
    PRIMARY KEY (id),
    KEY item_id (item_id),
    KEY assignee_id (assignee_id),
    KEY assignment_date (assignment_date),
    KEY due_date (due_date)
) charset;
```

**What class-assignment.php Expects:**
- ❌ **Missing:** `assignee_type` ENUM('location', 'team', 'user')
- ❌ **Missing:** `assigned_date` field (uses `assignment_date` instead - inconsistent naming)
- ❌ **Missing:** `assigned_by` int(11) - Who created the assignment
- ❌ **Missing:** `unassigned_date` datetime - When was it unassigned
- ❌ **Missing:** `unassigned_by` int(11) - Who unassigned it

**Impact:**
- Lines 121, 124, 126 of class-assignment.php will INSERT into non-existent columns → SQL errors
- Lines 182, 204 will SELECT from non-existent column → SQL errors
- Lines 290, 298, 304 filter queries using `assignee_type` → Empty results
- Assignment creation/updates **WILL FAIL**

---

### 2. ⚠️ NO FOREIGN KEY CONSTRAINTS

**Missing Constraints:**
```sql
-- Should exist but are missing:
ALTER TABLE wp_bkgt_inventory_assignments 
ADD CONSTRAINT fk_assignment_item 
  FOREIGN KEY (item_id) REFERENCES wp_bkgt_inventory_items(id) 
  ON DELETE CASCADE;

ALTER TABLE wp_bkgt_inventory_assignments 
ADD CONSTRAINT fk_assignment_user 
  FOREIGN KEY (assignee_id) REFERENCES wp_users(ID) 
  ON DELETE CASCADE;
```

**Risk:** 
- Deleting a user (coach) synced from svenskalag leaves **orphaned assignment records**
- Deleting equipment leaves assignment records pointing to non-existent items
- No CASCADE behavior = manual cleanup required
- **Data integrity cannot be guaranteed**

---

### 3. ⚠️ NO VALIDATION FOR ASSIGNEE EXISTENCE

**Location:** `class-assignment.php` Lines 64-100

**Current Code:**
```php
// Assigns to user without verifying they still exist
$assignee_id = $assignment_id;  
$assignee_type = 'user';

// INSERT into assignments table
$result = $wpdb->insert($assignments_table, array(
    'assignee_id' => $assignee_id,  // Could be deleted user ID!
    'assignee_type' => $assignee_type,
    ...
));
```

**Scenario That Breaks:**
1. Assign equipment to "Coach Anna" (user ID 15) from svenskalag
2. Coach Anna is deleted from svenskalag sync
3. Equipment is still assigned to non-existent user 15
4. `get_assignment()` tries to call `get_userdata(15)` → Returns null/false → Shows "Unknown user"

---

### 4. ⚠️ MISSING INDEXES FOR QUERY PERFORMANCE

**Current Indexes:**
- ✅ `item_id` - Good
- ✅ `assignee_id` - Good
- ✅ `assignment_date` - Good
- ❌ **Missing:** `assignee_type` - Needed for filtering by assignment type
- ❌ **Missing:** `unassigned_date` - Needed for "active assignments" queries
- ❌ **Missing:** Composite index on `(item_id, unassigned_date)` - Frequently used together

**Impact:** Slow queries when retrieving assignments by type or filtering active assignments

---

### 5. ⚠️ NO HANDLING OF TEAM CHANGES FROM SVENSKALAG

**Scenario That Could Break:**
1. Equipment assigned to "Team A" (team_id = 5)
2. Team is deleted or renamed in svenskalag sync
3. Equipment assignment still references team_id=5
4. `BKGT_Team::get_team(5)` returns null → Shows "Unknown team"

**Missing Validation:**
```php
// class-assignment.php line 37-44 - Only validates team exists AT ASSIGNMENT TIME
// But doesn't check if team still exists when RETRIEVING assignment

if ($assignment_type === self::TYPE_TEAM && class_exists('BKGT_Team')) {
    $team = BKGT_Team::get_team($assignment_id);
    if (!$team) {
        return new WP_Error('team_not_found', ...);
    }
}
```

---

### 6. ⚠️ CASCADING DELETE ISSUES

**What Happens If:**

| Scenario | Current Behavior | Expected Behavior |
|----------|------------------|-------------------|
| User deleted from SQLs | Orphaned assignment record remains | Should auto-unassign or delete |
| Team deleted | Orphaned assignment record remains | Should auto-unassign or delete |
| Equipment deleted | Orphaned assignment record remains | Should auto-delete with item |
| Location deleted | Orphaned assignment record remains | Should auto-unassign |

**Risk:** Over time, hundreds of orphaned records accumulate

---

### 7. ⚠️ UNASSIGN OPERATION NOT ATOMIC

**Location:** `class-assignment.php` Lines 100-131

```php
// Problem: Two separate operations, not transactional
// If UPDATE succeeds but INSERT fails, item is unassigned but not reassigned

$wpdb->update(...); // Unassign old
// <-- FAILURE POINT: If next operation fails...
$wpdb->insert(...); // Assign new
```

**Risk:** Equipment left in "unassigned" state if assignment fails partway through

---

## Detailed Failure Scenarios

### Scenario A: Equipment Assigned to Coach via API

**Step 1:** Frontend calls API to assign equipment to "Coach Anna"
```javascript
PUT /wp-json/bkgt/v1/equipment/27
{ 
  assignee_type: 'user',
  assignee_id: 15  // Anna's user ID
}
```

**Step 2:** API tries to INSERT:
```php
$wpdb->insert($assignments_table, array(
    'assignee_type' => 'user',    // ✅ Column exists
    'assignee_id' => 15,           // ✅ Column exists
    'assigned_by' => 32,           // ❌ COLUMN DOESN'T EXIST
    'assigned_date' => '2025-11-14 10:00:00',  // ❌ COLUMN DOESN'T EXIST
    'unassigned_by' => null,       // ❌ COLUMN DOESN'T EXIST
    ...
));
```

**Result:** SQL Error → Request fails → UI shows error to user

---

### Scenario B: Coach Deleted from Svenskalag Sync

**Before:** Equipment assigned to Coach Anna (user_id=15)
- Equipment record has `assignee_id = 15, assignee_type = 'user'`
- Anna shown as assignee in UI

**After:** Anna is deleted from svenskalag, sync runs
- No cascade delete rule exists
- Equipment assignment still references user_id=15
- Next time equipment is viewed: `get_userdata(15)` returns null
- UI shows "Unknown user" for assignee
- If admin tries to reassign: Works, but orphaned record remains

---

### Scenario C: Team Renamed in Svenskalag

**Before:** Equipment assigned to "U19 Boys Team" (team_id=5)

**After:** Team renamed to "U19 Elite" but ID still = 5
- Should still work fine because ID hasn't changed

**But if team is DELETED:**
- Equipment still assigned to team_id=5
- `BKGT_Team::get_team(5)` returns null
- Shows "Unknown team"
- No automatic cleanup

---

## Database Migration Required

### Step 1: Add Missing Columns

```sql
ALTER TABLE wp_bkgt_inventory_assignments 
ADD COLUMN assignee_type enum('location','team','user') NOT NULL DEFAULT 'location' AFTER assignee_id;

ALTER TABLE wp_bkgt_inventory_assignments 
ADD COLUMN assigned_by int(11) NOT NULL DEFAULT 0 AFTER assignment_date;

ALTER TABLE wp_bkgt_inventory_assignments 
ADD COLUMN unassigned_date datetime NULL AFTER return_date;

ALTER TABLE wp_bkgt_inventory_assignments 
ADD COLUMN unassigned_by int(11) NULL AFTER unassigned_date;

-- Rename for consistency (optional but recommended)
-- ALTER TABLE wp_bkgt_inventory_assignments 
-- CHANGE COLUMN assignment_date assigned_date datetime DEFAULT CURRENT_TIMESTAMP;
```

### Step 2: Add Foreign Key Constraints

```sql
ALTER TABLE wp_bkgt_inventory_assignments 
ADD CONSTRAINT fk_assignment_item 
  FOREIGN KEY (item_id) REFERENCES wp_bkgt_inventory_items(id) 
  ON DELETE CASCADE;

ALTER TABLE wp_bkgt_inventory_assignments 
ADD CONSTRAINT fk_assignment_user 
  FOREIGN KEY (assignee_id) REFERENCES wp_users(ID) 
  ON DELETE CASCADE;

-- For team assignments, would need:
-- ALTER TABLE wp_bkgt_inventory_assignments 
-- ADD CONSTRAINT fk_assignment_team 
--   FOREIGN KEY (assignee_id) REFERENCES wp_bkgt_teams(id) 
--   ON DELETE CASCADE
-- But this is COMPLEX because same column holds multiple FK types!
```

### Step 3: Add Indexes

```sql
ALTER TABLE wp_bkgt_inventory_assignments 
ADD INDEX idx_assignee_type (assignee_type);

ALTER TABLE wp_bkgt_inventory_assignments 
ADD INDEX idx_unassigned_date (unassigned_date);

ALTER TABLE wp_bkgt_inventory_assignments 
ADD INDEX idx_active_assignments (item_id, unassigned_date);
```

### Step 4: Update Database Version

```php
// In class-database.php, add migration function
public function migrate_assignment_schema() {
    global $wpdb;
    
    $assignments_table = $this->assignments_table;
    
    // 1. Add missing columns if they don't exist
    $columns = $wpdb->get_col("DESCRIBE {$assignments_table}");
    
    if (!in_array('assignee_type', $columns)) {
        $wpdb->query("ALTER TABLE {$assignments_table} 
            ADD COLUMN assignee_type enum('location','team','user') NOT NULL DEFAULT 'location'");
    }
    
    if (!in_array('assigned_by', $columns)) {
        $wpdb->query("ALTER TABLE {$assignments_table} 
            ADD COLUMN assigned_by int(11) NOT NULL DEFAULT 0");
    }
    
    if (!in_array('unassigned_date', $columns)) {
        $wpdb->query("ALTER TABLE {$assignments_table} 
            ADD COLUMN unassigned_date datetime NULL");
    }
    
    if (!in_array('unassigned_by', $columns)) {
        $wpdb->query("ALTER TABLE {$assignments_table} 
            ADD COLUMN unassigned_by int(11) NULL");
    }
    
    // 2. Add indexes
    // 3. Update version
    update_option('bkgt_inventory_db_version', '1.4.0');
}
```

---

## Validation Checklist Before Assignment Feature Launch

- [ ] Database migration executed on production
- [ ] All missing columns exist in assignments table
- [ ] Foreign key constraints enforced
- [ ] Test: Assign equipment to coach, verify record created correctly
- [ ] Test: Delete coach from system, verify assignment handled properly
- [ ] Test: Assign equipment to team, verify team assignments work
- [ ] Test: Delete team from system, verify assignment cleanup
- [ ] Test: Query active assignments for user/team, verify indexes used
- [ ] Test: Unassign equipment mid-operation, verify atomic transaction
- [ ] Load test: 1000+ assignments, check query performance

---

## Recommended Fixes (Priority Order)

### Priority 1 (BLOCKER) - Must Fix Before Any Assignment Operations
1. ✅ Add missing columns to assignments table
2. ✅ Update database schema version check in class-database.php
3. ✅ Add migration function to plugin activation

### Priority 2 (HIGH) - Must Fix Before Production Launch
4. Add foreign key constraints
5. Add indexes for query performance
6. Add validation that assignee exists when retrieving assignments
7. Add cascade delete handlers

### Priority 3 (MEDIUM) - Should Fix Soon
8. Make assignment create/unassign fully transactional
9. Add audit logging for assignment changes
10. Add assignment history table for tracking changes

---

## Files Affected

- ✅ `wp-content/plugins/bkgt-inventory/includes/class-database.php` - Schema definition
- ✅ `wp-content/plugins/bkgt-inventory/includes/class-assignment.php` - Assignment logic
- ✅ `wp-content/plugins/bkgt-api/includes/class-bkgt-endpoints.php` - API endpoints
- ⚠️ Any code calling `BKGT_Assignment::assign_to_*()` methods

---

## Next Steps

1. **Immediate:** Create migration script and test on staging
2. **Day 1:** Deploy migration to production before assignment feature goes live
3. **Day 1:** Add validation checks to prevent orphaned records
4. **Week 1:** Add cascade delete handlers
5. **Week 2:** Add unit tests for assignment system

