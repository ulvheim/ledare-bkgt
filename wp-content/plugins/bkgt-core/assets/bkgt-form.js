/**
 * BKGT Unified Form System
 * 
 * Provides a consistent, reusable form component for all BKGT plugins.
 * Handles validation, error display, submission, and accessibility.
 * 
 * @package BKGT_Core
 * @since 1.0.0
 */

/**
 * BKGTForm - Unified form component
 * 
 * Usage:
 * const form = new BKGTForm({
 *     id: 'my-form',
 *     fields: [
 *         { name: 'email', type: 'email', label: 'E-postadress', required: true },
 *         { name: 'message', type: 'textarea', label: 'Meddelande', required: true }
 *     ],
 *     onSubmit: function(data) { console.log(data); },
 *     onValidationError: function(errors) { console.log(errors); }
 * });
 * 
 * form.render(container);
 * form.validate();
 * form.submit();
 */
class BKGTForm {
    constructor(options = {}) {
        this.options = {
            id: options.id || 'bkgt-form-' + Date.now(),
            fields: options.fields || [],
            layout: options.layout || 'vertical', // vertical, horizontal, grid
            submitText: options.submitText || 'Skicka',
            cancelText: options.cancelText || 'Avbryt',
            onSubmit: options.onSubmit || null,
            onCancel: options.onCancel || null,
            onValidationError: options.onValidationError || null,
            showCancel: options.showCancel !== false,
            ajax: options.ajax || null, // { url, action, nonce }
            ...options
        };
        
        this.form = null;
        this.fields = {};
        this.errors = {};
        this.isDirty = false;
        this.isSubmitting = false;
        
        this.init();
    }
    
    /**
     * Initialize form
     */
    init() {
        this.buildFields();
    }
    
    /**
     * Build field objects from configuration
     */
    buildFields() {
        this.options.fields.forEach(fieldConfig => {
            this.fields[fieldConfig.name] = {
                ...fieldConfig,
                value: fieldConfig.value || '',
                touched: false,
                error: null
            };
        });
    }
    
    /**
     * Render form into a container
     * @param {string|HTMLElement} container - Container element or selector
     */
    render(container) {
        // Get container element
        let containerEl;
        if (typeof container === 'string') {
            containerEl = document.querySelector(container);
        } else {
            containerEl = container;
        }
        
        if (!containerEl) {
            bkgt_log('error', 'BKGTForm: Container not found', { selector: container });
            return;
        }
        
        // Build form HTML
        const formHtml = this.buildFormHtml();
        containerEl.innerHTML = formHtml;
        
        // Store reference to form element
        this.form = document.getElementById(this.options.id);
        
        // Attach event listeners
        this.attachEventListeners();
        
        bkgt_log('info', 'Form rendered', { formId: this.options.id });
    }
    
    /**
     * Build form HTML
     */
    buildFormHtml() {
        let html = `
            <form id="${this.options.id}" class="bkgt-form bkgt-form-${this.options.layout}" role="form">
                <div class="bkgt-form-fields">
        `;
        
        // Build each field
        this.options.fields.forEach(field => {
            html += this.buildFieldHtml(field);
        });
        
        html += `
                </div>
                <div class="bkgt-form-footer">
                    <button type="submit" class="bkgt-btn bkgt-btn-primary" aria-label="${this.options.submitText}">
                        ${this.options.submitText}
                    </button>
        `;
        
        if (this.options.showCancel) {
            html += `
                    <button type="button" class="bkgt-btn bkgt-btn-secondary bkgt-form-cancel" aria-label="${this.options.cancelText}">
                        ${this.options.cancelText}
                    </button>
            `;
        }
        
        html += `
                </div>
            </form>
        `;
        
        return html;
    }
    
