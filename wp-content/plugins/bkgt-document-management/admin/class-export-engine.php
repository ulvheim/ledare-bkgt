<?php
/**
 * Advanced Export & Integration Engine
 *
 * Handles multi-format document export and cloud integration
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Advanced_Export_Engine {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_bkgt_export_document', array($this, 'ajax_export_document'));
        add_action('wp_ajax_bkgt_batch_export', array($this, 'ajax_batch_export'));
        add_action('wp_ajax_bkgt_cloud_upload', array($this, 'ajax_cloud_upload'));
        add_action('wp_ajax_bkgt_get_export_settings', array($this, 'ajax_get_export_settings'));
        add_action('wp_ajax_bkgt_save_export_settings', array($this, 'ajax_save_export_settings'));
        add_action('wp_ajax_bkgt_get_documents_for_export', array($this, 'ajax_get_documents_for_export'));
        add_action('wp_ajax_bkgt_preview_document', array($this, 'ajax_preview_document'));

        // REST API endpoints
        add_action('rest_api_init', array($this, 'register_api_endpoints'));

        // Initialize export formats
        $this->init_export_formats();
    }

    /**
     * Initialize export format handlers
     */
    private function init_export_formats() {
        // Include format handlers
        require_once plugin_dir_path(__FILE__) . 'export-formats/class-docx-export.php';
        require_once plugin_dir_path(__FILE__) . 'export-formats/class-pdf-export.php';
        require_once plugin_dir_path(__FILE__) . 'export-formats/class-excel-export.php';
        require_once plugin_dir_path(__FILE__) . 'export-formats/class-html-export.php';
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'bkgt-documents',
            __('Exportera dokument', 'bkgt-document-management'),
            __('Exportera', 'bkgt-document-management'),
            'edit_documents',
            'bkgt-export-engine',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'bkgt-documents',
            __('Exportinställningar', 'bkgt-document-management'),
            __('Exportinställningar', 'bkgt-document-management'),
            'manage_options',
            'bkgt-export-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'bkgt-export') === false) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-progressbar');
        wp_enqueue_media();

        wp_enqueue_script(
            'bkgt-export-engine-js',
            plugins_url('admin/js/export-engine.js', dirname(__FILE__)),
            array('jquery'),
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'bkgt-export-engine-css',
            plugins_url('admin/css/export-engine.css', dirname(__FILE__)),
            array(),
            '1.0.0'
        );

        wp_localize_script('bkgt-export-engine-js', 'bkgt_export', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkgt-export-nonce'),
            'strings' => array(
                'exporting' => __('Exporterar...', 'bkgt-document-management'),
                'export_complete' => __('Export slutförd!', 'bkgt-document-management'),
                'export_failed' => __('Export misslyckades', 'bkgt-document-management'),
                'uploading' => __('Laddar upp...', 'bkgt-document-management'),
                'upload_complete' => __('Uppladdning slutförd!', 'bkgt-document-management'),
                'upload_failed' => __('Uppladdning misslyckades', 'bkgt-document-management'),
                'select_documents' => __('Välj dokument att exportera', 'bkgt-document-management'),
                'no_documents_selected' => __('Inga dokument valda', 'bkgt-document-management'),
                'confirm_batch_export' => __('Är du säker på att du vill exportera %d dokument?', 'bkgt-document-management'),
            )
        ));
    }

    /**
     * Main admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Avancerad exportmotor', 'bkgt-document-management'); ?></h1>

            <div class="bkgt-export-engine">
                <!-- Export Controls -->
                <div class="bkgt-export-controls">
                    <h2><?php _e('Exportalternativ', 'bkgt-document-management'); ?></h2>

                    <div class="bkgt-export-options">
                        <div class="bkgt-format-selection">
                            <h3><?php _e('Välj format', 'bkgt-document-management'); ?></h3>
                            <div class="bkgt-format-checkboxes">
                                <label>
                                    <input type="checkbox" id="export-docx" checked>
                                    <span class="bkgt-format-icon docx">DOCX</span>
                                    <?php _e('Microsoft Word', 'bkgt-document-management'); ?>
                                </label>
                                <label>
                                    <input type="checkbox" id="export-pdf" checked>
                                    <span class="bkgt-format-icon pdf">PDF</span>
                                    <?php _e('PDF Dokument', 'bkgt-document-management'); ?>
                                </label>
                                <label>
                                    <input type="checkbox" id="export-excel">
                                    <span class="bkgt-format-icon excel">XLSX</span>
                                    <?php _e('Excel Kalkylblad', 'bkgt-document-management'); ?>
                                </label>
                                <label>
                                    <input type="checkbox" id="export-html">
                                    <span class="bkgt-format-icon html">HTML</span>
                                    <?php _e('Webbformat', 'bkgt-document-management'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="bkgt-export-settings">
                            <h3><?php _e('Exportinställningar', 'bkgt-document-management'); ?></h3>
                            <div class="bkgt-setting-group">
                                <label for="export-quality"><?php _e('Kvalitet:', 'bkgt-document-management'); ?></label>
                                <select id="export-quality">
                                    <option value="standard"><?php _e('Standard', 'bkgt-document-management'); ?></option>
                                    <option value="high"><?php _e('Hög kvalitet', 'bkgt-document-management'); ?></option>
                                    <option value="print"><?php _e('Utskriftskvalitet', 'bkgt-document-management'); ?></option>
                                </select>
                            </div>
                            <div class="bkgt-setting-group">
                                <label for="export-orientation"><?php _e('Orientering:', 'bkgt-document-management'); ?></label>
                                <select id="export-orientation">
                                    <option value="portrait"><?php _e('Porträtt', 'bkgt-document-management'); ?></option>
                                    <option value="landscape"><?php _e('Landskap', 'bkgt-document-management'); ?></option>
                                </select>
                            </div>
                            <div class="bkgt-setting-group">
                                <label>
                                    <input type="checkbox" id="include-headers" checked>
                                    <?php _e('Inkludera sidhuvuden/sidfot', 'bkgt-document-management'); ?>
                                </label>
                            </div>
                            <div class="bkgt-setting-group">
                                <label>
                                    <input type="checkbox" id="brand-styling" checked>
                                    <?php _e('Applicera klubbanpassad styling', 'bkgt-document-management'); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Document Selection -->
                <div class="bkgt-document-selection">
                    <h2><?php _e('Välj dokument', 'bkgt-document-management'); ?></h2>

                    <div class="bkgt-selection-controls">
                        <button id="select-all-docs" class="button"><?php _e('Välj alla', 'bkgt-document-management'); ?></button>
                        <button id="select-none-docs" class="button"><?php _e('Välj inga', 'bkgt-document-management'); ?></button>
                        <button id="filter-recent" class="button"><?php _e('Senaste 7 dagar', 'bkgt-document-management'); ?></button>
                        <input type="text" id="doc-search" placeholder="<?php _e('Sök dokument...', 'bkgt-document-management'); ?>" class="regular-text">
                    </div>

                    <div id="bkgt-document-list" class="bkgt-document-list">
                        <!-- Documents will be loaded here -->
                    </div>
                </div>

                <!-- Export Actions -->
                <div class="bkgt-export-actions">
                    <div class="bkgt-action-buttons">
                        <button id="export-selected" class="button button-primary button-hero">
                            <i class="dashicons dashicons-download"></i>
                            <?php _e('Exportera valda dokument', 'bkgt-document-management'); ?>
                        </button>
                        <button id="preview-export" class="button button-secondary">
                            <i class="dashicons dashicons-visibility"></i>
                            <?php _e('Förhandsgranska', 'bkgt-document-management'); ?>
                        </button>
                    </div>

                    <div class="bkgt-cloud-actions">
                        <h3><?php _e('Molnintegration', 'bkgt-document-management'); ?></h3>
                        <div class="bkgt-cloud-buttons">
                            <button id="upload-drive" class="button" data-provider="google">
                                <i class="dashicons dashicons-cloud-upload"></i>
                                <?php _e('Google Drive', 'bkgt-document-management'); ?>
                            </button>
                            <button id="upload-onedrive" class="button" data-provider="microsoft">
                                <i class="dashicons dashicons-cloud-upload"></i>
                                <?php _e('OneDrive', 'bkgt-document-management'); ?>
                            </button>
                            <button id="upload-dropbox" class="button" data-provider="dropbox">
                                <i class="dashicons dashicons-cloud-upload"></i>
                                <?php _e('Dropbox', 'bkgt-document-management'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Export Progress -->
                <div id="bkgt-export-progress" class="bkgt-export-progress" style="display: none;">
                    <h3><?php _e('Exportförlopp', 'bkgt-document-management'); ?></h3>
                    <div class="bkgt-progress-bar">
                        <div class="bkgt-progress-fill" id="progress-fill"></div>
                    </div>
                    <div class="bkgt-progress-text" id="progress-text">
                        <?php _e('Förbereder export...', 'bkgt-document-management'); ?>
                    </div>
                </div>

                <!-- Export Results -->
                <div id="bkgt-export-results" class="bkgt-export-results" style="display: none;">
                    <h3><?php _e('Exportresultat', 'bkgt-document-management'); ?></h3>
                    <div id="export-files-list" class="bkgt-files-list"></div>
                    <div class="bkgt-results-actions">
                        <button id="download-all" class="button button-primary">
                            <?php _e('Ladda ner alla', 'bkgt-document-management'); ?>
                        </button>
                        <button id="share-results" class="button">
                            <?php _e('Dela filer', 'bkgt-document-management'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div id="bkgt-preview-modal" class="bkgt-modal">
            <div class="bkgt-modal-content large">
                <div class="bkgt-modal-header">
                    <h2><?php _e('Exportförhandsgranskning', 'bkgt-document-management'); ?></h2>
                    <button class="bkgt-modal-close">&times;</button>
                </div>
                <div class="bkgt-modal-body">
                    <div id="bkgt-preview-content"></div>
                </div>
            </div>
        </div>

        <!-- Cloud Auth Modal -->
        <div id="bkgt-cloud-auth-modal" class="bkgt-modal">
            <div class="bkgt-modal-content">
                <div class="bkgt-modal-header">
                    <h2 id="cloud-auth-title"><?php _e('Molnauktorisering', 'bkgt-document-management'); ?></h2>
                    <button class="bkgt-modal-close">&times;</button>
                </div>
                <div class="bkgt-modal-body">
                    <div id="bkgt-cloud-auth-content"></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['save_settings']) && wp_verify_nonce($_POST['bkgt_export_nonce'], 'bkgt_export_settings')) {
            $this->save_settings($_POST);
            echo '<div class="notice notice-success"><p>' . __('Inställningar sparade!', 'bkgt-document-management') . '</p></div>';
        }

        $settings = $this->get_settings();
        ?>
        <div class="wrap">
            <h1><?php _e('Exportinställningar', 'bkgt-document-management'); ?></h1>

            <form method="post">
                <?php wp_nonce_field('bkgt_export_settings', 'bkgt_export_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Klubbinformation', 'bkgt-document-management'); ?></th>
                        <td>
                            <fieldset>
                                <label for="club_name">
                                    <input type="text" id="club_name" name="club_name" value="<?php echo esc_attr($settings['club_name']); ?>" class="regular-text">
                                    <?php _e('Klubbnamn', 'bkgt-document-management'); ?>
                                </label>
                                <br>
                                <label for="club_logo">
                                    <input type="url" id="club_logo" name="club_logo" value="<?php echo esc_attr($settings['club_logo']); ?>" class="regular-text">
                                    <?php _e('Logotyp URL', 'bkgt-document-management'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Färgschema', 'bkgt-document-management'); ?></th>
                        <td>
                            <fieldset>
                                <label for="primary_color">
                                    <input type="color" id="primary_color" name="primary_color" value="<?php echo esc_attr($settings['primary_color']); ?>">
                                    <?php _e('Primärfärg', 'bkgt-document-management'); ?>
                                </label>
                                <br>
                                <label for="secondary_color">
                                    <input type="color" id="secondary_color" name="secondary_color" value="<?php echo esc_attr($settings['secondary_color']); ?>">
                                    <?php _e('Sekundärfärg', 'bkgt-document-management'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Standardformat', 'bkgt-document-management'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="default_formats[]" value="docx" <?php checked(in_array('docx', $settings['default_formats'])); ?>>
                                    <?php _e('DOCX (Word)', 'bkgt-document-management'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="default_formats[]" value="pdf" <?php checked(in_array('pdf', $settings['default_formats'])); ?>>
                                    <?php _e('PDF', 'bkgt-document-management'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="default_formats[]" value="excel" <?php checked(in_array('excel', $settings['default_formats'])); ?>>
                                    <?php _e('Excel', 'bkgt-document-management'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Sidinställningar', 'bkgt-document-management'); ?></th>
                        <td>
                            <fieldset>
                                <label for="page_size">
                                    <select id="page_size" name="page_size">
                                        <option value="a4" <?php selected($settings['page_size'], 'a4'); ?>>A4</option>
                                        <option value="letter" <?php selected($settings['page_size'], 'letter'); ?>>Letter</option>
                                        <option value="legal" <?php selected($settings['page_size'], 'legal'); ?>>Legal</option>
                                    </select>
                                    <?php _e('Pappersstorlek', 'bkgt-document-management'); ?>
                                </label>
                                <br>
                                <label for="default_orientation">
                                    <select id="default_orientation" name="default_orientation">
                                        <option value="portrait" <?php selected($settings['default_orientation'], 'portrait'); ?>>Porträtt</option>
                                        <option value="landscape" <?php selected($settings['default_orientation'], 'landscape'); ?>>Landskap</option>
                                    </select>
                                    <?php _e('Standardorientering', 'bkgt-document-management'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Sidhuvuden och sidfot', 'bkgt-document-management'); ?></th>
                        <td>
                            <fieldset>
                                <label for="header_text">
                                    <input type="text" id="header_text" name="header_text" value="<?php echo esc_attr($settings['header_text']); ?>" class="regular-text">
                                    <?php _e('Sidhuvudstext', 'bkgt-document-management'); ?>
                                </label>
                                <br>
                                <label for="footer_text">
                                    <input type="text" id="footer_text" name="footer_text" value="<?php echo esc_attr($settings['footer_text']); ?>" class="regular-text">
                                    <?php _e('Sidfotstext', 'bkgt-document-management'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="save_settings" class="button button-primary" value="<?php _e('Spara inställningar', 'bkgt-document-management'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Get export settings
     */
    private function get_settings() {
        return wp_parse_args(get_option('bkgt_export_settings', array()), array(
            'club_name' => 'BKGT',
            'club_logo' => '',
            'primary_color' => '#007cba',
            'secondary_color' => '#005a87',
            'default_formats' => array('docx', 'pdf'),
            'page_size' => 'a4',
            'default_orientation' => 'portrait',
            'header_text' => '',
            'footer_text' => '',
        ));
    }

    /**
     * Save export settings
     */
    private function save_settings($data) {
        $settings = array(
            'club_name' => sanitize_text_field($data['club_name']),
            'club_logo' => esc_url_raw($data['club_logo']),
            'primary_color' => sanitize_hex_color($data['primary_color']),
            'secondary_color' => sanitize_hex_color($data['secondary_color']),
            'default_formats' => isset($data['default_formats']) ? array_map('sanitize_text_field', $data['default_formats']) : array(),
            'page_size' => sanitize_text_field($data['page_size']),
            'default_orientation' => sanitize_text_field($data['default_orientation']),
            'header_text' => sanitize_text_field($data['header_text']),
            'footer_text' => sanitize_text_field($data['footer_text']),
        );

        update_option('bkgt_export_settings', $settings);
    }

    /**
     * Register REST API endpoints
     */
    public function register_api_endpoints() {
        register_rest_route('bkgt/v1', '/export/(?P<id>\d+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_export_document'),
            'permission_callback' => array($this, 'api_permissions_check'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'format' => array(
                    'required' => false,
                    'default' => 'pdf',
                    'validate_callback' => function($param) {
                        return in_array($param, array('pdf', 'docx', 'excel', 'html'));
                    }
                ),
            ),
        ));

        register_rest_route('bkgt/v1', '/export/batch', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_batch_export'),
            'permission_callback' => array($this, 'api_permissions_check'),
            'args' => array(
                'document_ids' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_array($param) && !empty($param);
                    }
                ),
                'formats' => array(
                    'required' => false,
                    'default' => array('pdf'),
                    'validate_callback' => function($param) {
                        if (!is_array($param)) return false;
                        $valid_formats = array('pdf', 'docx', 'excel', 'html');
                        return empty(array_diff($param, $valid_formats));
                    }
                ),
            ),
        ));
    }

    /**
     * API permissions check
     */
    public function api_permissions_check($request) {
        return current_user_can('edit_documents');
    }

    /**
     * API export single document
     */
    public function api_export_document($request) {
        $document_id = $request->get_param('id');
        $format = $request->get_param('format');

        $result = $this->export_document($document_id, $format);

        if (is_wp_error($result)) {
            return new WP_Error('export_failed', $result->get_error_message(), array('status' => 500));
        }

        return array(
            'success' => true,
            'file_url' => $result['url'],
            'file_name' => $result['filename'],
            'format' => $format,
        );
    }

    /**
     * API batch export
     */
    public function api_batch_export($request) {
        $document_ids = $request->get_param('document_ids');
        $formats = $request->get_param('formats');

        $results = array();
        foreach ($document_ids as $doc_id) {
            foreach ($formats as $format) {
                $result = $this->export_document($doc_id, $format);
                if (!is_wp_error($result)) {
                    $results[] = array(
                        'document_id' => $doc_id,
                        'format' => $format,
                        'file_url' => $result['url'],
                        'file_name' => $result['filename'],
                    );
                }
            }
        }

        return array(
            'success' => true,
            'exports' => $results,
            'total_exports' => count($results),
        );
    }

    /**
     * AJAX export document
     */
    public function ajax_export_document() {
        check_ajax_referer('bkgt-export-nonce', 'nonce');

        $document_id = intval($_POST['document_id']);
        $formats = isset($_POST['formats']) ? $_POST['formats'] : array('pdf');
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();

        $results = array();
        foreach ($formats as $format) {
            $result = $this->export_document($document_id, $format, $settings);
            if (!is_wp_error($result)) {
                $results[$format] = $result;
            }
        }

        if (empty($results)) {
            wp_send_json_error(__('Export misslyckades för alla format', 'bkgt-document-management'));
        } else {
            wp_send_json_success(array(
                'exports' => $results,
                'message' => __('Dokument exporterat!', 'bkgt-document-management')
            ));
        }
    }

    /**
     * AJAX batch export
     */
    public function ajax_batch_export() {
        check_ajax_referer('bkgt-export-nonce', 'nonce');

        $document_ids = $_POST['document_ids'];
        $formats = isset($_POST['formats']) ? $_POST['formats'] : array('pdf');
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();

        $results = array();
        $total_processed = 0;

        foreach ($document_ids as $doc_id) {
            foreach ($formats as $format) {
                $result = $this->export_document($doc_id, $format, $settings);
                if (!is_wp_error($result)) {
                    $results[] = array(
                        'document_id' => $doc_id,
                        'format' => $format,
                        'file_url' => $result['url'],
                        'file_name' => $result['filename'],
                    );
                    $total_processed++;
                }
            }
        }

        wp_send_json_success(array(
            'exports' => $results,
            'total_processed' => $total_processed,
            'message' => sprintf(__('Exporterade %d filer!', 'bkgt-document-management'), $total_processed)
        ));
    }

    /**
     * Export single document
     */
    private function export_document($document_id, $format, $settings = array()) {
        $document = get_post($document_id);
        if (!$document || $document->post_type !== 'bkgt_document') {
            return new WP_Error('invalid_document', __('Ogiltigt dokument.', 'bkgt-document-management'));
        }

        // Get export settings
        $export_settings = wp_parse_args($settings, $this->get_settings());

        // Generate filename
        $filename = $this->generate_filename($document, $format);

        // Export based on format
        switch ($format) {
            case 'pdf':
                $result = $this->export_to_pdf($document, $filename, $export_settings);
                break;
            case 'docx':
                $result = $this->export_to_docx($document, $filename, $export_settings);
                break;
            case 'excel':
                $result = $this->export_to_excel($document, $filename, $export_settings);
                break;
            case 'html':
                $result = $this->export_to_html($document, $filename, $export_settings);
                break;
            default:
                return new WP_Error('unsupported_format', __('Formatet stöds inte.', 'bkgt-document-management'));
        }

        return $result;
    }

    /**
     * Generate filename for export
     */
    private function generate_filename($document, $format) {
        $title = sanitize_title($document->post_title);
        $timestamp = date('Y-m-d-H-i-s');
        return "{$title}-{$timestamp}.{$format}";
    }

    /**
     * Export to PDF
     */
    private function export_to_pdf($document, $filename, $settings) {
        try {
            $pdf_exporter = new BKGT_PDF_Export();
            return $pdf_exporter->export($document, $filename, $settings);
        } catch (Exception $e) {
            return new WP_Error('pdf_export_failed', $e->getMessage());
        }
    }

    /**
     * Export to DOCX
     */
    private function export_to_docx($document, $filename, $settings) {
        try {
            $docx_exporter = new BKGT_DOCX_Export();
            return $docx_exporter->export($document, $filename, $settings);
        } catch (Exception $e) {
            return new WP_Error('docx_export_failed', $e->getMessage());
        }
    }

    /**
     * Export to Excel
     */
    private function export_to_excel($document, $filename, $settings) {
        try {
            $excel_exporter = new BKGT_Excel_Export();
            return $excel_exporter->export($document, $filename, $settings);
        } catch (Exception $e) {
            return new WP_Error('excel_export_failed', $e->getMessage());
        }
    }

    /**
     * Export to HTML
     */
    private function export_to_html($document, $filename, $settings) {
        try {
            $html_exporter = new BKGT_HTML_Export();
            return $html_exporter->export($document, $filename, $settings);
        } catch (Exception $e) {
            return new WP_Error('html_export_failed', $e->getMessage());
        }
    }

    /**
     * AJAX cloud upload
     */
    public function ajax_cloud_upload() {
        check_ajax_referer('bkgt-export-nonce', 'nonce');

        $files = $_POST['files'];
        $provider = sanitize_text_field($_POST['provider']);

        $results = array();
        foreach ($files as $file) {
            $result = $this->upload_to_cloud($file, $provider);
            if (!is_wp_error($result)) {
                $results[] = $result;
            }
        }

        if (empty($results)) {
            wp_send_json_error(__('Uppladdning misslyckades', 'bkgt-document-management'));
        } else {
            wp_send_json_success(array(
                'uploads' => $results,
                'message' => __('Filer uppladdade till molnet!', 'bkgt-document-management')
            ));
        }
    }

    /**
     * Upload to cloud storage
     */
    private function upload_to_cloud($file, $provider) {
        // This would integrate with actual cloud APIs
        // For now, return mock success
        return array(
            'file' => $file,
            'provider' => $provider,
            'url' => 'https://example.com/cloud-file',
            'share_url' => 'https://example.com/share/cloud-file',
        );
    }

    /**
     * AJAX get export settings
     */
    public function ajax_get_export_settings() {
        check_ajax_referer('bkgt-export-nonce', 'nonce');

        wp_send_json_success($this->get_settings());
    }

    /**
     * AJAX save export settings
     */
    public function ajax_save_export_settings() {
        check_ajax_referer('bkgt-export-nonce', 'nonce');

        $this->save_settings($_POST);
        wp_send_json_success(__('Inställningar sparade!', 'bkgt-document-management'));
    }

    /**
     * AJAX get documents for export
     */
    public function ajax_get_documents_for_export() {
        check_ajax_referer('bkgt-export-nonce', 'nonce');

        $args = array(
            'post_type' => 'bkgt_document',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $documents = get_posts($args);
        $document_data = array();

        foreach ($documents as $doc) {
            $author = get_the_author_meta('display_name', $doc->post_author);
            $date = get_the_date('Y-m-d', $doc);

            // Get document type/category
            $categories = get_the_terms($doc->ID, 'bkgt_document_category');
            $type = $categories ? $categories[0]->name : __('Dokument', 'bkgt-document-management');

            $document_data[] = array(
                'ID' => $doc->ID,
                'post_title' => $doc->post_title,
                'post_date' => $date,
                'author' => $author,
                'type' => $type,
            );
        }

        wp_send_json_success(array('documents' => $document_data));
    }

    /**
     * AJAX preview document
     */
    public function ajax_preview_document() {
        check_ajax_referer('bkgt-export-nonce', 'nonce');

        $document_id = intval($_POST['document_id']);
        $document = get_post($document_id);

        if (!$document || $document->post_type !== 'bkgt_document') {
            wp_send_json_error(__('Ogiltigt dokument.', 'bkgt-document-management'));
        }

        // Generate preview HTML
        $content = apply_filters('the_content', $document->post_content);
        $preview = '<h1>' . esc_html($document->post_title) . '</h1>';
        $preview .= $content;

        wp_send_json_success(array('preview' => $preview));
    }
}