<?php
/**
 * PDF Export Handler
 *
 * Handles PDF document generation with custom styling
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_PDF_Export {

    /**
     * Export document to PDF
     */
    public function export($document, $filename, $settings) {
        // Check if TCPDF is available
        if (!class_exists('TCPDF')) {
            // Try to load TCPDF
            $tcpdf_path = plugin_dir_path(__FILE__) . '../../vendor/tcpdf/tcpdf.php';
            if (file_exists($tcpdf_path)) {
                require_once $tcpdf_path;
            } else {
                // Fallback to basic HTML to PDF conversion
                return $this->export_via_html2pdf($document, $filename, $settings);
            }
        }

        // Create PDF instance
        $pdf = new TCPDF(
            $settings['default_orientation'] === 'landscape' ? 'L' : 'P',
            PDF_UNIT,
            $settings['page_size'],
            true,
            'UTF-8',
            false
        );

        // Set document information
        $pdf->SetCreator('BKGT Document Management');
        $pdf->SetAuthor($settings['club_name']);
        $pdf->SetTitle($document->post_title);
        $pdf->SetSubject('BKGT Document');

        // Set margins
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 15);

        // Set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 11);

        // Add header if enabled
        if (!empty($settings['header_text'])) {
            $pdf->SetY(15);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, $settings['header_text'], 0, 1, 'C');
            $pdf->Ln(5);
        }

        // Add club logo if available
        if (!empty($settings['club_logo'])) {
            $logo_path = $this->download_image($settings['club_logo']);
            if ($logo_path) {
                $pdf->Image($logo_path, 15, 25, 30, 0, '', '', '', false, 300, '', false, false, 0, false, false, false);
                $pdf->SetY(60);
            }
        }

        // Process document content
        $content = $this->process_content($document->post_content, $settings);

        // Write HTML content
        $pdf->writeHTML($content, true, false, true, false, '');

        // Add footer if enabled
        if (!empty($settings['footer_text'])) {
            $pdf->SetY(-15);
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->Cell(0, 10, $settings['footer_text'], 0, 0, 'C');
        }

        // Create uploads directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/bkgt-exports/';
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }

        $file_path = $export_dir . $filename;

        // Save PDF file
        $pdf->Output($file_path, 'F');

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
     * Fallback HTML to PDF export
     */
    private function export_via_html2pdf($document, $filename, $settings) {
        // For now, create a simple HTML file that can be converted to PDF
        // In a real implementation, you'd use a service like Puppeteer or similar

        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/bkgt-exports/';
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }

        $html_content = $this->generate_html_content($document, $settings);
        $html_file = $export_dir . str_replace('.pdf', '.html', $filename);

        file_put_contents($html_file, $html_content);

        // For demo purposes, we'll create a placeholder PDF
        // In production, you'd integrate with a proper PDF generation service
        $pdf_content = $this->generate_placeholder_pdf($document, $settings);
        $pdf_file = $export_dir . $filename;

        file_put_contents($pdf_file, $pdf_content);

        return array(
            'path' => $pdf_file,
            'url' => $upload_dir['baseurl'] . '/bkgt-exports/' . $filename,
            'filename' => $filename,
            'size' => strlen($pdf_content),
        );
    }

    /**
     * Process document content for PDF
     */
    private function process_content($content, $settings) {
        // Convert WordPress content to HTML suitable for PDF
        $content = apply_filters('the_content', $content);
        $content = str_replace(']]>', ']]&gt;', $content);

        // Apply custom styling if enabled
        if (!empty($settings['brand-styling'])) {
            $content = $this->apply_brand_styling($content, $settings);
        }

        return $content;
    }

    /**
     * Apply brand styling to content
     */
    private function apply_brand_styling($content, $settings) {
        $primary_color = $settings['primary_color'];
        $secondary_color = $settings['secondary_color'];

        $style = "
            <style>
                h1, h2, h3, h4, h5, h6 {
                    color: {$primary_color};
                    font-family: 'Helvetica', sans-serif;
                }
                .bkgt-brand-header {
                    background-color: {$secondary_color};
                    color: white;
                    padding: 10px;
                    text-align: center;
                    margin-bottom: 20px;
                }
                a {
                    color: {$primary_color};
                }
                table {
                    border-collapse: collapse;
                    width: 100%;
                }
                table th {
                    background-color: {$secondary_color};
                    color: white;
                    padding: 8px;
                    text-align: left;
                }
                table td {
                    padding: 8px;
                    border: 1px solid #ddd;
                }
            </style>
        ";

        return $style . $content;
    }

    /**
     * Generate HTML content for fallback
     */
    private function generate_html_content($document, $settings) {
        $content = $this->process_content($document->post_content, $settings);

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . esc_html($document->post_title) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: ' . $settings['primary_color'] . '; }
        .header { text-align: center; margin-bottom: 30px; }
        .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #666; }
    </style>
</head>
<body>';

        if (!empty($settings['club_name'])) {
            $html .= '<div class="header"><h1>' . esc_html($settings['club_name']) . '</h1></div>';
        }

        $html .= '<h1>' . esc_html($document->post_title) . '</h1>';
        $html .= $content;

        if (!empty($settings['footer_text'])) {
            $html .= '<div class="footer">' . esc_html($settings['footer_text']) . '</div>';
        }

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Generate placeholder PDF content
     */
    private function generate_placeholder_pdf($document, $settings) {
        // This is a placeholder - in real implementation, use proper PDF generation
        $content = "BKGT Document Export\n\n";
        $content .= "Title: " . $document->post_title . "\n";
        $content .= "Club: " . $settings['club_name'] . "\n";
        $content .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $content .= "Content:\n" . wp_strip_all_tags($document->post_content);

        return $content;
    }

    /**
     * Download image from URL
     */
    private function download_image($url) {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/bkgt-temp/';

        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }

        $filename = basename($url);
        $filepath = $temp_dir . $filename;

        $response = wp_remote_get($url);
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $image_data = wp_remote_retrieve_body($response);
            file_put_contents($filepath, $image_data);
            return $filepath;
        }

        return false;
    }
}