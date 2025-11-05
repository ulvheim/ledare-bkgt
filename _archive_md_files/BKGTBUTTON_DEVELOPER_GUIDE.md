# BKGT Button System - Complete Guide

## Overview

The BKGT Button System provides a unified, accessible, and flexible way to create buttons throughout the BKGT ecosystem. It includes CSS styling, JavaScript utilities, and a PHP builder class to ensure consistency across all components and plugins.

**Status:** Production-ready  
**Created:** PHASE 2 Step 4  
**Components:** CSS (bkgt-buttons.css) + JavaScript (bkgt-buttons.js) + PHP (class-button-builder.php)

---

## Quick Start

### PHP (Server-Side)

```php
// Simple button
echo bkgt_button( 'Click Me' )->primary();

// Button with options
echo bkgt_button( 'Delete' )
    ->danger()
    ->large()
    ->id( 'delete-btn' )
    ->onClick( "if(confirm('Are you sure?')) { /* action */ }" );

// Full example with builder
echo bkgt_button( 'Save Changes' )
    ->primary()
    ->size( 'lg' )
    ->type( 'submit' )
    ->addClass( 'my-custom-class' )
    ->ariaLabel( 'Save all changes' )
    ->render();
```

### JavaScript (Client-Side)

```javascript
// Create button instance
const btn = new BKGTButton('#my-button');

// Set loading state
btn.setLoading(true, '⏳ Loading...');

// Handle async operation
btn.perform(async () => {
    const response = await fetch('/api/endpoint');
    return response.json();
});

// Show success
btn.showSuccess(2000);

// Fluent API
btn.setSize('lg')
   .addVariant('primary')
   .enable();

// Auto-initialize on page load
// Just add data attributes: data-bkgt-button
```

### HTML (Manual)

```html
<!-- Basic button -->
<button class="bkgt-btn bkgt-btn-primary">Click Me</button>

<!-- With utilities -->
<button class="bkgt-btn bkgt-btn-success bkgt-btn-lg">
    ✓ Success
</button>

<!-- Auto-init with JS -->
<button class="bkgt-btn bkgt-btn-danger" data-bkgt-button>
    Delete
</button>
```

---

## CSS Variants

### Primary Button (Default)

```css
.bkgt-btn-primary {
    /* Uses CSS variables from bkgt-variables.css */
    background-color: var(--bkgt-color-primary);      /* #3498db */
    color: var(--bkgt-text-white);
    border: 1px solid var(--bkgt-color-primary);
}

/* States */
:hover    /* Primary Dark */
:active   /* Primary Darker */
:disabled /* Gray with reduced opacity */
```

**Use when:** User needs to take the main action on a page

```php
bkgt_button( 'Save' )->primary()->render();
```

### Secondary Button

```css
.bkgt-btn-secondary {
    background-color: var(--bkgt-color-light);
    color: var(--bkgt-text-primary);
    border: 1px solid var(--bkgt-border-color);
}

/* States */
:hover    /* Light gray with primary border */
:active   /* Darker gray with darker border */
:disabled /* Muted text, reduced opacity */
```

**Use when:** User needs alternative or less important action

```php
bkgt_button( 'Cancel' )->secondary()->render();
```

### Danger Button

```css
.bkgt-btn-danger {
    background-color: var(--bkgt-color-danger);       /* #e74c3c */
    color: var(--bkgt-text-white);
    border: 1px solid var(--bkgt-color-danger);
}

/* States */
:hover    /* Darker red */
:active   /* Even darker red */
:disabled /* Gray */
```

**Use when:** Action is destructive (delete, remove)

```php
bkgt_button( 'Delete' )->danger()->delete_action();
```

### Success Button

```css
.bkgt-btn-success {
    background-color: var(--bkgt-color-success);      /* #27ae60 */
    color: var(--bkgt-text-white);
    border: 1px solid var(--bkgt-color-success);
}

/* States */
:hover    /* Darker green */
:active   /* Even darker green */
:disabled /* Gray */
```

