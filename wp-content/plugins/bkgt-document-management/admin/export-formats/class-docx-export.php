<?php
/**
 * DOCX Export Handler
 *
 * Handles Microsoft Word document generation
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_DOCX_Export {

    /**
     * Export document to DOCX
     */
    public function export($document, $filename, $settings) {
        // Check if PHPWord is available
        if (!class_exists('PhpOffice\PhpWord\PhpWord')) {
            // Try to load PHPWord
            $phpword_path = plugin_dir_path(__FILE__) . '../../vendor/autoload.php';
            if (file_exists($phpword_path)) {
                require_once $phpword_path;
            } else {
                // Fallback to basic HTML export
                return $this->export_via_html($document, $filename, $settings);
            }
        }

        // Create PHPWord instance
        $phpWord = new \PhpOffice\PhpWord\PhpWord();

        // Set document properties
        $properties = $phpWord->getDocInfo();
        $properties->setCreator('BKGT Document Management');
        $properties->setCompany($settings['club_name']);
        $properties->setTitle($document->post_title);
        $properties->setDescription('BKGT Document Export');
        $properties->setCategory('Document');
        $properties->setLastModifiedBy($settings['club_name']);
        $properties->setCreated(time());
        $properties->setModified(time());

        // Add section
        $section = $phpWord->addSection(array(
            'orientation' => $settings['default_orientation'],
            'marginLeft' => 1200,    // 2cm
            'marginRight' => 1200,   // 2cm
            'marginTop' => 1440,     // 2.5cm
            'marginBottom' => 1440,  // 2.5cm
        ));

        // Add header if enabled
        if (!empty($settings['header_text'])) {
            $header = $section->addHeader();
            $header->addText($settings['header_text'], array('bold' => true, 'size' => 12), array('alignment' => 'center'));
        }

        // Add club logo if available
        if (!empty($settings['club_logo'])) {
            $logo_path = $this->download_image($settings['club_logo']);
            if ($logo_path) {
                $section->addImage($logo_path, array(
                    'width' => 150,
                    'height' => 50,
                    'alignment' => 'center',
                ));
                $section->addTextBreak(1);
            }
        }

        // Process and add content
        $this->add_content_to_section($section, $document->post_content, $settings);

        // Add footer if enabled
        if (!empty($settings['footer_text'])) {
            $footer = $section->addFooter();
            $footer->addText($settings['footer_text'], array('italic' => true, 'size' => 10), array('alignment' => 'center'));
        }

        // Create uploads directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/bkgt-exports/';
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }

        $file_path = $export_dir . $filename;

        // Save DOCX file
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($file_path);

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
     * Fallback HTML export
     */
    private function export_via_html($document, $filename, $settings) {
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/bkgt-exports/';
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }

        $html_content = $this->generate_docx_html($document, $settings);
        $html_file = $export_dir . str_replace('.docx', '.html', $filename);

        file_put_contents($html_file, $html_content);

        // For demo purposes, create a placeholder DOCX
        $docx_content = $this->generate_placeholder_docx($document, $settings);
        $docx_file = $export_dir . $filename;

        file_put_contents($docx_file, $docx_content);

        return array(
            'path' => $docx_file,
            'url' => $upload_dir['baseurl'] . '/bkgt-exports/' . $filename,
            'filename' => $filename,
            'size' => strlen($docx_content),
        );
    }

    /**
     * Add content to PHPWord section
     */
    private function add_content_to_section($section, $content, $settings) {
        // Process WordPress content
        $content = apply_filters('the_content', $content);
        $content = wp_strip_all_tags($content, '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><table><tr><td><th>');

        // Split content into paragraphs
        $paragraphs = preg_split('/<\/p>/', $content);

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim(strip_tags($paragraph, '<strong><em><u>'));

            if (empty($paragraph)) {
                continue;
            }

            // Check for headings
            if (preg_match('/<h([1-6])>(.*?)<\/h[1-6]>/', $paragraph, $matches)) {
                $level = $matches[1];
                $text = strip_tags($matches[2]);

                $fontStyle = array('bold' => true, 'size' => 16 - $level);
                $section->addText($text, $fontStyle);
            } else {
                // Regular paragraph
                $text = strip_tags($paragraph);
                $section->addText($text);
            }

            $section->addTextBreak(1);
        }
    }

    /**
     * Generate DOCX-compatible HTML
     */
    private function generate_docx_html($document, $settings) {
        $content = apply_filters('the_content', $document->post_content);

        $html = '<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="UTF-8">
    <title>' . esc_html($document->post_title) . '</title>
    <style>
        body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
        h1 { font-size: 18pt; color: ' . $settings['primary_color'] . '; margin-bottom: 12pt; }
        h2 { font-size: 16pt; color: ' . $settings['primary_color'] . '; margin-bottom: 10pt; }
        h3 { font-size: 14pt; color: ' . $settings['primary_color'] . '; margin-bottom: 8pt; }
        p { margin-bottom: 8pt; }
        .header { text-align: center; margin-bottom: 24pt; }
        .footer { text-align: center; margin-top: 36pt; font-size: 10pt; color: #666; }
        strong { font-weight: bold; }
        em { font-style: italic; }
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
     * Generate placeholder DOCX content
     */
    private function generate_placeholder_docx($document, $settings) {
        // This is a placeholder - in real implementation, use proper DOCX generation
        $content = "BKGT Document Export - Word Document\n\n";
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