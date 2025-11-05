<?php
/**
 * Admin Form Integration Examples
 * 
 * Demonstrates how to use the BKGT Form Builder in WordPress admin pages.
 * Shows practical patterns for modernizing admin forms with the new form system.
 *
 * @package BKGT_Core
 * @subpackage Examples
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ============================================================================
 * EXAMPLE 1: Basic Settings Form
 * ============================================================================
 * 
 * Common use case: A simple settings form with text and textarea fields.
 */

?>
<!-- EXAMPLE 1: Basic Settings Form -->
<?php
if (class_exists('BKGT_Form_Builder')) {
    // Create form
    $form = new BKGT_Form_Builder('basic_settings_form', 'POST', admin_url('admin-ajax.php'));
    
    // Add form title
    $form->addTitle(__('Site Settings', 'bkgt'));
    
    // Add hidden action field
    $form->addField('hidden', 'action', [
        'value' => 'save_settings'
    ]);
    
    // Add nonce for security
    $form->addField('nonce', '_settings_nonce', [
        'value' => wp_create_nonce('save_settings_nonce')
    ]);
    
    // Add form fields
    $form->addField('text', 'Site Title', [
        'name' => 'site_title',
        'value' => get_option('site_title', ''),
        'placeholder' => __('Enter site title', 'bkgt'),
        'required' => true,
        'description' => __('The name of your website', 'bkgt')
    ]);
    
    $form->addField('text', 'Site Email', [
        'name' => 'site_email',
        'type' => 'email',
        'value' => get_option('site_email', ''),
        'placeholder' => 'example@domain.com',
        'required' => true,
        'description' => __('Your site admin email address', 'bkgt')
    ]);
    
    $form->addField('textarea', 'Site Description', [
        'name' => 'site_description',
        'value' => get_option('site_description', ''),
        'placeholder' => __('Enter site description', 'bkgt'),
        'rows' => 4,
        'description' => __('A brief description of your site', 'bkgt')
    ]);
    
    // Add buttons
    $form->addButton('primary', __('Save Settings', 'bkgt'), 'submit', ['name' => 'submit']);
    $form->addButton('secondary', __('Cancel', 'bkgt'), 'reset');
    
    // Output form
    echo $form->build();
}
?>

<?php

/**
 * ============================================================================
 * EXAMPLE 2: Advanced Settings Form with Multiple Sections
 * ============================================================================
 * 
 * Common use case: Settings form with multiple sections separated visually.
 */

?>
<!-- EXAMPLE 2: Advanced Settings Form with Sections -->
<?php
if (class_exists('BKGT_Form_Builder')) {
    $form = new BKGT_Form_Builder('advanced_settings', 'POST', admin_url('admin-ajax.php'));
    
    $form->addTitle(__('Plugin Settings', 'bkgt'));
    
    // Security fields
    $form->addField('hidden', 'action', ['value' => 'save_plugin_settings']);
    $form->addField('nonce', '_plugin_nonce', ['value' => wp_create_nonce('plugin_settings')]);
    
    // ========== SECTION 1: General Settings ==========
    $form->addField('section_header', 'General Settings', [
        'description' => __('Configure general plugin behavior', 'bkgt')
    ]);
    
    $form->addField('text', 'Plugin Name', [
        'name' => 'plugin_name',
        'value' => get_option('plugin_name', 'My Plugin'),
        'required' => true
    ]);
    
    $form->addField('checkbox', 'Enable Features', [
        'name' => 'enable_features',
        'value' => '1',
        'checked' => (bool)get_option('enable_features'),
        'label' => __('Enable all plugin features', 'bkgt')
    ]);
    
    // ========== SECTION 2: API Settings ==========
    $form->addField('section_header', 'API Configuration', [
        'description' => __('Configure API credentials', 'bkgt')
    ]);
    
    $form->addField('text', 'API Key', [
        'name' => 'api_key',
        'value' => get_option('api_key', ''),
        'placeholder' => __('Enter your API key', 'bkgt'),
        'required' => true
    ]);
    
    $form->addField('text', 'API Secret', [
        'name' => 'api_secret',
        'type' => 'password',
        'value' => get_option('api_secret', ''),
        'placeholder' => __('Enter your API secret', 'bkgt')
    ]);
    
    $form->addField('select', 'API Endpoint', [
        'name' => 'api_endpoint',
        'value' => get_option('api_endpoint', 'production'),
        'options' => [
            'development' => __('Development', 'bkgt'),
            'staging' => __('Staging', 'bkgt'),
            'production' => __('Production', 'bkgt')
        ]
    ]);
    
    // ========== SECTION 3: Advanced Settings ==========
    $form->addField('section_header', 'Advanced Settings', [
        'description' => __('Advanced configuration options', 'bkgt')
    ]);
    
    $form->addField('checkbox', 'Debug Mode', [
        'name' => 'debug_mode',
        'value' => '1',
        'checked' => (bool)get_option('debug_mode'),
        'label' => __('Enable debug logging', 'bkgt')
    ]);
    
    $form->addField('number', 'Cache TTL (seconds)', [
        'name' => 'cache_ttl',
        'value' => get_option('cache_ttl', 3600),
        'min' => 60,
        'max' => 86400
    ]);
    
    // Add buttons
    $form->addButton('primary', __('Save All Settings', 'bkgt'), 'submit');
    $form->addButton('secondary', __('Restore Defaults', 'bkgt'), 'reset');
    
    echo $form->build();
}
?>

