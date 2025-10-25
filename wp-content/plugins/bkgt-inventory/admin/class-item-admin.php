<?php
/**
 * Inventory Item Admin Customization
 *
 * @package BKGT_Inventory
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Item_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_filter('manage_bkgt_inventory_item_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_bkgt_inventory_item_posts_custom_column', array($this, 'render_custom_columns'), 10, 2);
        add_filter('manage_edit-bkgt_inventory_item_sortable_columns', array($this, 'make_columns_sortable'));
        add_action('pre_get_posts', array($this, 'modify_query_for_sorting'));
        add_filter('post_row_actions', array($this, 'modify_row_actions'), 10, 2);
        add_action('restrict_manage_posts', array($this, 'add_filters'));
        add_filter('parse_query', array($this, 'filter_posts'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_init', array($this, 'handle_quick_assignments'));
    }
    
    /**
     * Add custom columns to admin list
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['unique_id'] = __('Unikt ID', 'bkgt-inventory');
                $new_columns['manufacturer'] = __('Tillverkare', 'bkgt-inventory');
                $new_columns['item_type'] = __('Typ', 'bkgt-inventory');
                $new_columns['assignment'] = __('Tilldelning', 'bkgt-inventory');
                $new_columns['condition'] = __('Skick', 'bkgt-inventory');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Render custom columns
     */
    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'unique_id':
                $unique_id = get_post_meta($post_id, '_bkgt_unique_id', true);
                echo $unique_id ? esc_html($unique_id) : '<em>' . __('Ej genererat', 'bkgt-inventory') . '</em>';
                break;
                
            case 'manufacturer':
                $manufacturer_id = get_post_meta($post_id, '_bkgt_manufacturer_id', true);
                if ($manufacturer_id) {
                    $manufacturer = BKGT_Manufacturer::get($manufacturer_id);
                    echo $manufacturer ? esc_html($manufacturer->name) : __('Okänd', 'bkgt-inventory');
                } else {
                    echo __('Ej vald', 'bkgt-inventory');
                }
                break;
                
            case 'item_type':
                $item_type_id = get_post_meta($post_id, '_bkgt_item_type_id', true);
                if ($item_type_id) {
                    $item_type = BKGT_Item_Type::get($item_type_id);
                    echo $item_type ? esc_html($item_type->name) : __('Okänd', 'bkgt-inventory');
                } else {
                    echo __('Ej vald', 'bkgt-inventory');
                }
                break;
                
            case 'assignment':
                $assignment_type = get_post_meta($post_id, '_bkgt_assignment_type', true);
                $assigned_to = get_post_meta($post_id, '_bkgt_assigned_to', true);
                
                if (empty($assignment_type)) {
                    echo __('Ej tilldelad', 'bkgt-inventory');
                } else {
                    $type_labels = array(
                        'club' => __('Klubben', 'bkgt-inventory'),
                        'team' => __('Lag', 'bkgt-inventory'),
                        'individual' => __('Individ', 'bkgt-inventory'),
                    );
                    
                    $label = isset($type_labels[$assignment_type]) ? $type_labels[$assignment_type] : $assignment_type;
                    
                    if ($assignment_type === 'team' && $assigned_to) {
                        // Get team name from user management plugin
                        if (function_exists('bkgt_get_team')) {
                            $team = bkgt_get_team($assigned_to);
                            if ($team) {
                                $label .= ': ' . esc_html($team->name);
                            }
                        }
                    } elseif ($assignment_type === 'individual' && $assigned_to) {
                        $user = get_userdata($assigned_to);
                        if ($user) {
                            $label .= ': ' . esc_html($user->display_name);
                        }
                    }
                    
                    echo esc_html($label);
                }
                break;
                
            case 'condition':
                $conditions = wp_get_post_terms($post_id, 'bkgt_condition');
                if (!empty($conditions)) {
                    $condition_names = array();
                    foreach ($conditions as $condition) {
                        $condition_names[] = $condition->name;
                    }
                    echo esc_html(implode(', ', $condition_names));
                } else {
                    echo __('Ej satt', 'bkgt-inventory');
                }
                break;
        }
    }
    
    /**
     * Make columns sortable
     */
    public function make_columns_sortable($columns) {
        $columns['unique_id'] = 'unique_id';
        $columns['manufacturer'] = 'manufacturer';
        $columns['item_type'] = 'item_type';
        $columns['assignment'] = 'assignment';
        
        return $columns;
    }
    
    /**
     * Modify query for sorting
     */
    public function modify_query_for_sorting($query) {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'bkgt_inventory_item') {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        switch ($orderby) {
            case 'unique_id':
                $query->set('meta_key', '_bkgt_unique_id');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'manufacturer':
                $query->set('meta_key', '_bkgt_manufacturer_id');
                $query->set('orderby', 'meta_value_num');
                break;
                
            case 'item_type':
                $query->set('meta_key', '_bkgt_item_type_id');
                $query->set('orderby', 'meta_value_num');
                break;
                
            case 'assignment':
                $query->set('meta_key', '_bkgt_assignment_type');
                $query->set('orderby', 'meta_value');
                break;
        }
    }
    
    /**
     * Modify row actions
     */
    public function modify_row_actions($actions, $post) {
        if ($post->post_type !== 'bkgt_inventory_item') {
            return $actions;
        }
        
        // Add quick assignment actions
        $assignment_actions = array();
        
        if (current_user_can('manage_inventory')) {
            $assignment_actions['assign_club'] = sprintf(
                '<a href="%s" class="assign_club">%s</a>',
                wp_nonce_url(add_query_arg(array('action' => 'assign_club', 'post' => $post->ID)), 'assign_item'),
                __('Tilldela klubben', 'bkgt-inventory')
            );
            
            $assignment_actions['assign_team'] = sprintf(
                '<a href="%s" class="assign_team">%s</a>',
                wp_nonce_url(add_query_arg(array('action' => 'assign_team', 'post' => $post->ID)), 'assign_item'),
                __('Tilldela lag', 'bkgt-inventory')
            );
            
            $assignment_actions['unassign'] = sprintf(
                '<a href="%s" class="unassign">%s</a>',
                wp_nonce_url(add_query_arg(array('action' => 'unassign', 'post' => $post->ID)), 'assign_item'),
                __('Ta bort tilldelning', 'bkgt-inventory')
            );
        }
        
        // Insert assignment actions before other actions
        $actions = array_merge(array_slice($actions, 0, 1), $assignment_actions, array_slice($actions, 1));
        
        return $actions;
    }
    
    /**
     * Add filters to admin list
     */
    public function add_filters() {
        global $typenow;
        
        if ($typenow !== 'bkgt_inventory_item') {
            return;
        }
        
        // Manufacturer filter
        $manufacturers = BKGT_Manufacturer::get_all();
        if (!empty($manufacturers)) {
            $current_manufacturer = isset($_GET['manufacturer_filter']) ? $_GET['manufacturer_filter'] : '';
            echo '<select name="manufacturer_filter">';
            echo '<option value="">' . __('Alla tillverkare', 'bkgt-inventory') . '</option>';
            foreach ($manufacturers as $manufacturer) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    $manufacturer->id,
                    selected($current_manufacturer, $manufacturer->id, false),
                    esc_html($manufacturer->name)
                );
            }
            echo '</select>';
        }
        
        // Item type filter
        $item_types = BKGT_Item_Type::get_all();
        if (!empty($item_types)) {
            $current_item_type = isset($_GET['item_type_filter']) ? $_GET['item_type_filter'] : '';
            echo '<select name="item_type_filter">';
            echo '<option value="">' . __('Alla typer', 'bkgt-inventory') . '</option>';
            foreach ($item_types as $item_type) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    $item_type->id,
                    selected($current_item_type, $item_type->id, false),
                    esc_html($item_type->name)
                );
            }
            echo '</select>';
        }
        
        // Assignment filter
        $current_assignment = isset($_GET['assignment_filter']) ? $_GET['assignment_filter'] : '';
        echo '<select name="assignment_filter">';
        echo '<option value="">' . __('Alla tilldelningar', 'bkgt-inventory') . '</option>';
        echo '<option value="none" ' . selected($current_assignment, 'none', false) . '>' . __('Ej tilldelad', 'bkgt-inventory') . '</option>';
        echo '<option value="club" ' . selected($current_assignment, 'club', false) . '>' . __('Klubben', 'bkgt-inventory') . '</option>';
        echo '<option value="team" ' . selected($current_assignment, 'team', false) . '>' . __('Lag', 'bkgt-inventory') . '</option>';
        echo '<option value="individual" ' . selected($current_assignment, 'individual', false) . '>' . __('Individ', 'bkgt-inventory') . '</option>';
        echo '</select>';
    }
    
    /**
     * Filter posts based on custom filters
     */
    public function filter_posts($query) {
        global $pagenow;
        
        if (!is_admin() || $pagenow !== 'edit.php' || $query->get('post_type') !== 'bkgt_inventory_item' || !$query->is_main_query()) {
            return;
        }
        
        $meta_query = array();
        
        // Manufacturer filter
        if (!empty($_GET['manufacturer_filter'])) {
            $meta_query[] = array(
                'key' => '_bkgt_manufacturer_id',
                'value' => intval($_GET['manufacturer_filter']),
                'compare' => '='
            );
        }
        
        // Item type filter
        if (!empty($_GET['item_type_filter'])) {
            $meta_query[] = array(
                'key' => '_bkgt_item_type_id',
                'value' => intval($_GET['item_type_filter']),
                'compare' => '='
            );
        }
        
        // Assignment filter
        if (!empty($_GET['assignment_filter'])) {
            if ($_GET['assignment_filter'] === 'none') {
                $meta_query[] = array(
                    'key' => '_bkgt_assignment_type',
                    'value' => '',
                    'compare' => '='
                );
            } else {
                $meta_query[] = array(
                    'key' => '_bkgt_assignment_type',
                    'value' => sanitize_text_field($_GET['assignment_filter']),
                    'compare' => '='
                );
            }
        }
        
        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        global $pagenow, $post;
        
        if ($pagenow !== 'post.php' || !isset($post) || $post->post_type !== 'bkgt_inventory_item') {
            return;
        }
        
        $unique_id = get_post_meta($post->ID, '_bkgt_unique_id', true);
        if (empty($unique_id)) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>' . __('Det unika ID:t har inte genererats än. Välj tillverkare och artikeltyp för att generera det.', 'bkgt-inventory') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Handle quick assignment actions
     */
    public function handle_quick_assignments() {
        if (!isset($_GET['action']) || !isset($_GET['post'])) {
            return;
        }
        
        $action = $_GET['action'];
        $post_id = intval($_GET['post']);
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'assign_item')) {
            return;
        }
        
        if (!current_user_can('manage_inventory')) {
            wp_die(__('Du har inte behörighet att utföra denna åtgärd.', 'bkgt-inventory'));
        }
        
        $assignment_type = '';
        $assigned_to = 0;
        
        switch ($action) {
            case 'assign_club':
                $assignment_type = 'club';
                break;
            case 'assign_team':
                $assignment_type = 'team';
                // For team assignment, redirect to edit page for selection
                wp_redirect(add_query_arg(array(
                    'post' => $post_id,
                    'action' => 'edit',
                    'message' => 'select_team'
                ), admin_url('post.php')));
                exit;
            case 'unassign':
                $assignment_type = '';
                break;
            default:
                return;
        }
        
        // Update assignment
        update_post_meta($post_id, '_bkgt_assignment_type', $assignment_type);
        update_post_meta($post_id, '_bkgt_assigned_to', $assigned_to);
        
        // Log the action
        BKGT_History::log_action($post_id, 'assignment_changed', array(
            'new_assignment_type' => $assignment_type,
            'assigned_to' => $assigned_to,
        ));
        
        // Redirect back to list
        wp_redirect(add_query_arg(array(
            'post_type' => 'bkgt_inventory_item',
            'assigned' => 1
        ), admin_url('edit.php')));
        exit;
    }
}
