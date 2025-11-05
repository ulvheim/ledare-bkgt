# BKGT Form Component System - Developer Guide

**Version:** 1.0.0  
**Date:** November 2, 2025  
**Status:** Production Ready

---

## 🎯 Overview

The **BKGTForm** system provides a unified, accessible, and developer-friendly way to build forms across all BKGT plugins. It includes:

- **BKGTForm class** - JavaScript component for client-side form rendering and validation
- **BKGT_Form_Builder class** - PHP helper class for server-side form configuration
- **Comprehensive CSS** - Responsive, accessible, and themable form styling
- **Form validation** - Both client-side (real-time) and server-side validation
- **BKGTModal integration** - Forms work seamlessly inside modals
- **Accessibility** - WCAG AA compliant with ARIA labels and semantic HTML

---

## 📋 Quick Start

### Basic Form in PHP

```php
<?php
// Create a form using the builder
$form = bkgt_form_builder( 'contact-form', [
    'layout' => 'vertical',
    'submitText' => 'Skicka',
] )
    ->add_text( 'name', 'Namn', [ 'required' => true ] )
    ->add_email( 'email', 'E-postadress', [ 'required' => true ] )
    ->add_textarea( 'message', 'Meddelande', [ 'required' => true, 'rows' => 5 ] );

// Validate form data
if ( $_POST ) {
    $validation = $form->validate( $_POST );
    if ( $validation === true ) {
        // Form is valid, process data
        $data = $form->sanitize( $_POST );
        // Handle submission...
    } else {
        // Display errors
        $errors = $validation;
    }
}

// Render form
echo $form->render_in_container( 'contact-form-container' );
?>
```

### Basic Form in JavaScript

```javascript
// Create a form
const form = new BKGTForm({
    id: 'login-form',
    fields: [
        { name: 'username', type: 'text', label: 'Användarnamn', required: true },
        { name: 'password', type: 'password', label: 'Lösenord', required: true }
    ],
    onSubmit: function(data) {
        console.log('Form submitted:', data);
    }
});

// Render into container
form.render('#login-form-container');

// Get form data
const data = form.getFormData();

// Validate
const isValid = form.validate();

// Clear form
form.clear();
```

---

## 📖 Detailed Usage

### PHP Form Builder API

#### Creating a Form

```php
$form = bkgt_form_builder( 'my-form-id', [
    'layout' => 'vertical',        // vertical, horizontal, grid
    'submitText' => 'Skicka',       // Submit button text
    'cancelText' => 'Avbryt',       // Cancel button text
    'showCancel' => true,           // Show/hide cancel button
    'ajax' => [                     // Optional AJAX configuration
        'action' => 'my_form_handler',
        'nonce' => wp_create_nonce( 'my_nonce' )
    ]
]);
```

#### Adding Fields

```php
// Text field
$form->add_text( 'username', 'Användarnamn', [
    'required' => true,
    'placeholder' => 'Ange användarnamn',
    'minLength' => 3,
    'maxLength' => 20,
    'help' => '3-20 tecken'
]);

// Email field
$form->add_email( 'email', 'E-postadress', [
    'required' => true,
    'placeholder' => 'example@domain.com'
]);

// Password field
$form->add_password( 'password', 'Lösenord', [
    'required' => true,
    'minLength' => 8
]);

// Textarea field
$form->add_textarea( 'message', 'Meddelande', [
    'required' => true,
    'rows' => 5,
    'maxLength' => 500
]);

// Select field
$form->add_select( 'category', 'Kategori', [
    'required' => true,
    'options' => [
        ['value' => 'sports', 'label' => 'Sport'],
        ['value' => 'news', 'label' => 'Nyheter'],
        ['value' => 'other', 'label' => 'Övriga']
    ]
]);

// Checkbox field
$form->add_checkbox( 'agree', 'Jag godkänner villkoren', [
    'required' => true
]);

// Radio field
$form->add_radio( 'gender', 'Kön', [
    'options' => [
        ['value' => 'male', 'label' => 'Man'],
        ['value' => 'female', 'label' => 'Kvinna'],
        ['value' => 'other', 'label' => 'Annat']
    ]
]);

// Date field
$form->add_date( 'birth_date', 'Födelsedatum', [
    'required' => true
]);

// Number field
$form->add_number( 'age', 'Ålder', [
    'min' => 0,
    'max' => 120
]);

// Phone field
$form->add_phone( 'phone', 'Telefonnummer' );

// URL field
$form->add_url( 'website', 'Webbplats' );

// Hidden field
$form->add_hidden( 'user_id', get_current_user_id() );
```

#### Form Validation

```php
// Validate form data
$validation = $form->validate( $_POST );

if ( $validation === true ) {
    echo 'Form is valid!';
} else {
    $errors = $validation; // Array of field => error message
    foreach ( $errors as $field => $error ) {
        echo "$field: $error";
    }
}
```

#### Form Sanitization

