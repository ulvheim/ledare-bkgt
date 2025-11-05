# PHASE 1 INTEGRATION - bkgt-inventory Plugin Update

## Overview

This document outlines the integration of BKGT Core systems into the bkgt-inventory plugin.

## Tasks

### 1. Main Plugin File (bkgt-inventory.php)
- [ ] Add BKGT Core as required plugin dependency
- [ ] Replace database initialization with BKGT_Database
- [ ] Add BKGT Core dependency check in activation
- [ ] Replace error handling with bkgt_log()
- [ ] Replace permission checks with bkgt_can()
- [ ] Replace input validation with bkgt_validate()
- [ ] Update AJAX handler to use new systems

### 2. Admin Classes (admin/class-admin.php, admin/class-item-admin.php)
- [ ] Add permission checks at start of admin pages
- [ ] Replace error handling with bkgt_log()
- [ ] Add input validation to forms
- [ ] Update AJAX handlers for security
- [ ] Add validation to all AJAX endpoints

### 3. Core Classes (includes/*)
- [ ] Update database queries to use bkgt_db()
- [ ] Add input validation before database operations
- [ ] Add logging for important operations
- [ ] Replace error_log with bkgt_log()
- [ ] Standardize error handling

### 4. Testing
- [ ] Test with Admin role
- [ ] Test with Coach role
- [ ] Test with Team Manager role
- [ ] Verify all AJAX endpoints work
- [ ] Check logs for errors

## Current Status

Starting integration with bkgt-inventory plugin.

