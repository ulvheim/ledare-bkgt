# BKGTForm Migration Guide

**Version:** 1.0.0  
**Date:** November 2, 2025  
**For:** Plugin Developers

---

## 📋 Quick Migration Checklist

- [ ] Identify all forms in your plugin
- [ ] Create BKGTForm or use Form Builder
- [ ] Replace old form HTML/JavaScript
- [ ] Migrate validation logic
- [ ] Test thoroughly
- [ ] Remove old form CSS
- [ ] Update documentation

---

## 🔄 Migration Patterns

### Pattern 1: Simple HTML Form → BKGTForm

#### BEFORE (Old HTML Form)

```php
<?php
// Old form rendering
?>
<form method="POST" id="contact-form">
    <?php wp_nonce_field( 'contact_form', 'nonce' ); ?>
    
    <div class="form-group">
        <label for="name">Namn</label>
        <input type="text" id="name" name="name" required>
        <span class="error"></span>
    </div>
    
    <div class="form-group">
        <label for="email">E-postadress</label>
        <input type="email" id="email" name="email" required>
        <span class="error"></span>
    </div>
    
    <div class="form-group">
        <label for="message">Meddelande</label>
        <textarea id="message" name="message" rows="5" required></textarea>
        <span class="error"></span>
    </div>
    
    <button type="submit">Skicka</button>
</form>

<script>
jQuery(function($) {
    $('#contact-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var name = form.find('[name="name"]').val();
        var email = form.find('[name="email"]').val();
        var message = form.find('[name="message"]').val();
        
        // Basic validation
        var errors = false;
        if (!name) {
            form.find('[name="name"]').next('.error').text('Ange namn').show();
            errors = true;
        }
        if (!email || !email.includes('@')) {
            form.find('[name="email"]').next('.error').text('Ange giltig email').show();
            errors = true;
        }
        if (!message) {
            form.find('[name="message"]').next('.error').text('Ange meddelande').show();
            errors = true;
        }
        
        if (errors) return;
        
        // Submit
        $.post(ajaxurl, {
            action: 'submit_contact_form',
            nonce: form.find('[name="nonce"]').val(),
            name: name,
            email: email,
            message: message
        }, function(response) {
            if (response.success) {
                form.html('<p>Tack för ditt meddelande!</p>');
            } else {
                alert('Ett fel uppstod: ' + response.data);
            }
        });
    });
});
</script>
```

#### AFTER (Using BKGTForm)

```php
<?php
// Option 1: PHP-based rendering
$form = bkgt_form_builder( 'contact-form' )
    ->add_text( 'name', 'Namn', [ 'required' => true ] )
    ->add_email( 'email', 'E-postadress', [ 'required' => true ] )
    ->add_textarea( 'message', 'Meddelande', [ 'required' => true, 'rows' => 5 ] )
    ->set_submit_text( 'Skicka' )
    ->set_config( [
        'ajax' => [
            'action' => 'submit_contact_form',
            'nonce' => wp_create_nonce( 'submit_contact_form' )
        ]
    ] );

echo $form->render_in_container( 'contact-form-container' );
?>

<!-- Or Option 2: JavaScript-based rendering -->
<div id="contact-form-container"></div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = new BKGTForm({
        id: 'contact-form',
        fields: [
            { name: 'name', type: 'text', label: 'Namn', required: true },
            { name: 'email', type: 'email', label: 'E-postadress', required: true },
            { name: 'message', type: 'textarea', label: 'Meddelande', required: true, rows: 5 }
        ],
        submitText: 'Skicka',
        ajax: {
            action: 'submit_contact_form',
            nonce: bkgtFormConfig.nonce
        },
        onSubmit: function(data, response) {
            console.log('Form submitted successfully!');
        }
    });
    
    form.render('#contact-form-container');
});
</script>
```

**Benefits of this approach:**
✅ No manual validation code needed  
✅ Consistent styling across plugins  
✅ Accessibility built-in  
✅ Responsive design  
✅ Real-time validation  
✅ Loading states handled automatically  

---

### Pattern 2: Modal Form → BKGTForm + BKGTModal

#### BEFORE (Old Modal Form)

```javascript
// Old modal with inline form
jQuery(function($) {
    $('#open-form-btn').on('click', function() {
        var modal = $('<div class="modal">')
            .html(`
                <div class="modal-content">
                    <h2>Nytt objekt</h2>
                    <form>
                        <input type="text" name="title" placeholder="Titel" />
                        <textarea name="description"></textarea>
                        <button type="submit">Spara</button>
                        <button type="button" class="close">Stäng</button>
                    </form>
                </div>
            `)
            .appendTo('body')
            .show();
        
        // Manual event handling
        modal.on('submit', 'form', function(e) {
            e.preventDefault();
            // ... form submission logic
        });
        
        modal.on('click', '.close', function() {
            modal.remove();
        });
    });
});
```

#### AFTER (Using BKGTForm + BKGTModal)