```php
// Sanitize form data based on field types
$clean_data = $form->sanitize( $_POST );

// Each field is sanitized according to its type:
// - email: sanitize_email()
// - url: esc_url()
// - number: cast to float
// - textarea: wp_kses_post()
// - checkbox: cast to boolean
// - default: sanitize_text_field()
```

#### Rendering Forms

```php
// Render form with inline container
echo $form->render_in_container( 'my-form-container' );
// Output: <div id="my-form-container"></div> + <script>...</script>

// Get form configuration as array (for use in JavaScript)
$config = $form->get_array();
echo wp_json_encode( $config );

// Get form configuration
$config = $form->get_config();
```

---

### JavaScript Form API

#### Creating a Form

```javascript
const form = new BKGTForm({
    id: 'my-form',
    fields: [
        {
            name: 'email',
            type: 'email',
            label: 'E-postadress',
            required: true,
            placeholder: 'example@domain.com',
            help: 'Vi skickar aldrig spam'
        },
        {
            name: 'message',
            type: 'textarea',
            label: 'Meddelande',
            required: true,
            rows: 4,
            maxLength: 500,
            errorRequired: 'Ange ett meddelande',
            errorInvalid: 'Meddelandet är för långt'
        }
    ],
    layout: 'vertical',
    submitText: 'Skicka',
    showCancel: true,
    onSubmit: function(data, response) {
        console.log('Form submitted:', data);
        if (response) {
            console.log('Server response:', response);
        }
    },
    onCancel: function() {
        console.log('Form cancelled');
    },
    onValidationError: function(errors) {
        console.log('Validation errors:', errors);
    }
});
```

#### Rendering Forms

```javascript
// Render into container
form.render('#form-container');

// Render into element
form.render(document.getElementById('form-container'));
```

#### Working with Form Data

```javascript
// Get all form data
const data = form.getFormData();
// Returns: { email: '...', message: '...' }

// Set form data
form.setFormData({
    email: 'user@example.com',
    message: 'Hello!'
});

// Clear form
form.clear();
```

#### Validation

```javascript
// Validate form
const isValid = form.validate();

// Validate single field
form.validateField( 'email' );

// Manual validation
if ( form.validate() ) {
    console.log('Form is valid');
} else {
    console.log('Form has errors:', form.errors);
}
```

#### Form Submission

```javascript
// Manual submission
form.handleSubmit();

// Programmatic submission
form.form.dispatchEvent( new Event( 'submit' ) );

// Check if currently submitting
if ( form.isSubmitting ) {
    console.log( 'Form is submitting...' );
}

// Check if form is dirty
if ( form.isDirty ) {
    console.log( 'Form has unsaved changes' );
}
```

#### AJAX Submission

```javascript
// Configure AJAX submission
const form = new BKGTForm({
    id: 'my-form',
    fields: [ /* ... */ ],
    ajax: {
        action: 'my_form_handler',
        nonce: bkgtFormConfig.nonce
    },
    onSubmit: function(data, response) {
        console.log('Server response:', response);
        // Handle server response
    }
});

// The form will automatically submit via AJAX
```

#### Cleanup

```javascript
// Destroy form
form.destroy();
```

---

## 🔌 Integration Examples

### Form Inside Modal

```javascript
// Create modal
const modal = new BKGTModal({
    id: 'contact-modal',
    title: 'Kontakta oss',
    size: 'medium'
});

// Create form
const form = new BKGTForm({
    id: 'contact-form',
    fields: [
        { name: 'email', type: 'email', label: 'E-postadress', required: true },
        { name: 'message', type: 'textarea', label: 'Meddelande', required: true }
    ],
    onSubmit: function(data) {
        console.log('Form submitted:', data);
        modal.close();
    },
    onCancel: function() {
        modal.close();
    }
});

// Open modal with form
modal.open();
form.render(modal.modal.querySelector('.bkgt-modal-body'));
```

### AJAX Form Handler

```php
// In your plugin's admin/handlers.php

add_action( 'wp_ajax_contact_form_submit', function() {
    // Verify nonce
    check_ajax_referer( 'my_nonce', 'nonce' );
    
    // Check permissions
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Insufficient permissions' );
    }
    
    // Build form
    $form = bkgt_form_builder( 'contact-form' )
        ->add_email( 'email', 'E-postadress', [ 'required' => true ] )
        ->add_textarea( 'message', 'Meddelande', [ 'required' => true ] );
    
    // Get and sanitize data
    $data = array_filter( $_POST, function( $key ) {
        return in_array( $key, [ 'email', 'message' ], true );
    }, ARRAY_FILTER_USE_KEY );
    
    // Validate
    $validation = $form->validate( $data );
    if ( $validation !== true ) {
        wp_send_json_error( $validation );
    }
    
    // Sanitize
    $clean_data = $form->sanitize( $data );
    
    // Process form
    wp_mail(
        get_option( 'admin_email' ),
        'New Contact Form Submission',
        "Email: {$clean_data['email']}\n\nMessage: {$clean_data['message']}"
    );
    
    // Return success
    wp_send_json_success( [
        'message' => 'Tack för ditt meddelande!',
        'email' => $clean_data['email']
    ] );
} );
```

