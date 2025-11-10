/**
 * BKGT Core Admin JavaScript
 *
 * Provides common JavaScript functionality for BKGT admin interfaces
 *
 * @package BKGT_Core
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * BKGT Core Admin object
     */
    window.BKGT_Core_Admin = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initConfirmations();
        },

        /**
         * Bind common events
         */
        bindEvents: function() {
            // Handle form submissions with loading states
            $(document).on('submit', '.bkgt-admin-form', this.handleFormSubmit);

            // Handle AJAX actions
            $(document).on('click', '.bkgt-ajax-action', this.handleAjaxAction);

            // Handle dismissible notices
            $(document).on('click', '.bkgt-notice .notice-dismiss', this.dismissNotice);
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            if (typeof $.fn.tooltip !== 'undefined') {
                $('.bkgt-tooltip').tooltip({
                    position: {
                        my: 'center bottom',
                        at: 'center top-10'
                    }
                });
            }
        },

        /**
         * Initialize confirmation dialogs
         */
        initConfirmations: function() {
            $(document).on('click', '.bkgt-confirm-action', function(e) {
                var message = $(this).data('confirm-message') || 'Are you sure you want to perform this action?';
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        },

        /**
         * Handle form submissions with loading states
         */
        handleFormSubmit: function(e) {
            var $form = $(this);
            var $submitButton = $form.find('input[type="submit"], button[type="submit"]');

            // Add loading state
            $submitButton.prop('disabled', true);
            $form.addClass('bkgt-loading');

            // Remove loading state after 10 seconds as fallback
            setTimeout(function() {
                $submitButton.prop('disabled', false);
                $form.removeClass('bkgt-loading');
            }, 10000);
        },

        /**
         * Handle AJAX actions
         */
        handleAjaxAction: function(e) {
            e.preventDefault();

            var $button = $(this);
            var action = $button.data('action');
            var data = $button.data('ajax-data') || {};

            if ($button.hasClass('disabled') || $button.prop('disabled')) {
                return;
            }

            // Add loading state
            $button.addClass('disabled').prop('disabled', true);
            $button.data('original-text', $button.html());
            $button.html('<span class="dashicons dashicons-update spin"></span> Processing...');

            // Prepare AJAX data
            var ajaxData = {
                action: action,
                nonce: bkgt_core_admin ? bkgt_core_admin.nonce : '',
                data: data
            };

            // Make AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    BKGT_Core_Admin.handleAjaxSuccess(response, $button);
                },
                error: function(xhr, status, error) {
                    BKGT_Core_Admin.handleAjaxError(xhr, status, error, $button);
                }
            });
        },

        /**
         * Handle AJAX success
         */
        handleAjaxSuccess: function(response, $button) {
            // Restore button state
            $button.removeClass('disabled').prop('disabled', false);
            if ($button.data('original-text')) {
                $button.html($button.data('original-text'));
            }

            if (response.success) {
                // Show success message
                if (response.data && response.data.message) {
                    BKGT_Core_Admin.showNotice(response.data.message, 'success');
                }

                // Trigger custom event
                $(document).trigger('bkgt_ajax_success', [response, $button]);
            } else {
                // Show error message
                var message = (response.data && response.data.message) ? response.data.message : 'An error occurred';
                BKGT_Core_Admin.showNotice(message, 'error');
            }
        },

        /**
         * Handle AJAX error
         */
        handleAjaxError: function(xhr, status, error, $button) {
            // Restore button state
            $button.removeClass('disabled').prop('disabled', false);
            if ($button.data('original-text')) {
                $button.html($button.data('original-text'));
            }

            // Show error message
            var message = 'AJAX Error: ' + error;
            BKGT_Core_Admin.showNotice(message, 'error');
        },

        /**
         * Show notice message
         */
        showNotice: function(message, type) {
            type = type || 'info';

            var $notice = $('<div class="bkgt-notice ' + type + '"><p>' + message + '</p></div>');
            $notice.hide().prependTo('.bkgt-admin-wrapper').fadeIn();

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Dismiss notice
         */
        dismissNotice: function() {
            $(this).closest('.bkgt-notice').fadeOut(function() {
                $(this).remove();
            });
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        BKGT_Core_Admin.init();
    });

})(jQuery);