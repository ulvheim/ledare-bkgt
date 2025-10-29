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

        // Initialize Monaco Editor for Markdown
        if ($('#bkgt-markdown-editor').length > 0) {
            initializeMonacoEditor();
        }

    });

    // Monaco Editor initialization
    function initializeMonacoEditor() {
        // Show loading state
        $('#bkgt-markdown-editor').html('<div style="display:flex;align-items:center;justify-content:center;height:400px;font-size:16px;color:#666;">' + bkgt_document_ajax.strings.editor_loading + '</div>');

        // Load Monaco Editor
        require.config({ paths: { vs: bkgt_document_ajax.monaco_loader_url } });

        require(['vs/editor/editor.main'], function() {
            // Create Monaco Editor
            window.bkgtMonacoEditor = monaco.editor.create(document.getElementById('bkgt-markdown-editor'), {
                value: $('#bkgt-markdown-content').val() || '',
                language: 'markdown',
                theme: 'vs-light',
                fontSize: 14,
                lineHeight: 1.5,
                wordWrap: 'on',
                minimap: { enabled: false },
                scrollBeyondLastLine: false,
                automaticLayout: true,
                suggestOnTriggerCharacters: true,
                quickSuggestions: {
                    other: true,
                    comments: false,
                    strings: true
                },
                tabSize: 2,
                insertSpaces: true
            });

            // Set up live preview
            setupLivePreview();

            // Set up editor mode switching
            setupEditorModes();

            // Set up toolbar actions
            setupToolbarActions();

            // Set up variable insertion
            setupVariableInsertion();

            // Set up auto-complete for template variables
            setupAutoComplete();

            // Set up collaborative editing indicators
            setupCollaborativeEditing();

            // Update word count
            updateWordCount();

            // Listen for content changes
            window.bkgtMonacoEditor.onDidChangeModelContent(function() {
                var content = window.bkgtMonacoEditor.getValue();
                $('#bkgt-markdown-content').val(content);
                updateWordCount();
                updatePreview();
            });
        });
    }

    // Set up live preview
    function setupLivePreview() {
        updatePreview();
    }

    // Update preview content
    function updatePreview() {
        if (typeof marked !== 'undefined' && window.bkgtMonacoEditor) {
            var markdownContent = window.bkgtMonacoEditor.getValue();
            var htmlContent = marked.parse(markdownContent);
            $('#bkgt-preview-content').html(htmlContent);
        }
    }

    // Set up editor mode switching
    function setupEditorModes() {
        $('input[name="bkgt_editor_mode"]').on('change', function() {
            var mode = $(this).val();
            var $container = $('.bkgt-editor-container');

            $container.attr('data-mode', mode);

            if (mode === 'markdown') {
                $container.find('.bkgt-preview-pane').hide();
                $container.find('.bkgt-markdown-pane').css('width', '100%');
            } else if (mode === 'preview') {
                $container.find('.bkgt-markdown-pane').hide();
                $container.find('.bkgt-preview-pane').css('width', '100%');
            } else { // split
                $container.find('.bkgt-markdown-pane, .bkgt-preview-pane').show();
                $container.find('.bkgt-markdown-pane, .bkgt-preview-pane').css('width', '50%');
            }

            // Refresh Monaco Editor layout
            if (window.bkgtMonacoEditor) {
                setTimeout(function() {
                    window.bkgtMonacoEditor.layout();
                }, 100);
            }
        });

        // Trigger initial mode
        $('input[name="bkgt_editor_mode"]:checked').trigger('change');
    }

    // Set up toolbar actions
    function setupToolbarActions() {
        // Insert media
        $('.bkgt-insert-media').on('click', function(e) {
            e.preventDefault();

            // Open WordPress media uploader
            if (wp.media) {
                var mediaUploader = wp.media({
                    title: 'Infoga media',
                    button: {
                        text: 'Infoga'
                    },
                    multiple: false
                });

                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    var markdownLink = '![' + attachment.alt + '](' + attachment.url + ')';

                    if (window.bkgtMonacoEditor) {
                        var position = window.bkgtMonacoEditor.getPosition();
                        window.bkgtMonacoEditor.executeEdits('', [{
                            range: new monaco.Range(position.lineNumber, position.column, position.lineNumber, position.column),
                            text: markdownLink
                        }]);
                        window.bkgtMonacoEditor.focus();
                    }
                });

                mediaUploader.open();
            }
        });

        // Insert template variable
        $('.bkgt-insert-template').on('click', function(e) {
            e.preventDefault();
            $('.bkgt-template-variables').slideToggle();
        });

        // Fullscreen toggle
        $('.bkgt-toggle-fullscreen').on('click', function(e) {
            e.preventDefault();
            var $editor = $('.bkgt-markdown-editor');
            var $button = $(this);

            if ($editor.hasClass('fullscreen')) {
                $editor.removeClass('fullscreen');
                $button.find('.dashicons').removeClass('dashicons-editor-contract').addClass('dashicons-editor-expand');
            } else {
                $editor.addClass('fullscreen');
                $button.find('.dashicons').removeClass('dashicons-editor-expand').addClass('dashicons-editor-contract');
            }

            // Refresh Monaco Editor layout
            if (window.bkgtMonacoEditor) {
                setTimeout(function() {
                    window.bkgtMonacoEditor.layout();
                }, 300);
            }
        });

        // Refresh preview
        $('.bkgt-refresh-preview').on('click', function(e) {
            e.preventDefault();
            updatePreview();
        });
    }

    // Set up variable insertion
    function setupVariableInsertion() {
        $('.bkgt-insert-variable').on('click', function(e) {
            e.preventDefault();

            var variable = $(this).data('variable');

            if (window.bkgtMonacoEditor) {
                var position = window.bkgtMonacoEditor.getPosition();
                window.bkgtMonacoEditor.executeEdits('', [{
                    range: new monaco.Range(position.lineNumber, position.column, position.lineNumber, position.column),
                    text: variable
                }]);
                window.bkgtMonacoEditor.focus();
            }
        });
    }

    // Set up auto-complete for template variables
    function setupAutoComplete() {
        if (!window.bkgtMonacoEditor || !bkgt_document_ajax.template_variables) {
            return;
        }

        // Register completion item provider for template variables
        monaco.languages.registerCompletionItemProvider('markdown', {
            provideCompletionItems: function(model, position) {
                var word = model.getWordUntilPosition(position);
                var range = {
                    startLineNumber: position.lineNumber,
                    endLineNumber: position.lineNumber,
                    startColumn: word.startColumn,
                    endColumn: word.endColumn
                };

                var suggestions = [];

                // Add template variables as suggestions
                for (var variable in bkgt_document_ajax.template_variables) {
                    if (bkgt_document_ajax.template_variables.hasOwnProperty(variable)) {
                        var description = bkgt_document_ajax.template_variables[variable];

                        // Check if the current word matches the variable (case-insensitive)
                        if (variable.toLowerCase().indexOf(word.word.toLowerCase()) !== -1 ||
                            word.word === '' ||
                            variable.toLowerCase().startsWith(word.word.toLowerCase())) {

                            suggestions.push({
                                label: variable,
                                kind: monaco.languages.CompletionItemKind.Variable,
                                documentation: 'Template variable: ' + description.replace(/_/g, ' '),
                                insertText: variable,
                                range: range
                            });
                        }
                    }
                }

                return { suggestions: suggestions };
            },
            triggerCharacters: ['{']
        });
    }

    // Set up collaborative editing indicators
    function setupCollaborativeEditing() {
        if (!window.bkgtMonacoEditor) {
            return;
        }

        var collaborationStatus = $('#bkgt-collaboration-status');
        var isEditing = false;

        // Update status when editor gains focus
        window.bkgtMonacoEditor.onDidFocusEditorText(function() {
            if (!isEditing) {
                isEditing = true;
                collaborationStatus.html('<span class="dashicons dashicons-edit"></span> ' + (bkgt_document_ajax.strings.editing_now || 'Redigerar nu'));
                collaborationStatus.addClass('active');
            }
        });

        // Update status when editor loses focus
        window.bkgtMonacoEditor.onDidBlurEditorText(function() {
            if (isEditing) {
                isEditing = false;
                collaborationStatus.html('<span class="dashicons dashicons-edit"></span> ' + (bkgt_document_ajax.strings.editing_individual || 'Redigerar enskilt'));
                collaborationStatus.removeClass('active');
            }
        });

        // Auto-save indicator (simulate collaborative saving)
        var saveTimeout;
        window.bkgtMonacoEditor.onDidChangeModelContent(function() {
            clearTimeout(saveTimeout);
            collaborationStatus.html('<span class="dashicons dashicons-update spin"></span> ' + (bkgt_document_ajax.strings.saving || 'Sparar...'));

            saveTimeout = setTimeout(function() {
                if (isEditing) {
                    collaborationStatus.html('<span class="dashicons dashicons-edit"></span> ' + (bkgt_document_ajax.strings.editing_now || 'Redigerar nu'));
                } else {
                    collaborationStatus.html('<span class="dashicons dashicons-edit"></span> ' + (bkgt_document_ajax.strings.editing_individual || 'Redigerar enskilt'));
                }
            }, 1000);
        });
    }

    // Modal handling
    $('#bkgt-upload-modal-trigger').on('click', function() {
        $('#bkgt-upload-modal').show();
    });

    $('.bkgt-modal-close, #bkgt-cancel-upload, .bkgt-modal-overlay').on('click', function() {
        $('#bkgt-upload-modal').hide();
        $('#bkgt-upload-form')[0].reset();
    });

    // Form submission
    $('#bkgt-submit-upload').on('click', function() {
        var form = $('#bkgt-upload-form')[0];
        var formData = new FormData(form);

        formData.append('action', 'bkgt_create_document');
        formData.append('nonce', bkgt_document_ajax.nonce);

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        var fileInput = $('#bkgt-doc-file')[0];
        if (!fileInput.files[0]) {
            alert(bkgt_document_ajax.strings.upload_error);
            return;
        }

        $(this).prop('disabled', true).text(bkgt_document_ajax.strings.uploading);

        $.ajax({
            url: bkgt_document_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#bkgt-submit-upload').prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> ' + bkgt_document_ajax.strings.upload_success.split('!')[0]);

                if (response.success) {
                    alert(response.data.message);
                    $('#bkgt-upload-modal').hide();
                    $('#bkgt-upload-form')[0].reset();

                    // Redirect to edit page or reload dashboard
                    if (response.data.edit_url) {
                        window.location.href = response.data.edit_url;
                    } else {
                        location.reload();
                    }
                } else {
                    alert(response.data || bkgt_document_ajax.strings.upload_error);
                    $('#bkgt-submit-upload').html('<span class="dashicons dashicons-upload"></span> Ladda upp');
                }
            },
            error: function() {
                $('#bkgt-submit-upload').prop('disabled', false).html('<span class="dashicons dashicons-upload"></span> Ladda upp');
                alert(bkgt_document_ajax.strings.upload_error);
            }
        });
    });

    // Update word count
    function updateWordCount() {
        if (window.bkgtMonacoEditor) {
            var content = window.bkgtMonacoEditor.getValue();
            var words = content.trim().split(/\s+/).filter(function(word) {
                return word.length > 0;
            }).length;

            $('.bkgt-word-count').text(words + ' ' + (words === 1 ? 'ord' : 'ord'));
        }
    }

})(jQuery);