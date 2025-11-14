<?php
/**
 * BKGT API Admin Class
 *
 * Handles the admin interface for the BKGT API plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_API_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_bkgt_api_create_key', array($this, 'ajax_create_api_key'));
        add_action('wp_ajax_bkgt_api_revoke_key', array($this, 'ajax_revoke_api_key'));
        add_action('wp_ajax_bkgt_api_delete_key', array($this, 'ajax_delete_api_key'));
        add_action('wp_ajax_bkgt_api_get_logs', array($this, 'ajax_get_logs'));
        add_action('wp_ajax_bkgt_api_get_security_logs', array($this, 'ajax_get_security_logs'));
        add_action('wp_ajax_bkgt_api_mark_notification_read', array($this, 'ajax_mark_notification_read'));
        add_action('wp_ajax_bkgt_api_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_bkgt_api_upload_update', array($this, 'ajax_upload_update'));
        add_action('wp_ajax_bkgt_api_get_updates', array($this, 'ajax_get_updates'));
        add_action('wp_ajax_bkgt_api_deactivate_update', array($this, 'ajax_deactivate_update'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('BKGT API', 'bkgt-api'),
            __('BKGT API', 'bkgt-api'),
            'manage_options',
            'bkgt-api',
            array($this, 'admin_page'),
            'dashicons-rest-api',
            30
        );

        add_submenu_page(
            'bkgt-api',
            __('Dashboard', 'bkgt-api'),
            __('Dashboard', 'bkgt-api'),
            'manage_options',
            'bkgt-api',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'bkgt-api',
            __('API Keys', 'bkgt-api'),
            __('API Keys', 'bkgt-api'),
            'manage_options',
            'bkgt-api-keys',
            array($this, 'api_keys_page')
        );

        add_submenu_page(
            'bkgt-api',
            __('Logs', 'bkgt-api'),
            __('Logs', 'bkgt-api'),
            'manage_options',
            'bkgt-api-logs',
            array($this, 'logs_page')
        );

        add_submenu_page(
            'bkgt-api',
            __('Security', 'bkgt-api'),
            __('Security', 'bkgt-api'),
            'manage_options',
            'bkgt-api-security',
            array($this, 'security_page')
        );

        add_submenu_page(
            'bkgt-api',
            __('Settings', 'bkgt-api'),
            __('Settings', 'bkgt-api'),
            'manage_options',
            'bkgt-api-settings',
            array($this, 'settings_page')
        );

        add_submenu_page(
            'bkgt-api',
            __('Diagnostic', 'bkgt-api'),
            __('Diagnostic', 'bkgt-api'),
            'manage_options',
            'bkgt-api-diagnostic',
            array($this, 'diagnostic_page')
        );

        add_submenu_page(
            'bkgt-api',
            __('Updates', 'bkgt-api'),
            __('Updates', 'bkgt-api'),
            'manage_options',
            'bkgt-api-updates',
            array($this, 'updates_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on BKGT API admin pages
        if (strpos($hook, 'bkgt-api') === false) {
            return;
        }

        wp_enqueue_style(
            'bkgt-api-admin',
            BKGT_API_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            time() // Use timestamp to force cache refresh
        );

        wp_enqueue_script(
            'bkgt-api-admin',
            BKGT_API_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            time(), // Use timestamp to force cache refresh
            true
        );

        wp_localize_script('bkgt-api-admin', 'bkgt_api_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkgt_api_admin_nonce'),
            'strings' => array(
                'confirm_revoke' => __('Are you sure you want to revoke this API key?', 'bkgt-api'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'bkgt-api'),
                'loading' => __('Loading...', 'bkgt-api'),
                'error' => __('An error occurred. Please try again.', 'bkgt-api'),
                'success' => __('Operation completed successfully.', 'bkgt-api'),
            ),
        ));
    }

    /**
     * Main admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('BKGT API Dashboard', 'bkgt-api'); ?></h1>

            <div class="bkgt-api-dashboard">
                <div class="bkgt-api-stats-grid">
                    <?php $this->render_stats_cards(); ?>
                </div>

                <div class="bkgt-api-dashboard-content">
                    <div class="bkgt-api-recent-activity">
                        <h2><?php _e('Recent Activity', 'bkgt-api'); ?></h2>
                        <?php $this->render_recent_activity(); ?>
                    </div>

                    <div class="bkgt-api-notifications">
                        <h2><?php _e('Notifications', 'bkgt-api'); ?></h2>
                        <?php $this->render_notifications(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * API Keys page
     */
    public function api_keys_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('API Keys Management', 'bkgt-api'); ?></h1>

            <div class="bkgt-api-keys">
                <div class="bkgt-api-section">
                    <h2><?php _e('Create New API Key', 'bkgt-api'); ?></h2>
                    <form id="bkgt-api-create-key-form" class="bkgt-api-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="key_name"><?php _e('Key Name', 'bkgt-api'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="key_name" name="key_name" class="regular-text" required>
                                    <p class="description"><?php _e('A descriptive name for this API key.', 'bkgt-api'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="key_permissions"><?php _e('Permissions', 'bkgt-api'); ?></label>
                                </th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="key_permissions[]" value="read" checked>
                                            <?php _e('Read', 'bkgt-api'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="key_permissions[]" value="write">
                                            <?php _e('Write', 'bkgt-api'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="key_permissions[]" value="admin">
                                            <?php _e('Admin', 'bkgt-api'); ?>
                                        </label>
                                    </fieldset>
                                    <p class="description"><?php _e('Select the permissions for this API key.', 'bkgt-api'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Create API Key', 'bkgt-api'); ?></button>
                        </p>
                    </form>
                </div>

                <div class="bkgt-api-section">
                    <h2><?php _e('Existing API Keys', 'bkgt-api'); ?></h2>
                    <div id="bkgt-api-keys-list">
                        <?php $this->render_api_keys_list(); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Key Created Modal -->
        <div id="bkgt-api-key-modal" class="bkgt-modal" style="display: none;">
            <div class="bkgt-modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;"></div>
            <div class="bkgt-modal-content" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); z-index: 1001; max-width: 500px; width: 90%;">
                <div class="bkgt-modal-header" style="margin-bottom: 20px;">
                    <h3 style="margin: 0; color: #1d2327;"><?php _e('API Key Created Successfully', 'bkgt-api'); ?></h3>
                </div>
                <div class="bkgt-modal-body">
                    <p style="margin-bottom: 15px; color: #50575e;"><?php _e('Your new API key has been created. Please copy and save it securely - it will only be shown once.', 'bkgt-api'); ?></p>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #1d2327;"><?php _e('API Key:', 'bkgt-api'); ?></label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="bkgt-api-key-display" readonly style="flex: 1; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; background: #f6f7f7; font-family: monospace; font-size: 14px; color: #1d2327;" />
                            <button type="button" id="bkgt-api-key-copy" class="button button-secondary" style="white-space: nowrap;"><?php _e('Copy', 'bkgt-api'); ?></button>
                        </div>
                    </div>

                    <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 12px; margin-bottom: 20px;">
                        <strong style="color: #856404;"><?php _e('Security Warning:', 'bkgt-api'); ?></strong>
                        <p style="margin: 5px 0 0 0; color: #856404; font-size: 14px;"><?php _e('This API key provides access to your data. Store it securely and never share it publicly.', 'bkgt-api'); ?></p>
                    </div>
                </div>
                <div class="bkgt-modal-footer" style="text-align: right;">
                    <button type="button" id="bkgt-api-key-close" class="button button-primary"><?php _e('I Understand', 'bkgt-api'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Logs page
     */
    public function logs_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('API Logs', 'bkgt-api'); ?></h1>

            <div class="bkgt-api-logs">
                <div class="bkgt-api-filters">
                    <form id="bkgt-api-logs-filter" class="bkgt-api-form">
                        <div class="bkgt-api-filter-row">
                            <select name="method">
                                <option value=""><?php _e('All Methods', 'bkgt-api'); ?></option>
                                <option value="GET">GET</option>
                                <option value="POST">POST</option>
                                <option value="PUT">PUT</option>
                                <option value="DELETE">DELETE</option>
                            </select>

                            <input type="text" name="endpoint" placeholder="<?php esc_attr_e('Endpoint', 'bkgt-api'); ?>">

                            <input type="date" name="start_date" placeholder="<?php esc_attr_e('Start Date', 'bkgt-api'); ?>">

                            <input type="date" name="end_date" placeholder="<?php esc_attr_e('End Date', 'bkgt-api'); ?>">

                            <button type="submit" class="button"><?php _e('Filter', 'bkgt-api'); ?></button>
                        </div>
                    </form>
                </div>

                <div id="bkgt-api-logs-table">
                    <?php $this->render_logs_table(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Security page
     */
    public function security_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('API Security', 'bkgt-api'); ?></h1>

            <div class="bkgt-api-security">
                <div class="bkgt-api-section">
                    <h2><?php _e('Security Overview', 'bkgt-api'); ?></h2>
                    <?php $this->render_security_overview(); ?>
                </div>

                <div class="bkgt-api-section">
                    <h2><?php _e('Security Logs', 'bkgt-api'); ?></h2>
                    <div id="bkgt-api-security-logs">
                        <?php $this->render_security_logs(); ?>
                    </div>
                </div>

                <div class="bkgt-api-section">
                    <h2><?php _e('Blocked IPs', 'bkgt-api'); ?></h2>
                    <?php $this->render_blocked_ips(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'bkgt-api') . '</p></div>';
        }

        $settings = array(
            'jwt_expiry' => get_option('jwt_expiry', 900),
            'refresh_token_expiry' => get_option('refresh_token_expiry', 604800),
            'api_rate_limit' => get_option('api_rate_limit', 100),
            'api_rate_limit_window' => get_option('api_rate_limit_window', 60),
            'cors_allowed_origins' => get_option('cors_allowed_origins', array()),
            'api_debug_mode' => get_option('api_debug_mode', false),
            'api_logging_enabled' => get_option('api_logging_enabled', true),
            'api_cache_enabled' => get_option('api_cache_enabled', true),
            'api_cache_ttl' => get_option('api_cache_ttl', 300),
        );

        ?>
        <div class="wrap">
            <h1><?php _e('API Settings', 'bkgt-api'); ?></h1>

            <form method="post" class="bkgt-api-settings-form">
                <?php wp_nonce_field('bkgt_api_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('JWT Token Expiry', 'bkgt-api'); ?></th>
                        <td>
                            <input type="number" name="jwt_expiry" value="<?php echo esc_attr($settings['jwt_expiry']); ?>" min="60" max="86400">
                            <p class="description"><?php _e('Token expiry time in seconds (default: 900 = 15 minutes).', 'bkgt-api'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Refresh Token Expiry', 'bkgt-api'); ?></th>
                        <td>
                            <input type="number" name="refresh_token_expiry" value="<?php echo esc_attr($settings['refresh_token_expiry']); ?>" min="3600" max="2592000">
                            <p class="description"><?php _e('Refresh token expiry time in seconds (default: 604800 = 7 days).', 'bkgt-api'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Rate Limit', 'bkgt-api'); ?></th>
                        <td>
                            <input type="number" name="api_rate_limit" value="<?php echo esc_attr($settings['api_rate_limit']); ?>" min="1" max="1000">
                            <p class="description"><?php _e('Maximum requests per time window (default: 100).', 'bkgt-api'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Rate Limit Window', 'bkgt-api'); ?></th>
                        <td>
                            <input type="number" name="api_rate_limit_window" value="<?php echo esc_attr($settings['api_rate_limit_window']); ?>" min="1" max="3600">
                            <p class="description"><?php _e('Time window for rate limiting in seconds (default: 60).', 'bkgt-api'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('CORS Allowed Origins', 'bkgt-api'); ?></th>
                        <td>
                            <textarea name="cors_allowed_origins" rows="3" cols="50"><?php echo esc_textarea(implode("\n", $settings['cors_allowed_origins'])); ?></textarea>
                            <p class="description"><?php _e('Allowed origins for CORS (one per line). Leave empty to allow all origins.', 'bkgt-api'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Debug Mode', 'bkgt-api'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="api_debug_mode" value="1" <?php checked($settings['api_debug_mode']); ?>>
                                <?php _e('Enable debug mode', 'bkgt-api'); ?>
                            </label>
                            <p class="description"><?php _e('Show detailed error messages in API responses.', 'bkgt-api'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('API Logging', 'bkgt-api'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="api_logging_enabled" value="1" <?php checked($settings['api_logging_enabled']); ?>>
                                <?php _e('Enable API request logging', 'bkgt-api'); ?>
                            </label>
                            <p class="description"><?php _e('Log all API requests for monitoring and debugging.', 'bkgt-api'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('API Caching', 'bkgt-api'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="api_cache_enabled" value="1" <?php checked($settings['api_cache_enabled']); ?>>
                                <?php _e('Enable API response caching', 'bkgt-api'); ?>
                            </label>
                            <p class="description"><?php _e('Cache API responses to improve performance.', 'bkgt-api'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Cache TTL', 'bkgt-api'); ?></th>
                        <td>
                            <input type="number" name="api_cache_ttl" value="<?php echo esc_attr($settings['api_cache_ttl']); ?>" min="60" max="86400">
                            <p class="description"><?php _e('Cache time-to-live in seconds (default: 300 = 5 minutes).', 'bkgt-api'); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'bkgt-api'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render stats cards
     */
    private function render_stats_cards() {
        global $wpdb;

        $stats = array(
            'total_requests' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_api_logs"),
            'requests_today' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_api_logs WHERE DATE(created_at) = %s",
                current_time('Y-m-d')
            )),
            'active_keys' => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_api_keys WHERE is_active = 1"
            ),
            'error_rate' => $this->calculate_error_rate(),
        );

        $cards = array(
            array(
                'title' => __('Total Requests', 'bkgt-api'),
                'value' => number_format($stats['total_requests']),
                'icon' => 'dashicons-chart-line',
            ),
            array(
                'title' => __('Today\'s Requests', 'bkgt-api'),
                'value' => number_format($stats['requests_today']),
                'icon' => 'dashicons-calendar',
            ),
            array(
                'title' => __('Active API Keys', 'bkgt-api'),
                'value' => $stats['active_keys'],
                'icon' => 'dashicons-admin-network',
            ),
            array(
                'title' => __('Error Rate', 'bkgt-api'),
                'value' => number_format($stats['error_rate'], 1) . '%',
                'icon' => 'dashicons-warning',
            ),
        );

        foreach ($cards as $card) {
            ?>
            <div class="bkgt-api-stat-card">
                <div class="bkgt-api-stat-icon">
                    <span class="dashicons <?php echo esc_attr($card['icon']); ?>"></span>
                </div>
                <div class="bkgt-api-stat-content">
                    <h3><?php echo esc_html($card['value']); ?></h3>
                    <p><?php echo esc_html($card['title']); ?></p>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Render recent activity
     */
    private function render_recent_activity() {
        global $wpdb;

        $recent_logs = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name FROM {$wpdb->prefix}bkgt_api_logs l
             LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
             ORDER BY l.created_at DESC LIMIT %d",
            10
        ));

        if (empty($recent_logs)) {
            echo '<p>' . __('No recent activity.', 'bkgt-api') . '</p>';
            return;
        }

        ?>
        <div class="bkgt-api-activity-list">
            <?php foreach ($recent_logs as $log): ?>
                <div class="bkgt-api-activity-item">
                    <div class="bkgt-api-activity-method <?php echo esc_attr(strtolower($log->method)); ?>">
                        <?php echo esc_html($log->method); ?>
                    </div>
                    <div class="bkgt-api-activity-details">
                        <div class="bkgt-api-activity-endpoint"><?php echo esc_html($log->endpoint); ?></div>
                        <div class="bkgt-api-activity-meta">
                            <?php if ($log->display_name): ?>
                                <span class="bkgt-api-activity-user"><?php echo esc_html($log->display_name); ?></span>
                            <?php endif; ?>
                            <span class="bkgt-api-activity-time"><?php echo esc_html(human_time_diff(strtotime($log->created_at))); ?> ago</span>
                            <span class="bkgt-api-activity-ip"><?php echo esc_html($log->ip_address); ?></span>
                        </div>
                    </div>
                    <div class="bkgt-api-activity-status status-<?php echo esc_attr($log->response_code >= 400 ? 'error' : 'success'); ?>">
                        <?php echo esc_html($log->response_code); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render notifications
     */
    private function render_notifications() {
        $notifications = new BKGT_API_Notifications();
        $unread_notifications = $notifications->get_unread_notifications(5);

        if (empty($unread_notifications)) {
            echo '<p>' . __('No unread notifications.', 'bkgt-api') . '</p>';
            return;
        }

        ?>
        <div class="bkgt-api-notifications-list">
            <?php foreach ($unread_notifications as $notification): ?>
                <div class="bkgt-api-notification-item notification-<?php echo esc_attr($notification->severity); ?>">
                    <div class="bkgt-api-notification-header">
                        <h4><?php echo esc_html($notification->title); ?></h4>
                        <button class="bkgt-api-mark-read" data-id="<?php echo esc_attr($notification->id); ?>">
                            <span class="dashicons dashicons-dismiss"></span>
                        </button>
                    </div>
                    <div class="bkgt-api-notification-content">
                        <?php echo wp_kses_post(wpautop($notification->message)); ?>
                    </div>
                    <div class="bkgt-api-notification-meta">
                        <span class="bkgt-api-notification-time"><?php echo esc_html(human_time_diff(strtotime($notification->created_at))); ?> ago</span>
                        <span class="bkgt-api-notification-type"><?php echo esc_html(ucfirst($notification->type)); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render API keys list
     */
    private function render_api_keys_list() {
        $auth = new BKGT_API_Auth();
        $current_user_id = get_current_user_id();
        $api_keys = $auth->get_user_api_keys($current_user_id);

        if (empty($api_keys)) {
            echo '<p>' . __('No API keys found.', 'bkgt-api') . '</p>';
            return;
        }

        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Name', 'bkgt-api'); ?></th>
                    <th><?php _e('API Key', 'bkgt-api'); ?></th>
                    <th><?php _e('Permissions', 'bkgt-api'); ?></th>
                    <th><?php _e('Created', 'bkgt-api'); ?></th>
                    <th><?php _e('Last Used', 'bkgt-api'); ?></th>
                    <th><?php _e('Status', 'bkgt-api'); ?></th>
                    <th><?php _e('Actions', 'bkgt-api'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($api_keys as $key): ?>
                    <tr>
                        <td><?php echo esc_html($key->name); ?></td>
                        <td>
                            <code><?php echo esc_html(substr($key->api_key, 0, 20) . '...'); ?></code>
                            <button class="button button-small bkgt-api-toggle-key" data-full-key="<?php echo esc_attr($key->api_key); ?>">
                                <?php _e('Show', 'bkgt-api'); ?>
                            </button>
                        </td>
                        <td><?php echo esc_html($key->permissions ? implode(', ', json_decode($key->permissions, true)) : 'None'); ?></td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($key->created_at))); ?></td>
                        <td><?php echo $key->last_used ? esc_html(date_i18n(get_option('date_format'), strtotime($key->last_used))) : __('Never', 'bkgt-api'); ?></td>
                        <td>
                            <span class="bkgt-api-status status-<?php echo $key->is_active ? 'active' : 'inactive'; ?>">
                                <?php echo $key->is_active ? __('Active', 'bkgt-api') : __('Inactive', 'bkgt-api'); ?>
                            </span>
                        </td>
                        <td>
                            <button class="button button-small bkgt-api-revoke-key" data-key-id="<?php echo esc_attr($key->id); ?>">
                                <?php _e('Revoke', 'bkgt-api'); ?>
                            </button>
                            <button class="button button-small button-link-delete bkgt-api-delete-key" data-key-id="<?php echo esc_attr($key->id); ?>" style="color: #dc3232; margin-left: 5px;">
                                <?php _e('Delete', 'bkgt-api'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render logs table
     */
    private function render_logs_table() {
        global $wpdb;

        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name FROM {$wpdb->prefix}bkgt_api_logs l
             LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
             ORDER BY l.created_at DESC LIMIT %d",
            50
        ));

        if (empty($logs)) {
            echo '<p>' . __('No logs found.', 'bkgt-api') . '</p>';
            return;
        }

        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Time', 'bkgt-api'); ?></th>
                    <th><?php _e('Method', 'bkgt-api'); ?></th>
                    <th><?php _e('Endpoint', 'bkgt-api'); ?></th>
                    <th><?php _e('User', 'bkgt-api'); ?></th>
                    <th><?php _e('IP Address', 'bkgt-api'); ?></th>
                    <th><?php _e('Status', 'bkgt-api'); ?></th>
                    <th><?php _e('Response Time', 'bkgt-api'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html(date_i18n('Y-m-d H:i:s', strtotime($log->created_at))); ?></td>
                        <td>
                            <span class="bkgt-api-method method-<?php echo esc_attr(strtolower($log->method)); ?>">
                                <?php echo esc_html($log->method); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($log->endpoint); ?></td>
                        <td><?php echo esc_html($log->display_name ?: __('Anonymous', 'bkgt-api')); ?></td>
                        <td><?php echo esc_html($log->ip_address); ?></td>
                        <td>
                            <span class="bkgt-api-status status-<?php echo $log->response_code >= 400 ? 'error' : 'success'; ?>">
                                <?php echo esc_html($log->response_code); ?>
                            </span>
                        </td>
                        <td><?php echo $log->response_time ? esc_html(number_format($log->response_time, 3) . 's') : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render security overview
     */
    private function render_security_overview() {
        $security = new BKGT_API_Security();
        $stats = $security->get_security_stats();

        ?>
        <div class="bkgt-api-security-stats">
            <div class="bkgt-api-stat-item">
                <span class="bkgt-api-stat-label"><?php _e('Total Security Events', 'bkgt-api'); ?>:</span>
                <span class="bkgt-api-stat-value"><?php echo number_format($stats['total_events']); ?></span>
            </div>
            <div class="bkgt-api-stat-item">
                <span class="bkgt-api-stat-label"><?php _e('Events Today', 'bkgt-api'); ?>:</span>
                <span class="bkgt-api-stat-value"><?php echo number_format($stats['events_today']); ?></span>
            </div>
            <div class="bkgt-api-stat-item">
                <span class="bkgt-api-stat-label"><?php _e('Blocked IPs', 'bkgt-api'); ?>:</span>
                <span class="bkgt-api-stat-value"><?php echo number_format($stats['blocked_ips']); ?></span>
            </div>
            <div class="bkgt-api-stat-item">
                <span class="bkgt-api-stat-label"><?php _e('High Severity Events (7 days)', 'bkgt-api'); ?>:</span>
                <span class="bkgt-api-stat-value"><?php echo number_format($stats['high_severity_events']); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Render security logs
     */
    private function render_security_logs() {
        global $wpdb;

        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name FROM {$wpdb->prefix}bkgt_security_logs l
             LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
             ORDER BY l.created_at DESC LIMIT %d",
            20
        ));

        if (empty($logs)) {
            echo '<p>' . __('No security logs found.', 'bkgt-api') . '</p>';
            return;
        }

        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Time', 'bkgt-api'); ?></th>
                    <th><?php _e('Event', 'bkgt-api'); ?></th>
                    <th><?php _e('Severity', 'bkgt-api'); ?></th>
                    <th><?php _e('User', 'bkgt-api'); ?></th>
                    <th><?php _e('IP Address', 'bkgt-api'); ?></th>
                    <th><?php _e('Details', 'bkgt-api'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html(date_i18n('Y-m-d H:i:s', strtotime($log->created_at))); ?></td>
                        <td><?php echo esc_html($log->event_type); ?></td>
                        <td>
                            <span class="bkgt-api-severity severity-<?php echo esc_attr($log->severity); ?>">
                                <?php echo esc_html(ucfirst($log->severity)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($log->display_name ?: __('System', 'bkgt-api')); ?></td>
                        <td><?php echo esc_html($log->ip_address); ?></td>
                        <td>
                            <button class="button button-small bkgt-api-show-details" data-details="<?php echo esc_attr($log->event_data); ?>">
                                <?php _e('Show Details', 'bkgt-api'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render blocked IPs
     */
    private function render_blocked_ips() {
        $blocked_ips = get_option('bkgt_blocked_ips', array());

        if (empty($blocked_ips)) {
            echo '<p>' . __('No IPs are currently blocked.', 'bkgt-api') . '</p>';
            return;
        }

        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('IP Address', 'bkgt-api'); ?></th>
                    <th><?php _e('Reason', 'bkgt-api'); ?></th>
                    <th><?php _e('Blocked At', 'bkgt-api'); ?></th>
                    <th><?php _e('Expires At', 'bkgt-api'); ?></th>
                    <th><?php _e('Actions', 'bkgt-api'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blocked_ips as $ip => $data): ?>
                    <tr>
                        <td><?php echo esc_html($ip); ?></td>
                        <td><?php echo esc_html($data['reason']); ?></td>
                        <td><?php echo esc_html(date_i18n('Y-m-d H:i:s', $data['blocked_at'])); ?></td>
                        <td><?php echo esc_html(date_i18n('Y-m-d H:i:s', $data['expires_at'])); ?></td>
                        <td>
                            <button class="button button-small bkgt-api-unblock-ip" data-ip="<?php echo esc_attr($ip); ?>">
                                <?php _e('Unblock', 'bkgt-api'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Calculate error rate
     */
    private function calculate_error_rate() {
        global $wpdb;

        $total_requests = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_api_logs");
        if (!$total_requests) {
            return 0;
        }

        $error_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_api_logs WHERE response_code >= %d",
            400
        ));

        return round(($error_requests / $total_requests) * 100, 1);
    }

    /**
     * Save settings
     */
    private function save_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'bkgt_api_settings')) {
            wp_die(__('Security check failed.', 'bkgt-api'));
        }

        $settings = array(
            'jwt_expiry',
            'refresh_token_expiry',
            'api_rate_limit',
            'api_rate_limit_window',
            'cors_allowed_origins',
            'api_debug_mode',
            'api_logging_enabled',
            'api_cache_enabled',
            'api_cache_ttl',
        );

        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                $value = $_POST[$setting];

                if ($setting === 'cors_allowed_origins') {
                    $value = array_filter(array_map('trim', explode("\n", $value)));
                } elseif (in_array($setting, array('api_debug_mode', 'api_logging_enabled', 'api_cache_enabled'))) {
                    $value = (bool) $value;
                } else {
                    $value = intval($value);
                }

                update_option($setting, $value);
            }
        }
    }

    /**
     * AJAX handlers
     */
    public function ajax_create_api_key() {
        check_ajax_referer('bkgt_api_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
        }

        $name = sanitize_text_field($_POST['key_name']);
        $permissions = isset($_POST['key_permissions']) ? $_POST['key_permissions'] : array();

        if (empty($name)) {
            wp_send_json_error(__('Key name is required.', 'bkgt-api'));
        }

        $auth = new BKGT_API_Auth();
        $api_key = $auth->create_api_key(get_current_user_id(), $name, $permissions);

        if (!$api_key) {
            wp_send_json_error(__('Failed to create API key.', 'bkgt-api'));
        }

        do_action('bkgt_api_key_created', get_current_user_id(), array('name' => $name));

        wp_send_json_success(array(
            'message' => __('API key created successfully.', 'bkgt-api'),
            'api_key' => $api_key,
        ));
    }

    public function ajax_revoke_api_key() {
        check_ajax_referer('bkgt_api_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
        }

        $key_id = intval($_POST['key_id']);

        $auth = new BKGT_API_Auth();
        $result = $auth->revoke_api_key($key_id, get_current_user_id());

        if (!$result) {
            wp_send_json_error(__('Failed to revoke API key.', 'bkgt-api'));
        }

        do_action('bkgt_api_key_revoked', get_current_user_id(), array('id' => $key_id));

        wp_send_json_success(__('API key revoked successfully.', 'bkgt-api'));
    }

    public function ajax_delete_api_key() {
        error_log('BKGT API: ajax_delete_api_key called');
        check_ajax_referer('bkgt_api_admin_nonce');
        error_log('BKGT API: nonce verified');

        if (!current_user_can('manage_options')) {
            error_log('BKGT API: insufficient permissions');
            wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
        }

        $key_id = intval($_POST['key_id']);
        error_log('BKGT API: deleting key ID: ' . $key_id);

        $auth = new BKGT_API_Auth();
        $result = $auth->delete_api_key($key_id, get_current_user_id());
        error_log('BKGT API: delete result: ' . ($result ? 'success' : 'failed'));

        if ($result === false) {
            wp_send_json_error(__('Failed to delete API key.', 'bkgt-api'));
        }

        do_action('bkgt_api_key_deleted', get_current_user_id(), array('id' => $key_id));

        wp_send_json_success(__('API key deleted permanently.', 'bkgt-api'));
    }

    public function ajax_get_logs() {
        check_ajax_referer('bkgt_api_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
        }

        // Implementation for filtered logs
        wp_send_json_success();
    }

    public function ajax_get_security_logs() {
        check_ajax_referer('bkgt_api_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
        }

        // Implementation for security logs
        wp_send_json_success();
    }

    public function ajax_mark_notification_read() {
        check_ajax_referer('bkgt_api_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
        }

        $notification_id = intval($_POST['notification_id']);

        $notifications = new BKGT_API_Notifications();
        $result = $notifications->mark_notification_read($notification_id);

        if (!$result) {
            wp_send_json_error(__('Failed to mark notification as read.', 'bkgt-api'));
        }

        wp_send_json_success(__('Notification marked as read.', 'bkgt-api'));
    }

    public function ajax_get_stats() {
        check_ajax_referer('bkgt_api_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'bkgt-api'));
        }

        // Implementation for real-time stats
        wp_send_json_success();
    }

    /**
     * Diagnostic page
     */
    public function diagnostic_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('BKGT API Diagnostic', 'bkgt-api'); ?></h1>
            
            <div class="bkgt-diagnostic-controls" style="margin: 20px 0; background: #f1f1f1; padding: 15px; border-radius: 4px;">
                <button id="bkgt-test-all-endpoints" class="button button-primary" style="font-size: 14px; padding: 8px 20px;">
                    <?php _e('Testa alla slutpunkter', 'bkgt-api'); ?>
                </button>
                <span id="bkgt-test-status" style="margin-left: 20px; display: none;">
                    <span class="spinner" style="float: none; visibility: visible; margin: 0 5px 0 0;"></span>
                    <span id="bkgt-test-message"></span>
                </span>
            </div>
            
            <div id="bkgt-test-results" style="margin: 20px 0;"></div>
            
            <?php $this->render_diagnostic_section(); ?>
        </div>
        
        <style>
            .bkgt-endpoint-result {
                margin: 10px 0;
                padding: 12px;
                border-left: 4px solid #ddd;
                background: #fafafa;
            }
            .bkgt-endpoint-result.success {
                border-left-color: #46b450;
                background: #f0f9f0;
            }
            .bkgt-endpoint-result.error {
                border-left-color: #dc3545;
                background: #fef5f5;
            }
            .bkgt-endpoint-result.pending {
                border-left-color: #0073aa;
                background: #f0f6ff;
            }
            .bkgt-endpoint-name {
                font-weight: bold;
                margin-bottom: 5px;
            }
            .bkgt-endpoint-details {
                font-size: 12px;
                color: #666;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var endpoints = [
                {
                    name: 'Teams List',
                    method: 'GET',
                    endpoint: '/wp-json/bkgt/v1/teams'
                },
                {
                    name: 'Inventory Items',
                    method: 'GET',
                    endpoint: '/wp-json/bkgt/v1/inventory/items'
                },
                {
                    name: 'API Status',
                    method: 'GET',
                    endpoint: '/wp-json/bkgt/v1/status'
                },
                {
                    name: 'Documents',
                    method: 'GET',
                    endpoint: '/wp-json/bkgt/v1/documents'
                }
            ];
            
            $('#bkgt-test-all-endpoints').on('click', function() {
                var $button = $(this);
                var $status = $('#bkgt-test-status');
                var $results = $('#bkgt-test-results');
                
                $button.prop('disabled', true);
                $status.show();
                $results.html('');
                
                var completed = 0;
                var total = endpoints.length;
                
                endpoints.forEach(function(endpoint, index) {
                    var $result = $('<div class="bkgt-endpoint-result pending"></div>')
                        .append('<div class="bkgt-endpoint-name">' + endpoint.name + ' (' + endpoint.method + ')</div>')
                        .append('<div class="bkgt-endpoint-details">Testing...</div>');
                    
                    $results.append($result);
                    
                    var url = window.location.origin + endpoint.endpoint;
                    
                    $.ajax({
                        url: url,
                        method: endpoint.method,
                        timeout: 5000,
                        headers: {
                            'X-API-Key': '<?php echo esc_js(get_option('bkgt_api_key', '')); ?>'
                        }
                    }).done(function(data) {
                        $result.removeClass('pending').addClass('success');
                        $result.find('.bkgt-endpoint-details').html(
                            'Status: 200 OK<br>' +
                            'Response: ' + (typeof data === 'string' ? data.substring(0, 100) : JSON.stringify(data).substring(0, 100)) + '...'
                        );
                    }).fail(function(xhr) {
                        $result.removeClass('pending').addClass('error');
                        var errorMsg = 'Status: ' + xhr.status + ' ' + xhr.statusText;
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg += '<br>Message: ' + xhr.responseJSON.message;
                        }
                        $result.find('.bkgt-endpoint-details').html(errorMsg);
                    }).always(function() {
                        completed++;
                        if (completed === total) {
                            $button.prop('disabled', false);
                            $status.hide();
                            $('#bkgt-test-message').text('Testing complete');
                        }
                    });
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render diagnostic section
     */
    private function render_diagnostic_section() {
        ?>
        <div class="bkgt-api-diagnostic-section">
            <h2><?php _e('Plugin Status', 'bkgt-api'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Plugin', 'bkgt-api'); ?></th>
                        <th><?php _e('Status', 'bkgt-api'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $plugins = array(
                        'bkgt-core/bkgt-core.php' => 'BKGT Core',
                        'bkgt-data-scraping/bkgt-data-scraping.php' => 'BKGT Data Scraping',
                        'bkgt-inventory/bkgt-inventory.php' => 'BKGT Inventory',
                        'bkgt-api/bkgt-api.php' => 'BKGT API'
                    );

                    foreach ($plugins as $file => $name) {
                        $active = is_plugin_active($file);
                        $status_class = $active ? 'bkgt-status-active' : 'bkgt-status-inactive';
                        $status_text = $active ? __('ACTIVE', 'bkgt-api') : __('INACTIVE', 'bkgt-api');
                        ?>
                        <tr>
                            <td><?php echo esc_html($name); ?></td>
                            <td><span class="bkgt-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="bkgt-api-diagnostic-section">
            <h2><?php _e('Database Tables', 'bkgt-api'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Table', 'bkgt-api'); ?></th>
                        <th><?php _e('Status', 'bkgt-api'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    global $wpdb;
                    $tables = array(
                        'bkgt_inventory_items',
                        'bkgt_manufacturers',
                        'bkgt_item_types',
                        'bkgt_inventory_assignments',
                        'bkgt_locations'
                    );

                    foreach ($tables as $table) {
                        $table_name = $wpdb->prefix . $table;
                        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
                        $status_class = $exists ? 'bkgt-status-active' : 'bkgt-status-inactive';
                        $status_text = $exists ? __('EXISTS', 'bkgt-api') : __('MISSING', 'bkgt-api');
                        ?>
                        <tr>
                            <td><?php echo esc_html($table); ?></td>
                            <td><span class="bkgt-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="bkgt-api-diagnostic-section">
            <h2><?php _e('API Endpoints', 'bkgt-api'); ?></h2>
            <p><em><?php _e('Note: Most endpoints require authentication. HTTP 401 is expected for unauthenticated requests.', 'bkgt-api'); ?></em></p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Endpoint', 'bkgt-api'); ?></th>
                        <th><?php _e('Status', 'bkgt-api'); ?></th>
                        <th><?php _e('Expected', 'bkgt-api'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $endpoints = array(
                        'wp-json/bkgt/v1/equipment' => 'Requires authentication',
                        'wp-json/bkgt/v1/equipment/preview-identifier' => 'Requires authentication',
                        'wp-json/bkgt/v1/equipment/manufacturers' => 'Requires authentication',
                        'wp-json/bkgt/v1/equipment/types' => 'Requires authentication'
                    );

                    foreach ($endpoints as $endpoint => $description) {
                        $url = home_url($endpoint);
                        $response = wp_remote_head($url);
                        $status = wp_remote_retrieve_response_code($response);
                        $is_expected = ($status == 401);
                        $status_class = $is_expected ? 'bkgt-status-active' : 'bkgt-status-inactive';
                        $expected_text = $is_expected ? __('✅ Expected (401)', 'bkgt-api') : __('❌ Unexpected', 'bkgt-api');
                        ?>
                        <tr>
                            <td><?php echo esc_html($endpoint); ?><br><small><?php echo esc_html($description); ?></small></td>
                            <td><span class="bkgt-status <?php echo esc_attr($status_class); ?>">HTTP <?php echo esc_html($status); ?></span></td>
                            <td><?php echo $expected_text; ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="bkgt-api-diagnostic-section">
            <h2><?php _e('Inventory Items', 'bkgt-api'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'bkgt-api'); ?></th>
                        <th><?php _e('Identifier', 'bkgt-api'); ?></th>
                        <th><?php _e('Title', 'bkgt-api'); ?></th>
                        <th><?php _e('Manufacturer', 'bkgt-api'); ?></th>
                        <th><?php _e('Type', 'bkgt-api'); ?></th>
                        <th><?php _e('Status', 'bkgt-api'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    global $wpdb;
                    $items = $wpdb->get_results(
                        "SELECT i.id, i.unique_identifier, i.title, m.name as manufacturer_name, it.name as item_type_name, i.condition_status
                         FROM {$wpdb->prefix}bkgt_inventory_items i
                         LEFT JOIN {$wpdb->prefix}bkgt_manufacturers m ON i.manufacturer_id = m.id
                         LEFT JOIN {$wpdb->prefix}bkgt_item_types it ON i.item_type_id = it.id
                         ORDER BY i.id DESC LIMIT 10"
                    );

                    if (empty($items)) {
                        echo '<tr><td colspan="6">' . __('No inventory items found.', 'bkgt-api') . '</td></tr>';
                    } else {
                        foreach ($items as $item) {
                            $status_class = $item->condition_status === 'normal' ? 'bkgt-status-active' : 'bkgt-status-inactive';
                            ?>
                            <tr>
                                <td><?php echo esc_html($item->id); ?></td>
                                <td><code><?php echo esc_html($item->unique_identifier); ?></code></td>
                                <td><?php echo esc_html($item->title); ?></td>
                                <td><?php echo esc_html($item->manufacturer_name ?: 'Unknown'); ?></td>
                                <td><?php echo esc_html($item->item_type_name ?: 'Unknown'); ?></td>
                                <td><span class="bkgt-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($item->condition_status); ?></span></td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
            <?php if (!empty($items)): ?>
            <p><em><?php _e('Showing last 10 items. Total items:', 'bkgt-api'); ?> <?php echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkgt_inventory_items"); ?></em></p>
            <?php endif; ?>
        </div>

        <div class="bkgt-api-diagnostic-section">
            <h2><?php _e('Class Availability', 'bkgt-api'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Class', 'bkgt-api'); ?></th>
                        <th><?php _e('Status', 'bkgt-api'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $classes = array(
                        'BKGT_Inventory_Item',
                        'BKGT_Manufacturer',
                        'BKGT_Item_Type',
                        'BKGT_Assignment'
                    );

                    foreach ($classes as $class) {
                        $exists = class_exists($class);
                        $status_class = $exists ? 'bkgt-status-active' : 'bkgt-status-inactive';
                        $status_text = $exists ? __('EXISTS', 'bkgt-api') : __('MISSING', 'bkgt-api');
                        ?>
                        <tr>
                            <td><?php echo esc_html($class); ?></td>
                            <td><span class="bkgt-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Updates management page
     */
    public function updates_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        ?>
        <div class="wrap">
            <h1><?php _e('BKGT API - Updates Management', 'bkgt-api'); ?></h1>

            <div class="bkgt-api-updates-container">
                <!-- Upload New Update Section -->
                <div class="bkgt-api-section">
                    <h2><?php _e('Upload New Update', 'bkgt-api'); ?></h2>
                    <form id="bkgt-update-upload-form" enctype="multipart/form-data">
                        <?php wp_nonce_field('bkgt_update_upload', 'bkgt_update_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="update_version"><?php _e('Version', 'bkgt-api'); ?> *</label></th>
                                <td>
                                    <input type="text" id="update_version" name="version" class="regular-text" pattern="\d+\.\d+\.\d+" required />
                                    <p class="description"><?php _e('Semantic version (e.g., 1.2.3)', 'bkgt-api'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="update_platform"><?php _e('Platform', 'bkgt-api'); ?> *</label></th>
                                <td>
                                    <select id="update_platform" name="platform" required>
                                        <option value="win32-x64"><?php _e('Windows 64-bit', 'bkgt-api'); ?></option>
                                        <option value="darwin-x64"><?php _e('macOS Intel', 'bkgt-api'); ?></option>
                                        <option value="darwin-arm64"><?php _e('macOS Apple Silicon', 'bkgt-api'); ?></option>
                                        <option value="linux-x64"><?php _e('Linux 64-bit', 'bkgt-api'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="update_changelog"><?php _e('Changelog', 'bkgt-api'); ?></label></th>
                                <td>
                                    <textarea id="update_changelog" name="changelog" rows="4" class="large-text"></textarea>
                                    <p class="description"><?php _e('Describe the changes in this update', 'bkgt-api'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="update_critical"><?php _e('Critical Update', 'bkgt-api'); ?></label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="update_critical" name="critical" value="1" />
                                        <?php _e('This is a critical security update', 'bkgt-api'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="update_minimum_version"><?php _e('Minimum Version', 'bkgt-api'); ?></label></th>
                                <td>
                                    <input type="text" id="update_minimum_version" name="minimum_version" class="regular-text" pattern="\d+\.\d+\.\d+" />
                                    <p class="description"><?php _e('Minimum version required to install this update (optional)', 'bkgt-api'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="update_file"><?php _e('Update File', 'bkgt-api'); ?> *</label></th>
                                <td>
                                    <input type="file" id="update_file" name="file" accept=".exe,.dmg,.AppImage,.zip" required />
                                    <p class="description"><?php _e('Upload the update package file (max 500MB)', 'bkgt-api'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary" id="bkgt-update-upload-btn">
                                <?php _e('Upload Update', 'bkgt-api'); ?>
                            </button>
                            <span id="bkgt-update-upload-status"></span>
                        </p>
                    </form>
                </div>

                <!-- Updates List Section -->
                <div class="bkgt-api-section">
                    <h2><?php _e('Available Updates', 'bkgt-api'); ?></h2>
                    <div id="bkgt-updates-list">
                        <p><?php _e('Loading updates...', 'bkgt-api'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for uploading updates
     */
    public function ajax_upload_update() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'bkgt_update_upload')) {
                throw new Exception(__('Security check failed', 'bkgt-api'));
            }

            // Check permissions
            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'bkgt-api'));
            }

            // Validate required fields
            $required_fields = array('version', 'platform');
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception(sprintf(__('Field "%s" is required', 'bkgt-api'), $field));
                }
            }

            // Validate version format
            if (!preg_match('/^\d+\.\d+\.\d+$/', $_POST['version'])) {
                throw new Exception(__('Version must follow semantic versioning (x.y.z)', 'bkgt-api'));
            }

            // Prepare data
            $data = array(
                'version' => sanitize_text_field($_POST['version']),
                'platform' => sanitize_text_field($_POST['platform']),
                'changelog' => sanitize_textarea_field($_POST['changelog'] ?? ''),
                'critical' => isset($_POST['critical']) ? 1 : 0,
                'minimum_version' => sanitize_text_field($_POST['minimum_version'] ?? null),
            );

            // Handle file upload
            if (empty($_FILES['file'])) {
                throw new Exception(__('No file uploaded', 'bkgt-api'));
            }

            $file = $_FILES['file'];

            // Get updates handler
            $updates = bkgt_api()->updates;
            if (!$updates) {
                throw new Exception(__('Updates service unavailable', 'bkgt-api'));
            }

            $result = $updates->upload_update_package(
                $data['version'],
                $data['platform'],
                $file,
                $data['changelog'],
                $data['critical'],
                $data['minimum_version']
            );

            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            wp_send_json_success(array(
                'message' => __('Update uploaded successfully', 'bkgt-api'),
                'data' => $result
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * AJAX handler for getting updates list
     */
    public function ajax_get_updates() {
        try {
            // Check permissions
            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'bkgt-api'));
            }

            $page = intval($_POST['page'] ?? 1);
            $per_page = intval($_POST['per_page'] ?? 20);

            // Get updates handler
            $updates = bkgt_api()->updates;
            if (!$updates) {
                throw new Exception(__('Updates service unavailable', 'bkgt-api'));
            }

            $result = $updates->get_admin_updates($page, $per_page);

            wp_send_json_success($result);

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * AJAX handler for deactivating updates
     */
    public function ajax_deactivate_update() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'bkgt_update_deactivate')) {
                throw new Exception(__('Security check failed', 'bkgt-api'));
            }

            // Check permissions
            if (!current_user_can('manage_options')) {
                throw new Exception(__('Insufficient permissions', 'bkgt-api'));
            }

            $version = sanitize_text_field($_POST['version'] ?? '');
            if (empty($version)) {
                throw new Exception(__('Version is required', 'bkgt-api'));
            }

            // Get updates handler
            $updates = bkgt_api()->updates;
            if (!$updates) {
                throw new Exception(__('Updates service unavailable', 'bkgt-api'));
            }

            $deactivated = $updates->deactivate_update($version);

            if (!$deactivated) {
                throw new Exception(__('Failed to deactivate update', 'bkgt-api'));
            }

            wp_send_json_success(array(
                'message' => sprintf(__('Update version %s deactivated successfully', 'bkgt-api'), $version)
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
}