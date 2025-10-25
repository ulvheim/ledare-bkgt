<?php
/**
 * Plugin Name: BKGT Inventory System
 * Plugin URI: https://ledare.bkgt.se
 * Description: Utrustningssystem för BKGTS Ledarsystem. Hanterar utrustning, tilldelningar och lagerhållning.
 * Version: 1.0.0
 * Author: BKGTS American Football
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
define('BKGT_INV_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once BKGT_INV_PLUGIN_DIR . 'includes/class-database.php';
require_once BKGT_INV_PLUGIN_DIR . 'includes/class-manufacturer.php';
require_once BKGT_INV_PLUGIN_DIR . 'includes/class-item-type.php';
require_once BKGT_INV_PLUGIN_DIR . 'includes/class-inventory-item.php';
require_once BKGT_INV_PLUGIN_DIR . 'includes/class-assignment.php';
require_once BKGT_INV_PLUGIN_DIR . 'includes/class-history.php';
require_once BKGT_INV_PLUGIN_DIR . 'admin/class-admin.php';
require_once BKGT_INV_PLUGIN_DIR . 'admin/class-item-admin.php';

/**
 * Main Plugin Class
 */
class BKGT_Inventory {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Database handler
     */
    public $db;
    
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
        $this->db = new BKGT_Inventory_Database();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'load_textdomain'));
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('init', array($this, 'ensure_default_data'));
        add_action('admin_init', array($this, 'ensure_default_data'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Check dependencies
        add_action('admin_notices', array($this, 'check_dependencies'));
        
        // Frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_bkgt_get_item_types', array($this, 'ajax_get_item_types'));
        add_action('wp_ajax_bkgt_generate_identifier', array($this, 'ajax_generate_identifier'));
        add_action('wp_ajax_bkgt_search_items', array($this, 'ajax_search_items'));
        add_action('wp_ajax_bkgt_search_assignees', array($this, 'ajax_search_assignees'));
        add_action('wp_ajax_bkgt_get_assignment_history', array($this, 'ajax_get_assignment_history'));
        
        // Dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_inventory_dashboard_widget'));
        
        // Shortcodes
        add_shortcode('bkgt_inventory', array($this, 'shortcode_inventory'));
        add_shortcode('bkgt_inventory_admin', array($this, 'shortcode_inventory_admin'));
    }
    
    /**
     * Check plugin dependencies
     */
    public function check_dependencies() {
        if (!class_exists('BKGT_Team') || !class_exists('BKGT_User_Team_Assignment')) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>BKGT Inventory:</strong> ';
            echo __('Funktioner relaterade till lag kräver att BKGT User Management plugin är aktiverad.', 'bkgt-inventory');
            echo '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('BKGT Utrustning', 'bkgt-inventory'),
            __('Utrustning', 'bkgt-inventory'),
            'manage_options',
            'bkgt-inventory',
            array($this, 'admin_page'),
            'dashicons-archive',
            25
        );
        
        add_submenu_page(
            'bkgt-inventory',
            __('Tilldelningar', 'bkgt-inventory'),
            __('Tilldelningar', 'bkgt-inventory'),
            'manage_options',
            'bkgt-item-assignments',
            array($this, 'assignments_admin_page')
        );
        
        add_submenu_page(
            'bkgt-inventory',
            __('Rapporter', 'bkgt-inventory'),
            __('Rapporter', 'bkgt-inventory'),
            'manage_options',
            'bkgt-inventory-reports',
            array($this, 'reports_admin_page')
        );
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('BKGT Utrustningssystem', 'bkgt-inventory'); ?></h1>
            <p><?php _e('Hantera klubbens utrustning här.', 'bkgt-inventory'); ?></p>
            <div class="bkgt-inventory-admin">
                <p><?php _e('Admin interface kommer här.', 'bkgt-inventory'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Item Assignments Admin Page
     */
    public function assignments_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Utrustningstilldelningar', 'bkgt-inventory'); ?></h1>
            <p><?php _e('Tilldela utrustning till lag, spelare eller platser.', 'bkgt-inventory'); ?></p>
            
            <div class="bkgt-assignments-interface">
                <div class="assignments-panel assignments-left-panel">
                    <h3><?php _e('Välj Utrustning', 'bkgt-inventory'); ?></h3>
                    <div class="search-section">
                        <input type="text" id="item-search" placeholder="<?php _e('Sök efter utrustning...', 'bkgt-inventory'); ?>" class="regular-text">
                        <button id="search-items-btn" class="button"><?php _e('Sök', 'bkgt-inventory'); ?></button>
                    </div>
                    <div id="items-list" class="items-list">
                        <!-- Items will be loaded here -->
                    </div>
                </div>
                
                <div class="assignments-panel assignments-right-panel">
                    <h3><?php _e('Välj Mottagare', 'bkgt-inventory'); ?></h3>
                    <div class="assignee-section">
                        <select id="assignee-type" class="assignee-type-select">
                            <option value="location"><?php _e('Plats', 'bkgt-inventory'); ?></option>
                            <option value="team"><?php _e('Lag', 'bkgt-inventory'); ?></option>
                            <option value="user"><?php _e('Användare', 'bkgt-inventory'); ?></option>
                        </select>
                        <input type="text" id="assignee-search" placeholder="<?php _e('Sök efter mottagare...', 'bkgt-inventory'); ?>" class="regular-text">
                        <button id="search-assignees-btn" class="button"><?php _e('Sök', 'bkgt-inventory'); ?></button>
                    </div>
                    <div id="assignees-list" class="assignees-list">
                        <!-- Assignees will be loaded here -->
                    </div>
                </div>
                
                <div class="assignments-actions">
                    <button id="assign-selected-btn" class="button button-primary"><?php _e('Tilldela Valda', 'bkgt-inventory'); ?></button>
                    <button id="bulk-assign-btn" class="button"><?php _e('Massutdelning', 'bkgt-inventory'); ?></button>
                    <button id="view-history-btn" class="button"><?php _e('Visa Historik', 'bkgt-inventory'); ?></button>
                </div>
                
                <div class="workflow-suggestions" id="workflow-suggestions" style="display: none;">
                    <h3><?php _e('Arbetsflödesförslag', 'bkgt-inventory'); ?></h3>
                    <div id="suggestions-content"></div>
                </div>
                
                <!-- Assignment History Modal -->
                <div id="assignment-history-modal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h3><?php _e('Tilldelningshistorik', 'bkgt-inventory'); ?></h3>
                        <div id="history-content"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .bkgt-assignments-interface {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .assignments-panel {
            flex: 1;
            border: 1px solid #ddd;
            padding: 20px;
            background: #fff;
            border-radius: 4px;
        }
        .assignments-left-panel, .assignments-right-panel {
            min-height: 400px;
        }
        .search-section, .assignee-section {
            margin-bottom: 20px;
        }
        .items-list, .assignees-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
        }
        .assignments-actions {
            margin-top: 20px;
            text-align: center;
        }
        .item-item, .assignee-item {
            padding: 8px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        .item-item:hover, .assignee-item:hover {
            background: #f5f5f5;
        }
        .item-item.selected, .assignee-item.selected {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }
        .status {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status.available {
            background: #4caf50;
            color: white;
        }
        .status.assigned {
            background: #ff9800;
            color: white;
        }
        .condition {
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 10px;
            margin-left: 8px;
        }
        .condition-normal {
            background: #4caf50;
            color: white;
        }
        .condition-behöver-reparation {
            background: #f44336;
            color: white;
        }
        .condition-reparerard {
            background: #2196f3;
            color: white;
        }
        .condition-förlustanmäld {
            background: #9c27b0;
            color: white;
        }
        .condition-skrotad {
            background: #607d8b;
            color: white;
        }
        .assignee {
            color: #666;
            font-style: italic;
        }
        .workflow-suggestions {
            margin-top: 20px;
            padding: 15px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .suggestion-item {
            padding: 8px;
            margin-bottom: 8px;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .suggestion-high {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .suggestion-medium {
            background: #d1ecf1;
            border-left-color: #17a2b8;
        }
        .suggestion-low {
            background: #f8f9fa;
            border-left-color: #6c757d;
        }
        .suggestion-alert {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 4px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
        .history-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            margin-bottom: 10px;
        }
        .history-assigned {
            color: #4caf50;
        }
        .history-unassigned {
            color: #f44336;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var nonce = '<?php echo wp_create_nonce('bkgt-inventory-nonce'); ?>';
            
            // Item search
            $('#search-items-btn').on('click', function() {
                var query = $('#item-search').val();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bkgt_search_items',
                        nonce: nonce,
                        query: query
                    },
                    success: function(response) {
                        if (response.success) {
                            displayItems(response.data);
                        }
                    }
                });
            });
            
            // Assignee search
            $('#search-assignees-btn').on('click', function() {
                var type = $('#assignee-type').val();
                var query = $('#assignee-search').val();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bkgt_search_assignees',
                        nonce: nonce,
                        type: type,
                        query: query
                    },
                    success: function(response) {
                        if (response.success) {
                            displayAssignees(response.data);
                        }
                    }
                });
            });
            
            // Assign selected
            $('#assign-selected-btn').on('click', function() {
                var selectedItems = $('.item-item.selected').map(function() { return $(this).data('id'); }).get();
                var selectedAssignee = $('.assignee-item.selected').first();
                
                if (selectedItems.length === 0 || selectedAssignee.length === 0) {
                    alert('<?php _e('Välj minst en utrustning och en mottagare.', 'bkgt-inventory'); ?>');
                    return;
                }
                
                var assigneeType = selectedAssignee.data('type');
                var assigneeId = selectedAssignee.data('id');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bkgt_assign_items',
                        nonce: nonce,
                        item_ids: selectedItems,
                        assignee_type: assigneeType,
                        assignee_id: assigneeId
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            // Refresh lists
                            $('#search-items-btn').click();
                        } else {
                            alert(response.data);
                        }
                    }
                });
            });
            
            // Item selection with workflow suggestions
            $(document).on('click', '.item-item', function() {
                $(this).toggleClass('selected');
                updateWorkflowSuggestions();
            });
            
            // Assignee selection
            $(document).on('click', '.assignee-item', function() {
                $('.assignee-item').removeClass('selected');
                $(this).addClass('selected');
            });
            
            function updateWorkflowSuggestions() {
                var selectedItems = $('.item-item.selected');
                
                if (selectedItems.length === 1) {
                    var itemId = selectedItems.first().data('id');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'bkgt_get_workflow_suggestions',
                            nonce: nonce,
                            item_id: itemId
                        },
                        success: function(response) {
                            if (response.success && response.data.length > 0) {
                                displayWorkflowSuggestions(response.data);
                                $('#workflow-suggestions').show();
                            } else {
                                $('#workflow-suggestions').hide();
                            }
                        }
                    });
                } else {
                    $('#workflow-suggestions').hide();
                }
            }
            
            function displayWorkflowSuggestions(suggestions) {
                var html = '';
                suggestions.forEach(function(suggestion) {
                    html += '<div class="suggestion-item suggestion-' + suggestion.priority + '">' +
                            '<strong>' + suggestion.type + ':</strong> ' + suggestion.message +
                            '</div>';
                });
                $('#suggestions-content').html(html);
            }
            
            // View history button
            $('#view-history-btn').on('click', function() {
                var selectedItems = $('.item-item.selected');
                
                if (selectedItems.length !== 1) {
                    alert('<?php _e('Välj exakt en utrustning för att visa historik.', 'bkgt-inventory'); ?>');
                    return;
                }
                
                var itemId = selectedItems.first().data('id');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bkgt_get_assignment_history',
                        nonce: nonce,
                        item_id: itemId
                    },
                    success: function(response) {
                        if (response.success) {
                            displayAssignmentHistory(response.data);
                            $('#assignment-history-modal').show();
                        }
                    }
                });
            });
            
            // Close modal
            $('.close').on('click', function() {
                $('#assignment-history-modal').hide();
            });
            
            $(window).on('click', function(event) {
                if (event.target.id === 'assignment-history-modal') {
                    $('#assignment-history-modal').hide();
                }
            });
            
            function displayAssignmentHistory(history) {
                var html = '';
                
                if (history.length === 0) {
                    html = '<p><?php _e('Ingen tilldelningshistorik tillgänglig.', 'bkgt-inventory'); ?></p>';
                } else {
                    history.forEach(function(entry) {
                        var actionClass = entry.unassigned_date ? 'history-unassigned' : 'history-assigned';
                        var actionText = entry.unassigned_date ? 
                            '<?php _e('Avtilldelad', 'bkgt-inventory'); ?>' : 
                            '<?php _e('Tilldelad', 'bkgt-inventory'); ?>';
                        var dateText = entry.unassigned_date || entry.assigned_date;
                        var userText = entry.unassigned_date ? entry.unassigned_by_name : entry.assigned_by_name;
                        
                        html += '<div class="history-item ' + actionClass + '">' +
                                '<strong>' + actionText + '</strong> till ' + entry.assignee_name + '<br>' +
                                '<small>' + dateText + ' av ' + userText + '</small>' +
                                '</div>';
                    });
                }
                
                $('#history-content').html(html);
            }
            
            function displayItems(items) {
                var html = '';
                items.forEach(function(item) {
                    var statusClass = item.status === 'available' ? 'available' : 'assigned';
                    var conditionClass = 'condition-' + item.condition.toLowerCase().replace(' ', '-');
                    var assigneeInfo = item.status === 'assigned' ? 
                        '<br><small class="assignee">Tilldelad: ' + item.assignee_name + '</small>' : '';
                    
                    html += '<div class="item-item" data-id="' + item.id + '">' +
                            '<div class="item-header">' +
                            '<strong>' + item.title + '</strong>' +
                            '<span class="status ' + statusClass + '">' + item.status + '</span>' +
                            '</div>' +
                            '<small>' + item.unique_id + '</small>' +
                            '<span class="condition ' + conditionClass + '">' + item.condition + '</span>' +
                            assigneeInfo +
                            '</div>';
                });
                $('#items-list').html(html);
            }
            
            function displayAssignees(assignees) {
                var html = '';
                assignees.forEach(function(assignee) {
                    html += '<div class="assignee-item" data-id="' + assignee.id + '" data-type="' + assignee.type + '">' +
                            '<strong>' + assignee.name + '</strong>' +
                            '</div>';
                });
                $('#assignees-list').html(html);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Reports Admin Page
     */
    public function reports_admin_page() {
        global $wpdb;
        
        $db = BKGT_Database::get_instance();
        
        // Get various statistics
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$db->get_inventory_items_table()}");
        $assigned_items = $wpdb->get_var("SELECT COUNT(DISTINCT item_id) FROM {$db->get_assignments_table()} WHERE unassigned_date IS NULL");
        $available_items = $total_items - $assigned_items;
        
        // Equipment by condition
        $condition_stats = $wpdb->get_results("
            SELECT condition, COUNT(*) as count 
            FROM {$db->get_inventory_items_table()} 
            GROUP BY condition 
            ORDER BY count DESC
        ");
        
        // Assignments by type
        $assignment_stats = $wpdb->get_results("
            SELECT assignee_type, COUNT(*) as count 
            FROM {$db->get_assignments_table()} 
            WHERE unassigned_date IS NULL 
            GROUP BY assignee_type 
            ORDER BY count DESC
        ");
        
        // Most assigned item types
        $popular_types = $wpdb->get_results("
            SELECT t.name, COUNT(i.id) as total_items, COUNT(a.item_id) as assigned_items
            FROM {$db->get_item_types_table()} t
            LEFT JOIN {$db->get_inventory_items_table()} i ON t.id = i.item_type_id
            LEFT JOIN {$db->get_assignments_table()} a ON i.id = a.item_id AND a.unassigned_date IS NULL
            GROUP BY t.id, t.name
            ORDER BY assigned_items DESC, total_items DESC
            LIMIT 10
        ");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Utrustningsrapporter', 'bkgt-inventory'); ?></h1>
            
            <div class="bkgt-reports-grid">
                <!-- Overview Cards -->
                <div class="bkgt-report-card">
                    <h3><?php _e('Översikt', 'bkgt-inventory'); ?></h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $total_items; ?></span>
                            <span class="stat-label"><?php _e('Total Utrustning', 'bkgt-inventory'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $assigned_items; ?></span>
                            <span class="stat-label"><?php _e('Tilldelad', 'bkgt-inventory'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $available_items; ?></span>
                            <span class="stat-label"><?php _e('Tillgänglig', 'bkgt-inventory'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $total_items > 0 ? round(($assigned_items / $total_items) * 100) : 0; ?>%</span>
                            <span class="stat-label"><?php _e('Utnyttjandegrad', 'bkgt-inventory'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Condition Status -->
                <div class="bkgt-report-card">
                    <h3><?php _e('Skick på Utrustning', 'bkgt-inventory'); ?></h3>
                    <div class="condition-chart">
                        <?php foreach ($condition_stats as $stat): ?>
                        <div class="condition-item">
                            <span class="condition-label"><?php echo esc_html($stat->condition); ?></span>
                            <span class="condition-count"><?php echo $stat->count; ?></span>
                            <div class="condition-bar" style="width: <?php echo $total_items > 0 ? ($stat->count / $total_items) * 100 : 0; ?>%"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Assignment Types -->
                <div class="bkgt-report-card">
                    <h3><?php _e('Tilldelningar per Typ', 'bkgt-inventory'); ?></h3>
                    <div class="assignment-chart">
                        <?php foreach ($assignment_stats as $stat): 
                            $type_label = $stat->assignee_type === 'location' ? __('Plats', 'bkgt-inventory') : 
                                        ($stat->assignee_type === 'team' ? __('Lag', 'bkgt-inventory') : __('Användare', 'bkgt-inventory'));
                        ?>
                        <div class="assignment-item">
                            <span class="assignment-label"><?php echo $type_label; ?></span>
                            <span class="assignment-count"><?php echo $stat->count; ?></span>
                            <div class="assignment-bar" style="width: <?php echo $assigned_items > 0 ? ($stat->count / $assigned_items) * 100 : 0; ?>%"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Popular Item Types -->
                <div class="bkgt-report-card">
                    <h3><?php _e('Mest Tilldelade Typer', 'bkgt-inventory'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Utrustningstyp', 'bkgt-inventory'); ?></th>
                                <th><?php _e('Total', 'bkgt-inventory'); ?></th>
                                <th><?php _e('Tilldelad', 'bkgt-inventory'); ?></th>
                                <th><?php _e('Tillgänglig', 'bkgt-inventory'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($popular_types as $type): ?>
                            <tr>
                                <td><?php echo esc_html($type->name); ?></td>
                                <td><?php echo $type->total_items; ?></td>
                                <td><?php echo $type->assigned_items; ?></td>
                                <td><?php echo $type->total_items - $type->assigned_items; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <style>
        .bkgt-reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .bkgt-report-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .stat-number {
            display: block;
            font-size: 2em;
            font-weight: bold;
            color: #007cba;
        }
        .stat-label {
            font-size: 0.9em;
            color: #666;
        }
        .condition-chart, .assignment-chart {
            margin-top: 15px;
        }
        .condition-item, .assignment-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        .condition-label, .assignment-label {
            flex: 1;
            font-size: 0.9em;
        }
        .condition-count, .assignment-count {
            margin-left: 10px;
            font-weight: bold;
            min-width: 30px;
        }
        .condition-bar, .assignment-bar {
            height: 8px;
            background: #007cba;
            border-radius: 4px;
            margin-left: 10px;
            transition: width 0.3s ease;
        }
        </style>
        <?php
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->db->create_tables();
        
        // Register post types first
        $this->register_post_types();
        $this->register_taxonomies();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Create default data
        $this->create_default_data();
        
        // Set plugin version
        update_option('bkgt_inv_version', BKGT_INV_VERSION);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'bkgt-inventory',
            false,
            dirname(BKGT_INV_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Inventory Item post type
        register_post_type('bkgt_inventory_item', array(
            'labels' => array(
                'name'               => __('Utrustning', 'bkgt-inventory'),
                'singular_name'      => __('Utrustningsartikel', 'bkgt-inventory'),
                'menu_name'          => __('Utrustning', 'bkgt-inventory'),
                'add_new'            => __('Lägg till ny', 'bkgt-inventory'),
                'add_new_item'       => __('Lägg till ny utrustningsartikel', 'bkgt-inventory'),
                'edit_item'          => __('Redigera utrustningsartikel', 'bkgt-inventory'),
                'new_item'           => __('Ny utrustningsartikel', 'bkgt-inventory'),
                'view_item'          => __('Visa utrustningsartikel', 'bkgt-inventory'),
                'search_items'       => __('Sök utrustning', 'bkgt-inventory'),
                'not_found'          => __('Ingen utrustning hittades', 'bkgt-inventory'),
                'not_found_in_trash' => __('Ingen utrustning i papperskorgen', 'bkgt-inventory'),
            ),
            'public'              => true,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-archive',
            'menu_position'       => 26,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title', 'thumbnail'),
            'has_archive'         => false,
            'rewrite'             => false,
            'show_in_rest'        => false,
        ));
    }
    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Condition taxonomy
        register_taxonomy('bkgt_condition', 'bkgt_inventory_item', array(
            'labels' => array(
                'name'              => __('Skick', 'bkgt-inventory'),
                'singular_name'     => __('Skick', 'bkgt-inventory'),
                'search_items'      => __('Sök skick', 'bkgt-inventory'),
                'all_items'         => __('Alla skick', 'bkgt-inventory'),
                'edit_item'         => __('Redigera skick', 'bkgt-inventory'),
                'update_item'       => __('Uppdatera skick', 'bkgt-inventory'),
                'add_new_item'      => __('Lägg till nytt skick', 'bkgt-inventory'),
                'new_item_name'     => __('Nytt skick namn', 'bkgt-inventory'),
                'menu_name'         => __('Skick', 'bkgt-inventory'),
            ),
            'hierarchical'      => false,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => false,
        ));
        
        // Storage Location taxonomy
        register_taxonomy('bkgt_storage_location', 'bkgt_inventory_item', array(
            'labels' => array(
                'name'              => __('Lagringsplats', 'bkgt-inventory'),
                'singular_name'     => __('Lagringsplats', 'bkgt-inventory'),
                'search_items'      => __('Sök lagringsplats', 'bkgt-inventory'),
                'all_items'         => __('Alla lagringsplatser', 'bkgt-inventory'),
                'edit_item'         => __('Redigera lagringsplats', 'bkgt-inventory'),
                'update_item'       => __('Uppdatera lagringsplats', 'bkgt-inventory'),
                'add_new_item'      => __('Lägg till ny lagringsplats', 'bkgt-inventory'),
                'new_item_name'     => __('Ny lagringsplats namn', 'bkgt-inventory'),
                'menu_name'         => __('Lagringsplatser', 'bkgt-inventory'),
            ),
            'hierarchical'      => false,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => false,
        ));
    }
    
    /**
     * Ensure default data exists
     */
    public function ensure_default_data() {
        error_log('BKGT Inventory: ensure_default_data called');
        // Check if we have manufacturers
        $manufacturers = BKGT_Manufacturer::get_all();
        error_log('BKGT Inventory: Found ' . count($manufacturers) . ' manufacturers');

        if (empty($manufacturers)) {
            error_log('BKGT Inventory: No manufacturers found, calling update_default_data');
            $this->update_default_data();
        } else {
            error_log('BKGT Inventory: Manufacturers already exist');
        }
    }
    public function update_default_data() {
        // Clear existing manufacturers and item types
        global $wpdb;
        
        // Clear manufacturers
        $wpdb->query("DELETE FROM {$wpdb->prefix}bkgt_manufacturers");
        
        // Clear item types
        $wpdb->query("DELETE FROM {$wpdb->prefix}bkgt_item_types");
        
        // Reset auto increment
        $wpdb->query("ALTER TABLE {$wpdb->prefix}bkgt_manufacturers AUTO_INCREMENT = 1");
        $wpdb->query("ALTER TABLE {$wpdb->prefix}bkgt_item_types AUTO_INCREMENT = 1");
        
        // Call create_default_data
        $this->create_default_data();
    }
    
    /**
     * Create default data
     */
    private function create_default_data() {
        // Create default conditions
        $conditions = array(
            'normal' => __('Normal', 'bkgt-inventory'),
            'behöver-reparation' => __('Behöver reparation', 'bkgt-inventory'),
            'reparerad' => __('Reparerad', 'bkgt-inventory'),
            'förlustanmäld' => __('Förlustanmäld', 'bkgt-inventory'),
            'skrotad' => __('Skrotad', 'bkgt-inventory'),
        );
        
        foreach ($conditions as $slug => $name) {
            if (!term_exists($slug, 'bkgt_condition')) {
                wp_insert_term($name, 'bkgt_condition', array('slug' => $slug));
            }
        }
        
        // Create default storage locations
        $locations = array(
            'klubbförråd' => __('Klubbförråd', 'bkgt-inventory'),
            'containern-tyresövallen' => __('Containern, Tyresövallen', 'bkgt-inventory'),
        );
        
        foreach ($locations as $slug => $name) {
            if (!term_exists($slug, 'bkgt_storage_location')) {
                wp_insert_term($name, 'bkgt_storage_location', array('slug' => $slug));
            }
        }
        
        // Create default manufacturers
        $default_manufacturers = array(
            array('name' => 'BKGT', 'id' => '0001'),
            array('name' => 'Riddell', 'id' => '0002'),
            array('name' => 'Schutt', 'id' => '0003'),
            array('name' => 'Xenith', 'id' => '0004'),
            array('name' => 'Wilson', 'id' => '0005'),
            array('name' => 'Quickslant', 'id' => '0006'),
            array('name' => 'Reyrr', 'id' => '0007'),
        );
        
        foreach ($default_manufacturers as $manufacturer) {
            BKGT_Manufacturer::create($manufacturer['name'], $manufacturer['id']);
        }
        
        // Create default item types with custom fields
        $default_item_types = array(
            array(
                'name' => 'Hjälmar', 
                'id' => '0001',
                'custom_fields' => array(
                    'storlek' => array(
                        'type' => 'text',
                        'label' => 'Storlek',
                        'required' => false,
                        'placeholder' => 't.ex. M, L, XL'
                    )
                )
            ),
            array(
                'name' => 'Axelskydd', 
                'id' => '0002',
                'custom_fields' => array(
                    'storlek' => array(
                        'type' => 'text',
                        'label' => 'Storlek',
                        'required' => false,
                        'placeholder' => 't.ex. S, M, L'
                    )
                )
            ),
            array(
                'name' => 'Spelarbyxor', 
                'id' => '0003',
                'custom_fields' => array(
                    'storlek' => array(
                        'type' => 'text',
                        'label' => 'Storlek',
                        'required' => false,
                        'placeholder' => 't.ex. 30, 32, 34'
                    )
                )
            ),
            array(
                'name' => 'Träningströjor', 
                'id' => '0004',
                'custom_fields' => array(
                    'storlek' => array(
                        'type' => 'text',
                        'label' => 'Storlek',
                        'required' => false,
                        'placeholder' => 't.ex. S, M, L, XL'
                    )
                )
            ),
            array(
                'name' => 'Fotbollar', 
                'id' => '0005',
                'custom_fields' => array(
                    'storlek' => array(
                        'type' => 'select',
                        'label' => 'Storlek',
                        'required' => false,
                        'options' => array('3', '4', '5', '6', '7')
                    )
                )
            ),
        );
        
        foreach ($default_item_types as $item_type) {
            $custom_fields = isset($item_type['custom_fields']) ? $item_type['custom_fields'] : array();
            BKGT_Item_Type::create($item_type['name'], $item_type['id'], $custom_fields);
        }
    }
    
    /**
     * AJAX: Get item types for manufacturer
     */
    public function ajax_get_item_types() {
        check_ajax_referer('bkgt-inventory-nonce', 'nonce');
        
        $manufacturer_id = intval($_POST['manufacturer_id']);
        $item_types = BKGT_Item_Type::get_all();
        
        wp_send_json_success($item_types);
    }
    
    /**
     * AJAX: Generate unique identifier
     */
    public function ajax_generate_identifier() {
        check_ajax_referer('bkgt-inventory-nonce', 'nonce');
        
        $manufacturer_id = intval($_POST['manufacturer_id']);
        $item_type_id = intval($_POST['item_type_id']);
        
        $identifier = BKGT_Inventory_Item::generate_unique_identifier($manufacturer_id, $item_type_id);
        
        wp_send_json_success(array('identifier' => $identifier));
    }
    
    /**
     * AJAX: Search items
     */
    public function ajax_search_items() {
        check_ajax_referer('bkgt-inventory-nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query']);
        
        global $wpdb;
        $db = BKGT_Database::get_instance();
        $inventory_table = $db->get_inventory_items_table();
        $assignments_table = $db->get_assignments_table();
        
        // Build search query
        $where_clause = '';
        $search_terms = array();
        
        if (!empty($query)) {
            $search_terms = array(
                $wpdb->prepare("i.unique_id LIKE %s", '%' . $wpdb->esc_like($query) . '%'),
                $wpdb->prepare("m.name LIKE %s", '%' . $wpdb->esc_like($query) . '%'),
                $wpdb->prepare("t.name LIKE %s", '%' . $wpdb->esc_like($query) . '%')
            );
            $where_clause = 'WHERE (' . implode(' OR ', $search_terms) . ')';
        }
        
        // Get items with assignment status and current assignee
        $sql = "SELECT i.id, i.unique_id, i.condition, i.sticker,
                       CONCAT(m.name, ' ', t.name) as title,
                       CASE WHEN a.id IS NULL THEN 'available' ELSE 'assigned' END as status,
                       CASE 
                           WHEN a.assignee_type = 'location' THEN 'Plats'
                           WHEN a.assignee_type = 'team' THEN COALESCE(tm.post_title, 'Lag')
                           WHEN a.assignee_type = 'user' THEN COALESCE(um.display_name, 'Användare')
                           ELSE 'Otilldelad'
                       END as assignee_name
                FROM {$inventory_table} i
                LEFT JOIN {$db->get_manufacturers_table()} m ON i.manufacturer_id = m.id
                LEFT JOIN {$db->get_item_types_table()} t ON i.item_type_id = t.id
                LEFT JOIN {$assignments_table} a ON i.id = a.item_id AND a.unassigned_date IS NULL
                LEFT JOIN {$wpdb->posts} tm ON a.assignee_type = 'team' AND a.assignee_id = tm.ID
                LEFT JOIN {$wpdb->users} um ON a.assignee_type = 'user' AND a.assignee_id = um.ID
                {$where_clause}
                ORDER BY i.created_date DESC
                LIMIT 50";
        
        $items = $wpdb->get_results($sql);
        
        // Format for frontend
        $formatted_items = array();
        foreach ($items as $item) {
            $formatted_items[] = array(
                'id' => $item->id,
                'title' => $item->title,
                'unique_id' => $item->unique_id,
                'status' => $item->status,
                'condition' => $item->condition,
                'sticker' => $item->sticker,
                'assignee_name' => $item->assignee_name
            );
        }
        
        wp_send_json_success($formatted_items);
    }
    
    /**
     * AJAX: Search assignees
     */
    public function ajax_search_assignees() {
        check_ajax_referer('bkgt-inventory-nonce', 'nonce');
        
        $type = sanitize_text_field($_POST['type']);
        $query = sanitize_text_field($_POST['query']);
        
        $assignees = array();
        
        switch ($type) {
            case 'location':
                // For locations, we'll use predefined storage locations
                $locations = array(
                    1 => __('Förråd Siklöjevägen', 'bkgt-inventory'),
                    2 => __('Containern, Tyresövallen', 'bkgt-inventory'),
                    3 => __('Klubblokalen', 'bkgt-inventory'),
                    4 => __('Träningsplan', 'bkgt-inventory')
                );
                
                foreach ($locations as $id => $name) {
                    if (empty($query) || stripos($name, $query) !== false) {
                        $assignees[] = array(
                            'id' => $id,
                            'name' => $name,
                            'type' => 'location'
                        );
                    }
                }
                break;
                
            case 'team':
                // Search teams if User Management plugin is available
                if (class_exists('BKGT_Team')) {
                    $teams = BKGT_Team::get_teams();
                    foreach ($teams as $team) {
                        if (empty($query) || stripos($team->post_title, $query) !== false) {
                            $assignees[] = array(
                                'id' => $team->ID,
                                'name' => $team->post_title,
                                'type' => 'team'
                            );
                        }
                    }
                } else {
                    // Fallback sample data
                    $sample_teams = array(
                        array('id' => 1, 'name' => 'Damlag'),
                        array('id' => 2, 'name' => 'Herrlag'),
                        array('id' => 3, 'name' => 'U17'),
                        array('id' => 4, 'name' => 'U19')
                    );
                    
                    foreach ($sample_teams as $team) {
                        if (empty($query) || stripos($team['name'], $query) !== false) {
                            $assignees[] = array(
                                'id' => $team['id'],
                                'name' => $team['name'],
                                'type' => 'team'
                            );
                        }
                    }
                }
                break;
                
            case 'user':
                // Search users
                $user_args = array(
                    'search' => '*' . $query . '*',
                    'search_columns' => array('user_login', 'user_email', 'display_name'),
                    'number' => 20
                );
                
                if (empty($query)) {
                    $user_args = array('number' => 20);
                }
                
                $users = get_users($user_args);
                
                foreach ($users as $user) {
                    $assignees[] = array(
                        'id' => $user->ID,
                        'name' => $user->display_name,
                        'type' => 'user'
                    );
                }
                break;
        }
        
        wp_send_json_success($assignees);
    }
    
    /**
     * AJAX: Assign items
     */
    public function ajax_assign_items() {
        check_ajax_referer('bkgt-inventory-nonce', 'nonce');
        
        $item_ids = array_map('intval', $_POST['item_ids']);
        $assignee_type = sanitize_text_field($_POST['assignee_type']);
        $assignee_id = intval($_POST['assignee_id']);
        
        // Map frontend assignee types to assignment class constants
        $assignment_type_map = array(
            'location' => BKGT_Assignment::TYPE_CLUB,
            'team' => BKGT_Assignment::TYPE_TEAM,
            'user' => BKGT_Assignment::TYPE_INDIVIDUAL
        );
        
        $assignment_type = isset($assignment_type_map[$assignee_type]) ? $assignment_type_map[$assignee_type] : BKGT_Assignment::TYPE_CLUB;
        
        // Validate assignment
        $validation = BKGT_Assignment::validate_assignment($item_ids, $assignment_type, $assignee_id);
        
        if (!$validation['valid']) {
            wp_send_json_error(__('Tilldelning kunde inte genomföras:', 'bkgt-inventory') . ' ' . implode(', ', $validation['errors']));
        }
        
        // Show warnings if any
        $warning_message = '';
        if (!empty($validation['warnings'])) {
            $warning_message = __('Varningar:', 'bkgt-inventory') . ' ' . implode(', ', $validation['warnings']) . ' ';
        }
        
        // Perform bulk assignment
        $results = BKGT_Assignment::bulk_assign($item_ids, $assignment_type, $assignee_id);
        
        // Check if all assignments were successful
        $success_count = 0;
        $error_messages = array();
        
        foreach ($results as $item_id => $result) {
            if (is_wp_error($result)) {
                $error_messages[] = sprintf(__('Artikel %d: %s', 'bkgt-inventory'), $item_id, $result->get_error_message());
            } else {
                $success_count++;
            }
        }
        
        if ($success_count > 0) {
            $message = $warning_message . sprintf(
                _n(
                    '%d utrustning tilldelad framgångsrikt.',
                    '%d utrustningar tilldelade framgångsrikt.',
                    $success_count,
                    'bkgt-inventory'
                ), 
                $success_count
            );
            
            if (!empty($error_messages)) {
                $message .= ' ' . __('Vissa tilldelningar misslyckades:', 'bkgt-inventory') . ' ' . implode(', ', $error_messages);
            }
            
            wp_send_json_success(array('message' => $message));
        } else {
            wp_send_json_error(__('Inga utrustningar kunde tilldelas.', 'bkgt-inventory') . ' ' . implode(', ', $error_messages));
        }
    }
    
    /**
     * Add inventory dashboard widget
     */
    public function add_inventory_dashboard_widget() {
        wp_add_dashboard_widget(
            'bkgt_inventory_stats',
            __('Utrustning - Snabbstatistik', 'bkgt-inventory'),
            array($this, 'display_inventory_stats_widget')
        );
    }
    
    /**
     * Display inventory stats dashboard widget
     */
    public function display_inventory_stats_widget() {
        global $wpdb;
        $db = BKGT_Database::get_instance();
        
        // Get quick statistics
        $inventory_table = $db->get_inventory_items_table();
        $assignments_table = $db->get_assignments_table();
        
        // Total items
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$inventory_table}");
        
        // Available items
        $available_items = $wpdb->get_var("
            SELECT COUNT(*) FROM {$inventory_table} i
            LEFT JOIN {$assignments_table} a ON i.id = a.item_id AND a.unassigned_date IS NULL
            WHERE a.id IS NULL
        ");
        
        // Assigned items
        $assigned_items = $total_items - $available_items;
        
        // Items by condition
        $condition_stats = $wpdb->get_results("
            SELECT condition, COUNT(*) as count
            FROM {$inventory_table}
            GROUP BY condition
            ORDER BY count DESC
        ", ARRAY_A);
        
        // Recent assignments (last 30 days)
        $recent_assignments = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$assignments_table}
            WHERE assigned_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        "));
        
        // Items needing attention (damaged or lost)
        $attention_items = $wpdb->get_var("
            SELECT COUNT(*) FROM {$inventory_table}
            WHERE condition IN ('skadad', 'förlustanmäld')
        ");
        
        // Top assigned item types
        $top_types = $wpdb->get_results("
            SELECT t.name as type_name, COUNT(i.id) as count
            FROM {$inventory_table} i
            JOIN {$db->get_item_types_table()} t ON i.item_type_id = t.id
            GROUP BY t.id, t.name
            ORDER BY count DESC
            LIMIT 3
        ", ARRAY_A);
        
        ?>
        <div class="bkgt-stats-widget">
            <div class="stats-grid">
                <div class="stat-card total-items">
                    <div class="stat-number"><?php echo number_format_i18n($total_items); ?></div>
                    <div class="stat-label"><?php _e('Total Utrustning', 'bkgt-inventory'); ?></div>
                </div>
                
                <div class="stat-card available-items">
                    <div class="stat-number"><?php echo number_format_i18n($available_items); ?></div>
                    <div class="stat-label"><?php _e('Tillgänglig', 'bkgt-inventory'); ?></div>
                    <div class="stat-percentage"><?php echo $total_items > 0 ? round(($available_items / $total_items) * 100) : 0; ?>%</div>
                </div>
                
                <div class="stat-card assigned-items">
                    <div class="stat-number"><?php echo number_format_i18n($assigned_items); ?></div>
                    <div class="stat-label"><?php _e('Tilldelad', 'bkgt-inventory'); ?></div>
                    <div class="stat-percentage"><?php echo $total_items > 0 ? round(($assigned_items / $total_items) * 100) : 0; ?>%</div>
                </div>
                
                <div class="stat-card recent-activity">
                    <div class="stat-number"><?php echo number_format_i18n($recent_assignments); ?></div>
                    <div class="stat-label"><?php _e('Nya Tilldelningar (30d)', 'bkgt-inventory'); ?></div>
                </div>
            </div>
            
            <?php if ($attention_items > 0): ?>
            <div class="attention-alert">
                <span class="dashicons dashicons-warning"></span>
                <?php printf(_n('%d artikel behöver uppmärksamhet', '%d artiklar behöver uppmärksamhet', $attention_items, 'bkgt-inventory'), $attention_items); ?>
            </div>
            <?php endif; ?>
            
            <div class="stats-details">
                <div class="condition-breakdown">
                    <h4><?php _e('Skickfördelning', 'bkgt-inventory'); ?></h4>
                    <ul>
                        <?php foreach ($condition_stats as $stat): ?>
                        <li>
                            <span class="condition-label"><?php echo esc_html(ucfirst($stat['condition'])); ?></span>
                            <span class="condition-count"><?php echo number_format_i18n($stat['count']); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="top-types">
                    <h4><?php _e('Mest Utrustning', 'bkgt-inventory'); ?></h4>
                    <ul>
                        <?php foreach ($top_types as $type): ?>
                        <li>
                            <span class="type-label"><?php echo esc_html($type['type_name']); ?></span>
                            <span class="type-count"><?php echo number_format_i18n($type['count']); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <div class="widget-actions">
                <a href="<?php echo admin_url('admin.php?page=bkgt-inventory-reports'); ?>" class="button">
                    <?php _e('Visa Rapporter', 'bkgt-inventory'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=bkgt-item-assignments'); ?>" class="button">
                    <?php _e('Hantera Tilldelningar', 'bkgt-inventory'); ?>
                </a>
            </div>
        </div>
        
        <style>
        .bkgt-stats-widget {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #fff;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #1d2327;
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #646970;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .stat-percentage {
            font-size: 11px;
            color: #8c8f94;
            margin-top: 3px;
        }
        
        .available-items .stat-number { color: #00a32a; }
        .assigned-items .stat-number { color: #2271b1; }
        .recent-activity .stat-number { color: #dba617; }
        
        .attention-alert {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #856404;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .attention-alert .dashicons {
            color: #856404;
        }
        
        .stats-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stats-details h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #1d2327;
            font-weight: 600;
        }
        
        .stats-details ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .stats-details li {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .stats-details li:last-child {
            border-bottom: none;
        }
        
        .condition-label, .type-label {
            color: #1d2327;
            font-size: 13px;
        }
        
        .condition-count, .type-count {
            color: #646970;
            font-weight: 600;
            font-size: 13px;
        }
        
        .widget-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .widget-actions .button {
            flex: 1;
            text-align: center;
            text-decoration: none;
        }
        
        @media (max-width: 782px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .stats-details {
                grid-template-columns: 1fr;
            }
            
            .widget-actions {
                flex-direction: column;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!is_singular('bkgt_inventory_item') && !is_post_type_archive('bkgt_inventory_item')) {
            return;
        }
        
        wp_enqueue_style(
            'bkgt-inventory-frontend',
            BKGT_INV_PLUGIN_URL . 'assets/frontend.css',
            array(),
            BKGT_INV_VERSION
        );
    }
    
    /**
     * Shortcode for inventory display
     */
    public function shortcode_inventory($atts) {
        // Check user permissions
        if (!is_user_logged_in()) {
            return '<p>' . __('Du måste vara inloggad för att se denna sida.', 'bkgt-inventory') . '</p>';
        }
        
        // Get current user role
        $user = wp_get_current_user();
        $user_roles = $user->roles;
        
        // Basic inventory list
        ob_start();
        ?>
        <div class="bkgt-inventory-container">
            <h2><?php _e('Utrustningsinventarie', 'bkgt-inventory'); ?></h2>
            <p><?php _e('Här kan du hantera klubbens utrustning.', 'bkgt-inventory'); ?></p>
            
            <?php if (in_array('administrator', $user_roles) || in_array('styrelsemedlem', $user_roles)): ?>
                <a href="<?php echo esc_url(home_url('/?page_id=18')); ?>" class="btn btn-primary">
                    <?php _e('Hantera Inventarie', 'bkgt-inventory'); ?>
                </a>
            <?php endif; ?>
            
            <!-- Placeholder for inventory list -->
            <div class="inventory-list">
                <p><?php _e('Inventarielista kommer här.', 'bkgt-inventory'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode for inventory admin interface
     */
    public function shortcode_inventory_admin($atts) {
        // Check user permissions - only admins
        if (!current_user_can('manage_options')) {
            return '<p>' . __('Du har inte behörighet att komma åt denna sida.', 'bkgt-inventory') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="bkgt-inventory-admin">
            <h2><?php _e('Hantera Utrustning', 'bkgt-inventory'); ?></h2>
            
            <!-- Add Item Form -->
            <div class="admin-section">
                <h3><?php _e('Lägg till ny utrustning', 'bkgt-inventory'); ?></h3>
                <form id="add-item-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="manufacturer"><?php _e('Tillverkare', 'bkgt-inventory'); ?></label>
                            <select id="manufacturer" name="manufacturer_id" required>
                                <option value=""><?php _e('Välj tillverkare', 'bkgt-inventory'); ?></option>
                                <?php
                                $manufacturers = BKGT_Manufacturer::get_all();
                                foreach ($manufacturers as $manufacturer) {
                                    echo '<option value="' . esc_attr($manufacturer['id']) . '">' . esc_html($manufacturer['name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="item_type"><?php _e('Artikeltyp', 'bkgt-inventory'); ?></label>
                            <select id="item_type" name="item_type_id" required>
                                <option value=""><?php _e('Välj artikeltyp', 'bkgt-inventory'); ?></option>
                                <?php
                                $item_types = BKGT_Item_Type::get_all();
                                foreach ($item_types as $item_type) {
                                    echo '<option value="' . esc_attr($item_type['id']) . '">' . esc_html($item_type['name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="unique_identifier"><?php _e('Unik Identifierare', 'bkgt-inventory'); ?></label>
                            <input type="text" id="unique_identifier" name="unique_identifier" readonly required>
                            <button type="button" id="generate-identifier"><?php _e('Generera', 'bkgt-inventory'); ?></button>
                        </div>
                        <div class="form-group">
                            <label for="assigned_to"><?php _e('Tilldelad till', 'bkgt-inventory'); ?></label>
                            <select id="assigned_to" name="assigned_to">
                                <option value="club"><?php _e('Klubben', 'bkgt-inventory'); ?></option>
                                <option value="team"><?php _e('Lag', 'bkgt-inventory'); ?></option>
                                <option value="player"><?php _e('Spelare', 'bkgt-inventory'); ?></option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><?php _e('Lägg till', 'bkgt-inventory'); ?></button>
                </form>
            </div>
            
            <!-- Inventory List -->
            <div class="admin-section">
                <h3><?php _e('Utrustningslista', 'bkgt-inventory'); ?></h3>
                <div id="inventory-table">
                    <p><?php _e('Laddar...', 'bkgt-inventory'); ?></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * Initialize the plugin
 */
function bkgt_inventory() {
    return BKGT_Inventory::get_instance();
}

// Start the plugin
bkgt_inventory();

// Initialize admin classes
add_action('admin_init', function() {
    new BKGT_Inventory_Admin();
    new BKGT_Item_Admin();
});
