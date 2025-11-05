/**
 * BKGT Unified Modal System
 * 
 * Provides a consistent, reusable modal component for all BKGT plugins
 * Handles open/close, content loading, form submission, and accessibility
 * 
 * @package BKGT_Core
 * @since 1.0.0
 */

/**
 * BKGTModal - Unified modal component
 * 
 * Usage:
 * const modal = new BKGTModal({
 *     id: 'my-modal',
 *     title: 'Modal Title',
 *     size: 'medium', // small, medium, large
 *     onOpen: function() {},
 *     onClose: function() {},
 *     onSubmit: function(data) {}
 * });
 * 
 * modal.open(content);
 * modal.close();
 * modal.setContent(html);
 * modal.showLoading();
 * modal.hideLoading();
 * modal.showError(message);
 * modal.clearError();
 */
class BKGTModal {
    constructor(options = {}) {
        this.options = {
            id: options.id || 'bkgt-modal-' + Date.now(),
            title: options.title || '',
            size: options.size || 'medium', // small, medium, large
            closeButton: options.closeButton !== false,
            overlay: options.overlay !== false,
            onOpen: options.onOpen || null,
            onClose: options.onClose || null,
            onSubmit: options.onSubmit || null,
            onError: options.onError || null,
            ...options
        };
        
        this.modal = null;
        this.isOpen = false;
        this.isLoading = false;
        
        this.init();
    }
    
    /**
     * Initialize modal HTML and event listeners
     */
    init() {
        // Create modal HTML structure
        const modalHtml = `
            <div id="${this.options.id}" class="bkgt-modal bkgt-modal-${this.options.size}" style="display: none;">
                ${this.options.overlay ? '<div class="bkgt-modal-overlay"></div>' : ''}
                <div class="bkgt-modal-wrapper">
                    <div class="bkgt-modal-content">
                        <div class="bkgt-modal-header">
                            <h2 class="bkgt-modal-title">${this.options.title}</h2>
                            ${this.options.closeButton ? '<button type="button" class="bkgt-modal-close" aria-label="Stäng">&times;</button>' : ''}
                        </div>
                        <div class="bkgt-modal-body"></div>
                        <div class="bkgt-modal-error" style="display: none;"></div>
                        <div class="bkgt-modal-loading" style="display: none;">
                            <div class="bkgt-spinner"></div>
                            <p><?php _e('Laddar...', 'bkgt-core'); ?></p>
                        </div>
                        <div class="bkgt-modal-footer"></div>
                    </div>
                </div>
            </div>
        `;
        
        // Insert modal into DOM if not already present
        if (!document.getElementById(this.options.id)) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = modalHtml;
            document.body.appendChild(tempDiv.firstElementChild);
        }
        