<?php

/**
 * ============================================================================
 * EXAMPLE 3: User Profile Form
 * ============================================================================
 * 
 * Common use case: Edit user profile information in admin.
 */

?>
<!-- EXAMPLE 3: User Profile Form -->
<?php
if (class_exists('BKGT_Form_Builder')) {
    $user = wp_get_current_user();
    
    $form = new BKGT_Form_Builder('user_profile_form', 'POST', admin_url('admin-ajax.php'));
    
    $form->addTitle(__('Edit Profile', 'bkgt'));
    
    $form->addField('hidden', 'action', ['value' => 'update_user_profile']);
    $form->addField('hidden', 'user_id', ['value' => $user->ID]);
    $form->addField('nonce', '_profile_nonce', ['value' => wp_create_nonce('profile_update')]);
    
    // ========== User Information ==========
    $form->addField('section_header', 'Personal Information', []);
    
    $form->addField('text', 'First Name', [
        'name' => 'first_name',
        'value' => $user->first_name,
        'required' => false
    ]);
    
    $form->addField('text', 'Last Name', [
        'name' => 'last_name',
        'value' => $user->last_name,
        'required' => false
    ]);
    
    $form->addField('text', 'Email', [
        'name' => 'user_email',
        'type' => 'email',
        'value' => $user->user_email,
        'required' => true
    ]);
    
    // ========== Contact Information ==========
    $form->addField('section_header', 'Contact Information', []);
    
    $form->addField('text', 'Website', [
        'name' => 'user_url',
        'type' => 'url',
        'value' => $user->user_url,
        'placeholder' => 'https://example.com'
    ]);
    
    $form->addField('textarea', 'Biographical Info', [
        'name' => 'description',
        'value' => $user->description,
        'rows' => 4,
        'description' => __('Share information about yourself', 'bkgt')
    ]);
    
    // ========== Password ==========
    if (current_user_can('edit_user', $user->ID)) {
        $form->addField('section_header', 'Password', [
            'description' => __('Leave blank to keep your current password', 'bkgt')
        ]);
        
        $form->addField('text', 'New Password', [
            'name' => 'new_password',
            'type' => 'password',
            'placeholder' => __('Enter new password', 'bkgt')
        ]);
        
        $form->addField('text', 'Confirm Password', [
            'name' => 'confirm_password',
            'type' => 'password',
            'placeholder' => __('Confirm new password', 'bkgt')
        ]);
    }
    
    // Add buttons
    $form->addButton('primary', __('Update Profile', 'bkgt'), 'submit');
    $form->addButton('secondary', __('Cancel', 'bkgt'), 'reset');
    
    echo $form->build();
}
?>

