<?php
/**
 * Frontend Class - User-Facing Document Management
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Document_Frontend {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_ajax_bkgt_get_templates', array($this, 'ajax_get_templates'));
        add_action('wp_ajax_bkgt_create_from_template', array($this, 'ajax_create_from_template'));
        add_action('wp_ajax_bkgt_create_user_document', array($this, 'ajax_create_user_document'));
        add_action('wp_ajax_bkgt_get_user_documents', array($this, 'ajax_get_user_documents'));
        add_action('wp_ajax_bkgt_get_document', array($this, 'ajax_get_document'));
        add_action('wp_ajax_bkgt_edit_user_document', array($this, 'ajax_edit_user_document'));
        add_action('wp_ajax_bkgt_delete_user_document', array($this, 'ajax_delete_user_document'));
        add_action('wp_ajax_bkgt_download_document', array($this, 'ajax_download_document'));
        
        // New advanced features
        add_action('wp_ajax_bkgt_get_document_versions', array($this, 'ajax_get_document_versions'));
        add_action('wp_ajax_bkgt_restore_document_version', array($this, 'ajax_restore_document_version'));
        add_action('wp_ajax_bkgt_compare_versions', array($this, 'ajax_compare_versions'));
        add_action('wp_ajax_bkgt_export_document_format', array($this, 'ajax_export_document_format'));
        add_action('wp_ajax_bkgt_get_document_sharing', array($this, 'ajax_get_document_sharing'));
        add_action('wp_ajax_bkgt_get_document_viewer_html', array($this, 'ajax_get_document_viewer_html'));
        add_action('wp_ajax_bkgt_update_document_sharing', array($this, 'ajax_update_document_sharing'));
        add_action('wp_ajax_bkgt_search_documents_advanced', array($this, 'ajax_search_documents_advanced'));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        // Only load scripts if the shortcode is present on the page
        global $post;
        if (!is_user_logged_in() || !is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'bkgt_documents')) {
            return;
        }

        // Enqueue WordPress built-in dependencies
        wp_enqueue_style('dashicons');
        wp_enqueue_style('wp-jquery-ui-dialog');

        // Enqueue main plugin styles with dependencies
        wp_enqueue_style('bkgt-document-frontend', BKGT_DM_PLUGIN_URL . 'assets/css/frontend.css',
            array('dashicons', 'wp-jquery-ui-dialog'), BKGT_DM_VERSION);

        // Enqueue document viewer styles
        wp_enqueue_style('bkgt-document-viewer', BKGT_DM_PLUGIN_URL . 'assets/css/document-viewer.css', array(), BKGT_DM_VERSION);

        // Enqueue WordPress script dependencies
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('wp-util');

        // Enqueue PDF.js for document viewing
        wp_enqueue_script('pdfjs', 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js', array(), '3.11.174', true);

        // Enqueue main plugin scripts with dependencies
        wp_enqueue_script('bkgt-document-frontend', BKGT_DM_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'jquery-ui-dialog', 'wp-util'), BKGT_DM_VERSION, true);

        // Enqueue document viewer script
        wp_enqueue_script('bkgt-document-viewer', BKGT_DM_PLUGIN_URL . 'assets/js/document-viewer.js',
            array('jquery', 'pdfjs'), BKGT_DM_VERSION, true);

        wp_localize_script('bkgt-document-frontend', 'bkgtDocFrontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkgt_document_frontend'),
            'strings' => array(
                'new_document' => __('Nytt dokument', 'bkgt-document-management'),
                'from_template' => __('Från mall', 'bkgt-document-management'),
                'my_documents' => __('Mina dokument', 'bkgt-document-management'),
                'templates' => __('Mallar', 'bkgt-document-management'),
                'loading' => __('Laddar...', 'bkgt-document-management'),
                'error' => __('Något gick fel', 'bkgt-document-management'),
                'delete_confirm' => __('Är du säker på att du vill ta bort detta dokument?', 'bkgt-document-management'),
                'create_new' => __('Skapa nytt', 'bkgt-document-management'),
            )
        ));

        wp_localize_script('bkgt-document-viewer', 'bkgtDocViewer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkgt_document_viewer'),
            'strings' => array(
                'loading' => __('Laddar dokument...', 'bkgt-document-management'),
                'error' => __('Kunde inte ladda dokumentet', 'bkgt-document-management'),
                'download' => __('Ladda ner', 'bkgt-document-management'),
                'zoom_in' => __('Zooma in', 'bkgt-document-management'),
                'zoom_out' => __('Zooma ut', 'bkgt-document-management'),
                'previous' => __('Föregående', 'bkgt-document-management'),
                'next' => __('Nästa', 'bkgt-document-management'),
                'page' => __('Sida', 'bkgt-document-management'),
                'of' => __('av', 'bkgt-document-management'),
            )
        ));
    }

    /**
     * Render the document dashboard
     */
    public function render_dashboard() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Vänligen logga in för att komma åt dokumenthanteringen.', 'bkgt-document-management') . '</p>';
        }

        ob_start();
        ?>
        <input type="hidden" name="bkgt_document_nonce" value="<?php echo wp_create_nonce('bkgt_document_frontend'); ?>">
        
        <div class="bkgt-document-frontend-dashboard">
            <div class="bkgt-doc-header">
                <h2><?php _e('Dokumenthantering', 'bkgt-document-management'); ?></h2>
                <div class="bkgt-doc-actions">
                    <button class="button button-primary bkgt-create-document">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Skapa dokument', 'bkgt-document-management'); ?>
                    </button>
                    <button class="button bkgt-create-from-template">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Från mall', 'bkgt-document-management'); ?>
                    </button>
                </div>
            </div>

            <div class="bkgt-doc-content">
                <!-- Navigation Tabs -->
                <nav class="bkgt-doc-nav">
                    <button class="bkgt-doc-nav-item active" data-tab="my-documents">
                        <span class="dashicons dashicons-media-document"></span>
                        <?php _e('Mina dokument', 'bkgt-document-management'); ?>
                    </button>
                    <button class="bkgt-doc-nav-item" data-tab="search">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Avancerad sökning', 'bkgt-document-management'); ?>
                    </button>
                    <button class="bkgt-doc-nav-item" data-tab="templates">
                        <span class="dashicons dashicons-admin-page"></span>
                        <?php _e('Mallar', 'bkgt-document-management'); ?>
                    </button>
                </nav>

                <!-- Tab Content -->
                <div class="bkgt-doc-tabs">
                    <!-- My Documents Tab -->
                    <div class="bkgt-doc-tab active" id="my-documents">
                        <div class="bkgt-doc-section-header">
                            <h3><?php _e('Mina dokument', 'bkgt-document-management'); ?></h3>
                        </div>
                        <div class="bkgt-doc-list-header">
                            <div class="bkgt-doc-filters">
                                <input type="search" class="bkgt-doc-search" placeholder="<?php _e('Sök dokument...', 'bkgt-document-management'); ?>">
                                <select class="bkgt-doc-sort">
                                    <option value="date_desc"><?php _e('Nyast först', 'bkgt-document-management'); ?></option>
                                    <option value="date_asc"><?php _e('Äldst först', 'bkgt-document-management'); ?></option>
                                    <option value="title"><?php _e('Titel (A-Z)', 'bkgt-document-management'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="bkgt-doc-list" id="my-documents-list">
                            <p class="bkgt-doc-loading"><?php _e('Laddar...', 'bkgt-document-management'); ?></p>
                        </div>
                    </div>

                    <!-- Advanced Search Tab -->
                    <div class="bkgt-doc-tab" id="search">
                        <div class="bkgt-doc-search-header">
                            <h3><?php _e('Avancerad sökning', 'bkgt-document-management'); ?></h3>
                        </div>
                        <div class="bkgt-doc-search-form">
                            <div class="bkgt-form-row">
                                <div class="bkgt-form-col">
                                    <label for="search-query"><?php _e('Sökterm', 'bkgt-document-management'); ?></label>
                                    <input type="text" id="search-query" class="bkgt-form-control" placeholder="<?php _e('Sök efter titel eller innehål...', 'bkgt-document-management'); ?>">
                                </div>
                                <div class="bkgt-form-col">
                                    <label for="search-template"><?php _e('Mall', 'bkgt-document-management'); ?></label>
                                    <select id="search-template" class="bkgt-form-control">
                                        <option value=""><?php _e('-- Alla mallar --', 'bkgt-document-management'); ?></option>
                                        <option value="meeting-minutes"><?php _e('Mötesprotokolll', 'bkgt-document-management'); ?></option>
                                        <option value="report-template"><?php _e('Rapport', 'bkgt-document-management'); ?></option>
                                        <option value="letter-template"><?php _e('Brev', 'bkgt-document-management'); ?></option>
                                        <option value="markdown-document"><?php _e('Markdown Dokument', 'bkgt-document-management'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="bkgt-form-row">
                                <div class="bkgt-form-col">
                                    <label for="search-date-from"><?php _e('Från datum', 'bkgt-document-management'); ?></label>
                                    <input type="date" id="search-date-from" class="bkgt-form-control">
                                </div>
                                <div class="bkgt-form-col">
                                    <label for="search-date-to"><?php _e('Till datum', 'bkgt-document-management'); ?></label>
                                    <input type="date" id="search-date-to" class="bkgt-form-control">
                                </div>
                            </div>
                            <div class="bkgt-form-row">
                                <div class="bkgt-form-col">
                                    <label for="search-sort"><?php _e('Sortering', 'bkgt-document-management'); ?></label>
                                    <select id="search-sort" class="bkgt-form-control">
                                        <option value="date"><?php _e('Nyast först', 'bkgt-document-management'); ?></option>
                                        <option value="date_asc"><?php _e('Äldst först', 'bkgt-document-management'); ?></option>
                                        <option value="title"><?php _e('Titel (A-Z)', 'bkgt-document-management'); ?></option>
                                    </select>
                                </div>
                                <div class="bkgt-form-col bkgt-button-col">
                                    <button class="button button-primary bkgt-search-submit"><?php _e('Sök', 'bkgt-document-management'); ?></button>
                                </div>
                            </div>
                        </div>
                        <div class="bkgt-search-results" id="search-results">
                            <!-- Results will be displayed here -->
                        </div>
                    </div>

                    <!-- Templates Tab -->
                    <div class="bkgt-doc-tab" id="templates">
                        <div class="bkgt-doc-section-header">
                            <h3><?php _e('Tillgängliga mallar', 'bkgt-document-management'); ?></h3>
                        </div>
                        <p class="bkgt-doc-description">
                            <?php _e('Skapa nya dokument baserat på dessa mallar. Mallarna innehåller fördefinierade format och variabler som fylls i automatiskt.', 'bkgt-document-management'); ?>
                        </p>
                        <div class="bkgt-doc-templates" id="templates-list">
                            <p class="bkgt-doc-loading"><?php _e('Laddar...', 'bkgt-document-management'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create from Template Modal -->
            <div class="bkgt-doc-modal" id="template-modal">
                <div class="bkgt-doc-modal-content">
                    <div class="bkgt-doc-modal-header">
                        <h3><?php _e('Skapa dokument från mall', 'bkgt-document-management'); ?></h3>
                        <button class="bkgt-doc-modal-close">&times;</button>
                    </div>
                    <div class="bkgt-doc-modal-body">
                        <form id="template-create-form">
                            <div class="bkgt-form-group">
                                <label for="template-select"><?php _e('Välj mall', 'bkgt-document-management'); ?></label>
                                <select id="template-select" required>
                                    <option value=""><?php _e('-- Välj en mall --', 'bkgt-document-management'); ?></option>
                                </select>
                            </div>
                            <div class="bkgt-form-group">
                                <label for="document-title"><?php _e('Dokumenttitel', 'bkgt-document-management'); ?></label>
                                <input type="text" id="document-title" placeholder="<?php _e('t.ex. Mötesprotokolll 2025-11', 'bkgt-document-management'); ?>" required>
                            </div>
                            <div id="template-variables"></div>
                        </form>
                    </div>
                    <div class="bkgt-doc-modal-footer">
                        <button class="button" id="template-modal-close"><?php _e('Avbryt', 'bkgt-document-management'); ?></button>
                        <button class="button button-primary" id="template-create-submit"><?php _e('Skapa dokument', 'bkgt-document-management'); ?></button>
                    </div>
                </div>
            </div>

            <!-- Document Detail Modal -->
            <div class="bkgt-doc-modal" id="document-detail-modal">
                <div class="bkgt-doc-modal-content bkgt-large">
                    <div class="bkgt-doc-modal-header">
                        <h3 id="detail-title"></h3>
                        <button class="bkgt-doc-modal-close">&times;</button>
                    </div>
                    <div class="bkgt-doc-modal-body bkgt-detail-tabs">
                        <div class="bkgt-detail-tab-nav">
                            <button class="bkgt-detail-tab-btn active" data-tab="content"><?php _e('Innehål', 'bkgt-document-management'); ?></button>
                            <button class="bkgt-detail-tab-btn" data-tab="viewer"><?php _e('Visa dokument', 'bkgt-document-management'); ?></button>
                            <button class="bkgt-detail-tab-btn" data-tab="versions"><?php _e('Versioner', 'bkgt-document-management'); ?></button>
                            <button class="bkgt-detail-tab-btn" data-tab="sharing"><?php _e('Delning', 'bkgt-document-management'); ?></button>
                            <button class="bkgt-detail-tab-btn" data-tab="export"><?php _e('Exportera', 'bkgt-document-management'); ?></button>
                        </div>
                        <div class="bkgt-detail-content">
                            <div class="bkgt-detail-pane active" data-pane="content" id="detail-content"></div>
                            <div class="bkgt-detail-pane" data-pane="viewer" id="detail-viewer"></div>
                            <div class="bkgt-detail-pane" data-pane="versions" id="detail-versions"></div>
                            <div class="bkgt-detail-pane" data-pane="sharing" id="detail-sharing"></div>
                            <div class="bkgt-detail-pane" data-pane="export" id="detail-export"></div>
                        </div>
                    </div>
                    <div class="bkgt-doc-modal-footer">
                        <button class="button" id="detail-close"><?php _e('Stäng', 'bkgt-document-management'); ?></button>
                        <button class="button button-primary" id="detail-edit"><?php _e('Redigera', 'bkgt-document-management'); ?></button>
                        <button class="button bkgt-button-danger" id="detail-delete"><?php _e('Ta bort', 'bkgt-document-management'); ?></button>
                    </div>
                </div>
            </div>

            <!-- Edit Document Modal -->
            <div class="bkgt-doc-modal" id="edit-modal">
                <div class="bkgt-doc-modal-content bkgt-large">
                    <div class="bkgt-doc-modal-header">
                        <h3><?php _e('Redigera dokument', 'bkgt-document-management'); ?></h3>
                        <button class="bkgt-doc-modal-close">&times;</button>
                    </div>
                    <div class="bkgt-doc-modal-body">
                        <form id="edit-document-form">
                            <input type="hidden" id="edit-post-id">
                            <div class="bkgt-form-group">
                                <label for="edit-title"><?php _e('Dokumenttitel', 'bkgt-document-management'); ?></label>
                                <input type="text" id="edit-title" required>
                            </div>
                            <div class="bkgt-form-group">
                                <label for="edit-content"><?php _e('Innehål', 'bkgt-document-management'); ?></label>
                                <textarea id="edit-content" rows="15" required></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="bkgt-doc-modal-footer">
                        <button class="button" id="edit-cancel"><?php _e('Avbryt', 'bkgt-document-management'); ?></button>
                        <button class="button button-primary" id="edit-save"><?php _e('Spara ändringar', 'bkgt-document-management'); ?></button>
                    </div>
                </div>
            </div>

            <!-- Document Editor Modal -->
            <div class="bkgt-doc-modal" id="document-editor-modal">
                <div class="bkgt-doc-modal-content bkgt-large">
                    <div class="bkgt-doc-modal-header">
                        <h3><?php _e('Skapa nytt dokument', 'bkgt-document-management'); ?></h3>
                        <button class="bkgt-doc-modal-close">&times;</button>
                    </div>
                    <div class="bkgt-doc-modal-body">
                        <form id="document-editor-form">
                            <div class="bkgt-form-group">
                                <label for="document-editor-title"><?php _e('Dokumenttitel', 'bkgt-document-management'); ?></label>
                                <input type="text" id="document-editor-title" placeholder="<?php _e('Ange en titel för dokumentet', 'bkgt-document-management'); ?>" required>
                            </div>
                            <div class="bkgt-form-group">
                                <label for="document-editor-content"><?php _e('Innehål', 'bkgt-document-management'); ?></label>
                                <textarea id="document-editor-content" rows="20" placeholder="<?php _e('Skriv ditt dokument här. Du kan använda Markdown-syntax för formatering.', 'bkgt-document-management'); ?>" required></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="bkgt-doc-modal-footer">
                        <button class="button" id="document-editor-cancel"><?php _e('Avbryt', 'bkgt-document-management'); ?></button>
                        <button class="button button-primary" id="document-editor-save"><?php _e('Spara dokument', 'bkgt-document-management'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get templates via AJAX
     */
    public function ajax_get_templates() {
        check_ajax_referer('bkgt_document_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-document-management'), 403);
        }

        // Use the proper template system
        $template_system = new BKGT_DM_Template_System();
        $templates = $template_system->get_templates();

        // If no templates in database, return default templates
        if (empty($templates)) {
            $templates = $this->get_default_templates();
        } else {
            // Format database templates to match frontend expectations
            $formatted_templates = array();
            foreach ($templates as $template) {
                $formatted_templates[] = array(
                    'id' => $template['template_slug'],
                    'name' => $template['template_name'],
                    'description' => $template['description'],
                    'content' => $template['template_content'],
                    'variables' => $this->format_template_variables($template['variables_used']),
                    'category' => $template['category'],
                    'created_by' => $template['created_by'],
                    'created_date' => $template['created_date']
                );
            }
            $templates = $formatted_templates;
        }

        wp_send_json_success(array(
            'templates' => $templates,
            'count' => count($templates),
        ));
    }

    /**
     * Format template variables for frontend
     */
    private function format_template_variables($variables_used) {
        if (!is_array($variables_used)) {
            return array();
        }

        $formatted = array();
        $template_system = new BKGT_DM_Template_System();
        $available_vars = $template_system->get_available_variables();

        foreach ($variables_used as $var_key) {
            if (isset($available_vars[$var_key])) {
                $formatted[] = array(
                    'name' => $var_key,
                    'label' => $this->get_variable_label($var_key)
                );
            }
        }

        return $formatted;
    }

    /**
     * Get human-readable label for variable
     */
    private function get_variable_label($variable) {
        $labels = array(
            '{{SPELARE_NAMN}}' => __('Spelarens namn', 'bkgt-document-management'),
            '{{SPELARE_EFTERNAMN}}' => __('Spelarens efternamn', 'bkgt-document-management'),
            '{{SPELARE_FODELSEDATUM}}' => __('Spelarens födelsedatum', 'bkgt-document-management'),
            '{{SPELARE_LAG}}' => __('Spelarens lag', 'bkgt-document-management'),
            '{{SPELARE_POSITION}}' => __('Spelarens position', 'bkgt-document-management'),
            '{{TRÄNARE_NAMN}}' => __('Tränarens namn', 'bkgt-document-management'),
            '{{TRÄNARE_EFTERNAMN}}' => __('Tränarens efternamn', 'bkgt-document-management'),
            '{{TRÄNARE_LAG}}' => __('Tränarens lag', 'bkgt-document-management'),
            '{{UTFÄRDANDE_DATUM}}' => __('Utfärdandedatum', 'bkgt-document-management'),
            '{{UTFÄRDANDE_ÅR}}' => __('Utfärdandeår', 'bkgt-document-management'),
            '{{DOKUMENT_TITEL}}' => __('Dokumenttitel', 'bkgt-document-management'),
            '{{DOKUMENT_NUMMER}}' => __('Dokumentnummer', 'bkgt-document-management'),
            '{{KLUBB_NAMN}}' => __('Klubbnamn', 'bkgt-document-management'),
            '{{KLUBB_ADRESS}}' => __('Klubbadress', 'bkgt-document-management'),
            '{{KLUBB_TELEFON}}' => __('Klubbtelefon', 'bkgt-document-management'),
            '{{KLUBB_EPOST}}' => __('Klubbens e-post', 'bkgt-document-management'),
            '{{UTRUSTNING_LISTA}}' => __('Utrustningslista', 'bkgt-document-management'),
            '{{UTRUSTNING_ÅTERLÄMNINGSDATUM}}' => __('Återlämningsdatum för utrustning', 'bkgt-document-management'),
            '{{PERSON_NAMN}}' => __('Personens namn', 'bkgt-document-management'),
            '{{PERSON_EFTERNAMN}}' => __('Personens efternamn', 'bkgt-document-management'),
            '{{SLUTDATUM}}' => __('Slutdatum', 'bkgt-document-management'),
            '{{AVSLUTANDE_ANSTÄLLNING}}' => __('Anledning till avslutande av anställning', 'bkgt-document-management'),
        );

        return $labels[$variable] ?? $variable;
    }

    /**
     * Get default templates
     */
    private function get_default_templates() {
        return array(
            array(
                'id' => 'meeting-minutes',
                'name' => __('Mötesprotokolll', 'bkgt-document-management'),
                'description' => __('Standard møtesprotokolll för möten', 'bkgt-document-management'),
                'content' => $this->get_template_content('meeting-minutes'),
                'variables' => array(
                    array('name' => '{{MEETING_DATE}}', 'label' => __('Mötesdatum', 'bkgt-document-management')),
                    array('name' => '{{MEETING_TITLE}}', 'label' => __('Mötets titel', 'bkgt-document-management')),
                    array('name' => '{{PARTICIPANTS}}', 'label' => __('Deltagare', 'bkgt-document-management')),
                )
            ),
            array(
                'id' => 'report-template',
                'name' => __('Rapport', 'bkgt-document-management'),
                'description' => __('Allmän rapportmall', 'bkgt-document-management'),
                'content' => $this->get_template_content('report'),
                'variables' => array(
                    array('name' => '{{REPORT_TITLE}}', 'label' => __('Rapporttitel', 'bkgt-document-management')),
                    array('name' => '{{REPORT_DATE}}', 'label' => __('Rapportdatum', 'bkgt-document-management')),
                    array('name' => '{{AUTHOR}}', 'label' => __('Författare', 'bkgt-document-management')),
                )
            ),
            array(
                'id' => 'letter-template',
                'name' => __('Brev', 'bkgt-document-management'),
                'description' => __('Standard brevmall', 'bkgt-document-management'),
                'content' => $this->get_template_content('letter'),
                'variables' => array(
                    array('name' => '{{RECIPIENT_NAME}}', 'label' => __('Mottagarens namn', 'bkgt-document-management')),
                    array('name' => '{{LETTER_DATE}}', 'label' => __('Brevdatum', 'bkgt-document-management')),
                )
            ),
            array(
                'id' => 'markdown-document',
                'name' => __('Markdown Dokument', 'bkgt-document-management'),
                'description' => __('Skapa ett nytt markdown-dokument', 'bkgt-document-management'),
                'content' => $this->get_template_content('markdown-document'),
                'variables' => array(
                    array('name' => '{{TITLE}}', 'label' => __('Dokumenttitel', 'bkgt-document-management')),
                    array('name' => '{{AUTHOR}}', 'label' => __('Författare', 'bkgt-document-management')),
                    array('name' => '{{DATE}}', 'label' => __('Datum', 'bkgt-document-management')),
                )
            ),
        );
    }

    /**
     * Get template content
     */
    private function get_template_content($template_type) {
        switch ($template_type) {
            case 'meeting-minutes':
                return "# {{MEETING_TITLE}}\n\n**Datum:** {{MEETING_DATE}}\n\n**Deltagare:** {{PARTICIPANTS}}\n\n## Dagordning\n\n1. \n\n## Diskussioner\n\n## Beslut\n\n## Nästa möte\n";
            case 'report':
                return "# {{REPORT_TITLE}}\n\n**Datum:** {{REPORT_DATE}}\n**Författare:** {{AUTHOR}}\n\n## Sammanfattning\n\n## Bakgrund\n\n## Resultat\n\n## Slutsats\n";
            case 'letter':
                return "BKGT Amerikansk Fotboll\n\n{{LETTER_DATE}}\n\n**Till:** {{RECIPIENT_NAME}}\n\n---\n\n\n\nMed vänlig hälsning,\n\nBKGT Amerikansk Fotboll";
            case 'markdown-document':
                return "# {{TITLE}}\n\n**Författare:** {{AUTHOR}}\n**Datum:** {{DATE}}\n\n## Inledning\n\n## Huvudinnehåll\n\n## Slutsats\n";
            default:
                return '';
        }
    }

    /**
     * Create document from template via AJAX
     */
    public function ajax_create_from_template() {
        check_ajax_referer('bkgt_document_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-document-management'), 403);
        }

        $template_id = sanitize_text_field($_POST['template_id'] ?? '');
        $document_title = sanitize_text_field($_POST['document_title'] ?? '');
        $variables = isset($_POST['variables']) ? array_map('sanitize_text_field', $_POST['variables']) : array();

        if (empty($template_id) || empty($document_title)) {
            wp_send_json_error(__('Mall och titel krävs', 'bkgt-document-management'), 400);
        }

        // Get template content
        $templates = $this->get_default_templates();
        $template = null;
        foreach ($templates as $t) {
            if ($t['id'] === $template_id) {
                $template = $t;
                break;
            }
        }

        if (!$template) {
            wp_send_json_error(__('Mallen hittades inte', 'bkgt-document-management'), 404);
        }

        // Replace variables in content
        $content = $template['content'];
        foreach ($variables as $var_name => $var_value) {
            $content = str_replace($var_name, $var_value, $content);
        }

        // Create new document post
        $post_data = array(
            'post_type' => 'bkgt_document',
            'post_status' => 'publish',
            'post_title' => $document_title,
            'post_content' => $content,
            'post_author' => get_current_user_id(),
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            wp_send_json_error(__('Det gick inte att skapa dokumentet', 'bkgt-document-management'), 500);
        }

        // Store template source
        update_post_meta($post_id, '_bkgt_template_source', $template_id);
        update_post_meta($post_id, '_bkgt_created_by_user', get_current_user_id());

        wp_send_json_success(array(
            'message' => __('Dokument skapat framgångsrikt', 'bkgt-document-management'),
            'post_id' => $post_id,
            'edit_url' => get_edit_post_link($post_id, 'raw'),
        ));
    }

    /**
     * Create user document via AJAX
     */
    public function ajax_create_user_document() {
        check_ajax_referer('bkgt_document_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-document-management'), 403);
        }

        $title = sanitize_text_field($_POST['title'] ?? '');
        $content = wp_kses_post($_POST['content'] ?? '');

        if (empty($title) || empty($content)) {
            wp_send_json_error(__('Titel och innehål är obligatoriska', 'bkgt-document-management'), 400);
        }

        // Create new document post
        $post_data = array(
            'post_type' => 'bkgt_document',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_content' => $content,
            'post_author' => get_current_user_id(),
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            wp_send_json_error(__('Det gick inte att skapa dokumentet', 'bkgt-document-management'), 500);
        }

        // Mark as user-created (not from template)
        update_post_meta($post_id, '_bkgt_created_by_user', get_current_user_id());

        wp_send_json_success(array(
            'message' => __('Dokument skapat framgångsrikt', 'bkgt-document-management'),
            'post_id' => $post_id,
        ));
    }

    /**
     * Get user's documents via AJAX
     */
    public function ajax_get_user_documents() {
        check_ajax_referer('bkgt_document_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-document-management'), 403);
        }

        $user_id = get_current_user_id();
        error_log('BKGT DM: Getting documents for user ' . $user_id);

        $query = new WP_Query(array(
            'post_type' => 'bkgt_document',
            'author' => $user_id,
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'publish',
        ));

        if (is_wp_error($query)) {
            error_log('BKGT DM: WP_Query error - ' . $query->get_error_message());
            wp_send_json_error(__('Fel vid hämtning av dokument', 'bkgt-document-management'));
        }

        $documents = $query->get_posts();
        error_log('BKGT DM: Found ' . count($documents) . ' documents');

        $formatted_docs = array();
        foreach ($documents as $doc) {
            $formatted_docs[] = array(
                'id' => $doc->ID,
                'title' => $doc->post_title,
                'date' => get_the_date('Y-m-d', $doc->ID),
                'date_formatted' => get_the_date('j M Y', $doc->ID),
                'url' => get_permalink($doc->ID),
                'edit_url' => get_edit_post_link($doc->ID, 'raw'),
                'template_source' => get_post_meta($doc->ID, '_bkgt_template_source', true),
            );
        }

        error_log('BKGT DM: Returning ' . count($formatted_docs) . ' formatted documents');
        wp_send_json_success(array(
            'documents' => $formatted_docs,
            'count' => count($formatted_docs),
        ));
    }

    /**
     * Get single document via AJAX
     */
    public function ajax_get_document() {
        check_ajax_referer('bkgt_document_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-document-management'), 403);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'bkgt_document' || $post->post_author != get_current_user_id()) {
            wp_send_json_error(__('Dokumentet hittades inte eller du har inte behörighet', 'bkgt-document-management'), 403);
        }

        wp_send_json_success(array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'date' => get_the_date('Y-m-d H:i', $post->ID),
            'author' => get_the_author_meta('display_name', $post->post_author),
        ));
    }

    /**
     * Edit user document via AJAX
     */
    public function ajax_edit_user_document() {
        check_ajax_referer('bkgt_document_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-document-management'), 403);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $new_title = sanitize_text_field($_POST['title'] ?? '');
        $new_content = wp_kses_post($_POST['content'] ?? '');

        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'bkgt_document') {
            wp_send_json_error(__('Dokumentet hittades inte', 'bkgt-document-management'), 404);
        }

        // Check if user can edit
        $current_user_id = get_current_user_id();
        $can_edit = false;

        // Document author can always edit their own
        if ($post->post_author == $current_user_id) {
            $can_edit = true;
        }

        // Check role-based access
        if (!$can_edit) {
            // Check if user has edit_documents capability
            if (function_exists('bkgt_can')) {
                if (bkgt_can('edit_documents')) {
                    // For coaches/team managers, verify it's their team
                    $user_team = get_user_meta($current_user_id, 'bkgt_team_id', true);
                    $doc_team = get_post_meta($post_id, '_bkgt_team_id', true);

                    if ($user_team && $doc_team && $user_team == $doc_team) {
                        $can_edit = true;
                    } elseif (!$doc_team) {
                        // If document has no team assignment, allow edit for those with capability
                        $can_edit = true;
                    }
                }
            }
        }

        if (!$can_edit) {
            wp_send_json_error(__('Du har inte behörighet att redigera detta dokument', 'bkgt-document-management'), 403);
        }

        // Update the document
        $update_result = wp_update_post(array(
            'ID' => $post_id,
            'post_title' => $new_title,
            'post_content' => $new_content,
            'post_modified' => current_time('mysql'),
            'post_modified_gmt' => current_time('mysql', true),
        ));

        if (is_wp_error($update_result)) {
            wp_send_json_error(__('Misslyckades att uppdatera dokumentet', 'bkgt-document-management'));
        }

        wp_send_json_success(array(
            'message' => __('Dokumentet har uppdaterats', 'bkgt-document-management'),
            'post_id' => $post_id,
        ));
    }

    /**
     * Delete user document via AJAX
     */
    public function ajax_delete_user_document() {
        check_ajax_referer('bkgt_document_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-document-management'), 403);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'bkgt_document') {
            wp_send_json_error(__('Dokumentet hittades inte', 'bkgt-document-management'), 404);
        }

        // Only document author can delete (admins can delete via admin panel)
        $current_user_id = get_current_user_id();
        if ($post->post_author != $current_user_id) {
            wp_send_json_error(__('Du har inte behörighet att ta bort detta dokument', 'bkgt-document-management'), 403);
        }

        wp_delete_post($post_id, true);

        wp_send_json_success(array(
            'message' => __('Dokumentet har tagits bort', 'bkgt-document-management'),
        ));
    }

    /**
     * Download document
     */
    public function ajax_download_document() {
        check_ajax_referer('bkgt_document_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-document-management'), 403);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'bkgt_document' || $post->post_author != get_current_user_id()) {
            wp_send_json_error(__('Dokumentet hittades inte eller du har inte behörighet', 'bkgt-document-management'), 403);
        }

        // Generate download (as text for now, could be extended to DOCX/PDF)
        wp_send_json_success(array(
            'title' => $post->post_title,
            'content' => $post->post_content,
            'download_url' => admin_url('admin-ajax.php?action=bkgt_export_document&post_id=' . $post_id . '&nonce=' . wp_create_nonce('download_' . $post_id)),
        ));
    }

    /**
     * Get document version history
     */
    public function ajax_get_document_versions() {
        check_ajax_referer('bkgt_document_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-document-management'), 403);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'bkgt_document' || $post->post_author != get_current_user_id()) {
            wp_send_json_error(__('Dokumentet hittades inte eller du har inte behörighet', 'bkgt-document-management'), 403);
        }

        // Get revisions
        $revisions = wp_get_post_revisions($post_id, array('posts_per_page' => 20));

        $versions = array();
        foreach ($revisions as $revision) {
            $versions[] = array(
                'id' => $revision->ID,
                'date' => get_the_date('Y-m-d H:i', $revision->ID),
                'author' => get_the_author_meta('display_name', $revision->post_author),
                'title' => $revision->post_title,
                'excerpt' => wp_trim_words($revision->post_content, 20),
            );
        }

        // Add current version
        array_unshift($versions, array(
            'id' => $post->ID,
            'date' => get_the_date('Y-m-d H:i', $post->ID),
            'author' => get_the_author_meta('display_name', $post->post_author),
            'title' => $post->post_title,
            'excerpt' => wp_trim_words($post->post_content, 20),
            'current' => true,
        ));

        wp_send_json_success(array('versions' => $versions));
    }

    /**
     * Restore a document version
     */
    public function ajax_restore_document_version() {
        check_ajax_referer('bkgt_document_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-document-management'), 403);
        }

        $version_id = intval($_POST['version_id'] ?? 0);
        $revision = get_post($version_id);

        if (!$revision) {
            wp_send_json_error(__('Versionen hittades inte', 'bkgt-document-management'), 404);
        }

        $post = get_post($revision->post_parent);
        if (!$post || $post->post_author != get_current_user_id()) {
            wp_send_json_error(__('Du har inte behörighet att återställa denna version', 'bkgt-document-management'), 403);
        }

        // Restore the revision
        wp_restore_post_revision($version_id);

        wp_send_json_success(array('message' => __('Versionen har återställts', 'bkgt-document-management')));
    }

    /**
     * Compare two document versions
     */
    public function ajax_compare_versions() {
        check_ajax_referer('bkgt_document_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-document-management'), 403);
        }

        $version1_id = intval($_POST['version1_id'] ?? 0);
        $version2_id = intval($_POST['version2_id'] ?? 0);

        $version1 = get_post($version1_id);
        $version2 = get_post($version2_id);

        if (!$version1 || !$version2) {
            wp_send_json_error(__('En eller båda versionerna hittades inte', 'bkgt-document-management'), 404);
        }

        $post = get_post($version1->post_parent ?: $version1->ID);
        if (!$post || $post->post_author != get_current_user_id()) {
            wp_send_json_error(__('Du har inte behörighet att jämföra dessa versioner', 'bkgt-document-management'), 403);
        }

        wp_send_json_success(array(
            'version1' => array(
                'title' => $version1->post_title,
                'content' => $version1->post_content,
                'date' => get_the_date('Y-m-d H:i', $version1->ID),
            ),
            'version2' => array(
                'title' => $version2->post_title,
                'content' => $version2->post_content,
                'date' => get_the_date('Y-m-d H:i', $version2->ID),
            ),
        ));
    }

    /**
     * Export document in various formats
     */
    public function ajax_export_document_format() {
        check_ajax_referer('bkgt_document_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-document-management'), 403);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $format = sanitize_text_field($_POST['format'] ?? 'txt');

        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'bkgt_document' || $post->post_author != get_current_user_id()) {
            wp_send_json_error(__('Dokumentet hittades inte eller du har inte behörighet', 'bkgt-document-management'), 403);
        }

        $filename = sanitize_file_name($post->post_title . '.' . $format);
        $content = $post->post_content;

        // Create download link based on format
        $download_url = add_query_arg(array(
            'action' => 'bkgt_generate_export',
            'post_id' => $post_id,
            'format' => $format,
            'nonce' => wp_create_nonce('export_' . $post_id),
        ), admin_url('admin-ajax.php'));

        wp_send_json_success(array(
            'filename' => $filename,
            'download_url' => $download_url,
            'formats' => array(
                'txt' => __('Text (.txt)', 'bkgt-document-management'),
                'md' => __('Markdown (.md)', 'bkgt-document-management'),
                'html' => __('HTML (.html)', 'bkgt-document-management'),
            ),
        ));
    }

    /**
     * Get document sharing/permissions
     */
    public function ajax_get_document_sharing() {
        check_ajax_referer('bkgt_document_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-document-management'), 403);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'bkgt_document' || $post->post_author != get_current_user_id()) {
            wp_send_json_error(__('Dokumentet hittades inte eller du har inte behörighet', 'bkgt-document-management'), 403);
        }

        $shared_with = get_post_meta($post_id, '_bkgt_shared_with', true);
        if (!is_array($shared_with)) {
            $shared_with = array();
        }

        $shares = array();
        foreach ($shared_with as $user_id => $permission) {
            $user = get_userdata($user_id);
            if ($user) {
                $shares[] = array(
                    'user_id' => $user_id,
                    'name' => $user->display_name,
                    'email' => $user->user_email,
                    'permission' => $permission, // 'view' or 'edit'
                );
            }
        }

        wp_send_json_success(array('shares' => $shares));
    }

    /**
     * Update document sharing/permissions
     */
    public function ajax_update_document_sharing() {
        check_ajax_referer('bkgt_document_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-document-management'), 403);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $action = sanitize_text_field($_POST['share_action'] ?? 'add'); // add or remove
        $user_id = intval($_POST['user_id'] ?? 0);
        $permission = sanitize_text_field($_POST['permission'] ?? 'view'); // view or edit

        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'bkgt_document' || $post->post_author != get_current_user_id()) {
            wp_send_json_error(__('Dokumentet hittades inte eller du har inte behörighet', 'bkgt-document-management'), 403);
        }

        $shared_with = get_post_meta($post_id, '_bkgt_shared_with', true);
        if (!is_array($shared_with)) {
            $shared_with = array();
        }

        if ($action === 'add') {
            $shared_with[$user_id] = in_array($permission, array('view', 'edit')) ? $permission : 'view';
        } elseif ($action === 'remove') {
            unset($shared_with[$user_id]);
        }

        update_post_meta($post_id, '_bkgt_shared_with', $shared_with);

        wp_send_json_success(array('message' => __('Delningsinställningar uppdaterade', 'bkgt-document-management')));
    }

    /**
     * Advanced search with filters
     */
    public function ajax_search_documents_advanced() {
        check_ajax_referer('bkgt_document_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-document-management'), 403);
        }

        $search_query = sanitize_text_field($_POST['search'] ?? '');
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');
        $template_filter = sanitize_text_field($_POST['template'] ?? '');
        $sort_by = sanitize_text_field($_POST['sort'] ?? 'date');

        $user_id = get_current_user_id();

        // Build query
        $args = array(
            'post_type' => 'bkgt_document',
            'author' => $user_id,
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        if (!empty($search_query)) {
            $args['s'] = $search_query;
        }

        if (!empty($sort_by)) {
            if ($sort_by === 'title') {
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
            } elseif ($sort_by === 'date_asc') {
                $args['order'] = 'ASC';
            }
        }

        $query = new WP_Query($args);
        $documents = $query->posts;

        // Filter by date range if provided
        if (!empty($date_from) || !empty($date_to)) {
            $documents = array_filter($documents, function($doc) use ($date_from, $date_to) {
                $doc_date = strtotime($doc->post_date);
                if (!empty($date_from) && $doc_date < strtotime($date_from)) {
                    return false;
                }
                if (!empty($date_to) && $doc_date > strtotime($date_to . ' 23:59:59')) {
                    return false;
                }
                return true;
            });
        }

        // Filter by template if provided
        if (!empty($template_filter)) {
            $documents = array_filter($documents, function($doc) use ($template_filter) {
                $template = get_post_meta($doc->ID, '_bkgt_template_source', true);
                return $template === $template_filter;
            });
        }

        $formatted_docs = array();
        foreach ($documents as $doc) {
            $formatted_docs[] = array(
                'id' => $doc->ID,
                'title' => $doc->post_title,
                'date' => get_the_date('Y-m-d', $doc->ID),
                'date_formatted' => get_the_date('j M Y', $doc->ID),
                'template' => get_post_meta($doc->ID, '_bkgt_template_source', true),
            );
        }

        wp_send_json_success(array(
            'documents' => $formatted_docs,
            'count' => count($formatted_docs),
        ));
    }

    /**
     * AJAX handler for getting document viewer HTML
     */
    public function ajax_get_document_viewer_html() {
        check_ajax_referer('bkgt_document_frontend', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-document-management'), 403);
        }

        $document_id = intval($_POST['document_id']);

        if (!$document_id) {
            wp_send_json_error(__('Ogiltigt dokument-ID', 'bkgt-document-management'));
        }

        // Check if user can view this document
        if (!$this->user_can_view_document($document_id)) {
            wp_send_json_error(__('Du har inte behörighet att visa detta dokument', 'bkgt-document-management'), 403);
        }

        // Get the document viewer HTML
        $viewer_html = do_shortcode('[bkgt_document_viewer id="' . $document_id . '" width="100%" height="500px"]');

        wp_send_json_success(array(
            'html' => $viewer_html,
        ));
    }

    /**
     * Check if user can view a document
     */
    private function user_can_view_document($document_id) {
        $document = get_post($document_id);

        if (!$document || $document->post_type !== 'bkgt_document') {
            return false;
        }

        // Allow if user is the author
        if (get_current_user_id() === $document->post_author) {
            return true;
        }

        // Add additional permission checks here (sharing, roles, etc.)
        return false;
    }
}
