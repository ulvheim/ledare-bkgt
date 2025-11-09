<?php
/**
 * Plugin Name: BKGT Inventory System
 * Plugin URI: https://ledare.bkgt.se
 * Description: Utrustningssystem för BKGTS Ledarsystem. Hanterar utrustning, tilldelningar och lagerhållning.
 * Version: 1.0.0
 * Author: BKGT Amerikansk Fotboll
 * Author URI: https://bkgt.se
 * Text Domain: bkgt-inventory
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Requires Plugins: bkgt-core
 * License: Proprietary
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BKGT_INV_VERSION', '1.0.0');
define('BKGT_INV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKGT_INV_PLUGIN_URL', plugin_dir_url(__FILE__));

// Initialize plugin instance
function bkgt_inventory() {
    static $instance = null;
    if ($instance === null) {
        $instance = new stdClass();
        global $bkgt_inventory_db;
        $instance->db = $bkgt_inventory_db;
    }
    return $instance;
}

// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-analytics.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-history.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-location.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-manufacturer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-item-type.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-inventory-item.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-assignment.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-api-endpoints.php';

// Include admin files if in admin area
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'admin/class-admin.php';
    require_once plugin_dir_path(__FILE__) . 'admin/class-item-admin.php';
}

// Initialize database class
global $bkgt_inventory_db;
$bkgt_inventory_db = new BKGT_Inventory_Database();

// Initialize API endpoints
        new BKGT_Inventory_API_Endpoints();// Initialize admin class if in admin area or AJAX request
if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
    new BKGT_Inventory_Admin();
}

// Plugin activation
register_activation_hook(__FILE__, 'bkgt_inventory_activate');
function bkgt_inventory_activate() {
    // Check if BKGT Core plugin is active
    if (!function_exists('bkgt_log')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('BKGT Core plugin is required for BKGT Inventory to work. Please activate BKGT Core first.');
    }
    
    // Start output buffering to prevent any unexpected output
    ob_start();
    
    add_option('bkgt_inventory_test', 'activated');
    
    // Create database tables
    global $bkgt_inventory_db;
    $bkgt_inventory_db->create_tables();
    
    // Create history table
    BKGT_History::create_history_table();
    
    // Create sample data
    bkgt_inventory_create_sample_data();
    
    // Add capabilities to administrator role
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('manage_inventory');
    }
    
    // Flush rewrite rules for custom post type
    flush_rewrite_rules();
    
    // Log activation
    bkgt_log('info', 'BKGT Inventory plugin activated', array(
        'version' => BKGT_INV_VERSION,
    ));
    
    // Clean output buffer to prevent headers already sent errors
    ob_end_clean();
}

// Plugin deactivation
register_deactivation_hook(__FILE__, 'bkgt_inventory_deactivate');
function bkgt_inventory_deactivate() {
    // Log deactivation if BKGT Core is available
    if (function_exists('bkgt_log')) {
        bkgt_log('info', 'BKGT Inventory plugin deactivated');
    }
    
    delete_option('bkgt_inventory_test');
}

// Register shortcodes
add_action('init', 'bkgt_inventory_register_shortcodes');
function bkgt_inventory_register_shortcodes() {
    add_shortcode('bkgt_inventory', 'bkgt_inventory_shortcode');
}

// Register custom post type
add_action('init', 'bkgt_inventory_register_post_type');
function bkgt_inventory_register_post_type() {
    register_post_type('bkgt_inventory_item', array(
        'labels' => array(
            'name' => __('Utrustning', 'bkgt-inventory'),
            'singular_name' => __('Artikel', 'bkgt-inventory'),
            'add_new' => __('Lägg till artikel', 'bkgt-inventory'),
            'add_new_item' => __('Lägg till ny artikel', 'bkgt-inventory'),
            'edit_item' => __('Redigera artikel', 'bkgt-inventory'),
            'new_item' => __('Ny artikel', 'bkgt-inventory'),
            'view_item' => __('Visa artikel', 'bkgt-inventory'),
            'search_items' => __('Sök artiklar', 'bkgt-inventory'),
            'not_found' => __('Inga artiklar hittades', 'bkgt-inventory'),
            'not_found_in_trash' => __('Inga artiklar i papperskorgen', 'bkgt-inventory'),
        ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => false, // Will be shown under our custom menu
        'capability_type' => 'post',
        'capabilities' => array(
            'edit_post' => 'manage_inventory',
            'read_post' => 'manage_inventory',
            'delete_post' => 'manage_inventory',
            'edit_posts' => 'manage_inventory',
            'edit_others_posts' => 'manage_inventory',
            'publish_posts' => 'manage_inventory',
            'read_private_posts' => 'manage_inventory',
        ),
        'supports' => array('custom-fields'),
        'has_archive' => false,
        'rewrite' => array('slug' => 'utrustning/artikel'),
    ));
}

// Remove default title and content fields from inventory item edit screen
add_action('admin_head', 'bkgt_inventory_remove_default_fields');
function bkgt_inventory_remove_default_fields() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'bkgt_inventory_item') {
        // Remove title field
        remove_post_type_support('bkgt_inventory_item', 'title');
        // Remove content editor
        remove_post_type_support('bkgt_inventory_item', 'editor');
    }
}

// Remove unwanted metaboxes from inventory item edit screen
add_action('add_meta_boxes', 'bkgt_inventory_remove_metaboxes', 99);
function bkgt_inventory_remove_metaboxes() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'bkgt_inventory_item') {
        // Remove publish metabox
        remove_meta_box('submitdiv', 'bkgt_inventory_item', 'side');
        // Remove slug metabox
        remove_meta_box('slugdiv', 'bkgt_inventory_item', 'normal');
        // Remove author metabox
        remove_meta_box('authordiv', 'bkgt_inventory_item', 'normal');
        // Remove comments metabox
        remove_meta_box('commentsdiv', 'bkgt_inventory_item', 'normal');
        // Remove revisions metabox
        remove_meta_box('revisionsdiv', 'bkgt_inventory_item', 'normal');
        // Remove custom fields metabox (we have our own)
        remove_meta_box('postcustom', 'bkgt_inventory_item', 'normal');
        // Remove excerpt metabox
        remove_meta_box('postexcerpt', 'bkgt_inventory_item', 'normal');
        // Remove trackbacks metabox
        remove_meta_box('trackbacksdiv', 'bkgt_inventory_item', 'normal');
        // Remove tags metabox
        remove_meta_box('tagsdiv-bkgt_condition', 'bkgt_inventory_item', 'side');
    }
}

// Register custom taxonomies
add_action('init', 'bkgt_inventory_register_taxonomies');
function bkgt_inventory_register_taxonomies() {
    // Condition taxonomy
    register_taxonomy('bkgt_condition', 'bkgt_inventory_item', array(
        'labels' => array(
            'name' => __('Skick', 'bkgt-inventory'),
            'singular_name' => __('Skick', 'bkgt-inventory'),
            'search_items' => __('Sök skick', 'bkgt-inventory'),
            'all_items' => __('Alla skick', 'bkgt-inventory'),
            'parent_item' => __('Förälderskick', 'bkgt-inventory'),
            'parent_item_colon' => __('Förälderskick:', 'bkgt-inventory'),
            'edit_item' => __('Redigera skick', 'bkgt-inventory'),
            'update_item' => __('Uppdatera skick', 'bkgt-inventory'),
            'add_new_item' => __('Lägg till nytt skick', 'bkgt-inventory'),
            'new_item_name' => __('Nytt skick namn', 'bkgt-inventory'),
            'menu_name' => __('Skick', 'bkgt-inventory'),
        ),
        'hierarchical' => false,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'condition'),
        'capabilities' => array(
            'manage_terms' => 'manage_inventory',
            'edit_terms' => 'manage_inventory',
            'delete_terms' => 'manage_inventory',
            'assign_terms' => 'manage_inventory',
        ),
    ));
}

/**
 * Inventory shortcode
 */
