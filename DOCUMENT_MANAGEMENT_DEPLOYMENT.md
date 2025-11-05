# Document Management Frontend - Deployment Guide

## Overview
The document management plugin has been enhanced with a comprehensive user-facing frontend. Users can now create documents from templates, browse their documents, and manage them directly from the front-end.

## Changes Made

### 1. **Frontend Class Implementation**
**File:** `wp-content/plugins/bkgt-document-management/frontend/class-frontend.php`
- **Status:** ✅ Implemented (424 lines)
- **Features:**
  - Dashboard UI with tabbed navigation
  - User document browser with search
  - Template system with variable substitution
  - Full CRUD operations (author-only access)
  - 7 AJAX handlers for frontend functionality

### 2. **Main Plugin File Updates**
**File:** `wp-content/plugins/bkgt-document-management/bkgt-document-management.php`
- **Changes:**
  - Updated `documents_shortcode()` to delegate to frontend class instead of returning stub
  - Updated AJAX handlers to delegate to frontend class
  - Added frontend class loading in `bkgt_document_management_init()`
  - Frontend class only loaded on front-end (not in WordPress admin)

### 3. **Frontend Assets Already Exist**
- **CSS:** `assets/css/frontend.css` (638 lines)
- **JavaScript:** `assets/js/frontend.js` (372 lines)
- Both files use WordPress standards with proper nonce/security handling

## Frontend Functionality

### User Dashboard (Two Tabs)

#### Tab 1: "Mina dokument" (My Documents)
- List of user's documents
- Search functionality
- Shows: title, date created, template source
- Actions: view, edit, delete, download
- Author-only access enforced

#### Tab 2: "Mallar" (Templates)
- Three default templates available:
  1. **"Mötesprotokolll"** (Meeting Minutes)
     - Variables: `{{MEETING_DATE}}`, `{{MEETING_TITLE}}`, `{{PARTICIPANTS}}`
  2. **"Rapport"** (Report)
     - Variables: `{{REPORT_TITLE}}`, `{{REPORT_DATE}}`, `{{AUTHOR}}`
  3. **"Brev"** (Letter)
     - Variables: `{{RECIPIENT_NAME}}`, `{{LETTER_DATE}}`

### Create Document Flow
1. User clicks "Nytt dokument från mall" button
2. Modal opens with template selector
3. User selects template
4. Dynamic form fields appear for each template variable
5. User enters document title and variable values
6. Document created as WordPress post with metadata
7. Variables are substituted in document content

## AJAX Handlers (Frontend)

All handlers include proper security:
- Nonce verification (`bkgt_document_nonce`)
- User authentication checks (`is_user_logged_in()`)
- Author-only access validation

| AJAX Action | Method | Purpose | Access |
|---|---|---|---|
| `bkgt_get_templates` | `ajax_get_templates()` | Returns list of available templates | All logged-in users |
| `bkgt_create_from_template` | `ajax_create_from_template()` | Creates document from template, replaces variables | All logged-in users |
| `bkgt_get_user_documents` | `ajax_get_user_documents()` | Lists current user's documents (50 limit) | Current user |
| `bkgt_get_document` | `ajax_get_document()` | Retrieves single document | Document author |
| `bkgt_delete_user_document` | `ajax_delete_user_document()` | Deletes document | Document author |
| `bkgt_download_document` | `ajax_download_document()` | Prepares document for download | Document author |

## Database Schema

### Custom Post Type: `bkgt_document`
- `post_type` = 'bkgt_document'
- `post_author` = User ID (for access control)
- `post_title` = Document title (user-provided)
- `post_content` = Document content (template with substituted variables)

### Post Metadata
- `_bkgt_template_id`: Which template was used
- `_bkgt_template_variables`: JSON of variable values used

## Security Features

✅ **Nonce Verification:** All AJAX calls require valid `bkgt_document_nonce`  
✅ **User Authentication:** Frontend only available to logged-in users  
✅ **Author-Only Access:** Users can only manage their own documents  
✅ **Capability Checks:** WordPress post author validation  
✅ **Content Escaping:** All output properly escaped with `esc_html`, `wp_kses`  