    /**
     * Build individual field HTML
     * @param {Object} field - Field configuration
     */
    buildFieldHtml(field) {
        const fieldId = `${this.options.id}-${field.name}`;
        const isRequired = field.required ? 'required' : '';
        const requiredIndicator = field.required ? '<span class="bkgt-required" aria-label="Obligatoriskt">*</span>' : '';
        
        let fieldHtml = `
            <div class="bkgt-form-group" data-field-name="${field.name}">
        `;
        
        // Label
        if (field.label) {
            fieldHtml += `
                <label for="${fieldId}" class="bkgt-form-label">
                    ${field.label}
                    ${requiredIndicator}
                </label>
            `;
        }
        
        // Field input based on type
        switch (field.type) {
            case 'textarea':
                fieldHtml += `
                    <textarea 
                        id="${fieldId}"
                        name="${field.name}"
                        class="bkgt-form-input bkgt-form-textarea"
                        ${isRequired}
                        ${field.placeholder ? `placeholder="${field.placeholder}"` : ''}
                        ${field.rows ? `rows="${field.rows}"` : 'rows="4"'}
                        aria-required="${field.required ? 'true' : 'false'}"
                    ></textarea>
                `;
                break;
                
            case 'select':
                fieldHtml += `
                    <select 
                        id="${fieldId}"
                        name="${field.name}"
                        class="bkgt-form-input bkgt-form-select"
                        ${isRequired}
                        aria-required="${field.required ? 'true' : 'false'}"
                    >
                        <option value="">${field.placeholder || 'Välj...'}</option>
                `;
                
                if (field.options) {
                    field.options.forEach(option => {
                        fieldHtml += `<option value="${option.value}">${option.label}</option>`;
                    });
                }
                
                fieldHtml += `</select>`;
                break;
                
            case 'checkbox':
                fieldHtml += `
                    <div class="bkgt-form-checkbox">
                        <input 
                            type="checkbox"
                            id="${fieldId}"
                            name="${field.name}"
                            class="bkgt-form-input"
                            ${field.checked ? 'checked' : ''}
                            aria-label="${field.label || field.name}"
                        />
                        <label for="${fieldId}" class="bkgt-form-checkbox-label">${field.label}</label>
                    </div>
                `;
                break;
                
            case 'radio':
                fieldHtml += `<div class="bkgt-form-radio-group">`;
                
                if (field.options) {
                    field.options.forEach(option => {
                        const optionId = `${fieldId}-${option.value}`;
                        fieldHtml += `
                            <div class="bkgt-form-radio">
                                <input 
                                    type="radio"
                                    id="${optionId}"
                                    name="${field.name}"
                                    value="${option.value}"
                                    class="bkgt-form-input"
                                    ${field.value === option.value ? 'checked' : ''}
                                    aria-label="${option.label}"
                                />
                                <label for="${optionId}" class="bkgt-form-radio-label">${option.label}</label>
                            </div>
                        `;
                    });
                }
                
                fieldHtml += `</div>`;
                break;
                
            case 'date':
            case 'email':
            case 'password':
            case 'number':
            case 'tel':
            case 'url':
                fieldHtml += `
                    <input 
                        type="${field.type}"
                        id="${fieldId}"
                        name="${field.name}"
                        class="bkgt-form-input"
                        ${isRequired}
                        ${field.placeholder ? `placeholder="${field.placeholder}"` : ''}
                        ${field.min ? `min="${field.min}"` : ''}
                        ${field.max ? `max="${field.max}"` : ''}
                        aria-required="${field.required ? 'true' : 'false'}"
                    />
                `;
                break;
                
            case 'hidden':
                fieldHtml += `
                    <input 
                        type="hidden"
                        id="${fieldId}"
                        name="${field.name}"
                        value="${field.value || ''}"
                    />
                `;
                break;
                
            case 'text':
            default:
                fieldHtml += `
                    <input 
                        type="text"
                        id="${fieldId}"
                        name="${field.name}"
                        class="bkgt-form-input"
                        ${isRequired}
                        ${field.placeholder ? `placeholder="${field.placeholder}"` : ''}
                        ${field.pattern ? `pattern="${field.pattern}"` : ''}
                        aria-required="${field.required ? 'true' : 'false'}"
                    />
                `;
                break;
        }
        
        // Help text
        if (field.help) {
            fieldHtml += `<small class="bkgt-form-help">${field.help}</small>`;
        }
        
        // Error container
        fieldHtml += `<div class="bkgt-form-error" role="alert" aria-live="polite"></div>`;
        
        fieldHtml += `</div>`;
        
        return fieldHtml;
    }
    
