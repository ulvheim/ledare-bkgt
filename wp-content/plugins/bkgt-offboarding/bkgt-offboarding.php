<?php
/**
 * Plugin Name: BKGT Offboarding System
 * Plugin URI: https://bkgt.se
 * Description: Personnel transition management system for equipment handover and access control.
 * Version: 1.0.0
 * Author: BKGT Amerikansk Fotboll
 * License: GPL v2 or later
 * Text Domain: bkgt-offboarding
 * Requires Plugins: bkgt-core
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BKGT_OFFBOARDING_VERSION', '1.0.0');
define('BKGT_OFFBOARDING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKGT_OFFBOARDING_PLUGIN_URL', plugin_dir_url(__FILE__));

// Plugin activation hook
register_activation_hook(__FILE__, 'bkgt_offboarding_activate');

function bkgt_offboarding_activate() {
    if (!function_exists('bkgt_log')) {
        die(__('BKGT Core plugin must be activated first.', 'bkgt-offboarding'));
    }
    bkgt_log('info', 'Offboarding plugin activated');
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'bkgt_offboarding_deactivate');

function bkgt_offboarding_deactivate() {
    if (function_exists('bkgt_log')) {
        bkgt_log('info', 'Offboarding plugin deactivated');
    }
}

/**
 * Main Plugin Class
 */
class BKGT_Offboarding_System {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    public function init() {
        // Register custom post types
        add_action('init', array($this, 'register_post_types'));

        // Add shortcodes
        add_shortcode('bkgt_offboarding_dashboard', array($this, 'offboarding_dashboard_shortcode'));

        // AJAX handlers
        add_action('wp_ajax_bkgt_start_offboarding', array($this, 'ajax_start_offboarding'));
        add_action('wp_ajax_bkgt_update_offboarding_task', array($this, 'ajax_update_offboarding_task'));
        add_action('wp_ajax_bkgt_complete_offboarding', array($this, 'ajax_complete_offboarding'));
        add_action('wp_ajax_bkgt_generate_equipment_receipt', array($this, 'ajax_generate_equipment_receipt'));
        add_action('wp_ajax_bkgt_update_equipment_status', array($this, 'ajax_update_equipment_status'));
        add_action('wp_ajax_bkgt_add_notification', array($this, 'ajax_add_notification'));
        add_action('wp_ajax_bkgt_delete_notification', array($this, 'ajax_delete_notification'));
        add_action('wp_ajax_nopriv_bkgt_download_receipt', array($this, 'download_receipt'));
        add_action('wp_ajax_bkgt_download_receipt', array($this, 'download_receipt'));

        // Admin menus
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Shortcodes
        add_shortcode('bkgt_offboarding_dashboard', array($this, 'offboarding_dashboard_shortcode'));