<?php

/**
 * ============================================================================
 * EXAMPLE 4: Search and Filter Form
 * ============================================================================
 * 
 * Common use case: Search and filter form for data tables.
 */

?>
<!-- EXAMPLE 4: Search and Filter Form -->
<?php
if (class_exists('BKGT_Form_Builder')) {
    $form = new BKGT_Form_Builder('search_filter_form', 'GET', admin_url('admin.php'), [
        'class' => 'inline-form'
    ]);
    
    $form->addField('hidden', 'page', ['value' => 'manage-items']);
    
    $form->addField('text', 'Search', [
        'name' => 's',
        'value' => isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '',
        'placeholder' => __('Search items...', 'bkgt'),
        'inline' => true
    ]);
    
    $form->addField('select', 'Category', [
        'name' => 'category',
        'value' => isset($_GET['category']) ? $_GET['category'] : '',
        'options' => [
            '' => __('All Categories', 'bkgt'),
            'cat1' => __('Category 1', 'bkgt'),
            'cat2' => __('Category 2', 'bkgt'),
            'cat3' => __('Category 3', 'bkgt')
        ],
        'inline' => true
    ]);
    
    $form->addField('select', 'Status', [
        'name' => 'status',
        'value' => isset($_GET['status']) ? $_GET['status'] : '',
        'options' => [
            '' => __('All Statuses', 'bkgt'),
            'active' => __('Active', 'bkgt'),
            'inactive' => __('Inactive', 'bkgt'),
            'archived' => __('Archived', 'bkgt')
        ],
        'inline' => true
    ]);
    
    $form->addButton('primary', __('Search', 'bkgt'), 'submit', ['inline' => true]);
    $form->addButton('secondary', __('Clear', 'bkgt'), 'reset', ['inline' => true]);
    
    echo $form->build();
}
?>

<?php

/**
 * ============================================================================
 * EXAMPLE 5: Bulk Action Form
 * ============================================================================
 * 
 * Common use case: Form for bulk actions on multiple items.
 */

?>
<!-- EXAMPLE 5: Bulk Action Form -->
<?php
if (class_exists('BKGT_Form_Builder')) {
    $form = new BKGT_Form_Builder('bulk_action_form', 'POST', admin_url('admin-ajax.php'), [
        'class' => 'bulk-actions-form'
    ]);
    
    $form->addTitle(__('Bulk Actions', 'bkgt'));
    
    $form->addField('hidden', 'action', ['value' => 'bulk_actions']);
    $form->addField('nonce', '_bulk_nonce', ['value' => wp_create_nonce('bulk_actions')]);
    
    // Checkboxes for items
    $form->addField('fieldset_header', 'Select Items', []);
    
    // Get items (example)
    $items = [
        ['id' => 1, 'name' => 'Item 1'],
        ['id' => 2, 'name' => 'Item 2'],
        ['id' => 3, 'name' => 'Item 3']
    ];
    
    foreach ($items as $item) {
        $form->addField('checkbox', $item['name'], [
            'name' => 'items[]',
            'value' => $item['id'],
            'label' => esc_html($item['name'])
        ]);
    }
    
    // Bulk action selector
    $form->addField('section_header', 'Actions', []);
    
    $form->addField('select', 'Action', [
        'name' => 'bulk_action',
        'value' => '',
        'options' => [
            '' => __('Select an action', 'bkgt'),
            'delete' => __('Delete', 'bkgt'),
            'archive' => __('Archive', 'bkgt'),
            'publish' => __('Publish', 'bkgt'),
            'export' => __('Export', 'bkgt')
        ],
        'required' => true
    ]);
    
    // Buttons
    $form->addButton('primary', __('Apply', 'bkgt'), 'submit');
    $form->addButton('secondary', __('Cancel', 'bkgt'), 'reset');
    
    echo $form->build();
}
?>

<?php