**Use when:** Action confirms a successful workflow

```php
bkgt_button( 'Confirm' )->success();
```

### Warning Button

```css
.bkgt-btn-warning {
    background-color: var(--bkgt-color-warning);      /* #f39c12 */
    color: #000000;
    border: 1px solid var(--bkgt-color-warning);
}

/* States */
:hover    /* Darker orange */
:active   /* Even darker orange */
:disabled /* Gray */
```

**Use when:** Action has important caution considerations

```php
bkgt_button( 'Proceed with Caution' )->warning();
```

### Info Button

```css
.bkgt-btn-info {
    background-color: var(--bkgt-color-info);         /* #3498db lighter */
    color: var(--bkgt-text-white);
    border: 1px solid var(--bkgt-color-info);
}
```

**Use when:** Action provides information or help

```php
bkgt_button( 'Learn More' )->info();
```

### Text Button (Link Style)

```css
.bkgt-btn-text {
    background-color: transparent;
    border: none;
    color: var(--bkgt-color-primary);
    text-decoration: underline;
}
```

**Use when:** Minimal action, inline with text

```php
bkgt_button( 'Edit' )->text();
```

### Outline Button

```css
.bkgt-btn-outline {
    background-color: transparent;
    border: 1px solid var(--bkgt-border-color);
    color: var(--bkgt-text-primary);
}

/* States */
:hover    /* Light background with primary border */
:active   /* Darker background with primary border */
```

**Use when:** Secondary action with emphasis

```php
bkgt_button( 'Import' )->outline();
```

---

## Sizes

### Small Button

```css
.bkgt-btn-sm {
    padding: var(--bkgt-button-padding-y-sm) var(--bkgt-button-padding-x-sm);
    font-size: var(--bkgt-font-size-sm);
}
```

**Use when:** Space is limited or action is minor

```php
bkgt_button( 'Small' )->small();
// or
bkgt_button( 'Small' )->size( 'sm' );
```

### Medium Button (Default)

No class needed, default styling.

```php
bkgt_button( 'Medium' )->primary();
```

### Large Button

```css
.bkgt-btn-lg {
    padding: var(--bkgt-button-padding-y-lg) var(--bkgt-button-padding-x-lg);
    font-size: var(--bkgt-font-size-lg);
}
```

**Use when:** Primary action needs prominence or touch targets

```php
bkgt_button( 'Large' )->large();
// or
bkgt_button( 'Large' )->size( 'lg' );
```

### Block Button (Full Width)

```css
.bkgt-btn-block {
    width: 100%;
    display: flex;
}
```

**Use when:** Button spans full container width

```php
bkgt_button( 'Full Width' )->block();
// or
bkgt_button( 'Full Width' )->setBlock( true );
```

---

## PHP Builder API

The `BKGT_Button_Builder` class provides a fluent interface for creating buttons.

### Construction

```php
// Method 1: Direct instantiation
$button = new BKGT_Button_Builder( 'Click Me' );

// Method 2: Static factory
$button = BKGT_Button_Builder::create( 'Click Me' );

// Method 3: Helper function
$button = bkgt_button( 'Click Me' );
```

### Builder Methods

#### Content Methods

```php
// Set button text
bkgt_button( 'Old Text' )
    ->text( 'New Text' );

// Add HTML content
bkgt_button()
    ->html( '<strong>Bold</strong> Button' );

// Add icon
bkgt_button( 'Download' )
    ->icon( 'fa-download' );  // Font Awesome
    
bkgt_button( 'Download' )
    ->icon( '<svg>...</svg>' ); // Custom SVG
```

#### Variant Methods

```php
// Set variant
bkgt_button( 'Save' )->variant( 'primary' );

// Quick variant methods
bkgt_button( 'Save' )->primary();
bkgt_button( 'Cancel' )->secondary();
bkgt_button( 'Delete' )->danger();
bkgt_button( 'Confirm' )->success();
bkgt_button( 'Caution' )->warning();
bkgt_button( 'Help' )->info();
bkgt_button( 'Link' )->text();
bkgt_button( 'More' )->outline();
```

