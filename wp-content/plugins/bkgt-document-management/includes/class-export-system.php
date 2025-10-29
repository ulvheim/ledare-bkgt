<?php
/**
 * BKGT Document Management - Export System
 * Handles document export to various formats (DOCX, PDF, Excel/CSV)
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Export System Class
 */
class BKGT_DM_Export_System {

    /**
     * Supported export formats
     */
    private $supported_formats = array(
        'pdf' => 'PDF',
        'docx' => 'Word Document',
        'xlsx' => 'Excel Spreadsheet',
        'csv' => 'CSV File'
    );

    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize the export system
     */
    public function init() {
        // Add AJAX handlers for export operations
        add_action('wp_ajax_bkgt_export_document', array($this, 'ajax_export_document'));
        add_action('wp_ajax_bkgt_bulk_export', array($this, 'ajax_bulk_export'));
        add_action('wp_ajax_bkgt_get_export_formats', array($this, 'ajax_get_export_formats'));
    }

    /**
     * Get supported export formats
     */
    public function get_supported_formats() {
        return $this->supported_formats;
    }

    /**
     * Export document to specified format
     */
    public function export_document($document_id, $format, $options = array()) {
        // Get document data
        $document = $this->get_document_data($document_id);
        if (!$document) {
            return new WP_Error('document_not_found', 'Document not found.');
        }

        // Check permissions
        if (!$this->can_export_document($document)) {
            return new WP_Error('permission_denied', 'You do not have permission to export this document.');
        }

        switch ($format) {
            case 'pdf':
                return $this->export_to_pdf($document, $options);
            case 'docx':
                return $this->export_to_docx($document, $options);
            case 'xlsx':
                return $this->export_to_xlsx($document, $options);
            case 'csv':
                return $this->export_to_csv($document, $options);
            default:
                return new WP_Error('unsupported_format', 'Unsupported export format.');
        }
    }

