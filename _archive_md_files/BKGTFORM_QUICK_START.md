# BKGTForm Quick Start Guide

**Status:** Production Ready  
**Version:** 1.0.0  
**Last Updated:** November 2, 2025

---

## 🚀 5-Minute Quick Start

### Installation
✅ Already installed! Forms are auto-loaded on all pages.

```php
// Forms available globally - no setup needed
$form = bkgt_form_builder('my-form');
```

---

## 📝 Creating Your First Form

### PHP Approach (Server-Side)

```php
<?php
// In your plugin or template
$form = bkgt_form_builder('contact-form')
    ->add_text('name', 'Namn', ['required' => true])
    ->add_email('email', 'E-postadress', ['required' => true])
    ->add_textarea('message', 'Meddelande', ['required' => true])
    ->set_submit_text('Skicka')
    ->set_config([
        'ajax' => [
            'action' => 'my_form_handler',
            'nonce' => wp_create_nonce('my_form_action')
        ]
    ]);

echo $form->render_in_container('contact-form-container');
?>
```

### JavaScript Approach (Client-Side)

```javascript
// Create form with configuration
const form = new BKGTForm({
    id: 'contact-form',
    fields: [
        { name: 'name', type: 'text', label: 'Namn', required: true },
        { name: 'email', type: 'email', label: 'E-postadress', required: true },
        { name: 'message', type: 'textarea', label: 'Meddelande', required: true }
    ],
    submitText: 'Skicka',
    ajax: {
        action: 'my_form_handler',
        nonce: bkgtFormConfig.nonce
    },
    onSubmit: function(data, response) {
        console.log('Form submitted!', response);
    }
});

// Render into container
form.render('#contact-form-container');
```

### HTML Structure

```html
<!-- Create a container for the form -->
<div id="contact-form-container"></div>

<!-- Form will be rendered here automatically -->
```

---

## 🎨 Styling & Layouts

### Multiple Layout Options

```php
// Vertical layout (default)
->set_layout('vertical')

// Horizontal layout (2 columns)
->set_layout('horizontal')

// Grid layout (auto-fit columns)
->set_layout('grid')
```

### Styling the Form

The form automatically uses the unified BKGTForm CSS. To customize:

```css
/* Target your specific form */
#my-form .bkgt-form-group {
    margin-bottom: 2rem;
}

#my-form .bkgt-form-input {
    border-radius: 8px;
}
```

---

## 🔍 Field Types Reference

### Text Input
```php
->add_text('username', 'Användarnamn', [
    'required' => true,
    'min_length' => 3,
    'max_length' => 20,
    'help' => 'Min 3, max 20 tecken'
])
```

### Email Input
```php
->add_email('email', 'E-postadress', [
    'required' => true,
    'help' => 'Vi kommer aldrig dela din email'
])
```

### Password Input
```php
->add_password('password', 'Lösenord', [
    'required' => true,
    'min_length' => 8
])
```

### Textarea
```php
->add_textarea('message', 'Meddelande', [
    'required' => true,
    'rows' => 5,
    'max_length' => 500
])
```

### Select Dropdown
```php
->add_select('position', 'Position', [
    'required' => true,
    'options' => [
        ['value' => 'qb', 'label' => 'Quarterback'],
        ['value' => 'rb', 'label' => 'Running Back'],
        ['value' => 'wr', 'label' => 'Wide Receiver']
    ]
])
```

### Checkbox
```php
->add_checkbox('agree', 'Jag accepterar villkoren', [
    'required' => true
])
```

### Radio Buttons
```php
->add_radio('type', 'Välj typ', [
    'required' => true,
    'options' => [
        ['value' => 'personal', 'label' => 'Personlig'],
        ['value' => 'business', 'label' => 'Affär']
    ]
])
```

### Date Input
```php
->add_date('birthdate', 'Födelsedag', [
    'required' => true,
    'format' => 'YYYY-MM-DD'
])
```

### Number Input
```php
->add_number('age', 'Ålder', [
    'required' => true,
    'min' => 0,
    'max' => 120
])
```

### Phone Input
```php
->add_phone('phone', 'Telefon', [
    'required' => false,
    'help' => 'Format: +46 XXX XXX XXX'
])
```

### URL Input
```php
->add_url('website', 'Webbplats', [
    'required' => false
])
```

### Hidden Field
```php
->add_field('hidden_id', 'hidden', null, [
    'value' => '123'
])
```

---

## ✅ Validation

### Client-Side (Real-Time)
```javascript
// Validation happens automatically on blur/input
// Errors display with red border and message
```

### Server-Side (Secure)
```php
// In your AJAX handler
add_action('wp_ajax_my_form_handler', function() {
    check_ajax_referer('my_form_action', 'nonce');
    
    $form = bkgt_form_builder('my-form')
        ->add_text('name', 'Name', ['required' => true])
        ->add_email('email', 'Email', ['required' => true]);
    
    // Validate and sanitize
    $validation = $form->validate($_POST);
    if ($validation !== true) {
        wp_send_json_error($validation); // Send errors back to form
        return;
    }
    
    $data = $form->sanitize($_POST);
    
    // Process form data
    // ...
    
    wp_send_json_success(['message' => 'Formulär skickat!']);
});
```

