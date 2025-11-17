/**
 * BKGT SWE3 Scraper Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        BKGT_SWE3_Admin.init();
    });

    var BKGT_SWE3_Admin = {

        init: function() {
            this.bindEvents();
            this.initTooltips();
        },

        bindEvents: function() {
            // Manual scrape button
            $('#bkgt-swe3-manual-scrape').on('click', this.handleManualScrape);

            // Settings form
            $('#bkgt-swe3-settings-form').on('submit', this.handleSettingsUpdate);

            // Enable/disable toggle
            $('#bkgt_swe3_scrape_enabled').on('change', this.handleToggleScraping);

            // Time selector changes
            $('#bkgt_swe3_scrape_hour, #bkgt_swe3_scrape_minute').on('change', this.handleScheduleChange);
        },

        initTooltips: function() {
            // Add tooltips to help text
            $('.bkgt-swe3-tooltip').each(function() {
                var $element = $(this);
                var tooltip = $element.data('tooltip');

                if (tooltip) {
                    $element.attr('title', tooltip);
                }
            });
        },

        handleManualScrape: function(e) {
            e.preventDefault();

            if (!confirm(bkgt_ajax.strings.confirm_scrape)) {
                return;
            }

            var $button = $(this);
            var $status = $('#bkgt-swe3-scrape-status');

            // Disable button and show loading
            $button.prop('disabled', true);
            $status.removeClass('success error').addClass('loading').html(
                '<span class="bkgt-swe3-spinner"></span>' + bkgt_ajax.strings.scraping
            );

            // Make AJAX request
            $.ajax({
                url: bkgt_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_swe3_manual_scrape',
                    nonce: bkgt_ajax.scraper_nonce
                },
                success: function(response) {
                    BKGT_SWE3_Admin.handleScrapeResponse(response, $button, $status);
                },
                error: function(xhr, status, error) {
                    BKGT_SWE3_Admin.handleScrapeError(xhr, status, error, $button, $status);
                }
            });
        },

        handleScrapeResponse: function(response, $button, $status) {
            $button.prop('disabled', false);

            if (response.success) {
                $status.removeClass('loading error').addClass('success').text(bkgt_swe3_ajax.strings.success + ': ' + response.message);
                BKGT_SWE3_Admin.refreshActivityLog();
                BKGT_SWE3_Admin.showSuccessMessage(response.message);
            } else {
                $status.removeClass('loading success').addClass('error').text(bkgt_swe3_ajax.strings.error + ': ' + response.message);
                BKGT_SWE3_Admin.showErrorMessage(response.message);
            }
        },

        handleScrapeError: function(xhr, status, error, $button, $status) {
            $button.prop('disabled', false);

            // Capture comprehensive error details
            var errorDetails = {
                timestamp: new Date().toISOString(),
                ajaxStatus: status,
                errorThrown: error,
                xhrStatus: xhr ? xhr.status : 'unknown',
                xhrStatusText: xhr ? xhr.statusText : 'unknown',
                responseText: xhr ? xhr.responseText : 'no response',
                responseJSON: null,
                headers: xhr ? xhr.getAllResponseHeaders() : 'no headers',
                readyState: xhr ? xhr.readyState : 'unknown'
            };

            // Try to parse response as JSON
            if (xhr && xhr.responseText) {
                try {
                    errorDetails.responseJSON = JSON.parse(xhr.responseText);
                } catch (e) {
                    errorDetails.responseJSON = 'Failed to parse JSON: ' + e.message;
                }
            }

            // Store error details for debugging
            BKGT_SWE3_Admin.lastErrorDetails = errorDetails;

            // Create user-friendly error message
            var errorMessage = 'Unknown error occurred';
            if (errorDetails.responseJSON && errorDetails.responseJSON.data && errorDetails.responseJSON.data.message) {
                // WordPress structured error response
                errorMessage = errorDetails.responseJSON.data.message;
            } else if (error) {
                errorMessage = error;
            } else if (xhr && xhr.status) {
                errorMessage = 'HTTP ' + xhr.status + ' ' + xhr.statusText;
            } else if (status === 'timeout') {
                errorMessage = 'Request timed out';
            } else if (status === 'abort') {
                errorMessage = 'Request was aborted';
            } else if (status === 'parsererror') {
                errorMessage = 'Response parsing failed';
            }

            $status.removeClass('loading success').addClass('error').text(bkgt_swe3_ajax.strings.error + ': ' + errorMessage);

            // Show detailed error information
            BKGT_SWE3_Admin.showDetailedError(errorDetails);
        },

        handleSettingsUpdate: function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submitButton = $form.find('input[type="submit"]');
            var originalText = $submitButton.val();

            $submitButton.prop('disabled', true).val('Saving...');

            var formData = $form.serialize();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData + '&action=bkgt_swe3_update_settings',
                success: function(response) {
                    $submitButton.prop('disabled', false).val(originalText);

                    if (response.success) {
                        BKGT_SWE3_Admin.showSuccessMessage('Settings saved successfully');
                    } else {
                        BKGT_SWE3_Admin.showErrorMessage('Error saving settings: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    $submitButton.prop('disabled', false).val(originalText);
                    BKGT_SWE3_Admin.showErrorMessage('AJAX Error: ' + error);
                }
            });
        },

        handleToggleScraping: function() {
            var enabled = $(this).is(':checked');

            $.ajax({
                url: bkgt_swe3_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_swe3_toggle_scraping',
                    enabled: enabled,
                    nonce: bkgt_swe3_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        BKGT_SWE3_Admin.showSuccessMessage(response.data.message);
                        // Refresh status after a short delay
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        BKGT_SWE3_Admin.showErrorMessage(response.data.message);
                        // Revert checkbox
                        $('#bkgt_swe3_scrape_enabled').prop('checked', !enabled);
                    }
                },
                error: function(xhr, status, error) {
                    BKGT_SWE3_Admin.showErrorMessage('Error toggling scraping: ' + error);
                    // Revert checkbox
                    $('#bkgt_swe3_scrape_enabled').prop('checked', !enabled);
                }
            });
        },

        handleScheduleChange: function() {
            var hour = $('#bkgt_swe3_scrape_hour').val();
            var minute = $('#bkgt_swe3_scrape_minute').val();

            $.ajax({
                url: bkgt_swe3_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_swe3_update_schedule',
                    hour: hour,
                    minute: minute,
                    nonce: bkgt_swe3_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        BKGT_SWE3_Admin.showSuccessMessage(response.data.message);
                    } else {
                        BKGT_SWE3_Admin.showErrorMessage(response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    BKGT_SWE3_Admin.showErrorMessage('Error updating schedule: ' + error);
                }
            });
        },

        refreshActivityLog: function() {
            var $activityLog = $('#bkgt-swe3-activity-log');

            $activityLog.html('<p>Loading...</p>');

            $.ajax({
                url: bkgt_swe3_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_swe3_refresh_activity',
                    nonce: bkgt_swe3_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $activityLog.html(response.data.html);
                    } else {
                        $activityLog.html('<p>Error loading activity log</p>');
                    }
                },
                error: function(xhr, status, error) {
                    $activityLog.html('<p>Error loading activity log: ' + error + '</p>');
                }
            });
        },

        showSuccessMessage: function(message) {
            this.showMessage(message, 'success');
        },

        showErrorMessage: function(message) {
            this.showMessage(message, 'error');
        },

        showMessage: function(message, type) {
            // Remove existing messages
            $('.bkgt-swe3-message').remove();

            var cssClass = type === 'success' ? 'bkgt-swe3-success-message' : 'bkgt-swe3-error-message';
            var icon = type === 'success' ? '✓' : '✕';

            var $message = $('<div class="bkgt-swe3-message ' + cssClass + '">' +
                '<strong>' + icon + ' ' + message + '</strong>' +
                '</div>');

            $('.bkgt-swe3-admin-container').prepend($message);

            // Auto-hide after 5 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        // Utility functions
        formatTime: function(timestamp) {
            var date = new Date(timestamp * 1000);
            return date.toLocaleString();
        },

        showDetailedError: function(errorDetails) {
            // Create or update debug section
            var $debugSection = $('#bkgt-swe3-debug-section');
            if (!$debugSection.length) {
                $debugSection = $('<div id="bkgt-swe3-debug-section" class="bkgt-swe3-debug-section" style="display: none;">' +
                    '<h3>Debug Information <button type="button" id="bkgt-swe3-toggle-debug" class="button">Toggle Details</button></h3>' +
                    '<div id="bkgt-swe3-debug-content" class="bkgt-swe3-debug-content" style="display: none;">' +
                        '<pre id="bkgt-swe3-debug-output"></pre>' +
                    '</div>' +
                '</div>');
                $('#bkgt-swe3-scraper-status').after($debugSection);

                // Bind toggle event
                $('#bkgt-swe3-toggle-debug').on('click', function() {
                    $('#bkgt-swe3-debug-content').toggle();
                });
            }

            // Format error details for display
            var debugOutput = '=== AJAX Error Details ===\n';
            debugOutput += 'Timestamp: ' + errorDetails.timestamp + '\n';
            debugOutput += 'AJAX Status: ' + errorDetails.ajaxStatus + '\n';
            debugOutput += 'Error Thrown: ' + (errorDetails.errorThrown || 'null') + '\n';
            debugOutput += 'XHR Status: ' + errorDetails.xhrStatus + '\n';
            debugOutput += 'XHR Status Text: ' + errorDetails.xhrStatusText + '\n';
            debugOutput += 'Ready State: ' + errorDetails.readyState + '\n\n';

            debugOutput += '=== Response Headers ===\n';
            debugOutput += errorDetails.headers + '\n\n';

            debugOutput += '=== Response Text ===\n';
            debugOutput += errorDetails.responseText + '\n\n';

            debugOutput += '=== Parsed Response JSON ===\n';
            if (typeof errorDetails.responseJSON === 'object') {
                debugOutput += JSON.stringify(errorDetails.responseJSON, null, 2) + '\n';
            } else {
                debugOutput += errorDetails.responseJSON + '\n';
            }

            $('#bkgt-swe3-debug-output').text(debugOutput);
            $debugSection.show();
        },

        formatBytes: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    };

})(jQuery);