    /**
     * Attach event listeners to form
     */
    attachEventListeners() {
        if (!this.form) return;
        
        // Submit button
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSubmit();
        });
        
        // Cancel button
        const cancelBtn = this.form.querySelector('.bkgt-form-cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleCancel();
            });
        }
        
        // Field change listeners (for dirty tracking and real-time validation)
        Object.keys(this.fields).forEach(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.addEventListener('input', () => {
                    this.isDirty = true;
                    this.validateField(fieldName);
                });
                
                field.addEventListener('blur', () => {
                    this.fields[fieldName].touched = true;
                    this.validateField(fieldName);
                });
            }
        });
        
        bkgt_log('debug', 'Form event listeners attached', { formId: this.options.id });
    }
    
    /**
     * Validate a single field
     * @param {string} fieldName - Field name to validate
     */
    validateField(fieldName) {
        const field = this.fields[fieldName];
        if (!field) return;
        
        const formField = this.form.querySelector(`[name="${fieldName}"]`);
        if (!formField) return;
        
        const value = formField.value.trim();
        let error = null;
        
        // Required validation
        if (field.required && !value) {
            error = field.errorRequired || 'Detta fält är obligatoriskt';
        }
        
        // Type-specific validation
        if (!error && value) {
            switch (field.type) {
                case 'email':
                    if (!this.isValidEmail(value)) {
                        error = field.errorInvalid || 'Ogiltig e-postadress';
                    }
                    break;
                    
                case 'tel':
                case 'phone':
                    if (!this.isValidPhone(value)) {
                        error = field.errorInvalid || 'Ogiltigt telefonnummer';
                    }
                    break;
                    
                case 'url':
                    if (!this.isValidUrl(value)) {
                        error = field.errorInvalid || 'Ogiltig URL';
                    }
                    break;
                    
                case 'number':
                    if (isNaN(value)) {
                        error = field.errorInvalid || 'Måste vara ett nummer';
                    }
                    break;
                    
                case 'date':
                    if (!this.isValidDate(value)) {
                        error = field.errorInvalid || 'Ogiltigt datumformat';
                    }
                    break;
            }
        }
        
        // Custom validation
        if (!error && field.validate && typeof field.validate === 'function') {
            error = field.validate(value);
        }
        
        // Min/max length
        if (!error && field.minLength && value.length < field.minLength) {
            error = `Måste innehålla minst ${field.minLength} tecken`;
        }
        
        if (!error && field.maxLength && value.length > field.maxLength) {
            error = `Får innehålla högst ${field.maxLength} tecken`;
        }
        
        // Update field error state
        this.fields[fieldName].error = error;
        this.displayFieldError(fieldName, error);
    }
    
    /**
     * Display field error
     * @param {string} fieldName - Field name
     * @param {string} error - Error message
     */
    displayFieldError(fieldName, error) {
        const fieldGroup = this.form.querySelector(`[data-field-name="${fieldName}"]`);
        if (!fieldGroup) return;
        
        const errorEl = fieldGroup.querySelector('.bkgt-form-error');
        const inputEl = fieldGroup.querySelector('input, textarea, select');
        
        if (error) {
            errorEl.textContent = error;
            errorEl.style.display = 'block';
            fieldGroup.classList.add('bkgt-form-error-state');
            if (inputEl) inputEl.setAttribute('aria-invalid', 'true');
        } else {
            errorEl.textContent = '';
            errorEl.style.display = 'none';
            fieldGroup.classList.remove('bkgt-form-error-state');
            if (inputEl) inputEl.setAttribute('aria-invalid', 'false');
        }
    }
    
    /**
     * Validate all fields
     * @returns {boolean} True if all fields are valid
     */
    validate() {
        this.errors = {};
        
        Object.keys(this.fields).forEach(fieldName => {
            this.validateField(fieldName);
            if (this.fields[fieldName].error) {
                this.errors[fieldName] = this.fields[fieldName].error;
            }
        });
        
        if (Object.keys(this.errors).length > 0) {
            bkgt_log('warning', 'Form validation failed', { formId: this.options.id, errors: this.errors });
            
            if (this.options.onValidationError) {
                this.options.onValidationError.call(this, this.errors);
            }
            
            return false;
        }
        
        bkgt_log('info', 'Form validation passed', { formId: this.options.id });
        return true;
    }
    
    /**
     * Get form data
     * @returns {Object} Form data object
     */
    getFormData() {
        const data = {};
        
        Object.keys(this.fields).forEach(fieldName => {
            const formField = this.form.querySelector(`[name="${fieldName}"]`);
            if (formField) {
                if (formField.type === 'checkbox') {
                    data[fieldName] = formField.checked;
                } else if (formField.type === 'radio') {
                    const checked = this.form.querySelector(`[name="${fieldName}"]:checked`);
                    data[fieldName] = checked ? checked.value : null;
                } else {
                    data[fieldName] = formField.value;
                }
            }
        });
        
        return data;
    }
    
    /**
     * Set form data
     * @param {Object} data - Data object to populate form with
     */
    setFormData(data) {
        Object.keys(data).forEach(fieldName => {
            const formField = this.form.querySelector(`[name="${fieldName}"]`);
            if (formField) {
                if (formField.type === 'checkbox') {
                    formField.checked = data[fieldName];
                } else if (formField.type === 'radio') {
                    const radio = this.form.querySelector(`[name="${fieldName}"][value="${data[fieldName]}"]`);
                    if (radio) radio.checked = true;
                } else {
                    formField.value = data[fieldName];
                }
            }
        });
    }
    
    /**
     * Clear form data
     */
    clear() {
        this.form.reset();
        this.isDirty = false;
        this.errors = {};
        
        // Clear error displays
        this.form.querySelectorAll('.bkgt-form-error').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
        
        this.form.querySelectorAll('.bkgt-form-error-state').forEach(el => {
            el.classList.remove('bkgt-form-error-state');
        });
    }
    
    /**
     * Handle form submission
     */
    handleSubmit() {
        if (!this.validate()) {
            return;
        }
        
        const formData = this.getFormData();
        
        if (this.options.ajax) {
            this.submitViaAjax(formData);
        } else if (this.options.onSubmit) {
            this.options.onSubmit.call(this, formData);
        }
        
        bkgt_log('info', 'Form submitted', { formId: this.options.id, data: formData });
    }
    
    /**
     * Submit form via AJAX
     * @param {Object} formData - Form data to submit
     */
    submitViaAjax(formData) {
        if (this.isSubmitting) return;
        
        this.isSubmitting = true;
        this.setSubmitButtonLoading(true);
        
        const ajaxData = {
            action: this.options.ajax.action,
            nonce: this.options.ajax.nonce,
            ...formData
        };
        
        fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(ajaxData).toString()
        })
            .then(response => response.json())
            .then(result => {
                this.isSubmitting = false;
                this.setSubmitButtonLoading(false);
                
                if (result.success) {
                    bkgt_log('info', 'AJAX submission successful', { formId: this.options.id });
                    
                    if (this.options.onSubmit) {
                        this.options.onSubmit.call(this, formData, result.data);
                    }
                } else {
                    bkgt_log('error', 'AJAX submission failed', { formId: this.options.id, error: result.data });
                    
                    // Display server-side errors
                    if (result.data && typeof result.data === 'object') {
                        Object.keys(result.data).forEach(fieldName => {
                            this.fields[fieldName].error = result.data[fieldName];
                            this.displayFieldError(fieldName, result.data[fieldName]);
                        });
                    }
                }
            })
            .catch(error => {
                this.isSubmitting = false;
                this.setSubmitButtonLoading(false);
                bkgt_log('error', 'AJAX submission error', { formId: this.options.id, error: error.message });
            });
    }
    
    /**
     * Handle form cancellation
     */
    handleCancel() {
        if (this.options.onCancel) {
            this.options.onCancel.call(this);
        }
        
        bkgt_log('info', 'Form cancelled', { formId: this.options.id });
    }
    
    /**
     * Set submit button loading state
     */
    setSubmitButtonLoading(isLoading) {
        const submitBtn = this.form.querySelector('button[type="submit"]');
        if (!submitBtn) return;
        
        if (isLoading) {
            submitBtn.disabled = true;
            submitBtn.classList.add('bkgt-loading');
            submitBtn.innerHTML = '<span class="bkgt-spinner"></span> Skickar...';
        } else {
            submitBtn.disabled = false;
            submitBtn.classList.remove('bkgt-loading');
            submitBtn.innerHTML = this.options.submitText;
        }
    }
    
    /**
     * Validation helper: Check if email is valid
     */
    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    /**
     * Validation helper: Check if phone is valid (Swedish format)
     */
    isValidPhone(phone) {
        const cleaned = phone.replace(/\D/g, '');
        return cleaned.length >= 6;
    }
    
    /**
     * Validation helper: Check if URL is valid
     */
    isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    }
    
    /**
     * Validation helper: Check if date is valid
     */
    isValidDate(dateString) {
        const date = new Date(dateString);
        return date instanceof Date && !isNaN(date);
    }
    
    /**
     * Destroy form
     */
    destroy() {
        if (this.form && this.form.parentNode) {
            this.form.parentNode.removeChild(this.form);
        }
        this.form = null;
        this.fields = {};
        
        bkgt_log('info', 'Form destroyed', { formId: this.options.id });
    }
}

// Make BKGTForm globally available
window.BKGTForm = BKGTForm;

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BKGTForm;
}