function bkgt_inventory_shortcode($atts) {
    global $wpdb;

    $atts = shortcode_atts(array(
        'limit' => -1,
        'show_filters' => 'true',
        'layout' => 'table'
    ), $atts);

    // Query real inventory data from custom post types
    $args = array(
        'post_type' => 'bkgt_inventory_item',
        'post_status' => 'publish',
        'numberposts' => $atts['limit'] > 0 ? intval($atts['limit']) : -1,
        'orderby' => 'date',
        'order' => 'DESC'
    );

    $inventory_posts = get_posts($args);
    $inventory_items = array();

    // Convert posts to objects with metadata for consistent processing
    foreach ($inventory_posts as $post) {
        $manufacturer_id = get_post_meta($post->ID, '_bkgt_manufacturer_id', true);
        $item_type_id = get_post_meta($post->ID, '_bkgt_item_type_id', true);
        $assignment_type = get_post_meta($post->ID, '_bkgt_assignment_type', true);
        $assigned_to = get_post_meta($post->ID, '_bkgt_assigned_to', true);

        // Get manufacturer and item type names
        $manufacturer_name = '';
        $item_type_name = '';

        // Map IDs to names (this is a simplified mapping - you may need to expand this)
        $manufacturer_map = array(
            1 => 'BKGT',
            2 => 'Riddell',
            3 => 'Schutt',
            4 => 'Xenith',
            5 => 'Wilson'
        );

        $item_type_map = array(
            1 => 'Hjälmar',
            2 => 'Axelskydd',
            3 => 'Spelarbyxor',
            4 => 'Träningströjor',
            5 => 'Fotbollar'
        );

        // Singular forms for individual items
        $item_type_singular_map = array(
            1 => 'Hjälm',
            2 => 'Axelskydd',
            3 => 'Spelarbyxa',
            4 => 'Träningströja',
            5 => 'Fotboll'
        );

        $manufacturer_name = isset($manufacturer_map[$manufacturer_id]) ? $manufacturer_map[$manufacturer_id] : '';
        $item_type_name = isset($item_type_map[$item_type_id]) ? $item_type_map[$item_type_id] : '';
        $item_type_name_singular = isset($item_type_singular_map[$item_type_id]) ? $item_type_singular_map[$item_type_id] : $item_type_name;

        // Determine location name based on assignment
        $location_name = '';
        if ($assignment_type === 'location' && !empty($assigned_to)) {
            // Get the actual location name from the database
            $location = BKGT_Location::get_location($assigned_to);
            $location_name = $location ? $location['name'] : 'Okänd plats';
        } elseif (empty($assignment_type) || empty($assigned_to)) {
            // Default assignment for items without assignment
            $assignment_type = 'location';
            $location_name = 'Lager';
        }

        // Get additional metadata for display
        $size = get_post_meta($post->ID, '_bkgt_size', true);
        $material = get_post_meta($post->ID, '_bkgt_material', true);
        $serial_number = get_post_meta($post->ID, '_bkgt_serial_number', true);

        // Create a meaningful title
        $display_title = $manufacturer_name;
        if (!empty($item_type_name_singular)) {
            $display_title .= ' ' . $item_type_name_singular;
        }
        if (!empty($size)) {
            $display_title .= ' - ' . $size;
        }

        // Fallback to post title if we don't have manufacturer/type
        if (empty($display_title)) {
            $display_title = $post->post_title;
        }

        $inventory_items[] = (object) array(
            'unique_identifier' => get_post_meta($post->ID, '_bkgt_unique_id', true),
            'title' => $display_title, // Changed from $post->post_title to meaningful title
            'manufacturer_name' => $manufacturer_name,
            'item_type_name' => $item_type_name,
            'item_type_name_singular' => $item_type_name_singular,
            'storage_location' => '', // Could be derived from assignment
            'condition_status' => 'normal', // Default, could be stored in meta
            'assignment_type' => $assignment_type,
            'location_name' => $location_name,
            'size' => $size,
            'material' => $material,
            'serial_number' => $serial_number
        );
    }

    // Handle empty inventory state
    if (empty($inventory_items)) {
        bkgt_log('info', 'Inventory shortcode: no items in database', array('user_id' => get_current_user_id()));
        return bkgt_render_empty_state(
            array(
                'icon' => '📦',
                'title' => __('Ingen utrustning registrerad', 'bkgt-inventory'),
                'message' => __('Det finns ingen utrustning registrerad i systemet ännu.', 'bkgt-inventory'),
                'actions' => current_user_can('manage_inventory') ? array(
                    array(
                        'label' => __('Lägg till utrustning', 'bkgt-inventory'),
                        'url' => admin_url('post-new.php?post_type=bkgt_inventory_item'),
                        'primary' => true
                    ),
                    array(
                        'label' => __('Till administrationspanelen', 'bkgt-inventory'),
                        'url' => admin_url('admin.php?page=bkgt-inventory'),
                        'primary' => false
                    )
                ) : array()
            )
        );
    }
    
    ob_start();
    ?>
    <div class="bkgt-inventory">
        <?php if ($atts['show_filters'] === 'true'): ?>
        <div class="bkgt-filters">
            <input type="text" id="bkgt-inventory-search" placeholder="Sök utrustning..." class="bkgt-search-input">
        </div>
        <?php endif; ?>
        
        <div class="bkgt-inventory-grid">
            <?php foreach ($inventory_items as $item): 
                // Determine icon based on item type
                $icon_class = 'dashicons-admin-tools'; // default
                switch (strtolower($item->item_type_name ?? '')) {
                    case 'hjälm':
                        $icon_class = 'dashicons-shield';
                        break;
                    case 'tröja':
                        $icon_class = 'dashicons-shirt';
                        break;
                    case 'byxor':
                        $icon_class = 'dashicons-groups';
                        break;
                    case 'skor':
                        $icon_class = 'dashicons-shoe';
                        break;
                }
                
                // Determine status color
                $status_class = '';
                switch ($item->condition_status ?? 'normal') {
                    case 'normal':
                        $status_class = 'status-normal';
                        break;
                    case 'needs_repair':
                        $status_class = 'status-warning';
                        break;
                    case 'repaired':
                        $status_class = 'status-good';
                        break;
                    case 'reported_lost':
                    case 'scrapped':
                        $status_class = 'status-error';
                        break;
                }
                // Create searchable text including all metadata
                $searchable_text = strtolower(($item->title ?? '') . ' ' . 
                                           ($item->unique_identifier ?? '') . ' ' . 
                                           ($item->manufacturer_name ?? '') . ' ' . 
                                           ($item->item_type_name ?? '') . ' ' . 
                                           ($item->item_type_name_singular ?? '') . ' ' . 
                                           ($item->size ?? '') . ' ' . 
                                           ($item->material ?? '') . ' ' . 
                                           ($item->serial_number ?? ''));
            ?>
            <div class="bkgt-inventory-item" data-title="<?php echo esc_attr($searchable_text); ?>">
                <div class="inventory-item-header">
                    <div class="inventory-icon">
                        <span class="dashicons <?php echo esc_attr($icon_class); ?>"></span>
                    </div>
                    <div class="inventory-content">
                        <h4><?php echo esc_html($item->title ?? ''); ?></h4>
                        <div class="inventory-meta">
                            <span class="meta-item"><strong>ID:</strong> <?php echo esc_html($item->unique_identifier ?? ''); ?></span>
                            <span class="meta-item"><strong>Tillverkare:</strong> <?php echo esc_html($item->manufacturer_name ?? ''); ?></span>
                            <span class="meta-item"><strong>Typ:</strong> <?php echo esc_html($item->item_type_name ?? ''); ?></span>
                            <?php if (!empty($item->size)): ?>
                            <span class="meta-item"><strong>Storlek:</strong> <?php echo esc_html($item->size); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item->material)): ?>
                            <span class="meta-item"><strong>Material:</strong> <?php echo esc_html($item->material); ?></span>
                            <?php endif; ?>
                            <span class="meta-item"><strong>Plats:</strong> <?php 
                                if (!empty($item->assignment_type) && $item->assignment_type === 'location' && !empty($item->location_name)) {
                                    echo esc_html($item->location_name);
                                } elseif (!empty($item->assignment_type) && $item->assignment_type === 'team') {
                                    echo 'Lag';
                                } elseif (!empty($item->assignment_type) && $item->assignment_type === 'individual') {
                                    echo 'Individ';
                                } elseif (!empty($item->assignment_type) && $item->assignment_type === 'club') {
                                    echo 'Klubben';
                                } else {
                                    echo esc_html($item->storage_location ?? 'Ej tilldelad');
                                }
                            ?></span>
                        </div>
                    </div>
                    <div class="inventory-status">
                        <span class="status-text <?php echo esc_attr($status_class); ?>">
                            <?php echo esc_html(ucfirst($item->condition_status ?? 'normal')); ?>
                        </span>
                    </div>
                </div>
                <div class="inventory-item-actions">
                    <button class="btn btn-sm btn-outline inventory-action-btn bkgt-show-details" 
                            data-action="view"
                            data-item-title="<?php echo esc_attr($item->title ?? ''); ?>"
                            data-unique-id="<?php echo esc_attr($item->unique_identifier ?? ''); ?>"
                            data-manufacturer="<?php echo esc_attr($item->manufacturer_name ?? ''); ?>"
                            data-item-type="<?php echo esc_attr($item->item_type_name ?? ''); ?>"
                            data-size="<?php echo esc_attr($item->size ?? ''); ?>"
                            data-material="<?php echo esc_attr($item->material ?? ''); ?>"
                            data-serial-number="<?php echo esc_attr($item->serial_number ?? ''); ?>"
                            data-status="<?php echo esc_attr($item->condition_status ?? 'normal'); ?>"
                            data-assignment="<?php 
                                if (!empty($item->assignment_type) && $item->assignment_type === 'location' && !empty($item->location_name)) {
                                    echo esc_attr($item->location_name);
                                } elseif (!empty($item->assignment_type) && $item->assignment_type === 'team') {
                                    echo 'Lag';
                                } elseif (!empty($item->assignment_type) && $item->assignment_type === 'individual') {
                                    echo 'Individ';
                                } elseif (!empty($item->assignment_type) && $item->assignment_type === 'club') {
                                    echo 'Klubben';
                                } else {
                                    echo esc_attr($item->storage_location ?? 'Ej tilldelad');
                                }
                            ?>">
                        <?php _e('Visa detaljer', 'bkgt-inventory'); ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <style>
    .bkgt-inventory-grid {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-top: 20px;
    }
    
    .bkgt-inventory-item {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .bkgt-inventory-item:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        border-color: #007cba;
    }
    
    .inventory-item-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .inventory-icon {
        font-size: 24px;
        color: #007cba;
        flex-shrink: 0;
    }
    
    .inventory-content {
        flex: 1;
    }
    
    .inventory-content h4 {
        margin: 0 0 8px 0;
        color: #007cba;
        font-size: 16px;
        font-weight: 600;
    }
    
    .inventory-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        font-size: 14px;
        color: #666;
    }
    
    .meta-item strong {
        color: #333;
        font-weight: 600;
        margin-right: 4px;
    }
    
    .inventory-status {
        flex-shrink: 0;
    }
    
    .status-text {
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        text-transform: uppercase;
        display: inline-block;
    }
    
    .status-text.status-normal {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-text.status-warning {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-text.status-good {
        background-color: #d1ecf1;
        color: #0c5460;
    }
    
    .status-text.status-error {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .inventory-item-actions {
        text-align: right;
    }
    
    .inventory-action-btn {
        padding: 6px 12px;
        font-size: 13px;
        font-weight: 500;
        border-radius: 4px;
        transition: all 0.2s ease;
    }
    
    .inventory-action-btn:hover {
        background-color: #007cba;
        color: white;
        border-color: #007cba;
    }
        border-color: #007cba;
    }
    
    .bkgt-filters {
        margin-bottom: 20px;
        text-align: center;
    }
    
    .bkgt-search-input {
        padding: 10px 16px;
        border: 2px solid #ddd;
        border-radius: 25px;
        width: 100%;
        max-width: 400px;
        font-size: 16px;
        transition: border-color 0.3s ease;
    }
    
    .bkgt-search-input:focus {
        outline: none;
        border-color: #007cba;
        box-shadow: 0 0 0 3px rgba(0,123,186,0.1);
    }
    
    .btn {
        display: inline-block;
        padding: 8px 16px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: #f8f9fa;
        color: #333;
        text-decoration: none;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s ease;
    }
    
    .btn-outline {
        background: transparent;
        color: #007cba;
        border-color: #007cba;
    }
    
    .btn-outline:hover {
        background: #007cba;
        color: white;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .bkgt-inventory-item {
            padding: 12px;
        }
        
        .inventory-item-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .inventory-meta {
            flex-direction: column;
            gap: 8px;
        }
        
        .inventory-item-actions {
            text-align: center;
        }
        
        .inventory-action-btn {
            width: 100%;
        }
    }
    
    /* Fallback Notice Styles */
    .bkgt-inventory-fallback-notice {
        margin-bottom: 20px;
    }
    
    .bkgt-inventory-fallback-notice .notice {
        border-left: 4px solid;
        padding: 12px 15px;
        border-radius: 4px;
        margin: 0;
    }
    
    .bkgt-inventory-fallback-notice .notice-info {
        background-color: #d1ecf1;
        border-left-color: #0c5460;
        color: #0c5460;
    }
    
    .bkgt-inventory-fallback-notice .notice-warning {
        background-color: #fff3cd;
        border-left-color: #856404;
        color: #856404;
    }
    
    .bkgt-inventory-fallback-notice .notice p {
        margin: 8px 0;
        font-size: 14px;
    }
    
    .bkgt-inventory-fallback-notice .notice strong {
        font-weight: 600;
        display: block;
        margin-bottom: 4px;
        font-size: 15px;
    }
    
    .bkgt-inventory-fallback-notice .notice .button {
        display: inline-block;
        margin-right: 10px;
        margin-top: 8px;
        padding: 8px 16px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 500;
        font-size: 13px;
        transition: all 0.2s ease;
    }
    
    .bkgt-inventory-fallback-notice .notice .button-primary {
        background-color: #0073aa;
        color: white;
        border: 1px solid #0073aa;
    }
    
    .bkgt-inventory-fallback-notice .notice .button-primary:hover {
        background-color: #005a87;
        border-color: #005a87;
    }
    
    .bkgt-inventory-fallback-notice .notice .button {
        background-color: #f8f9fa;
        color: #0073aa;
        border: 1px solid #ddd;
    }
    
    .bkgt-inventory-fallback-notice .notice .button:hover {
        background-color: #e9ecef;
        border-color: #0073aa;
        color: #0073aa;
    }
    
    /* Modal Styles handled by BKGTModal component from bkgt-core */
    </style>
    
    <!-- Item Details Modal is handled by BKGTModal component -->

    <script>
    /**
     * BKGT Inventory Modal System
     * Uses unified BKGTModal component from bkgt-core
     */
    var bkgtInventoryModal = null;
    
    function initBkgtInventoryModal() {
        // Create modal instance if BKGTModal is available
        if (typeof BKGTModal === 'undefined') {
            bkgt_log('error', 'BKGTModal not loaded');
            return;
        }
        
        // Create detail modal
        bkgtInventoryModal = new BKGTModal({
            id: 'bkgt-inventory-details-modal',
            title: '<?php esc_html_e('Artikeldetaljer', 'bkgt-inventory'); ?>',
            size: 'medium',
            closeButton: true,
            overlay: true,
            onClose: function() {
                bkgt_log('info', 'Inventory modal closed');
            }
        });
        
        // Attach click handlers to all detail buttons
        var detailButtons = document.querySelectorAll('.bkgt-show-details');
        
        detailButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Gather item data from button attributes
                var itemData = {
                    title: this.getAttribute('data-item-title') || '',
                    unique_id: this.getAttribute('data-unique-id') || '<?php esc_html_e('Ej angivet', 'bkgt-inventory'); ?>',
                    manufacturer: this.getAttribute('data-manufacturer') || '<?php esc_html_e('Ej angivet', 'bkgt-inventory'); ?>',
                    item_type: this.getAttribute('data-item-type') || '<?php esc_html_e('Ej angivet', 'bkgt-inventory'); ?>',
                    size: this.getAttribute('data-size') || '<?php esc_html_e('Ej angivet', 'bkgt-inventory'); ?>',
                    material: this.getAttribute('data-material') || '<?php esc_html_e('Ej angivet', 'bkgt-inventory'); ?>',
                    serial_number: this.getAttribute('data-serial-number') || '<?php esc_html_e('Ej angivet', 'bkgt-inventory'); ?>',
                    status: this.getAttribute('data-status') || '<?php esc_html_e('Normal', 'bkgt-inventory'); ?>',
                    assignment: this.getAttribute('data-assignment') || '<?php esc_html_e('Ej tilldelad', 'bkgt-inventory'); ?>'
                };
                
                // Build modal content HTML
                var content = '<div class="bkgt-modal-details">' +
                    '<div class="bkgt-detail-row">' +
                        '<label><?php esc_html_e('ID', 'bkgt-inventory'); ?>:</label>' +
                        '<span>' + escapeHtml(itemData.unique_id) + '</span>' +
                    '</div>' +
                    '<div class="bkgt-detail-row">' +
                        '<label><?php esc_html_e('Tillverkare', 'bkgt-inventory'); ?>:</label>' +
                        '<span>' + escapeHtml(itemData.manufacturer) + '</span>' +
                    '</div>' +
                    '<div class="bkgt-detail-row">' +
                        '<label><?php esc_html_e('Typ', 'bkgt-inventory'); ?>:</label>' +
                        '<span>' + escapeHtml(itemData.item_type) + '</span>' +
                    '</div>' +
                    '<div class="bkgt-detail-row">' +
                        '<label><?php esc_html_e('Storlek', 'bkgt-inventory'); ?>:</label>' +
                        '<span>' + escapeHtml(itemData.size) + '</span>' +
                    '</div>' +
                    '<div class="bkgt-detail-row">' +
                        '<label><?php esc_html_e('Material', 'bkgt-inventory'); ?>:</label>' +
                        '<span>' + escapeHtml(itemData.material) + '</span>' +
                    '</div>' +
                    '<div class="bkgt-detail-row">' +
                        '<label><?php esc_html_e('Serienummer', 'bkgt-inventory'); ?>:</label>' +
                        '<span>' + escapeHtml(itemData.serial_number) + '</span>' +
                    '</div>' +
                    '<div class="bkgt-detail-row">' +
                        '<label><?php esc_html_e('Tilldelning', 'bkgt-inventory'); ?>:</label>' +
                        '<span>' + escapeHtml(itemData.assignment) + '</span>' +
                    '</div>' +
                    '<div class="bkgt-detail-row">' +
                        '<label><?php esc_html_e('Status', 'bkgt-inventory'); ?>:</label>' +
                        '<span>' + escapeHtml(itemData.status) + '</span>' +
                    '</div>' +
                '</div>';
                
                // Update modal title and content
                bkgtInventoryModal.options.title = itemData.title;
                bkgtInventoryModal.setContent(content);
                
                // Set footer with close button
                bkgtInventoryModal.setFooter(
                    '<button class="btn btn-sm btn-secondary" onclick="bkgtInventoryModal.close();">' +
                        '<?php esc_html_e('Stäng', 'bkgt-inventory'); ?>' +
                    '</button>'
                );
                
                // Open the modal
                bkgtInventoryModal.open();
                
                bkgt_log('info', 'Inventory detail modal opened for: ' + itemData.title);
            });
        });
        
        bkgt_log('info', 'Inventory modal system initialized with ' + detailButtons.length + ' buttons');
    }
    
    /**
     * Escape HTML special characters to prevent XSS
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Initialize when BKGTModal is ready - with robust timing handling
    (function() {
        var initialized = false;
        
        // Function to attempt initialization
        function attemptInit() {
            if (initialized) return;
            
            if (typeof BKGTModal !== 'undefined') {
                try {
                    initBkgtInventoryModal();
                    initialized = true;
                    if (typeof bkgt_log === 'function') {
                        bkgt_log('info', 'BKGT Inventory modal system initialized successfully');
                    }
                } catch (e) {
                    if (typeof bkgt_log === 'function') {
                        bkgt_log('error', 'Error initializing inventory modal: ' + e.message);
                    }
                    console.error('BKGT Inventory Modal Init Error:', e);
                }
            }
        }
        
        // Try immediate initialization
        attemptInit();
        
        // If not initialized, try on DOMContentLoaded
        if (!initialized) {
            document.addEventListener('DOMContentLoaded', function() {
                attemptInit();
            });
        }
        
        // If still not initialized, try on load event (fallback)
        if (!initialized) {
            window.addEventListener('load', function() {
                attemptInit();
            });
        }
        
        // Final fallback: check periodically for max 10 seconds
        if (!initialized) {
            var checkCount = 0;
            var checkInterval = setInterval(function() {
                checkCount++;
                attemptInit();
                
                // Stop checking after 100 attempts (roughly 10 seconds at 100ms intervals)
                if (initialized || checkCount > 100) {
                    clearInterval(checkInterval);
                    if (!initialized) {
                        if (typeof bkgt_log === 'function') {
                            bkgt_log('error', 'BKGTModal component not available after timeout');
                        }
                        console.warn('BKGT Inventory: BKGTModal not available, inventory details button will not work');
                    }
                }
            }, 100);
        }
    })();
    </script>
    
    <?php
    return ob_get_clean();
}

/**
 * Create sample data for testing
 */
