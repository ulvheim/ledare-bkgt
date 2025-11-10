/**
 * BKGT API Admin JavaScript
 */

(function($) {
    'use strict';

    const BKGT_API_Admin = {
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.initFilters();
            this.initModals();
        },

        bindEvents: function() {
            // API Key management
            $(document).on('submit', '#bkgt-api-create-key-form', this.createApiKey);
            $(document).on('click', '.bkgt-api-toggle-key', this.toggleApiKeyVisibility);
            $(document).on('click', '.bkgt-api-generate-key', this.generateApiKey);
            $(document).on('click', '.bkgt-api-revoke-key', this.revokeApiKey);
            $(document).on('click', '.bkgt-api-delete-key', this.deleteApiKey);
            $(document).on('click', '.bkgt-api-edit-key', this.editApiKey);

            // Log filtering
            $(document).on('change', '.bkgt-api-filter', this.filterLogs);
            $(document).on('click', '.bkgt-api-clear-filters', this.clearFilters);

            // Security actions
            $(document).on('click', '.bkgt-api-block-ip', this.blockIP);
            $(document).on('click', '.bkgt-api-unblock-ip', this.unblockIP);
            $(document).on('click', '.bkgt-api-clear-logs', this.clearLogs);

            // Notification actions
            $(document).on('click', '.bkgt-api-mark-read', this.markNotificationRead);
            $(document).on('click', '.bkgt-api-mark-all-read', this.markAllNotificationsRead);

            // Settings
            $(document).on('change', '.bkgt-api-setting-toggle', this.toggleSetting);
            $(document).on('click', '.bkgt-api-test-endpoint', this.testEndpoint);

            // Modal actions
            $(document).on('click', '.bkgt-api-modal-close', this.closeModal);
            $(document).on('click', '.bkgt-api-show-details', this.showDetails);

            // API Key modal actions
            $(document).on('click', '#bkgt-api-key-copy', this.copyApiKey);
            $(document).on('click', '#bkgt-api-key-close', this.closeApiKeyModal);
            $(document).on('click', '#bkgt-api-key-modal .bkgt-modal-overlay', this.closeApiKeyModal);

            // Auto-refresh
            this.initAutoRefresh();
        },

        initCharts: function() {
            // Initialize any charts if Chart.js is available
            if (typeof Chart !== 'undefined') {
                this.createActivityChart();
                this.createSecurityChart();
            }
        },

        createActivityChart: function() {
            const ctx = document.getElementById('bkgt-api-activity-chart');
            if (!ctx) return;

            const data = {
                labels: ['GET', 'POST', 'PUT', 'DELETE'],
                datasets: [{
                    data: [120, 45, 23, 8],
                    backgroundColor: [
                        '#e6f3ff',
                        '#e8f5e8',
                        '#fff3cd',
                        '#f8d7da'
                    ],
                    borderColor: [
                        '#0066cc',
                        '#28a745',
                        '#856404',
                        '#721c24'
                    ],
                    borderWidth: 1
                }]
            };

            new Chart(ctx, {
                type: 'doughnut',
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        },

        createSecurityChart: function() {
            const ctx = document.getElementById('bkgt-api-security-chart');
            if (!ctx) return;

            const data = {
                labels: ['Last 24h', 'Last 7d', 'Last 30d'],
                datasets: [{
                    label: 'Blocked Requests',
                    data: [12, 45, 156],
                    backgroundColor: '#dc3545',
                    borderColor: '#dc3545',
                    borderWidth: 1
                }, {
                    label: 'Rate Limited',
                    data: [23, 89, 234],
                    backgroundColor: '#ffc107',
                    borderColor: '#ffc107',
                    borderWidth: 1
                }]
            };

            new Chart(ctx, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        initFilters: function() {
            // Initialize date pickers if available
            if ($.fn.datepicker) {
                $('.bkgt-api-date-filter').datepicker({
                    dateFormat: 'yy-mm-dd',
                    maxDate: 0
                });
            }

            // Initialize select2 if available
            if ($.fn.select2) {
                $('.bkgt-api-filter-select').select2({
                    placeholder: 'Select...',
                    allowClear: true
                });
            }
        },

        initModals: function() {
            // Close modal when clicking outside
            $(document).on('click', '.bkgt-api-modal', function(e) {
                if (e.target === this) {
                    BKGT_API_Admin.closeModal();
                }
            });

            // Close modal on escape key
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) {
                    BKGT_API_Admin.closeModal();
                }
            });
        },

        initAutoRefresh: function() {
            // Auto-refresh dashboard data every 30 seconds
            setInterval(function() {
                if ($('.bkgt-api-dashboard').length && !$('.bkgt-api-loading').length) {
                    BKGT_API_Admin.refreshDashboard();
                }
            }, 30000);
        },

        toggleApiKeyVisibility: function() {
            const $button = $(this);
            const $keyField = $button.closest('tr').find('.bkgt-api-key-value');
            const isVisible = $keyField.hasClass('bkgt-api-key-visible');

            if (isVisible) {
                $keyField.text('••••••••••••••••').removeClass('bkgt-api-key-visible');
                $button.find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                const key = $keyField.data('key');
                $keyField.text(key).addClass('bkgt-api-key-visible');
                $button.find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        },

        generateApiKey: function() {
            const $button = $(this);
            const $container = $button.closest('.bkgt-api-section');

            $button.prop('disabled', true).text('Generating...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bkgt_api_generate_key',
                    nonce: bkgt_api_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error generating API key: ' + response.data);
                        $button.prop('disabled', false).text('Generate New Key');
                    }
                },
                error: function() {
                    alert('Error generating API key');
                    $button.prop('disabled', false).text('Generate New Key');
                }
            });
        },

        createApiKey: function(e) {
            e.preventDefault();

            const $form = $(this);
            const $submitButton = $form.find('button[type="submit"]');
            const formData = new FormData(this);

            $submitButton.prop('disabled', true).text('Creating...');

            // Convert FormData to object for AJAX
            const permissions = formData.getAll('key_permissions[]');
            
            const data = {
                action: 'bkgt_api_create_key',
                _wpnonce: bkgt_api_admin.nonce,
                key_name: formData.get('key_name'),
                key_permissions: permissions
            };

            $.ajax({
                url: bkgt_api_admin.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    console.log('AJAX success response:', response);
                    if (response.success) {
                        console.log('Response success, API key:', response.data.api_key);
                        // Show success message with the API key in modal
                        const apiKey = response.data.api_key;
                        console.log('Calling showApiKeyModal with:', apiKey.api_key);

                        try {
                            BKGT_API_Admin.showApiKeyModal(apiKey.api_key);
                            console.log('Modal should now be visible');

                            // Fallback: reload page after 3 seconds if modal doesn't show
                            setTimeout(function() {
                                console.log('Fallback: reloading page');
                                location.reload();
                            }, 3000);

                        } catch (error) {
                            console.error('Error showing modal:', error);
                            // Fallback: just reload the page
                            location.reload();
                        }
                    } else {
                        alert('Error creating API key: ' + (response.data || 'Unknown error'));
                        $submitButton.prop('disabled', false).text('Create API Key');
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error creating API key: ' + error + ' (Status: ' + xhr.status + ')');
                    $submitButton.prop('disabled', false).text('Create API Key');
                }
            });
        },

        revokeApiKey: function() {
            if (!confirm('Are you sure you want to revoke this API key? This action cannot be undone.')) {
                return;
            }

            const $button = $(this);
            const keyId = $button.data('key-id');

            $button.prop('disabled', true).text('Revoking...');

            $.ajax({
                url: bkgt_api_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_api_revoke_key',
                    key_id: keyId,
                    _wpnonce: bkgt_api_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error revoking API key: ' + response.data);
                        $button.prop('disabled', false).text('Revoke');
                    }
                },
                error: function() {
                    alert('Error revoking API key');
                    $button.prop('disabled', false).text('Revoke');
                }
            });
        },

        deleteApiKey: function() {
            console.log('deleteApiKey function called');
            const $button = $(this);
            const keyId = $button.data('key-id');
            console.log('Key ID:', keyId);

            if (!confirm('Are you sure you want to permanently delete this API key? This action cannot be undone and the key will be completely removed from the database.')) {
                console.log('User cancelled delete');
                return;
            }

            console.log('User confirmed delete, proceeding...');
            $button.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: bkgt_api_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_api_delete_key',
                    key_id: keyId,
                    _wpnonce: bkgt_api_admin.nonce
                },
                success: function(response) {
                    console.log('AJAX success response:', response);
                    if (response.success) {
                        console.log('Delete successful, reloading page');
                        location.reload();
                    } else {
                        console.log('Delete failed:', response.data);
                        alert('Error deleting API key: ' + response.data);
                        $button.prop('disabled', false).text('Delete');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error:', xhr.status, status, error);
                    alert('Error deleting API key');
                    $button.prop('disabled', false).text('Delete');
                }
            });
        },

        editApiKey: function() {
            const $button = $(this);
            const keyId = $button.data('key-id');
            const currentName = $button.closest('tr').find('td:first').text().trim();

            const newName = prompt('Enter new name for API key:', currentName);
            if (!newName || newName === currentName) return;

            $button.prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bkgt_api_update_key',
                    key_id: keyId,
                    name: newName,
                    nonce: bkgt_api_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error updating API key: ' + response.data);
                        $button.prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Error updating API key');
                    $button.prop('disabled', false);
                }
            });
        },

        filterLogs: function() {
            const $container = $(this).closest('.bkgt-api-section');
            const filters = {};

            $container.find('.bkgt-api-filter').each(function() {
                const $filter = $(this);
                const name = $filter.attr('name');
                const value = $filter.val();

                if (value && value !== '') {
                    filters[name] = value;
                }
            });

            BKGT_API_Admin.loadFilteredLogs($container, filters);
        },

        clearFilters: function() {
            const $container = $(this).closest('.bkgt-api-section');

            $container.find('.bkgt-api-filter').val('');
            $container.find('.bkgt-api-filter-select').trigger('change');

            BKGT_API_Admin.loadFilteredLogs($container, {});
        },

        loadFilteredLogs: function($container, filters) {
            const $table = $container.find('.wp-list-table');
            const action = $container.data('action');

            $container.addClass('bkgt-api-loading');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: action,
                    filters: filters,
                    nonce: bkgt_api_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $table.find('tbody').html(response.data.html);
                        BKGT_API_Admin.updatePagination($container, response.data.pagination);
                    } else {
                        alert('Error loading logs: ' + response.data);
                    }
                },
                error: function() {
                    alert('Error loading logs');
                },
                complete: function() {
                    $container.removeClass('bkgt-api-loading');
                }
            });
        },

        updatePagination: function($container, pagination) {
            const $pagination = $container.find('.tablenav .tablenav-pages');
            if (pagination) {
                $pagination.html(pagination);
            }
        },

        blockIP: function() {
            const $button = $(this);
            const ip = $button.data('ip');

            if (!confirm(`Are you sure you want to block IP ${ip}?`)) {
                return;
            }

            $button.prop('disabled', true).text('Blocking...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bkgt_api_block_ip',
                    ip: ip,
                    nonce: bkgt_api_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('tr').find('.bkgt-api-status').removeClass('status-active').addClass('status-inactive').text('Blocked');
                        $button.removeClass('bkgt-api-block-ip').addClass('bkgt-api-unblock-ip').text('Unblock').prop('disabled', false);
                    } else {
                        alert('Error blocking IP: ' + response.data);
                        $button.prop('disabled', false).text('Block');
                    }
                },
                error: function() {
                    alert('Error blocking IP');
                    $button.prop('disabled', false).text('Block');
                }
            });
        },

        unblockIP: function() {
            const $button = $(this);
            const ip = $button.data('ip');

            $button.prop('disabled', true).text('Unblocking...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bkgt_api_unblock_ip',
                    ip: ip,
                    nonce: bkgt_api_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('tr').find('.bkgt-api-status').removeClass('status-inactive').addClass('status-active').text('Active');
                        $button.removeClass('bkgt-api-unblock-ip').addClass('bkgt-api-block-ip').text('Block').prop('disabled', false);
                    } else {
                        alert('Error unblocking IP: ' + response.data);
                        $button.prop('disabled', false).text('Unblock');
                    }
                },
                error: function() {
                    alert('Error unblocking IP');
                    $button.prop('disabled', false).text('Unblock');
                }
            });
        },

        clearLogs: function() {
            const $button = $(this);
            const logType = $button.data('type');

            if (!confirm(`Are you sure you want to clear all ${logType} logs? This action cannot be undone.`)) {
                return;
            }

            $button.prop('disabled', true).text('Clearing...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bkgt_api_clear_logs',
                    type: logType,
                    nonce: bkgt_api_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error clearing logs: ' + response.data);
                        $button.prop('disabled', false).text('Clear Logs');
                    }
                },
                error: function() {
                    alert('Error clearing logs');
                    $button.prop('disabled', false).text('Clear Logs');
                }
            });
        },

        markNotificationRead: function() {
            const $button = $(this);
            const notificationId = $button.data('id');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bkgt_api_mark_notification_read',
                    id: notificationId,
                    nonce: bkgt_api_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('.bkgt-api-notification-item').fadeOut();
                    }
                }
            });
        },

        markAllNotificationsRead: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bkgt_api_mark_all_notifications_read',
                    nonce: bkgt_api_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.bkgt-api-notification-item').fadeOut();
                    }
                }
            });
        },

        toggleSetting: function() {
            const $toggle = $(this);
            const setting = $toggle.attr('name');
            const value = $toggle.is(':checked') ? '1' : '0';

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bkgt_api_update_setting',
                    setting: setting,
                    value: value,
                    nonce: bkgt_api_admin.nonce
                },
                success: function(response) {
                    if (!response.success) {
                        alert('Error updating setting: ' + response.data);
                        $toggle.prop('checked', !$toggle.is(':checked'));
                    }
                },
                error: function() {
                    alert('Error updating setting');
                    $toggle.prop('checked', !$toggle.is(':checked'));
                }
            });
        },

        testEndpoint: function() {
            const $button = $(this);
            const endpoint = $button.data('endpoint');

            $button.prop('disabled', true).text('Testing...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bkgt_api_test_endpoint',
                    endpoint: endpoint,
                    nonce: bkgt_api_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Endpoint test successful: ' + response.data);
                    } else {
                        alert('Endpoint test failed: ' + response.data);
                    }
                    $button.prop('disabled', false).text('Test Endpoint');
                },
                error: function() {
                    alert('Error testing endpoint');
                    $button.prop('disabled', false).text('Test Endpoint');
                }
            });
        },

        showDetails: function() {
            const $button = $(this);
            const data = $button.data('details');
            const title = $button.data('title') || 'Details';

            BKGT_API_Admin.showModal(title, '<pre>' + JSON.stringify(data, null, 2) + '</pre>');
        },

        showModal: function(title, content) {
            const modal = `
                <div class="bkgt-api-modal">
                    <div class="bkgt-api-modal-content">
                        <div class="bkgt-api-modal-header">
                            <h3>${title}</h3>
                            <button class="bkgt-api-modal-close">&times;</button>
                        </div>
                        <div class="bkgt-api-modal-body">
                            ${content}
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modal);
        },

        closeModal: function() {
            $('.bkgt-api-modal').remove();
        },

        refreshDashboard: function() {
            const $dashboard = $('.bkgt-api-dashboard');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bkgt_api_refresh_dashboard',
                    nonce: bkgt_api_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update stats
                        if (response.data.stats) {
                            Object.keys(response.data.stats).forEach(function(key) {
                                $(`.bkgt-api-stat-${key} .bkgt-api-stat-content h3`).text(response.data.stats[key]);
                            });
                        }

                        // Update activity
                        if (response.data.activity) {
                            $('.bkgt-api-activity-list').html(response.data.activity);
                        }

                        // Update notifications
                        if (response.data.notifications) {
                            $('.bkgt-api-notifications-list').html(response.data.notifications);
                        }
                    }
                }
            });
        },

        showApiKeyModal: function(apiKey) {
            console.log('showApiKeyModal called with API key:', apiKey);

            // Check if modal exists
            const $modal = $('#bkgt-api-key-modal');
            console.log('Modal element found:', $modal.length > 0);

            if ($modal.length === 0) {
                console.error('Modal element not found on page!');
                alert('Modal not found! API Key: ' + apiKey + '\n\nPlease save this key securely.');
                location.reload();
                return;
            }

            // Set the API key in the input field
            const $input = $('#bkgt-api-key-display');
            console.log('Input element found:', $input.length > 0);

            if ($input.length === 0) {
                console.error('Input element not found!');
                alert('Input field not found! API Key: ' + apiKey + '\n\nPlease save this key securely.');
                location.reload();
                return;
            }

            $input.val(apiKey);

            // Force show the modal with maximum visibility
            $modal.css({
                'display': 'block',
                'visibility': 'visible',
                'opacity': '1',
                'z-index': '999999',
                'position': 'fixed',
                'top': '0',
                'left': '0',
                'width': '100%',
                'height': '100%'
            });

            // Force show overlay
            $modal.find('.bkgt-modal-overlay').css({
                'display': 'block',
                'visibility': 'visible',
                'opacity': '1',
                'z-index': '999998'
            });

            // Force show content
            $modal.find('.bkgt-modal-content').css({
                'display': 'block',
                'visibility': 'visible',
                'opacity': '1',
                'z-index': '999999',
                'position': 'relative',
                'border': '5px solid red',
                'background': 'white'
            });

            console.log('Modal forced to maximum visibility');

            // Focus on the input field for easy selection
            $input.focus().select();
            console.log('Input focused and selected');

            // Scroll to top to ensure modal is visible
            window.scrollTo(0, 0);
        },

        copyApiKey: function() {
            const $input = $('#bkgt-api-key-display');
            const $button = $('#bkgt-api-key-copy');

            // Select the text
            $input.select();

            try {
                // Copy to clipboard
                document.execCommand('copy');

                // Show success feedback
                const originalText = $button.text();
                $button.text(bkgt_api_admin.strings.success || 'Copied!');
                $button.removeClass('button-secondary').addClass('button-success');

                setTimeout(function() {
                    $button.text(originalText);
                    $button.removeClass('button-success').addClass('button-secondary');
                }, 2000);
            } catch (err) {
                // Fallback: show alert with instructions
                alert('Please select and copy the API key manually: ' + $input.val());
            }
        },

        closeApiKeyModal: function() {
            $('#bkgt-api-key-modal').hide();
            // Reload the page to show the new key in the list
            location.reload();
        },

        // Update management
        initUpdates: function() {
            if (!$('#bkgt-updates-list').length) {
                return;
            }

            this.loadUpdates();
            this.bindUpdateEvents();
        },

        bindUpdateEvents: function() {
            // Update upload form
            $(document).on('submit', '#bkgt-update-upload-form', this.uploadUpdate);

            // Update list actions
            $(document).on('click', '.bkgt-update-deactivate', this.deactivateUpdate);
            $(document).on('click', '.bkgt-update-refresh', this.loadUpdates);
        },

        uploadUpdate: function(e) {
            e.preventDefault();

            const $form = $(this);
            const $submitBtn = $form.find('#bkgt-update-upload-btn');
            const $status = $('#bkgt-update-upload-status');

            // Get form data
            const formData = new FormData(this);

            // Add nonce
            formData.append('action', 'bkgt_api_upload_update');
            formData.append('nonce', bkgt_api_admin.nonce);

            // Disable form
            $submitBtn.prop('disabled', true).text(bkgt_api_admin.strings.loading);
            $status.removeClass('success error').addClass('loading').text(bkgt_api_admin.strings.loading);

            // Upload via AJAX
            $.ajax({
                url: bkgt_api_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $status.removeClass('loading error').addClass('success').text(response.data.message || bkgt_api_admin.strings.success);
                        $form[0].reset();
                        BKGT_API_Admin.loadUpdates();
                    } else {
                        $status.removeClass('loading success').addClass('error').text(response.data.message || bkgt_api_admin.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    let message = bkgt_api_admin.strings.error;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        message = xhr.responseJSON.data.message;
                    }
                    $status.removeClass('loading success').addClass('error').text(message);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(bkgt_api.strings ? bkgt_api.strings.upload_update : 'Upload Update');
                }
            });
        },

        loadUpdates: function() {
            const $container = $('#bkgt-updates-list');

            $container.html('<p>' + bkgt_api_admin.strings.loading + '</p>');

            $.ajax({
                url: bkgt_api_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_api_get_updates',
                    nonce: bkgt_api_admin.nonce,
                    page: 1,
                    per_page: 20
                },
                success: function(response) {
                    if (response.success) {
                        BKGT_API_Admin.renderUpdatesList(response.data);
                    } else {
                        $container.html('<p class="error">' + (response.data.message || bkgt_api_admin.strings.error) + '</p>');
                    }
                },
                error: function(xhr, status, error) {
                    $container.html('<p class="error">' + bkgt_api_admin.strings.error + '</p>');
                }
            });
        },

        renderUpdatesList: function(data) {
            const $container = $('#bkgt-updates-list');

            if (!data.updates || data.updates.length === 0) {
                $container.html('<p>' + (bkgt_api.strings ? bkgt_api.strings.no_updates : 'No updates found.') + '</p>');
                return;
            }

            let html = '<table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr>';
            html += '<th>Version</th>';
            html += '<th>Release Date</th>';
            html += '<th>Platforms</th>';
            html += '<th>Downloads</th>';
            html += '<th>Status</th>';
            html += '<th>Actions</th>';
            html += '</tr></thead>';
            html += '<tbody>';

            data.updates.forEach(function(update) {
                const statusClass = update.status === 'active' ? 'bkgt-status-active' : 'bkgt-status-inactive';
                const statusText = update.status === 'active' ? 'Active' : 'Inactive';
                const platforms = update.platforms ? update.platforms.join(', ') : 'None';

                html += '<tr>';
                html += '<td><strong>' + update.version + '</strong></td>';
                html += '<td>' + update.release_date + '</td>';
                html += '<td>' + platforms + '</td>';
                html += '<td>' + (update.download_count || 0) + '</td>';
                html += '<td><span class="bkgt-status ' + statusClass + '">' + statusText + '</span></td>';
                html += '<td>';

                if (update.status === 'active') {
                    html += '<button class="button button-small bkgt-update-deactivate" data-version="' + update.version + '" data-nonce="' + wp_create_nonce('bkgt_update_deactivate') + '">Deactivate</button>';
                }

                html += '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';

            // Pagination
            if (data.pagination && data.pagination.total_pages > 1) {
                html += '<div class="tablenav bottom">';
                html += '<div class="tablenav-pages">';
                html += '<span class="displaying-num">' + data.pagination.total + ' items</span>';
                html += '<span class="pagination-links">';

                // Add pagination links here if needed

                html += '</span>';
                html += '</div>';
                html += '</div>';
            }

            $container.html(html);
        },

        deactivateUpdate: function(e) {
            e.preventDefault();

            const $btn = $(this);
            const version = $btn.data('version');
            const nonce = $btn.data('nonce');

            if (!confirm(bkgt_api.strings ? bkgt_api.strings.confirm_deactivate : 'Are you sure you want to deactivate this update?')) {
                return;
            }

            $btn.prop('disabled', true).text(bkgt_api_admin.strings.loading);

            $.ajax({
                url: bkgt_api_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_api_deactivate_update',
                    nonce: nonce,
                    version: version
                },
                success: function(response) {
                    if (response.success) {
                        BKGT_API_Admin.loadUpdates();
                    } else {
                        alert(response.data.message || bkgt_api_admin.strings.error);
                        $btn.prop('disabled', false).text('Deactivate');
                    }
                },
                error: function(xhr, status, error) {
                    alert(bkgt_api_admin.strings.error);
                    $btn.prop('disabled', false).text('Deactivate');
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        BKGT_API_Admin.init();
        BKGT_API_Admin.initUpdates();
    });

    // Expose for debugging
    window.BKGT_API_Admin = BKGT_API_Admin;

})(jQuery);