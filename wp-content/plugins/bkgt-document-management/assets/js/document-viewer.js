/**
 * Document Viewer JavaScript
 *
 * @package BKGT_Document_Management
 * @since 1.0.1
 */

(function($) {
    'use strict';

    class BKGTDocumentViewer {
        constructor(container) {
            this.container = $(container);
            this.documentId = this.container.data('document-id');
            this.canvas = this.container.find('.bkgt-document-canvas');
            this.loadingOverlay = this.container.find('.bkgt-loading-overlay');
            this.pdfDoc = null;
            this.currentPage = 1;
            this.totalPages = 1;
            this.scale = 1.0;
            this.rendering = false;

            this.init();
        }

        init() {
            this.bindEvents();
            this.loadDocument();
        }

        bindEvents() {
            const self = this;

            // Zoom controls
            this.container.find('.bkgt-zoom-in').on('click', function() {
                self.zoomIn();
            });

            this.container.find('.bkgt-zoom-out').on('click', function() {
                self.zoomOut();
            });

            // Download button
            this.container.find('.bkgt-download').on('click', function() {
                self.downloadDocument();
            });

            // PDF navigation
            this.container.find('.bkgt-prev-page').on('click', function() {
                self.prevPage();
            });

            this.container.find('.bkgt-next-page').on('click', function() {
                self.nextPage();
            });
        }

        loadDocument() {
            const self = this;
            this.showLoading();

            $.ajax({
                url: bkgtDocViewer.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_get_document_viewer',
                    document_id: this.documentId,
                    nonce: bkgtDocViewer.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.renderDocument(response.data);
                    } else {
                        self.showError(response.data || bkgtDocViewer.strings.error);
                    }
                },
                error: function() {
                    self.showError(bkgtDocViewer.strings.error);
                }
            });
        }

        renderDocument(data) {
            this.hideLoading();

            // Adjust toolbar based on document type
            this.adjustToolbarForDocumentType(data.file_type);

            if (data.file_type === 'application/pdf') {
                this.renderPDF(data.file_url);
            } else if (this.isOfficeDocument(data.file_type)) {
                this.renderOfficeDocument(data.file_url, data.file_type);
            } else {
                this.showError('Dokumenttypen stöds inte än. Ladda ner för att visa.');
            }
        }

        renderPDF(url) {
            const self = this;

            // Configure PDF.js worker
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

            // Load PDF document
            pdfjsLib.getDocument(url).promise.then(function(pdf) {
                self.pdfDoc = pdf;
                self.totalPages = pdf.numPages;
                self.updateNavigation();
                self.renderPage(self.currentPage);
            }).catch(function(error) {
                console.error('Error loading PDF:', error);
                self.showError('Kunde inte ladda PDF-dokumentet.');
            });
        }

        renderPage(pageNum) {
            if (this.rendering) return;
            this.rendering = true;

            const self = this;
            this.pdfDoc.getPage(pageNum).then(function(page) {
                const viewport = page.getViewport({ scale: self.scale });
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');

                canvas.height = viewport.height;
                canvas.width = viewport.width;

                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };

                page.render(renderContext).promise.then(function() {
                    self.canvas.empty().append(canvas);
                    self.rendering = false;
                    self.updateZoomLevel();
                });
            });
        }

        renderOfficeDocument(url, mimeType) {
            const viewerUrl = this.getOfficeViewerUrl(url, mimeType);

            if (viewerUrl) {
                // Use Microsoft Office Online viewer
                this.canvas.html(`
                    <div class="bkgt-office-viewer">
                        <iframe src="${viewerUrl}"
                                width="100%"
                                height="600"
                                frameborder="0"
                                allowfullscreen>
                            <p>Din webbläsare stöder inte iframes.
                               <a href="${url}" target="_blank">Klicka här för att visa dokumentet</a>
                            </p>
                        </iframe>
                    </div>
                `);
            } else {
                // Fallback to download
                this.canvas.html(`
                    <div class="bkgt-office-placeholder">
                        <div class="dashicons dashicons-media-document"></div>
                        <h3>Office-dokument</h3>
                        <p>Detta Office-dokument kan inte visas inline. Ladda ner för att öppna i din favoritapp.</p>
                        <a href="${url}" class="button button-primary" download>Ladda ner dokument</a>
                    </div>
                `);
            }
        }

        adjustToolbarForDocumentType(fileType) {
            const zoomControls = this.container.find('.bkgt-zoom-out, .bkgt-zoom-in, .bkgt-zoom-level');
            const pdfNavigation = this.container.find('.bkgt-pdf-navigation');

            if (fileType === 'application/pdf') {
                // Show PDF-specific controls
                zoomControls.show();
                pdfNavigation.hide(); // Will be shown when PDF loads
            } else {
                // Hide PDF-specific controls for other document types
                zoomControls.hide();
                pdfNavigation.hide();
            }
        }

        isOfficeDocument(mimeType) {
            const officeTypes = [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ];
            return officeTypes.includes(mimeType);
        }

        zoomIn() {
            this.scale = Math.min(this.scale * 1.2, 3.0);
            if (this.pdfDoc) {
                this.renderPage(this.currentPage);
            }
        }

        zoomOut() {
            this.scale = Math.max(this.scale / 1.2, 0.5);
            if (this.pdfDoc) {
                this.renderPage(this.currentPage);
            }
        }

        updateZoomLevel() {
            const percent = Math.round(this.scale * 100);
            this.container.find('.bkgt-zoom-level').text(percent + '%');
        }

        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.renderPage(this.currentPage);
                this.updateNavigation();
            }
        }

        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.renderPage(this.currentPage);
                this.updateNavigation();
            }
        }

        updateNavigation() {
            this.container.find('.bkgt-pdf-navigation').show();
            this.container.find('.bkgt-current-page').text(this.currentPage);
            this.container.find('.bkgt-total-pages').text(this.totalPages);

            this.container.find('.bkgt-prev-page').prop('disabled', this.currentPage <= 1);
            this.container.find('.bkgt-next-page').prop('disabled', this.currentPage >= this.totalPages);
        }

        downloadDocument() {
            // Get the file URL and trigger download
            const self = this;
            $.ajax({
                url: bkgtDocViewer.ajax_url,
                type: 'POST',
                data: {
                    action: 'bkgt_get_document_content',
                    document_id: this.documentId,
                    nonce: bkgtDocViewer.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const link = document.createElement('a');
                        link.href = response.data.file_url;
                        link.download = '';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                }
            });
        }

        showLoading() {
            this.loadingOverlay.show();
        }

        hideLoading() {
            this.loadingOverlay.hide();
        }

        showError(message) {
            this.hideLoading();
            this.canvas.html(`
                <div class="bkgt-viewer-error">
                    <span class="dashicons dashicons-warning"></span>
                    <h3>Fel vid laddning</h3>
                    <p>${message}</p>
                </div>
            `);
        }
    }

    // Initialize viewers on document ready
    $(document).ready(function() {
        $('.bkgt-document-viewer-container').each(function() {
            new BKGTDocumentViewer(this);
        });
    });

})(jQuery);