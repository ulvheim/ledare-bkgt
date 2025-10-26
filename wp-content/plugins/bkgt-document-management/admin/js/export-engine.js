/**
 * Export Engine JavaScript
 *
 * Handles the frontend functionality for the advanced export engine
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

(function($) {
    'use strict';

    var BKGT_Export_Engine = {

        init: function() {
            this.bindEvents();
            this.loadDocuments();
            this.initProgressBar();
        },

        bindEvents: function() {
            var self = this;

            // Format selection
            $('.bkgt-format-checkboxes input').on('change', function() {
                self.updateSelectedFormats();
            });

            // Export actions
            $('#export-selected').on('click', function(e) {
                e.preventDefault();
                self.exportSelectedDocuments();
            });

            $('#preview-export').on('click', function(e) {
                e.preventDefault();
                self.previewExport();
            });

            // Document selection
            $('#select-all-docs').on('click', function(e) {
                e.preventDefault();
                self.selectAllDocuments();
            });

            $('#select-none-docs').on('click', function(e) {
                e.preventDefault();
                self.selectNoneDocuments();
            });

            $('#filter-recent').on('click', function(e) {
                e.preventDefault();
                self.filterRecentDocuments();
            });

            $('#doc-search').on('keyup', function() {
                self.searchDocuments();
            });

            // Cloud upload
            $('.bkgt-cloud-buttons button').on('click', function(e) {
                e.preventDefault();
                var provider = $(this).data('provider');
                self.uploadToCloud(provider);
            });

            // Modal handling
            $('.bkgt-modal-close').on('click', function() {
                self.closeModal();
            });

            $(document).on('click', function(e) {
                if ($(e.target).hasClass('bkgt-modal')) {
                    self.closeModal();
                }
            });

            // Export results
            $('#download-all').on('click', function(e) {
                e.preventDefault();
                self.downloadAllFiles();
            });

            $('#share-results').on('click', function(e) {
                e.preventDefault();
                self.shareResults();
            });
        },

        loadDocuments: function() {
            var self = this;

            $.ajax({
                url: bkgt_export.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_get_documents_for_export',
                    nonce: bkgt_export.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.renderDocumentList(response.data.documents);
                    } else {
                        self.showError(response.data.message || bkgt_export.strings.select_documents);
                    }
                },
                error: function() {
                    self.showError(bkgt_export.strings.select_documents);
                }
            });
        },

        renderDocumentList: function(documents) {
            var $list = $('#bkgt-document-list');
            var html = '';

            if (documents.length === 0) {
                html = '<p>' + bkgt_export.strings.no_documents_selected + '</p>';
            } else {
                html = '<div class="bkgt-document-items">';

                documents.forEach(function(doc) {
                    html += '<div class="bkgt-document-item" data-id="' + doc.ID + '">';
                    html += '<label>';
                    html += '<input type="checkbox" class="bkgt-doc-checkbox" value="' + doc.ID + '">';
                    html += '<span class="bkgt-doc-info">';
                    html += '<strong>' + doc.post_title + '</strong>';
                    html += '<span class="bkgt-doc-meta">';

                    var author = doc.author || 'Unknown';
                    var date = new Date(doc.post_date).toLocaleDateString('sv-SE');
                    var type = doc.type || 'Document';

                    html += type + ' | ' + author + ' | ' + date;
                    html += '</span>';
                    html += '</span>';
                    html += '</label>';
                    html += '</div>';
                });

                html += '</div>';
            }

            $list.html(html);
        },

        updateSelectedFormats: function() {
            var selected = [];
            $('.bkgt-format-checkboxes input:checked').each(function() {
                selected.push($(this).val());
            });

            // Update export button text
            var $exportBtn = $('#export-selected');
            if (selected.length > 0) {
                $exportBtn.prop('disabled', false);
                $exportBtn.text(bkgt_export.strings.export_selected + ' (' + selected.length + ')');
            } else {
                $exportBtn.prop('disabled', true);
                $exportBtn.text(bkgt_export.strings.export_selected);
            }
        },

        exportSelectedDocuments: function() {
            var self = this;
            var selectedDocs = [];
            var selectedFormats = [];

            $('.bkgt-doc-checkbox:checked').each(function() {
                selectedDocs.push($(this).val());
            });

            $('.bkgt-format-checkboxes input:checked').each(function() {
                selectedFormats.push($(this).attr('id').replace('export-', ''));
            });

            if (selectedDocs.length === 0) {
                self.showError(bkgt_export.strings.no_documents_selected);
                return;
            }

            if (selectedFormats.length === 0) {
                self.showError('Välj minst ett exportformat');
                return;
            }

            // Confirm batch export
            if (selectedDocs.length > 1) {
                var message = bkgt_export.strings.confirm_batch_export.replace('%d', selectedDocs.length);
                if (!confirm(message)) {
                    return;
                }
            }

            // Get export settings
            var settings = {
                quality: $('#export-quality').val(),
                orientation: $('#export-orientation').val(),
                include_headers: $('#include-headers').is(':checked'),
                brand_styling: $('#brand-styling').is(':checked')
            };

            self.startExport(selectedDocs, selectedFormats, settings);
        },

        startExport: function(documents, formats, settings) {
            var self = this;

            $('#bkgt-export-progress').show();
            $('#export-selected, #preview-export').prop('disabled', true);

            var totalOperations = documents.length * formats.length;
            var completedOperations = 0;
            var results = [];

            self.updateProgress(0, 'Förbereder export...');

            // Process documents sequentially to avoid server overload
            var processNext = function(index) {
                if (index >= documents.length) {
                    self.finishExport(results);
                    return;
                }

                var docId = documents[index];

                self.exportDocument(docId, formats, settings, function(docResults) {
                    results = results.concat(docResults);
                    completedOperations += formats.length;

                    var progress = Math.round((completedOperations / totalOperations) * 100);
                    var message = 'Exporterar dokument ' + (index + 1) + ' av ' + documents.length;
                    self.updateProgress(progress, message);

                    processNext(index + 1);
                });
            };

            processNext(0);
        },

        exportDocument: function(docId, formats, settings, callback) {
            var self = this;
            var results = [];

            $.ajax({
                url: bkgt_export.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_export_document',
                    nonce: bkgt_export.nonce,
                    document_id: docId,
                    formats: formats,
                    settings: settings
                },
                success: function(response) {
                    if (response.success) {
                        Object.keys(response.data.exports).forEach(function(format) {
                            results.push({
                                document_id: docId,
                                format: format,
                                file_url: response.data.exports[format].url,
                                file_name: response.data.exports[format].filename
                            });
                        });
                    }
                    callback(results);
                },
                error: function() {
                    callback(results);
                }
            });
        },

        finishExport: function(results) {
            var self = this;

            $('#bkgt-export-progress').hide();
            $('#export-selected, #preview-export').prop('disabled', false);

            if (results.length > 0) {
                self.showExportResults(results);
                self.showSuccess('Export slutförd! ' + results.length + ' filer genererade.');
            } else {
                self.showError(bkgt_export.strings.export_failed);
            }
        },

        showExportResults: function(results) {
            var $results = $('#bkgt-export-results');
            var $list = $('#export-files-list');

            var html = '<ul class="bkgt-exported-files">';
            results.forEach(function(result) {
                var formatLabel = result.format.toUpperCase();
                html += '<li class="bkgt-exported-file">';
                html += '<span class="bkgt-file-info">';
                html += '<strong>' + result.file_name + '</strong>';
                html += '<span class="bkgt-file-meta">' + formatLabel + '</span>';
                html += '</span>';
                html += '<span class="bkgt-file-actions">';
                html += '<a href="' + result.file_url + '" class="button button-small" download>Ladda ner</a>';
                html += '<button class="button button-small bkgt-share-file" data-url="' + result.file_url + '">Dela</button>';
                html += '</span>';
                html += '</li>';
            });
            html += '</ul>';

            $list.html(html);
            $results.show();

            // Scroll to results
            $('html, body').animate({
                scrollTop: $results.offset().top - 50
            }, 500);
        },

        previewExport: function() {
            var selectedDocs = [];
            $('.bkgt-doc-checkbox:checked').each(function() {
                selectedDocs.push($(this).val());
            });

            if (selectedDocs.length === 0) {
                this.showError(bkgt_export.strings.no_documents_selected);
                return;
            }

            if (selectedDocs.length > 1) {
                this.showError('Förhandsgranskning är endast tillgänglig för ett dokument åt gången');
                return;
            }

            this.showPreviewModal(selectedDocs[0]);
        },

        showPreviewModal: function(docId) {
            var self = this;
            var $modal = $('#bkgt-preview-modal');
            var $content = $('#bkgt-preview-content');

            $content.html('<p>Laddar förhandsgranskning...</p>');
            $modal.show();

            $.ajax({
                url: bkgt_export.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_preview_document',
                    nonce: bkgt_export.nonce,
                    document_id: docId
                },
                success: function(response) {
                    if (response.success) {
                        $content.html(response.data.preview);
                    } else {
                        $content.html('<p>Fel vid laddning av förhandsgranskning</p>');
                    }
                },
                error: function() {
                    $content.html('<p>Fel vid laddning av förhandsgranskning</p>');
                }
            });
        },

        uploadToCloud: function(provider) {
            var files = [];
            $('.bkgt-exported-files .bkgt-exported-file').each(function() {
                var $file = $(this);
                files.push({
                    url: $file.find('.bkgt-share-file').data('url'),
                    name: $file.find('strong').text()
                });
            });

            if (files.length === 0) {
                this.showError('Inga filer att ladda upp');
                return;
            }

            this.showCloudAuthModal(provider, files);
        },

        showCloudAuthModal: function(provider, files) {
            var $modal = $('#bkgt-cloud-auth-modal');
            var $content = $('#bkgt-cloud-auth-content');

            var providerName = provider === 'google' ? 'Google Drive' :
                             provider === 'microsoft' ? 'OneDrive' : 'Dropbox';

            var html = '<div class="bkgt-cloud-auth-form">';
            html += '<h3>Ladda upp till ' + providerName + '</h3>';
            html += '<p>' + files.length + ' filer kommer att laddas upp.</p>';

            if (provider === 'google') {
                html += '<p>Klicka på knappen nedan för att auktorisera åtkomst till Google Drive.</p>';
                html += '<button id="auth-google" class="button button-primary">Auktorisera Google Drive</button>';
            } else if (provider === 'microsoft') {
                html += '<p>Klicka på knappen nedan för att auktorisera åtkomst till OneDrive.</p>';
                html += '<button id="auth-microsoft" class="button button-primary">Auktorisera OneDrive</button>';
            } else if (provider === 'dropbox') {
                html += '<p>Klicka på knappen nedan för att auktorisera åtkomst till Dropbox.</p>';
                html += '<button id="auth-dropbox" class="button button-primary">Auktorisera Dropbox</button>';
            }

            html += '</div>';

            $content.html(html);
            $modal.show();

            // Bind auth button
            $('#auth-' + provider).on('click', function() {
                // In a real implementation, this would redirect to OAuth
                alert('OAuth-integration skulle implementeras här för ' + providerName);
            });
        },

        selectAllDocuments: function() {
            $('.bkgt-doc-checkbox').prop('checked', true);
            this.updateSelectedFormats();
        },

        selectNoneDocuments: function() {
            $('.bkgt-doc-checkbox').prop('checked', false);
            this.updateSelectedFormats();
        },

        filterRecentDocuments: function() {
            var sevenDaysAgo = new Date();
            sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);

            $('.bkgt-document-item').each(function() {
                var $item = $(this);
                var dateText = $item.find('.bkgt-doc-meta').text();
                var dateMatch = dateText.match(/(\d{4}-\d{2}-\d{2})/);

                if (dateMatch) {
                    var itemDate = new Date(dateMatch[1]);
                    if (itemDate >= sevenDaysAgo) {
                        $item.show();
                    } else {
                        $item.hide();
                    }
                }
            });
        },

        searchDocuments: function() {
            var searchTerm = $('#doc-search').val().toLowerCase();

            $('.bkgt-document-item').each(function() {
                var $item = $(this);
                var title = $item.find('strong').text().toLowerCase();
                var meta = $item.find('.bkgt-doc-meta').text().toLowerCase();

                if (title.indexOf(searchTerm) !== -1 || meta.indexOf(searchTerm) !== -1) {
                    $item.show();
                } else {
                    $item.hide();
                }
            });
        },

        initProgressBar: function() {
            $('#progress-fill').progressbar({
                value: 0
            });
        },

        updateProgress: function(percent, message) {
            $('#progress-fill').progressbar('value', percent);
            $('#progress-text').text(message);
        },

        downloadAllFiles: function() {
            $('.bkgt-exported-file a[download]').each(function() {
                var link = document.createElement('a');
                link.href = $(this).attr('href');
                link.download = $(this).attr('download') || '';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        },

        shareResults: function() {
            var urls = [];
            $('.bkgt-exported-file').each(function() {
                var url = $(this).find('.bkgt-share-file').data('url');
                if (url) urls.push(url);
            });

            if (urls.length > 0) {
                var shareText = 'Här är de exporterade dokumenten:\n\n' + urls.join('\n');
                navigator.clipboard.writeText(shareText).then(function() {
                    alert('Delningslänkar kopierade till urklipp!');
                });
            }
        },

        closeModal: function() {
            $('.bkgt-modal').hide();
        },

        showSuccess: function(message) {
            this.showNotice(message, 'success');
        },

        showError: function(message) {
            this.showNotice(message, 'error');
        },

        showNotice: function(message, type) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.bkgt-export-engine').prepend($notice);

            // Auto-remove after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, 5000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        BKGT_Export_Engine.init();
    });

})(jQuery);