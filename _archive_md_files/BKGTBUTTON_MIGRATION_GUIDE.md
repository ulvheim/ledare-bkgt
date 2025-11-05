# BKGT Button System - Migration Guide

## From Bootstrap/Old Buttons to BKGT Buttons

This guide helps you migrate existing button code to the new BKGT Button System.

---

## Quick Reference

| Old Code | New Code |
|----------|----------|
| `<button class="btn btn-primary">Click</button>` | `<?php echo bkgt_button('Click')->primary(); ?>` |
| `<button class="btn btn-danger">Delete</button>` | `<?php echo bkgt_button('Delete')->danger(); ?>` |
| `<button class="btn btn-lg">Large</button>` | `<?php echo bkgt_button()->large(); ?>` |
| `<button disabled>Disabled</button>` | `<?php echo bkgt_button()->disabled(); ?>` |

---

## Migration by Use Case

### 1. Simple Buttons

#### Before (Bootstrap)
```html
<button class="btn btn-primary">Save</button>
<button class="btn btn-secondary">Cancel</button>
<button class="btn btn-danger">Delete</button>
```

#### After (BKGT)
```php
<?php
echo bkgt_button('Save')->primary();
echo bkgt_button('Cancel')->secondary();
echo bkgt_button('Delete')->danger();
?>
```

**Benefits:**
- More readable
- No HTML classes needed
- Type-safe variants
- Automatic accessibility

---

### 2. Form Buttons

#### Before (Bootstrap)
```html
<form method="POST">
    <input type="text" name="name" class="form-control">
    <button type="submit" class="btn btn-primary btn-lg">Submit</button>
    <button type="reset" class="btn btn-secondary">Reset</button>
</form>
```

#### After (BKGT)
```php
<?php
?>
<form method="POST">
    <input type="text" name="name" class="form-control">
    <?php
    echo bkgt_button('Submit')
        ->primary()
        ->large()
        ->type('submit');
    
    echo bkgt_button('Reset')
        ->secondary()
        ->type('reset');
    ?>
</form>
<?php
?>
```

**Benefits:**
- Consistent styling
- Fluent API
- Less code
- Better maintainability

---

### 3. Button Groups

#### Before (Bootstrap)
```html
<div class="btn-group" role="group">
    <button type="button" class="btn btn-outline-primary">Left</button>
    <button type="button" class="btn btn-outline-primary">Middle</button>
    <button type="button" class="btn btn-outline-primary">Right</button>
</div>
```

#### After (BKGT)
```html
<div class="bkgt-btn-group" data-bkgt-button-group="radio">
    <button class="bkgt-btn bkgt-btn-outline" value="left">Left</button>
    <button class="bkgt-btn bkgt-btn-outline" value="middle">Middle</button>
    <button class="bkgt-btn bkgt-btn-outline" value="right">Right</button>
</div>
```

```javascript
const group = new BKGTButtonGroup('.bkgt-btn-group', {
    type: 'radio',
    onSelect: (btn) => console.log('Selected:', btn.getText())
});
```

**Benefits:**
- Semantic grouping
- Built-in callbacks
- Better event handling
- Easier state management

---

### 4. Loading/Processing Buttons

#### Before (Custom JavaScript)
```html
<button id="submit-btn" class="btn btn-primary">Submit</button>

<script>
document.getElementById('submit-btn').addEventListener('click', function() {
    this.disabled = true;
    this.innerHTML = '<span class="spinner"></span> Processing...';
    
    fetch('/api/submit').then(res => {
        this.disabled = false;
        this.innerHTML = 'Success!';
    });
});
</script>
```

#### After (BKGT)
```php
<?php
echo bkgt_button('Submit')
    ->primary()
    ->id('submit-btn');
?>

<script>
const btn = new BKGTButton('#submit-btn');
btn.perform(async () => {
    await fetch('/api/submit');
    btn.showSuccess();
});
</script>
```

**Benefits:**
- Built-in loading state
- Automatic spinner
- Success/error feedback
- Cleaner code
- Better UX

---

### 5. Icon Buttons