#### Size Methods

```php
// Set size
bkgt_button( 'Button' )->size( 'lg' );

// Quick size methods
bkgt_button( 'Button' )->small();
bkgt_button( 'Button' )->large();

// Block (full width)
bkgt_button( 'Button' )->block();
bkgt_button( 'Button' )->setBlock( false );
```

#### Attribute Methods

```php
// Set button type (submit, reset, button - default is button)
bkgt_button( 'Save' )->type( 'submit' );

// Set name
bkgt_button()->name( 'action' );

// Set value
bkgt_button()->value( 'submit' );

// Set ID
bkgt_button( 'Button' )->id( 'my-button' );

// Add custom attribute
bkgt_button()->attr( 'data-action', 'save' );

// Add data attribute
bkgt_button()->data( 'modal_id', 'save-modal' );

// Set disabled
bkgt_button()->disabled( true );
bkgt_button()->disabled( false );

// Add click handler
bkgt_button()->onClick( "alert('Clicked!');" );
```

#### Class Methods

```php
// Add custom class
bkgt_button( 'Button' )->addClass( 'my-class' );

// Remove class
bkgt_button( 'Button' )->removeClass( 'my-class' );
```

#### Accessibility Methods

```php
// Add ARIA label
bkgt_button( '✓' )->ariaLabel( 'Confirm' );

// Semantic action methods
bkgt_button( 'Save' )->primary_action();      // Primary + class + label
bkgt_button( 'Cancel' )->secondary_action();  // Secondary + class + label
bkgt_button( 'Delete' )->delete_action();     // Danger + class + label
bkgt_button( 'Abandon' )->cancel_action();    // Secondary + class + label
```

#### Output Methods

```php
// Build HTML string
$html = bkgt_button( 'Click' )->build();

// Echo HTML
bkgt_button( 'Click' )->render();

// Magic __toString()
echo bkgt_button( 'Click' )->primary();
```

### Fluent API Example

```php
<?php
echo bkgt_button( 'Save Document' )
    ->primary()
    ->large()
    ->type( 'submit' )
    ->id( 'save-btn' )
    ->name( 'action' )
    ->value( 'save' )
    ->data( 'action', 'save_document' )
    ->ariaLabel( 'Save the current document' )
    ->addClass( 'form-action' );
?>
```

---

## JavaScript API

The `BKGTButton` class provides utilities for interacting with buttons via JavaScript.

### Construction

```javascript
// From element reference
const btn = new BKGTButton(document.getElementById('my-btn'));

// From selector string
const btn = new BKGTButton('#my-btn');

// Static factory
const btn = BKGTButton.create('#my-btn');

// Multiple buttons
const buttons = BKGTButton.createAll('.action-btn');
```

### Instance Methods

#### State Management

```javascript
// Set loading state
btn.setLoading(true);
btn.setLoading(true, '⏳ Processing...');
btn.clearLoading();
btn.toggleLoading();

// Check state
if (btn.isLoading) {
    console.log('Button is processing...');
}

// Check if disabled
if (btn.isDisabled()) {
    console.log('Button is disabled');
}
```

#### Enable/Disable

```javascript
// Disable button
btn.disable();

// Enable button
btn.enable();

// Check state
const isDisabled = btn.isDisabled();
```

#### Text Management

```javascript
// Set text
btn.setText('New Text');

// Get text
const text = btn.getText();
```

#### Styling

```javascript
// Change variant
btn.addVariant('danger');

// Change size
btn.setSize('lg');

// Make full width
btn.setBlock(true);
btn.setBlock(false);
```

#### Async Operations

```javascript
// Perform async action with automatic loading state
btn.perform(async () => {
    const response = await fetch('/api/save');
    return response.json();
})
.then(data => console.log('Success:', data))
.catch(error => console.error('Error:', error));
```