/**
 * ============================================================================
 * EXAMPLE 6: Multi-Step Form
 * ============================================================================
 * 
 * Common use case: Multi-step wizard for complex data entry.
 */

?>
<!-- EXAMPLE 6: Multi-Step Form -->
<?php
if (class_exists('BKGT_Form_Builder')) {
    $step = isset($_POST['step']) ? intval($_POST['step']) : 1;
    
    $form = new BKGT_Form_Builder('multi_step_form', 'POST', admin_url('admin-ajax.php'));
    
    $form->addTitle(__('Setup Wizard - Step %d of 3', 'bkgt'), $step);
    
    $form->addField('hidden', 'action', ['value' => 'wizard_step']);
    $form->addField('hidden', 'step', ['value' => $step]);
    $form->addField('nonce', '_wizard_nonce', ['value' => wp_create_nonce('wizard_step')]);
    
    if ($step === 1) {
        // Step 1: Basic Information
        $form->addField('section_header', 'Basic Information', [
            'description' => __('Enter your basic setup information', 'bkgt')
        ]);
        
        $form->addField('text', 'Company Name', [
            'name' => 'company_name',
            'required' => true
        ]);
        
        $form->addField('text', 'Industry', [
            'name' => 'industry',
            'required' => true
        ]);
        
    } elseif ($step === 2) {
        // Step 2: Contact Information
        $form->addField('section_header', 'Contact Information', [
            'description' => __('Provide your contact details', 'bkgt')
        ]);
        
        $form->addField('text', 'Contact Email', [
            'name' => 'contact_email',
            'type' => 'email',
            'required' => true
        ]);
        
        $form->addField('text', 'Phone', [
            'name' => 'phone',
            'type' => 'tel',
            'required' => true
        ]);
        
    } elseif ($step === 3) {
        // Step 3: Confirmation
        $form->addField('section_header', 'Review & Confirm', [
            'description' => __('Please review your information', 'bkgt')
        ]);
        
        $form->addField('checkbox', 'I agree to terms', [
            'name' => 'agree_terms',
            'value' => '1',
            'label' => __('I agree to the terms and conditions', 'bkgt'),
            'required' => true
        ]);
    }
    
    // Navigation buttons
    $buttons = [];
    
    if ($step > 1) {
        $form->addButton('secondary', __('← Previous', 'bkgt'), 'button', [
            'name' => 'previous',
            'value' => $step - 1,
            'onclick' => 'document.querySelector("input[name=step]").value = ' . ($step - 1)
        ]);
    }
    
    if ($step < 3) {
        $form->addButton('primary', __('Next →', 'bkgt'), 'button', [
            'name' => 'next',
            'value' => $step + 1,
            'onclick' => 'document.querySelector("input[name=step]").value = ' . ($step + 1)
        ]);
    } else {
        $form->addButton('primary', __('Complete Setup', 'bkgt'), 'submit');
    }
    
    echo $form->build();
}
?>

<?php

/**
 * ============================================================================
 * EXAMPLE 7: Data Import Form
 * ============================================================================
 * 
 * Common use case: Form for importing data from CSV/JSON files.
 */

?>
<!-- EXAMPLE 7: Data Import Form -->
<?php
if (class_exists('BKGT_Form_Builder')) {
    $form = new BKGT_Form_Builder('import_data_form', 'POST', admin_url('admin-ajax.php'), [
        'enctype' => 'multipart/form-data'
    ]);
    
    $form->addTitle(__('Import Data', 'bkgt'));
    
    $form->addField('hidden', 'action', ['value' => 'import_data']);
    $form->addField('nonce', '_import_nonce', ['value' => wp_create_nonce('import_data')]);
    
    $form->addField('section_header', 'Import Settings', []);
    
    $form->addField('select', 'Data Type', [
        'name' => 'data_type',
        'value' => 'csv',
        'options' => [
            'csv' => __('CSV File', 'bkgt'),
            'json' => __('JSON File', 'bkgt'),
            'xml' => __('XML File', 'bkgt')
        ],
        'required' => true
    ]);
    
    $form->addField('file', 'Select File', [
        'name' => 'import_file',
        'accept' => '.csv,.json,.xml',
        'required' => true,
        'description' => __('Maximum file size: 10MB', 'bkgt')
    ]);
    
    $form->addField('checkbox', 'Overwrite Existing', [
        'name' => 'overwrite',
        'value' => '1',
        'label' => __('Overwrite existing data', 'bkgt')
    ]);
    
    $form->addField('checkbox', 'Send Notifications', [
        'name' => 'notify',
        'value' => '1',
        'checked' => true,
        'label' => __('Send email notification when complete', 'bkgt')
    ]);
    
    $form->addButton('primary', __('Start Import', 'bkgt'), 'submit');
    $form->addButton('secondary', __('Cancel', 'bkgt'), 'reset');
    
    echo $form->build();
}
?>