#### Before
```html
<button class="btn btn-primary">
    <i class="fas fa-download"></i> Download
</button>

<button class="btn btn-sm">
    <i class="fas fa-times"></i>
</button>
```

#### After (BKGT)
```php
<?php
echo bkgt_button('Download')
    ->primary()
    ->icon('fa-download');

echo bkgt_button()
    ->ariaLabel('Close')
    ->icon('fa-times')
    ->text();
?>
```

**Benefits:**
- Icon helper method
- Accessibility built-in
- Consistent spacing
- Semantic icons

---

### 6. Button with Custom Attributes

#### Before
```html
<button class="btn btn-danger" 
        onclick="if(confirm('Delete?')) deleteItem();"
        data-item-id="123">
    Delete
</button>
```

#### After (BKGT)
```php
<?php
echo bkgt_button('Delete')
    ->danger()
    ->data('item-id', 123)
    ->id('delete-btn-123')
    ->delete_action();
?>

<script>
const btn = new BKGTButton('#delete-btn-123');
btn.onClick(function(button) {
    if (confirm('Delete?')) {
        button.perform(() => deleteItem());
    }
});
</script>
```

**Benefits:**
- Semantic delete action
- Better data handling
- Safer event binding
- Accessibility labels auto-added

---

### 7. Disabled Buttons

#### Before
```html
<button class="btn btn-primary" disabled>
    Cannot Click
</button>
```

#### After (BKGT)
```php
<?php
// Server-side
echo bkgt_button('Cannot Click')
    ->primary()
    ->disabled();

// Client-side
const btn = new BKGTButton('#my-btn');
btn.disable();
btn.enable();
?>
```

**Benefits:**
- Fluent API
- Proper ARIA attributes
- Both server & client-side
- State management

---

### 8. Conditional Styling

#### Before
```html
<?php
$class = $is_active ? 'btn-primary' : 'btn-secondary';
?>
<button class="btn <?php echo $class; ?>">
    <?php echo $is_active ? 'Active' : 'Inactive'; ?>
</button>
```

#### After (BKGT)
```php
<?php
$btn = bkgt_button($is_active ? 'Active' : 'Inactive');

if ($is_active) {
    $btn->primary();
} else {
    $btn->secondary();
}

echo $btn;
?>

<!-- Or more elegantly -->
<?php
echo bkgt_button($is_active ? 'Active' : 'Inactive')
    ->{$is_active ? 'primary' : 'secondary'}();
?>
```

**Benefits:**
- More readable
- Type-safe
- No CSS class strings
- Easy conditionals

---

## Plugin Migration Checklist

### Step 1: Identify Button HTML
- [ ] Search for `<button` tags
- [ ] Search for `class="btn`
- [ ] Search for button-related HTML

### Step 2: Convert Static HTML
- [ ] Replace HTML buttons with `bkgt_button()` calls
- [ ] Update class names to variant methods
- [ ] Update sizes to size methods
- [ ] Add IDs where needed for JavaScript

### Step 3: Update JavaScript
- [ ] Replace manual click handlers with BKGTButton
- [ ] Update loading state handling
- [ ] Add success/error feedback
- [ ] Test all interactions

### Step 4: Test & Verify
- [ ] Visual testing in browser
- [ ] Keyboard navigation
- [ ] Screen reader testing
- [ ] Mobile responsive
- [ ] High contrast mode
- [ ] Reduced motion

### Step 5: Update Documentation
- [ ] Update plugin docs
- [ ] Add usage examples
- [ ] Document breaking changes
- [ ] Update admin/user guides

---

## Common Migration Patterns

### Pattern 1: Simple Replacement

```php
// Find: Multiple simple buttons
// Replace: with bkgt_button() fluent API

// Before
<button class="btn btn-primary">Save</button>
<button class="btn btn-secondary">Cancel</button>

// After
<?php
echo bkgt_button('Save')->primary();
echo bkgt_button('Cancel')->secondary();
?>
```

### Pattern 2: Form Button Groups

```php
// Before: Multiple form buttons
<form>
    ...
    <button class="btn btn-primary" type="submit">Submit</button>
    <button class="btn btn-secondary" type="reset">Reset</button>
</form>

// After: Organized button group
<form>
    ...
    <div class="bkgt-form-footer">
        <?php
        echo bkgt_button('Submit')->primary()->type('submit');
        echo bkgt_button('Reset')->secondary()->type('reset');
        ?>
    </div>
</form>
```