#### Feedback States

```javascript
// Show success feedback
btn.showSuccess(2000); // Duration in ms

// Show error feedback
btn.showError('Oops! Something went wrong', 2000);
btn.showError();  // Uses original text
```

#### Event Handling

```javascript
// Add click handler
btn.onClick(function(button) {
    console.log('Button clicked!');
});

// Custom event listener
document.addEventListener('bkgtButtonClick', (e) => {
    console.log('Button was clicked:', e.detail.button);
});
```

#### Misc

```javascript
// Get HTML element
const element = btn.getElement();

// Destroy instance
btn.destroy();
```

### Static Methods

```javascript
// Create instance from selector
const btn = BKGTButton.create('#my-btn');

// Create multiple instances
const buttons = BKGTButton.createAll('.action-btn');

// Add handler to all buttons
BKGTButton.onAll('.save-btn', function(btn) {
    btn.setLoading(true);
});

// Disable all buttons
BKGTButton.disableAll('.form-btn');

// Enable all buttons
BKGTButton.enableAll('.form-btn');

// Set loading on all buttons
BKGTButton.setAllLoading('.action-btn', true, '⏳ Loading...');
BKGTButton.setAllLoading('.action-btn', false);
```

### Event Handling

```javascript
// Custom event is dispatched before click
btn.onClick(() => {
    console.log('Button clicked');
});

// Listen to custom event
document.addEventListener('bkgtButtonClick', (event) => {
    const button = event.detail.button;
    console.log('Custom event triggered');
});
```

---

## Button Groups

### JavaScript Implementation

```javascript
// Create button group (checkbox mode - multiple selection)
const group = new BKGTButtonGroup('.btn-group', {
    type: 'checkbox',
    onSelect: (btn) => console.log('Selected:', btn),
    onDeselect: (btn) => console.log('Deselected:', btn)
});

// Create button group (radio mode - single selection)
const group = new BKGTButtonGroup('.btn-group', {
    type: 'radio'
});

// Get selected buttons
const selected = group.getSelected();

// Get selected values
const values = group.getSelectedValues();

// Clear selection
group.clearSelection();

// Disable/enable all
group.disableAll();
group.enableAll();

// Destroy
group.destroy();
```

### HTML Example

```html
<!-- Auto-init button group -->
<div class="bkgt-btn-group" data-bkgt-button-group="checkbox">
    <button class="bkgt-btn bkgt-btn-outline">Option 1</button>
    <button class="bkgt-btn bkgt-btn-outline">Option 2</button>
    <button class="bkgt-btn bkgt-btn-outline">Option 3</button>
</div>

<!-- Radio group (single selection) -->
<div class="bkgt-btn-group" data-bkgt-button-group="radio">
    <button class="bkgt-btn">Yes</button>
    <button class="bkgt-btn">No</button>
</div>
```

---

## CSS Utility Classes

### Text Color

```css
.bkgt-text-primary      /* Primary color */
.bkgt-text-success      /* Success color */
.bkgt-text-danger       /* Danger color */
.bkgt-text-warning      /* Warning color */
.bkgt-text-info         /* Info color */
.bkgt-text-muted        /* Muted color */
```

### Background Color

```css
.bkgt-bg-primary        /* Primary background */
.bkgt-bg-success        /* Success background */
.bkgt-bg-danger         /* Danger background */
.bkgt-bg-warning        /* Warning background */
.bkgt-bg-info           /* Info background */
```

### Padding

```css
.bkgt-p-sm    /* Padding: var(--bkgt-padding-sm) */
.bkgt-p-md    /* Padding: var(--bkgt-padding-md) */
.bkgt-p-lg    /* Padding: var(--bkgt-padding-lg) */
.bkgt-px-md   /* Horizontal padding */
.bkgt-py-md   /* Vertical padding */
```

### Spacing