```javascript
// Modern approach with proper separation of concerns
const form = new BKGTForm({
    id: 'new-item-form',
    fields: [
        { name: 'title', type: 'text', label: 'Titel', required: true },
        { name: 'description', type: 'textarea', label: 'Beskrivning', required: true }
    ],
    submitText: 'Spara',
    onSubmit: function(data) {
        console.log('Item created:', data);
        modal.close();
    },
    onCancel: function() {
        modal.close();
    }
});

const modal = new BKGTModal({
    id: 'new-item-modal',
    title: 'Nytt objekt',
    size: 'medium'
});

// Open modal and render form
document.getElementById('open-form-btn').addEventListener('click', function() {
    modal.open();
    form.render(modal.modal.querySelector('.bkgt-modal-body'));
});
```

---

### Pattern 3: AJAX Form → BKGTForm with Auto-AJAX

#### BEFORE (Manual AJAX handling)

```php
// PHP: Admin handler
add_action('wp_ajax_save_player', function() {
    check_ajax_referer('player_nonce', 'nonce');
    
    $player_data = array(
        'name' => sanitize_text_field($_POST['name']),
        'position' => sanitize_text_field($_POST['position']),
        'number' => intval($_POST['number'])
    );
    
    // Manual validation
    $errors = array();
    if (empty($player_data['name'])) {
        $errors['name'] = 'Namn är obligatoriskt';
    }
    if (!in_array($player_data['position'], ['QB', 'RB', 'WR'])) {
        $errors['position'] = 'Ogiltig position';
    }
    
    if (!empty($errors)) {
        wp_send_json_error($errors);
        return;
    }
    
    // Save to database
    $id = wp_insert_post(/* ... */);
    wp_send_json_success(['id' => $id]);
});
```

```javascript
// JavaScript: Manual AJAX
jQuery(function($) {
    $('#add-player-form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_player',
                nonce: $(this).data('nonce'),
                name: $('[name="name"]').val(),
                position: $('[name="position"]').val(),
                number: $('[name="number"]').val()
            },
            success: function(response) {
                if (response.success) {
                    // Handle success
                } else {
                    // Display errors
                    $.each(response.data, function(field, error) {
                        $('[name="' + field + '"]').after('<span class="error">' + error + '</span>');
                    });
                }
            },
            error: function() {
                alert('AJAX request failed');
            }
        });
    });
});
```

#### AFTER (Automatic AJAX with BKGTForm)

```php
// PHP: Simplified with validation
add_action('wp_ajax_save_player', function() {
    check_ajax_referer('player_nonce', 'nonce');
    
    // Create form and validate/sanitize automatically
    $form = bkgt_form_builder('player-form')
        ->add_text('name', 'Namn', ['required' => true])
        ->add_select('position', 'Position', [
            'required' => true,
            'options' => [
                ['value' => 'QB', 'label' => 'Quarterback'],
                ['value' => 'RB', 'label' => 'Running Back'],
                ['value' => 'WR', 'label' => 'Wide Receiver']
            ]
        ])
        ->add_number('number', 'Tröjnummer', ['required' => true, 'min' => 0, 'max' => 99]);
    
    // Validate
    $validation = $form->validate($_POST);
    if ($validation !== true) {
        wp_send_json_error($validation);
        return;
    }
    
    // Sanitize
    $data = $form->sanitize($_POST);
    
    // Save to database
    $id = wp_insert_post(/* ... */);
    wp_send_json_success(['id' => $id, 'message' => 'Spelare skapad!']);
});
```

```javascript
// JavaScript: Automatic AJAX with BKGTForm
const form = new BKGTForm({
    id: 'player-form',
    fields: [
        { name: 'name', type: 'text', label: 'Namn', required: true },
        {
            name: 'position',
            type: 'select',
            label: 'Position',
            required: true,
            options: [
                { value: 'QB', label: 'Quarterback' },
                { value: 'RB', label: 'Running Back' },
                { value: 'WR', label: 'Wide Receiver' }
            ]
        },
        { name: 'number', type: 'number', label: 'Tröjnummer', required: true, min: 0, max: 99 }
    ],
    ajax: {
        action: 'save_player',
        nonce: bkgtFormConfig.nonce
    },
    onSubmit: function(data, response) {
        console.log('Player created:', response);
        alert('Spelare skapad!');
    }
});

form.render('#player-form-container');
```

**What improved:**
✅ Automatic AJAX handling  
✅ Centralized validation  
✅ Error responses handled automatically  
✅ Loading states managed  
✅ Consistent error display  

---

## 📊 Side-by-Side Comparison

| Feature | Old Way | BKGTForm |
|---------|---------|----------|
| **HTML Structure** | Manual HTML in template | Auto-generated |
| **Validation** | JavaScript + PHP duplicated | Unified with builder |
| **Error Display** | Manual jQuery/DOM manipulation | Built-in with ARIA |
| **AJAX Submission** | Manual $.ajax() calls | Automatic |
| **Styling** | Custom CSS per form | Unified system |
| **Accessibility** | May be missing | WCAG AA compliant |
| **Responsive** | Custom breakpoints | Built-in mobile |
| **Loading State** | Manual UI updates | Automatic |
| **Code Lines** | 50-100 lines per form | 10-20 lines |

---

## 🚀 Step-by-Step Migration Process

### Step 1: Identify Forms to Migrate

