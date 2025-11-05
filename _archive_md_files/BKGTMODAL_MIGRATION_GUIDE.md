# BKGTModal Plugin Migration Guide

**Purpose:** Help developers migrate existing modal implementations to the unified BKGTModal component  
**Status:** ✅ Ready to Use  
**Created:** Session 4 (Current)

---

## 🎯 Quick Migration (5 minutes per plugin)

### Before (Old Code - ❌ Broken)
```javascript
// Create modal HTML inline
var modal = $('<div class="bkgt-modal"><div class="bkgt-modal-content">...');
$('body').append(modal);
modal.show();

// Manual close handling
modal.find('.bkgt-modal-close').on('click', function() {
    modal.remove();
});

// Click outside to close
$(document).on('click', function(e) {
    if ($(e.target).hasClass('bkgt-modal')) {
        modal.remove();
    }
});
```

### After (New Code - ✅ Production Ready)
```javascript
// Create modal instance once
var shareModal = new BKGTModal({
    id: 'share-modal',
    title: 'Dela dokument',
    size: 'medium'
});

// Wire button click
$(document).on('click', '.bkgt-share-document', function(e) {
    e.preventDefault();
    
    var documentId = $(this).data('document-id');
    var shareUrl = buildShareUrl(documentId);
    
    // Build content
    var content = '<p>Kopiera denna länk för att dela dokumentet:</p>' +
        '<input type="text" readonly value="' + shareUrl + '" />' +
        '<p><small>Denna länk kräver inloggning och rättigheter för att visa dokumentet.</small></p>';
    
    // Set and open
    shareModal.setContent(content);
    shareModal.setFooter('<button class="button" onclick="shareModal.close();">Stäng</button>');
    shareModal.open();
});
```

---

## 📋 Step-by-Step Migration Process

### Step 1: Identify Modal HTML in Templates

**Location:** Look for `<div class="bkgt-modal">`

**Example Files:**
- Document-Management: `admin/templates/*.php`
- Data-Scraping: `templates/admin-dashboard.php`
- Team-Player: Theme templates

### Step 2: Identify JavaScript Modal Code

**Location:** Look for modal initialization in JS files

**Patterns to Find:**
```javascript
// Pattern 1: DOM manipulation
$('.bkgt-modal-close').on('click', function() { ... });
$(this).closest('.bkgt-modal').hide();

// Pattern 2: jQuery show/hide
$('#bkgt-modal').show();
$('#bkgt-modal').hide();

// Pattern 3: Inline modal creation
var modal = $('<div class="bkgt-modal">...');
$('body').append(modal);
```

### Step 3: Create BKGTModal Instance

Replace old initialization with:

```javascript
// OLD: Per-instance modal (one per type)
// NEW: Create once at script load
var documentModal = new BKGTModal({
    id: 'bkgt-document-modal',
    title: 'Dokumentdetaljer',
    size: 'medium'
});

var shareModal = new BKGTModal({
    id: 'bkgt-share-modal',
    title: 'Dela dokument',
    size: 'medium'
});
```

### Step 4: Wire Event Handlers

Replace jQuery modal handling:

```javascript
// OLD: Hardcoded modal HTML and inline JavaScript
// NEW: Clean event delegation
$(document).on('click', '.bkgt-edit-document', function(e) {
    e.preventDefault();
    
    var docId = $(this).data('doc-id');
    
    // Fetch or build content
    var content = getDocumentContent(docId);
    
    // Set and open
    documentModal.setContent(content);
    documentModal.setFooter(
        '<button class="button button-secondary" onclick="documentModal.close();">Avbryt</button>' +
        '<button class="button button-primary" onclick="saveDocument();">Spara</button>'
    );
    documentModal.open();
});
```

### Step 5: Remove Old Modal HTML

Delete from templates:
```php
<!-- REMOVE THIS -->
<div id="bkgt-document-modal" class="bkgt-modal" style="display: none;">
    <div class="bkgt-modal-overlay"></div>
    <div class="bkgt-modal-content">
        <div class="bkgt-modal-header">
            <h2>Dokumentdetaljer</h2>
            <span class="bkgt-modal-close">&times;</span>
        </div>
        <div class="bkgt-modal-body">
            <!-- Old content here -->
        </div>
    </div>
</div>
```

**Result:** BKGTModal creates the HTML dynamically now

### Step 6: Remove Old Modal CSS

Delete from stylesheets:
```css
/* REMOVE THESE */
.bkgt-modal { ... }
.bkgt-modal-overlay { ... }
.bkgt-modal-content { ... }
.bkgt-modal-header { ... }
.bkgt-modal-close { ... }
/* etc */
```

**Result:** Styling comes from unified `bkgt-modal.css` now

### Step 7: Test and Verify