        this.modal = document.getElementById(this.options.id);
        this.setupEventListeners();
    }
    
    /**
     * Setup event listeners for modal interactions
     */
    setupEventListeners() {
        if (!this.modal) return;
        
        // Close button
        const closeBtn = this.modal.querySelector('.bkgt-modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.close());
        }
        
        // Overlay click closes modal
        const overlay = this.modal.querySelector('.bkgt-modal-overlay');
        if (overlay) {
            overlay.addEventListener('click', () => this.close());
        }
        
        // Prevent closing when clicking inside modal content
        const content = this.modal.querySelector('.bkgt-modal-content');
        if (content) {
            content.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
        
        // Form submission
        const form = this.modal.querySelector('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleFormSubmit(form);
            });
        }
    }
    
    /**
     * Open the modal
     * @param {string|HTMLElement} content - Content to display in modal
     * @param {Object} options - Additional options (title, etc)
     */
    open(content = null, options = {}) {
        // Update title if provided
        if (options.title) {
            const titleEl = this.modal.querySelector('.bkgt-modal-title');
            if (titleEl) titleEl.textContent = options.title;
        }
        
        // Set content if provided
        if (content) {
            this.setContent(content);
        }
        
        // Show modal
        this.modal.style.display = 'flex';
        this.isOpen = true;
        
        // Add class for animations
        setTimeout(() => this.modal.classList.add('bkgt-modal-visible'), 10);
        
        // Call onOpen callback
        if (this.options.onOpen) {
            this.options.onOpen.call(this);
        }
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        // Focus first focusable element
        const focusable = this.modal.querySelector('button, input, select, textarea, a[href]');
        if (focusable) focusable.focus();
        
        bkgt_log('info', 'Modal opened', { modalId: this.options.id });
    }
    
    /**
     * Close the modal
     */
    close() {
        if (!this.isOpen) return;
        
        // Remove visible class
        this.modal.classList.remove('bkgt-modal-visible');
        
        // Hide after animation
        setTimeout(() => {
            this.modal.style.display = 'none';
            this.isOpen = false;
            
            // Call onClose callback
            if (this.options.onClose) {
                this.options.onClose.call(this);
            }
            
            // Clear content
            this.setContent('');
            
            // Restore body scroll
            document.body.style.overflow = '';
            
            bkgt_log('info', 'Modal closed', { modalId: this.options.id });
        }, 300);
    }
    
    /**
     * Set modal content
     * @param {string|HTMLElement} content - HTML string or DOM element
     */
    setContent(content) {
        const body = this.modal.querySelector('.bkgt-modal-body');
        if (!body) return;
        
        if (typeof content === 'string') {
            body.innerHTML = content;
        } else if (content instanceof HTMLElement) {
            body.innerHTML = '';
            body.appendChild(content.cloneNode(true));
        } else {
            body.innerHTML = '';
        }
        
        // Re-setup form listeners after content change
        this.setupEventListeners();
    }
    
    /**
     * Show loading indicator
     */
    showLoading() {
        const loading = this.modal.querySelector('.bkgt-modal-loading');
        if (loading) {
            loading.style.display = 'flex';
            this.isLoading = true;
            bkgt_log('info', 'Modal loading started', { modalId: this.options.id });
        }
    }
    
    /**
     * Hide loading indicator
     */
    hideLoading() {
        const loading = this.modal.querySelector('.bkgt-modal-loading');
        if (loading) {
            loading.style.display = 'none';
            this.isLoading = false;
            bkgt_log('info', 'Modal loading completed', { modalId: this.options.id });
        }
    }
    
    /**
     * Show error message
     * @param {string} message - Error message to display
     */
    showError(message) {
        const errorEl = this.modal.querySelector('.bkgt-modal-error');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.style.display = 'block';
            
            if (this.options.onError) {
                this.options.onError.call(this, message);
            }
            
            bkgt_log('warning', 'Modal error displayed', { 
                modalId: this.options.id, 
                error: message 
            });
        }
    }
    
    /**
     * Clear error message
     */
    clearError() {
        const errorEl = this.modal.querySelector('.bkgt-modal-error');
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.style.display = 'none';
        }
    }
    
    /**
     * Set footer content (e.g., action buttons)
     * @param {string|HTMLElement} content - Footer content
     */
    setFooter(content) {
        const footer = this.modal.querySelector('.bkgt-modal-footer');
        if (!footer) return;
        
        if (typeof content === 'string') {
            footer.innerHTML = content;
        } else if (content instanceof HTMLElement) {
            footer.innerHTML = '';
            footer.appendChild(content.cloneNode(true));
        } else {
            footer.innerHTML = '';
        }
        
        this.setupEventListeners();
    }
    
    /**
     * Handle form submission
     * @param {HTMLFormElement} form - Form element
     */
    handleFormSubmit(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        bkgt_log('info', 'Modal form submitted', { 
            modalId: this.options.id,
            data: data 
        });
        
        if (this.options.onSubmit) {
            this.options.onSubmit.call(this, data);
        }
    }
    
    /**
     * Load content from URL (AJAX)
     * @param {string} url - URL to load
     * @param {Object} params - Query parameters
     */
    loadFromUrl(url, params = {}) {
        this.showLoading();
        this.clearError();
        
        const queryParams = new URLSearchParams(params).toString();
        const fullUrl = queryParams ? `${url}?${queryParams}` : url;
        
        fetch(fullUrl)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(html => {
                this.hideLoading();
                this.setContent(html);
                bkgt_log('info', 'Modal content loaded from URL', { 
                    modalId: this.options.id,
                    url: url
                });
            })
            .catch(error => {
                this.hideLoading();
                this.showError('Kunde inte ladda innehål. Försök igen senare.');
                bkgt_log('error', 'Modal content load failed', { 
                    modalId: this.options.id,
                    url: url,
                    error: error.message
                });
            });
    }
    
    /**
     * Load content from AJAX endpoint
     * @param {string} action - WordPress AJAX action
     * @param {Object} data - Data to send
     */
    loadFromAjax(action, data = {}) {
        this.showLoading();
        this.clearError();
        
        data.action = action;
        data.nonce = window.bkgtNonce || '';
        
        fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data).toString()
        })
            .then(response => response.json())
            .then(result => {
                this.hideLoading();
                
                if (result.success) {
                    this.setContent(result.data.html || result.data);
                    bkgt_log('info', 'Modal content loaded via AJAX', { 
                        modalId: this.options.id,
                        action: action
                    });
                } else {
                    throw new Error(result.data || 'Unknown error');
                }
            })
            .catch(error => {
                this.hideLoading();
                this.showError('Kunde inte ladda innehål: ' + error.message);
                bkgt_log('error', 'Modal AJAX load failed', { 
                    modalId: this.options.id,
                    action: action,
                    error: error.message
                });
            });
    }
    
    /**
     * Destroy modal (remove from DOM)
     */
    destroy() {
        if (this.isOpen) this.close();
        
        setTimeout(() => {
            if (this.modal && this.modal.parentNode) {
                this.modal.parentNode.removeChild(this.modal);
            }
            this.modal = null;
            bkgt_log('info', 'Modal destroyed', { modalId: this.options.id });
        }, 400);
    }
}

// Make BKGTModal globally available
window.BKGTModal = BKGTModal;

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BKGTModal;
}