    /**
     * Export to PDF
     */
    private function export_to_pdf($document, $options = array()) {
        // Check if TCPDF or similar library is available
        if (!class_exists('TCPDF')) {
            // Try to include TCPDF if available
            $tcpdf_path = WP_PLUGIN_DIR . '/bkgt-document-management/vendor/tcpdf/tcpdf.php';
            if (file_exists($tcpdf_path)) {
                require_once($tcpdf_path);
            } else {
                return new WP_Error('library_missing', 'PDF library not available. Please install TCPDF.');
            }
        }

        try {
            // Create PDF
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator('BKGT Document Management');
            $pdf->SetAuthor(get_bloginfo('name'));
            $pdf->SetTitle($document['title']);
            $pdf->SetSubject($document['category']);

            // Set margins
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetHeaderMargin(5);
            $pdf->SetFooterMargin(10);

            // Add page
            $pdf->AddPage();

            // Set font
            $pdf->SetFont('dejavusans', '', 11);

            // Add title
            $pdf->SetFont('dejavusans', 'B', 16);
            $pdf->Cell(0, 10, $document['title'], 0, 1, 'C');
            $pdf->Ln(5);

            // Add metadata
            $pdf->SetFont('dejavusans', '', 10);
            $pdf->Cell(0, 8, 'Kategori: ' . $document['category'], 0, 1);
            $pdf->Cell(0, 8, 'Skapad: ' . $document['created_date'], 0, 1);
            $pdf->Ln(5);

            // Add content
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->writeHTML($document['content'], true, false, true, false, '');

            // Generate filename
            $filename = $this->generate_filename($document, 'pdf');

            // Output PDF
            $pdf_content = $pdf->Output('', 'S');

            return array(
                'filename' => $filename,
                'content' => $pdf_content,
                'mime_type' => 'application/pdf'
            );

        } catch (Exception $e) {
            return new WP_Error('pdf_generation_failed', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export to DOCX
     */
    private function export_to_docx($document, $options = array()) {
        // Check if PHPWord is available
        if (!class_exists('PhpOffice\PhpWord\PhpWord')) {
            // Try to include PHPWord if available
            $phpword_path = WP_PLUGIN_DIR . '/bkgt-document-management/vendor/phpword/bootstrap.php';
            if (file_exists($phpword_path)) {
                require_once($phpword_path);
            } else {
                return new WP_Error('library_missing', 'DOCX library not available. Please install PHPWord.');
            }
        }

        try {
            // Create Word document
            $phpWord = new \PhpOffice\PhpWord\PhpWord();

            // Add section
            $section = $phpWord->addSection();

            // Add title
            $section->addTitle($document['title'], 1);

            // Add metadata
            $section->addText('Kategori: ' . $document['category']);
            $section->addText('Skapad: ' . $document['created_date']);
            $section->addTextBreak(1);

            // Add content
            $html_content = $document['content'];
            \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html_content);

            // Generate filename
            $filename = $this->generate_filename($document, 'docx');

            // Save to temporary file
            $temp_file = tempnam(sys_get_temp_dir(), 'bkgt_docx');
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($temp_file);

            $docx_content = file_get_contents($temp_file);
            unlink($temp_file);

            return array(
                'filename' => $filename,
                'content' => $docx_content,
                'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            );

        } catch (Exception $e) {
            return new WP_Error('docx_generation_failed', 'Failed to generate DOCX: ' . $e->getMessage());
        }
    }

    /**
     * Export to Excel/CSV
     */
    private function export_to_xlsx($document, $options = array()) {
        // For now, export document metadata as Excel
        // In a full implementation, this would parse structured data

        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Try to include PhpSpreadsheet if available
            $spreadsheet_path = WP_PLUGIN_DIR . '/bkgt-document-management/vendor/phpspreadsheet/bootstrap.php';
            if (file_exists($spreadsheet_path)) {
                require_once($spreadsheet_path);
            } else {
                return new WP_Error('library_missing', 'Excel library not available. Please install PhpSpreadsheet.');
            }
        }

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Add headers
            $sheet->setCellValue('A1', 'Fält');
            $sheet->setCellValue('B1', 'Värde');

            // Add document data
            $sheet->setCellValue('A2', 'Titel');
            $sheet->setCellValue('B2', $document['title']);

            $sheet->setCellValue('A3', 'Kategori');
            $sheet->setCellValue('B3', $document['category']);

            $sheet->setCellValue('A4', 'Skapad');
            $sheet->setCellValue('B4', $document['created_date']);

            $sheet->setCellValue('A5', 'Senast ändrad');
            $sheet->setCellValue('B5', $document['modified_date']);

            // Generate filename
            $filename = $this->generate_filename($document, 'xlsx');

            // Save to temporary file
            $temp_file = tempnam(sys_get_temp_dir(), 'bkgt_xlsx');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($temp_file);

            $xlsx_content = file_get_contents($temp_file);
            unlink($temp_file);

            return array(
                'filename' => $filename,
                'content' => $xlsx_content,
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );

        } catch (Exception $e) {
            return new WP_Error('xlsx_generation_failed', 'Failed to generate Excel file: ' . $e->getMessage());
        }
    }

    /**
     * Export to CSV
     */
    private function export_to_csv($document, $options = array()) {
        try {
            // Create CSV content
            $csv_data = array(
                array('Fält', 'Värde'),
                array('Titel', $document['title']),
                array('Kategori', $document['category']),
                array('Skapad', $document['created_date']),
                array('Senast ändrad', $document['modified_date']),
                array('Innehåll', strip_tags($document['content']))
            );

            $csv_content = '';
            foreach ($csv_data as $row) {
                $csv_content .= '"' . implode('","', str_replace('"', '""', $row)) . '"' . "\n";
            }

            // Generate filename
            $filename = $this->generate_filename($document, 'csv');

            return array(
                'filename' => $filename,
                'content' => $csv_content,
                'mime_type' => 'text/csv'
            );

        } catch (Exception $e) {
            return new WP_Error('csv_generation_failed', 'Failed to generate CSV: ' . $e->getMessage());
        }
    }

    /**
     * Get document data for export
     */
    private function get_document_data($document_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_documents';

        $document = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $document_id
            ),
            ARRAY_A
        );

        if (!$document) {
            return false;
        }

