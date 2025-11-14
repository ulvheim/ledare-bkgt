<?php
/**
 * Document Viewer Class - PDF and Office Document Viewer
 *
 * @package BKGT_Document_Management
 * @since 1.0.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Document_Viewer {

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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_viewer_scripts'));
        add_shortcode('bkgt_document_viewer', array($this, 'document_viewer_shortcode'));
        add_action('wp_ajax_bkgt_get_document_viewer', array($this, 'ajax_get_document_viewer'));
        add_action('wp_ajax_bkgt_get_document_content', array($this, 'ajax_get_document_content'));
    }

    /**
     * Enqueue viewer scripts and styles
     */
    public function enqueue_viewer_scripts() {
        // Only load on pages with the document viewer shortcode
        global $post;
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'bkgt_document_viewer')) {
            return;
        }

        // Enqueue PDF.js library
        wp_enqueue_script('pdfjs', 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js', array(), '3.11.174', true);

        // Enqueue viewer CSS
        wp_enqueue_style('bkgt-document-viewer', BKGT_DM_PLUGIN_URL . 'assets/css/document-viewer.css', array(), BKGT_DM_VERSION);

        // Enqueue viewer JS
        wp_enqueue_script('bkgt-document-viewer', BKGT_DM_PLUGIN_URL . 'assets/js/document-viewer.js',
            array('jquery', 'pdfjs'), BKGT_DM_VERSION, true);

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
     * Document viewer shortcode
     */
    public function document_viewer_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'width' => '100%',
            'height' => '600px',
        ), $atts);

        if (!$atts['id'] || !is_user_logged_in()) {
            return '<p>' . __('Ogiltigt dokument-ID eller otillräckliga behörigheter.', 'bkgt-document-management') . '</p>';
        }

        // Verify user has access to this document
        if (!$this->user_can_view_document($atts['id'])) {
            return '<p>' . __('Du har inte behörighet att visa detta dokument.', 'bkgt-document-management') . '</p>';
        }

        $document = get_post($atts['id']);
        if (!$document || $document->post_type !== 'bkgt_document') {
            return '<p>' . __('Dokumentet hittades inte.', 'bkgt-document-management') . '</p>';
        }

        ob_start();
        ?>
        <div class="bkgt-document-viewer-container" data-document-id="<?php echo esc_attr($atts['id']); ?>"
             style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;">
            <div class="bkgt-document-viewer-toolbar">
                <div class="bkgt-viewer-info">
                    <h4><?php echo esc_html($document->post_title); ?></h4>
                    <span class="bkgt-document-meta">
                        <?php echo esc_html(get_the_author_meta('display_name', $document->post_author)); ?> |
                        <?php echo esc_html(get_the_date('', $document)); ?>
                    </span>
                </div>
                <div class="bkgt-viewer-controls">
                    <button class="bkgt-zoom-out" title="<?php _e('Zooma ut', 'bkgt-document-management'); ?>">
                        <span class="dashicons dashicons-minus"></span>
                    </button>
                    <span class="bkgt-zoom-level">100%</span>
                    <button class="bkgt-zoom-in" title="<?php _e('Zooma in', 'bkgt-document-management'); ?>">
                        <span class="dashicons dashicons-plus"></span>
                    </button>
                    <button class="bkgt-download" title="<?php _e('Ladda ner', 'bkgt-document-management'); ?>">
                        <span class="dashicons dashicons-download"></span>
                    </button>
                </div>
            </div>
            <div class="bkgt-document-viewer-content">
                <div class="bkgt-loading-overlay">
                    <div class="bkgt-spinner"></div>
                    <p><?php _e('Laddar dokument...', 'bkgt-document-management'); ?></p>
                </div>
                <div class="bkgt-document-canvas"></div>
                <div class="bkgt-pdf-navigation" style="display: none;">
                    <button class="bkgt-prev-page">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                    </button>
                    <span class="bkgt-page-info">
                        <?php _e('Sida', 'bkgt-document-management'); ?>
                        <span class="bkgt-current-page">1</span>
                        <?php _e('av', 'bkgt-document-management'); ?>
                        <span class="bkgt-total-pages">1</span>
                    </span>
                    <button class="bkgt-next-page">
                        <span class="dashicons dashicons-arrow-right-alt"></span>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler for getting document viewer data
     */
    public function ajax_get_document_viewer() {
        check_ajax_referer('bkgt_document_viewer', 'nonce');

        $document_id = intval($_POST['document_id']);

        if (!$document_id || !$this->user_can_view_document($document_id)) {
            wp_send_json_error(__('Ogiltigt dokument eller otillräckliga behörigheter.', 'bkgt-document-management'));
        }

        $document = get_post($document_id);
        if (!$document || $document->post_type !== 'bkgt_document') {
            wp_send_json_error(__('Dokumentet hittades inte.', 'bkgt-document-management'));
        }

        // Get document file URL
        $file_url = $this->get_document_file_url($document_id);
        $file_type = $this->get_document_file_type($document_id);

        wp_send_json_success(array(
            'document_id' => $document_id,
            'title' => $document->post_title,
            'file_url' => $file_url,
            'file_type' => $file_type,
            'author' => get_the_author_meta('display_name', $document->post_author),
            'date' => get_the_date('', $document),
        ));
    }

    /**
     * AJAX handler for getting document content (for Office docs)
     */
    public function ajax_get_document_content() {
        check_ajax_referer('bkgt_document_viewer', 'nonce');

        $document_id = intval($_POST['document_id']);

        if (!$document_id || !$this->user_can_view_document($document_id)) {
            wp_send_json_error(__('Ogiltigt dokument eller otillräckliga behörigheter.', 'bkgt-document-management'));
        }

        $file_url = $this->get_document_file_url($document_id);

        // For now, just return the file URL - Office document viewing will be implemented in Phase 2
        wp_send_json_success(array(
            'file_url' => $file_url,
        ));
    }

    /**
     * Check if user can view a document
     */
    private function user_can_view_document($document_id) {
        $document = get_post($document_id);

        if (!$document) {
            return false;
        }

        // Allow if user is the author
        if (get_current_user_id() === $document->post_author) {
            return true;
        }

        // Add additional permission checks here (sharing, roles, etc.)
        // For now, only authors can view their own documents

        return false;
    }

    /**
     * Get document file URL
     */
    private function get_document_file_url($document_id) {
        // First try to get attachment
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'post_parent' => $document_id,
            'posts_per_page' => 1,
        ));

        if (!empty($attachments)) {
            return wp_get_attachment_url($attachments[0]->ID);
        }

        // Fallback to post meta
        $file_id = get_post_meta($document_id, '_bkgt_file_id', true);
        if ($file_id) {
            return wp_get_attachment_url($file_id);
        }

        return false;
    }

    /**
     * Get document file type
     */
    private function get_document_file_type($document_id) {
        $file_url = $this->get_document_file_url($document_id);

        if (!$file_url) {
            return false;
        }

        $path_info = pathinfo($file_url);
        $extension = strtolower($path_info['extension'] ?? '');

        $mime_types = array(
            // PDF
            'pdf' => 'application/pdf',

            // Office documents
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',

            // Other common types
            'txt' => 'text/plain',
            'rtf' => 'application/rtf',
        );

        return $mime_types[$extension] ?? 'application/octet-stream';
    }
}