function bkgt_inventory_create_sample_data() {
    global $wpdb;
    
    // Create sample manufacturers
    $manufacturers_table = $wpdb->prefix . 'bkgt_manufacturers';
    $sample_manufacturers = array(
        array('Nike', 'NIKE'),
        array('Under Armour', 'UA'),
        array('Schutt', 'SCHT'),
        array('Riddell', 'RIDL')
    );
    
    foreach ($sample_manufacturers as $manufacturer) {
        $wpdb->insert($manufacturers_table, array(
            'name' => $manufacturer[0],
            'manufacturer_id' => $manufacturer[1]
        ));
    }
    
    // Create sample item types
    $item_types_table = $wpdb->prefix . 'bkgt_item_types';
    $sample_item_types = array(
        array('Hjälm', 'HELM'),
        array('Axelskydd', 'SHLD'),
        array('Tröja', 'SHRT'),
        array('Byxor', 'PANT'),
        array('Skor', 'SHOE')
    );
    
    foreach ($sample_item_types as $item_type) {
        $wpdb->insert($item_types_table, array(
            'name' => $item_type[0],
            'item_type_id' => $item_type[1]
        ));
    }
    
    // Create sample inventory items
    $inventory_table = $wpdb->prefix . 'bkgt_inventory_items';
    $sample_items = array(
        array('HELM001', 3, 1, 'Schutt F7 VTD', 'Lager A1', 'normal'),
        array('HELM002', 4, 1, 'Riddell SpeedFlex', 'Lager A1', 'normal'),
        array('SHIRT001', 1, 3, 'Nike Vapor Tröja', 'Lager B2', 'normal'),
        array('SHIRT002', 2, 3, 'Under Armour Tröja', 'Lager B2', 'needs_repair'),
        array('PANTS001', 1, 4, 'Nike Vapor Byxor', 'Lager B3', 'normal'),
        array('SHOES001', 1, 5, 'Nike Vapor Skor', 'Lager C1', 'normal')
    );
    
    foreach ($sample_items as $item) {
        $wpdb->insert($inventory_table, array(
            'unique_identifier' => $item[0],
            'manufacturer_id' => $item[1],
            'item_type_id' => $item[2],
            'title' => $item[3],
            'storage_location' => $item[4],
            'condition_status' => $item[5]
        ));
    }
}