        // Scheduled tasks
        add_action('bkgt_check_offboarding_deadlines', array($this, 'check_offboarding_deadlines'));
        if (!wp_next_scheduled('bkgt_check_offboarding_deadlines')) {
            wp_schedule_event(time(), 'daily', 'bkgt_check_offboarding_deadlines');
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain('bkgt-offboarding', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function register_post_types() {
        // Offboarding Process post type
        register_post_type('bkgt_offboarding', array(
            'labels' => array(
                'name' => __('Offboarding Processes', 'bkgt-offboarding'),
                'singular_name' => __('Offboarding Process', 'bkgt-offboarding'),
                'add_new' => __('Start Offboarding', 'bkgt-offboarding'),
                'add_new_item' => __('Start New Offboarding Process', 'bkgt-offboarding'),
                'edit_item' => __('Edit Offboarding Process', 'bkgt-offboarding'),
                'new_item' => __('New Offboarding Process', 'bkgt-offboarding'),
                'view_item' => __('View Offboarding Process', 'bkgt-offboarding'),
                'search_items' => __('Search Offboarding Processes', 'bkgt-offboarding'),
                'not_found' => __('No offboarding processes found', 'bkgt-offboarding'),
                'not_found_in_trash' => __('No offboarding processes found in trash', 'bkgt-offboarding'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => array('title'),
            'capability_type' => 'offboarding',
            'capabilities' => array(
                'edit_post' => 'manage_options',
                'read_post' => 'manage_options',
                'delete_post' => 'manage_options',
                'edit_posts' => 'manage_options',
                'edit_others_posts' => 'manage_options',
                'publish_posts' => 'manage_options',
                'read_private_posts' => 'manage_options',
            ),
        ));

        // Add meta boxes for offboarding posts
        add_action('add_meta_boxes', array($this, 'add_offboarding_meta_boxes'));
        add_action('save_post', array($this, 'save_offboarding_meta_boxes'));
    }

    public function add_offboarding_meta_boxes() {
        add_meta_box(
            'bkgt_offboarding_details',
            __('Offboarding Details', 'bkgt-offboarding'),
            array($this, 'display_offboarding_details_meta_box'),
            'bkgt_offboarding',
            'normal',
            'high'
        );

        add_meta_box(
            'bkgt_offboarding_tasks',
            __('Task Checklist', 'bkgt-offboarding'),
            array($this, 'display_task_checklist_meta_box'),
            'bkgt_offboarding',
            'normal',
            'high'
        );

        add_meta_box(
            'bkgt_offboarding_equipment',
            __('Equipment & Assets', 'bkgt-offboarding'),
            array($this, 'display_equipment_meta_box'),
            'bkgt_offboarding',
            'normal',
            'high'
        );

        add_meta_box(
            'bkgt_offboarding_notifications',
            __('Notifications & Reminders', 'bkgt-offboarding'),
            array($this, 'display_notifications_meta_box'),
            'bkgt_offboarding',
            'normal',
            'low'
        );
    }

    public function display_offboarding_details_meta_box($post) {
        wp_nonce_field('bkgt_offboarding_meta_box', 'bkgt_offboarding_meta_box_nonce');

        $user_id = get_post_meta($post->ID, '_bkgt_offboarding_user_id', true);
        $end_date = get_post_meta($post->ID, '_bkgt_offboarding_end_date', true);
        $status = get_post_meta($post->ID, '_bkgt_offboarding_status', true);
        $notes = get_post_meta($post->ID, '_bkgt_offboarding_notes', true);
        $started_by = get_post_meta($post->ID, '_bkgt_offboarding_started_by', true);
        $started_date = get_post_meta($post->ID, '_bkgt_offboarding_started_date', true);
        $completed_date = get_post_meta($post->ID, '_bkgt_offboarding_completed_date', true);

        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label><?php _e('Person', 'bkgt-offboarding'); ?></label></th>
                <td>
                    <?php
                    if ($user_id) {
                        $user = get_userdata($user_id);
                        echo esc_html($user ? $user->display_name . ' (' . $user->user_email . ')' : __('User not found', 'bkgt-offboarding'));
                    } else {
                        _e('Not set', 'bkgt-offboarding');
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php _e('End Date', 'bkgt-offboarding'); ?></label></th>
                <td>
                    <input type="date" name="bkgt_offboarding_end_date" value="<?php echo esc_attr($end_date); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php _e('Status', 'bkgt-offboarding'); ?></label></th>
                <td>
                    <select name="bkgt_offboarding_status">
                        <option value="active" <?php selected($status, 'active'); ?>><?php _e('Active', 'bkgt-offboarding'); ?></option>
                        <option value="completed" <?php selected($status, 'completed'); ?>><?php _e('Completed', 'bkgt-offboarding'); ?></option>
                        <option value="cancelled" <?php selected($status, 'cancelled'); ?>><?php _e('Cancelled', 'bkgt-offboarding'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php _e('Notes', 'bkgt-offboarding'); ?></label></th>
                <td>
                    <textarea name="bkgt_offboarding_notes" rows="4" cols="50"><?php echo esc_textarea($notes); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php _e('Started By', 'bkgt-offboarding'); ?></label></th>
                <td>
                    <?php
                    if ($started_by) {
                        $user = get_userdata($started_by);
                        echo esc_html($user ? $user->display_name : __('Unknown', 'bkgt-offboarding'));
                    } else {
                        _e('Not set', 'bkgt-offboarding');
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php _e('Started Date', 'bkgt-offboarding'); ?></label></th>
                <td><?php echo $started_date ? date_i18n(get_option('date_format'), strtotime($started_date)) : __('Not set', 'bkgt-offboarding'); ?></td>
            </tr>
            <?php if ($completed_date): ?>
            <tr>
                <th scope="row"><label><?php _e('Completed Date', 'bkgt-offboarding'); ?></label></th>
                <td><?php echo date_i18n(get_option('date_format'), strtotime($completed_date)); ?></td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }

    public function display_task_checklist_meta_box($post) {
        $tasks = get_post_meta($post->ID, '_bkgt_offboarding_tasks', true);

        if (empty($tasks)) {
            echo '<p>' . __('No tasks assigned to this offboarding process.', 'bkgt-offboarding') . '</p>';
            return;
        }

        echo '<div class="bkgt-task-progress">';
        $completed_count = 0;
        foreach ($tasks as $index => $task) {
            if (get_post_meta($post->ID, '_bkgt_task_' . $index . '_completed', true)) {
                $completed_count++;
            }
        }
        $progress_percentage = count($tasks) > 0 ? round(($completed_count / count($tasks)) * 100) : 0;
        echo '<div class="bkgt-progress-bar"><div class="bkgt-progress-fill" style="width: ' . $progress_percentage . '%"></div></div>';
        echo '<p>' . sprintf(__('Progress: %d of %d tasks completed (%d%%)', 'bkgt-offboarding'), $completed_count, count($tasks), $progress_percentage) . '</p>';
        echo '</div>';

        echo '<ul class="bkgt-admin-task-list">';
        foreach ($tasks as $index => $task) {
            $completed = get_post_meta($post->ID, '_bkgt_task_' . $index . '_completed', true);
            $checked = $completed ? 'checked' : '';
            $class = $completed ? 'completed' : '';

            echo '<li class="bkgt-task-item ' . $class . '">';
            echo '<label>';
            echo '<input type="checkbox" class="bkgt-admin-task-checkbox" data-post-id="' . $post->ID . '" data-task-index="' . $index . '" ' . $checked . '> ';
            echo '<strong>' . esc_html($task['task']) . '</strong>';
            if (!empty($task['description'])) {
                echo '<br><small>' . esc_html($task['description']) . '</small>';
            }
            echo '</label>';
            echo '</li>';
        }
        echo '</ul>';
    }

    public function display_equipment_meta_box($post) {
        $equipment = get_post_meta($post->ID, '_bkgt_offboarding_equipment', true);

        if (empty($equipment)) {
            echo '<p>' . __('Ingen utrustning tilldelad denna person.', 'bkgt-offboarding') . '</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped bkgt-equipment-table">';
        echo '<thead><tr><th>' . __('Objekt', 'bkgt-offboarding') . '</th><th>' . __('Typ', 'bkgt-offboarding') . '</th><th>' . __('Tillverkare', 'bkgt-offboarding') . '</th><th>' . __('Status', 'bkgt-offboarding') . '</th></tr></thead>';
        echo '<tbody>';

        foreach ($equipment as $item) {
            $assignment_id = $item->id;
            $current_status = $item->status ?: 'assigned';

            echo '<tr class="' . esc_attr(strtolower($current_status)) . '">';
            echo '<td>' . esc_html($item->item_name) . '</td>';
            echo '<td>' . esc_html($item->type_name) . '</td>';
            echo '<td>' . esc_html($item->manufacturer_name) . '</td>';
            echo '<td>';
            echo '<select class="bkgt-equipment-status" data-assignment-id="' . $assignment_id . '">';
            echo '<option value="assigned" ' . selected($current_status, 'assigned', false) . '>' . __('Tilldelad', 'bkgt-offboarding') . '</option>';
            echo '<option value="returned" ' . selected($current_status, 'returned', false) . '>' . __('Returnerad', 'bkgt-offboarding') . '</option>';
            echo '<option value="damaged" ' . selected($current_status, 'damaged', false) . '>' . __('Skadad', 'bkgt-offboarding') . '</option>';
            echo '<option value="lost" ' . selected($current_status, 'lost', false) . '>' . __('Förlorad', 'bkgt-offboarding') . '</option>';
            echo '</select>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        echo '<p><button class="button bkgt-generate-receipt" data-post-id="' . $post->ID . '">' . __('Generera PDF-kvitto', 'bkgt-offboarding') . '</button></p>';
    }

    public function display_actions_meta_box($post) {
        $status = get_post_meta($post->ID, '_bkgt_offboarding_status', true);

        if ($status !== 'completed') {
            echo '<p><button class="button button-primary bkgt-complete-offboarding" data-post-id="' . $post->ID . '">' . __('Avsluta avslutningsprocess', 'bkgt-offboarding') . '</button></p>';
            echo '<p><small>' . __('Detta kommer att inaktivera användarkontot och markera processen som slutförd.', 'bkgt-offboarding') . '</small></p>';
        } else {
            echo '<p><strong>' . __('Process Slutförd', 'bkgt-offboarding') . '</strong></p>';
        }

        echo '<hr>';
        echo '<p><button class="button bkgt-add-notification" data-post-id="' . $post->ID . '">' . __('Add Notification', 'bkgt-offboarding') . '</button></p>';

        // Display existing notifications
        $notifications = get_post_meta($post->ID, '_bkgt_offboarding_notifications', true);
        if (!empty($notifications)) {
            echo '<div class="bkgt-notifications-list">';
            foreach ($notifications as $notification) {
                echo '<div class="bkgt-notification-item">';
                echo '<strong>' . esc_html($notification['message']) . '</strong><br>';
                echo '<small>' . date_i18n(get_option('date_format'), strtotime($notification['date'])) . '</small>';
                echo '</div>';
            }
            echo '</div>';
        }
    }

    public function display_notifications_meta_box($post) {
        $notifications = get_post_meta($post->ID, '_bkgt_offboarding_notifications', true);
        if (!is_array($notifications)) {
            $notifications = array();
        }

        echo '<div class="bkgt-notifications-section">';
        echo '<p>' . __('Schedule reminders and notifications for this offboarding process.', 'bkgt-offboarding') . '</p>';

        echo '<div class="bkgt-notifications-list">';
        if (empty($notifications)) {
            echo '<p>' . __('No notifications scheduled.', 'bkgt-offboarding') . '</p>';
        } else {
            foreach ($notifications as $index => $notification) {
                echo '<div class="bkgt-notification-item" data-index="' . $index . '">';
                echo '<div class="bkgt-notification-content">';
                echo '<strong>' . esc_html($notification['message']) . '</strong><br>';
                echo '<small>' . sprintf(__('Scheduled for: %s', 'bkgt-offboarding'), date_i18n(get_option('date_format'), strtotime($notification['date']))) . '</small>';
                echo '</div>';
                echo '<button class="button bkgt-delete-notification" data-index="' . $index . '">' . __('Delete', 'bkgt-offboarding') . '</button>';
                echo '</div>';
            }
        }
        echo '</div>';

        echo '<hr>';
        echo '<h4>' . __('Add New Notification', 'bkgt-offboarding') . '</h4>';
        echo '<div class="bkgt-add-notification-form">';
        echo '<p><input type="text" class="bkgt-notification-message" placeholder="' . __('Notification message', 'bkgt-offboarding') . '" style="width: 100%;"></p>';
        echo '<p><input type="date" class="bkgt-notification-date" min="' . date('Y-m-d') . '" style="width: 200px;"></p>';
        echo '<p><button class="button bkgt-save-notification" data-post-id="' . $post->ID . '">' . __('Add Notification', 'bkgt-offboarding') . '</button></p>';
        echo '</div>';
    }

    public function save_offboarding_meta_boxes($post_id) {
        if (!isset($_POST['bkgt_offboarding_meta_box_nonce']) || !wp_verify_nonce($_POST['bkgt_offboarding_meta_box_nonce'], 'bkgt_offboarding_meta_box')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['bkgt_offboarding_end_date'])) {
            update_post_meta($post_id, '_bkgt_offboarding_end_date', sanitize_text_field($_POST['bkgt_offboarding_end_date']));
        }

        if (isset($_POST['bkgt_offboarding_status'])) {
            update_post_meta($post_id, '_bkgt_offboarding_status', sanitize_text_field($_POST['bkgt_offboarding_status']));
        }

        if (isset($_POST['bkgt_offboarding_notes'])) {
            update_post_meta($post_id, '_bkgt_offboarding_notes', sanitize_textarea_field($_POST['bkgt_offboarding_notes']));
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Offboarding Management', 'bkgt-offboarding'),
            __('Offboarding', 'bkgt-offboarding'),
            'manage_options',
            'bkgt-offboarding',
            array($this, 'admin_page'),
            'dashicons-migrate',
            30
        );

        add_submenu_page(
            'bkgt-offboarding',
            __('All Processes', 'bkgt-offboarding'),
            __('All Processes', 'bkgt-offboarding'),
            'manage_options',
            'edit.php?post_type=bkgt_offboarding'
        );

        add_submenu_page(
            'bkgt-offboarding',
            __('Start Offboarding', 'bkgt-offboarding'),
            __('Start Offboarding', 'bkgt-offboarding'),
            'manage_options',
            'bkgt-start-offboarding',
            array($this, 'start_offboarding_page')
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Offboarding Management', 'bkgt-offboarding'); ?></h1>

            <div class="bkgt-offboarding-dashboard">
                <div class="bkgt-dashboard-section">
                    <h2><?php _e('Active Processes', 'bkgt-offboarding'); ?></h2>
                    <?php $this->display_active_processes(); ?>
                </div>

                <div class="bkgt-dashboard-section">
                    <h2><?php _e('Completed This Month', 'bkgt-offboarding'); ?></h2>
                    <?php $this->display_completed_processes(); ?>
                </div>

                <div class="bkgt-dashboard-section">
                    <h2><?php _e('Quick Actions', 'bkgt-offboarding'); ?></h2>
                    <a href="<?php echo admin_url('admin.php?page=bkgt-start-offboarding'); ?>" class="button button-primary">
                        <?php _e('Start New Offboarding Process', 'bkgt-offboarding'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    private function display_active_processes() {
        global $wpdb;

        $active_processes = $wpdb->get_results("
            SELECT p.ID, p.post_title, pm.meta_value as end_date
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_bkgt_offboarding_end_date'
            WHERE p.post_type = 'bkgt_offboarding'
            AND p.post_status = 'publish'
            AND (pm.meta_value IS NULL OR pm.meta_value > NOW())
            ORDER BY pm.meta_value ASC
            LIMIT 10
        ");

        if (empty($active_processes)) {
            echo '<p>' . __('No active offboarding processes.', 'bkgt-offboarding') . '</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>' . __('Person', 'bkgt-offboarding') . '</th><th>' . __('End Date', 'bkgt-offboarding') . '</th><th>' . __('Actions', 'bkgt-offboarding') . '</th></tr></thead>';
        echo '<tbody>';

        foreach ($active_processes as $process) {
            $end_date = $process->end_date ? date_i18n(get_option('date_format'), strtotime($process->end_date)) : __('Not set', 'bkgt-offboarding');
            echo '<tr>';
            echo '<td>' . esc_html($process->post_title) . '</td>';
            echo '<td>' . esc_html($end_date) . '</td>';
            echo '<td><a href="' . get_edit_post_link($process->ID) . '">' . __('View Details', 'bkgt-offboarding') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private function display_completed_processes() {
        global $wpdb;

        $completed_count = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_bkgt_offboarding_status'
            WHERE p.post_type = 'bkgt_offboarding'
            AND pm.meta_value = 'completed'
            AND MONTH(p.post_date) = MONTH(NOW())
            AND YEAR(p.post_date) = YEAR(NOW())
        ");

        echo '<div class="bkgt-stat-box">';
        echo '<span class="bkgt-stat-number">' . intval($completed_count) . '</span>';
        echo '<span class="bkgt-stat-label">' . __('Completed this month', 'bkgt-offboarding') . '</span>';
        echo '</div>';
    }

    public function start_offboarding_page() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'bkgt-offboarding'));
        }

        if (isset($_POST['start_offboarding']) && wp_verify_nonce($_POST['offboarding_nonce'], 'start_offboarding')) {
            $this->process_start_offboarding();
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Start Offboarding Process', 'bkgt-offboarding'); ?></h1>

            <form method="post" class="bkgt-offboarding-form">
                <?php wp_nonce_field('start_offboarding', 'offboarding_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="user_id"><?php _e('Select Person', 'bkgt-offboarding'); ?></label></th>
                        <td>
                            <select name="user_id" id="user_id" required>
                                <option value=""><?php _e('Choose a person...', 'bkgt-offboarding'); ?></option>
                                <?php
                                $users = get_users(array('role__not_in' => array('inactive')));
                                foreach ($users as $user) {
                                    echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="end_date"><?php _e('End Date', 'bkgt-offboarding'); ?></label></th>
                        <td>
                            <input type="date" name="end_date" id="end_date" required
                                   min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                            <p class="description"><?php _e('Date when the person should be deactivated.', 'bkgt-offboarding'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="notes"><?php _e('Additional Notes', 'bkgt-offboarding'); ?></label></th>
                        <td>
                            <textarea name="notes" id="notes" rows="4" cols="50"></textarea>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="start_offboarding" class="button button-primary" value="<?php _e('Start Offboarding Process', 'bkgt-offboarding'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    private function process_start_offboarding() {
        $user_id = intval($_POST['user_id']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $notes = sanitize_textarea_field($_POST['notes']);

        $user = get_userdata($user_id);
        if (!$user) {
            wp_die(__('Invalid user selected.', 'bkgt-offboarding'));
        }

        // Create offboarding process post
        $post_data = array(
            'post_title' => $user->display_name,
            'post_type' => 'bkgt_offboarding',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        );

        $post_id = wp_insert_post($post_data);

        if ($post_id) {
            // Save metadata
            update_post_meta($post_id, '_bkgt_offboarding_user_id', $user_id);
            update_post_meta($post_id, '_bkgt_offboarding_end_date', $end_date);
            update_post_meta($post_id, '_bkgt_offboarding_status', 'active');
            update_post_meta($post_id, '_bkgt_offboarding_notes', $notes);
            update_post_meta($post_id, '_bkgt_offboarding_started_by', get_current_user_id());
            update_post_meta($post_id, '_bkgt_offboarding_started_date', current_time('mysql'));

            // Generate task checklist based on user role
            $this->generate_task_checklist($post_id, $user);

            // Generate equipment receipt
            $this->generate_equipment_receipt($post_id, $user_id);

            wp_redirect(admin_url('post.php?post=' . $post_id . '&action=edit&message=1'));
            exit;
        } else {
            wp_die(__('Failed to create offboarding process.', 'bkgt-offboarding'));
        }
    }

    private function generate_task_checklist($post_id, $user) {
        $tasks = array();

        // Base tasks for everyone
        $tasks[] = array(
            'task' => __('Return all equipment and keys', 'bkgt-offboarding'),
            'description' => __('Ensure all assigned equipment is returned and keys are handed over', 'bkgt-offboarding'),
            'required' => true
        );

        $tasks[] = array(
            'task' => __('Complete final reports', 'bkgt-offboarding'),
            'description' => __('Submit any outstanding reports or documentation', 'bkgt-offboarding'),
            'required' => true
        );

        // Role-specific tasks
        $user_roles = $user->roles;
        if (in_array('styrelsemedlem', $user_roles) || in_array('administrator', $user_roles)) {
            $tasks[] = array(
                'task' => __('Transfer financial responsibilities', 'bkgt-offboarding'),
                'description' => __('Hand over budget control and financial reporting responsibilities', 'bkgt-offboarding'),
                'required' => true
            );
        }

        if (in_array('tranare', $user_roles)) {
            $tasks[] = array(
                'task' => __('Transfer team leadership', 'bkgt-offboarding'),
                'description' => __('Ensure team leadership is transferred to replacement coach', 'bkgt-offboarding'),
                'required' => true
            );
        }

        // Save tasks
        update_post_meta($post_id, '_bkgt_offboarding_tasks', $tasks);
    }

    private function generate_equipment_receipt($post_id, $user_id) {
        global $wpdb;

        // Get all equipment assigned to this user
        $assignments = $wpdb->get_results($wpdb->prepare("
            SELECT a.*, i.name as item_name, i.item_id, m.name as manufacturer_name, t.name as type_name
            FROM {$wpdb->prefix}bkgt_assignments a
            LEFT JOIN {$wpdb->prefix}bkgt_inventory_items i ON a.item_id = i.id
            LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
            LEFT JOIN {$wpdb->prefix}bkgt_item_types t ON i.item_type_id = t.id
            WHERE a.assignee_id = %d AND a.status = 'assigned'
        ", $user_id));

        update_post_meta($post_id, '_bkgt_offboarding_equipment', $assignments);
    }

    // Shortcode implementations
    public function offboarding_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Vänligen logga in för att visa din avslutningsprocess.', 'bkgt-offboarding') . '</p>';
        }

        $user_id = get_current_user_id();
        $processes = $this->get_user_offboarding_processes($user_id);

        if (empty($processes)) {
            return '<p>' . __('Ingen avslutningsprocess hittades.', 'bkgt-offboarding') . '</p>';
        }

        ob_start();
        ?>
        <div class="bkgt-offboarding-dashboard">
            <h2><?php _e('Din Avslutningsprocess', 'bkgt-offboarding'); ?></h2>

            <?php foreach ($processes as $process): ?>
                <div class="bkgt-offboarding-process">
                    <h3><?php echo esc_html($process->post_title); ?></h3>
                    <p><strong><?php _e('Status:', 'bkgt-offboarding'); ?></strong> <?php echo esc_html($this->translate_status(get_post_meta($process->ID, '_bkgt_offboarding_status', true))); ?></p>
                    <p><strong><?php _e('Slutdatum:', 'bkgt-offboarding'); ?></strong> <?php echo esc_html(get_post_meta($process->ID, '_bkgt_offboarding_end_date', true)); ?></p>

                    <?php $this->display_task_checklist($process->ID); ?>
                    <?php $this->display_equipment_receipt($process->ID); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_user_offboarding_processes($user_id) {
        return get_posts(array(
            'post_type' => 'bkgt_offboarding',
            'meta_query' => array(
                array(
                    'key' => '_bkgt_offboarding_user_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1
        ));
    }

    private function translate_status($status) {
        $translations = array(
            'active' => __('Aktiv', 'bkgt-offboarding'),
            'completed' => __('Avslutad', 'bkgt-offboarding'),
            'pending' => __('Väntande', 'bkgt-offboarding'),
        );
        return isset($translations[$status]) ? $translations[$status] : $status;
    }

    private function display_task_checklist($post_id) {
        $tasks = get_post_meta($post_id, '_bkgt_offboarding_tasks', true);

        if (empty($tasks)) {
            return;
        }

        // Calculate progress
        $completed_count = 0;
        foreach ($tasks as $index => $task) {
            if (get_post_meta($post_id, '_bkgt_task_' . $index . '_completed', true)) {
                $completed_count++;
            }
        }
        $progress_percentage = count($tasks) > 0 ? round(($completed_count / count($tasks)) * 100) : 0;

        echo '<div class="bkgt-task-progress">';
        echo '<div class="bkgt-progress-bar"><div class="bkgt-progress-fill" style="width: ' . $progress_percentage . '%"></div></div>';
        echo '<p>' . sprintf(__('Framsteg: %d av %d uppgifter slutförda (%d%%)', 'bkgt-offboarding'), $completed_count, count($tasks), $progress_percentage) . '</p>';
        echo '</div>';

        echo '<h4>' . __('Checklista', 'bkgt-offboarding') . '</h4>';
        echo '<ul class="bkgt-task-list">';

        foreach ($tasks as $index => $task) {
            $completed = get_post_meta($post_id, '_bkgt_task_' . $index . '_completed', true);
            $checked = $completed ? 'checked' : '';
            $class = $completed ? 'completed' : '';

            echo '<li class="bkgt-task-item ' . $class . '">';
            echo '<label>';
            echo '<input type="checkbox" class="bkgt-task-checkbox" data-post-id="' . $post_id . '" data-task-index="' . $index . '" ' . $checked . '> ';
            echo '<strong>' . esc_html($task['task']) . '</strong>';
            if (!empty($task['description'])) {
                echo '<br><small>' . esc_html($task['description']) . '</small>';
            }
            echo '</label>';
            echo '</li>';
        }

        echo '</ul>';
    }

    private function display_equipment_receipt($post_id) {
        $equipment = get_post_meta($post_id, '_bkgt_offboarding_equipment', true);

        if (empty($equipment)) {
            return;
        }

        echo '<h4>' . __('Utrustning att returnera', 'bkgt-offboarding') . '</h4>';
        echo '<table class="bkgt-equipment-table">';
        echo '<thead><tr><th>' . __('Objekt', 'bkgt-offboarding') . '</th><th>' . __('Typ', 'bkgt-offboarding') . '</th><th>' . __('Tillverkare', 'bkgt-offboarding') . '</th><th>' . __('Status', 'bkgt-offboarding') . '</th></tr></thead>';
        echo '<tbody>';

        foreach ($equipment as $item) {
            $status = get_post_meta($post_id, '_bkgt_equipment_' . $item->id . '_status', true) ?: 'assigned';
            $checked = $status === 'returned' ? 'checked' : '';
            echo '<tr class="' . ($status === 'returned' ? 'returned' : '') . '">';
            echo '<td>' . esc_html($item->item_name) . '</td>';
            echo '<td>' . esc_html($item->type_name) . '</td>';
            echo '<td>' . esc_html($item->manufacturer_name) . '</td>';
            echo '<td><input type="checkbox" class="bkgt-equipment-returned" data-assignment-id="' . $item->id . '" ' . $checked . '> ' . __('Returnerad', 'bkgt-offboarding') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        echo '<p><button class="button bkgt-generate-receipt" data-post-id="' . $post_id . '">' . __('Generate PDF Receipt', 'bkgt-offboarding') . '</button></p>';
    }

    // AJAX handlers
    public function ajax_start_offboarding() {
        // Verify nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bkgt_start_offboarding_nonce')) {
            wp_send_json_error(__('Security check failed', 'bkgt-offboarding'), 403);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action', 'bkgt-offboarding'), 403);
            return;
        }

        // Validate required fields
        if (empty($_POST['user_id']) || empty($_POST['end_date'])) {
            wp_send_json_error(__('Missing required fields', 'bkgt-offboarding'), 400);
            return;
        }

        $user_id = intval($_POST['user_id']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        // Validate user exists and is not already inactive
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(__('Invalid user selected', 'bkgt-offboarding'), 404);
            return;
        }

        if (in_array('inactive', $user->roles)) {
            wp_send_json_error(__('User is already inactive', 'bkgt-offboarding'), 400);
            return;
        }

        // Validate end date is in the future
        if (strtotime($end_date) < strtotime('today')) {
            wp_send_json_error(__('End date must be in the future', 'bkgt-offboarding'), 400);
            return;
        }

        // Check if offboarding process already exists for this user
        $existing = new WP_Query(array(
            'post_type' => 'bkgt_offboarding',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_bkgt_offboarding_user_id',
                    'value' => $user_id,
                    'compare' => '='
                ),
                array(
                    'key' => '_bkgt_offboarding_status',
                    'value' => 'active',
                    'compare' => '='
                )
            )
        ));

        if ($existing->have_posts()) {
            wp_send_json_error(__('An active offboarding process already exists for this user', 'bkgt-offboarding'), 400);
            return;
        }

        // Create offboarding process post
        $post_data = array(
            'post_title' => $user->display_name,
            'post_type' => 'bkgt_offboarding',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        );

        $post_id = wp_insert_post($post_data);

        if (!$post_id || is_wp_error($post_id)) {
            wp_send_json_error(__('Failed to create offboarding process', 'bkgt-offboarding'), 500);
            return;
        }

        // Save metadata
        update_post_meta($post_id, '_bkgt_offboarding_user_id', $user_id);
        update_post_meta($post_id, '_bkgt_offboarding_end_date', $end_date);
        update_post_meta($post_id, '_bkgt_offboarding_status', 'active');
        update_post_meta($post_id, '_bkgt_offboarding_notes', $notes);
        update_post_meta($post_id, '_bkgt_offboarding_started_by', get_current_user_id());
        update_post_meta($post_id, '_bkgt_offboarding_started_date', current_time('mysql'));

        // Generate task checklist based on user role
        $this->generate_task_checklist($post_id, $user);

        // Generate equipment receipt
        $this->generate_equipment_receipt($post_id, $user_id);

        // Log the action
        if (function_exists('bkgt_log')) {
            bkgt_log('info', sprintf('Offboarding process started for user %d (%s) by user %d', $user_id, $user->display_name, get_current_user_id()));
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Offboarding process started for %s', 'bkgt-offboarding'), $user->display_name),
            'post_id' => $post_id,
            'edit_url' => admin_url('post.php?post=' . $post_id . '&action=edit')
        ));
    }

    public function ajax_update_offboarding_task() {
        $post_id = intval($_POST['post_id']);
        $task_index = intval($_POST['task_index']);
        $completed = isset($_POST['completed']) ? 1 : 0;

        update_post_meta($post_id, '_bkgt_task_' . $task_index . '_completed', $completed);

        wp_send_json_success();
    }

    public function ajax_complete_offboarding() {
        $post_id = intval($_POST['post_id']);

        // Mark as completed
        update_post_meta($post_id, '_bkgt_offboarding_status', 'completed');
        update_post_meta($post_id, '_bkgt_offboarding_completed_date', current_time('mysql'));

        // Deactivate user account
        $user_id = get_post_meta($post_id, '_bkgt_offboarding_user_id', true);
        if ($user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                // Change role to inactive instead of deleting
                $user->set_role('inactive');
            }
        }

        wp_send_json_success();
    }

    public function ajax_update_equipment_status() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bkgt_offboarding_nonce')) {
            wp_send_json_error(__('Säkerhetskontroll misslyckades', 'bkgt-offboarding'), 403);
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Du måste vara inloggad', 'bkgt-offboarding'), 403);
            return;
        }

        $assignment_id = intval($_POST['assignment_id']);
        $status = sanitize_text_field($_POST['status']);

        // Validate status value
        $valid_statuses = array('assigned', 'returned', 'damaged', 'lost');
        if (!in_array($status, $valid_statuses)) {
            wp_send_json_error(__('Ogiltigt status värde', 'bkgt-offboarding'), 400);
            return;
        }

        if (empty($assignment_id)) {
            wp_send_json_error(__('Tilldelnings-ID saknas', 'bkgt-offboarding'), 400);
            return;
        }

        global $wpdb;

        // Update equipment assignment status
        $result = $wpdb->update(
            $wpdb->prefix . 'bkgt_assignments',
            array('status' => $status),
            array('id' => $assignment_id),
            array('%s'),
            array('%d')
        );

        if (false === $result) {
            wp_send_json_error(__('Det gick inte att uppdatera utrustningsstatus', 'bkgt-offboarding'), 500);
            return;
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Utrustningen markerad som %s', 'bkgt-offboarding'), $this->translate_equipment_status($status))
        ));
    }

    private function translate_equipment_status($status) {
        $translations = array(
            'assigned' => __('tilldelad', 'bkgt-offboarding'),
            'returned' => __('returnerad', 'bkgt-offboarding'),
            'damaged' => __('skadad', 'bkgt-offboarding'),
            'lost' => __('förlorad', 'bkgt-offboarding'),
        );
        return isset($translations[$status]) ? $translations[$status] : $status;
    }

    public function ajax_add_notification() {
        $post_id = intval($_POST['post_id']);
        $message = sanitize_text_field($_POST['message']);
        $notification_date = sanitize_text_field($_POST['notification_date']);

        $notifications = get_post_meta($post_id, '_bkgt_offboarding_notifications', true);
        if (!is_array($notifications)) {
            $notifications = array();
        }

        $notifications[] = array(
            'message' => $message,
            'date' => $notification_date,
            'created' => current_time('mysql')
        );

        update_post_meta($post_id, '_bkgt_offboarding_notifications', $notifications);

        wp_send_json_success();
    }

    public function ajax_delete_notification() {
        $post_id = intval($_POST['post_id']);
        $index = intval($_POST['index']);

        $notifications = get_post_meta($post_id, '_bkgt_offboarding_notifications', true);
        if (!is_array($notifications)) {
            $notifications = array();
        }

        if (isset($notifications[$index])) {
            unset($notifications[$index]);
            $notifications = array_values($notifications); // Re-index array
            update_post_meta($post_id, '_bkgt_offboarding_notifications', $notifications);
        }

        wp_send_json_success();
    }

    public function ajax_generate_equipment_receipt() {
        $post_id = intval($_POST['post_id']);

        // Get equipment data
        $equipment = get_post_meta($post_id, '_bkgt_offboarding_equipment', true);
        $user_id = get_post_meta($post_id, '_bkgt_offboarding_user_id', true);
        $user = get_userdata($user_id);

        if (empty($equipment) || !$user) {
            wp_send_json_error(__('No equipment or user data found.', 'bkgt-offboarding'));
            return;
        }

        // Generate HTML content for PDF
        $html_content = $this->generate_receipt_html($post_id, $equipment, $user);

        // For now, return HTML content - in production, use a PDF library
        // You would typically use TCPDF, FPDF, or similar library here
        wp_send_json_success(array(
            'message' => __('Receipt generated successfully', 'bkgt-offboarding'),
            'html_content' => $html_content,
            'download_url' => admin_url('admin-ajax.php?action=bkgt_download_receipt&post_id=' . $post_id . '&nonce=' . wp_create_nonce('download_receipt'))
        ));
    }

    private function generate_receipt_html($post_id, $equipment, $user) {
        $end_date = get_post_meta($post_id, '_bkgt_offboarding_end_date', true);
        $current_date = date_i18n(get_option('date_format'));

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php _e('Equipment Receipt', 'bkgt-offboarding'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
                .header h1 { margin: 0; color: #333; }
                .header p { margin: 5px 0; color: #666; }
                .person-info { margin-bottom: 30px; }
                .person-info table { width: 100%; border-collapse: collapse; }
                .person-info td { padding: 5px; border: 1px solid #ddd; }
                .person-info td:first-child { font-weight: bold; background-color: #f5f5f5; width: 150px; }
                .equipment-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                .equipment-table th, .equipment-table td { border: 1px solid #000; padding: 8px; text-align: left; }
                .equipment-table th { background-color: #f0f0f0; font-weight: bold; }
                .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
                .signature-section { margin-top: 50px; display: table; width: 100%; }
                .signature-box { display: table-cell; width: 45%; text-align: center; border-top: 1px solid #000; padding-top: 10px; }
                .signature-box:first-child { border-right: 1px solid #000; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php _e('Equipment Return Receipt', 'bkgt-offboarding'); ?></h1>
                <p><?php _e('BKGT - Personnel Transition', 'bkgt-offboarding'); ?></p>
                <p><?php echo sprintf(__('Generated on: %s', 'bkgt-offboarding'), $current_date); ?></p>
            </div>

            <div class="person-info">
                <h2><?php _e('Person Information', 'bkgt-offboarding'); ?></h2>
                <table>
                    <tr>
                        <td><?php _e('Name:', 'bkgt-offboarding'); ?></td>
                        <td><?php echo esc_html($user->display_name); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Email:', 'bkgt-offboarding'); ?></td>
                        <td><?php echo esc_html($user->user_email); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('End Date:', 'bkgt-offboarding'); ?></td>
                        <td><?php echo $end_date ? date_i18n(get_option('date_format'), strtotime($end_date)) : __('Not specified', 'bkgt-offboarding'); ?></td>
                    </tr>
                </table>
            </div>

            <h2><?php _e('Equipment to be Returned', 'bkgt-offboarding'); ?></h2>
            <table class="equipment-table">
                <thead>
                    <tr>
                        <th><?php _e('Item Name', 'bkgt-offboarding'); ?></th>
                        <th><?php _e('Type', 'bkgt-offboarding'); ?></th>
                        <th><?php _e('Manufacturer', 'bkgt-offboarding'); ?></th>
                        <th><?php _e('Status', 'bkgt-offboarding'); ?></th>
                        <th><?php _e('Returned', 'bkgt-offboarding'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipment as $item): ?>
                    <tr>
                        <td><?php echo esc_html($item->item_name); ?></td>
                        <td><?php echo esc_html($item->type_name); ?></td>
                        <td><?php echo esc_html($item->manufacturer_name); ?></td>
                        <td><?php echo esc_html(ucfirst($item->status ?: 'assigned')); ?></td>
                        <td>☐ <?php _e('Yes', 'bkgt-offboarding'); ?> ☐ <?php _e('No', 'bkgt-offboarding'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="signature-section">
                <div class="signature-box">
                    <p><?php _e('Person Leaving', 'bkgt-offboarding'); ?></p>
                    <p><?php _e('Signature & Date', 'bkgt-offboarding'); ?></p>
                </div>
                <div class="signature-box">
                    <p><?php _e('Authorized Representative', 'bkgt-offboarding'); ?></p>
                    <p><?php _e('Signature & Date', 'bkgt-offboarding'); ?></p>
                </div>
            </div>

            <div class="footer">
                <p><?php _e('This receipt serves as confirmation of equipment return. Please ensure all items are returned in good condition.', 'bkgt-offboarding'); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    public function download_receipt() {
        if (!isset($_GET['post_id']) || !isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'download_receipt')) {
            wp_die(__('Invalid request.', 'bkgt-offboarding'));
        }

        $post_id = intval($_GET['post_id']);

        // Check permissions
        if (!current_user_can('manage_options')) {
            $user_id = get_post_meta($post_id, '_bkgt_offboarding_user_id', true);
            if (get_current_user_id() != $user_id) {
                wp_die(__('You do not have permission to access this receipt.', 'bkgt-offboarding'));
            }
        }

        $equipment = get_post_meta($post_id, '_bkgt_offboarding_equipment', true);
        $user_id = get_post_meta($post_id, '_bkgt_offboarding_user_id', true);
        $user = get_userdata($user_id);

        if (empty($equipment) || !$user) {
            wp_die(__('No equipment or user data found.', 'bkgt-offboarding'));
        }

        $html_content = $this->generate_receipt_html($post_id, $equipment, $user);

        // Set headers for HTML download (can be opened in Word/browser)
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="equipment-receipt-' . sanitize_file_name($user->display_name) . '.html"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $html_content;
        exit;
    }

    // Scheduled task
    public function check_offboarding_deadlines() {
        global $wpdb;

        // Find offboarding processes ending today or in the past
        $expired_processes = $wpdb->get_results("
            SELECT p.ID, p.post_title, pm1.meta_value as end_date, pm2.meta_value as user_id, pm3.meta_value as status
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_bkgt_offboarding_end_date'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_bkgt_offboarding_user_id'
            LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_bkgt_offboarding_status'
            WHERE p.post_type = 'bkgt_offboarding'
            AND p.post_status = 'publish'
            AND pm3.meta_value = 'active'
            AND pm1.meta_value <= CURDATE()
        ");

        foreach ($expired_processes as $process) {
            // Automatically complete the offboarding process
            update_post_meta($process->ID, '_bkgt_offboarding_status', 'completed');
            update_post_meta($process->ID, '_bkgt_offboarding_completed_date', current_time('mysql'));
            update_post_meta($process->ID, '_bkgt_offboarding_auto_completed', '1'); // Mark as auto-completed

            // Deactivate user account
            if ($process->user_id) {
                $user = get_userdata($process->user_id);
                if ($user && !in_array('inactive', $user->roles)) {
                    $user->set_role('inactive');
                }
            }
        }

        // Find offboarding processes ending soon (within 7 days) for notifications
        $upcoming_processes = $wpdb->get_results("
            SELECT p.ID, p.post_title, pm1.meta_value as end_date, pm2.meta_value as user_id
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_bkgt_offboarding_end_date'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_bkgt_offboarding_user_id'
            WHERE p.post_type = 'bkgt_offboarding'
            AND p.post_status = 'publish'
            AND pm1.meta_value BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ");

        foreach ($upcoming_processes as $process) {
            // Send notification to board members
            $this->send_deadline_notification($process);
        }
    }

    private function send_deadline_notification($process) {
        $board_members = get_users(array('role' => 'administrator'));

        $subject = sprintf(__('Offboarding deadline approaching: %s', 'bkgt-offboarding'), $process->post_title);
        $message = sprintf(__('The offboarding process for %s is approaching its end date: %s', 'bkgt-offboarding'),
                          $process->post_title, $process->end_date);

        foreach ($board_members as $member) {
            wp_mail($member->user_email, $subject, $message);
        }
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style('bkgt-offboarding-style', BKGT_OFFBOARDING_PLUGIN_URL . 'assets/css/frontend.css', array(), BKGT_OFFBOARDING_VERSION);
        wp_enqueue_script('bkgt-offboarding-script', BKGT_OFFBOARDING_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), BKGT_OFFBOARDING_VERSION, true);

        wp_localize_script('bkgt-offboarding-script', 'bkgt_offboarding_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkgt_offboarding_nonce')
        ));
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'bkgt-offboarding') !== false) {
            wp_enqueue_style('bkgt-offboarding-admin-style', BKGT_OFFBOARDING_PLUGIN_URL . 'assets/css/admin.css', array(), BKGT_OFFBOARDING_VERSION);
            wp_enqueue_script('bkgt-offboarding-admin-script', BKGT_OFFBOARDING_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), BKGT_OFFBOARDING_VERSION, true);
        }
    }
}

/**
 * Initialize the plugin
 */
function bkgt_offboarding_system() {
    return BKGT_Offboarding_System::get_instance();
}
add_action('plugins_loaded', 'bkgt_offboarding_system');

// Include database class
require_once BKGT_OFFBOARDING_PLUGIN_DIR . 'includes/class-database.php';
?>