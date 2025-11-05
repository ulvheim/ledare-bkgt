<?php
/**
 * Deploy Enhanced Document Management Plugin with Tabs
 * This script updates the bkgt-document-management.php file with tab functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include WordPress
require_once('wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

// Plugin path
$plugin_path = WP_PLUGIN_DIR . '/bkgt-document-management/bkgt-document-management.php';

// Enhanced plugin code with tabs
$enhanced_code = <<<'EOT'
<?php
/**
 * Plugin Name: BKGT Document Management
 * Plugin URI: https://bkgt.se
 * Description: Secure document management system for BKGTS with tabbed interface.
 * Version: 1.1.0
 * Author: BKGT Development Team
 * License: GPL v2 or later
 * Text Domain: bkgt-document-management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('BKGT_DM_VERSION', '1.1.0');
define('BKGT_DM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKGT_DM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check WordPress version compatibility
if (version_compare(get_bloginfo('version'), '5.0', '<')) {
    return;
}

/**
 * Main Plugin Class
 */
class BKGT_Document_Management {

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
    private function __construct() {
        // Initialize plugin
        $this->init();
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load textdomain
        add_action('init', array($this, 'load_textdomain'));

        // Register post types and taxonomies
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));

        // Add shortcodes
        add_shortcode('bkgt_documents', array($this, 'documents_shortcode'));

        // AJAX handlers
        add_action('wp_ajax_bkgt_load_documents', array($this, 'ajax_load_documents'));
        add_action('wp_ajax_nopriv_bkgt_load_documents', array($this, 'ajax_load_documents'));

        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('bkgt-document-management', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Register custom post types
     */
    public function register_post_types() {
        register_post_type('bkgt_document', array(
            'labels' => array(
                'name' => __('Documents', 'bkgt-document-management'),
                'singular_name' => __('Document', 'bkgt-document-management'),
                'add_new' => __('Add New', 'bkgt-document-management'),
                'add_new_item' => __('Add New Document', 'bkgt-document-management'),
                'edit_item' => __('Edit Document', 'bkgt-document-management'),
                'new_item' => __('New Document', 'bkgt-document-management'),
                'view_item' => __('View Document', 'bkgt-document-management'),
                'search_items' => __('Search Documents', 'bkgt-document-management'),
                'not_found' => __('No documents found', 'bkgt-document-management'),
                'not_found_in_trash' => __('No documents found in trash', 'bkgt-document-management'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'author'),
            'show_in_rest' => false,
        ));
    }

    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        register_taxonomy('bkgt_doc_category', 'bkgt_document', array(
            'labels' => array(
                'name' => __('Categories', 'bkgt-document-management'),
                'singular_name' => __('Category', 'bkgt-document-management'),
            ),
            'hierarchical' => true,
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => false,
        ));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Documents', 'bkgt-document-management'),
            __('Documents', 'bkgt-document-management'),
            'manage_options',
            'bkgt-documents',
            array($this, 'admin_page'),
            'dashicons-media-document',
            30
        );
    }

    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('BKGT Document Management', 'bkgt-document-management'); ?></h1>
            <p><?php _e('Document management system with tabbed interface is active.', 'bkgt-document-management'); ?></p>
            <div class="notice notice-info">
                <p><?php _e('Features:', 'bkgt-document-management'); ?></p>
                <ul>
                    <li><?php _e('Tabbed interface for document categories', 'bkgt-document-management'); ?></li>
                    <li><?php _e('AJAX-powered content loading', 'bkgt-document-management'); ?></li>
                    <li><?php _e('Responsive design for mobile devices', 'bkgt-document-management'); ?></li>
                    <li><?php _e('Secure access control and permissions', 'bkgt-document-management'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Documents shortcode
     */
    public function documents_shortcode($atts) {
        // Handle category filtering from URL
        if (isset($_GET['bkgt_category']) && !empty($_GET['bkgt_category'])) {
            $atts['category'] = sanitize_text_field($_GET['bkgt_category']);
        }

        ob_start();

        // Get all categories for tabs
        $categories = get_terms(array(
            'taxonomy' => 'bkgt_doc_category',
            'hide_empty' => false,
        ));

        // Set default attributes
        $atts = shortcode_atts(array(
            'limit' => 10,
            'category' => '',
            'show_categories' => 'true'
        ), $atts);

        ?>
        <div class="bkgt-documents">
            <h2><?php _e('Document Management', 'bkgt-document-management'); ?></h2>

            <?php if ($atts['show_categories'] === 'true' && !empty($categories) && !is_wp_error($categories)) : ?>
                <?php $this->display_category_tabs($categories, $atts['category']); ?>
            <?php endif; ?>

            <div class="bkgt-documents-content">
                <?php $this->display_documents_list($atts); ?>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Tab switching functionality
            $('.bkgt-tab-link').on('click', function(e) {
                e.preventDefault();

                // Remove active class from all tabs
                $('.bkgt-tab-link').removeClass('active');
                // Add active class to clicked tab
                $(this).addClass('active');

                // Get category slug
                var category = $(this).data('category');

                // Update URL without page reload
                var url = new URL(window.location);
                if (category) {
                    url.searchParams.set('bkgt_category', category);
                } else {
                    url.searchParams.delete('bkgt_category');
                }
                window.history.pushState({}, '', url);

                // Load documents for this category
                loadDocuments(category);
            });

            function loadDocuments(category) {
                $('.bkgt-documents-content').addClass('loading');

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'bkgt_load_documents',
                        category: category,
                        limit: <?php echo intval($atts['limit']); ?>
                    },
                    success: function(response) {
                        $('.bkgt-documents-content').removeClass('loading').html(response);
                    },
                    error: function() {
                        $('.bkgt-documents-content').removeClass('loading').html('<p><?php _e('Error loading documents.', 'bkgt-document-management'); ?></p>');
                    }
                });
            }

            // Handle browser back/forward buttons
            $(window).on('popstate', function() {
                var url = new URL(window.location);
                var category = url.searchParams.get('bkgt_category');

                // Update active tab
                $('.bkgt-tab-link').removeClass('active');
                if (category) {
                    $('.bkgt-tab-link[data-category="' + category + '"]').addClass('active');
                } else {
                    $('.bkgt-tab-link[data-category=""]').addClass('active');
                }

                loadDocuments(category);
            });
        });
        </script>

        <style>
            .bkgt-documents { margin: 20px 0; }
            .bkgt-documents h2 { color: #333; border-bottom: 2px solid #007cba; padding-bottom: 10px; }

            /* Tab styling */
            .bkgt-tabs { margin: 20px 0; }
            .bkgt-tab-navigation { display: flex; border-bottom: 1px solid #ddd; flex-wrap: wrap; }
            .bkgt-tab-link {
                padding: 12px 20px;
                text-decoration: none;
                color: #666;
                border-bottom: 3px solid transparent;
                transition: all 0.3s ease;
                font-weight: 500;
                white-space: nowrap;
            }
            .bkgt-tab-link:hover {
                color: #007cba;
                background-color: #f8f9fa;
            }
            .bkgt-tab-link.active {
                color: #007cba;
                border-bottom-color: #007cba;
                background-color: #fff;
            }

            /* Documents content */
            .bkgt-documents-content { position: relative; min-height: 200px; }
            .bkgt-documents-content.loading {
                opacity: 0.6;
                pointer-events: none;
            }
            .bkgt-documents-content.loading::after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 20px;
                height: 20px;
                margin: -10px 0 0 -10px;
                border: 2px solid #007cba;
                border-top: 2px solid transparent;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .bkgt-category-filter { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
            .bkgt-category-filter select { padding: 5px 10px; margin-left: 10px; }
            .bkgt-documents-list { margin-top: 20px; }
            .bkgt-document-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; background: #fff; }
            .bkgt-document-item h3 { margin-top: 0; color: #007cba; }
            .bkgt-document-item h3 a { text-decoration: none; color: inherit; }
            .bkgt-document-item h3 a:hover { color: #005a87; }
            .bkgt-document-meta { font-size: 0.9em; color: #666; margin: 10px 0; }
            .bkgt-document-meta span { display: inline-block; margin-right: 15px; }
            .bkgt-category-tag {
                display: inline-block;
                background: #007cba;
                color: white;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 0.8em;
                margin-right: 5px;
            }
            .bkgt-document-excerpt { margin-top: 10px; }
            .bkgt-no-documents { text-align: center; padding: 40px; background: #f8f9fa; border-radius: 5px; }

            /* Responsive design */
            @media (max-width: 768px) {
                .bkgt-tab-navigation { flex-direction: column; }
                .bkgt-tab-link { border-bottom: 1px solid #ddd; border-left: 3px solid transparent; }
                .bkgt-tab-link.active { border-left-color: #007cba; border-bottom-color: transparent; }
            }
        </style>
        <?php

        return ob_get_clean();
    }

    /**
     * Display category tabs
     */
    private function display_category_tabs($categories, $active_category = '') {
        ?>
        <div class="bkgt-tabs">
            <div class="bkgt-tab-navigation">
                <a href="#" class="bkgt-tab-link <?php echo empty($active_category) ? 'active' : ''; ?>" data-category="">
                    <?php _e('All Documents', 'bkgt-document-management'); ?>
                </a>
                <?php foreach ($categories as $category) : ?>
                    <a href="#" class="bkgt-tab-link <?php echo ($active_category === $category->slug) ? 'active' : ''; ?>" data-category="<?php echo esc_attr($category->slug); ?>">
                        <?php echo esc_html($category->name); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display documents list
     */
    private function display_documents_list($atts) {
        // Get documents
        $args = array(
            'post_type' => 'bkgt_document',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
        );

        // Filter by category if specified
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'bkgt_doc_category',
                    'field' => 'slug',
                    'terms' => $atts['category'],
                ),
            );
        }

        $documents = new WP_Query($args);

        if ($documents->have_posts()) : ?>
            <div class="bkgt-documents-list">
                <?php while ($documents->have_posts()) : $documents->the_post(); ?>
                    <div class="bkgt-document-item">
                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        <div class="bkgt-document-meta">
                            <span class="bkgt-document-author">
                                <?php _e('Author:', 'bkgt-document-management'); ?> <?php the_author(); ?>
                            </span>
                            <span class="bkgt-document-date">
                                <?php _e('Date:', 'bkgt-document-management'); ?> <?php the_date(); ?>
                            </span>
                            <?php
                            $categories = get_the_terms(get_the_ID(), 'bkgt_doc_category');
                            if ($categories && !is_wp_error($categories)) :
                            ?>
                                <span class="bkgt-document-categories">
                                    <?php _e('Categories:', 'bkgt-document-management'); ?>
                                    <?php foreach ($categories as $category) : ?>
                                        <span class="bkgt-category-tag"><?php echo esc_html($category->name); ?></span>
                                    <?php endforeach; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="bkgt-document-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <?php
            // Pagination
            $big = 999999999;
            echo paginate_links(array(
                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format' => '?paged=%#%',
                'current' => max(1, get_query_var('paged')),
                'total' => $documents->max_num_pages,
            ));
            ?>

        <?php else : ?>
            <div class="bkgt-no-documents">
                <p><?php _e('No documents found.', 'bkgt-document-management'); ?></p>
                <p><?php _e('Documents can be added through the admin panel.', 'bkgt-document-management'); ?></p>
            </div>
        <?php endif;

        wp_reset_postdata();
    }

    /**
     * AJAX handler for loading documents
     */
    public function ajax_load_documents() {
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;

        $atts = array(
            'category' => $category,
            'limit' => $limit,
            'show_categories' => 'false' // Don't show tabs in AJAX response
        );

        $this->display_documents_list($atts);
        wp_die();
    }
}

/**
 * Initialize the plugin
 */
function bkgt_document_management_init() {
    BKGT_Document_Management::get_instance();
}
add_action('plugins_loaded', 'bkgt_document_management_init');
EOT;

// Write the enhanced code to the plugin file
if (file_put_contents($plugin_path, $enhanced_code) !== false) {
    echo '<div class="notice notice-success"><p>✅ Document Management Plugin updated successfully with tab functionality!</p></div>';
    echo '<p>The plugin now includes:</p>';
    echo '<ul>';
    echo '<li>Tabbed interface for document categories</li>';
    echo '<li>AJAX-powered content loading</li>';
    echo '<li>Responsive design for mobile devices</li>';
    echo '<li>Loading animations and smooth transitions</li>';
    echo '</ul>';
    echo '<p><strong>Next steps:</strong></p>';
    echo '<ol>';
    echo '<li>Visit your document page to see the new tabs</li>';
    echo '<li>Create document categories in the admin panel if needed</li>';
    echo '<li>Add some documents to test the functionality</li>';
    echo '</ol>';
} else {
    echo '<div class="notice notice-error"><p>❌ Failed to update the plugin file. Please check file permissions.</p></div>';
}
?>