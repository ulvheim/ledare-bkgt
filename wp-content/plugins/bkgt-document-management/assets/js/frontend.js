/**
 * Frontend JavaScript for BKGT Document Management - Enhanced Version
 * Full-featured document management interface with version control, export, and advanced search
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

(function($) {
    'use strict';

    var ajaxurl = bkgtDocFrontend.ajax_url;
    var nonce = bkgtDocFrontend.nonce;
    var currentDocumentId = null;

    $(document).ready(function() {
        if ($('.bkgt-document-frontend-dashboard').length) {
            initializeDashboard();
        }
    });

    /**
     * Initialize dashboard
     */
    function initializeDashboard() {
        // Load initial data
        loadUserDocuments();
        loadTemplates();

        // Tab switching
        $('.bkgt-doc-nav-item').on('click', function(e) {
            e.preventDefault();
            switchTab($(this).data('tab'));
        });

        // Detail modal tabs
        $(document).on('click', '.bkgt-detail-tab-btn', function(e) {
            e.preventDefault();
            switchDetailTab($(this).data('tab'));
        });

        // Modal close buttons
        $(document).on('click', '.bkgt-doc-modal-close', function(e) {
            e.preventDefault();
            $(this).closest('.bkgt-doc-modal').removeClass('active');
        });

        // Create document
        $('.bkgt-create-document').on('click', function(e) {
            e.preventDefault();
            console.log('Create document button clicked');
            openDocumentEditor();
        });

        // Create from template
        $('.bkgt-create-from-template').on('click', function(e) {
            e.preventDefault();
            openTemplateModal();
        });

        // Template selection
        $(document).on('change', '#template-select', function() {
            var templateId = $(this).val();
            if (templateId) {
                loadTemplateVariables(templateId);
            } else {
                $('#template-variables').html('');
            }
        });

        // Template create submit
        $('#template-create-submit').on('click', function(e) {
            e.preventDefault();
            createFromTemplate();
        });

        // Close modals
        $('#template-modal-close, #detail-close, #edit-cancel, #document-editor-cancel').on('click', function(e) {
            e.preventDefault();
            $(this).closest('.bkgt-doc-modal').removeClass('active');
        });

        // Document editor save
        $('#document-editor-save').on('click', function(e) {
            e.preventDefault();
            saveNewDocument();
        });

        // Document actions
        $(document).on('click', '.bkgt-doc-view', viewDocument);
        $(document).on('click', '.bkgt-doc-edit-btn', editDocument);
        $(document).on('click', '#detail-edit', editFromDetail);
        $(document).on('click', '#detail-delete', deleteDocument);
        $(document).on('click', '#edit-save', saveDocument);

        // Document list sorting
        $('.bkgt-doc-sort').on('change', function() {
            loadUserDocuments();
        });

        // Advanced search
        $('.bkgt-search-submit').on('click', performAdvancedSearch);

        // Search filters
        $(document).on('keyup', '.bkgt-doc-search', function() {
            clearTimeout($(this).data('timer'));
            var timer = setTimeout(function() {
                loadUserDocuments();
            }, 500);
            $(this).data('timer', timer);
        });
    }

    /**
     * Switch main tabs
     */
    function switchTab(tabName) {
        $('.bkgt-doc-nav-item').removeClass('active');
        $('.bkgt-doc-tab').removeClass('active');
        $('.bkgt-doc-nav-item[data-tab="' + tabName + '"]').addClass('active');
        $('#' + tabName).addClass('active');

        if (tabName === 'my-documents') {
            loadUserDocuments();
        } else if (tabName === 'templates') {
            loadTemplates();
        } else if (tabName === 'search') {
            $('#search-results').html('');
        }
    }

    /**
     * Switch detail modal tabs
     */
    function switchDetailTab(tabName) {
        $('.bkgt-detail-tab-btn').removeClass('active');
        $('.bkgt-detail-pane').removeClass('active');
        $('.bkgt-detail-tab-btn[data-tab="' + tabName + '"]').addClass('active');
        $('.bkgt-detail-pane[data-pane="' + tabName + '"]').addClass('active');

        if (tabName === 'versions') {
            loadDocumentVersions(currentDocumentId);
        } else if (tabName === 'sharing') {
            loadDocumentSharing(currentDocumentId);
        } else if (tabName === 'export') {
            displayExportOptions(currentDocumentId);
        }
    }

    /**
     * Load user's documents
     */
    function loadUserDocuments() {
        var searchQuery = $('.bkgt-doc-search').val();
        var sortBy = $('.bkgt-doc-sort').val() || 'date_desc';

        console.log('BKGT DM: Fetching documents', {
            nonce: nonce,
            ajax_url: ajaxurl,
            search: searchQuery,
            sort: sortBy
        });

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bkgt_get_user_documents',
                nonce: nonce,
                search: searchQuery,
                sort: sortBy
            },
            beforeSend: function() {
                console.log('BKGT DM: Sending AJAX request with nonce:', nonce);
            },
            success: function(response) {
                console.log('BKGT DM: Documents loaded successfully:', response);
                if (response.success) {
                    displayDocuments(response.data.documents);
                } else {
                    console.error('BKGT DM: Server returned error:', response);
                    showError(__('Misslyckades att ladda dokument') + ': ' + (response.data || 'Okänt fel'));
                }
            },
            error: function(xhr, status, error) {
                console.error('BKGT DM: AJAX Error', {
                    status: status,
                    statusCode: xhr.status,
                    error: error,
                    response: xhr.responseText
                });
                showError(__('Misslyckades att ladda dokument') + ' (' + status + ')');
            }
        });
    }

    /**
     * Display documents in list
     */
    function displayDocuments(documents) {
        var $list = $('#my-documents-list');
        $list.html('');

        if (documents.length === 0) {
            $list.html('<div class="bkgt-doc-empty"><p>Inga dokument hittade</p></div>');
            return;
        }

        var html = '<div class="bkgt-doc-item-list">';
        documents.forEach(function(doc) {
            html += '<div class="bkgt-doc-item" data-doc-id="' + doc.id + '">' +
                '<div class="bkgt-doc-item-header">' +
                '  <h4 class="bkgt-doc-item-title">' +
                '    <a href="#" class="bkgt-doc-view" data-doc-id="' + doc.id + '">' + escapeHtml(doc.title) + '</a>' +
                '  </h4>' +
                '</div>' +
                '<div class="bkgt-doc-item-meta">' +
                '  <span class="bkgt-doc-item-date">' +
                '    <span class="dashicons dashicons-calendar-alt"></span> ' + doc.date_formatted +
                '  </span>' +
                (doc.template_source ? '  <span class="bkgt-doc-item-template">' +
                    '    <span class="dashicons dashicons-admin-page"></span> ' + doc.template_source +
                    '  </span>' : '') +
                '</div>' +
                '<div class="bkgt-doc-item-actions">' +
                '  <a href="#" class="button button-small bkgt-doc-view" data-doc-id="' + doc.id + '">' +
                '    <span class="dashicons dashicons-visibility"></span> Visa' +
                '  </a>' +
                '  <a href="#" class="button button-small bkgt-doc-edit-btn" data-doc-id="' + doc.id + '">' +
                '    <span class="dashicons dashicons-edit"></span> Redigera' +
                '  </a>' +
                '  <a href="#" class="button button-small bkgt-button-danger bkgt-doc-delete" data-doc-id="' + doc.id + '">' +
                '    <span class="dashicons dashicons-trash"></span> Ta bort' +
                '  </a>' +
                '</div>' +
                '</div>';
        });
        html += '</div>';
        $list.html(html);
    }

    /**
     * Load templates
     */
    function loadTemplates() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bkgt_get_templates',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    displayTemplates(response.data.templates);
                    populateTemplateSelect(response.data.templates);
                }
            }
        });
    }

    /**
     * Display templates
     */
    function displayTemplates(templates) {
        var $list = $('#templates-list');
        $list.html('');

        if (templates.length === 0) {
            $list.html('<p>Inga mallar tillgängliga</p>');
            return;
        }

        var html = '<div class="bkgt-templates-grid">';
        templates.forEach(function(template) {
            html += '<div class="bkgt-template-card">' +
                '<div class="bkgt-template-icon">' +
                '  <span class="dashicons dashicons-admin-page"></span>' +
                '</div>' +
                '<h4 class="bkgt-template-name">' + escapeHtml(template.name) + '</h4>' +
                '<p class="bkgt-template-description">' + escapeHtml(template.description) + '</p>' +
                '<button class="button button-primary bkgt-template-use" data-template-id="' + template.id + '">' +
                '  Använd denna mall' +
                '</button>' +
                '</div>';
        });
        html += '</div>';
        $list.html(html);

        // Template use handler
        $('.bkgt-template-use').on('click', function(e) {
            e.preventDefault();
            var templateId = $(this).data('template-id');
            $('#template-select').val(templateId).change();
            $('#template-select').closest('.bkgt-doc-modal').addClass('active');
        });
    }

    /**
     * Populate template select dropdown
     */
    function populateTemplateSelect(templates) {
        var $select = $('#template-select');
        var currentValue = $select.val();

        $select.find('option:not(:first)').remove();
        templates.forEach(function(template) {
            $select.append('<option value="' + template.id + '">' + escapeHtml(template.name) + '</option>');
        });

        if (currentValue) {
            $select.val(currentValue);
        }
    }

    /**
     * Load template variables
     */
    function loadTemplateVariables(templateId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bkgt_get_templates',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    var templates = response.data.templates;
                    var template = templates.find(function(t) {
                        return t.id === templateId;
                    });

                    if (template && template.variables) {
                        var html = '';
                        template.variables.forEach(function(variable) {
                            html += '<div class="bkgt-form-group">' +
                                '<label for="var-' + variable.name + '">' + escapeHtml(variable.label) + '</label>' +
                                '<input type="text" id="var-' + variable.name + '" class="bkgt-template-var" name="' + variable.name + '" placeholder="' + escapeHtml(variable.label) + '">' +
                                '</div>';
                        });
                        $('#template-variables').html(html);
                    }
                }
            }
        });
    }

    /**
     * Create document from template
     */
    function createFromTemplate() {
        var templateId = $('#template-select').val();
        var title = $('#document-title').val();

        if (!templateId || !title) {
            showError(__('Välj mall och dokumenttitel'));
            return;
        }

        var variables = {};
        $('.bkgt-template-var').each(function() {
            variables[$(this).attr('name')] = $(this).val();
        });

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bkgt_create_from_template',
                nonce: nonce,
                template_id: templateId,
                document_title: title,
                variables: variables
            },
            success: function(response) {
                if (response.success) {
                    showSuccess(__('Dokument skapat framgångsrikt'));
                    $('#template-modal').removeClass('active');
                    $('#template-create-form')[0].reset();
                    $('#template-variables').html('');
                    switchTab('my-documents');
                } else {
                    showError(response.data || __('Misslyckades att skapa dokument'));
                }
            },
            error: function() {
                showError(__('Misslyckades att skapa dokument'));
            }
        });
    }

    /**
     * Open template modal
     */
    function openTemplateModal() {
        $('#template-modal').addClass('active');
        $('#template-select').focus();
    }

    /**
     * Open document editor
     */
    function openDocumentEditor() {
        console.log('Opening document editor modal');
        $('#document-editor-modal').addClass('active');
        $('#document-editor-title').focus();
    }

    /**
     * View document
     */
    function viewDocument(e) {
        e.preventDefault();
        var docId = $(this).data('doc-id');
        currentDocumentId = docId;

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bkgt_get_document',
                nonce: nonce,
                post_id: docId
            },
            success: function(response) {
                if (response.success) {
                    displayDocumentDetail(response.data);
                    $('#document-detail-modal').addClass('active');
                } else {
                    showError(response.data || __('Misslyckades att ladda dokument'));
                }
            }
        });
    }

    /**
     * Display document detail
     */
    function displayDocumentDetail(doc) {
        $('#detail-title').text(doc.title);
        $('#detail-content').html(
            '<div class="bkgt-doc-content-display">' +
            '<div class="bkgt-doc-meta-info">' +
            '  <p><strong>Datum:</strong> ' + doc.date + '</p>' +
            '  <p><strong>Författare:</strong> ' + doc.author + '</p>' +
            '</div>' +
            '<div class="bkgt-doc-content-body">' +
            escapeHtml(doc.content).replace(/\n/g, '<br>') +
            '</div>' +
            '</div>'
        );
    }

    /**
     * Load document versions
     */
    function loadDocumentVersions(docId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bkgt_get_document_versions',
                nonce: nonce,
                post_id: docId
            },
            success: function(response) {
                if (response.success) {
                    displayVersions(response.data.versions);
                }
            }
        });
    }

    /**
     * Display versions
     */
    function displayVersions(versions) {
        var html = '<div class="bkgt-versions-list">';

        if (versions.length === 0) {
            html += '<p>Inga tidigare versioner finns</p>';
        } else {
            versions.forEach(function(version, index) {
                var isCurrent = version.current || false;
                html += '<div class="bkgt-version-item' + (isCurrent ? ' current' : '') + '">' +
                    '<div class="bkgt-version-header">' +
                    '<h5>' + (isCurrent ? '⭐ Nuvarande version' : 'Version ' + (index + 1)) + '</h5>' +
                    '</div>' +
                    '<div class="bkgt-version-info">' +
                    '  <p><strong>Datum:</strong> ' + version.date + '</p>' +
                    '  <p><strong>Författare:</strong> ' + version.author + '</p>' +
                    '  <p><strong>Sammandrag:</strong> ' + escapeHtml(version.excerpt) + '...</p>' +
                    '</div>' +
                    (isCurrent ? '' : '<div class="bkgt-version-actions">' +
                        '<button class="button button-small bkgt-version-restore" data-version-id="' + version.id + '">' +
                        '  <span class="dashicons dashicons-undo"></span> Återställ denna version' +
                        '</button>' +
                        '</div>') +
                    '</div>';
            });
        }

        html += '</div>';
        $('#detail-versions').html(html);

        // Restore button handler
        $('.bkgt-version-restore').on('click', function(e) {
            e.preventDefault();
            if (confirm('Är du säker på att du vill återställa denna version?')) {
                restoreVersion($(this).data('version-id'));
            }
        });
    }

    /**
     * Restore version
     */
    function restoreVersion(versionId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bkgt_restore_document_version',
                nonce: nonce,
                version_id: versionId
            },
            success: function(response) {
                if (response.success) {
                    showSuccess(__('Version återställd framgångsrikt'));
                    loadDocumentVersions(currentDocumentId);
                } else {
                    showError(response.data || __('Misslyckades att återställa version'));
                }
            }
        });
    }

    /**
     * Load document sharing
     */
    function loadDocumentSharing(docId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bkgt_get_document_sharing',
                nonce: nonce,
                post_id: docId
            },
            success: function(response) {
                if (response.success) {
                    displaySharing(response.data.shares);
                }
            }
        });
    }

    /**
     * Display sharing options
     */
    function displaySharing(shares) {
        var html = '<div class="bkgt-sharing-container">' +
            '<div class="bkgt-sharing-list">' +
            '<h4>Delad med</h4>';

        if (shares.length === 0) {
            html += '<p class="bkgt-sharing-empty">Dokumentet är inte delat med någon</p>';
        } else {
            shares.forEach(function(share) {
                html += '<div class="bkgt-sharing-item">' +
                    '<div class="bkgt-sharing-user">' +
                    '  <strong>' + escapeHtml(share.name) + '</strong>' +
                    '  <small>' + escapeHtml(share.email) + '</small>' +
                    '</div>' +
                    '<div class="bkgt-sharing-permission">' +
                    '  <span class="badge badge-' + share.permission + '">' + (share.permission === 'edit' ? 'Kan redigera' : 'Kan se') + '</span>' +
                    '</div>' +
                    '<button class="button button-small bkgt-button-danger bkgt-sharing-remove" data-user-id="' + share.user_id + '">' +
                    '  Ta bort' +
                    '</button>' +
                    '</div>';
            });
        }

        html += '</div>' +
            '<div class="bkgt-sharing-add">' +
            '<h4>Dela med användare</h4>' +
            '<p class="bkgt-sharing-help">Denna funktion är avancerad och kräver direktkonfiguration i WordPress Admin.</p>' +
            '</div>' +
            '</div>';

        $('#detail-sharing').html(html);

        $('.bkgt-sharing-remove').on('click', function(e) {
            e.preventDefault();
            if (confirm('Vill du sluta dela detta dokument med denna användare?')) {
                removeSharing(currentDocumentId, $(this).data('user-id'));
            }
        });
    }

    /**
     * Display export options
     */
    function displayExportOptions(docId) {
        var html = '<div class="bkgt-export-container">' +
            '<h4>Exportera dokument</h4>' +
            '<p>Välj format för att ladda ner ditt dokument:</p>' +
            '<div class="bkgt-export-options">' +
            '<button class="button button-large bkgt-export-btn" data-format="txt">' +
            '  <span class="dashicons dashicons-media-text"></span><br>Textfil (.txt)' +
            '</button>' +
            '<button class="button button-large bkgt-export-btn" data-format="md">' +
            '  <span class="dashicons dashicons-editor-code"></span><br>Markdown (.md)' +
            '</button>' +
            '<button class="button button-large bkgt-export-btn" data-format="html">' +
            '  <span class="dashicons dashicons-text"></span><br>HTML (.html)' +
            '</button>' +
            '</div>' +
            '</div>';

        $('#detail-export').html(html);

        $('.bkgt-export-btn').on('click', function(e) {
            e.preventDefault();
            exportDocument(docId, $(this).data('format'));
        });
    }

    /**
     * Export document
     */
    function exportDocument(docId, format) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bkgt_export_document_format',
                nonce: nonce,
                post_id: docId,
                format: format
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.download_url;
                }
            }
        });
    }

    /**
     * Edit document
     */
    function editDocument(e) {
        e.preventDefault();
        var docId = $(this).data('doc-id');
        currentDocumentId = docId;

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bkgt_get_document',
                nonce: nonce,
                post_id: docId
            },
            success: function(response) {
                if (response.success) {
                    var doc = response.data;
                    $('#edit-post-id').val(docId);
                    $('#edit-title').val(doc.title);
                    $('#edit-content').val(doc.content);
                    $('#edit-modal').addClass('active');
                }
            }
        });
    }

    /**
     * Edit from detail modal
     */
    function editFromDetail(e) {
        e.preventDefault();
        $('#document-detail-modal').removeClass('active');
        setTimeout(function() {
            editDocument({ preventDefault: function() {}, data: { 'doc-id': currentDocumentId } });
        }, 200);
    }

    /**
     * Save document
     */
    function saveDocument(e) {
        e.preventDefault();
        var docId = $('#edit-post-id').val();
        var title = $('#edit-title').val();
        var content = $('#edit-content').val();

        if (!title || !content) {
            showError(__('Titel och innehål är obligatoriska'));
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text(__('Sparar...'));

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bkgt_edit_user_document',
                nonce: nonce,
                post_id: docId,
                title: title,
                content: content
            },
            success: function(response) {
                $btn.prop('disabled', false).text(__('Spara ändringar'));

                if (response.success) {
                    showSuccess(__('Dokumentet har uppdaterats'));
                    $('#edit-modal').removeClass('active');
                    switchTab('my-documents');
                } else {
                    showError(response.data || __('Misslyckades att spara ändringar'));
                }
            },
            error: function() {
                $btn.prop('disabled', false).text(__('Spara ändringar'));
                showError(__('Misslyckades att spara ändringar'));
            }
        });
    }

    /**
     * Save new document
     */
    function saveNewDocument() {
        var title = $('#document-editor-title').val();
        var content = $('#document-editor-content').val();

        if (!title || !content) {
            showError(__('Titel och innehål är obligatoriska'));
            return;
        }

        var $btn = $('#document-editor-save');
        $btn.prop('disabled', true).text(__('Sparar...'));

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bkgt_create_user_document',
                nonce: nonce,
                title: title,
                content: content
            },
            success: function(response) {
                $btn.prop('disabled', false).text(__('Spara dokument'));

                if (response.success) {
                    showSuccess(__('Dokument skapat framgångsrikt'));
                    $('#document-editor-modal').removeClass('active');
                    $('#document-editor-form')[0].reset();
                    switchTab('my-documents');
                } else {
                    showError(response.data || __('Misslyckades att skapa dokument'));
                }
            },
            error: function() {
                $btn.prop('disabled', false).text(__('Spara dokument'));
                showError(__('Misslyckades att skapa dokument'));
            }
        });
    }

    /**
     * Delete document
     */
    function deleteDocument(e) {
        e.preventDefault();

        if (!confirm(__('Är du säker på att du vill ta bort detta dokument? Denna åtgärd kan inte ångras.'))) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bkgt_delete_user_document',
                nonce: nonce,
                post_id: currentDocumentId
            },
            success: function(response) {
                if (response.success) {
                    showSuccess(__('Dokumentet har tagits bort'));
                    $('#document-detail-modal').removeClass('active');
                    switchTab('my-documents');
                } else {
                    showError(response.data || __('Misslyckades att ta bort dokument'));
                }
            }
        });
    }

    /**
     * Perform advanced search
     */
    function performAdvancedSearch(e) {
        e.preventDefault();

        var searchQuery = $('#search-query').val();
        var dateFrom = $('#search-date-from').val();
        var dateTo = $('#search-date-to').val();
        var templateFilter = $('#search-template').val();
        var sortBy = $('#search-sort').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bkgt_search_documents_advanced',
                nonce: nonce,
                search: searchQuery,
                date_from: dateFrom,
                date_to: dateTo,
                template: templateFilter,
                sort: sortBy
            },
            success: function(response) {
                if (response.success) {
                    displaySearchResults(response.data.documents);
                }
            }
        });
    }

    /**
     * Display search results
     */
    function displaySearchResults(documents) {
        var $results = $('#search-results');
        $results.html('');

        if (documents.length === 0) {
            $results.html('<div class="bkgt-search-empty"><p>Inga dokument hittade som matchar dina sökkriterier</p></div>');
            return;
        }

        var html = '<div class="bkgt-search-count">Hittade ' + documents.length + ' dokument</div>' +
            '<div class="bkgt-doc-item-list">';

        documents.forEach(function(doc) {
            html += '<div class="bkgt-doc-item" data-doc-id="' + doc.id + '">' +
                '<div class="bkgt-doc-item-header">' +
                '  <h4 class="bkgt-doc-item-title">' +
                '    <a href="#" class="bkgt-doc-view" data-doc-id="' + doc.id + '">' + escapeHtml(doc.title) + '</a>' +
                '  </h4>' +
                '</div>' +
                '<div class="bkgt-doc-item-meta">' +
                '  <span class="bkgt-doc-item-date">' + doc.date_formatted + '</span>' +
                (doc.template ? '  <span class="bkgt-doc-item-template">' + doc.template + '</span>' : '') +
                '</div>' +
                '<div class="bkgt-doc-item-actions">' +
                '  <a href="#" class="button button-small bkgt-doc-view" data-doc-id="' + doc.id + '">Visa</a>' +
                '  <a href="#" class="button button-small bkgt-doc-edit-btn" data-doc-id="' + doc.id + '">Redigera</a>' +
                '</div>' +
                '</div>';
        });

        html += '</div>';
        $results.html(html);
    }

    /**
     * Remove sharing
     */
    function removeSharing(docId, userId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bkgt_update_document_sharing',
                nonce: nonce,
                post_id: docId,
                share_action: 'remove',
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    showSuccess(__('Delning uppdaterad'));
                    loadDocumentSharing(docId);
                }
            }
        });
    }

    /**
     * Helper: Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Helper: Show success message
     */
    function showSuccess(message) {
        showNotification(message, 'success');
    }

    /**
     * Helper: Show error message
     */
    function showError(message) {
        showNotification(message, 'error');
    }

    /**
     * Helper: Show notification
     */
    function showNotification(message, type) {
        var className = 'bkgt-notification bkgt-notification-' + type;
        var $notification = $('<div class="' + className + '">' + escapeHtml(message) + '</div>');

        $('body').append($notification);
        $notification.fadeIn();

        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 4000);
    }

    /**
     * Helper: Translation wrapper
     */
    function __(text) {
        return text; // In production, use proper i18n
    }

})(jQuery);