**Manual Testing:**
- [ ] Click button that opens modal
- [ ] Modal displays with smooth animation
- [ ] Content displays correctly
- [ ] Close button works
- [ ] Escape key closes modal
- [ ] Overlay click closes modal
- [ ] Check browser console - no errors
- [ ] Test on mobile/tablet

### Step 8: Update Documentation

Update plugin README to document modal usage:

```markdown
## Modals

This plugin uses the unified BKGTModal system from BKGT_Core.

### Usage

```javascript
// Create modal instance
var myModal = new BKGTModal({
    id: 'my-modal',
    title: 'Modal Title',
    size: 'medium'
});

// Open with content
myModal.setContent('<p>Content here</p>');
myModal.open();

// Close
myModal.close();
```

See BKGTMODAL_QUICK_START.md for full API reference.
```

---

## 🔍 Migration Checklist

For each plugin:

### Pre-Migration
- [ ] Identify all modal instances
- [ ] List all modal HTML in templates
- [ ] Document modal sizes and uses
- [ ] Back up original files (git commit)
- [ ] Note all CSS rules for modals

### During Migration
- [ ] Create BKGTModal instances in JavaScript
- [ ] Replace jQuery show/hide with BKGTModal.open()/.close()
- [ ] Wire button click handlers to modal methods
- [ ] Test content loading (static, dynamic, AJAX)
- [ ] Remove old modal HTML from templates
- [ ] Remove old modal CSS from stylesheets
- [ ] Remove inline modal creation code

### Post-Migration
- [ ] Test modal on desktop browser
- [ ] Test modal on tablet/mobile
- [ ] Verify no console errors
- [ ] Check BKGT_Logger for warnings
- [ ] Test keyboard navigation (Esc key)
- [ ] Test form submissions if applicable
- [ ] Verify loading states work
- [ ] Test error message display

### Documentation
- [ ] Update plugin README
- [ ] Add modal usage examples
- [ ] Document any custom options
- [ ] Update version number
- [ ] Commit changes with clear message

---

## 📋 Plugin Migration Plan

### Priority 1: Document Management (HIGH - Most Usage)
**Current State:** Multiple inline modals for document operations  
**Files Affected:**
- `admin/js/admin.js` (Share modal, etc.)
- `admin/templates/*.php` (Modal HTML)
- `admin/css/*.css` (Modal CSS)

**Modals to Migrate:**
1. Share Document Modal
2. Document Details Modal
3. Access Control Modal
4. Template Builder Modal

**Estimated Time:** 60-90 minutes

### Priority 2: Data Scraping (MEDIUM - Event Management)
**Current State:** jQuery show/hide for player and event modals  
**Files Affected:**
- `admin/js/admin.js` (Player/Event modals)
- `templates/admin-dashboard.php` (Modal HTML)

**Modals to Migrate:**
1. Player Assignment Modal
2. Event Management Modal
3. Import Configuration Modal

**Estimated Time:** 45-60 minutes

### Priority 3: Communication (MEDIUM - Notifications)
**Current State:** Potential modals for message details  
**Files Affected:**
- `admin/js/admin.js` (if exists)
- `assets/js/*.js`

**Modals to Migrate:**
1. Message Details Modal (if applicable)
2. Notification Popups

**Estimated Time:** 30-45 minutes

---

## 💡 Common Patterns

### Pattern 1: Static Content Modal

**Old:**
```javascript
$(document).on('click', '.button-share', function() {
    var modal = $('<div class="bkgt-modal">...' + content + '...</div>');
    $('body').append(modal);
    modal.show();
});
```

**New:**
```javascript
var shareModal = new BKGTModal({
    id: 'share-modal',
    title: 'Dela'
});

$(document).on('click', '.button-share', function() {
    var content = '<p>Share link: ' + $(this).data('link') + '</p>';
    shareModal.setContent(content);
    shareModal.open();
});
```

### Pattern 2: AJAX Content Loading

**Old:**
```javascript
$('#load-data').on('click', function() {
    $.ajax({
        url: '/get-data',
        success: function(data) {
            $('#modal-content').html(data);
            $('#modal').show();
        }
    });
});
```

**New:**
```javascript
var dataModal = new BKGTModal({
    id: 'data-modal',
    title: 'Data'
});

$('#load-data').on('click', function() {
    dataModal.showLoading();
    dataModal.open();
    
    $.ajax({
        url: '/get-data',
        success: function(data) {
            dataModal.hideLoading();
            dataModal.setContent(data);
        },
        error: function(error) {
            dataModal.hideLoading();
            dataModal.showError('Failed to load data');
        }
    });
});
```

### Pattern 3: Form Submission

**Old:**
```javascript
$('#modal-form').on('submit', function(e) {
    e.preventDefault();
    $.post('/save', $(this).serialize(), function() {
        $('#modal').hide();
    });
});
```

