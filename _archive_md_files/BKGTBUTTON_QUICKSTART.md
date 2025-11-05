# BKGT Button System - Quick Start

## 1-Minute Setup

### PHP (Server-side)

```php
// Basic button
echo bkgt_button( 'Click Me' )->primary();

// Primary action
echo bkgt_button( 'Save' )->primary_action()->large();

// Danger action
echo bkgt_button( 'Delete' )->delete_action();

// Cancel button
echo bkgt_button( 'Cancel' )->cancel_action();
```

### JavaScript (Client-side)

```javascript
// Create button
const btn = new BKGTButton('#my-button');

// Handle click with loading state
btn.perform(async () => {
    await fetch('/api/save');
});

// Show result
btn.showSuccess(2000);
```

### HTML (Manual)

```html
<!-- These classes work immediately -->
<button class="bkgt-btn bkgt-btn-primary">Save</button>
<button class="bkgt-btn bkgt-btn-danger bkgt-btn-lg">Delete</button>
<button class="bkgt-btn bkgt-btn-secondary">Cancel</button>
```

---

## Button Variants (Colors)

| Variant | Use When | Example |
|---------|----------|---------|
| `primary()` | Main action | Save, Submit, Create |
| `secondary()` | Alternative action | Cancel, Skip, Later |
| `danger()` | Destructive action | Delete, Remove, Close Account |
| `success()` | Positive confirmation | Confirm, Approve, Accept |
| `warning()` | Caution needed | Proceed, Override, Continue |
| `info()` | Information | Learn More, Help, About |
| `text()` | Minimal, inline | Edit, Link, Inline action |
| `outline()` | Secondary with emphasis | Import, Export, Options |

---

## Button Sizes

```php
bkgt_button('Small')->small();         // Compact
bkgt_button('Normal')->primary();      // Default
bkgt_button('Large')->large();         // Prominent
bkgt_button('Full Width')->block();    // Spans container
```

---

## Common Patterns

### Form Submission

```php
<form method="POST" action="">
    <?php
    echo bkgt_button('Name')->text( 'Your Name' )->type('text');
    echo bkgt_button('Submit')->primary()->type('submit')->large();
    echo bkgt_button('Cancel')->secondary()->type('reset');
    ?>
</form>
```

### Delete Confirmation

```php
<?php
echo bkgt_button('Delete Item')->danger()->delete_action();
?>

<script>
const deleteBtn = new BKGTButton('.bkgt-delete-action');
deleteBtn.onClick(function(btn) {
    if (confirm('Are you sure?')) {
        btn.perform(() => fetch('/api/delete'));
    }
});
</script>
```

### Button Group (Multiple Selection)

```html
<!-- Checkboxes: select multiple -->
<div data-bkgt-button-group="checkbox">
    <button class="bkgt-btn">Option 1</button>
    <button class="bkgt-btn">Option 2</button>
    <button class="bkgt-btn">Option 3</button>
</div>
```

```javascript
const group = new BKGTButtonGroup('[data-bkgt-button-group="checkbox"]', {
    onSelect: (btn) => console.log('Selected:', btn)
});

const selected = group.getSelectedValues();
```

### Button with Loading State

```javascript
const btn = new BKGTButton('#save-btn');

btn.onClick(() => {
    btn.perform(async () => {
        const response = await fetch('/api/save', {
            method: 'POST',
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            btn.showSuccess();
        } else {
            btn.showError('Failed to save');
        }
    });
});
```

### Button Group (Single Selection)

```html
<!-- Radio: select one -->
<div data-bkgt-button-group="radio">
    <button class="bkgt-btn">Yes</button>
    <button class="bkgt-btn">No</button>
</div>
```

---

## Accessibility

### Icon-Only Buttons

```php
<?php
echo bkgt_button()
    ->html( '<svg>...</svg>' )
    ->ariaLabel( 'Close modal' )
    ->text();
?>
```

### Action Labels

```php
<?php
// Automatically adds appropriate aria-label
echo bkgt_button('Delete')->delete_action();
echo bkgt_button('Save')->primary_action();
echo bkgt_button('Abandon')->cancel_action();
?>
```

### Keyboard Support

All buttons work with:
- Tab navigation
- Enter/Space activation
- Focus management
- Screen readers

