<?php
/**
 * Admin Class
 *
 * @package BKGT_Document_Management
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Document_Admin {

    /**
     * Template system instance
     */
    private $template_system;

    /**
     * Constructor
     */
    public function __construct() {
        $this->template_system = new BKGT_DM_Template_System();
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_filter('manage_bkgt_document_posts_columns', array($this, 'add_document_columns'));
        add_action('manage_bkgt_document_posts_custom_column', array($this, 'fill_document_columns'), 10, 2);
        add_filter('manage_edit-bkgt_document_sortable_columns', array($this, 'make_columns_sortable'));
        add_action('wp_ajax_bkgt_upload_document', array($this, 'ajax_upload_document'));
        add_action('wp_ajax_bkgt_create_document', array($this, 'ajax_create_document'));
        add_action('wp_ajax_bkgt_delete_document', array($this, 'ajax_delete_document'));
        add_action('wp_ajax_bkgt_get_document_versions', array($this, 'ajax_get_document_versions'));
        add_action('wp_ajax_bkgt_restore_version', array($this, 'ajax_restore_version'));
        add_action('wp_ajax_bkgt_manage_access', array($this, 'ajax_manage_access'));
        add_action('wp_ajax_bkgt_save_template', array($this, 'ajax_save_template'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Dokumenthantering', 'bkgt-document-management'),
            __('Dokument', 'bkgt-document-management'),
            'manage_options',
            'bkgt-documents',
            array($this, 'admin_page'),
            'dashicons-media-document',
            30
        );

        add_submenu_page(
            'bkgt-documents',
            __('Kategorier', 'bkgt-document-management'),
            __('Kategorier', 'bkgt-document-management'),
            'manage_categories',
            'edit-tags.php?taxonomy=bkgt_doc_category&post_type=bkgt_document'
        );

        add_submenu_page(
            'bkgt-documents',
            __('Inställningar', 'bkgt-document-management'),
            __('Inställningar', 'bkgt-document-management'),
            'manage_options',
            'bkgt-document-settings',
            array($this, 'settings_page')
        );

        add_submenu_page(
            'bkgt-documents',
            __('Mallbyggare', 'bkgt-document-management'),
            __('Mallbyggare', 'bkgt-document-management'),
            'manage_options',
            'bkgt-template-builder',
            array($this, 'template_builder_page')
        );
    }

    /**
     * Main admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Dokumenthantering', 'bkgt-document-management'); ?></h1>

            <div class="bkgt-admin-dashboard">
                <?php $this->quick_actions(); ?>
                <?php $this->dashboard_stats(); ?>
                <?php $this->recent_documents(); ?>
            </div>

            <!-- Upload Modal -->
            <div id="bkgt-upload-modal" class="bkgt-modal" style="display: none;">
                <div class="bkgt-modal-overlay"></div>
                <div class="bkgt-modal-content">
                    <div class="bkgt-modal-header">
                        <h2><?php _e('Ladda upp nytt dokument', 'bkgt-document-management'); ?></h2>
                        <button type="button" class="bkgt-modal-close">&times;</button>
                    </div>
                    <div class="bkgt-modal-body">
                        <form id="bkgt-upload-form" enctype="multipart/form-data">
                            <?php wp_nonce_field('bkgt_document_admin', 'bkgt_upload_nonce'); ?>
                            <div class="bkgt-form-row">
                                <label for="bkgt-doc-title"><?php _e('Dokumenttitel', 'bkgt-document-management'); ?> *</label>
                                <input type="text" id="bkgt-doc-title" name="title" required>
                            </div>
                            <div class="bkgt-form-row">
                                <label for="bkgt-doc-category"><?php _e('Kategori', 'bkgt-document-management'); ?></label>
                                <?php
                                $categories = get_terms(array(
                                    'taxonomy' => 'bkgt_doc_category',
                                    'hide_empty' => false,
                                ));
                                ?>
                                <select id="bkgt-doc-category" name="category_id">
                                    <option value="0"><?php _e('Välj kategori', 'bkgt-document-management'); ?></option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category->term_id; ?>"><?php echo esc_html($category->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="bkgt-form-row">
                                <label for="bkgt-doc-file"><?php _e('Välj fil', 'bkgt-document-management'); ?> *</label>
                                <input type="file" id="bkgt-doc-file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.jpg,.jpeg,.png" required>
                                <p class="description"><?php _e('Tillåtna filtyper: PDF, Word, Excel, Text, Bilder', 'bkgt-document-management'); ?></p>
                            </div>
                            <div class="bkgt-form-row">
                                <label for="bkgt-doc-description"><?php _e('Ändringsbeskrivning', 'bkgt-document-management'); ?></label>
                                <textarea id="bkgt-doc-description" name="change_description" rows="3" placeholder="<?php _e('Beskriv ändringen (valfritt)', 'bkgt-document-management'); ?>"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="bkgt-modal-footer">
                        <button type="button" class="button" id="bkgt-cancel-upload"><?php _e('Avbryt', 'bkgt-document-management'); ?></button>
                        <button type="button" class="button button-primary" id="bkgt-submit-upload">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Ladda upp', 'bkgt-document-management'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Quick actions section
     */
    private function quick_actions() {
        ?>
        <div class="bkgt-quick-actions">
            <h2><?php _e('Snabbåtgärder', 'bkgt-document-management'); ?></h2>
            <div class="bkgt-action-buttons">
                <button type="button" class="button button-primary" id="bkgt-upload-modal-trigger">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Ladda upp nytt dokument', 'bkgt-document-management'); ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=bkgt-template-builder'); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-editor-paste-text"></span>
                    <?php _e('Skapa från mall', 'bkgt-document-management'); ?>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=bkgt_document'); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Sök dokument', 'bkgt-document-management'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=bkgt-document-settings'); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Inställningar', 'bkgt-document-management'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Dashboard statistics
     */
    private function dashboard_stats() {
        $stats = BKGT_Document::get_statistics();
        ?>
        <div class="bkgt-stats-grid">
            <div class="bkgt-stat-card">
                <h3><?php _e('Totalt antal dokument', 'bkgt-document-management'); ?></h3>
                <div class="bkgt-stat-number"><?php echo number_format($stats['total_documents']); ?></div>
            </div>

            <div class="bkgt-stat-card">
                <h3><?php _e('Total storlek', 'bkgt-document-management'); ?></h3>
                <div class="bkgt-stat-number"><?php echo $this->format_bytes($stats['total_size']); ?></div>
            </div>

            <div class="bkgt-stat-card">
                <h3><?php _e('Nedladdningar (30 dagar)', 'bkgt-document-management'); ?></h3>
                <div class="bkgt-stat-number"><?php echo number_format($stats['total_downloads']); ?></div>
            </div>

            <div class="bkgt-stat-card">
                <h3><?php _e('Nya uppladdningar (7 dagar)', 'bkgt-document-management'); ?></h3>
                <div class="bkgt-stat-number"><?php echo number_format($stats['recent_uploads']); ?></div>
            </div>
        </div>
        <?php
    }

    /**
     * Recent documents
     */
    private function recent_documents() {
        $recent_docs = BKGT_Document::get_recent(5);
        ?>
        <div class="bkgt-recent-documents">
            <h2><?php _e('Senaste dokumenten', 'bkgt-document-management'); ?></h2>

            <?php if (!empty($recent_docs)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Titel', 'bkgt-document-management'); ?></th>
                            <th><?php _e('Kategori', 'bkgt-document-management'); ?></th>
                            <th><?php _e('Storlek', 'bkgt-document-management'); ?></th>
                            <th><?php _e('Uppladdad', 'bkgt-document-management'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_docs as $doc): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($doc->ID); ?>">
                                        <?php echo esc_html($doc->post_title); ?>
                                    </a>
                                </td>
                                <td><?php echo $this->get_document_categories($doc->ID); ?></td>
                                <td><?php echo $this->get_document_size($doc->ID); ?></td>
                                <td><?php echo get_the_date('', $doc->ID); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('Inga dokument hittades.', 'bkgt-document-management'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }

        $options = get_option('bkgt_document_settings', array());
        ?>
        <div class="wrap">
            <h1><?php _e('Dokumentinställningar', 'bkgt-document-management'); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('bkgt_document_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Tillåtna filtyper', 'bkgt-document-management'); ?></th>
                        <td>
                            <input type="text" name="allowed_file_types" value="<?php echo esc_attr($options['allowed_file_types'] ?? 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,rtf,jpg,jpeg,png,gif'); ?>" class="regular-text">
                            <p class="description"><?php _e('Kommaseparerad lista av tillåtna filändelser (utan punkter).', 'bkgt-document-management'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Max filstorlek (MB)', 'bkgt-document-management'); ?></th>
                        <td>
                            <input type="number" name="max_file_size" value="<?php echo esc_attr($options['max_file_size'] ?? 10); ?>" min="1" max="100">
                            <p class="description"><?php _e('Maximal filstorlek i megabyte.', 'bkgt-document-management'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Behåll versioner', 'bkgt-document-management'); ?></th>
                        <td>
                            <input type="number" name="keep_versions" value="<?php echo esc_attr($options['keep_versions'] ?? 5); ?>" min="1" max="50">
                            <p class="description"><?php _e('Antal versioner att behålla per dokument.', 'bkgt-document-management'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Aktivera nedladdningsloggning', 'bkgt-document-management'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_download_logging" value="1" <?php checked($options['enable_download_logging'] ?? 1); ?>>
                                <?php _e('Logga alla nedladdningar', 'bkgt-document-management'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Save settings
     */
    private function save_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'bkgt_document_settings')) {
            return;
        }

        $options = array(
            'allowed_file_types' => sanitize_text_field($_POST['allowed_file_types']),
            'max_file_size' => intval($_POST['max_file_size']),
            'keep_versions' => intval($_POST['keep_versions']),
            'enable_download_logging' => isset($_POST['enable_download_logging']) ? 1 : 0,
        );

        update_option('bkgt_document_settings', $options);
        echo '<div class="notice notice-success"><p>' . __('Inställningar sparade.', 'bkgt-document-management') . '</p></div>';
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'bkgt_document') === false && $hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        // Enqueue Monaco Editor from CDN
        wp_enqueue_script('monaco-editor', 'https://unpkg.com/monaco-editor@0.45.0/min/vs/loader.min.js', array(), '0.45.0', true);
        wp_enqueue_script('marked', 'https://cdn.jsdelivr.net/npm/marked@11.1.1/lib/marked.umd.js', array(), '11.1.1', true);

        wp_enqueue_script('bkgt-document-admin', plugin_dir_url(__FILE__) . '../assets/js/admin.js', array('jquery', 'monaco-editor', 'marked'), '1.0.0', true);
        wp_enqueue_style('bkgt-document-admin', plugin_dir_url(__FILE__) . '../assets/css/admin.css', array(), '1.0.0');

        wp_localize_script('bkgt-document-admin', 'bkgt_document_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkgt_document_admin'),
            'monaco_loader_url' => 'https://unpkg.com/monaco-editor@0.45.0/min/vs/',
            'template_variables' => $this->template_system->get_available_variables(),
            'strings' => array(
                'confirm_delete' => __('Är du säker på att du vill radera detta dokument?', 'bkgt-document-management'),
                'uploading' => __('Laddar upp...', 'bkgt-document-management'),
                'upload_success' => __('Dokument uppladdat!', 'bkgt-document-management'),
                'upload_error' => __('Uppladdning misslyckades.', 'bkgt-document-management'),
                'editor_loading' => __('Laddar editor...', 'bkgt-document-management'),
                'preview_error' => __('Kunde inte uppdatera förhandsvisning.', 'bkgt-document-management'),
                'editing_now' => __('Redigerar nu', 'bkgt-document-management'),
                'editing_individual' => __('Redigerar enskilt', 'bkgt-document-management'),
                'saving' => __('Sparar...', 'bkgt-document-management'),
            ),
        ));
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'bkgt_document_content',
            __('Dokumentinnehåll (Markdown)', 'bkgt-document-management'),
            array($this, 'content_meta_box'),
            'bkgt_document',
            'normal',
            'high'
        );

        add_meta_box(
            'bkgt_document_file',
            __('Dokumentfil', 'bkgt-document-management'),
            array($this, 'file_meta_box'),
            'bkgt_document',
            'normal',
            'default'
        );

        add_meta_box(
            'bkgt_document_versions',
            __('Versioner', 'bkgt-document-management'),
            array($this, 'versions_meta_box'),
            'bkgt_document',
            'normal',
            'default'
        );

        add_meta_box(
            'bkgt_document_access',
            __('Åtkomstkontroll', 'bkgt-document-management'),
            array($this, 'access_meta_box'),
            'bkgt_document',
            'side',
            'default'
        );
    }

    /**
     * File meta box
     */
    public function file_meta_box($post) {
        $document = new BKGT_Document($post->ID);
        ?>
        <div class="bkgt-file-upload">
            <?php if ($document->get_file_path()): ?>
                <div class="bkgt-current-file">
                    <h4><?php _e('Nuvarande fil:', 'bkgt-document-management'); ?></h4>
                    <p><strong><?php echo esc_html($document->get_file_name()); ?></strong></p>
                    <p><?php echo $document->get_formatted_file_size(); ?> | <?php echo esc_html($document->get_mime_type()); ?></p>
                    <p><?php _e('Uppladdad:', 'bkgt-document-management'); ?> <?php echo $document->get_upload_date(); ?></p>
                    <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=bkgt_download_document&document_id=' . $post->ID), 'download_document_' . $post->ID); ?>" class="button">
                        <?php _e('Ladda ner', 'bkgt-document-management'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <div class="bkgt-file-upload-new">
                <h4><?php _e('Ladda upp ny version:', 'bkgt-document-management'); ?></h4>
                <input type="file" name="bkgt_document_file" id="bkgt_document_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf,.jpg,.jpeg,.png,.gif">
                <input type="text" name="bkgt_change_description" placeholder="<?php esc_attr_e('Beskrivning av ändring (valfritt)', 'bkgt-document-management'); ?>" class="regular-text">
                <button type="button" class="button" id="bkgt_upload_file"><?php _e('Ladda upp', 'bkgt-document-management'); ?></button>
            </div>
        </div>
        <?php
    }

    /**
     * Content meta box - Advanced Markdown Editor
     */
    public function content_meta_box($post) {
        $content = get_post_meta($post->ID, '_bkgt_markdown_content', true);
        $editor_mode = get_post_meta($post->ID, '_bkgt_editor_mode', true) ?: 'split';
        wp_nonce_field('bkgt_document_content', 'bkgt_document_content_nonce');
        ?>
        <div class="bkgt-markdown-editor">
            <div class="bkgt-editor-toolbar">
                <div class="bkgt-editor-modes">
                    <label>
                        <input type="radio" name="bkgt_editor_mode" value="markdown" <?php checked($editor_mode, 'markdown'); ?>>
                        <?php _e('Endast Markdown', 'bkgt-document-management'); ?>
                    </label>
                    <label>
                        <input type="radio" name="bkgt_editor_mode" value="split" <?php checked($editor_mode, 'split'); ?>>
                        <?php _e('Delad vy', 'bkgt-document-management'); ?>
                    </label>
                    <label>
                        <input type="radio" name="bkgt_editor_mode" value="preview" <?php checked($editor_mode, 'preview'); ?>>
                        <?php _e('Endast förhandsvisning', 'bkgt-document-management'); ?>
                    </label>
                </div>
                <div class="bkgt-editor-actions">
                    <button type="button" class="button bkgt-insert-media" title="<?php esc_attr_e('Infoga media', 'bkgt-document-management'); ?>">
                        <span class="dashicons dashicons-format-image"></span>
                    </button>
                    <button type="button" class="button bkgt-insert-template" title="<?php esc_attr_e('Infoga mallvariabel', 'bkgt-document-management'); ?>">
                        <span class="dashicons dashicons-editor-code"></span>
                    </button>
                    <button type="button" class="button bkgt-toggle-fullscreen" title="<?php esc_attr_e('Helskärm', 'bkgt-document-management'); ?>">
                        <span class="dashicons dashicons-editor-expand"></span>
                    </button>
                </div>
            </div>

            <div class="bkgt-editor-container" data-mode="<?php echo esc_attr($editor_mode); ?>">
                <div class="bkgt-editor-pane bkgt-markdown-pane">
                    <div class="bkgt-editor-header">
                        <span class="bkgt-pane-title"><?php _e('Markdown', 'bkgt-document-management'); ?></span>
                        <div class="bkgt-editor-status">
                            <span class="bkgt-collaboration-indicator" id="bkgt-collaboration-status">
                                <span class="dashicons dashicons-edit"></span>
                                <?php _e('Redigerar enskilt', 'bkgt-document-management'); ?>
                            </span>
                        </div>
                        <span class="bkgt-word-count">0 <?php _e('ord', 'bkgt-document-management'); ?></span>
                    </div>
                    <div class="bkgt-monaco-editor" id="bkgt-markdown-editor"></div>
                    <textarea name="bkgt_markdown_content" id="bkgt-markdown-content" style="display: none;"><?php echo esc_textarea($content); ?></textarea>
                </div>

                <div class="bkgt-editor-pane bkgt-preview-pane">
                    <div class="bkgt-editor-header">
                        <span class="bkgt-pane-title"><?php _e('Förhandsvisning', 'bkgt-document-management'); ?></span>
                        <button type="button" class="button bkgt-refresh-preview" title="<?php esc_attr_e('Uppdatera förhandsvisning', 'bkgt-document-management'); ?>">
                            <span class="dashicons dashicons-update"></span>
                        </button>
                    </div>
                    <div class="bkgt-preview-content" id="bkgt-preview-content"></div>
                </div>
            </div>

            <div class="bkgt-template-variables">
                <h4><?php _e('Tillgängliga mallvariabler', 'bkgt-document-management'); ?></h4>
                <div class="bkgt-variables-grid">
                    <div class="bkgt-variable-group">
                        <h5><?php _e('Allmänt', 'bkgt-document-management'); ?></h5>
                        <button type="button" class="button bkgt-insert-variable" data-variable="{{CURRENT_DATE}}">{{CURRENT_DATE}}</button>
                        <button type="button" class="button bkgt-insert-variable" data-variable="{{CURRENT_USER}}">{{CURRENT_USER}}</button>
                        <button type="button" class="button bkgt-insert-variable" data-variable="{{DOCUMENT_TITLE}}">{{DOCUMENT_TITLE}}</button>
                    </div>
                    <div class="bkgt-variable-group">
                        <h5><?php _e('Spelare', 'bkgt-document-management'); ?></h5>
                        <button type="button" class="button bkgt-insert-variable" data-variable="{{PLAYER_NAME}}">{{PLAYER_NAME}}</button>
                        <button type="button" class="button bkgt-insert-variable" data-variable="{{PLAYER_ID}}">{{PLAYER_ID}}</button>
                        <button type="button" class="button bkgt-insert-variable" data-variable="{{PLAYER_TEAM}}">{{PLAYER_TEAM}}</button>
                    </div>
                    <div class="bkgt-variable-group">
                        <h5><?php _e('Utrustning', 'bkgt-document-management'); ?></h5>
                        <button type="button" class="button bkgt-insert-variable" data-variable="{{EQUIPMENT_NAME}}">{{EQUIPMENT_NAME}}</button>
                        <button type="button" class="button bkgt-insert-variable" data-variable="{{EQUIPMENT_ID}}">{{EQUIPMENT_ID}}</button>
                        <button type="button" class="button bkgt-insert-variable" data-variable="{{EQUIPMENT_STATUS}}">{{EQUIPMENT_STATUS}}</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Versions meta box
     */
    public function versions_meta_box($post) {
        $versions = BKGT_Document_Version::get_document_versions($post->ID);
        ?>
        <div class="bkgt-versions-list">
            <?php if (!empty($versions)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Version', 'bkgt-document-management'); ?></th>
                            <th><?php _e('Storlek', 'bkgt-document-management'); ?></th>
                            <th><?php _e('Uppladdad av', 'bkgt-document-management'); ?></th>
                            <th><?php _e('Datum', 'bkgt-document-management'); ?></th>
                            <th><?php _e('Åtgärder', 'bkgt-document-management'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($versions as $index => $version): ?>
                            <tr>
                                <td><?php echo count($versions) - $index; ?></td>
                                <td><?php echo $version->get_formatted_file_size(); ?></td>
                                <td><?php echo esc_html($version->get_uploaded_by_name()); ?></td>
                                <td><?php echo $version->get_formatted_upload_date(); ?></td>
                                <td>
                                    <a href="#" class="bkgt-download-version" data-version-id="<?php echo $version->get_id(); ?>">
                                        <?php _e('Ladda ner', 'bkgt-document-management'); ?>
                                    </a>
                                    <?php if ($index > 0): ?>
                                        | <a href="#" class="bkgt-restore-version" data-version-id="<?php echo $version->get_id(); ?>">
                                            <?php _e('Återställ', 'bkgt-document-management'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('Inga versioner tillgängliga.', 'bkgt-document-management'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Access meta box
     */
    public function access_meta_box($post) {
        $access_rules = BKGT_Document_Access::get_document_access($post->ID);
        ?>
        <div class="bkgt-access-control">
            <div class="bkgt-current-access">
                <h4><?php _e('Nuvarande åtkomst:', 'bkgt-document-management'); ?></h4>
                <?php if (!empty($access_rules)): ?>
                    <ul>
                        <?php foreach ($access_rules as $rule): ?>
                            <li>
                                <strong><?php echo esc_html($rule->get_target_name()); ?></strong>
                                (<?php echo esc_html($rule->get_access_type_name()); ?>)
                                <a href="#" class="bkgt-remove-access" data-access-id="<?php echo $rule->get_id(); ?>">×</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><?php _e('Ingen specifik åtkomst konfigurerad.', 'bkgt-document-management'); ?></p>
                <?php endif; ?>
            </div>

            <div class="bkgt-add-access">
                <h4><?php _e('Lägg till åtkomst:', 'bkgt-document-management'); ?></h4>
                <select id="bkgt_access_target_type">
                    <option value="user"><?php _e('Användare', 'bkgt-document-management'); ?></option>
                    <option value="role"><?php _e('Roll', 'bkgt-document-management'); ?></option>
                    <option value="team"><?php _e('Lag', 'bkgt-document-management'); ?></option>
                </select>

                <div id="bkgt_access_target_user" class="bkgt-access-target">
                    <?php wp_dropdown_users(array('name' => 'bkgt_access_user', 'show_option_none' => __('Välj användare', 'bkgt-document-management'))); ?>
                </div>

                <div id="bkgt_access_target_role" class="bkgt-access-target" style="display:none;">
                    <select name="bkgt_access_role">
                        <option value=""><?php _e('Välj roll', 'bkgt-document-management'); ?></option>
                        <option value="bkgt_styrelsemedlem"><?php _e('Styrelsemedlem', 'bkgt-document-management'); ?></option>
                        <option value="bkgt_tranare"><?php _e('Tränare', 'bkgt-document-management'); ?></option>
                        <option value="bkgt_lagledare"><?php _e('Lagledare', 'bkgt-document-management'); ?></option>
                    </select>
                </div>

                <div id="bkgt_access_target_team" class="bkgt-access-target" style="display:none;">
                    <select name="bkgt_access_team">
                        <option value=""><?php _e('Välj lag', 'bkgt-document-management'); ?></option>
                        <?php
                        if (function_exists('bkgt_get_teams')) {
                            $teams = bkgt_get_teams();
                            foreach ($teams as $team) {
                                echo '<option value="' . esc_attr($team->id) . '">' . esc_html($team->name . ' (' . $team->id . ')') . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <select name="bkgt_access_type">
                    <option value="view"><?php _e('Visa', 'bkgt-document-management'); ?></option>
                    <option value="edit"><?php _e('Redigera', 'bkgt-document-management'); ?></option>
                    <option value="manage"><?php _e('Hantera', 'bkgt-document-management'); ?></option>
                </select>

                <button type="button" class="button" id="bkgt_add_access"><?php _e('Lägg till', 'bkgt-document-management'); ?></button>
            </div>
        </div>
        <?php
    }

    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id) {
        if (!isset($_POST['post_type']) || $_POST['post_type'] !== 'bkgt_document') {
            return;
        }

        if (!current_user_can('edit_document', $post_id)) {
            return;
        }

        // Save markdown content
        if (isset($_POST['bkgt_document_content_nonce']) &&
            wp_verify_nonce($_POST['bkgt_document_content_nonce'], 'bkgt_document_content')) {

            $markdown_content = isset($_POST['bkgt_markdown_content']) ? wp_kses_post($_POST['bkgt_markdown_content']) : '';
            $editor_mode = isset($_POST['bkgt_editor_mode']) ? sanitize_text_field($_POST['bkgt_editor_mode']) : 'split';

            update_post_meta($post_id, '_bkgt_markdown_content', $markdown_content);
            update_post_meta($post_id, '_bkgt_editor_mode', $editor_mode);

            // Also update the post content for WordPress search and display
            if (!empty($markdown_content)) {
                $post_content = $this->convert_markdown_to_html($markdown_content);
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_content' => $post_content
                ));
            }
        }

        // Handle file upload if present
        if (!empty($_FILES['bkgt_document_file']['name'])) {
            $document = new BKGT_Document($post_id);
            $result = $document->add_version($_FILES['bkgt_document_file'], $_POST['bkgt_change_description'] ?? '');

            if (is_wp_error($result)) {
                // Handle error - could add admin notice
            }
        }
    }

    /**
     * Convert markdown to HTML
     */
    private function convert_markdown_to_html($markdown) {
        // Use WordPress built-in markdown if available, otherwise basic conversion
        if (function_exists('jetpack_markdown')) {
            return jetpack_markdown($markdown);
        }

        // Basic markdown conversion for common elements
        $html = $markdown;

        // Headers
        $html = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $html);

        // Bold and italic
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);

        // Links
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);

        // Lists
        $html = preg_replace('/^\* (.*)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/^- (.*)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);

        // Line breaks
        $html = nl2br($html);

        return wp_kses_post($html);
    }

    /**
     * Add document columns
     */
    public function add_document_columns($columns) {
        $new_columns = array();

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['categories'] = __('Kategorier', 'bkgt-document-management');
                $new_columns['file_size'] = __('Storlek', 'bkgt-document-management');
                $new_columns['upload_date'] = __('Uppladdad', 'bkgt-document-management');
            }
        }

        return $new_columns;
    }

    /**
     * Fill document columns
     */
    public function fill_document_columns($column, $post_id) {
        switch ($column) {
            case 'categories':
                echo $this->get_document_categories($post_id);
                break;
            case 'file_size':
                echo $this->get_document_size($post_id);
                break;
            case 'upload_date':
                $document = new BKGT_Document($post_id);
                echo $document->get_upload_date();
                break;
        }
    }

    /**
     * Make columns sortable
     */
    public function make_columns_sortable($columns) {
        $columns['file_size'] = 'file_size';
        $columns['upload_date'] = 'upload_date';
        return $columns;
    }

    /**
     * Get document categories
     */
    private function get_document_categories($post_id) {
        $categories = get_the_terms($post_id, 'bkgt_doc_category');
        if (!$categories) {
            return '-';
        }

        $category_names = array();
        foreach ($categories as $category) {
            $category_names[] = $category->name;
        }

        return implode(', ', $category_names);
    }

    /**
     * Get document size
     */
    private function get_document_size($post_id) {
        $document = new BKGT_Document($post_id);
        return $document->get_formatted_file_size();
    }

    /**
     * Format bytes
     */
    private function format_bytes($bytes) {
        if (!$bytes) {
            return '0 B';
        }

        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * AJAX upload document
     */
    public function ajax_upload_document() {
        check_ajax_referer('bkgt_document_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Otillräckliga behörigheter.', 'bkgt-document-management'));
        }

        $document_id = intval($_POST['document_id']);
        $change_description = sanitize_text_field($_POST['change_description'] ?? '');

        if (empty($_FILES['file'])) {
            wp_die(__('Ingen fil vald.', 'bkgt-document-management'));
        }

        $document = new BKGT_Document($document_id);
        $result = $document->add_version($_FILES['file'], $change_description);

        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('Dokumentversion uppladdad!', 'bkgt-document-management'),
        ));
    }

    /**
     * AJAX create new document
     */
    public function ajax_create_document() {
        check_ajax_referer('bkgt_document_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Otillräckliga behörigheter.', 'bkgt-document-management'));
        }

        $title = sanitize_text_field($_POST['title'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $change_description = sanitize_text_field($_POST['change_description'] ?? '');

        if (empty($title)) {
            wp_die(__('Dokumenttitel krävs.', 'bkgt-document-management'));
        }

        if (empty($_FILES['file'])) {
            wp_die(__('Ingen fil vald.', 'bkgt-document-management'));
        }

        // Create the document post
        $document_data = array(
            'post_title' => $title,
            'post_status' => 'publish',
            'post_type' => 'bkgt_document',
        );

        $document = BKGT_Document::create($document_data);

        if (is_wp_error($document)) {
            wp_die($document->get_error_message());
        }

        $document_id = $document->get_id();

        // Set category if provided
        if ($category_id > 0) {
            wp_set_post_terms($document_id, array($category_id), 'bkgt_doc_category');
        }

        // Handle file upload
        $result = BKGT_Document::handle_file_upload($document_id, $_FILES['file']);

        if (is_wp_error($result)) {
            // Clean up the post if file upload failed
            wp_delete_post($document_id, true);
            wp_die($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('Dokument skapat!', 'bkgt-document-management'),
            'document_id' => $document_id,
            'edit_url' => get_edit_post_link($document_id),
        ));
    }

    /**
     * AJAX delete document
     */
    public function ajax_delete_document() {
        check_ajax_referer('bkgt_document_admin', 'nonce');

        $document_id = intval($_POST['document_id']);

        if (!current_user_can('delete_document', $document_id)) {
            wp_die(__('Otillräckliga behörigheter.', 'bkgt-document-management'));
        }

        $result = BKGT_Document::delete($document_id);

        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('Dokument raderat!', 'bkgt-document-management'),
        ));
    }

    /**
     * AJAX get document versions
     */
    public function ajax_get_document_versions() {
        check_ajax_referer('bkgt_document_admin', 'nonce');

        $document_id = intval($_POST['document_id']);

        if (!current_user_can('edit_document', $document_id)) {
            wp_die(__('Otillräckliga behörigheter.', 'bkgt-document-management'));
        }

        $versions = BKGT_Document_Version::get_document_versions($document_id);

        $version_data = array();
        foreach ($versions as $version) {
            $version_data[] = array(
                'id' => $version->get_id(),
                'file_name' => $version->get_file_name(),
                'file_size' => $version->get_formatted_file_size(),
                'uploaded_by' => $version->get_uploaded_by_name(),
                'upload_date' => $version->get_formatted_upload_date(),
                'change_description' => $version->get_change_description(),
            );
        }

        wp_send_json_success($version_data);
    }

    /**
     * AJAX restore version
     */
    public function ajax_restore_version() {
        check_ajax_referer('bkgt_document_admin', 'nonce');

        $version_id = intval($_POST['version_id']);
        $version = new BKGT_Document_Version($version_id);

        if (!$version->data) {
            wp_die(__('Version hittades inte.', 'bkgt-document-management'));
        }

        $document_id = $version->get_document_id();

        if (!current_user_can('edit_document', $document_id)) {
            wp_die(__('Otillräckliga behörigheter.', 'bkgt-document-management'));
        }

        $result = $version->restore();

        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('Version återställd!', 'bkgt-document-management'),
        ));
    }

    /**
     * AJAX manage access
     */
    public function ajax_manage_access() {
        check_ajax_referer('bkgt_document_admin', 'nonce');

        $document_id = intval($_POST['document_id']);
        $action = sanitize_text_field($_POST['access_action']);

        if (!current_user_can('edit_document', $document_id)) {
            wp_die(__('Otillräckliga behörigheter.', 'bkgt-document-management'));
        }

        if ($action === 'add') {
            $access_data = array(
                'access_type' => sanitize_text_field($_POST['access_type']),
            );

            if (!empty($_POST['user_id'])) {
                $access_data['user_id'] = intval($_POST['user_id']);
            } elseif (!empty($_POST['role'])) {
                $access_data['role'] = sanitize_text_field($_POST['role']);
            } elseif (!empty($_POST['team_id'])) {
                $access_data['team_id'] = intval($_POST['team_id']);
            }

            $result = BKGT_Document_Access::create($document_id, $access_data);

            if (is_wp_error($result)) {
                wp_die($result->get_error_message());
            }

            wp_send_json_success(array(
                'message' => __('Åtkomst tillagd!', 'bkgt-document-management'),
            ));

        } elseif ($action === 'remove') {
            $access_id = intval($_POST['access_id']);
            $result = BKGT_Document_Access::delete($access_id);

            if (is_wp_error($result)) {
                wp_die($result->get_error_message());
            }

            wp_send_json_success(array(
                'message' => __('Åtkomst borttagen!', 'bkgt-document-management'),
            ));
        }
    }

    /**
     * Template Builder Page - OLD VERSION DISABLED
     */
    public function template_builder_page_old() {
        ?>
        <div class="wrap">
            <h1><?php _e('Mallbyggare', 'bkgt-document-management'); ?></h1>

            <div class="bkgt-template-builder">
                        <div class="bkgt-component-palette">
                            <div class="bkgt-component" data-type="text" draggable="true">
                                <i class="dashicons dashicons-text"></i>
                                <span><?php _e('Text', 'bkgt-document-management'); ?></span>
                            </div>
                            <div class="bkgt-component" data-type="heading" draggable="true">
                                <i class="dashicons dashicons-editor-bold"></i>
                                <span><?php _e('Rubrik', 'bkgt-document-management'); ?></span>
                            </div>
                            <div class="bkgt-component" data-type="variable" draggable="true">
                                <i class="dashicons dashicons-admin-generic"></i>
                                <span><?php _e('Variabel', 'bkgt-document-management'); ?></span>
                            </div>
                            <div class="bkgt-component" data-type="list" draggable="true">
                                <i class="dashicons dashicons-editor-ul"></i>
                                <span><?php _e('Lista', 'bkgt-document-management'); ?></span>
                            </div>
                            <div class="bkgt-component" data-type="table" draggable="true">
                                <i class="dashicons dashicons-editor-table"></i>
                                <span><?php _e('Tabell', 'bkgt-document-management'); ?></span>
                            </div>
                            <div class="bkgt-component" data-type="image" draggable="true">
                                <i class="dashicons dashicons-format-image"></i>
                                <span><?php _e('Bild', 'bkgt-document-management'); ?></span>
                            </div>
                            <div class="bkgt-component" data-type="divider" draggable="true">
                                <i class="dashicons dashicons-minus"></i>
                                <span><?php _e('Avdelare', 'bkgt-document-management'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="bkgt-toolbar-section">
                        <h3><?php _e('Egenskaper', 'bkgt-document-management'); ?></h3>
                        <div class="bkgt-properties-panel" id="bkgt-properties-panel">
                            <p><?php _e('Välj en komponent för att redigera dess egenskaper.', 'bkgt-document-management'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Canvas -->
                <div class="bkgt-builder-canvas">
                    <div class="bkgt-canvas-header">
                        <div class="bkgt-template-info">
                            <input type="text" id="bkgt-template-title" placeholder="<?php _e('Mallnamn', 'bkgt-document-management'); ?>" class="regular-text">
                            <textarea id="bkgt-template-description" placeholder="<?php _e('Beskrivning', 'bkgt-document-management'); ?>" rows="2" class="large-text"></textarea>
                        </div>
                        <div class="bkgt-canvas-actions">
                            <button id="bkgt-save-template" class="button button-primary">
                                <i class="dashicons dashicons-saved"></i>
                                <?php _e('Spara Mall', 'bkgt-document-management'); ?>
                            </button>
                            <button id="bkgt-preview-template" class="button">
                                <i class="dashicons dashicons-visibility"></i>
                                <?php _e('Förhandsgranska', 'bkgt-document-management'); ?>
                            </button>
                            <button id="bkgt-clear-canvas" class="button">
                                <i class="dashicons dashicons-trash"></i>
                                <?php _e('Rensa', 'bkgt-document-management'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="bkgt-canvas-content" id="bkgt-canvas-content">
                        <div class="bkgt-canvas-placeholder">
                            <i class="dashicons dashicons-plus-alt"></i>
                            <p><?php _e('Dra komponenter hit för att börja bygga din mall', 'bkgt-document-management'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Preview Modal -->
                <div id="bkgt-template-preview-modal" class="bkgt-modal" style="display: none;">
                    <div class="bkgt-modal-content">
                        <div class="bkgt-modal-header">
                            <h2><?php _e('Förhandsgranskning', 'bkgt-document-management'); ?></h2>
                            <span class="bkgt-modal-close">&times;</span>
                        </div>
                        <div class="bkgt-modal-body">
                            <div id="bkgt-preview-content"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .bkgt-template-builder {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .bkgt-builder-toolbar {
            width: 300px;
            flex-shrink: 0;
        }

        .bkgt-toolbar-section {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .bkgt-toolbar-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 16px;
            color: #23282d;
        }

        .bkgt-component-palette {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .bkgt-component {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            cursor: grab;
            transition: all 0.2s ease;
        }

        .bkgt-component:hover {
            background: #e9ecef;
            border-color: #007cba;
        }

        .bkgt-component:active {
            cursor: grabbing;
        }

        .bkgt-component i {
            color: #007cba;
            font-size: 18px;
        }

        .bkgt-builder-canvas {
            flex: 1;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .bkgt-canvas-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .bkgt-template-info {
            flex: 1;
            margin-right: 20px;
        }

        .bkgt-template-info input,
        .bkgt-template-info textarea {
            width: 100%;
            margin-bottom: 10px;
        }

        .bkgt-canvas-actions {
            display: flex;
            gap: 10px;
        }

        .bkgt-canvas-content {
            min-height: 600px;
            padding: 20px;
            position: relative;
        }

        .bkgt-canvas-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 400px;
            color: #6c757d;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .bkgt-canvas-placeholder i {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .bkgt-canvas-placeholder p {
            margin: 0;
            font-size: 16px;
        }

        .bkgt-canvas-component {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            background: #fff;
            position: relative;
            cursor: move;
        }

        .bkgt-canvas-component:hover {
            border-color: #007cba;
            box-shadow: 0 2px 8px rgba(0,123,186,0.1);
        }

        .bkgt-canvas-component.selected {
            border-color: #007cba;
            box-shadow: 0 0 0 2px rgba(0,123,186,0.2);
        }

        .bkgt-component-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }

        .bkgt-component-type {
            font-weight: 600;
            color: #495057;
            text-transform: capitalize;
        }

        .bkgt-component-actions {
            display: flex;
            gap: 5px;
        }

        .bkgt-component-actions button {
            padding: 4px 8px;
            font-size: 12px;
            line-height: 1;
        }

        .bkgt-component-content {
            color: #495057;
        }

        .bkgt-component-content textarea {
            width: 100%;
            min-height: 60px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 8px;
            font-family: inherit;
            resize: vertical;
        }

        .bkgt-component-content input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .bkgt-properties-panel {
            min-height: 200px;
        }

        .bkgt-property-group {
            margin-bottom: 15px;
        }

        .bkgt-property-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #495057;
        }

        .bkgt-property-group input,
        .bkgt-property-group select,
        .bkgt-property-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .bkgt-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bkgt-modal-content {
            background: #fff;
            border-radius: 8px;
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .bkgt-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .bkgt-modal-header h2 {
            margin: 0;
            color: #23282d;
        }

        .bkgt-modal-close {
            font-size: 28px;
            cursor: pointer;
            color: #6c757d;
        }

        .bkgt-modal-close:hover {
            color: #23282d;
        }

        .bkgt-modal-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
        }

        /* Drag and drop states */
        .bkgt-canvas-component.drag-over {
            border-color: #28a745;
            background: #d4edda;
        }

        .bkgt-component.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .bkgt-template-builder {
                flex-direction: column;
            }

            .bkgt-builder-toolbar {
                width: 100%;
                order: 2;
            }

            .bkgt-builder-canvas {
                order: 1;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            let selectedComponent = null;
            let componentCounter = 0;

            // Component drag and drop
            $('.bkgt-component').on('dragstart', function(e) {
                e.originalEvent.dataTransfer.setData('text/plain', $(this).data('type'));
                $(this).addClass('dragging');
            });

            $('.bkgt-component').on('dragend', function(e) {
                $(this).removeClass('dragging');
            });

            // Canvas drop zones
            $('#bkgt-canvas-content').on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });

            $('#bkgt-canvas-content').on('dragleave', function(e) {
                $(this).removeClass('drag-over');
            });

            $('#bkgt-canvas-content').on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');

                const componentType = e.originalEvent.dataTransfer.getData('text/plain');
                if (componentType) {
                    addComponentToCanvas(componentType, e.originalEvent);
                }
            });

            // Component selection
            $(document).on('click', '.bkgt-canvas-component', function(e) {
                if (!$(e.target).is('button, input, textarea, select')) {
                    selectComponent($(this));
                }
            });

            // Component editing
            $(document).on('input', '.bkgt-component-content textarea, .bkgt-component-content input', function() {
                updateComponentPreview($(this).closest('.bkgt-canvas-component'));
            });

            // Component actions
            $(document).on('click', '.bkgt-delete-component', function(e) {
                e.stopPropagation();
                $(this).closest('.bkgt-canvas-component').remove();
                if (selectedComponent && selectedComponent.is($(this).closest('.bkgt-canvas-component'))) {
                    selectedComponent = null;
                    updatePropertiesPanel();
                }
            });

            $(document).on('click', '.bkgt-duplicate-component', function(e) {
                e.stopPropagation();
                const component = $(this).closest('.bkgt-canvas-component');
                const newComponent = component.clone();
                newComponent.find('.bkgt-component-id').val('');
                component.after(newComponent);
                selectComponent(newComponent);
            });

            // Toolbar actions
            $('#bkgt-save-template').on('click', saveTemplate);
            $('#bkgt-preview-template').on('click', previewTemplate);
            $('#bkgt-clear-canvas').on('click', clearCanvas);

            // Modal close
            $('.bkgt-modal-close').on('click', function() {
                $(this).closest('.bkgt-modal').hide();
            });

            $(window).on('click', function(e) {
                if ($(e.target).hasClass('bkgt-modal')) {
                    $('.bkgt-modal').hide();
                }
            });

            function addComponentToCanvas(type, event) {
                componentCounter++;
                const componentId = 'component_' + componentCounter;
                const componentHtml = createComponentHtml(type, componentId);

                if (event) {
                    // Insert at drop position
                    const rect = $('#bkgt-canvas-content')[0].getBoundingClientRect();
                    const y = event.clientY - rect.top;

                    let insertBefore = null;
                    $('.bkgt-canvas-component').each(function() {
                        const componentRect = this.getBoundingClientRect();
                        const componentY = componentRect.top - rect.top + componentRect.height / 2;
                        if (y < componentY) {
                            insertBefore = this;
                            return false;
                        }
                    });

                    if (insertBefore) {
                        $(insertBefore).before(componentHtml);
                    } else {
                        $('#bkgt-canvas-content').append(componentHtml);
                    }
                } else {
                    $('#bkgt-canvas-content').append(componentHtml);
                }

                $('.bkgt-canvas-placeholder').hide();
                const newComponent = $('#bkgt-canvas-content .bkgt-canvas-component').last();
                selectComponent(newComponent);
            }

            function createComponentHtml(type, id) {
                const baseHtml = `
                    <div class="bkgt-canvas-component" data-type="${type}" data-id="${id}">
                        <div class="bkgt-component-header">
                            <span class="bkgt-component-type">${getComponentLabel(type)}</span>
                            <div class="bkgt-component-actions">
                                <button class="button button-small bkgt-duplicate-component" title="Duplicera">
                                    <i class="dashicons dashicons-admin-page"></i>
                                </button>
                                <button class="button button-small bkgt-delete-component" title="Ta bort">
                                    <i class="dashicons dashicons-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="bkgt-component-content">
                            ${getComponentContentHtml(type, id)}
                        </div>
                    </div>
                `;
                return baseHtml;
            }

            function getComponentLabel(type) {
                const labels = {
                    'text': '<?php _e('Text', 'bkgt-document-management'); ?>',
                    'heading': '<?php _e('Rubrik', 'bkgt-document-management'); ?>',
                    'variable': '<?php _e('Variabel', 'bkgt-document-management'); ?>',
                    'list': '<?php _e('Lista', 'bkgt-document-management'); ?>',
                    'table': '<?php _e('Tabell', 'bkgt-document-management'); ?>',
                    'image': '<?php _e('Bild', 'bkgt-document-management'); ?>',
                    'divider': '<?php _e('Avdelare', 'bkgt-document-management'); ?>'
                };
                return labels[type] || type;
            }

            function getComponentContentHtml(type, id) {
                switch (type) {
                    case 'text':
                        return `<textarea placeholder="<?php _e('Ange textinnehåll här...', 'bkgt-document-management'); ?>" data-property="content"></textarea>`;
                    case 'heading':
                        return `
                            <select data-property="level">
                                <option value="1"><?php _e('Rubrik 1', 'bkgt-document-management'); ?></option>
                                <option value="2"><?php _e('Rubrik 2', 'bkgt-document-management'); ?></option>
                                <option value="3"><?php _e('Rubrik 3', 'bkgt-document-management'); ?></option>
                            </select>
                            <input type="text" placeholder="<?php _e('Rubriktext', 'bkgt-document-management'); ?>" data-property="content">
                        `;
                    case 'variable':
                        return `
                            <select data-property="variable">
                                <option value=""><?php _e('Välj variabel', 'bkgt-document-management'); ?></option>
                                <option value="{{current_date}}"><?php _e('Aktuellt datum', 'bkgt-document-management'); ?></option>
                                <option value="{{author_name}}"><?php _e('Författarens namn', 'bkgt-document-management'); ?></option>
                                <option value="{{document_title}}"><?php _e('Dokumenttitel', 'bkgt-document-management'); ?></option>
                                <option value="{{team_name}}"><?php _e('Lagnamn', 'bkgt-document-management'); ?></option>
                                <option value="{{player_name}}"><?php _e('Spelares namn', 'bkgt-document-management'); ?></option>
                            </select>
                        `;
                    case 'list':
                        return `
                            <textarea placeholder="<?php _e('Ange listobjekt, ett per rad...', 'bkgt-document-management'); ?>" data-property="content"></textarea>
                            <select data-property="type">
                                <option value="unordered"><?php _e('Punktlista', 'bkgt-document-management'); ?></option>
                                <option value="ordered"><?php _e('Numrerad lista', 'bkgt-document-management'); ?></option>
                            </select>
                        `;
                    case 'table':
                        return `
                            <input type="number" placeholder="<?php _e('Antal kolumner', 'bkgt-document-management'); ?>" min="1" max="10" data-property="columns" value="3">
                            <input type="number" placeholder="<?php _e('Antal rader', 'bkgt-document-management'); ?>" min="1" max="20" data-property="rows" value="3">
                            <div class="bkgt-table-preview" style="margin-top: 10px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;">
                                <p><?php _e('Tabellförhandsgranskning kommer här...', 'bkgt-document-management'); ?></p>
                            </div>
                        `;
                    case 'image':
                        return `
                            <input type="text" placeholder="<?php _e('Bild-URL eller ladda upp...', 'bkgt-document-management'); ?>" data-property="src">
                            <input type="text" placeholder="<?php _e('Alt-text', 'bkgt-document-management'); ?>" data-property="alt">
                            <button class="button bkgt-upload-image"><?php _e('Ladda upp bild', 'bkgt-document-management'); ?></button>
                        `;
                    case 'divider':
                        return `<hr style="border: none; border-top: 2px solid #007cba; margin: 20px 0;">`;
                    default:
                        return `<p><?php _e('Okänd komponenttyp', 'bkgt-document-management'); ?></p>`;
                }
            }

            function selectComponent(component) {
                $('.bkgt-canvas-component').removeClass('selected');
                component.addClass('selected');
                selectedComponent = component;
                updatePropertiesPanel();
            }

            function updatePropertiesPanel() {
                const panel = $('#bkgt-properties-panel');

                if (!selectedComponent) {
                    panel.html('<p><?php _e('Välj en komponent för att redigera dess egenskaper.', 'bkgt-document-management'); ?></p>');
                    return;
                }

                const type = selectedComponent.data('type');
                const properties = getComponentProperties(type);

                let html = `<h4>${getComponentLabel(type)} - <?php _e('Egenskaper', 'bkgt-document-management'); ?></h4>`;

                properties.forEach(prop => {
                    const currentValue = selectedComponent.find(`[data-property="${prop.name}"]`).val() || '';
                    html += `
                        <div class="bkgt-property-group">
                            <label for="prop_${prop.name}">${prop.label}</label>
                            ${prop.type === 'textarea' ?
                                `<textarea id="prop_${prop.name}" data-property="${prop.name}" rows="3">${currentValue}</textarea>` :
                                `<input type="${prop.type}" id="prop_${prop.name}" data-property="${prop.name}" value="${currentValue}" ${prop.options ? '' : ''}>`
                            }
                            ${prop.options ? `<select id="prop_${prop.name}" data-property="${prop.name}">${prop.options.map(opt => `<option value="${opt.value}" ${opt.value === currentValue ? 'selected' : ''}>${opt.label}</option>`).join('')}</select>` : ''}
                        </div>
                    `;
                });

                panel.html(html);

                // Bind property changes
                panel.find('input, textarea, select').on('input change', function() {
                    const property = $(this).data('property');
                    const value = $(this).val();
                    selectedComponent.find(`[data-property="${property}"]`).val(value);
                    updateComponentPreview(selectedComponent);
                });
            }

            function getComponentProperties(type) {
                const baseProps = [
                    { name: 'id', label: '<?php _e('ID', 'bkgt-document-management'); ?>', type: 'text' },
                    { name: 'class', label: '<?php _e('CSS-klass', 'bkgt-document-management'); ?>', type: 'text' }
                ];

                const typeProps = {
                    'text': [
                        { name: 'content', label: '<?php _e('Innehåll', 'bkgt-document-management'); ?>', type: 'textarea' }
                    ],
                    'heading': [
                        { name: 'level', label: '<?php _e('Nivå', 'bkgt-document-management'); ?>', type: 'select', options: [
                            { value: '1', label: 'H1' }, { value: '2', label: 'H2' }, { value: '3', label: 'H3' }
                        ]},
                        { name: 'content', label: '<?php _e('Text', 'bkgt-document-management'); ?>', type: 'text' }
                    ],
                    'variable': [
                        { name: 'variable', label: '<?php _e('Variabel', 'bkgt-document-management'); ?>', type: 'select', options: [
                            { value: '{{current_date}}', label: '<?php _e('Aktuellt datum', 'bkgt-document-management'); ?>' },
                            { value: '{{author_name}}', label: '<?php _e('Författarens namn', 'bkgt-document-management'); ?>' },
                            { value: '{{document_title}}', label: '<?php _e('Dokumenttitel', 'bkgt-document-management'); ?>' },
                            { value: '{{team_name}}', label: '<?php _e('Lagnamn', 'bkgt-document-management'); ?>' },
                            { value: '{{player_name}}', label: '<?php _e('Spelares namn', 'bkgt-document-management'); ?>' }
                        ]}
                    ],
                    'list': [
                        { name: 'content', label: '<?php _e('Objekt', 'bkgt-document-management'); ?>', type: 'textarea' },
                        { name: 'type', label: '<?php _e('Typ', 'bkgt-document-management'); ?>', type: 'select', options: [
                            { value: 'unordered', label: '<?php _e('Punktlista', 'bkgt-document-management'); ?>' },
                            { value: 'ordered', label: '<?php _e('Numrerad lista', 'bkgt-document-management'); ?>' }
                        ]}
                    ],
                    'table': [
                        { name: 'columns', label: '<?php _e('Kolumner', 'bkgt-document-management'); ?>', type: 'number' },
                        { name: 'rows', label: '<?php _e('Rader', 'bkgt-document-management'); ?>', type: 'number' }
                    ],
                    'image': [
                        { name: 'src', label: '<?php _e('Bildkälla', 'bkgt-document-management'); ?>', type: 'text' },
                        { name: 'alt', label: '<?php _e('Alt-text', 'bkgt-document-management'); ?>', type: 'text' },
                        { name: 'width', label: '<?php _e('Bredd', 'bkgt-document-management'); ?>', type: 'number' },
                        { name: 'height', label: '<?php _e('Höjd', 'bkgt-document-management'); ?>', type: 'number' }
                    ]
                };

                return [...(typeProps[type] || []), ...baseProps];
            }

            function updateComponentPreview(component) {
                // Update visual preview if needed
                const type = component.data('type');
                if (type === 'table') {
                    const columns = parseInt(component.find('[data-property="columns"]').val()) || 3;
                    const rows = parseInt(component.find('[data-property="rows"]').val()) || 3;

                    let tableHtml = '<table border="1" style="width: 100%; border-collapse: collapse;">';
                    for (let i = 0; i < rows; i++) {
                        tableHtml += '<tr>';
                        for (let j = 0; j < columns; j++) {
                            tableHtml += `<td style="padding: 8px;">${i === 0 ? '<?php _e('Kolumn', 'bkgt-document-management'); ?> ' + (j + 1) : ''}</td>`;
                        }
                        tableHtml += '</tr>';
                    }
                    tableHtml += '</table>';

                    component.find('.bkgt-table-preview').html(tableHtml);
                }
            }

            function saveTemplate() {
                const title = $('#bkgt-template-title').val();
                const description = $('#bkgt-template-description').val();

                if (!title) {
                    alert('<?php _e('Ange ett namn för mallen.', 'bkgt-document-management'); ?>');
                    return;
                }

                const components = [];
                $('.bkgt-canvas-component').each(function() {
                    const component = $(this);
                    const type = component.data('type');
                    const properties = {};

                    component.find('[data-property]').each(function() {
                        const propName = $(this).data('property');
                        const propValue = $(this).val();
                        if (propValue) {
                            properties[propName] = propValue;
                        }
                    });

                    components.push({
                        type: type,
                        properties: properties
                    });
                });

                const templateData = {
                    title: title,
                    description: description,
                    components: components
                };

                // Save via AJAX
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bkgt_save_template',
                        nonce: '<?php echo wp_create_nonce('bkgt-template-nonce'); ?>',
                        template: JSON.stringify(templateData)
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Mallen har sparats!', 'bkgt-document-management'); ?>');
                        } else {
                            alert(response.data || '<?php _e('Ett fel uppstod när mallen skulle sparas.', 'bkgt-document-management'); ?>');
                        }
                    }
                });
            }

            function previewTemplate() {
                const components = $('.bkgt-canvas-component');
                let previewHtml = '';

                components.each(function() {
                    const component = $(this);
                    const type = component.data('type');
                    previewHtml += generateComponentPreview(component, type);
                });

                $('#bkgt-preview-content').html(previewHtml);
                $('#bkgt-template-preview-modal').show();
            }

            function generateComponentPreview(component, type) {
                const properties = {};
                component.find('[data-property]').each(function() {
                    properties[$(this).data('property')] = $(this).val();
                });

                switch (type) {
                    case 'text':
                        return `<p>${properties.content || '<?php _e('Textinnehåll', 'bkgt-document-management'); ?>'}</p>`;
                    case 'heading':
                        const level = properties.level || 1;
                        return `<h${level}>${properties.content || '<?php _e('Rubrik', 'bkgt-document-management'); ?>'}</h${level}>`;
                    case 'variable':
                        return `<span class="bkgt-variable">${properties.variable || '{{variable}}'}</span>`;
                    case 'list':
                        const items = (properties.content || '').split('\n').filter(item => item.trim());
                        const listType = properties.type === 'ordered' ? 'ol' : 'ul';
                        const listItems = items.map(item => `<li>${item.trim()}</li>`).join('');
                        return `<${listType}>${listItems}</${listType}>`;
                    case 'table':
                        const cols = parseInt(properties.columns) || 3;
                        const rows = parseInt(properties.rows) || 3;
                        let table = '<table border="1" style="width: 100%; border-collapse: collapse;">';
                        for (let i = 0; i < rows; i++) {
                            table += '<tr>';
                            for (let j = 0; j < cols; j++) {
                                table += '<td style="padding: 8px;">&nbsp;</td>';
                            }
                            table += '</tr>';
                        }
                        table += '</table>';
                        return table;
                    case 'image':
                        return `<img src="${properties.src || ''}" alt="${properties.alt || ''}" style="max-width: 100%; height: auto;">`;
                    case 'divider':
                        return '<hr style="border: none; border-top: 2px solid #007cba; margin: 20px 0;">';
                    default:
                        return `<div><?php _e('Okänd komponent', 'bkgt-document-management'); ?>: ${type}</div>`;
                }
            }

            function clearCanvas() {
                if (confirm('<?php _e('Är du säker på att du vill rensa arbetsytan? Alla osparade ändringar kommer att gå förlorade.', 'bkgt-document-management'); ?>')) {
                    $('#bkgt-canvas-content').html(`
                        <div class="bkgt-canvas-placeholder">
                            <i class="dashicons dashicons-plus-alt"></i>
                            <p><?php _e('Dra komponenter hit för att börja bygga din mall', 'bkgt-document-management'); ?></p>
                        </div>
                    `);
                    selectedComponent = null;
                    updatePropertiesPanel();
                }
            }
        });
        </script>
        <?php
    }

    /**
     * Template Builder Page
     */
    public function template_builder_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Mallbyggare', 'bkgt-document-management'); ?></h1>

            <div class="bkgt-template-builder">
                <!-- Toolbar -->
                <div class="bkgt-builder-toolbar">
                    <div class="bkgt-toolbar-left">
                        <input type="text" id="bkgt-template-title" placeholder="<?php _e('Mallnamn', 'bkgt-document-management'); ?>" class="regular-text">
                        <input type="text" id="bkgt-template-description" placeholder="<?php _e('Beskrivning (valfritt)', 'bkgt-document-management'); ?>" class="regular-text">
                    </div>
                    <div class="bkgt-toolbar-right">
                        <button id="bkgt-preview-template" class="button">
                            <i class="dashicons dashicons-visibility"></i>
                            <?php _e('Förhandsgranska', 'bkgt-document-management'); ?>
                        </button>
                        <button id="bkgt-save-template" class="button button-primary">
                            <i class="dashicons dashicons-save"></i>
                            <?php _e('Spara mall', 'bkgt-document-management'); ?>
                        </button>
                        <button id="bkgt-clear-canvas" class="button">
                            <i class="dashicons dashicons-trash"></i>
                            <?php _e('Rensa', 'bkgt-document-management'); ?>
                        </button>
                    </div>
                </div>

                <div class="bkgt-builder-content">
                    <!-- Component Library -->
                    <div class="bkgt-component-library">
                        <h3><?php _e('Komponenter', 'bkgt-document-management'); ?></h3>
                        <div class="bkgt-component-list">
                            <div class="bkgt-component-item" draggable="true" data-type="heading">
                                <i class="dashicons dashicons-editor-bold"></i>
                                <span><?php _e('Rubrik', 'bkgt-document-management'); ?></span>
                            </div>
                            <div class="bkgt-component-item" draggable="true" data-type="text">
                                <i class="dashicons dashicons-editor-paragraph"></i>
                                <span><?php _e('Text', 'bkgt-document-management'); ?></span>
                            </div>
                            <div class="bkgt-component-item" draggable="true" data-type="variable">
                                <i class="dashicons dashicons-admin-generic"></i>
                                <span><?php _e('Variabel', 'bkgt-document-management'); ?></span>
                            </div>
                            <div class="bkgt-component-item" draggable="true" data-type="list">
                                <i class="dashicons dashicons-editor-ul"></i>
                                <span><?php _e('Lista', 'bkgt-document-management'); ?></span>
                            </div>
                            <div class="bkgt-component-item" draggable="true" data-type="table">
                                <i class="dashicons dashicons-editor-table"></i>
                                <span><?php _e('Tabell', 'bkgt-document-management'); ?></span>
                            </div>
                            <div class="bkgt-component-item" draggable="true" data-type="image">
                                <i class="dashicons dashicons-format-image"></i>
                                <span><?php _e('Bild', 'bkgt-document-management'); ?></span>
                            </div>
                            <div class="bkgt-component-item" draggable="true" data-type="divider">
                                <i class="dashicons dashicons-minus"></i>
                                <span><?php _e('Avdelare', 'bkgt-document-management'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Canvas -->
                    <div class="bkgt-canvas">
                        <div class="bkgt-canvas-header">
                            <h3><?php _e('Arbetsyta', 'bkgt-document-management'); ?></h3>
                        </div>
                        <div id="bkgt-canvas-content" class="bkgt-canvas-content">
                            <div class="bkgt-canvas-placeholder">
                                <i class="dashicons dashicons-plus-alt"></i>
                                <p><?php _e('Dra komponenter hit för att börja bygga din mall', 'bkgt-document-management'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Properties Panel -->
                    <div class="bkgt-properties-panel">
                        <h3><?php _e('Egenskaper', 'bkgt-document-management'); ?></h3>
                        <div id="bkgt-properties-content">
                            <p class="bkgt-no-selection"><?php _e('Välj en komponent för att redigera dess egenskaper', 'bkgt-document-management'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Modal -->
            <div id="bkgt-preview-modal" class="bkgt-modal">
                <div class="bkgt-modal-content">
                    <div class="bkgt-modal-header">
                        <h2><?php _e('Förhandsgranskning', 'bkgt-document-management'); ?></h2>
                        <button class="bkgt-modal-close">&times;</button>
                    </div>
                    <div class="bkgt-modal-body">
                        <div id="bkgt-preview-content"></div>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            let selectedComponent = null;
            let componentCounter = 0;

            // Component library drag start
            $('.bkgt-component-item').on('dragstart', function(e) {
                e.originalEvent.dataTransfer.setData('text/plain', $(this).data('type'));
                $(this).addClass('dragging');
            });

            $('.bkgt-component-item').on('dragend', function(e) {
                $(this).removeClass('dragging');
            });

            // Canvas drag over
            $('#bkgt-canvas-content').on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });

            $('#bkgt-canvas-content').on('dragleave', function(e) {
                $(this).removeClass('drag-over');
            });

            // Canvas drop
            $('#bkgt-canvas-content').on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');

                const componentType = e.originalEvent.dataTransfer.getData('text/plain');
                if (componentType) {
                    addComponentToCanvas(componentType, e.originalEvent);
                }
            });

            // Component selection
            $(document).on('click', '.bkgt-canvas-component', function(e) {
                if (!$(e.target).is('button, input, textarea, select')) {
                    selectComponent($(this));
                }
            });

            // Component editing
            $(document).on('input', '.bkgt-component-content textarea, .bkgt-component-content input', function() {
                updateComponentPreview($(this).closest('.bkgt-canvas-component'));
            });

            // Component actions
            $(document).on('click', '.bkgt-delete-component', function(e) {
                e.stopPropagation();
                $(this).closest('.bkgt-canvas-component').remove();
                if (selectedComponent && selectedComponent.is($(this).closest('.bkgt-canvas-component'))) {
                    selectedComponent = null;
                    updatePropertiesPanel();
                }
            });

            $(document).on('click', '.bkgt-duplicate-component', function(e) {
                e.stopPropagation();
                const component = $(this).closest('.bkgt-canvas-component');
                const newComponent = component.clone();
                newComponent.find('.bkgt-component-id').val('');
                component.after(newComponent);
                selectComponent(newComponent);
            });

            // Toolbar actions
            $('#bkgt-save-template').on('click', saveTemplate);
            $('#bkgt-preview-template').on('click', previewTemplate);
            $('#bkgt-clear-canvas').on('click', clearCanvas);

            // Modal close
            $('.bkgt-modal-close').on('click', function() {
                $(this).closest('.bkgt-modal').hide();
            });

            $(window).on('click', function(e) {
                if ($(e.target).hasClass('bkgt-modal')) {
                    $('.bkgt-modal').hide();
                }
            });

            function addComponentToCanvas(type, event) {
                componentCounter++;
                const componentId = 'component_' + componentCounter;
                const componentHtml = createComponentHtml(type, componentId);

                if (event) {
                    // Insert at drop position
                    const rect = $('#bkgt-canvas-content')[0].getBoundingClientRect();
                    const y = event.clientY - rect.top;

                    let insertBefore = null;
                    $('.bkgt-canvas-component').each(function() {
                        const componentRect = this.getBoundingClientRect();
                        const componentY = componentRect.top - rect.top + componentRect.height / 2;
                        if (y < componentY) {
                            insertBefore = this;
                            return false;
                        }
                    });

                    if (insertBefore) {
                        $(insertBefore).before(componentHtml);
                    } else {
                        $('#bkgt-canvas-content').append(componentHtml);
                    }
                } else {
                    $('#bkgt-canvas-content').append(componentHtml);
                }

                $('.bkgt-canvas-placeholder').hide();
                const newComponent = $('#bkgt-canvas-content .bkgt-canvas-component').last();
                selectComponent(newComponent);
            }

            function createComponentHtml(type, id) {
                const baseHtml = `
                    <div class="bkgt-canvas-component" data-type="${type}" data-id="${id}">
                        <div class="bkgt-component-header">
                            <span class="bkgt-component-type">${getComponentLabel(type)}</span>
                            <div class="bkgt-component-actions">
                                <button class="button button-small bkgt-duplicate-component" title="Duplicera">
                                    <i class="dashicons dashicons-admin-page"></i>
                                </button>
                                <button class="button button-small bkgt-delete-component" title="Ta bort">
                                    <i class="dashicons dashicons-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="bkgt-component-content">
                            ${getComponentContentHtml(type, id)}
                        </div>
                    </div>
                `;
                return baseHtml;
            }

            function getComponentLabel(type) {
                const labels = {
                    'heading': '<?php _e('Rubrik', 'bkgt-document-management'); ?>',
                    'text': '<?php _e('Text', 'bkgt-document-management'); ?>',
                    'variable': '<?php _e('Variabel', 'bkgt-document-management'); ?>',
                    'list': '<?php _e('Lista', 'bkgt-document-management'); ?>',
                    'table': '<?php _e('Tabell', 'bkgt-document-management'); ?>',
                    'image': '<?php _e('Bild', 'bkgt-document-management'); ?>',
                    'divider': '<?php _e('Avdelare', 'bkgt-document-management'); ?>'
                };
                return labels[type] || type;
            }

            function getComponentContentHtml(type, id) {
                switch (type) {
                    case 'heading':
                        return `
                            <select class="bkgt-heading-level">
                                <option value="h1">H1</option>
                                <option value="h2">H2</option>
                                <option value="h3" selected>H3</option>
                                <option value="h4">H4</option>
                                <option value="h5">H5</option>
                                <option value="h6">H6</option>
                            </select>
                            <input type="text" placeholder="<?php _e('Rubriktext', 'bkgt-document-management'); ?>" class="bkgt-heading-text">
                        `;
                    case 'text':
                        return `<textarea placeholder="<?php _e('Skriv din text här...', 'bkgt-document-management'); ?>" rows="3"></textarea>`;
                    case 'variable':
                        return `
                            <input type="text" placeholder="<?php _e('Variabelnamn (t.ex. {{namn}})', 'bkgt-document-management'); ?>" class="bkgt-variable-name">
                            <input type="text" placeholder="<?php _e('Beskrivning', 'bkgt-document-management'); ?>" class="bkgt-variable-description">
                        `;
                    case 'list':
                        return `
                            <select class="bkgt-list-type">
                                <option value="ul"><?php _e('Punktlista', 'bkgt-document-management'); ?></option>
                                <option value="ol"><?php _e('Numrerad lista', 'bkgt-document-management'); ?></option>
                            </select>
                            <textarea placeholder="<?php _e('En rad per listobjekt...', 'bkgt-document-management'); ?>" rows="3" class="bkgt-list-items"></textarea>
                        `;
                    case 'table':
                        return `
                            <input type="number" placeholder="<?php _e('Kolumner', 'bkgt-document-management'); ?>" class="bkgt-table-cols" min="1" max="10" value="3">
                            <input type="number" placeholder="<?php _e('Rader', 'bkgt-document-management'); ?>" class="bkgt-table-rows" min="1" max="20" value="2">
                            <button class="button bkgt-create-table"><?php _e('Skapa tabell', 'bkgt-document-management'); ?></button>
                            <div class="bkgt-table-preview" style="display:none;"></div>
                        `;
                    case 'image':
                        return `
                            <input type="url" placeholder="<?php _e('Bild-URL', 'bkgt-document-management'); ?>" class="bkgt-image-url">
                            <input type="text" placeholder="<?php _e('Alt-text', 'bkgt-document-management'); ?>" class="bkgt-image-alt">
                            <button class="button bkgt-select-image"><?php _e('Välj bild', 'bkgt-document-management'); ?></button>
                        `;
                    case 'divider':
                        return `<hr style="border: 1px solid #ddd; margin: 10px 0;">`;
                    default:
                        return '';
                }
            }

            function selectComponent(component) {
                $('.bkgt-canvas-component').removeClass('selected');
                component.addClass('selected');
                selectedComponent = component;
                updatePropertiesPanel();
            }

            function updatePropertiesPanel() {
                if (!selectedComponent) {
                    $('#bkgt-properties-content').html('<p class="bkgt-no-selection"><?php _e('Välj en komponent för att redigera dess egenskaper', 'bkgt-document-management'); ?></p>');
                    return;
                }

                const type = selectedComponent.data('type');
                const id = selectedComponent.data('id');
                let propertiesHtml = `<h4>${getComponentLabel(type)} - Egenskaper</h4>`;

                switch (type) {
                    case 'heading':
                        const level = selectedComponent.find('.bkgt-heading-level').val();
                        const text = selectedComponent.find('.bkgt-heading-text').val();
                        propertiesHtml += `
                            <div class="bkgt-property-group">
                                <label><?php _e('Rubriknivå:', 'bkgt-document-management'); ?></label>
                                <select class="bkgt-prop-heading-level" data-target=".bkgt-heading-level">
                                    <option value="h1" ${level === 'h1' ? 'selected' : ''}>H1</option>
                                    <option value="h2" ${level === 'h2' ? 'selected' : ''}>H2</option>
                                    <option value="h3" ${level === 'h3' ? 'selected' : ''}>H3</option>
                                    <option value="h4" ${level === 'h4' ? 'selected' : ''}>H4</option>
                                    <option value="h5" ${level === 'h5' ? 'selected' : ''}>H5</option>
                                    <option value="h6" ${level === 'h6' ? 'selected' : ''}>H6</option>
                                </select>
                            </div>
                            <div class="bkgt-property-group">
                                <label><?php _e('Text:', 'bkgt-document-management'); ?></label>
                                <input type="text" class="bkgt-prop-heading-text" data-target=".bkgt-heading-text" value="${text}">
                            </div>
                        `;
                        break;
                    case 'text':
                        const textContent = selectedComponent.find('textarea').val();
                        propertiesHtml += `
                            <div class="bkgt-property-group">
                                <label><?php _e('Innehåll:', 'bkgt-document-management'); ?></label>
                                <textarea class="bkgt-prop-text-content" data-target="textarea" rows="5">${textContent}</textarea>
                            </div>
                        `;
                        break;
                    case 'variable':
                        const varName = selectedComponent.find('.bkgt-variable-name').val();
                        const varDesc = selectedComponent.find('.bkgt-variable-description').val();
                        propertiesHtml += `
                            <div class="bkgt-property-group">
                                <label><?php _e('Variabelnamn:', 'bkgt-document-management'); ?></label>
                                <input type="text" class="bkgt-prop-variable-name" data-target=".bkgt-variable-name" value="${varName}" placeholder="{{namn}}">
                            </div>
                            <div class="bkgt-property-group">
                                <label><?php _e('Beskrivning:', 'bkgt-document-management'); ?></label>
                                <input type="text" class="bkgt-prop-variable-desc" data-target=".bkgt-variable-description" value="${varDesc}">
                            </div>
                        `;
                        break;
                }

                $('#bkgt-properties-content').html(propertiesHtml);

                // Bind property changes
                $('#bkgt-properties-content input, #bkgt-properties-content textarea, #bkgt-properties-content select').on('input change', function() {
                    const target = $(this).data('target');
                    const value = $(this).val();
                    selectedComponent.find(target).val(value);
                    updateComponentPreview(selectedComponent);
                });
            }

            function updateComponentPreview(component) {
                const type = component.data('type');
                // Update preview based on component type
                // This would be expanded for each component type
            }

            function saveTemplate() {
                const title = $('#bkgt-template-title').val().trim();
                if (!title) {
                    alert('<?php _e('Ange ett namn för mallen.', 'bkgt-document-management'); ?>');
                    return;
                }

                const components = [];
                $('.bkgt-canvas-component').each(function() {
                    const component = $(this);
                    const type = component.data('type');
                    const id = component.data('id');
                    let data = { type, id };

                    switch (type) {
                        case 'heading':
                            data.level = component.find('.bkgt-heading-level').val();
                            data.text = component.find('.bkgt-heading-text').val();
                            break;
                        case 'text':
                            data.content = component.find('textarea').val();
                            break;
                        case 'variable':
                            data.name = component.find('.bkgt-variable-name').val();
                            data.description = component.find('.bkgt-variable-description').val();
                            break;
                        case 'list':
                            data.listType = component.find('.bkgt-list-type').val();
                            data.items = component.find('.bkgt-list-items').val().split('\n').filter(item => item.trim());
                            break;
                        case 'image':
                            data.url = component.find('.bkgt-image-url').val();
                            data.alt = component.find('.bkgt-image-alt').val();
                            break;
                    }

                    components.push(data);
                });

                const templateData = {
                    title: title,
                    description: $('#bkgt-template-description').val().trim(),
                    components: components
                };

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bkgt_save_template',
                        template: JSON.stringify(templateData),
                        nonce: '<?php echo wp_create_nonce('bkgt-template-nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                        } else {
                            alert('<?php _e('Ett fel uppstod när mallen skulle sparas.', 'bkgt-document-management'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Ett fel uppstod när mallen skulle sparas.', 'bkgt-document-management'); ?>');
                    }
                });
            }

            function previewTemplate() {
                const components = [];
                $('.bkgt-canvas-component').each(function() {
                    const component = $(this);
                    const type = component.data('type');
                    let preview = '';

                    switch (type) {
                        case 'heading':
                            const level = component.find('.bkgt-heading-level').val();
                            const text = component.find('.bkgt-heading-text').val() || '<?php _e('[Rubrik]', 'bkgt-document-management'); ?>';
                            preview = `<${level}>${text}</${level}>`;
                            break;
                        case 'text':
                            const content = component.find('textarea').val() || '<?php _e('[Textinnehåll]', 'bkgt-document-management'); ?>';
                            preview = `<p>${content.replace(/\n/g, '<br>')}</p>`;
                            break;
                        case 'variable':
                            const varName = component.find('.bkgt-variable-name').val() || '{{variabel}}';
                            preview = `<span class="bkgt-variable">${varName}</span>`;
                            break;
                        case 'list':
                            const listType = component.find('.bkgt-list-type').val();
                            const items = component.find('.bkgt-list-items').val().split('\n').filter(item => item.trim());
                            if (items.length > 0) {
                                preview = `<${listType}>${items.map(item => `<li>${item}</li>`).join('')}</${listType}>`;
                            } else {
                                preview = `<${listType}><li><?php _e('[Listobjekt]', 'bkgt-document-management'); ?></li></${listType}>`;
                            }
                            break;
                        case 'image':
                            const url = component.find('.bkgt-image-url').val();
                            const alt = component.find('.bkgt-image-alt').val() || '';
                            preview = url ? `<img src="${url}" alt="${alt}" style="max-width:100%;">` : '<div style="border:2px dashed #ccc;padding:20px;text-align:center;">[<?php _e('Bild', 'bkgt-document-management'); ?>]</div>';
                            break;
                        case 'divider':
                            preview = '<hr>';
                            break;
                    }

                    components.push(preview);
                });

                $('#bkgt-preview-content').html(components.join(''));
                $('#bkgt-preview-modal').show();
            }

            function clearCanvas() {
                if (confirm('<?php _e('Är du säker på att du vill rensa arbetsytan? Alla osparade ändringar kommer att gå förlorade.', 'bkgt-document-management'); ?>')) {
                    $('#bkgt-canvas-content').html(`
                        <div class="bkgt-canvas-placeholder">
                            <i class="dashicons dashicons-plus-alt"></i>
                            <p><?php _e('Dra komponenter hit för att börja bygga din mall', 'bkgt-document-management'); ?></p>
                        </div>
                    `);
                    selectedComponent = null;
                    updatePropertiesPanel();
                }
            }
        });
        </script>
        <?php
    }

    /**
     * AJAX: Save Template - TEMPORARILY DISABLED DUE TO DUPLICATE
     */
    /*
    public function ajax_save_template() {
        check_ajax_referer('bkgt-template-nonce', 'nonce');

        $template_data = json_decode(stripslashes($_POST['template']), true);

        if (!$template_data || !isset($template_data['title'])) {
            wp_send_json_error(__('Ogiltig malldata.', 'bkgt-document-management'));
        }

        // Create or update template post
        $template_args = array(
            'post_title' => sanitize_text_field($template_data['title']),
            'post_content' => wp_json_encode($template_data['components']),
            'post_excerpt' => sanitize_text_field($template_data['description']),
            'post_type' => 'bkgt_template',
            'post_status' => 'publish',
            'meta_input' => array(
                '_bkgt_template_data' => $template_data
            )
        );

        // Check if template already exists (by title)
        $existing_template = get_posts(array(
            'post_type' => 'bkgt_template',
            'title' => $template_data['title'],
            'posts_per_page' => 1
        ));

        if (!empty($existing_template)) {
            $template_args['ID'] = $existing_template[0]->ID;
            $template_id = wp_update_post($template_args);
        } else {
            $template_id = wp_insert_post($template_args);
        }

        if (is_wp_error($template_id)) {
            wp_send_json_error(__('Kunde inte spara mallen.', 'bkgt-document-management'));
        }

        wp_send_json_success(array(
            'template_id' => $template_id,
            'message' => __('Mallen har sparats framgångsrikt.', 'bkgt-document-management')
        ));
    }
    */
}