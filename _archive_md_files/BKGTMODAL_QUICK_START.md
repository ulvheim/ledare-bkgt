# BKGTModal Quick Start Guide

**Status:** ✅ Ready to Use

A unified, production-ready modal component for all BKGT plugins.

---

## 🚀 Quick Start (30 seconds)

### 1. Create a Modal
```javascript
var modal = new BKGTModal({
    id: 'my-modal',
    title: 'My Title',
    size: 'medium'
});
```

### 2. Open It
```javascript
modal.open('<p>Hello World</p>');
```

### 3. Close It
```javascript
modal.close();
```

That's it! BKGTModal is auto-loaded on all pages.

---

## 📚 API Reference

### Constructor

```javascript
new BKGTModal({
    id: 'unique-id',              // Optional: auto-generated if not provided
    title: 'Modal Title',         // Optional: displayed in header
    size: 'medium',               // Optional: 'small'|'medium'|'large'
    closeButton: true,            // Optional: show X button (default: true)
    overlay: true,                // Optional: show dark overlay (default: true)
    onOpen: function() {},        // Optional: callback when opened
    onClose: function() {},       // Optional: callback when closed
    onSubmit: function(data) {},  // Optional: callback for form submission
    onError: function(error) {}   // Optional: callback for errors
})
```

### Methods

#### `open(content, options)`
Opens the modal with content.
```javascript
modal.open('<p>Hello</p>');

// Or with options (title override)
modal.open('<p>Hello</p>', {
    title: 'New Title'
});
```

#### `close()`
Closes the modal.
```javascript
modal.close();
```

#### `setContent(html)`
Updates the modal body content.
```javascript
modal.setContent('<p>New content</p>');

// Or with DOM element
var div = document.createElement('div');
div.textContent = 'New content';
modal.setContent(div);
```

#### `setFooter(html)`
Sets footer action buttons.
```javascript
modal.setFooter(
    '<button onclick="modal.close()">Stäng</button>' +
    '<button class="btn btn-primary">Spara</button>'
);
```

#### `showLoading()`
Displays a loading spinner.
```javascript
modal.showLoading();
```

#### `hideLoading()`
Hides the loading spinner.
```javascript
modal.hideLoading();
```

#### `showError(message)`
Displays an error message.
```javascript
modal.showError('Something went wrong!');
```

#### `clearError()`
Removes the error message.
```javascript
modal.clearError();
```

#### `loadFromUrl(url, params)`
Loads content via HTTP fetch.
```javascript
modal.loadFromUrl('/api/items/123', {
    timeout: 5000  // 5 second timeout
});
```

#### `loadFromAjax(action, data)`
Loads content via WordPress AJAX.
```javascript
modal.loadFromAjax('get_item_details', {
    item_id: 123
});
```

#### `destroy()`
Removes the modal from DOM completely.
```javascript
modal.destroy();
```

---

## 💡 Common Patterns

### Pattern 1: Simple Details Modal

```javascript
// Create once
var detailsModal = new BKGTModal({
    id: 'details-modal',
    title: 'Item Details',
    size: 'medium'
});

// When button clicked
document.getElementById('show-details').addEventListener('click', function() {
    var itemId = this.getAttribute('data-id');
    
    detailsModal.setContent(
        '<div class="bkgt-modal-details">' +
            '<div class="bkgt-detail-row">' +
                '<label>ID:</label>' +
                '<span>' + itemId + '</span>' +
            '</div>' +
            '<div class="bkgt-detail-row">' +
                '<label>Name:</label>' +
                '<span>' + this.getAttribute('data-name') + '</span>' +
            '</div>' +
        '</div>'
    );
    
    detailsModal.setFooter(
        '<button class="btn btn-secondary" onclick="detailsModal.close();">Stäng</button>'
    );
    
    detailsModal.open();
});
```

### Pattern 2: Loading Remote Content

```javascript
var contentModal = new BKGTModal({
    id: 'content-modal',
    title: 'Loading...',
    size: 'large'
});

contentModal.showLoading();
contentModal.open();

// Load from AJAX
contentModal.loadFromAjax('get_content', { id: 123 })
    .then(() => contentModal.hideLoading())
    .catch(error => {
        contentModal.clearError();
        contentModal.showError('Failed to load content: ' + error);
    });
```

### Pattern 3: Form Submission

```javascript
var formModal = new BKGTModal({
    id: 'form-modal',
    title: 'Edit Item',
    size: 'medium'
});

// Set form content
var formHTML = 
    '<form class="bkgt-modal-form">' +
        '<div class="form-group">' +
            '<label>Title:</label>' +
            '<input type="text" name="title" required />' +
        '</div>' +
        '<div class="form-group">' +
            '<label>Description:</label>' +
            '<textarea name="description"></textarea>' +
        '</div>' +
    '</form>';

formModal.setContent(formHTML);
formModal.setFooter(
    '<button class="btn btn-secondary" onclick="formModal.close();">Avbryt</button>' +
    '<button class="btn btn-primary" onclick="submitForm();">Spara</button>'
);

function submitForm() {
    var form = formModal.$content.querySelector('form');
    var formData = new FormData(form);
    
    // Send to server
    fetch('/api/items', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        formModal.showError('Saved successfully!');
        setTimeout(() => formModal.close(), 1500);
    })
    .catch(error => {
        formModal.showError('Error: ' + error.message);
    });
}

formModal.open();
```

