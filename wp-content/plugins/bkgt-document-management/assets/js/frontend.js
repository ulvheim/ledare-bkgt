/**
 * Frontend JavaScript for BKGT Document Management
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Document grid/list view toggle
        $('.bkgt-view-toggle button').on('click', function(e) {
            e.preventDefault();

            var view = $(this).data('view');
            $('.bkgt-view-toggle button').removeClass('active');
            $(this).addClass('active');

            if (view === 'grid') {
                $('.bkgt-documents-grid').removeClass('list-view').addClass('grid-view');
            } else {
                $('.bkgt-documents-grid').removeClass('grid-view').addClass('list-view');
            }

            // Save preference
            localStorage.setItem('bkgt_document_view', view);
        });

        // Load saved view preference
        var savedView = localStorage.getItem('bkgt_document_view') || 'grid';
        $('.bkgt-view-toggle button[data-view="' + savedView + '"]').trigger('click');

        // Document sorting
        $('#bkgt-sort-documents').on('change', function() {
            var sortBy = $(this).val();
            var currentUrl = new URL(window.location);

            // Remove existing sort parameters
            currentUrl.searchParams.delete('orderby');

            // Add new sort parameter
            if (sortBy !== 'date_desc') {
                currentUrl.searchParams.set('orderby', sortBy);
            }

            window.location.href = currentUrl.toString();
        });

        // Download link tracking
        $('.bkgt-download-link').on('click', function(e) {
            var $link = $(this);
            var documentId = $link.closest('.bkgt-document-card').data('document-id');

            // Track download (optional analytics)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'document_download', {
                    'document_id': documentId,
                    'document_title': $link.closest('.bkgt-document-card').find('.bkgt-document-title a').text()
                });
            }
        });

        // Version restoration
        $(document).on('click', '.bkgt-restore-version', function(e) {
            e.preventDefault();

            if (!confirm('Är du säker på att du vill återställa denna version?')) {
                return;
            }

            var versionId = $(this).data('version-id');
            var $button = $(this);

            $button.prop('disabled', true).text('Återställer...');

            $.post(bkgtDocument.ajaxUrl, {
                action: 'bkgt_restore_version',
                nonce: bkgtDocument.nonce,
                version_id: versionId
            }, function(response) {
                $button.prop('disabled', false).text('Återställ');

                if (response.success) {
                    alert('Version återställd!');
                    location.reload();
                } else {
                    alert(response.data || 'Misslyckades att återställa version.');
                }
            });
        });

        // Share document
        $('.bkgt-share-document').on('click', function(e) {
            e.preventDefault();

            var documentId = $(this).data('document-id');
            var shareUrl = window.location.origin + '/wp-admin/admin-ajax.php?action=bkgt_share_document&document_id=' + documentId + '&nonce=' + bkgtDocument.nonce;

            // Copy to clipboard if supported
            if (navigator.clipboard) {
                navigator.clipboard.writeText(shareUrl).then(function() {
                    alert('Länk kopierad till urklipp!');
                });
            } else {
                // Fallback: show modal with link
                var modal = $('<div class="bkgt-modal"><div class="bkgt-modal-content"><div class="bkgt-modal-header"><h2>Dela dokument</h2><span class="bkgt-modal-close">&times;</span></div><div class="bkgt-modal-body"><p>Kopiera denna länk för att dela dokumentet:</p><input type="text" readonly value="' + shareUrl + '" style="width:100%;padding:8px;margin:10px 0;"><p><small>Denna länk kräver inloggning och rättigheter för att visa dokumentet.</small></p></div></div></div>');

                $('body').append(modal);
                modal.show();

                modal.find('input').on('click', function() {
                    $(this).select();
                });

                modal.find('.bkgt-modal-close').on('click', function() {
                    modal.remove();
                });

                $(document).on('click', function(e) {
                    if ($(e.target).hasClass('bkgt-modal')) {
                        modal.remove();
                    }
                });
            }
        });

        // Search functionality
        var searchTimeout;
        $('.bkgt-search-filter input[type="text"]').on('input', function() {
            clearTimeout(searchTimeout);
            var $input = $(this);
            var searchTerm = $input.val();

            searchTimeout = setTimeout(function() {
                if (searchTerm.length >= 3) {
                    // Perform live search
                    performSearch(searchTerm);
                } else if (searchTerm.length === 0) {
                    // Clear search
                    clearSearch();
                }
            }, 500);
        });

        function performSearch(searchTerm) {
            $('.bkgt-documents-grid').addClass('bkgt-loading');

            $.post(bkgtDocument.ajaxUrl, {
                action: 'bkgt_search_documents',
                nonce: bkgtDocument.nonce,
                search_term: searchTerm
            }, function(response) {
                $('.bkgt-documents-grid').removeClass('bkgt-loading');

                if (response.success) {
                    updateDocumentGrid(response.data);
                }
            });
        }

        function clearSearch() {
            $('.bkgt-documents-grid').addClass('bkgt-loading');

            $.post(bkgtDocument.ajaxUrl, {
                action: 'bkgt_get_documents',
                nonce: bkgtDocument.nonce
            }, function(response) {
                $('.bkgt-documents-grid').removeClass('bkgt-loading');

                if (response.success) {
                    updateDocumentGrid(response.data);
                }
            });
        }

        function updateDocumentGrid(documents) {
            var $grid = $('.bkgt-documents-grid');
            $grid.empty();

            if (documents.length === 0) {
                $grid.append('<div class="bkgt-no-documents"><div class="bkgt-no-documents-icon"><span class="dashicons dashicons-media-document"></span></div><h3>Inga dokument hittades</h3><p>Det finns inga dokument som matchar dina kriterier.</p></div>');
                return;
            }

            documents.forEach(function(doc) {
                var canView = doc.can_view ? 'accessible' : 'restricted';
                var canEdit = doc.can_edit ? '' : 'style="display:none;"';
                var canManage = doc.can_manage ? '' : 'style="display:none;"';

                var cardHtml = '<article class="bkgt-document-card ' + canView + '" data-document-id="' + doc.id + '">' +
                    '<div class="bkgt-document-icon">' + doc.icon + '</div>' +
                    '<div class="bkgt-document-info">' +
                        '<h3 class="bkgt-document-title">' +
                            (doc.can_view ? '<a href="' + doc.permalink + '">' + doc.title + '</a>' : doc.title) +
                        '</h3>' +
                        '<div class="bkgt-document-meta">' +
                            '<span class="bkgt-document-size">' + doc.size + '</span>' +
                            '<span class="bkgt-document-date">' + doc.date + '</span>' +
                        '</div>' +
                        '<div class="bkgt-document-categories">' + doc.categories + '</div>' +
                        '<div class="bkgt-document-actions">' +
                            (doc.can_view ? '<a href="' + doc.download_url + '" class="bkgt-button-secondary bkgt-download-link">Ladda ner</a>' : '') +
                            (doc.can_edit ? '<a href="' + doc.edit_url + '" class="bkgt-button-secondary">Redigera</a>' : '') +
                        '</div>' +
                        (doc.can_view ? '' : '<div class="bkgt-document-restricted"><span class="bkgt-restricted-notice">Begränsad åtkomst</span></div>') +
                    '</div>' +
                '</article>';

                $grid.append(cardHtml);
            });
        }

        // Category filter
        $('.bkgt-category-item a').on('click', function(e) {
            e.preventDefault();

            var categoryId = $(this).data('category-id') || 0;
            var $link = $(this);

            $('.bkgt-category-item a').removeClass('active');
            $link.addClass('active');

            $('.bkgt-documents-grid').addClass('bkgt-loading');

            $.post(bkgtDocument.ajaxUrl, {
                action: 'bkgt_filter_documents',
                nonce: bkgtDocument.nonce,
                category_id: categoryId
            }, function(response) {
                $('.bkgt-documents-grid').removeClass('bkgt-loading');

                if (response.success) {
                    updateDocumentGrid(response.data);
                }
            });
        });

        // Infinite scroll (optional enhancement)
        if ($('.bkgt-documents-grid').hasClass('infinite-scroll')) {
            var loading = false;
            var page = 1;

            $(window).on('scroll', function() {
                if (loading) return;

                if ($(window).scrollTop() + $(window).height() >= $(document).height() - 100) {
                    loading = true;
                    page++;

                    $.post(bkgtDocument.ajaxUrl, {
                        action: 'bkgt_load_more_documents',
                        nonce: bkgtDocument.nonce,
                        page: page
                    }, function(response) {
                        loading = false;

                        if (response.success && response.data.length > 0) {
                            response.data.forEach(function(doc) {
                                var canView = doc.can_view ? 'accessible' : 'restricted';
                                var cardHtml = '<article class="bkgt-document-card ' + canView + '" data-document-id="' + doc.id + '">' +
                                    // ... card HTML ...
                                '</article>';
                                $('.bkgt-documents-grid').append(cardHtml);
                            });
                        }
                    });
                }
            });
        }

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl+F for search focus
            if (e.ctrlKey && e.keyCode === 70) {
                e.preventDefault();
                $('.bkgt-search-filter input[type="text"]').focus();
            }
        });

        // Print document
        $('.bkgt-print-document').on('click', function(e) {
            e.preventDefault();
            window.print();
        });

        // Document preview (if supported)
        $('.bkgt-preview-document').on('click', function(e) {
            e.preventDefault();

            var documentId = $(this).data('document-id');
            var previewUrl = bkgtDocument.ajaxUrl + '?action=bkgt_preview_document&document_id=' + documentId + '&nonce=' + bkgtDocument.nonce;

            window.open(previewUrl, 'document_preview', 'width=800,height=600,scrollbars=yes');
        });

        // Document upload form handling
        $('#dms-upload-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $progress = $('#dms-upload-progress');
            var formData = new FormData(this);

            // Add nonce
            formData.append('action', 'bkgt_upload_document_frontend');
            formData.append('nonce', bkgtDocument.nonce);

            // Disable form
            $submitBtn.prop('disabled', true).text('Laddar upp...');
            $progress.show();

            $.ajax({
                url: bkgtDocument.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            var percentComplete = Math.round((e.loaded / e.total) * 100);
                            $progress.find('.progress-fill').css('width', percentComplete + '%');
                        }
                    });
                    return xhr;
                },
                success: function(response) {
                    $submitBtn.prop('disabled', false).text('Ladda upp dokument');
                    $progress.hide();

                    if (response.success) {
                        alert(response.data.message);
                        // Reset form
                        $form[0].reset();
                        // Redirect to manage tab to see the new document
                        window.location.href = window.location.pathname + '?tab=manage';
                    } else {
                        alert(response.data || 'Ett fel uppstod vid uppladdning.');
                    }
                },
                error: function() {
                    $submitBtn.prop('disabled', false).text('Ladda upp dokument');
                    $progress.hide();
                    alert('Ett fel uppstod vid uppladdning.');
                }
            });
        });

    });

})(jQuery);