### Pattern 3: Delete Confirmation

```javascript
// Before: Custom confirmation logic
btn.onclick = function() {
    if (confirm('Are you sure?')) {
        // delete logic
    }
};

// After: Semantic delete action
<?php
echo bkgt_button('Delete')->delete_action()->id('delete-btn');
?>

<script>
const btn = new BKGTButton('#delete-btn');
btn.onClick(function() {
    if (confirm('Are you sure?')) {
        btn.perform(() => fetch('/api/delete'));
    }
});
</script>
```

---

## Migration Timeline

### Phase 1: Preparation (1 day)
- [ ] Audit all button usage in plugins
- [ ] Create migration plan
- [ ] Document breaking changes
- [ ] Prepare rollback strategy

### Phase 2: Core Migration (3-5 days)
- [ ] Migrate BKGT_Core buttons
- [ ] Update primary plugins
- [ ] Test thoroughly
- [ ] Update documentation

### Phase 3: Secondary Migration (2-3 days)
- [ ] Migrate remaining plugins
- [ ] Update admin pages
- [ ] Update user-facing buttons
- [ ] Final testing

### Phase 4: Refinement (1-2 days)
- [ ] Performance optimization
- [ ] Browser compatibility testing
- [ ] Accessibility verification
- [ ] User feedback integration

---

## Breaking Changes

### Removed
- Bootstrap button classes
- Old custom button CSS
- Manual button state management code

### Changed
- Button creation method (now uses `bkgt_button()`)
- CSS class names (from `btn-*` to `bkgt-btn-*`)
- JavaScript API (from custom to BKGTButton class)

### Added
- Fluent API for buttons
- Built-in accessibility
- Loading state management
- Button groups
- Semantic actions

---

## Deprecation Warnings

If you're gradually migrating, use deprecation wrappers:

```php
// Wrapper function for old code
function old_button($text, $class = '') {
    bkgt_log('warning', 'old_button() is deprecated. Use bkgt_button() instead.');
    
    // Map old classes to new variants
    $variant = 'primary'; // default
    if (strpos($class, 'btn-secondary') !== false) {
        $variant = 'secondary';
    } elseif (strpos($class, 'btn-danger') !== false) {
        $variant = 'danger';
    }
    
    return bkgt_button($text)->$variant();
}
```

---

## Troubleshooting Migration

### Issue: Buttons not styled after migration

**Solution:**
- Ensure `bkgt-variables.css` and `bkgt-buttons.css` are enqueued
- Check browser cache (clear it)
- Verify no CSS conflicts with other plugins

### Issue: JavaScript events not working

**Solution:**
- Ensure `bkgt-buttons.js` is enqueued
- Check element exists in DOM
- Verify no JavaScript errors in console
- Use proper selector for BKGTButton initialization

### Issue: Layout broken after migration

**Solution:**
- Buttons are `display: inline-flex` by default
- Use `.bkgt-btn-block` or `->block()` for full-width
- Check custom CSS overrides
- Review responsive breakpoints

### Issue: Accessibility issues

**Solution:**
- Add `aria-label` to icon-only buttons
- Use semantic action methods (`->delete_action()`)
- Test with screen readers
- Verify keyboard navigation

---

## Support & Resources

- **Developer Guide:** BKGTBUTTON_DEVELOPER_GUIDE.md
- **Quick Start:** BKGTBUTTON_QUICKSTART.md
- **Examples:** wp-content/plugins/bkgt-core/examples-buttons.php
- **Implementation Summary:** BKGTBUTTON_IMPLEMENTATION_SUMMARY.md

---

## Migration Completed

After successful migration:
- ✅ All buttons use BKGT system
- ✅ Consistent styling throughout
- ✅ Better accessibility
- ✅ Improved user experience
- ✅ Easier maintenance

---

**Migration Guide Version:** 1.0.0  
**Last Updated:** PHASE 2 Step 4  
**Status:** Ready for Production Use