### Custom Validation
```javascript
const form = new BKGTForm({
    fields: [
        {
            name: 'password',
            type: 'password',
            label: 'Lösenord',
            validate: function(value) {
                if (value.length < 8) {
                    return 'Lösenordet måste vara minst 8 tecken';
                }
                if (!/[A-Z]/.test(value)) {
                    return 'Lösenordet måste innehålla en stor bokstav';
                }
                return true; // Valid
            }
        }
    ]
});
```

---

## 📡 AJAX Submission

### Automatic AJAX
```javascript
const form = new BKGTForm({
    fields: [/* ... */],
    ajax: {
        action: 'my_handler',
        nonce: bkgtFormConfig.nonce
    },
    onSubmit: function(data, response) {
        // Form submitted successfully
        console.log('Success:', response);
    },
    onError: function(error) {
        // Form submission failed
        console.error('Error:', error);
    }
});
```

### Manual Submission
```javascript
const form = new BKGTForm({
    fields: [/* ... */],
    ajax: false, // Disable automatic AJAX
    onSubmit: function(data) {
        // Handle submission manually
        console.log('Form data:', data);
        
        // Make your own AJAX call
        fetch(ajaxurl, {
            method: 'POST',
            body: new FormData(/* ... */)
        });
    }
});
```

---

## 🎯 Form Actions

### Get Form Data
```javascript
const data = form.getFormData();
console.log(data); // { name: 'John', email: 'john@example.com' }
```

### Set Form Data
```javascript
form.setFormData({
    name: 'John Doe',
    email: 'john@example.com'
});
```

### Clear Form
```javascript
form.clear();
```

### Validate Specific Field
```javascript
const isValid = form.validateField('email');
if (!isValid) {
    console.log('Email is invalid');
}
```

### Validate Entire Form
```javascript
const isValid = form.validate();
if (isValid) {
    console.log('All fields are valid');
} else {
    console.log('Form has errors');
}
```

---

## 🎪 Using Forms in Modals

### Combined Modal + Form
```javascript
// Create form
const form = new BKGTForm({
    id: 'edit-player-form',
    fields: [
        { name: 'name', type: 'text', label: 'Name', required: true },
        { name: 'position', type: 'text', label: 'Position' }
    ],
    onSubmit: function() {
        modal.close(); // Close modal when done
    }
});

// Create modal
const modal = new BKGTModal({
    id: 'player-modal',
    title: 'Edit Player',
    size: 'medium'
});

// Open modal and render form
document.getElementById('edit-btn').addEventListener('click', function() {
    modal.open();
    form.render(modal.modal.querySelector('.bkgt-modal-body'));
});
```

---

## 🧪 Testing Your Form

### Test Validation
```javascript
// Test required field
form.validateField('name'); // Should fail if empty

// Test email validation
form.validateField('email'); // Should validate email format

// Test entire form
form.validate(); // Should return true/false
```

### Test Data Extraction
```javascript
// Fill form with test data
form.setFormData({ name: 'Test', email: 'test@example.com' });

// Extract data
const data = form.getFormData();
console.log(data); // Should match what you set
```

### Test AJAX
```javascript
// Check browser network tab
// Should see POST request to admin-ajax.php
// Should include action and nonce
// Should receive success/error response
```

---

## 🐛 Common Issues & Solutions

### Problem: Form not rendering
```javascript
// Make sure container exists
if (!document.getElementById('form-container')) {
    console.error('Container not found!');
}

// Make sure form.render() is called
form.render('#form-container');
```

### Problem: Validation not working
```javascript
// Check that BKGTForm loaded
if (typeof BKGTForm === 'undefined') {
    console.error('BKGTForm not loaded');
}

// Check field configuration
console.log(form.config.fields);
```

### Problem: AJAX not submitting
```javascript
// Check nonce is provided
ajax: {
    action: 'my_handler',
    nonce: bkgtFormConfig.nonce // Must be defined
}

// Check AJAX handler exists
// Test with: wp_localize_script() in PHP
```

### Problem: Styling not applied
```javascript
// Check CSS loaded
// Open DevTools → Sources tab
// Search for 'bkgt-form.css'

// Check form container
console.log(document.getElementById('form-container'));
```

---

## 📚 Learn More

- **Full Documentation:** See `BKGTFORM_DEVELOPER_GUIDE.md`
- **Migration Guide:** See `BKGTFORM_MIGRATION_GUIDE.md`
- **Examples:** Check plugin implementations
- **Support:** Ask in development chat

---

## 🎓 Next Steps

1. ✅ Read this quick start
2. 📖 Read the full developer guide
3. 💻 Create your first form
4. 🧪 Test validation and AJAX
5. 🚀 Deploy to your plugin

---

**Good luck with your forms! 🎉**

For questions, refer to the full documentation or ask in development chat.
