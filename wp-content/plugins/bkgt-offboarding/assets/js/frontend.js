/**
 * BKGT Offboarding System - Frontend JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Task completion checkbox handler
        $('.bkgt-task-checkbox').on('change', function() {
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
                    } else {
                        // Revert checkbox state on error
                        $checkbox.prop('checked', !completed);
                        alert('Error updating task status. Please try again.');
                    }
                },
                error: function() {
                    // Revert checkbox state on error
                    $checkbox.prop('checked', !completed);
                    alert('Error updating task status. Please try again.');
                },
                complete: function() {
                    $checkbox.prop('disabled', false);
                }
            });
        });

        // Equipment return status handler
        $('.bkgt-equipment-returned').on('change', function() {
            var $checkbox = $(this);
            var assignmentId = $checkbox.data('assignment-id');
            var status = $checkbox.is(':checked') ? 'returned' : 'assigned';

            $checkbox.prop('disabled', true);

            $.ajax({
                url: bkgt_offboarding_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_update_equipment_status',
                    assignment_id: assignmentId,
                    status: status,
                    nonce: bkgt_offboarding_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $checkbox.closest('tr').toggleClass('returned', status === 'returned');
                    } else {
                        // Revert checkbox state on error
                        $checkbox.prop('checked', status === 'assigned');
                        alert('Error updating equipment status. Please try again.');
                    }
                },
                error: function() {
                    // Revert checkbox state on error
                    $checkbox.prop('checked', status === 'assigned');
                    alert('Error updating equipment status. Please try again.');
                },
                complete: function() {
                    $checkbox.prop('disabled', false);
                }
            });
        });

        // Generate PDF receipt button
        $('.bkgt-generate-receipt').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var postId = $button.data('post-id');

            $button.prop('disabled', true).text('Generating...');

            $.ajax({
                url: bkgt_offboarding_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_generate_equipment_receipt',
                    post_id: postId,
                    nonce: bkgt_offboarding_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message || 'Receipt generated successfully!');
                        // Open download link in new tab
                        if (response.data.download_url) {
                            window.open(response.data.download_url, '_blank');
                        }
                    } else {
                        alert(response.data.message || 'Error generating receipt.');
                    }
                },
                error: function() {
                    alert('Error generating receipt. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Generate PDF Receipt');
                }
            });
        });

        // Complete offboarding process (for admins/board members)
        $('.bkgt-complete-offboarding').on('click', function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to complete this offboarding process? This will deactivate the user account.')) {
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

        // Form validation for start offboarding
        $('#bkgt-start-offboarding-form').on('submit', function(e) {
            var userId = $('#user_id').val();
            var endDate = $('#end_date').val();

            if (!userId) {
                e.preventDefault();
                alert('Please select a person for offboarding.');
                return false;
            }

            if (!endDate) {
                e.preventDefault();
                alert('Please select an end date.');
                return false;
            }

            var today = new Date().toISOString().split('T')[0];
            if (endDate < today) {
                e.preventDefault();
                alert('End date cannot be in the past.');
                return false;
            }

            return confirm('Are you sure you want to start the offboarding process for this person?');
        });

        // Auto-populate end date with 30 days from now
        $('#user_id').on('change', function() {
            if ($(this).val() && !$('#end_date').val()) {
                var futureDate = new Date();
                futureDate.setDate(futureDate.getDate() + 30);
                $('#end_date').val(futureDate.toISOString().split('T')[0]);
            }
        });

    });

})(jQuery);