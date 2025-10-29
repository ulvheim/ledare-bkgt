<?php
/**
 * Admin Interface
 *
 * @package BKGT_Inventory
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class BKGT_Inventory_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_bkgt_inventory_action', array($this, 'handle_ajax_actions'));
        
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_bkgt_inventory_item', array($this, 'save_inventory_item'), 10, 2);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Utrustning', 'bkgt-inventory'),
            __('Utrustning', 'bkgt-inventory'),
            'manage_inventory',
            'bkgt-inventory',
            array($this, 'render_main_page'),
            'dashicons-archive',
            27
        );
        
        add_submenu_page(
            'bkgt-inventory',
            __('Alla artiklar', 'bkgt-inventory'),
            __('Alla artiklar', 'bkgt-inventory'),
            'manage_inventory',
            'edit.php?post_type=bkgt_inventory_item'
        );
        
        add_submenu_page(
            'bkgt-inventory',
            __('Lägg till artikel', 'bkgt-inventory'),
            __('Lägg till artikel', 'bkgt-inventory'),
            'manage_inventory',
            'post-new.php?post_type=bkgt_inventory_item'
        );
        
        add_submenu_page(
            'bkgt-inventory',
            __('Tillverkare', 'bkgt-inventory'),
            __('Tillverkare', 'bkgt-inventory'),
            'manage_options',
            'bkgt-manufacturers',
            array($this, 'render_manufacturers_page')
        );
        
        add_submenu_page(
            'bkgt-inventory',
            __('Artikeltyper', 'bkgt-inventory'),
            __('Artikeltyper', 'bkgt-inventory'),
            'manage_options',
            'bkgt-item-types',
            array($this, 'render_item_types_page')
        );
        
        add_submenu_page(
            'bkgt-inventory',
            __('Historik', 'bkgt-inventory'),
            __('Historik', 'bkgt-inventory'),
            'manage_inventory',
            'bkgt-history',
            array($this, 'render_history_page')
        );
        
        add_submenu_page(
            'bkgt-inventory',
            __('Platser', 'bkgt-inventory'),
            __('Platser', 'bkgt-inventory'),
            'manage_options',
            'bkgt-locations',
            array($this, 'render_locations_page')
        );
        
        add_submenu_page(
            'bkgt-inventory',
            __('Rapporter', 'bkgt-inventory'),
            __('Rapporter', 'bkgt-inventory'),
            'manage_inventory',
            'bkgt-reports',
            array($this, 'render_reports_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        $allowed_hooks = array(
            'toplevel_page_bkgt-inventory',
            'utrustning_page_bkgt-manufacturers',
            'utrustning_page_bkgt-item-types',
            'utrustning_page_bkgt-history',
            'utrustning_page_bkgt-reports',
            'post.php',
            'post-new.php'
        );
        
        if (!in_array($hook, $allowed_hooks)) {
            return;
        }
        
        wp_enqueue_style(
            'bkgt-inventory-admin',
            BKGT_INV_PLUGIN_URL . 'assets/admin.css',
            array(),
            BKGT_INV_VERSION
        );
        
        wp_enqueue_script(
            'bkgt-inventory-admin',
            BKGT_INV_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            BKGT_INV_VERSION,
            true
        );
        
        wp_localize_script('bkgt-inventory-admin', 'bkgtInventory', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkgt-inventory-nonce'),
            'strings' => array(
                'confirmDelete' => __('Är du säker på att du vill radera denna artikel?', 'bkgt-inventory'),
                'confirmBulkDelete' => __('Är du säker på att du vill radera de valda artiklarna?', 'bkgt-inventory'),
            ),
        ));
    }
    
    /**
     * Render main dashboard page
     */
    public function render_main_page() {
        $stats = $this->get_inventory_stats();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Utrustning - Översikt', 'bkgt-inventory'); ?></h1>
            
            <div class="bkgt-dashboard-stats">
                <div class="bkgt-stat-card">
                    <h3><?php esc_html_e('Totalt antal artiklar', 'bkgt-inventory'); ?></h3>
                    <div class="bkgt-stat-number"><?php echo $stats['total_items']; ?></div>
                </div>
                
                <div class="bkgt-stat-card">
                    <h3><?php esc_html_e('Tilldelade till klubben', 'bkgt-inventory'); ?></h3>
                    <div class="bkgt-stat-number"><?php echo $stats['club_items']; ?></div>
                </div>
                
                <div class="bkgt-stat-card">
                    <h3><?php esc_html_e('Tilldelade till lag', 'bkgt-inventory'); ?></h3>
                    <div class="bkgt-stat-number"><?php echo $stats['team_items']; ?></div>
                </div>
                
                <div class="bkgt-stat-card">
                    <h3><?php esc_html_e('Tilldelade till individer', 'bkgt-inventory'); ?></h3>
                    <div class="bkgt-stat-number"><?php echo $stats['individual_items']; ?></div>
                </div>
                
                <div class="bkgt-stat-card warning">
                    <h3><?php esc_html_e('Behöver reparation', 'bkgt-inventory'); ?></h3>
                    <div class="bkgt-stat-number"><?php echo $stats['needs_repair']; ?></div>
                </div>
                
                <div class="bkgt-stat-card error">
                    <h3><?php esc_html_e('Förlustanmälda', 'bkgt-inventory'); ?></h3>
                    <div class="bkgt-stat-number"><?php echo $stats['reported_lost']; ?></div>
                </div>
            </div>
            
            <div class="bkgt-dashboard-grid">
                <div class="bkgt-dashboard-card">
                    <h3><?php esc_html_e('Senaste aktivitet', 'bkgt-inventory'); ?></h3>
                    <?php $this->render_recent_activity(); ?>
                </div>
                
                <div class="bkgt-dashboard-card">
                    <h3><?php esc_html_e('Artiklar per skick', 'bkgt-inventory'); ?></h3>
                    <?php $this->render_condition_breakdown(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render manufacturers page
     */
    public function render_manufacturers_page() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $manufacturer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        switch ($action) {
            case 'edit':
                $this->render_manufacturer_form($manufacturer_id);
                break;
            case 'new':
                $this->render_manufacturer_form();
                break;
            default:
                $this->render_manufacturers_list();
                break;
        }
    }
    
    /**
     * Render item types page
     */
    public function render_item_types_page() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $item_type_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        switch ($action) {
            case 'edit':
                $this->render_item_type_form($item_type_id);
                break;
            case 'new':
                $this->render_item_type_form();
                break;
            default:
                $this->render_item_types_list();
                break;
        }
    }
    
    /**
     * Render history page
     */
    public function render_history_page() {
        $history = BKGT_History::get_recent_history(100);
        $stats = BKGT_History::get_statistics();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Utrustningshistorik', 'bkgt-inventory'); ?></h1>
            
            <div class="bkgt-history-stats">
                <div class="bkgt-stat-card">
                    <h3><?php esc_html_e('Totalt antal åtgärder', 'bkgt-inventory'); ?></h3>
                    <div class="bkgt-stat-number"><?php echo $stats['total_actions']; ?></div>
                </div>
                <div class="bkgt-stat-card">
                    <h3><?php esc_html_e('Åtgärder idag', 'bkgt-inventory'); ?></h3>
                    <div class="bkgt-stat-number"><?php echo $stats['actions_today']; ?></div>
                </div>
                <div class="bkgt-stat-card">
                    <h3><?php esc_html_e('Åtgärder denna vecka', 'bkgt-inventory'); ?></h3>
                    <div class="bkgt-stat-number"><?php echo $stats['actions_this_week']; ?></div>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Datum', 'bkgt-inventory'); ?></th>
                        <th><?php esc_html_e('Artikel', 'bkgt-inventory'); ?></th>
                        <th><?php esc_html_e('Åtgärd', 'bkgt-inventory'); ?></th>
                        <th><?php esc_html_e('Användare', 'bkgt-inventory'); ?></th>
                        <th><?php esc_html_e('Detaljer', 'bkgt-inventory'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $entry): ?>
                    <tr>
                        <td><?php echo wp_date('Y-m-d H:i', strtotime($entry->timestamp)); ?></td>
                        <td>
                            <?php if ($entry->item_title): ?>
                                <a href="<?php echo get_edit_post_link($entry->item_id); ?>">
                                    <?php echo esc_html($entry->item_title); ?>
                                </a>
                            <?php else: ?>
                                <?php esc_html_e('(Raderad artikel)', 'bkgt-inventory'); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(BKGT_History::get_action_description($entry->action, $entry->data)); ?></td>
                        <td><?php echo esc_html($entry->user_name); ?></td>
                        <td><?php echo $this->format_history_details($entry); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Get inventory statistics
     */
    private function get_inventory_stats() {
        $stats = array(
            'total_items' => 0,
            'club_items' => 0,
            'team_items' => 0,
            'individual_items' => 0,
            'needs_repair' => 0,
            'reported_lost' => 0,
        );
        
        // Count total items
        $stats['total_items'] = wp_count_posts('bkgt_inventory_item')->publish;
        
        // Count by assignment type
        global $wpdb;
        $assignment_counts = $wpdb->get_results(
            "SELECT meta_value as assignment_type, COUNT(*) as count 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_bkgt_assignment_type' 
             GROUP BY meta_value"
        );
        
        foreach ($assignment_counts as $count) {
            $stats[$count->assignment_type . '_items'] = $count->count;
        }
        
        // Count by condition
        $condition_counts = $wpdb->get_results(
            "SELECT t.slug, COUNT(*) as count 
             FROM {$wpdb->terms} t 
             JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
             JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id 
             WHERE tt.taxonomy = 'bkgt_condition' 
             GROUP BY t.slug"
        );
        
        foreach ($condition_counts as $count) {
            if ($count->slug === 'behöver-reparation') {
                $stats['needs_repair'] = $count->count;
            } elseif ($count->slug === 'förlustanmäld') {
                $stats['reported_lost'] = $count->count;
            }
        }
        
        return $stats;
    }
    
    /**
     * Render recent activity
     */
    private function render_recent_activity() {
        $history = BKGT_History::get_recent_history(10);
        
        if (empty($history)) {
            echo '<p>' . esc_html__('Ingen aktivitet att visa.', 'bkgt-inventory') . '</p>';
            return;
        }
        
        echo '<ul class="bkgt-activity-list">';
        foreach ($history as $entry) {
            printf(
                '<li><strong>%s</strong> %s <em>%s</em></li>',
                esc_html($entry->user_name),
                esc_html(BKGT_History::get_action_description($entry->action, $entry->data)),
                esc_html($entry->item_title ?: __('(Raderad artikel)', 'bkgt-inventory'))
            );
        }
        echo '</ul>';
    }
    
    /**
     * Render condition breakdown
     */
    private function render_condition_breakdown() {
        global $wpdb;
        
        $conditions = $wpdb->get_results(
            "SELECT t.name, t.slug, COUNT(*) as count 
             FROM {$wpdb->terms} t 
             JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
             JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id 
             WHERE tt.taxonomy = 'bkgt_condition' 
             GROUP BY t.term_id 
             ORDER BY count DESC"
        );
        
        if (empty($conditions)) {
            echo '<p>' . esc_html__('Inga artiklar att visa.', 'bkgt-inventory') . '</p>';
            return;
        }
        
        echo '<ul class="bkgt-condition-list">';
        foreach ($conditions as $condition) {
            printf(
                '<li><span class="condition-name">%s</span> <span class="condition-count">%d</span></li>',
                esc_html($condition->name),
                $condition->count
            );
        }
        echo '</ul>';
    }
    
    /**
     * Format history details
     */
    private function format_history_details($entry) {
        if (empty($entry->data)) {
            return '';
        }
        
        $details = array();
        
        if ($entry->action === 'assignment_changed') {
            if (!empty($entry->data['new_assignment_type'])) {
                $type_names = array(
                    'club' => __('Klubben', 'bkgt-inventory'),
                    'team' => __('Lag', 'bkgt-inventory'),
                    'individual' => __('Individ', 'bkgt-inventory'),
                );
                
                $type_name = isset($type_names[$entry->data['new_assignment_type']]) 
                    ? $type_names[$entry->data['new_assignment_type']] 
                    : $entry->data['new_assignment_type'];
                
                $details[] = sprintf(__('Ny tilldelning: %s', 'bkgt-inventory'), $type_name);
            }
        }
        
        return implode(', ', $details);
    }
    
    /**
     * Handle AJAX actions
     */
    public function handle_ajax_actions() {
        check_ajax_referer('bkgt-inventory-nonce', 'nonce');
        
        $action = isset($_POST['sub_action']) ? $_POST['sub_action'] : '';
        
        switch ($action) {
            case 'delete_manufacturer':
                $this->ajax_delete_manufacturer();
                break;
            case 'delete_item_type':
                $this->ajax_delete_item_type();
                break;
            case 'generate_identifier':
                $this->ajax_generate_identifier();
                break;
            case 'quick_assign':
                $this->ajax_quick_assign();
                break;
        }
        
        wp_die();
    }
    
    /**
     * AJAX: Delete manufacturer
     */
    private function ajax_delete_manufacturer() {
        $manufacturer_id = intval($_POST['id']);
        
        $result = BKGT_Manufacturer::delete($manufacturer_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Tillverkare raderad.', 'bkgt-inventory'));
        }
    }
    
    /**
     * AJAX: Delete item type
     */
    private function ajax_delete_item_type() {
        $item_type_id = intval($_POST['id']);
        
        $result = BKGT_Item_Type::delete($item_type_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Artikeltyp raderad.', 'bkgt-inventory'));
        }
    }
    
    /**
     * AJAX: Generate unique identifier
     */
    private function ajax_generate_identifier() {
        $manufacturer_id = intval($_POST['manufacturer_id']);
        $item_type_id = intval($_POST['item_type_id']);
        
        $manufacturer = BKGT_Manufacturer::get($manufacturer_id);
        $item_type = BKGT_Item_Type::get($item_type_id);
        
        if (!$manufacturer || !$item_type) {
            wp_send_json_error(__('Ogiltig tillverkare eller artikeltyp.', 'bkgt-inventory'));
            return;
        }
        
        $inventory_item = new BKGT_Inventory_Item();
        $identifier = $inventory_item->generate_unique_identifier($manufacturer->code, $item_type->code);
        
        wp_send_json_success(array('identifier' => $identifier));
    }
    
    /**
     * AJAX: Quick assign item
     */
    private function ajax_quick_assign() {
        $post_id = intval($_POST['post_id']);
        $assignment_type = sanitize_text_field($_POST['assignment_type']);
        $assigned_to = isset($_POST['assigned_to']) ? intval($_POST['assigned_to']) : 0;
        
        // Validate assignment
        if (!in_array($assignment_type, array('club', 'team', 'individual', 'unassign'))) {
            wp_send_json_error(__('Ogiltig tilldelningstyp.', 'bkgt-inventory'));
            return;
        }
        
        if ($assignment_type === 'unassign') {
            update_post_meta($post_id, '_bkgt_assignment_type', '');
            update_post_meta($post_id, '_bkgt_assigned_to', '');
        } else {
            update_post_meta($post_id, '_bkgt_assignment_type', $assignment_type);
            update_post_meta($post_id, '_bkgt_assigned_to', $assigned_to);
        }
        
        // Log the action
        BKGT_History::log_action($post_id, 'assignment_changed', array(
            'new_assignment_type' => $assignment_type,
            'assigned_to' => $assigned_to,
        ));
        
        wp_send_json_success(__('Artikel tilldelad.', 'bkgt-inventory'));
    }
    
    /**
     * Render manufacturers list
     */
    private function render_manufacturers_list() {
        $manufacturers = BKGT_Manufacturer::get_all();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Tillverkare', 'bkgt-inventory'); ?></h1>
            
            <div class="bkgt-admin-header">
                <a href="<?php echo add_query_arg('action', 'new'); ?>" class="button button-primary">
                    <?php esc_html_e('Lägg till tillverkare', 'bkgt-inventory'); ?>
                </a>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Namn', 'bkgt-inventory'); ?></th>
                        <th><?php esc_html_e('Kod', 'bkgt-inventory'); ?></th>
                        <th><?php esc_html_e('Kontakt', 'bkgt-inventory'); ?></th>
                        <th><?php esc_html_e('Antal artiklar', 'bkgt-inventory'); ?></th>
                        <th><?php esc_html_e('Åtgärder', 'bkgt-inventory'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($manufacturers)): ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e('Inga tillverkare hittades.', 'bkgt-inventory'); ?></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($manufacturers as $manufacturer): ?>
                        <tr>
                            <td><?php echo esc_html($manufacturer->name); ?></td>
                            <td><?php echo esc_html($manufacturer->code); ?></td>
                            <td><?php echo esc_html($manufacturer->contact_info); ?></td>
                            <td><?php echo intval($manufacturer->item_count); ?></td>
                            <td>
                                <a href="<?php echo add_query_arg(array('action' => 'edit', 'id' => $manufacturer->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Redigera', 'bkgt-inventory'); ?>
                                </a>
                                <button type="button" class="button button-small button-link-delete" 
                                        data-action="delete_manufacturer" 
                                        data-id="<?php echo $manufacturer->id; ?>"
                                        data-name="<?php echo esc_attr($manufacturer->name); ?>">
                                    <?php esc_html_e('Radera', 'bkgt-inventory'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render manufacturer form
     */
    private function render_manufacturer_form($manufacturer_id = 0) {
        $manufacturer = null;
        $is_edit = $manufacturer_id > 0;
        
        if ($is_edit) {
            $manufacturer = BKGT_Manufacturer::get($manufacturer_id);
            if (!$manufacturer) {
                wp_die(__('Tillverkare hittades inte.', 'bkgt-inventory'));
            }
        }
        
        if (isset($_POST['submit'])) {
            $this->handle_manufacturer_form($manufacturer_id);
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? __('Redigera tillverkare', 'bkgt-inventory') : __('Lägg till tillverkare', 'bkgt-inventory'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('bkgt_manufacturer_form'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="manufacturer_name"><?php esc_html_e('Namn', 'bkgt-inventory'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="manufacturer_name" name="name" 
                                   value="<?php echo $is_edit ? esc_attr($manufacturer->name) : ''; ?>" 
                                   class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="manufacturer_code"><?php esc_html_e('Kod', 'bkgt-inventory'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="manufacturer_code" name="code" 
                                   value="<?php echo $is_edit ? esc_attr($manufacturer->code) : ''; ?>" 
                                   class="regular-text" maxlength="4" required>
                            <p class="description"><?php esc_html_e('4 tecken lång kod för unik identifiering.', 'bkgt-inventory'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="manufacturer_contact"><?php esc_html_e('Kontaktinformation', 'bkgt-inventory'); ?></label>
                        </th>
                        <td>
                            <textarea id="manufacturer_contact" name="contact_info" 
                                      rows="3" class="large-text"><?php echo $is_edit ? esc_textarea($manufacturer->contact_info) : ''; ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button button-primary" 
                           value="<?php echo $is_edit ? __('Uppdatera tillverkare', 'bkgt-inventory') : __('Lägg till tillverkare', 'bkgt-inventory'); ?>">
                    <a href="<?php echo remove_query_arg(array('action', 'id')); ?>" class="button">
                        <?php esc_html_e('Avbryt', 'bkgt-inventory'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Handle manufacturer form submission
     */
    private function handle_manufacturer_form($manufacturer_id = 0) {
        check_admin_referer('bkgt_manufacturer_form');
        
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'code' => strtoupper(sanitize_text_field($_POST['code'])),
            'contact_info' => sanitize_textarea_field($_POST['contact_info']),
        );
        
        $is_edit = $manufacturer_id > 0;
        
        if ($is_edit) {
            $result = BKGT_Manufacturer::update($manufacturer_id, $data);
        } else {
            $result = BKGT_Manufacturer::create($data);
        }
        
        if (is_wp_error($result)) {
            add_settings_error('bkgt_manufacturer', 'error', $result->get_error_message(), 'error');
        } else {
            $message = $is_edit ? __('Tillverkare uppdaterad.', 'bkgt-inventory') : __('Tillverkare tillagd.', 'bkgt-inventory');
            add_settings_error('bkgt_manufacturer', 'success', $message, 'success');
        }
        
        settings_errors('bkgt_manufacturer');
    }
    
    /**
     * Render item types list
     */
    private function render_item_types_list() {
        $item_types = BKGT_Item_Type::get_all();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Artikeltyper', 'bkgt-inventory'); ?></h1>
            
            <div class="bkgt-admin-header">
                <a href="<?php echo add_query_arg('action', 'new'); ?>" class="button button-primary">
                    <?php esc_html_e('Lägg till artikeltyp', 'bkgt-inventory'); ?>
                </a>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Namn', 'bkgt-inventory'); ?></th>
                        <th><?php esc_html_e('Kod', 'bkgt-inventory'); ?></th>
                        <th><?php esc_html_e('Beskrivning', 'bkgt-inventory'); ?></th>
                        <th><?php esc_html_e('Antal artiklar', 'bkgt-inventory'); ?></th>
                        <th><?php esc_html_e('Åtgärder', 'bkgt-inventory'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($item_types)): ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e('Inga artikeltyper hittades.', 'bkgt-inventory'); ?></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($item_types as $item_type): ?>
                        <tr>
                            <td><?php echo esc_html($item_type->name); ?></td>
                            <td><?php echo esc_html($item_type->code); ?></td>
                            <td><?php echo esc_html($item_type->description); ?></td>
                            <td><?php echo intval($item_type->item_count); ?></td>
                            <td>
                                <a href="<?php echo add_query_arg(array('action' => 'edit', 'id' => $item_type->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Redigera', 'bkgt-inventory'); ?>
                                </a>
                                <button type="button" class="button button-small button-link-delete" 
                                        data-action="delete_item_type" 
                                        data-id="<?php echo $item_type->id; ?>"
                                        data-name="<?php echo esc_attr($item_type->name); ?>">
                                    <?php esc_html_e('Radera', 'bkgt-inventory'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render item type form
     */
    private function render_item_type_form($item_type_id = 0) {
        $item_type = null;
        $is_edit = $item_type_id > 0;
        
        if ($is_edit) {
            $item_type = BKGT_Item_Type::get($item_type_id);
            if (!$item_type) {
                wp_die(__('Artikeltyp hittades inte.', 'bkgt-inventory'));
            }
        }
        
        if (isset($_POST['submit'])) {
            $this->handle_item_type_form($item_type_id);
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? __('Redigera artikeltyp', 'bkgt-inventory') : __('Lägg till artikeltyp', 'bkgt-inventory'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('bkgt_item_type_form'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="item_type_name"><?php esc_html_e('Namn', 'bkgt-inventory'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="item_type_name" name="name" 
                                   value="<?php echo $is_edit ? esc_attr($item_type->name) : ''; ?>" 
                                   class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="item_type_code"><?php esc_html_e('Kod', 'bkgt-inventory'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="item_type_code" name="code" 
                                   value="<?php echo $is_edit ? esc_attr($item_type->code) : ''; ?>" 
                                   class="regular-text" maxlength="4" required>
                            <p class="description"><?php esc_html_e('4 tecken lång kod för unik identifiering.', 'bkgt-inventory'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="item_type_description"><?php esc_html_e('Beskrivning', 'bkgt-inventory'); ?></label>
                        </th>
                        <td>
                            <textarea id="item_type_description" name="description" 
                                      rows="3" class="large-text"><?php echo $is_edit ? esc_textarea($item_type->description) : ''; ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button button-primary" 
                           value="<?php echo $is_edit ? __('Uppdatera artikeltyp', 'bkgt-inventory') : __('Lägg till artikeltyp', 'bkgt-inventory'); ?>">
                    <a href="<?php echo remove_query_arg(array('action', 'id')); ?>" class="button">
                        <?php esc_html_e('Avbryt', 'bkgt-inventory'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Handle item type form submission
     */
    private function handle_item_type_form($item_type_id = 0) {
        check_admin_referer('bkgt_item_type_form');
        
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'code' => strtoupper(sanitize_text_field($_POST['code'])),
            'description' => sanitize_textarea_field($_POST['description']),
        );
        
        $is_edit = $item_type_id > 0;
        
        if ($is_edit) {
            $result = BKGT_Item_Type::update($item_type_id, $data);
        } else {
            $result = BKGT_Item_Type::create($data);
        }
        
        if (is_wp_error($result)) {
            add_settings_error('bkgt_item_type', 'error', $result->get_error_message(), 'error');
        } else {
            $message = $is_edit ? __('Artikeltyp uppdaterad.', 'bkgt-inventory') : __('Artikeltyp tillagd.', 'bkgt-inventory');
            add_settings_error('bkgt_item_type', 'success', $message, 'success');
        }
        
        settings_errors('bkgt_item_type');
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'bkgt_inventory_details',
            __('Utrustningsdetaljer', 'bkgt-inventory'),
            array($this, 'render_inventory_details_meta_box'),
            'bkgt_inventory_item',
            'normal',
            'high'
        );
        
        add_meta_box(
            'bkgt_inventory_assignment',
            __('Tilldelning', 'bkgt-inventory'),
            array($this, 'render_assignment_meta_box'),
            'bkgt_inventory_item',
            'normal',
            'high'
        );
        
        add_meta_box(
            'bkgt_inventory_condition',
            __('Skick', 'bkgt-inventory'),
            array($this, 'render_condition_meta_box'),
            'bkgt_inventory_item',
            'side',
            'default'
        );
        
        add_meta_box(
            'bkgt_inventory_history',
            __('Historik', 'bkgt-inventory'),
            array($this, 'render_history_meta_box'),
            'bkgt_inventory_item',
            'side',
            'default'
        );
    }
    
    /**
     * Render inventory details meta box
     */
    public function render_inventory_details_meta_box($post) {
        wp_nonce_field('bkgt_inventory_meta', 'bkgt_inventory_meta_nonce');
        
        $manufacturer_id = get_post_meta($post->ID, '_bkgt_manufacturer_id', true);
        $item_type_id = get_post_meta($post->ID, '_bkgt_item_type_id', true);
        $serial_number = get_post_meta($post->ID, '_bkgt_serial_number', true);
        $purchase_date = get_post_meta($post->ID, '_bkgt_purchase_date', true);
        $purchase_price = get_post_meta($post->ID, '_bkgt_purchase_price', true);
        $warranty_expiry = get_post_meta($post->ID, '_bkgt_warranty_expiry', true);
        $notes = get_post_meta($post->ID, '_bkgt_notes', true);
        
        // Get conditional field values
        $size = get_post_meta($post->ID, '_bkgt_size', true);
        $color = get_post_meta($post->ID, '_bkgt_color', true);
        $material = get_post_meta($post->ID, '_bkgt_material', true);
        $battery_type = get_post_meta($post->ID, '_bkgt_battery_type', true);
        $voltage = get_post_meta($post->ID, '_bkgt_voltage', true);
        $weight = get_post_meta($post->ID, '_bkgt_weight', true);
        $dimensions = get_post_meta($post->ID, '_bkgt_dimensions', true);
        
        $manufacturers = BKGT_Manufacturer::get_all();
        $item_types = BKGT_Item_Type::get_all();
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="bkgt_manufacturer_id"><?php esc_html_e('Tillverkare', 'bkgt-inventory'); ?> *</label>
                </th>
                <td>
                    <select id="bkgt_manufacturer_id" name="bkgt_manufacturer_id" required>
                        <option value=""><?php esc_html_e('Välj tillverkare', 'bkgt-inventory'); ?></option>
                        <?php foreach ($manufacturers as $manufacturer): ?>
                        <option value="<?php echo $manufacturer->id; ?>" <?php selected($manufacturer_id, $manufacturer->id); ?>>
                            <?php echo esc_html($manufacturer->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="bkgt_item_type_id"><?php esc_html_e('Artikeltyp', 'bkgt-inventory'); ?> *</label>
                </th>
                <td>
                    <select id="bkgt_item_type_id" name="bkgt_item_type_id" required>
                        <option value=""><?php esc_html_e('Välj artikeltyp', 'bkgt-inventory'); ?></option>
                        <?php foreach ($item_types as $item_type): ?>
                        <option value="<?php echo $item_type->id; ?>" <?php selected($item_type_id, $item_type->id); ?> data-type-id="<?php echo esc_attr($item_type->item_type_id); ?>">
                            <?php echo esc_html($item_type->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="bkgt_serial_number"><?php esc_html_e('Serienummer', 'bkgt-inventory'); ?></label>
                </th>
                <td>
                    <input type="text" id="bkgt_serial_number" name="bkgt_serial_number" 
                           value="<?php echo esc_attr($serial_number); ?>" class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="bkgt_purchase_date"><?php esc_html_e('Inköpsdatum', 'bkgt-inventory'); ?></label>
                </th>
                <td>
                    <input type="date" id="bkgt_purchase_date" name="bkgt_purchase_date" 
                           value="<?php echo esc_attr($purchase_date); ?>">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="bkgt_purchase_price"><?php esc_html_e('Inköpspris (SEK)', 'bkgt-inventory'); ?></label>
                </th>
                <td>
                    <input type="number" id="bkgt_purchase_price" name="bkgt_purchase_price" 
                           value="<?php echo esc_attr($purchase_price); ?>" step="0.01" min="0">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="bkgt_warranty_expiry"><?php esc_html_e('Garanti utgångsdatum', 'bkgt-inventory'); ?></label>
                </th>
                <td>
                    <input type="date" id="bkgt_warranty_expiry" name="bkgt_warranty_expiry" 
                           value="<?php echo esc_attr($warranty_expiry); ?>">
                </td>
            </tr>
            
            <!-- Conditional Fields based on Item Type -->
            <tr class="bkgt-conditional-field bkgt-size-field" style="display: none;">
                <th scope="row">
                    <label for="bkgt_size"><?php esc_html_e('Storlek', 'bkgt-inventory'); ?></label>
                </th>
                <td>
                    <select id="bkgt_size" name="bkgt_size">
                        <option value=""><?php esc_html_e('Välj storlek', 'bkgt-inventory'); ?></option>
                        <option value="XS" <?php selected($size, 'XS'); ?>>XS</option>
                        <option value="S" <?php selected($size, 'S'); ?>>S</option>
                        <option value="M" <?php selected($size, 'M'); ?>>M</option>
                        <option value="L" <?php selected($size, 'L'); ?>>L</option>
                        <option value="XL" <?php selected($size, 'XL'); ?>>XL</option>
                        <option value="XXL" <?php selected($size, 'XXL'); ?>>XXL</option>
                    </select>
                </td>
            </tr>
            
            <tr class="bkgt-conditional-field bkgt-color-field" style="display: none;">
                <th scope="row">
                    <label for="bkgt_color"><?php esc_html_e('Färg', 'bkgt-inventory'); ?></label>
                </th>
                <td>
                    <input type="text" id="bkgt_color" name="bkgt_color" 
                           value="<?php echo esc_attr($color); ?>" class="regular-text" placeholder="<?php esc_attr_e('t.ex. Svart, Vit, Röd', 'bkgt-inventory'); ?>">
                </td>
            </tr>
            
            <tr class="bkgt-conditional-field bkgt-material-field" style="display: none;">
                <th scope="row">
                    <label for="bkgt_material"><?php esc_html_e('Material', 'bkgt-inventory'); ?></label>
                </th>
                <td>
                    <select id="bkgt_material" name="bkgt_material">
                        <option value=""><?php esc_html_e('Välj material', 'bkgt-inventory'); ?></option>
                        <option value="plastic" <?php selected($material, 'plastic'); ?>><?php _e('Plast', 'bkgt-inventory'); ?></option>
                        <option value="metal" <?php selected($material, 'metal'); ?>><?php _e('Metall', 'bkgt-inventory'); ?></option>
                        <option value="composite" <?php selected($material, 'composite'); ?>><?php _e('Komposit', 'bkgt-inventory'); ?></option>
                        <option value="fabric" <?php selected($material, 'fabric'); ?>><?php _e('Tyg', 'bkgt-inventory'); ?></option>
                        <option value="leather" <?php selected($material, 'leather'); ?>><?php _e('Läder', 'bkgt-inventory'); ?></option>
                        <option value="rubber" <?php selected($material, 'rubber'); ?>><?php _e('Gummi', 'bkgt-inventory'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr class="bkgt-conditional-field bkgt-battery-field" style="display: none;">
                <th scope="row">
                    <label for="bkgt_battery_type"><?php esc_html_e('Batterityp', 'bkgt-inventory'); ?></label>
                </th>
                <td>
                    <select id="bkgt_battery_type" name="bkgt_battery_type">
                        <option value=""><?php esc_html_e('Välj batterityp', 'bkgt-inventory'); ?></option>
                        <option value="alkaline" <?php selected($battery_type, 'alkaline'); ?>><?php _e('Alkalisk', 'bkgt-inventory'); ?></option>
                        <option value="lithium" <?php selected($battery_type, 'lithium'); ?>><?php _e('Litium', 'bkgt-inventory'); ?></option>
                        <option value="rechargeable" <?php selected($battery_type, 'rechargeable'); ?>><?php _e('Uppladdningsbar', 'bkgt-inventory'); ?></option>
                        <option value="none" <?php selected($battery_type, 'none'); ?>><?php _e('Ingen batteri', 'bkgt-inventory'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr class="bkgt-conditional-field bkgt-voltage-field" style="display: none;">
                <th scope="row">
                    <label for="bkgt_voltage"><?php esc_html_e('Spänning (V)', 'bkgt-inventory'); ?></label>
                </th>
                <td>
                    <input type="number" id="bkgt_voltage" name="bkgt_voltage" 
                           value="<?php echo esc_attr($voltage); ?>" step="0.1" min="0" placeholder="t.ex. 3.7">
                </td>
            </tr>
            
            <tr class="bkgt-conditional-field bkgt-weight-field" style="display: none;">
                <th scope="row">
                    <label for="bkgt_weight"><?php esc_html_e('Vikt (kg)', 'bkgt-inventory'); ?></label>
                </th>
                <td>
                    <input type="number" id="bkgt_weight" name="bkgt_weight" 
                           value="<?php echo esc_attr($weight); ?>" step="0.01" min="0" placeholder="t.ex. 0.5">
                </td>
            </tr>
            
            <tr class="bkgt-conditional-field bkgt-dimensions-field" style="display: none;">
                <th scope="row">
                    <label for="bkgt_dimensions"><?php esc_html_e('Dimensioner (LxBxH cm)', 'bkgt-inventory'); ?></label>
                </th>
                <td>
                    <input type="text" id="bkgt_dimensions" name="bkgt_dimensions" 
                           value="<?php echo esc_attr($dimensions); ?>" class="regular-text" placeholder="t.ex. 30x20x10">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="bkgt_notes"><?php esc_html_e('Anteckningar', 'bkgt-inventory'); ?></label>
                </th>
                <td>
                    <textarea id="bkgt_notes" name="bkgt_notes" rows="3" class="large-text"><?php echo esc_textarea($notes); ?></textarea>
                </td>
            </tr>
        </table>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function toggleConditionalFields() {
                var selectedOption = $('#bkgt_item_type_id option:selected');
                var typeId = selectedOption.data('type-id');
                
                // Hide all conditional fields first
                $('.bkgt-conditional-field').hide();
                
                // Show fields based on item type
                if (typeId) {
                    switch(typeId.toLowerCase()) {
                        case 'helm': // Helmet
                        case 'hjlm':
                            $('.bkgt-size-field, .bkgt-color-field, .bkgt-material-field, .bkgt-weight-field').show();
                            break;
                        case 'axsk': // Shoulder pads
                        case 'bensk': // Knee pads
                        case 'armsk': // Elbow pads
                        case 'tröja': // Jersey
                        case 'byxor': // Pants
                            $('.bkgt-size-field, .bkgt-color-field, .bkgt-material-field').show();
                            break;
                        case 'boll': // Ball
                        case 'kon': // Cone
                            $('.bkgt-color-field, .bkgt-material-field, .bkgt-weight-field, .bkgt-dimensions-field').show();
                            break;
                        case 'elek': // Electronics
                        case 'tidt': // Timer
                        case 'ljus': // Light
                            $('.bkgt-battery-field, .bkgt-voltage-field, .bkgt-weight-field, .bkgt-dimensions-field').show();
                            break;
                        case 'verk': // Tools
                        case 'repd': // Repair parts
                            $('.bkgt-material-field, .bkgt-weight-field, .bkgt-dimensions-field').show();
                            break;
                        default:
                            // Show common fields for other types
                            $('.bkgt-color-field, .bkgt-material-field').show();
                            break;
                    }
                }
            }
            
            // Initial check
            toggleConditionalFields();
            
            // Listen for changes
            $('#bkgt_item_type_id').on('change', toggleConditionalFields);
            
            // Dynamic validation
            $('#bkgt_voltage').on('input', function() {
                var voltage = parseFloat($(this).val());
                if (voltage > 50) {
                    alert('<?php _e('Varning: Spänning över 50V kan vara farligt!', 'bkgt-inventory'); ?>');
                }
            });
            
            $('#bkgt_weight').on('input', function() {
                var weight = parseFloat($(this).val());
                if (weight > 10) {
                    $(this).addClass('warning-field');
                } else {
                    $(this).removeClass('warning-field');
                }
            });
        });
        </script>
        
        <style>
        .warning-field {
            border-color: #ff6b35 !important;
            background-color: #fff3cd !important;
        }
        .bkgt-conditional-field {
            background-color: #f8f9fa;
            border-left: 3px solid #007cba;
        }
        .bkgt-conditional-field th {
            font-weight: normal;
            color: #666;
        }
        </style>
        <?php
    }
    
    /**
     * Render assignment meta box
     */
    public function render_assignment_meta_box($post) {
        $assignment_type = get_post_meta($post->ID, '_bkgt_assignment_type', true);
        $assigned_to = get_post_meta($post->ID, '_bkgt_assigned_to', true);
        
        // Get teams from user management plugin
        $teams = array();
        if (function_exists('bkgt_get_teams')) {
            $teams = bkgt_get_teams();
        }
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="bkgt_assignment_type"><?php esc_html_e('Tilldelningstyp', 'bkgt-inventory'); ?></label>
                </th>
                <td>
                    <select id="bkgt_assignment_type" name="bkgt_assignment_type">
                        <option value=""><?php esc_html_e('Ej tilldelad', 'bkgt-inventory'); ?></option>
                        <option value="club" <?php selected($assignment_type, 'club'); ?>>
                            <?php esc_html_e('Klubben', 'bkgt-inventory'); ?>
                        </option>
                        <option value="team" <?php selected($assignment_type, 'team'); ?>>
                            <?php esc_html_e('Lag', 'bkgt-inventory'); ?>
                        </option>
                        <option value="individual" <?php selected($assignment_type, 'individual'); ?>>
                            <?php esc_html_e('Individ', 'bkgt-inventory'); ?>
                        </option>
                    </select>
                </td>
            </tr>
            
            <tr id="bkgt_team_assignment_row" style="display: <?php echo $assignment_type === 'team' ? 'table-row' : 'none'; ?>;">
                <th scope="row">
                    <label for="bkgt_assigned_team"><?php esc_html_e('Tilldelat lag', 'bkgt-inventory'); ?></label>
                </th>
                <td>
                    <select id="bkgt_assigned_team" name="bkgt_assigned_to">
                        <option value=""><?php esc_html_e('Välj lag', 'bkgt-inventory'); ?></option>
                        <?php foreach ($teams as $team): ?>
                        <option value="<?php echo $team->id; ?>" <?php selected($assigned_to, $team->id); ?>>
                            <?php echo esc_html($team->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            
            <tr id="bkgt_individual_assignment_row" style="display: <?php echo $assignment_type === 'individual' ? 'table-row' : 'none'; ?>;">
                <th scope="row">
                    <label for="bkgt_assigned_user"><?php esc_html_e('Tilldelad användare', 'bkgt-inventory'); ?></label>
                </th>
                <td>
                    <?php wp_dropdown_users(array(
                        'name' => 'bkgt_assigned_to',
                        'selected' => $assigned_to,
                        'show_option_none' => __('Välj användare', 'bkgt-inventory'),
                        'role__in' => array('styrelsemedlem', 'tränare', 'lagledare'),
                    )); ?>
                </td>
            </tr>
        </table>
        
        <script>
        jQuery(document).ready(function($) {
            $('#bkgt_assignment_type').change(function() {
                var type = $(this).val();
                $('#bkgt_team_assignment_row').hide();
                $('#bkgt_individual_assignment_row').hide();
                
                if (type === 'team') {
                    $('#bkgt_team_assignment_row').show();
                } else if (type === 'individual') {
                    $('#bkgt_individual_assignment_row').show();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render condition meta box
     */
    public function render_condition_meta_box($post) {
        $conditions = get_terms(array(
            'taxonomy' => 'bkgt_condition',
            'hide_empty' => false,
        ));
        
        $current_condition = wp_get_post_terms($post->ID, 'bkgt_condition', array('fields' => 'ids'));
        $current_condition = !empty($current_condition) ? $current_condition[0] : '';
        
        ?>
        <p>
            <select name="bkgt_condition" class="widefat">
                <option value=""><?php esc_html_e('Välj skick', 'bkgt-inventory'); ?></option>
                <?php foreach ($conditions as $condition): ?>
                <option value="<?php echo $condition->term_id; ?>" <?php selected($current_condition, $condition->term_id); ?>>
                    <?php echo esc_html($condition->name); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <p>
            <a href="<?php echo admin_url('edit-tags.php?taxonomy=bkgt_condition&post_type=bkgt_inventory_item'); ?>" target="_blank">
                <?php esc_html_e('Hantera skickstyper', 'bkgt-inventory'); ?>
            </a>
        </p>
        <?php
    }
    
    /**
     * Render history meta box
     */
    public function render_history_meta_box($post) {
        $history = BKGT_History::get_item_history($post->ID, 5);
        
        if (empty($history)) {
            echo '<p>' . esc_html__('Ingen historik tillgänglig.', 'bkgt-inventory') . '</p>';
            return;
        }
        
        echo '<ul class="bkgt-history-list">';
        foreach ($history as $entry) {
            printf(
                '<li><small>%s: %s</small></li>',
                wp_date('Y-m-d H:i', strtotime($entry->timestamp)),
                esc_html(BKGT_History::get_action_description($entry->action, $entry->data))
            );
        }
        echo '</ul>';
        
        printf(
            '<p><a href="%s" target="_blank">%s</a></p>',
            add_query_arg('item_id', $post->ID, admin_url('admin.php?page=bkgt-history')),
            esc_html__('Visa fullständig historik', 'bkgt-inventory')
        );
    }
    
    /**
     * Save inventory item
     */
    public function save_inventory_item($post_id, $post) {
        if (!isset($_POST['bkgt_inventory_meta_nonce']) || 
            !wp_verify_nonce($_POST['bkgt_inventory_meta_nonce'], 'bkgt_inventory_meta')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if ($post->post_type !== 'bkgt_inventory_item') {
            return;
        }
        
        // Save meta fields
        $meta_fields = array(
            'bkgt_manufacturer_id',
            'bkgt_item_type_id', 
            'bkgt_serial_number',
            'bkgt_purchase_date',
            'bkgt_purchase_price',
            'bkgt_warranty_expiry',
            'bkgt_notes',
            'bkgt_assignment_type',
            'bkgt_assigned_to',
            // Conditional fields
            'bkgt_size',
            'bkgt_color',
            'bkgt_material',
            'bkgt_battery_type',
            'bkgt_voltage',
            'bkgt_weight',
            'bkgt_dimensions',
        );
        
        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Handle condition taxonomy
        if (isset($_POST['bkgt_condition'])) {
            wp_set_post_terms($post_id, array(intval($_POST['bkgt_condition'])), 'bkgt_condition');
        }
        
        // Generate unique identifier if not set
        $unique_id = get_post_meta($post_id, '_bkgt_unique_id', true);
        if (empty($unique_id) && !empty($_POST['bkgt_manufacturer_id']) && !empty($_POST['bkgt_item_type_id'])) {
            $manufacturer = BKGT_Manufacturer::get($_POST['bkgt_manufacturer_id']);
            $item_type = BKGT_Item_Type::get($_POST['bkgt_item_type_id']);
            
            if ($manufacturer && $item_type) {
                $inventory_item = new BKGT_Inventory_Item($post_id);
                $unique_id = $inventory_item->generate_unique_identifier($manufacturer->code, $item_type->code);
                update_post_meta($post_id, '_bkgt_unique_id', $unique_id);
                
                // Update post title if it's "Auto Draft"
                if ($post->post_title === 'Auto Draft' || empty($post->post_title)) {
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_title' => $unique_id,
                    ));
                }
            }
        }
        
        // Log changes to history
        BKGT_History::log_action($post_id, 'item_updated', array(
            'manufacturer_id' => $_POST['bkgt_manufacturer_id'] ?? '',
            'item_type_id' => $_POST['bkgt_item_type_id'] ?? '',
            'assignment_type' => $_POST['bkgt_assignment_type'] ?? '',
            'assigned_to' => $_POST['bkgt_assigned_to'] ?? '',
        ));
    }
    
    /**
     * Render locations page
     */
    public function render_locations_page() {
        // Handle form submissions
        if (isset($_POST['action'])) {
            $this->handle_location_actions();
        }
        
        // Get locations
        $locations = BKGT_Location::get_all_locations();
        
        // Include template
        include BKGT_INV_PLUGIN_DIR . 'templates/locations-page.php';
    }
    
    /**
     * Handle location actions
     */
    private function handle_location_actions() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'bkgt_location_action')) {
            wp_die(__('Säkerhetstoken misslyckades.', 'bkgt-inventory'));
        }
        
        $action = sanitize_text_field($_POST['action']);
        $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
        
        switch ($action) {
            case 'create_location':
                $result = $this->create_location_from_post();
                if (is_wp_error($result)) {
                    add_settings_error('bkgt_locations', 'create_error', $result->get_error_message());
                } else {
                    add_settings_error('bkgt_locations', 'create_success', __('Plats skapad framgångsrikt.', 'bkgt-inventory'), 'updated');
                }
                break;
                
            case 'update_location':
                $result = $this->update_location_from_post($location_id);
                if (is_wp_error($result)) {
                    add_settings_error('bkgt_locations', 'update_error', $result->get_error_message());
                } else {
                    add_settings_error('bkgt_locations', 'update_success', __('Plats uppdaterad framgångsrikt.', 'bkgt-inventory'), 'updated');
                }
                break;
                
            case 'delete_location':
                $result = BKGT_Location::delete_location($location_id);
                if (is_wp_error($result)) {
                    add_settings_error('bkgt_locations', 'delete_error', $result->get_error_message());
                } else {
                    add_settings_error('bkgt_locations', 'delete_success', __('Plats borttagen framgångsrikt.', 'bkgt-inventory'), 'updated');
                }
                break;
        }
    }
    
    /**
     * Create location from POST data
     */
    private function create_location_from_post() {
        $data = array(
            'name' => sanitize_text_field($_POST['location_name'] ?? ''),
            'slug' => sanitize_title($_POST['location_slug'] ?? ''),
            'parent_id' => !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null,
            'location_type' => sanitize_text_field($_POST['location_type'] ?? BKGT_Location::TYPE_STORAGE),
            'address' => sanitize_textarea_field($_POST['address'] ?? ''),
            'contact_person' => sanitize_text_field($_POST['contact_person'] ?? ''),
            'contact_phone' => sanitize_text_field($_POST['contact_phone'] ?? ''),
            'contact_email' => sanitize_email($_POST['contact_email'] ?? ''),
            'capacity' => !empty($_POST['capacity']) ? intval($_POST['capacity']) : null,
            'access_restrictions' => sanitize_textarea_field($_POST['access_restrictions'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );
        
        return BKGT_Location::create_location($data);
    }
    
    /**
     * Update location from POST data
     */
    private function update_location_from_post($location_id) {
        $data = array(
            'name' => sanitize_text_field($_POST['location_name'] ?? ''),
            'slug' => sanitize_title($_POST['location_slug'] ?? ''),
            'parent_id' => !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null,
            'location_type' => sanitize_text_field($_POST['location_type'] ?? BKGT_Location::TYPE_STORAGE),
            'address' => sanitize_textarea_field($_POST['address'] ?? ''),
            'contact_person' => sanitize_text_field($_POST['contact_person'] ?? ''),
            'contact_phone' => sanitize_text_field($_POST['contact_phone'] ?? ''),
            'contact_email' => sanitize_email($_POST['contact_email'] ?? ''),
            'capacity' => !empty($_POST['capacity']) ? intval($_POST['capacity']) : null,
            'access_restrictions' => sanitize_textarea_field($_POST['access_restrictions'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );
        
        return BKGT_Location::update_location($location_id, $data);
    }
    
    /**
     * Render location options for select dropdown
     */
    private function render_location_options($locations, $selected = '', $parent_id = null, $level = 0) {
        $indent = str_repeat('—', $level);
        
        foreach ($locations as $location) {
            if ($location['parent_id'] == $parent_id) {
                $option_value = $location['id'];
                $option_text = $indent . ' ' . $location['name'];
                $is_selected = ($selected == $option_value) ? 'selected' : '';
                
                echo '<option value="' . esc_attr($option_value) . '" ' . $is_selected . '>' . esc_html($option_text) . '</option>';
                
                // Render children
                if (isset($location['children']) && !empty($location['children'])) {
                    $this->render_location_options($location['children'], $selected, $location['id'], $level + 1);
                }
            }
        }
    }
    
    /**
     * Render locations hierarchy
     */
    private function render_locations_hierarchy($locations, $parent_id = null, $level = 0) {
        foreach ($locations as $location) {
            if ($location['parent_id'] == $parent_id) {
                $this->render_location_item($location, $level);
                
                // Render children
                if (isset($location['children']) && !empty($location['children'])) {
                    echo '<div class="bkgt-location-children">';
                    $this->render_locations_hierarchy($location['children'], $location['id'], $level + 1);
                    echo '</div>';
                }
            }
        }
    }
    
    /**
     * Render single location item
     */
    private function render_location_item($location, $level = 0) {
        $location_types = BKGT_Location::get_location_types();
        $item_count = BKGT_Location::get_location_item_count($location['id']);
        $stats = BKGT_Location::get_location_stats($location['id']);
        
        ?>
        <div class="bkgt-location-item" data-location-id="<?php echo esc_attr($location['id']); ?>">
            <div class="bkgt-location-header">
                <div>
                    <span class="bkgt-location-name"><?php echo esc_html($location['name']); ?></span>
                    <span class="bkgt-location-type"><?php echo esc_html($location_types[$location['location_type']] ?? $location['location_type']); ?></span>
                </div>
                <div class="bkgt-location-actions">
                    <button class="button button-small bkgt-edit-location"><?php _e('Redigera', 'bkgt-inventory'); ?></button>
                    <?php if ($item_count == 0): ?>
                        <form method="post" action="" style="display: inline;">
                            <?php wp_nonce_field('bkgt_location_action'); ?>
                            <input type="hidden" name="action" value="delete_location">
                            <input type="hidden" name="location_id" value="<?php echo esc_attr($location['id']); ?>">
                            <button type="submit" class="button button-small button-link-delete" onclick="return confirm('<?php _e('Är du säker på att du vill ta bort denna plats?', 'bkgt-inventory'); ?>')"><?php _e('Ta bort', 'bkgt-inventory'); ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="bkgt-location-meta">
                <?php if ($location['capacity']): ?>
                    <span><?php printf(__('Kapacitet: %d artiklar', 'bkgt-inventory'), $location['capacity']); ?></span> |
                <?php endif; ?>
                <span><?php printf(_n('%d artikel tilldelad', '%d artiklar tilldelade', $item_count, 'bkgt-inventory'), $item_count); ?></span>
                <?php if ($location['contact_person']): ?>
                    | <span><?php printf(__('Kontakt: %s', 'bkgt-inventory'), esc_html($location['contact_person'])); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="bkgt-edit-location-form" style="display: none;">
                <form method="post" action="">
                    <?php wp_nonce_field('bkgt_location_action'); ?>
                    <input type="hidden" name="action" value="update_location">
                    <input type="hidden" name="location_id" value="<?php echo esc_attr($location['id']); ?>">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label><?php _e('Platsnamn *', 'bkgt-inventory'); ?></label></th>
                            <td><input type="text" name="location_name" value="<?php echo esc_attr($location['name']); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php _e('Slug', 'bkgt-inventory'); ?></label></th>
                            <td><input type="text" name="location_slug" value="<?php echo esc_attr($location['slug']); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php _e('Föräldraplats', 'bkgt-inventory'); ?></label></th>
                            <td>
                                <select name="parent_id" class="regular-text">
                                    <option value=""><?php _e('Ingen förälder (toppnivå)', 'bkgt-inventory'); ?></option>
                                    <?php 
                                    $all_locations = BKGT_Location::get_all_locations();
                                    $this->render_location_options($all_locations, $location['parent_id']); 
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php _e('Platstyp', 'bkgt-inventory'); ?></label></th>
                            <td>
                                <select name="location_type" class="regular-text">
                                    <?php foreach ($location_types as $type => $label): ?>
                                        <option value="<?php echo esc_attr($type); ?>" <?php selected($location['location_type'], $type); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php _e('Adress', 'bkgt-inventory'); ?></label></th>
                            <td><textarea name="address" class="regular-text" rows="3"><?php echo esc_textarea($location['address']); ?></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php _e('Kontaktperson', 'bkgt-inventory'); ?></label></th>
                            <td><input type="text" name="contact_person" value="<?php echo esc_attr($location['contact_person']); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php _e('Telefon', 'bkgt-inventory'); ?></label></th>
                            <td><input type="tel" name="contact_phone" value="<?php echo esc_attr($location['contact_phone']); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php _e('E-post', 'bkgt-inventory'); ?></label></th>
                            <td><input type="email" name="contact_email" value="<?php echo esc_attr($location['contact_email']); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php _e('Kapacitet', 'bkgt-inventory'); ?></label></th>
                            <td><input type="number" name="capacity" value="<?php echo esc_attr($location['capacity']); ?>" class="regular-text" min="0"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php _e('Åtkomstbegränsningar', 'bkgt-inventory'); ?></label></th>
                            <td><textarea name="access_restrictions" class="regular-text" rows="2"><?php echo esc_textarea($location['access_restrictions']); ?></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php _e('Anteckningar', 'bkgt-inventory'); ?></label></th>
                            <td><textarea name="notes" class="regular-text" rows="3"><?php echo esc_textarea($location['notes']); ?></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Aktiv', 'bkgt-inventory'); ?></th>
                            <td><label><input type="checkbox" name="is_active" value="1" <?php checked($location['is_active'], 1); ?>> <?php _e('Plats är aktiv', 'bkgt-inventory'); ?></label></td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit" class="button button-primary" value="<?php _e('Uppdatera plats', 'bkgt-inventory'); ?>">
                        <button type="button" class="button bkgt-cancel-edit"><?php _e('Avbryt', 'bkgt-inventory'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render reports page
     */
    public function render_reports_page() {
        $report_type = isset($_GET['report']) ? sanitize_text_field($_GET['report']) : 'overview';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Utrustning - Rapporter', 'bkgt-inventory'); ?></h1>
            
            <!-- Report Filters -->
            <div class="bkgt-report-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="bkgt-reports">
                    
                    <div class="bkgt-filter-row">
                        <label for="report"><?php _e('Rapporttyp:', 'bkgt-inventory'); ?></label>
                        <select name="report" id="report">
                            <option value="overview" <?php selected($report_type, 'overview'); ?>><?php _e('Översikt', 'bkgt-inventory'); ?></option>
                            <option value="assignments" <?php selected($report_type, 'assignments'); ?>><?php _e('Tilldelningar', 'bkgt-inventory'); ?></option>
                            <option value="utilization" <?php selected($report_type, 'utilization'); ?>><?php _e('Användning', 'bkgt-inventory'); ?></option>
                            <option value="maintenance" <?php selected($report_type, 'maintenance'); ?>><?php _e('Underhåll', 'bkgt-inventory'); ?></option>
                            <option value="locations" <?php selected($report_type, 'locations'); ?>><?php _e('Platser', 'bkgt-inventory'); ?></option>
                            <option value="recommendations" <?php selected($report_type, 'recommendations'); ?>><?php _e('Rekommendationer', 'bkgt-inventory'); ?></option>
                        </select>
                        
                        <label for="date_from"><?php _e('Från datum:', 'bkgt-inventory'); ?></label>
                        <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>">
                        
                        <label for="date_to"><?php _e('Till datum:', 'bkgt-inventory'); ?></label>
                        <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>">
                        
                        <input type="submit" class="button" value="<?php _e('Generera rapport', 'bkgt-inventory'); ?>">
                    </div>
                </form>
            </div>
            
            <!-- Report Content -->
            <div class="bkgt-report-content">
                <?php
                switch ($report_type) {
                    case 'assignments':
                        $this->render_assignments_report($date_from, $date_to);
                        break;
                    case 'utilization':
                        $this->render_utilization_report($date_from, $date_to);
                        break;
                    case 'maintenance':
                        $this->render_maintenance_report($date_from, $date_to);
                        break;
                    case 'locations':
                        $this->render_locations_report($date_from, $date_to);
                        break;
                    case 'recommendations':
                        $this->render_recommendations_report();
                        break;
                    default:
                        $this->render_overview_report($date_from, $date_to);
                        break;
                }
                ?>
            </div>
        </div>
        
        <style>
        .bkgt-report-filters {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .bkgt-filter-row {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .bkgt-filter-row label {
            font-weight: 600;
            margin-right: 5px;
        }
        .bkgt-report-content {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        .bkgt-report-section {
            margin-bottom: 30px;
        }
        .bkgt-report-section h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .bkgt-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .bkgt-stat-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            text-align: center;
        }
        .bkgt-stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007cba;
            display: block;
        }
        .bkgt-stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .bkgt-chart-placeholder {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 4px;
            padding: 40px;
            text-align: center;
            color: #6c757d;
            margin: 20px 0;
        }
        .bkgt-data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .bkgt-data-table th,
        .bkgt-data-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .bkgt-data-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        </style>
        <?php
    }
    
    /**
     * Render overview report
     */
    private function render_overview_report($date_from, $date_to) {
        $stats = $this->get_inventory_stats();
        $recent_assignments = $this->get_recent_assignments(10);
        $overdue_items = $this->get_overdue_assignments();
        
        ?>
        <div class="bkgt-report-section">
            <h3><?php _e('Översikt', 'bkgt-inventory'); ?></h3>
            
            <div class="bkgt-stats-grid">
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $stats['total_items']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Totalt antal artiklar', 'bkgt-inventory'); ?></span>
                </div>
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $stats['assigned_items']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Tilldelade artiklar', 'bkgt-inventory'); ?></span>
                </div>
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $stats['available_items']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Tillgängliga artiklar', 'bkgt-inventory'); ?></span>
                </div>
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $stats['needs_repair']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Behöver reparation', 'bkgt-inventory'); ?></span>
                </div>
            </div>
            
            <div class="bkgt-chart-placeholder">
                <?php _e('📊 Här kommer ett diagram över artikelanvändning över tid att visas', 'bkgt-inventory'); ?>
            </div>
        </div>
        
        <div class="bkgt-report-section">
            <h3><?php _e('Senaste tilldelningar', 'bkgt-inventory'); ?></h3>
            <table class="bkgt-data-table">
                <thead>
                    <tr>
                        <th><?php _e('Artikel', 'bkgt-inventory'); ?></th>
                        <th><?php _e('Tilldelad till', 'bkgt-inventory'); ?></th>
                        <th><?php _e('Datum', 'bkgt-inventory'); ?></th>
                        <th><?php _e('Status', 'bkgt-inventory'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_assignments)): ?>
                        <tr>
                            <td colspan="4"><?php _e('Inga tilldelningar hittades.', 'bkgt-inventory'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_assignments as $assignment): ?>
                            <tr>
                                <td><?php echo esc_html($assignment->item_name); ?></td>
                                <td><?php echo esc_html($assignment->assignee_name); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($assignment->assignment_date))); ?></td>
                                <td><?php echo esc_html($assignment->status); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="bkgt-report-section">
            <h3><?php _e('Försenade returer', 'bkgt-inventory'); ?></h3>
            <table class="bkgt-data-table">
                <thead>
                    <tr>
                        <th><?php _e('Artikel', 'bkgt-inventory'); ?></th>
                        <th><?php _e('Tilldelad till', 'bkgt-inventory'); ?></th>
                        <th><?php _e('Förväntat returdatum', 'bkgt-inventory'); ?></th>
                        <th><?php _e('Dagar försenad', 'bkgt-inventory'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($overdue_items)): ?>
                        <tr>
                            <td colspan="4"><?php _e('Inga försenade returer.', 'bkgt-inventory'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($overdue_items as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item->item_name); ?></td>
                                <td><?php echo esc_html($item->assignee_name); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($item->due_date))); ?></td>
                                <td><?php echo esc_html($item->days_overdue); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render assignments report
     */
    private function render_assignments_report($date_from, $date_to) {
        $assignment_stats = $this->get_assignment_stats($date_from, $date_to);
        $top_assignees = $this->get_top_assignees($date_from, $date_to);
        
        ?>
        <div class="bkgt-report-section">
            <h3><?php _e('Tilldelningsstatistik', 'bkgt-inventory'); ?></h3>
            
            <div class="bkgt-stats-grid">
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $assignment_stats['total_assignments']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Totalt tilldelningar', 'bkgt-inventory'); ?></span>
                </div>
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $assignment_stats['active_assignments']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Aktiva tilldelningar', 'bkgt-inventory'); ?></span>
                </div>
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $assignment_stats['returned_assignments']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Återlämnade', 'bkgt-inventory'); ?></span>
                </div>
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $assignment_stats['avg_assignment_duration']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Snittlängd (dagar)', 'bkgt-inventory'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="bkgt-report-section">
            <h3><?php _e('Mest tilldelade artiklar', 'bkgt-inventory'); ?></h3>
            <table class="bkgt-data-table">
                <thead>
                    <tr>
                        <th><?php _e('Artikel', 'bkgt-inventory'); ?></th>
                        <th><?php _e('Antal tilldelningar', 'bkgt-inventory'); ?></th>
                        <th><?php _e('Senaste tilldelning', 'bkgt-inventory'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_assignees)): ?>
                        <tr>
                            <td colspan="3"><?php _e('Inga tilldelningar hittades.', 'bkgt-inventory'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($top_assignees as $assignee): ?>
                            <tr>
                                <td><?php echo esc_html($assignee->name); ?></td>
                                <td><?php echo esc_html($assignee->assignment_count); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($assignee->last_assignment))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render utilization report
     */
    private function render_utilization_report($date_from, $date_to) {
        $utilization_stats = $this->get_utilization_stats($date_from, $date_to);
        
        ?>
        <div class="bkgt-report-section">
            <h3><?php _e('Användningsstatistik', 'bkgt-inventory'); ?></h3>
            
            <div class="bkgt-stats-grid">
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo round($utilization_stats['utilization_rate'], 1); ?>%</span>
                    <span class="bkgt-stat-label"><?php _e('Användningsgrad', 'bkgt-inventory'); ?></span>
                </div>
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $utilization_stats['most_used_category']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Mest använda kategori', 'bkgt-inventory'); ?></span>
                </div>
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $utilization_stats['avg_usage_per_item']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Snittanvändning per artikel', 'bkgt-inventory'); ?></span>
                </div>
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $utilization_stats['peak_usage_month']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Toppanvändningsmånad', 'bkgt-inventory'); ?></span>
                </div>
            </div>
            
            <div class="bkgt-chart-placeholder">
                <?php _e('📈 Här kommer ett diagram över användning per kategori att visas', 'bkgt-inventory'); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render maintenance report
     */
    private function render_maintenance_report($date_from, $date_to) {
        $maintenance_stats = $this->get_maintenance_stats($date_from, $date_to);
        
        ?>
        <div class="bkgt-report-section">
            <h3><?php _e('Underhållsstatistik', 'bkgt-inventory'); ?></h3>
            
            <div class="bkgt-stats-grid">
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $maintenance_stats['needs_repair']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Behöver reparation', 'bkgt-inventory'); ?></span>
                </div>
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $maintenance_stats['in_repair']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Under reparation', 'bkgt-inventory'); ?></span>
                </div>
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $maintenance_stats['repaired']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Reparerade', 'bkgt-inventory'); ?></span>
                </div>
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $maintenance_stats['avg_repair_time']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Snittreparationstid (dagar)', 'bkgt-inventory'); ?></span>
                </div>
            </div>
            
            <div class="bkgt-chart-placeholder">
                <?php _e('🔧 Här kommer ett diagram över reparationshistorik att visas', 'bkgt-inventory'); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render locations report
     */
    private function render_locations_report($date_from, $date_to) {
        $location_stats = $this->get_location_stats($date_from, $date_to);
        
        ?>
        <div class="bkgt-report-section">
            <h3><?php _e('Platsstatistik', 'bkgt-inventory'); ?></h3>
            
            <div class="bkgt-stats-grid">
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $location_stats['total_locations']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Totalt antal platser', 'bkgt-inventory'); ?></span>
                </div>
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo round($location_stats['avg_utilization'], 1); ?>%</span>
                    <span class="bkgt-stat-label"><?php _e('Snittutnyttjande', 'bkgt-inventory'); ?></span>
                </div>
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $location_stats['most_used_location']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Mest använda plats', 'bkgt-inventory'); ?></span>
                </div>
                <div class="bkgt-stat-box">
                    <span class="bkgt-stat-number"><?php echo $location_stats['underutilized_locations']; ?></span>
                    <span class="bkgt-stat-label"><?php _e('Underutnyttjade platser', 'bkgt-inventory'); ?></span>
                </div>
            </div>
            
            <div class="bkgt-chart-placeholder">
                <?php _e('📍 Här kommer ett diagram över platsutnyttjande att visas', 'bkgt-inventory'); ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render recommendations report
     */
    private function render_recommendations_report() {
        $recommendations = BKGT_Inventory_Analytics::get_quantity_recommendations();
        $suggestions = BKGT_Inventory_Analytics::get_optimization_suggestions();
        
        ?>
        <div class="bkgt-report-section">
            <h3><?php _e('📊 AI-drivna Rekommendationer', 'bkgt-inventory'); ?></h3>
            <p><?php _e('Analysbaserade förslag för lageroptimering baserat på historiska data och användningsmönster.', 'bkgt-inventory'); ?></p>
            
            <?php if (!empty($recommendations)): ?>
            <div class="bkgt-recommendations-grid">
                <?php foreach ($recommendations as $rec): ?>
                <div class="bkgt-recommendation-card <?php echo esc_attr($rec['confidence_level']); ?>">
                    <h4><?php echo esc_html($rec['item_type_name']); ?></h4>
                    
                    <div class="bkgt-rec-stats">
                        <div class="bkgt-stat">
                            <span class="bkgt-current"><?php echo $rec['current_quantity']; ?></span>
                            <span class="bkgt-label"><?php _e('Nuvarande', 'bkgt-inventory'); ?></span>
                        </div>
                        <div class="bkgt-arrow">→</div>
                        <div class="bkgt-stat recommended">
                            <span class="bkgt-recommended"><?php echo $rec['recommended_quantity']; ?></span>
                            <span class="bkgt-label"><?php _e('Rekommenderat', 'bkgt-inventory'); ?></span>
                        </div>
                    </div>
                    
                    <div class="bkgt-confidence-level">
                        <?php
                        $confidence_text = '';
                        switch ($rec['confidence_level']) {
                            case 'high': $confidence_text = __('Hög tillförlitlighet', 'bkgt-inventory'); break;
                            case 'medium': $confidence_text = __('Medelhög tillförlitlighet', 'bkgt-inventory'); break;
                            case 'low': $confidence_text = __('Låg tillförlitlighet', 'bkgt-inventory'); break;
                            default: $confidence_text = __('Mycket låg tillförlitlighet', 'bkgt-inventory'); break;
                        }
                        echo esc_html($confidence_text);
                        ?>
                    </div>
                    
                    <div class="bkgt-reasoning">
                        <strong><?php _e('Motivering:', 'bkgt-inventory'); ?></strong>
                        <p><?php echo esc_html($rec['reasoning']); ?></p>
                    </div>
                    
                    <div class="bkgt-additional-info">
                        <small>
                            <?php _e('Användningsgrad:', 'bkgt-inventory'); ?> <?php echo $rec['utilization_rate']; ?>% | 
                            <?php _e('Snittanvändning:', 'bkgt-inventory'); ?> <?php echo $rec['avg_usage_days']; ?> <?php _e('dagar', 'bkgt-inventory'); ?>
                            <?php if ($rec['seasonal_adjustment'] != 1.0): ?>
                            | <?php _e('Säsongsjustering:', 'bkgt-inventory'); ?> <?php echo round($rec['seasonal_adjustment'], 1); ?>x
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="bkgt-no-data">
                <p><?php _e('Inte tillräckligt med data för att generera rekommendationer. Fortsätt använda systemet för att samla mer användningsdata.', 'bkgt-inventory'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($suggestions)): ?>
        <div class="bkgt-report-section">
            <h3><?php _e('💡 Optimeringförslag', 'bkgt-inventory'); ?></h3>
            
            <?php foreach ($suggestions as $suggestion): ?>
            <div class="bkgt-suggestion-card <?php echo esc_attr($suggestion['type']); ?>">
                <h4><?php echo esc_html($suggestion['title']); ?></h4>
                <p><?php echo esc_html($suggestion['description']); ?></p>
                <div class="bkgt-suggestion-action">
                    <strong><?php _e('Föreslagen åtgärd:', 'bkgt-inventory'); ?></strong>
                    <p><?php echo esc_html($suggestion['action']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <style>
        .bkgt-recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .bkgt-recommendation-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .bkgt-recommendation-card.high { border-left: 4px solid #28a745; }
        .bkgt-recommendation-card.medium { border-left: 4px solid #ffc107; }
        .bkgt-recommendation-card.low { border-left: 4px solid #fd7e14; }
        .bkgt-recommendation-card.very_low { border-left: 4px solid #dc3545; }
        .bkgt-recommendation-card h4 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 18px;
        }
        .bkgt-rec-stats {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
        }
        .bkgt-stat {
            text-align: center;
            flex: 1;
        }
        .bkgt-stat span:first-child {
            display: block;
            font-size: 32px;
            font-weight: bold;
        }
        .bkgt-current { color: #6c757d; }
        .bkgt-recommended { color: #007cba; }
        .bkgt-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .bkgt-arrow {
            font-size: 24px;
            color: #007cba;
            margin: 0 15px;
        }
        .bkgt-confidence-level {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 4px;
            text-align: center;
            font-weight: 600;
            margin: 15px 0;
        }
        .bkgt-reasoning {
            margin: 15px 0;
            font-size: 14px;
        }
        .bkgt-reasoning p {
            margin: 5px 0 0 0;
            color: #666;
        }
        .bkgt-additional-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }
        .bkgt-suggestion-card {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }
        .bkgt-suggestion-card.overstocked { background: #d1ecf1; border-color: #bee5eb; }
        .bkgt-suggestion-card.maintenance { background: #f8d7da; border-color: #f5c6cb; }
        .bkgt-suggestion-card h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        .bkgt-suggestion-card.overstocked h4 { color: #0c5460; }
        .bkgt-suggestion-card.maintenance h4 { color: #721c24; }
        .bkgt-suggestion-action {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ffeaa7;
        }
        .bkgt-suggestion-card.overstocked .bkgt-suggestion-action { border-color: #bee5eb; }
        .bkgt-suggestion-card.maintenance .bkgt-suggestion-action { border-color: #f5c6cb; }
        .bkgt-no-data {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            color: #6c757d;
        }
        </style>
        <?php
    }

    /**
     * Get assignment statistics
     */
    private function get_assignment_stats($date_from, $date_to) {
        global $wpdb;
        
        $stats = array(
            'total_assignments' => 0,
            'active_assignments' => 0,
            'returned_assignments' => 0,
            'avg_assignment_duration' => 0
        );
        
        // Total assignments in date range
        $stats['total_assignments'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_inventory_assignments 
             WHERE assignment_date BETWEEN %s AND %s",
            $date_from, $date_to
        ));
        
        // Active assignments
        $stats['active_assignments'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_inventory_assignments 
             WHERE return_date IS NULL"
        );
        
        // Returned assignments
        $stats['returned_assignments'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_inventory_assignments 
             WHERE return_date BETWEEN %s AND %s",
            $date_from, $date_to
        ));
        
        // Average assignment duration
        $avg_duration = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(DATEDIFF(COALESCE(return_date, CURDATE()), assignment_date)) 
             FROM {$wpdb->prefix}bkgt_inventory_assignments 
             WHERE assignment_date BETWEEN %s AND %s AND return_date IS NOT NULL",
            $date_from, $date_to
        ));
        $stats['avg_assignment_duration'] = round($avg_duration ?: 0, 1);
        
        return $stats;
    }
    
    /**
     * Get top assignees
     */
    private function get_top_assignees($date_from, $date_to) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                COALESCE(u.display_name, a.assignee_name) as name,
                COUNT(*) as assignment_count,
                MAX(a.assignment_date) as last_assignment
             FROM {$wpdb->prefix}bkgt_inventory_assignments a
             LEFT JOIN {$wpdb->users} u ON a.assignee_id = u.ID
             WHERE a.assignment_date BETWEEN %s AND %s
             GROUP BY COALESCE(u.ID, a.assignee_name)
             ORDER BY assignment_count DESC
             LIMIT 10",
            $date_from, $date_to
        ));
    }
    
    /**
     * Get utilization statistics
     */
    private function get_utilization_stats($date_from, $date_to) {
        global $wpdb;
        
        $stats = array(
            'utilization_rate' => 0,
            'most_used_category' => 'N/A',
            'avg_usage_per_item' => 0,
            'peak_usage_month' => 'N/A'
        );
        
        // Utilization rate (assigned items / total items)
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bkgt_inventory_item'");
        $assigned_items = $wpdb->get_var(
            "SELECT COUNT(DISTINCT item_id) FROM {$wpdb->prefix}bkgt_inventory_assignments 
             WHERE return_date IS NULL"
        );
        $stats['utilization_rate'] = $total_items > 0 ? round(($assigned_items / $total_items) * 100, 1) : 0;
        
        // Most used category
        $category = $wpdb->get_row(
            "SELECT t.name, COUNT(a.id) as usage_count
             FROM {$wpdb->prefix}bkgt_inventory_assignments a
             JOIN {$wpdb->posts} p ON a.item_id = p.ID
             JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
             JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
             WHERE tt.taxonomy = 'bkgt_item_type'
             GROUP BY t.term_id
             ORDER BY usage_count DESC
             LIMIT 1"
        );
        $stats['most_used_category'] = $category ? $category->name : 'N/A';
        
        // Average usage per item
        $avg_usage = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(assignment_count) FROM (
                SELECT item_id, COUNT(*) as assignment_count
                FROM {$wpdb->prefix}bkgt_inventory_assignments
                WHERE assignment_date BETWEEN %s AND %s
                GROUP BY item_id
             ) as item_usage",
            $date_from, $date_to
        ));
        $stats['avg_usage_per_item'] = round($avg_usage ?: 0, 1);
        
        return $stats;
    }
    
    /**
     * Get maintenance statistics
     */
    private function get_maintenance_stats($date_from, $date_to) {
        global $wpdb;
        
        $stats = array(
            'needs_repair' => 0,
            'in_repair' => 0,
            'repaired' => 0,
            'avg_repair_time' => 0
        );
        
        // Items needing repair
        $stats['needs_repair'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
             WHERE meta_key = '_bkgt_condition_status' AND meta_value = 'needs_repair'"
        );
        
        // Items in repair (assuming there's a repair status)
        $stats['in_repair'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
             WHERE meta_key = '_bkgt_condition_status' AND meta_value = 'in_repair'"
        );
        
        // Repaired items
        $stats['repaired'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
             WHERE meta_key = '_bkgt_condition_status' AND meta_value = 'repaired'"
        );
        
        return $stats;
    }
    
    /**
     * Get location statistics
     */
    private function get_location_stats($date_from, $date_to) {
        global $wpdb;
        
        $stats = array(
            'total_locations' => 0,
            'avg_utilization' => 0,
            'most_used_location' => 'N/A',
            'underutilized_locations' => 0
        );
        
        // Total locations
        $stats['total_locations'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_inventory_locations WHERE is_active = 1"
        );
        
        // Most used location
        $location = $wpdb->get_row(
            "SELECT l.name, COUNT(a.id) as usage_count
             FROM {$wpdb->prefix}bkgt_inventory_locations l
             LEFT JOIN {$wpdb->prefix}bkgt_inventory_assignments a ON l.id = a.location_id
             WHERE l.is_active = 1
             GROUP BY l.id
             ORDER BY usage_count DESC
             LIMIT 1"
        );
        $stats['most_used_location'] = $location ? $location->name : 'N/A';
        
        return $stats;
    }
    
    /**
     * Get recent assignments
     */
    private function get_recent_assignments($limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                p.post_title as item_name,
                COALESCE(u.display_name, a.assignee_name) as assignee_name,
                a.assignment_date,
                CASE 
                    WHEN a.return_date IS NULL THEN 'Aktiv'
                    ELSE 'Återlämnad'
                END as status
             FROM {$wpdb->prefix}bkgt_inventory_assignments a
             JOIN {$wpdb->posts} p ON a.item_id = p.ID
             LEFT JOIN {$wpdb->users} u ON a.assignee_id = u.ID
             ORDER BY a.assignment_date DESC
             LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Get overdue assignments
     */
    private function get_overdue_assignments() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT 
                p.post_title as item_name,
                COALESCE(u.display_name, a.assignee_name) as assignee_name,
                a.due_date,
                DATEDIFF(CURDATE(), a.due_date) as days_overdue
             FROM {$wpdb->prefix}bkgt_inventory_assignments a
             JOIN {$wpdb->posts} p ON a.item_id = p.ID
             LEFT JOIN {$wpdb->users} u ON a.assignee_id = u.ID
             WHERE a.return_date IS NULL 
             AND a.due_date < CURDATE()
             ORDER BY a.due_date ASC"
        );
    }
}
