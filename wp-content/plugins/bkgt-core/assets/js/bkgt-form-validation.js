/**
 * BKGT Form Validation JavaScript
 * Real-time form validation and user feedback
 */

(function(window, document) {
    'use strict';

    /**
     * BKGT Form Validator
     * Handles real-time validation and error display
     */
    window.BKGTFormValidator = class {
        constructor(formElement, config = {}) {
            this.form = formElement;
            this.config = Object.assign({
                validateOnInput: true,
                validateOnBlur: true,
                validateOnChange: true,
                showErrorsLive: true,
                successCallback: null,
                errorCallback: null,
            }, config);
            
            this.fields = {};
            this.errors = {};
            this.isDirty = {};
            
            this.init();
        }
        
        /**
         * Initialize form validator
         */
        init() {
            if (!this.form) {
                console.error('BKGTFormValidator: Form element not found');
                return;
            }
            
            // Cache field elements
            this.cacheFields();
            
            // Attach event listeners
            this.attachEventListeners();
            
            // Attach form submit handler
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
            
            window.bkgt_log('info', 'BKGTFormValidator initialized', { 
                fieldCount: Object.keys(this.fields).length 
            });
        }
        
        /**
         * Cache form field elements
         */
        cacheFields() {
            const inputs = this.form.querySelectorAll('input, textarea, select');
            
            inputs.forEach(input => {
                if (!input.name) return;
                
                this.fields[input.name] = {
                    element: input,
                    type: input.type,
                    required: input.hasAttribute('required'),
                    value: input.value,
                };
                
                this.isDirty[input.name] = false;
            });
        }
        
        /**
         * Attach event listeners to fields
         */
        attachEventListeners() {
            Object.entries(this.fields).forEach(([fieldName, field]) => {
                const { element } = field;
                
                // Real-time validation on input
                if (this.config.validateOnInput && element.type !== 'checkbox' && element.type !== 'radio') {
                    element.addEventListener('input', (e) => {
                        this.markDirty(fieldName);
                        if (this.isDirty[fieldName]) {
                            this.validateField(fieldName);
                        }
                    });
                }
                
                // Validation on blur
                if (this.config.validateOnBlur) {
                    element.addEventListener('blur', (e) => {
                        this.markDirty(fieldName);
                        this.validateField(fieldName);
                    });
                }
                
                // Validation on change (for select, checkbox, radio)
                if (this.config.validateOnChange) {
                    element.addEventListener('change', (e) => {
                        this.markDirty(fieldName);
                        this.validateField(fieldName);
                    });
                }
            });
        }
        
        /**
         * Mark field as touched/dirty
         */
        markDirty(fieldName) {
            this.isDirty[fieldName] = true;
        }
        
        /**
         * Validate single field
         */
        validateField(fieldName) {
            if (!this.fields[fieldName]) {
                return true;
            }
            
            const field = this.fields[fieldName];
            const value = field.element.value;
            const errors = [];
            
            // Required validation
            if (field.required && !value) {
                errors.push('Dette feltet er obligatorisk');
            }
            
            // Type-specific validations
            if (value) {
                const typeErrors = this.validateByType(field.type, value);
                errors.push(...typeErrors);
            }
            
            // Update errors
            if (errors.length > 0) {
                this.setFieldError(fieldName, errors[0]);
                return false;
            } else {
                this.clearFieldError(fieldName);
                return true;
            }
        }
        
        /**
         * Validate field by type
         */
        validateByType(type, value) {
            const errors = [];
            
            switch (type) {
                case 'email':
                    if (!this.isValidEmail(value)) {
                        errors.push('Ogiltig e-postadress');
                    }
                    break;
                case 'number':
                    if (isNaN(value)) {
                        errors.push('Måste vara ett nummer');
                    }
                    break;
                case 'date':
                    if (!this.isValidDate(value)) {
                        errors.push('Ogiltigt datumformat');
                    }
                    break;
                case 'tel':
                    if (!this.isValidPhone(value)) {
                        errors.push('Ogiltigt telefonnummer');
                    }
                    break;
                case 'url':
                    if (!this.isValidUrl(value)) {
                        errors.push('Ogiltig URL');
                    }
                    break;
            }
            
            return errors;
        }
        
        /**
         * Email validation
         */
        isValidEmail(email) {
            const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return pattern.test(email);
        }
        
        /**
         * Date validation
         */
        isValidDate(dateString) {
            const date = new Date(dateString);
            return date instanceof Date && !isNaN(date);
        }
        
        /**
         * Phone validation
         */
        isValidPhone(phone) {
            const pattern = /^[\d\s\-\+\(\)]+$/;
            return pattern.test(phone) && phone.replace(/\D/g, '').length >= 7;
        }
        
        /**
         * URL validation
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
         * Set field error
         */
        setFieldError(fieldName, error) {
            const field = this.fields[fieldName];
            if (!field) return;
            
            this.errors[fieldName] = error;
            
            if (!this.config.showErrorsLive) {
                return;
            }
            
            // Add error class to field container
            const container = field.element.closest('.bkgt-form-field');
            if (container) {
                container.classList.add('bkgt-field-with-error');
            }
            
            // Show error message
            this.displayFieldError(fieldName, error);
        }
        
        /**
         * Clear field error
         */
        clearFieldError(fieldName) {
            const field = this.fields[fieldName];
            if (!field) return;
            
            delete this.errors[fieldName];
            
            // Remove error class
            const container = field.element.closest('.bkgt-form-field');
            if (container) {
                container.classList.remove('bkgt-field-with-error');
                
                // Remove existing error message
                const existingError = container.querySelector('.bkgt-field-error');
                if (existingError) {
                    existingError.remove();
                }
            }
        }
        
        /**
         * Display field error message
         */
        displayFieldError(fieldName, error) {
            const field = this.fields[fieldName];
            if (!field) return;
            
            const container = field.element.closest('.bkgt-form-field');
            if (!container) return;
            
            // Remove existing error
            const existing = container.querySelector('.bkgt-field-error');
            if (existing) {
                existing.remove();
            }
            
            // Create error element
            const errorElement = document.createElement('span');
            errorElement.className = 'bkgt-field-error';
            errorElement.setAttribute('role', 'alert');
            errorElement.textContent = error;
            
            // Insert after input or last help text
            const helpText = container.querySelector('.bkgt-help-text');
            if (helpText) {
                helpText.insertAdjacentElement('afterend', errorElement);
            } else {
                field.element.insertAdjacentElement('afterend', errorElement);
            }
        }
        
        /**
         * Validate entire form
         */
        validateForm() {
            let isValid = true;
            
            Object.keys(this.fields).forEach(fieldName => {
                this.markDirty(fieldName);
                if (!this.validateField(fieldName)) {
                    isValid = false;
                }
            });
            
            return isValid;
        }
        
        /**
         * Handle form submit
         */
        handleSubmit(e) {
            const isValid = this.validateForm();
            
            if (!isValid) {
                e.preventDefault();
                
                // Scroll to first error
                this.scrollToFirstError();
                
                // Call error callback
                if (this.config.errorCallback) {
                    this.config.errorCallback(this.errors);
                }
                
                window.bkgt_log('warning', 'Form validation failed', {
                    errorCount: Object.keys(this.errors).length,
                    errors: this.errors,
                });
                
                return false;
            }
            
            // Call success callback
            if (this.config.successCallback) {
                this.config.successCallback();
            }
            
            window.bkgt_log('info', 'Form validation passed', {
                fieldCount: Object.keys(this.fields).length,
            });
        }
        
        /**
         * Scroll to first error
         */
        scrollToFirstError() {
            const fieldName = Object.keys(this.errors)[0];
            if (!fieldName || !this.fields[fieldName]) return;
            
            const field = this.fields[fieldName];
            field.element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            field.element.focus();
        }
        
        /**
         * Get all errors
         */
        getErrors() {
            return { ...this.errors };
        }
        
        /**
         * Check if form is valid
         */
        isValid() {
            return Object.keys(this.errors).length === 0;
        }
        
        /**
         * Reset form
         */
        reset() {
            this.form.reset();
            this.errors = {};
            this.isDirty = {};
            
            // Clear visual errors
            const errorElements = this.form.querySelectorAll('.bkgt-field-with-error');
            errorElements.forEach(el => {
                el.classList.remove('bkgt-field-with-error');
                const error = el.querySelector('.bkgt-field-error');
                if (error) error.remove();
            });
        }
    };

    /**
     * Auto-initialize form validators
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Find all forms with data-validate attribute
        const forms = document.querySelectorAll('form[data-validate]');
        
        forms.forEach(form => {
            new window.BKGTFormValidator(form);
            window.bkgt_log('info', 'Form auto-initialized', { 
                formId: form.id || 'unnamed' 
            });
        });
    });

})(window, document);