<?php

/**
 * ============================================================================
 * EXAMPLE 8: Product/Item Creation Form
 * ============================================================================
 * 
 * Common use case: Create/edit form for complex products or items.
 */

?>
<!-- EXAMPLE 8: Product Creation Form -->
<?php
if (class_exists('BKGT_Form_Builder')) {
    $form = new BKGT_Form_Builder('product_form', 'POST', admin_url('admin-ajax.php'));
    
    $form->addTitle(__('Create Product', 'bkgt'));
    
    $form->addField('hidden', 'action', ['value' => 'save_product']);
    $form->addField('nonce', '_product_nonce', ['value' => wp_create_nonce('product_form')]);
    
    // Basic Information
    $form->addField('section_header', 'Basic Information', []);
    
    $form->addField('text', 'Product Name', [
        'name' => 'product_name',
        'placeholder' => __('Enter product name', 'bkgt'),
        'required' => true
    ]);
    
    $form->addField('textarea', 'Description', [
        'name' => 'product_description',
        'placeholder' => __('Enter product description', 'bkgt'),
        'rows' => 5
    ]);
    
    $form->addField('textarea', 'Short Description', [
        'name' => 'product_short_description',
        'placeholder' => __('Enter short description', 'bkgt'),
        'rows' => 3
    ]);
    
    // Pricing
    $form->addField('section_header', 'Pricing', []);
    
    $form->addField('number', 'Price', [
        'name' => 'product_price',
        'placeholder' => '0.00',
        'step' => '0.01',
        'required' => true
    ]);
    
    $form->addField('number', 'Sale Price', [
        'name' => 'product_sale_price',
        'placeholder' => '0.00',
        'step' => '0.01'
    ]);
    
    // Categories
    $form->addField('section_header', 'Categories', []);
    
    $form->addField('select', 'Category', [
        'name' => 'product_category',
        'multiple' => true,
        'options' => [
            'electronics' => __('Electronics', 'bkgt'),
            'clothing' => __('Clothing', 'bkgt'),
            'home' => __('Home & Garden', 'bkgt'),
            'sports' => __('Sports', 'bkgt')
        ]
    ]);
    
    // Inventory
    $form->addField('section_header', 'Inventory', []);
    
    $form->addField('number', 'Stock Quantity', [
        'name' => 'stock_qty',
        'placeholder' => '0',
        'min' => '0'
    ]);
    
    $form->addField('checkbox', 'Track Inventory', [
        'name' => 'track_inventory',
        'value' => '1',
        'checked' => true,
        'label' => __('Track inventory levels', 'bkgt')
    ]);
    
    // Status
    $form->addField('section_header', 'Status', []);
    
    $form->addField('select', 'Status', [
        'name' => 'product_status',
        'value' => 'draft',
        'options' => [
            'draft' => __('Draft', 'bkgt'),
            'published' => __('Published', 'bkgt'),
            'archived' => __('Archived', 'bkgt')
        ]
    ]);
    
    $form->addButton('primary', __('Save Product', 'bkgt'), 'submit');
    $form->addButton('secondary', __('Save as Draft', 'bkgt'), 'submit', ['name' => 'draft']);
    $form->addButton('secondary', __('Cancel', 'bkgt'), 'reset');
    
    echo $form->build();
}
?>

