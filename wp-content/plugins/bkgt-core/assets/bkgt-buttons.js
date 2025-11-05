/**
 * BKGT Button Component
 * 
 * Provides utilities for working with BKGT buttons
 * Handles loading states, click events, and interactions
 */

class BKGTButton {
    constructor(element) {
        this.element = typeof element === 'string' ? document.querySelector(element) : element;
        if (!this.element) {
            console.warn('BKGTButton: Element not found');
            return;
        }

        this.isLoading = false;
        this.originalHTML = this.element.innerHTML;
        this.originalText = this.element.textContent;

        this.init();
    }

    /**
     * Initialize button event listeners
     */
    init() {
        this.element.addEventListener('click', (e) => this.handleClick(e));
    }

    /**
     * Handle button click event
     */
    handleClick(event) {
        if (this.isLoading || this.element.disabled) {
            event.preventDefault();
            return;
        }

        // Trigger custom event
        const clickEvent = new CustomEvent('bkgtButtonClick', {
            detail: { button: this },
            bubbles: true,
            cancelable: true
        });

        if (!this.element.dispatchEvent(clickEvent)) {
            event.preventDefault();
        }
    }

    /**
     * Set loading state
     */
    setLoading(isLoading = true, loadingText = null) {
        this.isLoading = isLoading;
        this.element.disabled = isLoading;

        if (isLoading) {
            this.element.classList.add('bkgt-btn-loading');
            if (loadingText) {
                this.element.innerHTML = loadingText;
            }
        } else {
            this.element.classList.remove('bkgt-btn-loading');
            this.element.innerHTML = this.originalHTML;
        }

        return this;
    }

    /**
     * Clear loading state
     */
    clearLoading() {
        return this.setLoading(false);
    }

    /**
     * Toggle loading state
     */
    toggleLoading(loadingText = null) {
        return this.setLoading(!this.isLoading, loadingText);
    }

    /**
     * Disable the button
     */
    disable() {
        this.element.disabled = true;
        this.element.setAttribute('aria-disabled', 'true');
        return this;
    }

    /**
     * Enable the button
     */
    enable() {
        this.element.disabled = false;
        this.element.setAttribute('aria-disabled', 'false');
        return this;
    }

    /**
     * Check if button is disabled
     */
    isDisabled() {
        return this.element.disabled;
    }

    /**
     * Set button text
     */
    setText(text) {
        this.originalText = text;
        if (!this.isLoading) {
            this.element.textContent = text;
        }
        return this;
    }

    /**
     * Get button text
     */
    getText() {
        return this.originalText;
    }

    /**
     * Add variant class
     */
    addVariant(variant) {
        // Remove existing variant
        this.element.classList.forEach(cls => {
            if (cls.startsWith('bkgt-btn-') && cls !== 'bkgt-btn') {
                this.element.classList.remove(cls);
            }
        });

        // Add new variant
        this.element.classList.add(`bkgt-btn-${variant}`);
        return this;
    }

    /**
     * Add size class
     */
    setSize(size) {
        // Remove existing size
        ['sm', 'md', 'lg'].forEach(s => {
            this.element.classList.remove(`bkgt-btn-${s}`);
        });

        // Add new size (default is md, so only add if not default)
        if (size && size !== 'md') {
            this.element.classList.add(`bkgt-btn-${size}`);
        }

        return this;
    }

    /**
     * Make button block (full width)
     */
    setBlock(isBlock = true) {
        if (isBlock) {
            this.element.classList.add('bkgt-btn-block');
        } else {
            this.element.classList.remove('bkgt-btn-block');
        }
        return this;
    }

    /**
     * Add click handler
     */
    onClick(callback) {
        this.element.addEventListener('bkgtButtonClick', () => callback(this));
        return this;
    }

    /**
     * Perform async action with loading state
     */
    async perform(asyncFunction) {
        try {
            this.setLoading(true);
            const result = await asyncFunction();
            this.clearLoading();
            return result;
        } catch (error) {
            this.clearLoading();
            throw error;
        }
    }

    /**
     * Show success state
     */
    showSuccess(duration = 2000) {
        const originalVariant = this.element.className.match(/bkgt-btn-\w+/)?.[0];

        this.element.classList.add('bkgt-btn-success');
        const originalHTML = this.element.innerHTML;
        this.element.innerHTML = '<span>✓ ' + this.element.textContent + '</span>';

        setTimeout(() => {
            this.element.innerHTML = originalHTML;
            if (originalVariant) {
                this.element.classList.remove('bkgt-btn-success');
                this.element.classList.add(originalVariant);
            }
        }, duration);

        return this;
    }

    /**
     * Show error state
     */
    showError(errorMessage = null, duration = 2000) {
        const originalVariant = this.element.className.match(/bkgt-btn-\w+/)?.[0];

        this.element.classList.add('bkgt-btn-danger');
        const originalHTML = this.element.innerHTML;
        
        if (errorMessage) {
            this.element.innerHTML = '<span>✕ ' + errorMessage + '</span>';
        } else {
            this.element.innerHTML = '<span>✕ ' + this.element.textContent + '</span>';
        }

        setTimeout(() => {
            this.element.innerHTML = originalHTML;
            if (originalVariant) {
                this.element.classList.remove('bkgt-btn-danger');
                this.element.classList.add(originalVariant);
            }
        }, duration);

        return this;
    }