```bash
# Search for old form patterns in your plugin
grep -r "<form" wp-content/plugins/your-plugin/
grep -r "jQuery.*submit" wp-content/plugins/your-plugin/
grep -r "\.ajax(" wp-content/plugins/your-plugin/
```

### Step 2: Create Form Configuration

```php
// In your plugin's forms directory
$contact_form = bkgt_form_builder('contact-form')
    ->add_text('name', 'Namn', ['required' => true])
    ->add_email('email', 'E-postadress', ['required' => true])
    ->add_textarea('message', 'Meddelande', ['required' => true]);
```

### Step 3: Replace Form Rendering

**Old:**
```php
include 'templates/contact-form.php';
```

**New:**
```php
echo $contact_form->render_in_container('contact-form-container');
```

### Step 4: Replace Validation

**Old:**
```javascript
// 50+ lines of custom validation
```

**New:**
```php
$validation = $form->validate($_POST);
if ($validation !== true) {
    wp_send_json_error($validation);
    return;
}
```

### Step 5: Replace AJAX Handling

**Old:**
```javascript
// 30+ lines of $.ajax()
```

**New:**
```javascript
const form = new BKGTForm({
    fields: [...],
    ajax: { action: 'my_handler', nonce: '...' },
    onSubmit: function(data) { /* handle success */ }
});
```

### Step 6: Remove Old Files

```bash
# Delete old form templates
rm templates/old-contact-form.php
rm templates/old-player-form.php
rm templates/old-event-form.php

# Remove old form CSS
rm assets/css/old-forms.css

# Remove old form JavaScript
rm assets/js/old-form-validation.js
```

### Step 7: Test Thoroughly

✅ Form renders correctly  
✅ Validation works (client + server)  
✅ AJAX submission works  
✅ Error display works  
✅ Success handling works  
✅ Mobile responsive  
✅ Keyboard accessible  
✅ No console errors  

---

## 🎯 Migration Priority

### High Priority (Do First)
- Contact/feedback forms
- User registration forms
- Admin settings forms
- Modal forms frequently used

### Medium Priority (Do Second)
- Data entry forms
- Search/filter forms
- Comment forms
- Optional forms

### Low Priority (Do Last)
- Legacy forms rarely used
- Forms for deprecated features
- Admin-only internal forms

---

## ✅ Testing Checklist

### Functionality Testing
- [ ] Form renders without errors
- [ ] All fields display correctly
- [ ] Required fields show indicator
- [ ] Placeholder text displays
- [ ] Help text displays (if any)
- [ ] Submit button works
- [ ] Cancel button works (if applicable)

### Validation Testing
- [ ] Required validation works
- [ ] Email validation works
- [ ] Number validation works
- [ ] Min/max validation works
- [ ] Date validation works
- [ ] Errors display correctly
- [ ] Error styling applies

### AJAX Testing
- [ ] AJAX submission successful
- [ ] Server receives correct data
- [ ] Errors returned from server
- [ ] Success response handled
- [ ] Loading state displays
- [ ] Loading state clears on completion

### Accessibility Testing
- [ ] Keyboard navigation works
- [ ] Tab order correct
- [ ] ARIA labels present
- [ ] Error messages announced
- [ ] Focus management works
- [ ] Screen reader compatible

### Mobile Testing
- [ ] Form responsive on mobile
- [ ] Touch targets adequate size
- [ ] Layout wraps correctly
- [ ] No horizontal scroll
- [ ] Mobile keyboard appears
- [ ] Form works with mobile browser

---

## 📋 Common Migration Mistakes to Avoid

### ❌ Mistake 1: Not Updating AJAX Handler

```php
// WRONG: Still expecting form-encoded data
$_POST['field']; // May not be set correctly
```

```php
// CORRECT: Use standard array access
$data = array_filter($_POST, function($key) {
    return in_array($key, ['name', 'email'], true);
}, ARRAY_FILTER_USE_KEY);
```

### ❌ Mistake 2: Removing Old CSS Too Soon

```php
// WRONG: Remove CSS before verifying new styling works
wp_dequeue_style('old-form-css');
```

```php
// CORRECT: Keep both temporarily, then remove old
wp_enqueue_style('bkgt-form'); // New
// wp_enqueue_style('old-form-css'); // Commented out temporarily
```

### ❌ Mistake 3: Not Updating Documentation

```php
// WRONG: Leave documentation references to old forms
// Update all @see references in code comments
```

### ❌ Mistake 4: Not Testing Mobile

```javascript
// WRONG: Only test on desktop
// Test on actual mobile devices or mobile emulation
```

### ❌ Mistake 5: Breaking Change Without Notice

```php
// WRONG: Change form ID without migration
// Old code looking for 'old-form-id' breaks

// CORRECT: Keep backward compatibility or communicate change
```

---

## 📞 Support & Help

- **Documentation**: See BKGTFORM_DEVELOPER_GUIDE.md
- **Examples**: Check plugin examples in wp-content/plugins/
- **Issues**: File bugs in development tracker
- **Questions**: Ask in development chat

---

**Last Updated:** November 2, 2025  
**Status:** Production Ready