<?php

/**
 * ============================================================================
 * EXAMPLE 9: Validation Example
 * ============================================================================
 * 
 * Common use case: Form with various validation rules.
 */

?>
<!-- EXAMPLE 9: Form with Validation -->
<?php
if (class_exists('BKGT_Form_Builder')) {
    $form = new BKGT_Form_Builder('validation_example_form', 'POST', admin_url('admin-ajax.php'));
    
    $form->addTitle(__('Validation Examples', 'bkgt'));
    
    $form->addField('hidden', 'action', ['value' => 'validate_form']);
    $form->addField('nonce', '_validation_nonce', ['value' => wp_create_nonce('validation')]);
    
    // Text with pattern
    $form->addField('text', 'Username', [
        'name' => 'username',
        'pattern' => '^[a-zA-Z0-9_]{3,20}$',
        'placeholder' => __('3-20 characters, letters/numbers/_', 'bkgt'),
        'required' => true,
        'data-error' => __('Invalid username format', 'bkgt')
    ]);
    
    // Email
    $form->addField('text', 'Email', [
        'name' => 'email',
        'type' => 'email',
        'required' => true,
        'placeholder' => 'example@domain.com'
    ]);
    
    // Number with min/max
    $form->addField('number', 'Age', [
        'name' => 'age',
        'min' => '18',
        'max' => '120',
        'required' => true
    ]);
    
    // URL validation
    $form->addField('text', 'Website', [
        'name' => 'website',
        'type' => 'url',
        'placeholder' => 'https://example.com'
    ]);
    
    // Select (required)
    $form->addField('select', 'Country', [
        'name' => 'country',
        'options' => [
            '' => __('Select a country', 'bkgt'),
            'us' => __('United States', 'bkgt'),
            'uk' => __('United Kingdom', 'bkgt'),
            'ca' => __('Canada', 'bkgt')
        ],
        'required' => true
    ]);
    
    // Checkbox (must be checked)
    $form->addField('checkbox', 'Accept Terms', [
        'name' => 'accept_terms',
        'value' => '1',
        'label' => __('I accept the terms and conditions', 'bkgt'),
        'required' => true,
        'data-error' => __('You must accept the terms', 'bkgt')
    ]);
    
    $form->addButton('primary', __('Submit', 'bkgt'), 'submit');
    $form->addButton('secondary', __('Reset', 'bkgt'), 'reset');
    
    echo $form->build();
}
?>

<?php

/**
 * ============================================================================
 * STYLING GUIDE
 * ============================================================================
 */
