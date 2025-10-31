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

// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-analytics.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-history.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-location.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-manufacturer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-item-type.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-inventory-item.php';

// Include admin files if in admin area
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'admin/class-admin.php';
    require_once plugin_dir_path(__FILE__) . 'admin/class-item-admin.php';
}

// Initialize database class
global $bkgt_inventory_db;
$bkgt_inventory_db = new BKGT_Inventory_Database();

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

// Initialize admin class if in admin area or AJAX request
if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
    new BKGT_Inventory_Admin();
}

// Plugin activation
register_activation_hook(__FILE__, 'bkgt_inventory_activate');
function bkgt_inventory_activate() {
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
}

// Plugin deactivation
register_deactivation_hook(__FILE__, 'bkgt_inventory_deactivate');
function bkgt_inventory_deactivate() {
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
        'public' => false,
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
        'rewrite' => false,
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

    // Query real inventory data from database
    $inventory_db = new BKGT_Inventory_Database();

    $query = "SELECT
        i.*,
        m.name as manufacturer_name,
        t.name as item_type_name,
        pm_assignment.meta_value as assignment_type,
        pm_assigned.meta_value as assigned_to,
        l.name as location_name
        FROM {$inventory_db->get_inventory_items_table()} i
        LEFT JOIN {$inventory_db->get_manufacturers_table()} m ON i.manufacturer_id = m.id
        LEFT JOIN {$inventory_db->get_item_types_table()} t ON i.item_type_id = t.id
        LEFT JOIN {$wpdb->postmeta} pm_assignment ON i.post_id = pm_assignment.post_id AND pm_assignment.meta_key = '_bkgt_assignment_type'
        LEFT JOIN {$wpdb->postmeta} pm_assigned ON i.post_id = pm_assigned.post_id AND pm_assigned.meta_key = '_bkgt_assigned_to'
        LEFT JOIN {$inventory_db->get_locations_table()} l ON pm_assigned.meta_value = l.id AND pm_assignment.meta_value = 'location'
        ORDER BY i.created_at DESC";

    if ($atts['limit'] > 0) {
        $query .= $wpdb->prepare(" LIMIT %d", intval($atts['limit']));
    }

    $inventory_items = $wpdb->get_results($query);

    // If no items in database, show sample data for demonstration
    if (empty($inventory_items)) {
        $sample_items = array(
            array('HELM001', 'Schutt F7 VTD', 'Schutt', 'Hjälm', 'Lager A1', 'normal'),
            array('HELM002', 'Riddell SpeedFlex', 'Riddell', 'Hjälm', 'Lager A1', 'normal'),
            array('SHIRT001', 'Nike Vapor Tröja', 'Nike', 'Tröja', 'Lager B2', 'normal'),
            array('SHIRT002', 'Under Armour Tröja', 'Under Armour', 'Tröja', 'Lager B2', 'needs_repair'),
            array('PANTS001', 'Nike Vapor Byxor', 'Nike', 'Byxor', 'Lager B3', 'normal'),
            array('SHOES001', 'Nike Vapor Skor', 'Nike', 'Skor', 'Lager C1', 'normal')
        );

        // Convert sample data to objects for consistent processing
        $inventory_items = array();
        foreach ($sample_items as $item) {
            $inventory_items[] = (object) array(
                'unique_identifier' => $item[0],
                'title' => $item[1],
                'manufacturer_name' => $item[2],
                'item_type_name' => $item[3],
                'storage_location' => $item[4],
                'condition_status' => $item[5]
            );
        }
    }

    if (empty($inventory_items)) {
        return '<p>Ingen utrustning registrerad ännu.</p>';
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
            ?>
            <div class="bkgt-inventory-item" data-title="<?php echo esc_attr(strtolower($item->title ?? '')); ?>">
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
                    <button class="btn btn-sm btn-outline inventory-action-btn" data-action="view">
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
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Create database tables
 */
function bkgt_inventory_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Manufacturers table
    $manufacturers_table = $wpdb->prefix . 'bkgt_manufacturers';
    $manufacturers_sql = "CREATE TABLE IF NOT EXISTS $manufacturers_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        manufacturer_id varchar(4) NOT NULL UNIQUE,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY manufacturer_id (manufacturer_id)
    ) $charset_collate;";
    
    // Item types table
    $item_types_table = $wpdb->prefix . 'bkgt_item_types';
    $item_types_sql = "CREATE TABLE IF NOT EXISTS $item_types_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        item_type_id varchar(4) NOT NULL UNIQUE,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY item_type_id (item_type_id)
    ) $charset_collate;";
    
    // Inventory items table
    $inventory_items_table = $wpdb->prefix . 'bkgt_inventory_items';
    $inventory_items_sql = "CREATE TABLE IF NOT EXISTS $inventory_items_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        unique_identifier varchar(20) NOT NULL UNIQUE,
        manufacturer_id int(11) NOT NULL,
        item_type_id int(11) NOT NULL,
        title varchar(255) NOT NULL,
        storage_location varchar(255),
        condition_status enum('normal','needs_repair','repaired','reported_lost','scrapped') DEFAULT 'normal',
        condition_date datetime,
        condition_reason text,
        metadata longtext,
        sticker_code varchar(50),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_identifier (unique_identifier),
        FOREIGN KEY (manufacturer_id) REFERENCES $manufacturers_table(id),
        FOREIGN KEY (item_type_id) REFERENCES $item_types_table(id)
    ) $charset_collate;";
    
    // Assignments table
    $assignments_table = $wpdb->prefix . 'bkgt_assignments';
    $assignments_sql = "CREATE TABLE IF NOT EXISTS $assignments_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        item_id int(11) NOT NULL,
        assignee_type enum('location','team','user') NOT NULL,
        assignee_id int(11) NOT NULL,
        assigned_date datetime DEFAULT CURRENT_TIMESTAMP,
        assigned_by int(11) NOT NULL,
        unassigned_date datetime NULL,
        unassigned_by int(11) NULL,
        notes text,
        PRIMARY KEY (id),
        FOREIGN KEY (item_id) REFERENCES $inventory_items_table(id),
        UNIQUE KEY unique_active_assignment (item_id, unassigned_date)
    ) $charset_collate;";
    
    // Locations table
    $locations_table = $wpdb->prefix . 'bkgt_locations';
    $locations_sql = "CREATE TABLE IF NOT EXISTS $locations_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        slug varchar(255) NOT NULL UNIQUE,
        parent_id int(11) DEFAULT NULL,
        location_type enum('storage','repair','locker','warehouse','other') DEFAULT 'storage',
        address text,
        contact_person varchar(255),
        contact_phone varchar(50),
        contact_email varchar(255),
        capacity int(11) DEFAULT NULL,
        access_restrictions text,
        notes text,
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        FOREIGN KEY (parent_id) REFERENCES $locations_table(id) ON DELETE SET NULL
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($manufacturers_sql);
    dbDelta($item_types_sql);
    dbDelta($inventory_items_sql);
    dbDelta($assignments_sql);
    dbDelta($locations_sql);
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