### Pattern 4: Multiple Sequential Modals

```javascript
var modal1 = new BKGTModal({
    id: 'modal-1',
    title: 'Step 1',
    size: 'medium',
    onClose: function() {
        // Auto-open next modal
        modal2.open();
    }
});

var modal2 = new BKGTModal({
    id: 'modal-2',
    title: 'Step 2',
    size: 'medium'
});

modal1.setContent('<p>Confirm action?</p>');
modal1.setFooter(
    '<button class="btn btn-secondary" onclick="modal1.close();">Cancel</button>' +
    '<button class="btn btn-primary" onclick="modal1.close();">Continue</button>'
);
modal1.open();
```

---

## 🎨 CSS Classes

Style modals using these classes:

### Container Classes
- `.bkgt-modal` - Main modal container
- `.bkgt-modal-overlay` - Dark background
- `.bkgt-modal-content` - White content box
- `.bkgt-modal-header` - Title section
- `.bkgt-modal-body` - Main content area
- `.bkgt-modal-footer` - Action buttons section

### Content Classes
- `.bkgt-modal-details` - Detail display container
- `.bkgt-detail-row` - Individual detail row
- `.bkgt-detail-label` - Detail label text
- `.bkgt-detail-value` - Detail value text

### State Classes
- `.bkgt-modal-error` - Error message display
- `.bkgt-modal-loading` - Loading spinner

### Size Variants
- `.bkgt-modal-small` - 400px max width
- `.bkgt-modal-medium` - 600px max width (default)
- `.bkgt-modal-large` - 900px max width

---

## 📍 Integration Checklist

When adding BKGTModal to a plugin:

- [ ] BKGTModal is loaded (auto-loaded by BKGT_Core)
- [ ] Create modal instance in JavaScript
- [ ] Wire event listeners to buttons
- [ ] Call `modal.open()` to display
- [ ] Call `modal.close()` or overlay click to close
- [ ] Remove old modal HTML
- [ ] Remove old modal CSS
- [ ] Remove console.log debug statements
- [ ] Test opening/closing
- [ ] Test on mobile
- [ ] Test error states
- [ ] Document in plugin README

---

## 🚨 Error Handling

BKGTModal integrates with BKGT_Logger for error tracking:

```javascript
// Errors are automatically logged
modal.loadFromAjax('action', {}).catch(error => {
    // Error logged to console, database, file
    // View in WordPress admin dashboard (Tools > BKGT Log)
});
```

Check logs in WordPress admin:
- **Path:** Tools → BKGT Log
- **Filter by:** Modal operations
- **View:** File, Database, Email alerts

---

## ♿ Accessibility

BKGTModal includes built-in accessibility:

- ✅ **Keyboard Navigation:** Escape key closes modal
- ✅ **ARIA Labels:** Screen reader support
- ✅ **Focus Management:** Focus moves to modal on open
- ✅ **Color Contrast:** WCAG AA compliant
- ✅ **Reduced Motion:** Respects user preferences

---

## 📱 Responsive Design

BKGTModal automatically adapts to screen sizes:

| Device | Width | Font Size | Behavior |
|--------|-------|-----------|----------|
| Desktop | 600px | 16px | Fixed center |
| Tablet | Full-20px | 16px | Padded sides |
| Mobile | Full-10px | 14px | Flex layout |

---

## 🔧 Troubleshooting

### Modal Not Showing

```javascript
// Check if BKGTModal is loaded
if (typeof BKGTModal === 'undefined') {
    console.error('BKGTModal not loaded');
} else {
    console.log('BKGTModal ready');
}
```

### BKGTModal Not Available

- Verify BKGT_Core plugin is active
- Check wp-content/plugins/bkgt-core/assets/bkgt-modal.js exists
- Check wp-content/plugins/bkgt-core/assets/bkgt-modal.css exists
- Check browser console for script errors

### Content Not Displaying

```javascript
// Debug content
var modal = new BKGTModal({id: 'debug-modal'});
console.log('Modal created:', modal);
console.log('Modal content element:', modal.$content);

modal.open('<p>Test</p>');
console.log('Content after open:', modal.$content.innerHTML);
```

### Styling Issues

- Verify bkgt-modal.css is loaded (DevTools > Sources)
- Check for CSS conflicts with theme
- Use browser DevTools to inspect `.bkgt-modal` element
- Check z-index: should be 99999

---

## 📖 Full Documentation

See **PHASE2_MODAL_INTEGRATION_GUIDE.md** for:
- Complete integration examples
- How modals were implemented
- Architecture and design decisions
- Testing procedures
- Migration guide from old code

---

## 🆘 Support

### Getting Help

1. Check the error message in browser console
2. Review BKGT_Logger (WordPress admin → Tools → BKGT Log)
3. See PHASE2_MODAL_INTEGRATION_GUIDE.md
4. Check this Quick Start Guide

### Reporting Issues

Include:
- Steps to reproduce
- Browser console errors
- BKGT_Logger errors
- Code snippet showing the issue

---

**Created:** Session 4 (Current)  
**Last Updated:** Current Session  
**Status:** ✅ Production Ready  
**Used In:** bkgt-inventory, ready for other plugins