?>
<style>
    /* Form container */
    .bkgt-form {
        background: white;
        padding: 1.5rem;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    /* Form title */
    .bkgt-form h2 {
        margin-top: 0;
        margin-bottom: 1.5rem;
        font-size: 1.5rem;
        border-bottom: 2px solid #007cba;
        padding-bottom: 0.75rem;
    }

    /* Form sections */
    .bkgt-form .form-section {
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid #e0e0e0;
    }

    .bkgt-form .form-section:last-child {
        border-bottom: none;
        margin-bottom: 1rem;
        padding-bottom: 0;
    }

    /* Section headers */
    .bkgt-form .section-header {
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f0f0f0;
    }

    .bkgt-form .section-header h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.2rem;
        color: #333;
    }

    .bkgt-form .section-header p {
        margin: 0;
        color: #666;
        font-size: 0.9rem;
    }

    /* Form fields */
    .bkgt-form-field {
        margin-bottom: 1.5rem;
    }

    .bkgt-form-field label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #333;
    }

    .bkgt-form-field input[type="text"],
    .bkgt-form-field input[type="email"],
    .bkgt-form-field input[type="password"],
    .bkgt-form-field input[type="number"],
    .bkgt-form-field input[type="tel"],
    .bkgt-form-field input[type="url"],
    .bkgt-form-field input[type="file"],
    .bkgt-form-field textarea,
    .bkgt-form-field select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 1rem;
    }

    .bkgt-form-field textarea {
        resize: vertical;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .bkgt-form-field input:focus,
    .bkgt-form-field textarea:focus,
    .bkgt-form-field select:focus {
        border-color: #007cba;
        outline: none;
        box-shadow: 0 0 0 3px rgba(0, 124, 186, 0.1);
    }

    /* Checkboxes and radios */
    .bkgt-form-field input[type="checkbox"],
    .bkgt-form-field input[type="radio"] {
        width: auto;
        margin-right: 0.5rem;
    }

    .bkgt-form-field .checkbox-label,
    .bkgt-form-field .radio-label {
        display: flex;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    /* Descriptions */
    .bkgt-form-field .description {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.9rem;
        color: #666;
    }

    /* Error messages */
    .bkgt-form-field .error {
        color: #dc3545;
        font-size: 0.9rem;
        margin-top: 0.25rem;
    }

    .bkgt-form-field.has-error input,
    .bkgt-form-field.has-error textarea,
    .bkgt-form-field.has-error select {
        border-color: #dc3545;
    }

    /* Button group */
    .bkgt-form-buttons {
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #e0e0e0;
    }

    /* Inline forms */
    .inline-form {
        display: flex;
        gap: 0.75rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .inline-form .bkgt-form-field {
        margin-bottom: 0;
        flex: 0 1 auto;
        min-width: 150px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .bkgt-form {
            padding: 1rem;
        }

        .bkgt-form-buttons {
            flex-direction: column;
            justify-content: flex-start;
        }

        .bkgt-form-buttons .bkgt-button {
            width: 100%;
        }

        .inline-form {
            flex-direction: column;
        }

        .inline-form .bkgt-form-field {
            width: 100%;
        }
    }
</style>

<?php
/**
 * ============================================================================
 * USAGE NOTES
 * ============================================================================
 * 
 * 1. Always check if BKGT_Form_Builder class exists before using it
 * 2. Security:
 *    - Always include a nonce field
 *    - Verify nonce on form submission
 *    - Sanitize and validate input
 *    - Use current_user_can() for capability checks
 * 
 * 3. Form Methods:
 *    - addTitle() - Add form title
 *    - addField() - Add form field
 *    - addButton() - Add button
 *    - build() - Output HTML
 * 
 * 4. Available Field Types:
 *    - text, email, password, number, tel, url, date, time
 *    - textarea
 *    - select (with options array)
 *    - checkbox, radio
 *    - file
 *    - hidden
 *    - nonce
 *    - section_header
 *    - fieldset_header
 * 
 * 5. Field Properties:
 *    - name: Field name
 *    - value: Default value
 *    - required: Is required?
 *    - placeholder: Placeholder text
 *    - description: Help text
 *    - rows/cols: For textarea
 *    - min/max: For number inputs
 *    - pattern: Regex pattern validation
 * 
 * 6. Button Types:
 *    - submit: Form submission
 *    - reset: Clear form
 *    - button: Custom button
 *    - link: As link
 * 
 * 7. Button Variants:
 *    - primary: Main action
 *    - secondary: Alternative action
 *    - danger: Destructive action
 *    - info: Informational
 *    - success: Success/positive
 *    - warning: Warning/caution
 * 
 * 8. Validation:
 *    - Use HTML5 attributes (required, pattern, min, max, etc.)
 *    - JavaScript validation for complex rules
 *    - Server-side validation in AJAX handler
 * 
 * 9. Accessibility:
 *    - Always use proper labels
 *    - Include descriptions for complex fields
 *    - Use aria-* attributes where needed
 *    - Ensure keyboard navigation works
 * 
 * 10. Best Practices:
 *     - Group related fields in sections
 *     - Use clear, descriptive labels
 *     - Provide helpful descriptions
 *     - Highlight required fields
 *     - Show validation errors clearly
 *     - Confirm destructive actions
 * 
 * ============================================================================
 */
