# 📦 DEPLOYMENT FILES - Ready to Upload

**Deployment Date:** November 4, 2025  
**Target Server:** ledare.bkgt.se  
**Plugin:** BKGT Document Management v1.0.0  
**Total Files:** 3 core files  

---

## Files to Upload via SFTP

### Remote Path: `/public_html/wp-content/plugins/bkgt-document-management/`

#### 1. **bkgt-document-management.php** (MAIN PLUGIN FILE)
- **Status:** ✅ UPDATED
- **Size:** ~7.5 KB
- **Lines:** 223
- **Changes:**
  - Shortcode delegates to frontend class
  - AJAX handlers delegate to frontend methods
  - Frontend class loading added
- **Upload:** YES

#### 2. **frontend/class-frontend.php** (FRONTEND CLASS)
- **Status:** ✅ UPDATED  
- **Size:** ~16 KB
- **Lines:** 497
- **Changes:**
  - Singleton pattern added (get_instance method)
  - New: ajax_edit_user_document() method
  - Updated: ajax_delete_user_document() with better error handling
  - Nonce field added to HTML
- **Upload:** YES
- **Path:** `frontend/class-frontend.php`

#### 3. **assets/js/frontend.js** (JAVASCRIPT)
- **Status:** ✅ UPDATED
- **Size:** ~26 KB
- **Lines:** 794
- **Changes:**
  - Dashboard tab navigation (+~160 lines)
  - Template loading
  - Document creation modal
  - **NEW: Edit functionality** (+~127 lines)
  - Document deletion with confirmation
  - AJAX handlers for all operations
- **Upload:** YES
- **Path:** `assets/js/frontend.js`

---

## Files NOT Modified (No Need to Upload)

### Existing Files (Already in Production)
- ✅ `assets/css/frontend.css` (638 lines) - No changes needed
- ✅ `admin/class-admin.php` - No changes
- ✅ `includes/class-*.php` (all files) - No changes
- ✅ All other files - No changes

---

## Optional Documentation Updates

### Consider Uploading to Root Directory

```
ledare-bkgt/
├── SYSTEM_ARCHITECTURE.md              (Update - Coach permissions)
├── COACH_DOCUMENT_EDITING.md           (New - Feature documentation)
└── DEPLOYMENT_PACKAGE_README.md        (New - This file)
```

**Note:** These are documentation only and don't affect functionality. Update them if you want to document the changes.

---

## Quick Deployment Command

### For SFTP Upload:

```bash
# Connect to production server
sftp user@ledare.bkgt.se

# Navigate to plugin directory
cd /public_html/wp-content/plugins/bkgt-document-management/

# Upload core files
put bkgt-document-management.php
put frontend/class-frontend.php
put assets/js/frontend.js

# Verify uploads
ls -la bkgt-document-management.php
ls -la frontend/class-frontend.php
ls -la assets/js/frontend.js

# Exit
exit
```

### Alternative: Manual Upload Steps

1. Connect to ledare.bkgt.se via SFTP (FileZilla, WinSCP, etc.)
2. Navigate to: `/public_html/wp-content/plugins/bkgt-document-management/`
3. Drag and drop these 3 files:
   - `bkgt-document-management.php`
   - `frontend/class-frontend.php`
   - `assets/js/frontend.js`
4. Confirm overwrite when prompted
5. Verify file sizes match expected

---

## File Verification Checklist

After uploading, verify files on server:

```bash
ssh user@ledare.bkgt.se

# Check file exists and sizes
ls -lh /public_html/wp-content/plugins/bkgt-document-management/bkgt-document-management.php
# Expected: ~7-8 KB

ls -lh /public_html/wp-content/plugins/bkgt-document-management/frontend/class-frontend.php
# Expected: ~15-17 KB

ls -lh /public_html/wp-content/plugins/bkgt-document-management/assets/js/frontend.js
# Expected: ~25-27 KB

# Check PHP syntax (if PHP CLI available)
php -l /public_html/wp-content/plugins/bkgt-document-management/bkgt-document-management.php
# Expected: No syntax errors

php -l /public_html/wp-content/plugins/bkgt-document-management/frontend/class-frontend.php
# Expected: No syntax errors
```

---

## WordPress Plugin Activation Steps

After files are uploaded:

1. **Log into WordPress Admin**
   - URL: `https://ledare.bkgt.se/wp-admin`
   - Use admin credentials

2. **Navigate to Plugins**
   - Menu: Plugins → Installed Plugins

3. **Find "BKGT Document Management"**
   - Look for the plugin in the list

4. **Deactivate Plugin**
   - Click "Deactivate" button
   - Wait for confirmation (5-10 seconds)

5. **Activate Plugin**
   - Click "Activate" button
   - Wait for activation complete (5-10 seconds)
   - Should see "Plugin activated" message

6. **Verify Activation**
   - Plugin should show "Deactivate" button (not "Activate")
   - No error messages in admin

