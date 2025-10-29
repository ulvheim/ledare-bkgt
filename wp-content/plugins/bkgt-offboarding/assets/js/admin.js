/**
 * BKGT Offboarding System - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Initialize datepickers
        $('.bkgt-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 0
        });

        // Task completion in admin interface
        $('.bkgt-admin-task-checkbox').on('change', function() {
            var $checkbox = $(this);
            var postId = $checkbox.data('post-id');
            var taskIndex = $checkbox.data('task-index');
            var completed = $checkbox.is(':checked');

            $.ajax({
                url: bkgt_offboarding_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_update_offboarding_task',
                    post_id: postId,
                    task_index: taskIndex,
                    completed: completed,
                    nonce: bkgt_offboarding_ajax.nonce
                },
                beforeSend: function() {
                    $checkbox.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        $checkbox.closest('.bkgt-task-item').toggleClass('completed', completed);
                        updateProgressBar(postId);
                    } else {
                        $checkbox.prop('checked', !completed);
                        alert('Error updating task status. Please try again.');
                    }
                },
                error: function() {
                    $checkbox.prop('checked', !completed);
                    alert('Error updating task status. Please try again.');
                },
                complete: function() {
                    $checkbox.prop('disabled', false);
                }
            });
        });

        // Equipment return status update
        $('.bkgt-equipment-status').on('change', function() {
            var $select = $(this);
            var assignmentId = $select.data('assignment-id');
            var status = $select.val();

            $.ajax({
                url: bkgt_offboarding_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_update_equipment_status',
                    assignment_id: assignmentId,
                    status: status,
                    nonce: bkgt_offboarding_ajax.nonce
                },
                beforeSend: function() {
                    $select.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        $select.closest('tr').removeClass('returned pending damaged').addClass(status.toLowerCase());
                    } else {
                        alert('Error updating equipment status. Please try again.');
                    }
                },
                error: function() {
                    alert('Error updating equipment status. Please try again.');
                },
                complete: function() {
                    $select.prop('disabled', false);
                }
            });
        });

        // Add notification button
        $('.bkgt-add-notification').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var postId = $button.data('post-id');
            var $container = $button.closest('.bkgt-notifications-section');

            var $newNotification = $('<div class="bkgt-notification-item new">' +
                '<input type="text" placeholder="Notification message" class="bkgt-notification-message">' +
                '<input type="date" class="bkgt-notification-date bkgt-datepicker">' +
                '<button class="button bkgt-save-notification" data-post-id="' + postId + '">Save</button>' +
                '<button class="button bkgt-cancel-notification">Cancel</button>' +
                '</div>');

            $container.find('.bkgt-notifications-list').append($newNotification);

            // Initialize datepicker for new notification
            $newNotification.find('.bkgt-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0
            });
        });

        // Save notification
        $(document).on('click', '.bkgt-save-notification', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $item = $button.closest('.bkgt-notification-item');
            var postId = $button.data('post-id');
            var message = $item.find('.bkgt-notification-message').val();
            var date = $item.find('.bkgt-notification-date').val();

            if (!message || !date) {
                alert('Please fill in both message and date.');
                return;
            }

            $.ajax({
                url: bkgt_offboarding_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_add_notification',
                    post_id: postId,
                    message: message,
                    notification_date: date,
                    nonce: bkgt_offboarding_ajax.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true).text('Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        $item.removeClass('new').find('.bkgt-notification-message, .bkgt-notification-date').prop('disabled', true);
                        $button.remove();
                        $item.find('.bkgt-cancel-notification').remove();
                    } else {
                        alert('Error saving notification. Please try again.');
                    }
                },
                error: function() {
                    alert('Error saving notification. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Save');
                }
            });
        });

        // Cancel new notification
        $(document).on('click', '.bkgt-cancel-notification', function(e) {
            e.preventDefault();
            $(this).closest('.bkgt-notification-item.new').remove();
        });

        // Delete notification
        $(document).on('click', '.bkgt-delete-notification', function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to delete this notification?')) {
                return;
            }

            var $button = $(this);
            var notificationId = $button.data('notification-id');

            $.ajax({
                url: bkgt_offboarding_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_delete_notification',
                    notification_id: notificationId,
                    nonce: bkgt_offboarding_ajax.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        $button.closest('.bkgt-notification-item').fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error deleting notification. Please try again.');
                    }
                },
                error: function() {
                    alert('Error deleting notification. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });

        // Complete offboarding process
        $('.bkgt-admin-complete-offboarding').on('click', function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to complete this offboarding process? This will deactivate the user account and cannot be undone.')) {
                return;
            }

            var $button = $(this);
            var postId = $button.data('post-id');

            $button.prop('disabled', true).text('Completing...');

            $.ajax({
                url: bkgt_offboarding_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_complete_offboarding',
                    post_id: postId,
                    nonce: bkgt_offboarding_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Offboarding process completed successfully!');
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error completing offboarding process.');
                    }
                },
                error: function() {
                    alert('Error completing offboarding process. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Complete Offboarding');
                }
            });
        });

        // Delete notification
        $(document).on('click', '.bkgt-delete-notification', function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to delete this notification?')) {
                return;
            }

            var $button = $(this);
            var $item = $button.closest('.bkgt-notification-item');
            var postId = $button.closest('.bkgt-notifications-section').find('.bkgt-save-notification').data('post-id');
            var index = $item.data('index');

            $.ajax({
                url: bkgt_offboarding_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_delete_notification',
                    post_id: postId,
                    index: index,
                    nonce: bkgt_offboarding_ajax.nonce
                },
                beforeSend: function() {
                    $button.prop('disabled', true).text('Deleting...');
                },
                success: function(response) {
                    if (response.success) {
                        $item.fadeOut(function() {
                            $(this).remove();
                            // Check if list is empty
                            if ($('.bkgt-notifications-list .bkgt-notification-item').length === 0) {
                                $('.bkgt-notifications-list').html('<p>No notifications scheduled.</p>');
                            }
                        });
                    } else {
                        alert('Error deleting notification. Please try again.');
                        $button.prop('disabled', false).text('Delete');
                    }
                },
                error: function() {
                    alert('Error deleting notification. Please try again.');
                    $button.prop('disabled', false).text('Delete');
                }
            });
        });

        // Update progress bar function
        function updateProgressBar(postId) {
            var $progressBar = $('.bkgt-progress-bar[data-post-id="' + postId + '"]');
            if ($progressBar.length === 0) return;

            var totalTasks = $('.bkgt-admin-task-checkbox[data-post-id="' + postId + '"]').length;
            var completedTasks = $('.bkgt-admin-task-checkbox[data-post-id="' + postId + '"]:checked').length;
            var percentage = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;

            $progressBar.css('width', percentage + '%').text(percentage + '%');
        }

        // Initialize progress bars on page load
        $('.bkgt-progress-bar').each(function() {
            var postId = $(this).data('post-id');
            updateProgressBar(postId);
        });

    });

})(jQuery);