        // Get category name
        $category = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}bkgt_document_categories WHERE id = %d",
                $document['category_id']
            )
        );

        $document['category'] = $category ?: 'Okategoriserad';

        return $document;
    }

    /**
     * Check if user can export document
     */
    private function can_export_document($document) {
        // Check user capabilities
        if (!current_user_can('bkgt_export_documents')) {
            return false;
        }

        // Check if user has access to this document category
        // Implementation depends on your access control system

        return true;
    }

    /**
     * Generate filename for export
     */
    private function generate_filename($document, $extension) {
        $safe_title = sanitize_title($document['title']);
        $timestamp = date('Y-m-d-H-i-s');
        return "bkgt-document-{$safe_title}-{$timestamp}.{$extension}";
    }

    /**
     * Bulk export multiple documents
     */
    public function bulk_export($document_ids, $format, $options = array()) {
        if (!is_array($document_ids) || empty($document_ids)) {
            return new WP_Error('invalid_document_ids', 'Invalid document IDs provided.');
        }

        $exported_files = array();
        $errors = array();

        foreach ($document_ids as $document_id) {
            $result = $this->export_document($document_id, $format, $options);

            if (is_wp_error($result)) {
                $errors[] = "Document {$document_id}: " . $result->get_error_message();
            } else {
                $exported_files[] = $result;
            }
        }

        if (empty($exported_files)) {
            return new WP_Error('no_exports', 'No documents could be exported.', array('errors' => $errors));
        }

        // For multiple files, create a ZIP archive
        if (count($exported_files) > 1) {
            return $this->create_zip_archive($exported_files, $format);
        }

        return $exported_files[0];
    }

    /**
     * Create ZIP archive for bulk exports
     */
    private function create_zip_archive($files, $format) {
        if (!class_exists('ZipArchive')) {
            return new WP_Error('zip_not_available', 'ZIP compression not available on this server.');
        }

        try {
            $zip_filename = 'bkgt-documents-export-' . date('Y-m-d-H-i-s') . '.zip';
            $temp_zip = tempnam(sys_get_temp_dir(), 'bkgt_zip');

            $zip = new ZipArchive();
            if ($zip->open($temp_zip, ZipArchive::CREATE) !== true) {
                return new WP_Error('zip_creation_failed', 'Failed to create ZIP archive.');
            }

            foreach ($files as $file) {
                $zip->addFromString($file['filename'], $file['content']);
            }

            $zip->close();

            $zip_content = file_get_contents($temp_zip);
            unlink($temp_zip);

            return array(
                'filename' => $zip_filename,
                'content' => $zip_content,
                'mime_type' => 'application/zip'
            );

        } catch (Exception $e) {
            return new WP_Error('zip_error', 'Failed to create ZIP archive: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Export document
     */
    public function ajax_export_document() {
        // Verify nonce and capabilities
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_export_nonce') ||
            !current_user_can('bkgt_export_documents')) {
            wp_die('Security check failed');
        }

        $document_id = intval($_POST['document_id']);
        $format = sanitize_text_field($_POST['format']);
        $options = isset($_POST['options']) ? $_POST['options'] : array();

        $result = $this->export_document($document_id, $format, $options);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            // Return file data for download
            wp_send_json_success(array(
                'filename' => $result['filename'],
                'content' => base64_encode($result['content']),
                'mime_type' => $result['mime_type']
            ));
        }
    }

    /**
     * AJAX: Bulk export
     */
    public function ajax_bulk_export() {
        // Verify nonce and capabilities
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_export_nonce') ||
            !current_user_can('bkgt_export_documents')) {
            wp_die('Security check failed');
        }

        $document_ids = isset($_POST['document_ids']) ? array_map('intval', $_POST['document_ids']) : array();
        $format = sanitize_text_field($_POST['format']);
        $options = isset($_POST['options']) ? $_POST['options'] : array();

        $result = $this->bulk_export($document_ids, $format, $options);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array(
                'filename' => $result['filename'],
                'content' => base64_encode($result['content']),
                'mime_type' => $result['mime_type']
            ));
        }
    }

    /**
     * AJAX: Get export formats
     */
    public function ajax_get_export_formats() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'bkgt_export_nonce')) {
            wp_die('Security check failed');
        }

        wp_send_json_success(array('formats' => $this->supported_formats));
    }
}

// Initialize export system
new BKGT_DM_Export_System();