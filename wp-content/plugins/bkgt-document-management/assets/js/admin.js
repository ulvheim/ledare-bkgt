/**
 * Admin JavaScript for BKGT Document Management
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // File upload
        $('#bkgt_upload_file').on('click', function(e) {
            e.preventDefault();

            var fileInput = $('#bkgt_document_file')[0];
            var changeDescription = $('#bkgt_change_description').val();
            var documentId = $('#post_ID').val();

            if (!fileInput.files[0]) {
                alert(bkgt_document_ajax.strings.upload_error);
                return;
            }

            var formData = new FormData();
            formData.append('action', 'bkgt_upload_document');
            formData.append('nonce', bkgt_document_ajax.nonce);
            formData.append('document_id', documentId);
            formData.append('file', fileInput.files[0]);
            formData.append('change_description', changeDescription);

            $('.bkgt-file-upload').addClass('bkgt-loading');

            $.ajax({
                url: bkgt_document_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('.bkgt-file-upload').removeClass('bkgt-loading');

                    if (response.success) {
                        alert(bkgt_document_ajax.strings.upload_success);
                        location.reload();
                    } else {
                        alert(response.data || bkgt_document_ajax.strings.upload_error);
                    }
                },
                error: function() {
                    $('.bkgt-file-upload').removeClass('bkgt-loading');
                    alert(bkgt_document_ajax.strings.upload_error);
                }
            });
        });

        // Access control target type change
        $('#bkgt_access_target_type').on('change', function() {
            var targetType = $(this).val();
            $('.bkgt-access-target').hide();
            $('#bkgt_access_target_' + targetType).show();
        });

        // Add access rule
        $('#bkgt_add_access').on('click', function(e) {
            e.preventDefault();

            var documentId = $('#post_ID').val();
            var targetType = $('#bkgt_access_target_type').val();
            var accessType = $('#bkgt_access_type').val();
            var targetValue = '';

            if (targetType === 'user') {
                targetValue = $('#bkgt_access_user').val();
            } else if (targetType === 'role') {
                targetValue = $('#bkgt_access_role').val();
            } else if (targetType === 'team') {
                targetValue = $('#bkgt_access_team').val();
            }

            if (!targetValue) {
                alert('Välj en målgrupp för åtkomstregeln.');
                return;
            }

            var formData = {
                action: 'bkgt_manage_access',
                nonce: bkgt_document_ajax.nonce,
                document_id: documentId,
                access_action: 'add',
                target_type: targetType,
                target_value: targetValue,
                access_type: accessType
            };

            $('.bkgt-access-control').addClass('bkgt-loading');

            $.post(bkgt_document_ajax.ajax_url, formData, function(response) {
                $('.bkgt-access-control').removeClass('bkgt-loading');

                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || 'Misslyckades att lägga till åtkomstregel.');
                }
            });
        });

        // Remove access rule
        $(document).on('click', '.bkgt-remove-access', function(e) {
            e.preventDefault();

            if (!confirm('Är du säker på att du vill ta bort denna åtkomstregel?')) {
                return;
            }

            var accessId = $(this).data('access-id');
            var documentId = $('#post_ID').val();

            var formData = {
                action: 'bkgt_manage_access',
                nonce: bkgt_document_ajax.nonce,
                document_id: documentId,
                access_action: 'remove',
                access_id: accessId
            };

            $('.bkgt-access-control').addClass('bkgt-loading');

            $.post(bkgt_document_ajax.ajax_url, formData, function(response) {
                $('.bkgt-access-control').removeClass('bkgt-loading');

                if (response.success) {
                    $(e.target).closest('li').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data || 'Misslyckades att ta bort åtkomstregel.');
                }
            });
        });

        // Download version
        $(document).on('click', '.bkgt-download-version', function(e) {
            e.preventDefault();

            var versionId = $(this).data('version-id');
            var downloadUrl = bkgt_document_ajax.ajax_url + '?action=bkgt_download_version&version_id=' + versionId + '&nonce=' + bkgt_document_ajax.nonce;

            window.open(downloadUrl, '_blank');
        });

        // Restore version
        $(document).on('click', '.bkgt-restore-version', function(e) {
            e.preventDefault();

            if (!confirm('Är du säker på att du vill återställa denna version?')) {
                return;
            }

            var versionId = $(this).data('version-id');
            var documentId = $('#post_ID').val();

            var formData = {
                action: 'bkgt_restore_version',
                nonce: bkgt_document_ajax.nonce,
                version_id: versionId,
                document_id: documentId
            };

            $('.bkgt-versions-list').addClass('bkgt-loading');

            $.post(bkgt_document_ajax.ajax_url, formData, function(response) {
                $('.bkgt-versions-list').removeClass('bkgt-loading');

                if (response.success) {
                    alert('Version återställd!');
                    location.reload();
                } else {
                    alert(response.data || 'Misslyckades att återställa version.');
                }
            });
        });

        // Delete document
        $(document).on('click', '.bkgt-delete-document', function(e) {
            e.preventDefault();

            if (!confirm(bkgt_document_ajax.strings.confirm_delete)) {
                return;
            }

            var documentId = $(this).data('document-id');

            var formData = {
                action: 'bkgt_delete_document',
                nonce: bkgt_document_ajax.nonce,
                document_id: documentId
            };

            $('body').addClass('bkgt-loading');

            $.post(bkgt_document_ajax.ajax_url, formData, function(response) {
                $('body').removeClass('bkgt-loading');

                if (response.success) {
                    window.location.href = bkgt_document_ajax.admin_url + 'edit.php?post_type=bkgt_document';
                } else {
                    alert(response.data || 'Misslyckades att radera dokument.');
                }
            });
        });

        // Share document modal
        $(document).on('click', '.bkgt-share-document', function(e) {
            e.preventDefault();

            var documentId = $(this).data('document-id');
            var shareUrl = window.location.origin + '/wp-admin/admin-ajax.php?action=bkgt_share_document&document_id=' + documentId;

            // Create modal
            var modal = $('<div class="bkgt-modal"><div class="bkgt-modal-content"><div class="bkgt-modal-header"><h2>Dela dokument</h2><span class="bkgt-modal-close">&times;</span></div><div class="bkgt-modal-body"><p>Kopiera denna länk för att dela dokumentet:</p><input type="text" readonly value="' + shareUrl + '" style="width:100%;padding:8px;"><p><small>Denna länk kräver inloggning och rättigheter för att visa dokumentet.</small></p></div></div></div>');

            $('body').append(modal);
            modal.show();

            // Close modal
            modal.find('.bkgt-modal-close').on('click', function() {
                modal.remove();
            });

            $(document).on('click', function(e) {
                if ($(e.target).hasClass('bkgt-modal')) {
                    modal.remove();
                }
            });
        });

        // Initialize access target type
        $('#bkgt_access_target_type').trigger('change');

    });

})(jQuery);