---

## Testing After Deployment

### Quick 5-Minute Test

1. **Find page with shortcode**
   - Navigate to page containing `[bkgt_documents]`

2. **Test as Coach User**
   - Log in as coach
   - Dashboard appears with 2 tabs ✓
   - "Mina dokument" shows documents ✓
   - "Mallar" shows 3 templates ✓
   - Create document from template ✓
   - Edit document works ✓
   - Delete document works ✓

3. **Check Errors**
   - Open browser console (F12)
   - No red errors ✓
   - Check WordPress debug log: `/wp-content/debug.log`
   - No new errors related to documents ✓

---

## Rollback Instructions (If Needed)

If anything goes wrong:

### Step 1: Restore from Backup
```bash
# Via SFTP - restore previous version
sftp user@ledare.bkgt.se
cd /public_html/wp-content/plugins/

# Delete broken version
rm -rf bkgt-document-management

# Copy from backup (if you created one)
# Or reupload previous working files
```

### Step 2: Reactivate Plugin
1. Log into WordPress Admin
2. Plugins → Installed Plugins
3. Click "Activate" next to "BKGT Document Management"
4. Verify it works

### Step 3: Verify
- Check error logs
- Test document dashboard
- Confirm features working

---

## Deployment Checklist

### Pre-Upload
- [x] Files backed up locally
- [x] PHP syntax verified
- [x] Security checks passed
- [x] File sizes confirmed
- [x] WordPress plugin deactivated (ready for reactivation)

### Upload Phase
- [ ] Connect to SFTP
- [ ] Navigate to plugin directory
- [ ] Upload 3 files
- [ ] Verify file permissions (755 for dirs, 644 for files)
- [ ] Confirm file sizes match

### Activation Phase
- [ ] Log into WordPress admin
- [ ] Find BKGT Document Management plugin
- [ ] Deactivate (if active)
- [ ] Activate
- [ ] Verify no errors

### Testing Phase
- [ ] Test as coach
- [ ] Create test document
- [ ] Edit test document
- [ ] Delete test document
- [ ] Check error logs
- [ ] Test as multiple users

### Sign-Off
- [ ] All tests passed
- [ ] No errors in logs
- [ ] Feature working as expected
- [ ] Ready for users

---

## Important Notes

⚠️ **Make sure to:**
1. Upload to correct directory: `/public_html/wp-content/plugins/bkgt-document-management/`
2. Maintain file structure (frontend/class-frontend.php, assets/js/frontend.js)
3. Deactivate plugin BEFORE uploading (recommended)
4. Activate plugin AFTER uploading
5. Check error logs after activation

✅ **If using SFTP:**
- Use binary mode (not ASCII)
- Preserve file permissions
- Verify uploads before activating

✅ **If issues occur:**
- Check `/wp-content/debug.log`
- Check `/wp-content/bkgt-logs/`
- Restore backup if needed
- Contact support with error logs

---

## What Happens When Deployed

### Immediately After Activation

1. **New AJAX Handlers Registered**
   - `bkgt_edit_user_document` - Document editing
   - Plus 6 other handlers (already existed)

2. **Frontend Updates**
   - Edit button appears on documents
   - Edit modal becomes available
   - New JavaScript functions active

3. **Permissions Updated**
   - Coaches can now edit team documents
   - Team managers can edit team documents
   - Authors always can edit own documents

### For Users

- 🎯 New "Redigera" (Edit) button on each document
- 🎯 Edit modal opens to modify content
- 🎯 Coaches can edit team documents
- 🎯 Improved error messages

### For Admins

- 📊 Can monitor document editing in logs
- 📊 Can see who edited what and when
- 📊 Access control enforced by team
- 📊 All AJAX calls logged

---

## Success Indicators

After deployment, you should see:

✅ Plugin activates without errors  
✅ Dashboard loads with 2 tabs  
✅ Documents can be created from templates  
✅ Documents can be edited (by coaches/authors)  
✅ Documents can be deleted (author only)  
✅ Edit modal opens and closes properly  
✅ No JavaScript console errors  
✅ No PHP errors in error logs  

---

## Contact & Support

**During Deployment:**
- Check error logs: `/wp-content/debug.log`
- Review docs: `DEPLOYMENT_CHECKLIST.md`
- Rollback if needed (see instructions above)

**After Deployment:**
- Reference `COACH_DOCUMENT_EDITING.md` for feature details
- Check `SYSTEM_ARCHITECTURE.md` for permission model
- Monitor error logs for issues

---

## Summary

**3 files to upload:**
1. `bkgt-document-management.php`
2. `frontend/class-frontend.php`
3. `assets/js/frontend.js`

**To:** `/public_html/wp-content/plugins/bkgt-document-management/`

**Then:** Deactivate and reactivate plugin in WordPress admin

**Result:** Users can create, view, edit, and manage documents!

---

**Ready to deploy!** 🚀
