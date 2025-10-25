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
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_filter('manage_bkgt_document_posts_columns', array($this, 'add_document_columns'));
        add_action('manage_bkgt_document_posts_custom_column', array($this, 'fill_document_columns'), 10, 2);
        add_filter('manage_edit-bkgt_document_sortable_columns', array($this, 'make_columns_sortable'));
        add_action('wp_ajax_bkgt_upload_document', array($this, 'ajax_upload_document'));
        add_action('wp_ajax_bkgt_delete_document', array($this, 'ajax_delete_document'));
        add_action('wp_ajax_bkgt_get_document_versions', array($this, 'ajax_get_document_versions'));
        add_action('wp_ajax_bkgt_restore_version', array($this, 'ajax_restore_version'));
        add_action('wp_ajax_bkgt_manage_access', array($this, 'ajax_manage_access'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Dokumenthantering', 'bkgt-document-management'),
            __('Dokument', 'bkgt-document-management'),
            'edit_documents',
            'bkgt-documents',
            array($this, 'admin_page'),
            'dashicons-media-document',
            30
        );

        add_submenu_page(
            'bkgt-documents',
            __('Alla dokument', 'bkgt-document-management'),
            __('Alla dokument', 'bkgt-document-management'),
            'edit_documents',
            'edit.php?post_type=bkgt_document'
        );

        add_submenu_page(
            'bkgt-documents',
            __('Lägg till nytt', 'bkgt-document-management'),
            __('Lägg till nytt', 'bkgt-document-management'),
            'edit_documents',
            'post-new.php?post_type=bkgt_document'
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
    }

    /**
     * Main admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Dokumenthantering', 'bkgt-document-management'); ?></h1>

            <div class="bkgt-admin-dashboard">
                <?php $this->dashboard_stats(); ?>
                <?php $this->recent_documents(); ?>
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

        wp_enqueue_script('bkgt-document-admin', plugin_dir_url(__FILE__) . '../assets/js/admin.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('bkgt-document-admin', plugin_dir_url(__FILE__) . '../assets/css/admin.css', array(), '1.0.0');

        wp_localize_script('bkgt-document-admin', 'bkgt_document_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkgt_document_admin'),
            'strings' => array(
                'confirm_delete' => __('Är du säker på att du vill radera detta dokument?', 'bkgt-document-management'),
                'uploading' => __('Laddar upp...', 'bkgt-document-management'),
                'upload_success' => __('Dokument uppladdat!', 'bkgt-document-management'),
                'upload_error' => __('Uppladdning misslyckades.', 'bkgt-document-management'),
            ),
        ));
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'bkgt_document_file',
            __('Dokumentfil', 'bkgt-document-management'),
            array($this, 'file_meta_box'),
            'bkgt_document',
            'normal',
            'high'
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
                                echo '<option value="' . esc_attr($team->id) . '">' . esc_html($team->name) . '</option>';
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

        if (!current_user_can('edit_documents')) {
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
}