---

## 🎨 Customization

### Custom Field Validation

```javascript
const form = new BKGTForm({
    id: 'my-form',
    fields: [
        {
            name: 'username',
            type: 'text',
            label: 'Användarnamn',
            required: true,
            validate: function(value) {
                // Custom validation
                if (value.toLowerCase() === 'admin') {
                    return 'Användarnamnet "admin" är redan taget';
                }
                return true; // Valid
            }
        }
    ]
});
```

### Custom CSS Styling

```css
/* Override default styles */
.bkgt-form {
    max-width: 800px;
}

.bkgt-btn-primary {
    background-color: #2c3e50;
}

.bkgt-form-input {
    border-radius: 8px;
}
```

### Form Layouts

```php
// Vertical layout (default)
$form->set_layout( 'vertical' );

// Horizontal layout
$form->set_layout( 'horizontal' );

// Grid layout
$form->set_layout( 'grid' );
```

---

## ✅ Accessibility Features

- **ARIA Labels** - All form inputs have proper ARIA labels
- **Required Indicators** - Visual * and aria-required attributes
- **Error Display** - Errors shown with role="alert"
- **Focus Management** - Focus moves to first error on validation
- **Keyboard Navigation** - Full keyboard accessibility
- **High Contrast Support** - Works with high contrast themes
- **Reduced Motion Support** - Respects prefers-reduced-motion
- **Dark Mode** - Works with dark mode preferences

---

## 📊 Field Types

| Type | HTML5 Type | Validation | Notes |
|------|-----------|-----------|-------|
| `text` | text | Text format | Default if not specified |
| `email` | email | Valid email format | RFC 5322 compliant |
| `password` | password | No validation | Password field, value hidden |
| `tel` | tel | Minimum 6 digits | Swedish phone format |
| `url` | url | Valid URL | Must be valid URL format |
| `number` | number | Numeric, min/max | Can set min/max values |
| `date` | date | YYYY-MM-DD format | Date picker with validation |
| `textarea` | - | Text format | Multi-line text input |
| `select` | - | Value in options | Dropdown list |
| `checkbox` | checkbox | Boolean | Yes/no choice |
| `radio` | radio | Value in options | Single choice from many |
| `hidden` | hidden | No validation | Hidden form field |

---

## 🐛 Troubleshooting

### Form not rendering
- Ensure bkgt-form.js is loaded: `wp_enqueue_script( 'bkgt-form' )`
- Check browser console for JavaScript errors
- Verify BKGTForm class is available: `console.log(window.BKGTForm)`

### Validation not working
- Check that required fields have `required: true`
- Verify custom validators return true (valid) or error message (invalid)
- Check bkgt_log console output for validation details

### AJAX submission failing
- Verify nonce is correct
- Check `wp_ajax_` hook is registered
- Review server logs for errors
- Check Network tab in browser DevTools

### Styling issues
- Ensure bkgt-form.css is enqueued
- Check for CSS conflicts with other stylesheets
- Use browser DevTools to inspect computed styles

---

## 📝 Common Use Cases

### Login Form

```php
$form = bkgt_form_builder( 'login-form' )
    ->add_text( 'username', 'Användarnamn', [ 'required' => true ] )
    ->add_password( 'password', 'Lösenord', [ 'required' => true ] )
    ->add_checkbox( 'remember', 'Kom ihåg mig' );
```

### Contact Form

```php
$form = bkgt_form_builder( 'contact-form' )
    ->add_text( 'name', 'Namn', [ 'required' => true ] )
    ->add_email( 'email', 'E-postadress', [ 'required' => true ] )
    ->add_select( 'subject', 'Ämne', [
        'required' => true,
        'options' => [
            ['value' => 'general', 'label' => 'Allmän fråga'],
            ['value' => 'support', 'label' => 'Teknisk support'],
            ['value' => 'feedback', 'label' => 'Feedback']
        ]
    ])
    ->add_textarea( 'message', 'Meddelande', [ 'required' => true ] );
```

### User Registration

```php
$form = bkgt_form_builder( 'register-form' )
    ->add_text( 'username', 'Användarnamn', [
        'required' => true,
        'minLength' => 3,
        'maxLength' => 20
    ])
    ->add_email( 'email', 'E-postadress', [ 'required' => true ] )
    ->add_password( 'password', 'Lösenord', [
        'required' => true,
        'minLength' => 8
    ])
    ->add_date( 'birth_date', 'Födelsedatum', [ 'required' => true ] )
    ->add_checkbox( 'agree', 'Jag godkänner villkoren', [ 'required' => true ] );
```

---

## 📚 Related Documentation

- [BKGTModal Guide](./BKGTMODAL_MIGRATION_GUIDE.md)
- [BKGT_Validator Documentation](./BKGT_CORE_QUICK_REFERENCE.md)
- [Form Migration Guide](./BKGTFORM_MIGRATION_GUIDE.md)

---

**Last Updated:** November 2, 2025  
**Status:** Production Ready  
**Version:** 1.0.0
