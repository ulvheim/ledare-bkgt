// BKGT Communication Frontend Scripts

jQuery(document).ready(function($) {
    
    // Tab switching
    $('.tab-button').on('click', function() {
        var tab = $(this).data('tab');
        
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        
        $('.tab-content').removeClass('active');
        $('#' + tab + '-tab').addClass('active');
    });
    
    // Send message form
    $('#send-message-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'bkgt_send_message',
            nonce: bkgt_comm_ajax.nonce,
            subject: $('#message-subject').val(),
            message: $('#message-content').val(),
            recipients: $('input[name="recipients[]"]:checked').map(function() {
                return this.value;
            }).get()
        };
        
        $.post(bkgt_comm_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                alert(response.data);
                $('#send-message-form')[0].reset();
            } else {
                alert(response.data);
            }
        });
    });
    
    // Load notifications
    function loadNotifications() {
        $.post(bkgt_comm_ajax.ajax_url, {
            action: 'bkgt_get_notifications',
            nonce: bkgt_comm_ajax.nonce
        }, function(response) {
            if (response.success) {
                var html = '<ul>';
                if (response.data.length > 0) {
                    response.data.forEach(function(notification) {
                        html += '<li>' + notification.message + '</li>';
                    });
                } else {
                    html += '<li>Inga notifikationer</li>';
                }
                html += '</ul>';
                $('#notifications-list').html(html);
            }
        });
    }
    
    // Load notifications when notifications tab is clicked
    $('button[data-tab="notifications"]').on('click', function() {
        loadNotifications();
    });
    
});