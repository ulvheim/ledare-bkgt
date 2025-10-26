<?php
/**
 * HTML Export Handler
 *
 * Handles HTML document generation with embedded styling
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_HTML_Export {

    /**
     * Export document to HTML
     */
    public function export($document, $filename, $settings) {
        // Create uploads directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/bkgt-exports/';
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }

        // Generate HTML content
        $html_content = $this->generate_html($document, $settings);

        $file_path = $export_dir . $filename;

        // Save HTML file
        file_put_contents($file_path, $html_content);

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
     * Generate complete HTML document
     */
    private function generate_html($document, $settings) {
        $content = $this->process_content($document->post_content, $settings);
        $css = $this->generate_css($settings);
        $metadata = $this->generate_metadata($document, $settings);

        $html = '<!DOCTYPE html>
<html lang="sv-SE">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    ' . $metadata . '
    <title>' . esc_html($document->post_title) . ' - ' . esc_html($settings['club_name']) . '</title>
    <style>
        ' . $css . '
    </style>
</head>
<body>
    <div class="bkgt-document-container">
        ' . $this->generate_header($settings) . '

        <main class="bkgt-document-content">
            <h1 class="bkgt-document-title">' . esc_html($document->post_title) . '</h1>
            ' . $content . '
        </main>

        ' . $this->generate_footer($settings) . '
    </div>

    <script>
        ' . $this->generate_javascript() . '
    </script>
</body>
</html>';

        return $html;
    }

    /**
     * Process document content
     */
    private function process_content($content, $settings) {
        // Apply WordPress content filters
        $content = apply_filters('the_content', $content);

        // Convert relative URLs to absolute
        $content = $this->make_urls_absolute($content);

        // Apply brand styling if enabled
        if (!empty($settings['brand-styling'])) {
            $content = $this->apply_brand_styling($content, $settings);
        }

        return $content;
    }

    /**
     * Generate CSS styles
     */
    private function generate_css($settings) {
        $primary_color = $settings['primary_color'];
        $secondary_color = $settings['secondary_color'];

        return "
            /* Reset and base styles */
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                background-color: #f8f9fa;
                font-size: 14px;
            }

            .bkgt-document-container {
                max-width: 800px;
                margin: 0 auto;
                background-color: white;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
                min-height: 100vh;
            }

            /* Header styles */
            .bkgt-document-header {
                background: linear-gradient(135deg, {$primary_color}, {$secondary_color});
                color: white;
                padding: 30px 20px;
                text-align: center;
            }

            .bkgt-club-logo {
                max-width: 150px;
                height: auto;
                margin-bottom: 15px;
                border-radius: 8px;
            }

            .bkgt-club-name {
                font-size: 24px;
                font-weight: bold;
                margin-bottom: 10px;
            }

            .bkgt-header-text {
                font-size: 16px;
                opacity: 0.9;
            }

            /* Content styles */
            .bkgt-document-content {
                padding: 40px;
            }

            .bkgt-document-title {
                color: {$primary_color};
                font-size: 32px;
                margin-bottom: 30px;
                border-bottom: 3px solid {$secondary_color};
                padding-bottom: 15px;
            }

            /* Typography */
            h1, h2, h3, h4, h5, h6 {
                color: {$primary_color};
                margin-bottom: 15px;
                font-weight: 600;
            }

            h1 { font-size: 28px; }
            h2 { font-size: 24px; }
            h3 { font-size: 20px; }
            h4 { font-size: 18px; }
            h5 { font-size: 16px; }
            h6 { font-size: 14px; }

            p {
                margin-bottom: 20px;
                text-align: justify;
            }

            /* Lists */
            ul, ol {
                margin-bottom: 20px;
                padding-left: 30px;
            }

            li {
                margin-bottom: 8px;
            }

            /* Tables */
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
                background-color: white;
            }

            th, td {
                padding: 12px 15px;
                text-align: left;
                border: 1px solid #ddd;
            }

            th {
                background-color: {$secondary_color};
                color: white;
                font-weight: 600;
            }

            tr:nth-child(even) {
                background-color: #f8f9fa;
            }

            /* Links */
            a {
                color: {$primary_color};
                text-decoration: none;
                transition: color 0.3s ease;
            }

            a:hover {
                color: {$secondary_color};
                text-decoration: underline;
            }

            /* Blockquotes */
            blockquote {
                border-left: 4px solid {$primary_color};
                padding-left: 20px;
                margin: 20px 0;
                font-style: italic;
                color: #666;
            }

            /* Code */
            code {
                background-color: #f4f4f4;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: 'Courier New', monospace;
                font-size: 13px;
            }

            pre {
                background-color: #f4f4f4;
                padding: 15px;
                border-radius: 5px;
                overflow-x: auto;
                margin-bottom: 20px;
            }

            pre code {
                background-color: transparent;
                padding: 0;
            }

            /* Footer */
            .bkgt-document-footer {
                background-color: #f8f9fa;
                padding: 20px;
                text-align: center;
                border-top: 1px solid #dee2e6;
                color: #666;
                font-size: 12px;
            }

            /* Print styles */
            @media print {
                body {
                    background-color: white;
                }

                .bkgt-document-container {
                    box-shadow: none;
                    max-width: none;
                }

                .bkgt-document-content {
                    padding: 20px;
                }

                a {
                    color: black !important;
                    text-decoration: underline !important;
                }
            }

            /* Responsive design */
            @media (max-width: 768px) {
                .bkgt-document-content {
                    padding: 20px;
                }

                .bkgt-document-title {
                    font-size: 24px;
                }

                table {
                    font-size: 12px;
                }

                th, td {
                    padding: 8px 10px;
                }
            }
        ";
    }

    /**
     * Generate document metadata
     */
    private function generate_metadata($document, $settings) {
        $author = get_the_author_meta('display_name', $document->post_author);
        $description = wp_trim_words(wp_strip_all_tags($document->post_content), 30);

        return '
    <meta name="description" content="' . esc_attr($description) . '">
    <meta name="author" content="' . esc_attr($author) . '">
    <meta name="generator" content="BKGT Document Management System">
    <meta name="created" content="' . esc_attr($document->post_date) . '">
    <meta name="modified" content="' . esc_attr($document->post_modified) . '">
    <meta name="organization" content="' . esc_attr($settings['club_name']) . '">';
    }

    /**
     * Generate document header
     */
    private function generate_header($settings) {
        $header = '<header class="bkgt-document-header">';

        if (!empty($settings['club_logo'])) {
            $header .= '<img src="' . esc_url($settings['club_logo']) . '" alt="' . esc_attr($settings['club_name']) . ' Logo" class="bkgt-club-logo">';
        }

        if (!empty($settings['club_name'])) {
            $header .= '<h1 class="bkgt-club-name">' . esc_html($settings['club_name']) . '</h1>';
        }

        if (!empty($settings['header_text'])) {
            $header .= '<p class="bkgt-header-text">' . esc_html($settings['header_text']) . '</p>';
        }

        $header .= '</header>';

        return $header;
    }

    /**
     * Generate document footer
     */
    private function generate_footer($settings) {
        if (empty($settings['footer_text'])) {
            return '';
        }

        return '<footer class="bkgt-document-footer">
            <p>' . esc_html($settings['footer_text']) . '</p>
            <p>Generated on ' . date('Y-m-d H:i:s') . ' by BKGT Document Management System</p>
        </footer>';
    }

    /**
     * Generate JavaScript for interactivity
     */
    private function generate_javascript() {
        return "
            // Add print functionality
            function printDocument() {
                window.print();
            }

            // Add click tracking for external links
            document.addEventListener('DOMContentLoaded', function() {
                var links = document.querySelectorAll('a[href^=\"http\"]');
                links.forEach(function(link) {
                    link.setAttribute('target', '_blank');
                    link.setAttribute('rel', 'noopener noreferrer');
                });
            });

            // Add table sorting functionality
            function sortTable(table, column, asc = true) {
                var tbody = table.querySelector('tbody');
                var rows = Array.from(tbody.querySelectorAll('tr'));

                rows.sort(function(a, b) {
                    var aVal = a.children[column].textContent.trim();
                    var bVal = b.children[column].textContent.trim();

                    if (asc) {
                        return aVal.localeCompare(bVal);
                    } else {
                        return bVal.localeCompare(aVal);
                    }
                });

                rows.forEach(function(row) {
                    tbody.appendChild(row);
                });
            }

            // Make tables sortable
            document.addEventListener('DOMContentLoaded', function() {
                var tables = document.querySelectorAll('table');
                tables.forEach(function(table) {
                    var headers = table.querySelectorAll('th');
                    headers.forEach(function(header, index) {
                        header.style.cursor = 'pointer';
                        header.addEventListener('click', function() {
                            sortTable(table, index);
                        });
                    });
                });
            });
        ";
    }

    /**
     * Apply brand styling to content
     */
    private function apply_brand_styling($content, $settings) {
        // Add brand classes to headings
        $content = preg_replace('/<h([1-6])>/', '<h$1 class="bkgt-branded-heading">', $content);

        // Add brand classes to links
        $content = preg_replace('/<a /', '<a class="bkgt-branded-link" ', $content);

        return $content;
    }

    /**
     * Convert relative URLs to absolute
     */
    private function make_urls_absolute($content) {
        $site_url = get_site_url();

        // Convert relative image URLs
        $content = preg_replace('/src="\/([^"]+)"/', 'src="' . $site_url . '/$1"', $content);

        // Convert relative links
        $content = preg_replace('/href="\/([^"]+)"/', 'href="' . $site_url . '/$1"', $content);

        return $content;
    }
}