## Swedish Terminology

All user-facing text uses Swedish:
- "Dokumenthantering" - Document Management
- "Mina dokument" - My Documents
- "Mallar" - Templates
- "Nytt dokument från mall" - New Document from Template
- "Skapa dokument" - Create Document
- "Mötesprotokolll" - Meeting Minutes
- "Rapport" - Report
- "Brev" - Letter

## Deployment Steps

### 1. **Backup Current Installation**
```bash
cp -r wp-content/plugins/bkgt-document-management wp-content/plugins/bkgt-document-management.backup
```

### 2. **Upload Modified Files**
```
bkgt-document-management.php
frontend/class-frontend.php (new singleton wrapper added)
assets/css/frontend.css (exists)
assets/js/frontend.js (exists)
```

### 3. **Verify Plugin Activation**
- Navigate to WordPress admin > Plugins
- Confirm "BKGT Document Management" is active
- Check error logs for any issues

### 4. **Test Frontend Functionality**
1. Create or navigate to a page with `[bkgt_documents]` shortcode
2. Log in as a user
3. Verify dashboard appears with two tabs
4. Click "Nytt dokument från mall"
5. Select a template
6. Fill in the form (should show template variables as form fields)
7. Click "Skapa dokument"
8. Verify document appears in "Mina dokument" tab
9. Test document deletion
10. Test search functionality

### 5. **Post-Deployment Verification**
- Check browser console for JavaScript errors
- Check WordPress error logs
- Verify all AJAX calls completing successfully
- Test with multiple users
- Verify author-only access control

## Frontend Shortcode Usage

Add to any page or post:
```
[bkgt_documents]
```

Users must be logged in to see the dashboard. Logged-out users see:
> "Vänligen logga in för att komma åt dokumenthanteringen." (Please log in to access documents.)

## Troubleshooting

### Issue: Frontend not appearing
- **Check:** User is logged in
- **Check:** Shortcode is present on page: `[bkgt_documents]`
- **Check:** Browser console for JavaScript errors
- **Check:** WordPress error logs

### Issue: AJAX calls failing (403 Forbidden)
- **Check:** `bkgt_document_nonce` is properly set in `wp_localize_script`
- **Check:** Nonce verification code in AJAX handlers
- **Check:** User is logged in

### Issue: Documents not saving
- **Check:** Post type `bkgt_document` is registered
- **Check:** Database tables exist
- **Check:** WordPress has write permissions
- **Check:** Server error logs

### Issue: Variables not substituting
- **Check:** Template variables are in format `{{VARIABLE_NAME}}`
- **Check:** Form fields match template variables exactly
- **Check:** No trailing spaces in variable names

## Rollback Procedure

If issues occur:
```bash
rm -rf wp-content/plugins/bkgt-document-management
mv wp-content/plugins/bkgt-document-management.backup wp-content/plugins/bkgt-document-management
```

Then deactivate and reactivate the plugin in WordPress admin.

## Next Steps / Future Enhancements

### Phase 4 (Planned)
- ✅ User-facing dashboard with templates (COMPLETED)
- ⏳ Export to DOCX/PDF format
- ⏳ Document sharing and permissions
- ⏳ Version history and restore
- ⏳ Custom template creation by admins

### Known Limitations
- Download currently exports as plain text
- No document versioning yet
- No collaboration/sharing features
- Templates are hardcoded in PHP (could be made customizable in admin)

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review WordPress error logs: `wp-content/debug.log`
3. Check browser console for JavaScript errors (F12)
4. Review plugin-specific logs: `wp-content/bkgt-logs/`

## Completion Checklist

- [ ] Backup current installation
- [ ] Upload modified files
- [ ] Verify plugin activation
- [ ] Test all frontend features
- [ ] Verify AJAX communication
- [ ] Test with multiple users
- [ ] Verify author-only access
- [ ] Check browser console for errors
- [ ] Monitor error logs
- [ ] Communicate changes to team
