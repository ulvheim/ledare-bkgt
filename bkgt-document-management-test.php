<?php
/**
 * Plugin Name: BKGT Document Management - Test Version
 * Plugin URI: https://bkgt.se
 * Description: Minimal test version of DMS with function tabs.
 * Version: 1.0.0
 * Author: BKGT Development Team
 * License: GPL v2 or later
 * Text Domain: bkgt-document-management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin Class
 */
class BKGT_Document_Management_Test {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
    }

    public function init() {
        add_shortcode('bkgt_documents', array($this, 'documents_shortcode'));
        add_action('wp_ajax_bkgt_load_dms_content', array($this, 'ajax_load_dms_content'));
        add_action('wp_ajax_nopriv_bkgt_load_dms_content', array($this, 'ajax_load_dms_content'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_style('bkgt-dms-style', plugins_url('assets/css/frontend.css', __FILE__));
    }

    public function documents_shortcode($atts) {
        $active_tab = isset($_GET['bkgt_tab']) ? sanitize_text_field($_GET['bkgt_tab']) : 'browse';

        ob_start();
        ?>
        <div class="bkgt-dms">
            <h2><?php _e('Document Management System', 'bkgt-document-management'); ?></h2>
            <?php $this->display_dms_function_tabs($active_tab); ?>
            <div class="bkgt-dms-content">
                <?php $this->display_tab_content($active_tab); ?>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.bkgt-dms-tab-link').on('click', function(e) {
                e.preventDefault();
                var tab = $(this).data('tab');
                $('.bkgt-dms-tab-link').removeClass('active');
                $(this).addClass('active');
                var url = new URL(window.location);
                url.searchParams.set('bkgt_tab', tab);
                window.history.pushState({}, '', url);
                loadTabContent(tab);
            });

            function loadTabContent(tab) {
                $('.bkgt-dms-content').html('<p>Loading...</p>');
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: { action: 'bkgt_load_dms_content', tab: tab },
                    success: function(response) {
                        $('.bkgt-dms-content').html(response.data.html);
                    },
                    error: function() {
                        $('.bkgt-dms-content').html('<p>Error loading content.</p>');
                    }
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    private function display_dms_function_tabs($active_tab = 'browse') {
        $tabs = array(
            'browse' => __('Browse Documents', 'bkgt-document-management'),
            'upload' => __('Upload Document', 'bkgt-document-management'),
            'search' => __('Search & Filter', 'bkgt-document-management'),
            'permissions' => __('Permissions', 'bkgt-document-management'),
        );

        ?>
        <div class="bkgt-dms-tabs">
            <div class="bkgt-dms-tab-navigation">
                <?php foreach ($tabs as $tab_key => $tab_label) : ?>
                    <a href="#" class="bkgt-dms-tab-link <?php echo ($active_tab === $tab_key) ? 'active' : ''; ?>" data-tab="<?php echo esc_attr($tab_key); ?>">
                        <?php echo esc_html($tab_label); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private function display_tab_content($active_tab) {
        switch ($active_tab) {
            case 'browse':
                echo '<div class="bkgt-browse-section"><h3>Browse Documents</h3><p>Document browsing functionality will be implemented here.</p></div>';
                break;
            case 'upload':
                echo '<div class="bkgt-upload-section"><h3>Upload Document</h3><p>Document upload functionality will be implemented here.</p></div>';
                break;
            case 'search':
                echo '<div class="bkgt-search-section"><h3>Search & Filter</h3><p>Search functionality will be implemented here.</p></div>';
                break;
            case 'permissions':
                echo '<div class="bkgt-permissions-section"><h3>Permissions</h3><p>Permissions management will be implemented here.</p></div>';
                break;
            default:
                echo '<div class="bkgt-browse-section"><h3>Browse Documents</h3><p>Document browsing functionality will be implemented here.</p></div>';
        }
    }

    public function ajax_load_dms_content() {
        $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : 'browse';

        ob_start();
        $this->display_tab_content($tab);
        $content = ob_get_clean();

        wp_send_json_success(array('html' => $content));
    }
}

/**
 * Initialize the plugin
 */
function bkgt_document_management_test() {
    return BKGT_Document_Management_Test::get_instance();
}
add_action('plugins_loaded', 'bkgt_document_management_test');
?>