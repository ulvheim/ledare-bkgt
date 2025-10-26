<?php
/**
 * Excel Export Handler
 *
 * Handles Excel spreadsheet generation
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Excel_Export {

    /**
     * Export document to Excel
     */
    public function export($document, $filename, $settings) {
        // Check if PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Try to load PhpSpreadsheet
            $spreadsheet_path = plugin_dir_path(__FILE__) . '../../vendor/autoload.php';
            if (file_exists($spreadsheet_path)) {
                require_once $spreadsheet_path;
            } else {
                // Fallback to CSV export
                return $this->export_via_csv($document, $filename, $settings);
            }
        }

        // Create spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator('BKGT Document Management')
            ->setLastModifiedBy($settings['club_name'])
            ->setTitle($document->post_title)
            ->setSubject('BKGT Document Export')
            ->setDescription('Document exported from BKGT Document Management System')
            ->setKeywords('BKGT document export')
            ->setCategory('Document');

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(50);

        // Add header information
        $row = 1;

        // Club header
        if (!empty($settings['club_name'])) {
            $sheet->setCellValue('A' . $row, $settings['club_name']);
            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal('center');
            $row += 2;
        }

        // Document title
        $sheet->setCellValue('A' . $row, 'Document Title:');
        $sheet->setCellValue('B' . $row, $document->post_title);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        // Export date
        $sheet->setCellValue('A' . $row, 'Export Date:');
        $sheet->setCellValue('B' . $row, date('Y-m-d H:i:s'));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        // Author
        $author = get_the_author_meta('display_name', $document->post_author);
        $sheet->setCellValue('A' . $row, 'Author:');
        $sheet->setCellValue('B' . $row, $author);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row += 2;

        // Process content
        $this->add_content_to_sheet($sheet, $document->post_content, $row, $settings);

        // Apply styling
        $this->apply_styling($sheet, $settings);

        // Create uploads directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/bkgt-exports/';
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }

        $file_path = $export_dir . $filename;

        // Save Excel file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($file_path);

        // Generate download URL
        $file_url = $upload_dir['baseurl'] . '/bkgt-exports/' . $filename;

        return array(
            'path' => $file_path,
            'url' => $file_url,
            'filename' => $filename,
            'size' => filesize($file_path),
        );
    }

    /**
     * Fallback CSV export
     */
    private function export_via_csv($document, $filename, $settings) {
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/bkgt-exports/';
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }

        $csv_content = $this->generate_csv_content($document, $settings);
        $csv_file = $export_dir . str_replace('.xlsx', '.csv', $filename);

        file_put_contents($csv_file, $csv_content);

        // For demo purposes, create a placeholder Excel file
        $excel_content = $this->generate_placeholder_excel($document, $settings);
        $excel_file = $export_dir . $filename;

        file_put_contents($excel_file, $excel_content);

        return array(
            'path' => $excel_file,
            'url' => $upload_dir['baseurl'] . '/bkgt-exports/' . $filename,
            'filename' => $filename,
            'size' => strlen($excel_content),
        );
    }

    /**
     * Add content to spreadsheet
     */
    private function add_content_to_sheet($sheet, $content, &$row, $settings) {
        // Process WordPress content
        $content = apply_filters('the_content', $content);
        $content = wp_strip_all_tags($content);

        // Split into lines
        $lines = explode("\n", $content);
        $lines = array_filter($lines, 'trim');

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Check if line looks like a table row (contains multiple values separated by | or ,)
            if (preg_match('/[,|]\s*/', $line)) {
                $cells = preg_split('/[,|]\s*/', $line);
                $col = 'A';
                foreach ($cells as $cell_value) {
                    $sheet->setCellValue($col . $row, trim($cell_value));
                    $col++;
                }
            } else {
                // Regular text
                $sheet->setCellValue('A' . $row, $line);
                $sheet->mergeCells('A' . $row . ':B' . $row);
            }

            $row++;
        }
    }

    /**
     * Apply styling to spreadsheet
     */
    private function apply_styling($sheet, $settings) {
        // Header styling
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => str_replace('#', '', $settings['primary_color'])],
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => str_replace('#', '', $settings['secondary_color'])],
            ],
        ];

        // Apply header style to first few rows
        $sheet->getStyle('A1:B3')->applyFromArray($headerStyle);

        // Set borders
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ];

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray($styleArray);
    }

    /**
     * Generate CSV content
     */
    private function generate_csv_content($document, $settings) {
        $csv = array();

        // Add header
        if (!empty($settings['club_name'])) {
            $csv[] = '"' . $settings['club_name'] . '"';
            $csv[] = '';
        }

        $csv[] = '"Document Title","' . addslashes($document->post_title) . '"';
        $csv[] = '"Export Date","' . date('Y-m-d H:i:s') . '"';
        $csv[] = '"Author","' . addslashes(get_the_author_meta('display_name', $document->post_author)) . '"';
        $csv[] = '';

        // Add content
        $content = apply_filters('the_content', $document->post_content);
        $content = wp_strip_all_tags($content);
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $csv[] = '"' . addslashes($line) . '"';
            }
        }

        return implode("\n", $csv);
    }

    /**
     * Generate placeholder Excel content
     */
    private function generate_placeholder_excel($document, $settings) {
        // This is a placeholder - in real implementation, use proper Excel generation
        $content = "BKGT Document Export - Excel Spreadsheet\n\n";
        $content .= "Title: " . $document->post_title . "\n";
        $content .= "Club: " . $settings['club_name'] . "\n";
        $content .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $content .= "Content:\n" . wp_strip_all_tags($document->post_content);

        return $content;
    }
}