---

## Real-World Examples

### User Profile Form

```php
<?php
// Profile form with buttons
echo bkgt_form_builder('user_profile')
    ->method('POST')
    ->action(admin_url('admin-ajax.php'))
    
    // Add fields
    ->field('name')->text()->label('Full Name')->required()
    ->field('email')->email()->label('Email')->required()
    
    // Add buttons
    ->html('<div class="bkgt-form-footer">');
    
echo bkgt_button('Update Profile')
    ->primary()
    ->type('submit')
    ->large();
    
echo bkgt_button('Cancel')
    ->secondary()
    ->onclick("history.back()");
    
?>
```

### Confirmation Dialog

```php
<?php
echo bkgt_button('Delete Account')
    ->danger()
    ->large()
    ->id('delete-account-btn')
    ->onclick("document.getElementById('confirm-modal').style.display='block'");
?>

<!-- Modal -->
<div id="confirm-modal" style="display:none;" class="bkgt-modal">
    <div class="bkgt-modal-content">
        <h2>Delete Account?</h2>
        <p>This action cannot be undone.</p>
        
        <div class="bkgt-modal-footer">
            <?php
            echo bkgt_button('Delete')
                ->danger()
                ->id('confirm-delete');
                
            echo bkgt_button('Cancel')
                ->secondary()
                ->onclick("this.parentElement.parentElement.style.display='none'");
            ?>
        </div>
    </div>
</div>
```

### Admin Action Buttons

```php
<?php
// In plugin admin page
foreach ($items as $item) {
    echo bkgt_button('Edit')
        ->text()
        ->onclick("window.location.href='" . esc_url($item['edit_url']) . "'");
        
    echo bkgt_button('Delete')
        ->danger()
        ->small()
        ->onclick("if(confirm('Delete?')) fetch('" . esc_url($item['delete_url']) . "')");
}
?>
```

---

## Styling Customization

### Add Custom Class

```php
<?php
echo bkgt_button('Custom')
    ->primary()
    ->addClass('my-special-button')
    ->addClass('highlight');
?>
```

```css
/* In your CSS */
.my-special-button {
    text-transform: uppercase;
    letter-spacing: 2px;
}
```

### Override with CSS Variables

```css
/* Override button colors */
:root {
    --bkgt-button-padding-y-md: 12px;
    --bkgt-button-padding-x-md: 24px;
    --bkgt-button-font-size: 16px;
}
```

---

## JavaScript Tips

### Auto-Initialize

Buttons with `data-bkgt-button` attribute auto-initialize on page load:

```html
<button class="bkgt-btn bkgt-btn-primary" data-bkgt-button>
    Click Me
</button>
```

### Batch Operations

```javascript
// Disable all form buttons
BKGTButton.disableAll('.form-btn');

// Enable all form buttons
BKGTButton.enableAll('.form-btn');

// Set loading on all action buttons
BKGTButton.setAllLoading('.action-btn', true);
```

### Custom Events

```javascript
// Listen to button clicks
document.addEventListener('bkgtButtonClick', (e) => {
    const button = e.detail.button;
    console.log('Button clicked!');
});
```

---

## Common Mistakes

### ❌ Wrong: Forgetting the base class

```php
// Won't style correctly
echo bkgt_button('Click')->primary(); // Missing internal class
```

### ✅ Right: Builder handles it

```php
// Correct
echo bkgt_button('Click')->primary(); // Includes bkgt-btn internally
```

### ❌ Wrong: Adding onclick in PHP when you should use JS

```php
echo bkgt_button('Delete')
    ->onClick("fetch('/api/delete'); this.disabled=true");
```

### ✅ Right: Use JavaScript handler

```php
echo bkgt_button('Delete')
    ->danger()
    ->id('delete-btn');
?>

<script>
const btn = new BKGTButton('#delete-btn');
btn.perform(() => fetch('/api/delete'));
</script>
```

---

## Need More?

- **Full API:** See [BKGTBUTTON_DEVELOPER_GUIDE.md](BKGTBUTTON_DEVELOPER_GUIDE.md)
- **Examples:** Check `/examples/buttons/`
- **Issues:** Report in plugin documentation

---

**Status:** Ready to use  
**Last Updated:** PHASE 2 Step 4
