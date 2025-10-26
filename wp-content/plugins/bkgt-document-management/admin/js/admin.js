// Admin JavaScript for BKGT Document Management

jQuery(document).ready(function($) {
    'use strict';

    // Initialize any admin functionality here

    // Example: Handle document upload
    $('.bkgt-upload-btn').on('click', function(e) {
        e.preventDefault();

        var fileInput = $(this).siblings('.bkgt-file-input');
        fileInput.click();
    });

    // Example: Handle file input change
    $('.bkgt-file-input').on('change', function() {
        var file = this.files[0];
        if (file) {
            // Handle file upload
            console.log('File selected:', file.name);
        }
    });

    // Example: Handle AJAX actions
    $('.bkgt-ajax-action').on('click', function(e) {
        e.preventDefault();

        var $btn = $(this);
        var action = $btn.data('action');
        var data = $btn.data('data') || {};

        $btn.prop('disabled', true).text('Bearbetar...');

        $.ajax({
            url: bkgt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: action,
                nonce: bkgt_ajax.nonce,
                data: data
            },
            success: function(response) {
                if (response.success) {
                    // Handle success
                    console.log('Success:', response.data);
                } else {
                    // Handle error
                    console.error('Error:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
            },
            complete: function() {
                $btn.prop('disabled', false).text($btn.data('original-text') || 'Klar');
            }
        });
    });

    // Initialize tooltips if needed
    $('.bkgt-tooltip').tooltip({
        position: {
            my: 'center bottom',
            at: 'center top'
        }
    });
});