```css
.bkgt-m-sm    /* Margin: small */
.bkgt-m-md    /* Margin: medium */
.bkgt-mb-md   /* Margin-bottom */
.bkgt-gap-md  /* Gap between flex items */
```

### Sizing

```css
.bkgt-w-full  /* Width: 100% */
.bkgt-h-full  /* Height: 100% */
```

### Display

```css
.bkgt-d-none        /* display: none */
.bkgt-d-block       /* display: block */
.bkgt-d-inline      /* display: inline */
.bkgt-d-inline-block /* display: inline-block */
.bkgt-d-flex        /* display: flex */
.bkgt-d-grid        /* display: grid */
.bkgt-d-grid-2      /* display: grid; grid-template-columns: 1fr 1fr; */
.bkgt-d-grid-3      /* display: grid; grid-template-columns: 1fr 1fr 1fr; */
```

### Flexbox

```css
.bkgt-flex-center    /* Centered flex layout */
.bkgt-flex-between   /* Space-between flex */
.bkgt-flex-column    /* flex-direction: column */
.bkgt-flex-gap-md    /* Gap with medium spacing */
```

### Positioning & Visibility

```css
.bkgt-visible        /* visibility: visible */
.bkgt-invisible      /* visibility: hidden */
.bkgt-opacity-50     /* opacity: 0.5 */
.bkgt-opacity-75     /* opacity: 0.75 */
```

### Cursors

```css
.bkgt-cursor-pointer   /* cursor: pointer */
.bkgt-cursor-default   /* cursor: default */
.bkgt-cursor-disabled  /* cursor: not-allowed */
```

---

## Loading States

### CSS Loading Spinner

```css
.bkgt-btn-loading {
    color: transparent;
    position: relative;
}

.bkgt-btn-loading::after {
    /* Animated spinner */
    animation: bkgt-spin 0.6s linear infinite;
}
```

### JavaScript Loading State

```javascript
// Show loading with default spinner
btn.setLoading(true);

// Show loading with custom text
btn.setLoading(true, '⏳ Processing...');

// Clear loading state
btn.clearLoading();

// In async operation
try {
    btn.setLoading(true);
    const result = await fetch('/api/action');
    btn.clearLoading();
    btn.showSuccess();
} catch (error) {
    btn.clearLoading();
    btn.showError();
}
```

---

## Accessibility Features

### Built-In Accessibility

1. **Focus Management**
   - Visible focus indicators
   - Keyboard navigation support
   - High contrast mode enhancement

2. **ARIA Labels**
   ```php
   bkgt_button('✓')
       ->ariaLabel('Confirm action')
       ->primary();
   ```

3. **Semantic Actions**
   ```php
   bkgt_button('Delete')
       ->delete_action();  // Adds aria-label automatically
   ```

4. **High Contrast Mode**
   - Enhanced borders in high contrast
   - Thicker focus outlines
   - Better color contrast

5. **Reduced Motion**
   - Animations disabled for users with motion preferences
   - Smooth transitions replaced with instant changes

### Accessibility Checklist

- [ ] Use semantic button types (submit, reset, button)
- [ ] Add aria-label for icon-only buttons
- [ ] Use appropriate color variants for meaning
- [ ] Ensure adequate button size for touch (44x44px minimum)
- [ ] Test with keyboard navigation
- [ ] Verify color contrast ratios (WCAG AA minimum)
- [ ] Test with screen readers
- [ ] Test with high contrast mode enabled
- [ ] Test with reduced motion preference enabled

---

## Responsive Behavior

### Mobile Touch Targets

Buttons automatically adjust on mobile:
- Minimum height: 44px
- Minimum width: 44px
- Larger padding for touch accuracy

```css
@media (max-width: 768px) {
    .bkgt-btn {
        min-height: 44px;
        min-width: 44px;
    }
}
```

### Responsive Layout with Buttons