    /**
     * Get element
     */
    getElement() {
        return this.element;
    }

    /**
     * Destroy instance
     */
    destroy() {
        this.element = null;
    }

    /**
     * Static method: Create instance from selector
     */
    static create(selector) {
        const element = document.querySelector(selector);
        return element ? new BKGTButton(element) : null;
    }

    /**
     * Static method: Create instances from multiple selectors
     */
    static createAll(selector) {
        const elements = document.querySelectorAll(selector);
        const buttons = [];
        elements.forEach(element => {
            buttons.push(new BKGTButton(element));
        });
        return buttons;
    }

    /**
     * Static method: Add click handler to all buttons
     */
    static onAll(selector, callback) {
        const buttons = BKGTButton.createAll(selector);
        buttons.forEach(button => {
            button.onClick(callback);
        });
        return buttons;
    }

    /**
     * Static method: Disable all buttons
     */
    static disableAll(selector) {
        const buttons = BKGTButton.createAll(selector);
        buttons.forEach(button => button.disable());
        return buttons;
    }

    /**
     * Static method: Enable all buttons
     */
    static enableAll(selector) {
        const buttons = BKGTButton.createAll(selector);
        buttons.forEach(button => button.enable());
        return buttons;
    }

    /**
     * Static method: Set loading on all buttons
     */
    static setAllLoading(selector, isLoading = true, loadingText = null) {
        const buttons = BKGTButton.createAll(selector);
        buttons.forEach(button => {
            if (isLoading) {
                button.setLoading(true, loadingText);
            } else {
                button.clearLoading();
            }
        });
        return buttons;
    }
}

/**
 * Button Group Component
 * 
 * Manages group of buttons for selection/toggle behavior
 */
class BKGTButtonGroup {
    constructor(containerElement, options = {}) {
        this.container = typeof containerElement === 'string' ? 
            document.querySelector(containerElement) : containerElement;

        if (!this.container) {
            console.warn('BKGTButtonGroup: Container not found');
            return;
        }

        this.options = {
            type: 'checkbox', // 'checkbox' or 'radio'
            onSelect: null,
            onDeselect: null,
            ...options
        };

        this.selectedButtons = new Set();
        this.buttons = [];

        this.init();
    }

    /**
     * Initialize button group
     */
    init() {
        const buttonElements = this.container.querySelectorAll('.bkgt-btn');
        
        buttonElements.forEach(element => {
            const button = new BKGTButton(element);
            this.buttons.push(button);

            button.element.addEventListener('bkgtButtonClick', (e) => {
                e.preventDefault();
                this.selectButton(button);
            });
        });
    }

    /**
     * Select a button
     */
    selectButton(button) {
        const wasSelected = this.selectedButtons.has(button);

        if (this.options.type === 'radio') {
            // Deselect all others
            this.selectedButtons.forEach(btn => {
                btn.element.classList.remove('active');
                if (this.options.onDeselect) {
                    this.options.onDeselect(btn);
                }
            });
            this.selectedButtons.clear();
        }

        if (wasSelected) {
            this.selectedButtons.delete(button);
            button.element.classList.remove('active');
            if (this.options.onDeselect) {
                this.options.onDeselect(button);
            }
        } else {
            this.selectedButtons.add(button);
            button.element.classList.add('active');
            if (this.options.onSelect) {
                this.options.onSelect(button);
            }
        }

        return this;
    }

    /**
     * Get selected buttons
     */
    getSelected() {
        return Array.from(this.selectedButtons);
    }

    /**
     * Get selected values
     */
    getSelectedValues() {
        return this.getSelected().map(btn => btn.element.value || btn.element.textContent);
    }

    /**
     * Clear selection
     */
    clearSelection() {
        this.selectedButtons.forEach(button => {
            button.element.classList.remove('active');
            if (this.options.onDeselect) {
                this.options.onDeselect(button);
            }
        });
        this.selectedButtons.clear();
        return this;
    }

    /**
     * Disable group
     */
    disableAll() {
        this.buttons.forEach(button => button.disable());
        return this;
    }

    /**
     * Enable group
     */
    enableAll() {
        this.buttons.forEach(button => button.enable());
        return this;
    }

    /**
     * Destroy instance
     */
    destroy() {
        this.buttons.forEach(button => button.destroy());
        this.buttons = [];
        this.selectedButtons.clear();
    }
}

/**
 * Initialize BKGT buttons on document ready
 */
document.addEventListener('DOMContentLoaded', function() {
    // Auto-init buttons with data attributes
    document.querySelectorAll('[data-bkgt-button]').forEach(element => {
        new BKGTButton(element);
    });

    // Auto-init button groups
    document.querySelectorAll('[data-bkgt-button-group]').forEach(element => {
        const type = element.getAttribute('data-bkgt-button-group-type') || 'checkbox';
        new BKGTButtonGroup(element, { type });
    });
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { BKGTButton, BKGTButtonGroup };
}