**New:**
```javascript
var formModal = new BKGTModal({
    id: 'form-modal',
    title: 'Edit Item'
});

$('#modal-form').on('submit', function(e) {
    e.preventDefault();
    formModal.showLoading();
    
    $.post('/save', $(this).serialize(), function() {
        formModal.hideLoading();
        formModal.close();
    }).fail(function(error) {
        formModal.hideLoading();
        formModal.showError('Save failed: ' + error.statusText);
    });
});
```

---

## ⚠️ Common Mistakes

### Mistake 1: Creating New Instance Each Time

**❌ WRONG:**
```javascript
$('.button').on('click', function() {
    var modal = new BKGTModal({...});  // ❌ New instance every click!
    modal.open();
});
```

**✅ RIGHT:**
```javascript
var modal = new BKGTModal({...});  // ✅ Create once

$('.button').on('click', function() {
    modal.open();
});
```

### Mistake 2: Not Using BKGTModal Methods

**❌ WRONG:**
```javascript
var modal = new BKGTModal({...});
modal.open();
$('#modal').hide();  // ❌ Direct DOM manipulation
```

**✅ RIGHT:**
```javascript
var modal = new BKGTModal({...});
modal.open();
modal.close();  // ✅ Use modal methods
```

### Mistake 3: Forgetting to Remove Old HTML

**❌ WRONG:**
```
- Created BKGTModal in JS ✓
- Removed old JS code ✓
- Removed old CSS ✓
- LEFT old modal HTML in template ✗  // ❌ Still in DOM!
```

**✅ RIGHT:**
```
- Create BKGTModal in JS ✓
- Remove old JS code ✓
- Remove old CSS ✓
- Remove old modal HTML from template ✓
- Remove old modal CSS ✓
```

### Mistake 4: Not Handling Errors

**❌ WRONG:**
```javascript
modal.loadFromAjax('action', data);
modal.open();  // ❌ No error handling
```

**✅ RIGHT:**
```javascript
modal.showLoading();
modal.open();

modal.loadFromAjax('action', data)
    .then(() => {
        modal.hideLoading();
    })
    .catch(error => {
        modal.hideLoading();
        modal.showError('Error: ' + error);
    });
```

---

## 🧪 Testing Checklist

After migration, verify:

### Functionality
- [ ] Modal opens when button clicked
- [ ] Modal displays correct content
- [ ] Close button works
- [ ] Escape key works
- [ ] Overlay click closes modal
- [ ] Form submission works (if applicable)
- [ ] AJAX loading works (if applicable)

### Appearance
- [ ] Modal centered on screen
- [ ] Animation smooth (no jank)
- [ ] Content visible and readable
- [ ] Buttons styled correctly
- [ ] Error messages display with correct color
- [ ] Loading spinner animates

### Responsiveness
- [ ] Modal works on desktop (1920px)
- [ ] Modal works on tablet (768px)
- [ ] Modal works on mobile (480px)
- [ ] Content readable on all sizes
- [ ] Buttons clickable on touch devices

### Accessibility
- [ ] Tab navigation works
- [ ] Focus ring visible
- [ ] Keyboard shortcuts work (Esc)
- [ ] Screen reader announces modal open
- [ ] ARIA labels present
- [ ] Color contrast sufficient

### Performance
- [ ] Modal opens quickly
- [ ] No console errors
- [ ] No memory leaks
- [ ] BKGT_Logger shows expected entries
- [ ] No CSS/JS conflicts

### Browser Support
- [ ] Chrome/Chromium
- [ ] Firefox
- [ ] Safari
- [ ] Edge

---

## 🚀 Migration Timeline

**Session 4 (Current):**
- [x] Create BKGTModal component ✅
- [x] Fix inventory plugin ✅
- [x] Create this migration guide ✅

**Session 4 (Continuation - Next 2-3 hours):**
- [ ] Migrate document-management plugin
- [ ] Migrate data-scraping plugin
- [ ] Migrate communication plugin (if needed)

**After Session 4:**
- [ ] Test all plugins comprehensively
- [ ] Deploy to staging
- [ ] Gather feedback
- [ ] Final refinements

---

## 📞 Support

### Questions?
- See BKGTMODAL_QUICK_START.md for API reference
- Check PHASE2_MODAL_INTEGRATION_GUIDE.md for detailed info
- Review code examples in this guide

### Issues?
- Check browser console for errors
- Review BKGT_Logger (WordPress admin → Tools → BKGT Log)
- Compare with working inventory plugin example

### Migration Assistance
- Follow step-by-step process above
- Use checklist to verify completion
- Test thoroughly on all devices

---

**Version:** 1.0  
**Created:** Session 4 (Current)  
**Status:** Ready to Use  
**Estimated Effort:** 3-4 hours for all plugins