```php
<!-- Stacks vertically on mobile, horizontally on desktop -->
<div class="bkgt-d-flex bkgt-flex-column bkgt-gap-md">
    <?php
    echo bkgt_button('Save')->primary()->block();
    echo bkgt_button('Cancel')->secondary()->block();
    ?>
</div>

<!-- Using CSS grid for 2 columns -->
<div class="bkgt-d-grid-2 bkgt-gap-md">
    <?php
    echo bkgt_button('Yes')->success();
    echo bkgt_button('No')->danger();
    ?>
</div>
```

---

## Integration Examples

### With Forms

```php
<?php
// In template or plugin
$form = bkgt_form_builder( 'contact_form' )
    ->method( 'POST' )
    ->action( admin_url( 'admin-ajax.php' ) );

// Add fields...
$form->field( 'name' )->text()->label( 'Your Name' );
$form->field( 'email' )->email()->label( 'Email' );

// Add buttons
$form->html( '<div class="bkgt-form-footer">' );
$form->html( bkgt_button('Send Message')->primary()->type('submit') );
$form->html( bkgt_button('Reset')->secondary()->type('reset') );
$form->html( '</div>' );

$form->render();
?>
```

### With Modals

```javascript
// Create button that opens modal
const btn = new BKGTButton('#open-modal-btn');
const modal = new BKGTModal({
    id: 'confirmation-modal',
    title: 'Confirm Action'
});

btn.onClick(() => {
    modal.open('<p>Are you sure?</p>' +
        '<div class="bkgt-modal-footer">' +
        '<button class="bkgt-btn bkgt-btn-danger">Delete</button>' +
        '<button class="bkgt-btn bkgt-btn-secondary">Cancel</button>' +
        '</div>');
});
```

### Admin Notice with Buttons

```php
<?php
?>
<div class="notice notice-info">
    <p>Update available</p>
    <div>
        <?php
        echo bkgt_button('Update Now')->success();
        echo bkgt_button('Dismiss')->secondary();
        ?>
    </div>
</div>
<?php
?>
```

---

## Migration from Old Buttons

### Before (Bootstrap)

```html
<button class="btn btn-primary btn-lg">Click</button>
<button class="btn btn-danger">Delete</button>
```

### After (BKGT)

```php
<?php echo bkgt_button('Click')->primary()->large(); ?>
<?php echo bkgt_button('Delete')->danger(); ?>
```

---

## Performance Considerations

1. **CSS**: Variables cached by browser, minimal overhead
2. **JavaScript**: Lazy-loaded only when needed
3. **Auto-init**: Uses event delegation for efficiency
4. **Memory**: Button instances cleaned up with destroy()

---

## Browser Support

- Chrome 85+
- Firefox 78+
- Safari 14+
- Edge 85+
- IE 11 (with polyfills)

---

## Troubleshooting

### Button not styled
- Check that `bkgt-variables.css` and `bkgt-buttons.css` are enqueued
- Verify button has `bkgt-btn` class
- Check for CSS conflicts with other frameworks

### JavaScript not working
- Verify `bkgt-buttons.js` is enqueued
- Check console for errors
- Ensure element exists before creating instance
- Verify `data-bkgt-button` attribute for auto-init

### Loading state not showing
- Check browser supports CSS animations
- Verify `prefers-reduced-motion` isn't causing issues
- Check element isn't disabled elsewhere

### Accessibility issues
- Add `aria-label` to icon-only buttons
- Use semantic button types
- Test with keyboard and screen readers
- Verify color contrast with WCAG validator

---

## Related Documentation

- [CSS Variables Guide](BKGTCSS_VARIABLES_GUIDE.md)
- [Form System Guide](BKGTFORM_DEVELOPER_GUIDE.md)
- [Modal System Guide](BKGTMODAL_DEVELOPER_GUIDE.md)

---

## Examples Repository

Full working examples available in `/examples/buttons/`:
- Single buttons with all variants
- Button groups
- Loading states
- Form integration
- Modal integration
- Accessibility patterns

---

**Last Updated:** PHASE 2 Step 4  
**Maintained By:** BKGT Development Team  
**License:** GPL v2 or later
