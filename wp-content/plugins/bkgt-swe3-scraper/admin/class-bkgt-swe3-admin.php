<?php
/**
 * SWE3 scraper admin class
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_SWE3_Admin {

    /**
     * Admin page hook
     */
    private $admin_page_hook;

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_bkgt_swe3_manual_scrape', array($this, 'handle_manual_scrape'));
        add_action('wp_ajax_bkgt_swe3_update_schedule', array($this, 'handle_update_schedule'));
        add_action('wp_ajax_bkgt_swe3_toggle_scraping', array($this, 'handle_toggle_scraping'));
        add_filter('plugin_action_links_' . BKGT_SWE3_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        $this->admin_page_hook = add_management_page(
            'SWE3 Document Scraper',
            'SWE3 Scraper',
            'manage_options',
            'bkgt-swe3-scraper',
            array($this, 'admin_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('bkgt_swe3_settings', 'bkgt_swe3_scrape_enabled');
        register_setting('bkgt_swe3_settings', 'bkgt_swe3_scrape_hour');
        register_setting('bkgt_swe3_settings', 'bkgt_swe3_scrape_minute');
        register_setting('bkgt_swe3_settings', 'bkgt_swe3_log_level');
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== $this->admin_page_hook) {
            return;
        }

        wp_enqueue_script(
            'bkgt-swe3-admin',
            BKGT_SWE3_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            BKGT_SWE3_VERSION,
            true
        );

        wp_enqueue_style(
            'bkgt-swe3-admin',
            BKGT_SWE3_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            BKGT_SWE3_VERSION
        );

        wp_localize_script('bkgt-swe3-admin', 'bkgt_swe3_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkgt_swe3_admin_nonce'),
            'strings' => array(
                'scraping' => __('Starting scrape...', 'bkgt-swe3-scraper'),
                'success' => __('Success!', 'bkgt-swe3-scraper'),
                'error' => __('Error occurred', 'bkgt-swe3-scraper'),
                'confirm_scrape' => __('Are you sure you want to run a manual scrape?', 'bkgt-swe3-scraper'),
            )
        ));
    }

    /**
     * Admin page content
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $scheduler = bkgt_swe3_scraper()->scheduler;
        $dms_integration = bkgt_swe3_scraper()->dms_integration;

        $status = $scheduler->get_scheduler_status();
        $stats = $dms_integration->get_document_statistics();

        ?>
        <div class="wrap">
            <h1><?php _e('SWE3 Document Scraper', 'bkgt-swe3-scraper'); ?></h1>

            <div class="bkgt-swe3-admin-container">
                <!-- Status Section -->
                <div class="bkgt-swe3-section">
                    <h2><?php _e('Status', 'bkgt-swe3-scraper'); ?></h2>
                    <table class="widefat">
                        <tbody>
                            <tr>
                                <td><strong><?php _e('Scraping Enabled', 'bkgt-swe3-scraper'); ?>:</strong></td>
                                <td>
                                    <span class="bkgt-status-<?php echo $status['enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <?php echo $status['enabled'] ? __('Yes', 'bkgt-swe3-scraper') : __('No', 'bkgt-swe3-scraper'); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Next Scheduled Run', 'bkgt-swe3-scraper'); ?>:</strong></td>
                                <td><?php echo $status['next_run'] ? $status['next_run'] : __('Not scheduled', 'bkgt-swe3-scraper'); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Last Scrape', 'bkgt-swe3-scraper'); ?>:</strong></td>
                                <td><?php echo $status['last_scrape'] !== 'never' ? $status['last_scrape'] : __('Never', 'bkgt-swe3-scraper'); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Last Successful Scrape', 'bkgt-swe3-scraper'); ?>:</strong></td>
                                <td><?php echo $status['last_successful_scrape'] !== 'never' ? $status['last_successful_scrape'] : __('Never', 'bkgt-swe3-scraper'); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php _e('Consecutive Failures', 'bkgt-swe3-scraper'); ?>:</strong></td>
                                <td>
                                    <span class="<?php echo $status['failure_count'] > 0 ? 'bkgt-status-error' : 'bkgt-status-ok'; ?>">
                                        <?php echo $status['failure_count']; ?>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Statistics Section -->
                <div class="bkgt-swe3-section">
                    <h2><?php _e('Document Statistics', 'bkgt-swe3-scraper'); ?></h2>
                    <table class="widefat">
                        <tbody>
                            <tr>
                                <td><strong><?php _e('Total SWE3 Documents', 'bkgt-swe3-scraper'); ?>:</strong></td>
                                <td><?php echo $stats['total_documents']; ?></td>
                            </tr>
                            <?php if (!empty($stats['documents_by_type'])): ?>
                                <?php foreach ($stats['documents_by_type'] as $type => $count): ?>
                                    <tr>
                                        <td><strong><?php echo ucfirst(str_replace('-', ' ', $type)); ?>:</strong></td>
                                        <td><?php echo $count; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr>
                                <td><strong><?php _e('Last Document Update', 'bkgt-swe3-scraper'); ?>:</strong></td>
                                <td><?php echo $stats['last_updated'] ? $stats['last_updated'] : __('Never', 'bkgt-swe3-scraper'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Controls Section -->
                <div class="bkgt-swe3-section">
                    <h2><?php _e('Controls', 'bkgt-swe3-scraper'); ?></h2>

                    <form method="post" action="options.php">
                        <?php settings_fields('bkgt_swe3_settings'); ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Scraping', 'bkgt-swe3-scraper'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="bkgt_swe3_scrape_enabled" value="yes"
                                               <?php checked(get_option('bkgt_swe3_scrape_enabled', 'yes'), 'yes'); ?> />
                                        <?php _e('Enable automatic daily scraping', 'bkgt-swe3-scraper'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Scrape Time', 'bkgt-swe3-scraper'); ?></th>
                                <td>
                                    <select name="bkgt_swe3_scrape_hour">
                                        <?php for ($i = 0; $i < 24; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php selected($status['scrape_hour'], $i); ?>>
                                                <?php echo sprintf('%02d:00', $i); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    :
                                    <select name="bkgt_swe3_scrape_minute">
                                        <option value="0" <?php selected($status['scrape_minute'], 0); ?>>00</option>
                                        <option value="15" <?php selected($status['scrape_minute'], 15); ?>>15</option>
                                        <option value="30" <?php selected($status['scrape_minute'], 30); ?>>30</option>
                                        <option value="45" <?php selected($status['scrape_minute'], 45); ?>>45</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Log Level', 'bkgt-swe3-scraper'); ?></th>
                                <td>
                                    <select name="bkgt_swe3_log_level">
                                        <option value="debug" <?php selected(get_option('bkgt_swe3_log_level', 'info'), 'debug'); ?>>
                                            <?php _e('Debug', 'bkgt-swe3-scraper'); ?>
                                        </option>
                                        <option value="info" <?php selected(get_option('bkgt_swe3_log_level', 'info'), 'info'); ?>>
                                            <?php _e('Info', 'bkgt-swe3-scraper'); ?>
                                        </option>
                                        <option value="warning" <?php selected(get_option('bkgt_swe3_log_level', 'info'), 'warning'); ?>>
                                            <?php _e('Warning', 'bkgt-swe3-scraper'); ?>
                                        </option>
                                        <option value="error" <?php selected(get_option('bkgt_swe3_log_level', 'info'), 'error'); ?>>
                                            <?php _e('Error', 'bkgt-swe3-scraper'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button(__('Save Settings', 'bkgt-swe3-scraper')); ?>
                    </form>

                    <hr>

                    <h3><?php _e('Manual Actions', 'bkgt-swe3-scraper'); ?></h3>
                    <p>
                        <button id="bkgt-swe3-manual-scrape" class="button button-primary">
                            <?php _e('Run Manual Scrape', 'bkgt-swe3-scraper'); ?>
                        </button>
                        <span id="bkgt-swe3-scrape-status"></span>
                    </p>
                    <p class="description">
                        <?php _e('Manually trigger a scrape of the SWE3 website. This will run immediately regardless of schedule.', 'bkgt-swe3-scraper'); ?>
                    </p>
                </div>

                <!-- Recent Activity Section -->
                <div class="bkgt-swe3-section">
                    <h2><?php _e('Recent Activity', 'bkgt-swe3-scraper'); ?></h2>
                    <div id="bkgt-swe3-activity-log">
                        <?php $this->display_recent_activity(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle manual scrape AJAX request
     */
    public function handle_manual_scrape() {
        try {
            check_ajax_referer('bkgt_swe3_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => __('Insufficient permissions'),
                    'error_code' => 'insufficient_permissions',
                    'user_id' => get_current_user_id(),
                    'required_cap' => 'manage_options'
                ));
                return;
            }

            $scheduler = bkgt_swe3_scraper()->scheduler;
            if (!$scheduler) {
                wp_send_json_error(array(
                    'message' => __('Scheduler not available'),
                    'error_code' => 'scheduler_unavailable',
                    'scheduler_class' => 'BKGT_SWE3_Scheduler'
                ));
                return;
            }

            $result = $scheduler->trigger_manual_scrape();

            if (is_array($result) && isset($result['success'])) {
                wp_send_json($result);
            } else {
                wp_send_json_error(array(
                    'message' => __('Invalid response from scheduler'),
                    'error_code' => 'invalid_scheduler_response',
                    'response_type' => gettype($result),
                    'response_data' => $result
                ));
            }

        } catch (Exception $e) {
            $this->log('error', 'AJAX handler error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An unexpected error occurred: ' . $e->getMessage()),
                'error_code' => 'unexpected_exception',
                'exception_type' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
                'exception_trace' => $e->getTraceAsString(),
                'php_version' => PHP_VERSION,
                'wp_version' => get_bloginfo('version'),
                'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
            ));
        }
    }

    /**
     * Handle schedule update AJAX request
     */
    public function handle_update_schedule() {
        check_ajax_referer('bkgt_swe3_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions'));
        }

        $hour = intval($_POST['hour']);
        $minute = intval($_POST['minute']);

        $scheduler = bkgt_swe3_scraper()->scheduler;
        $result = $scheduler->update_schedule($hour, $minute);

        wp_send_json($result);
    }

    /**
     * Handle toggle scraping AJAX request
     */
    public function handle_toggle_scraping() {
        check_ajax_referer('bkgt_swe3_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions'));
        }

        $enabled = $_POST['enabled'] === 'true';

        $scheduler = bkgt_swe3_scraper()->scheduler;
        $result = $scheduler->set_scraping_enabled($enabled);

        wp_send_json($result);
    }

    /**
     * Display recent activity
     */
    private function display_recent_activity() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bkgt_swe3_documents';

        $recent_docs = $wpdb->get_results($wpdb->prepare(
            "SELECT title, scraped_date, status
             FROM $table_name
             ORDER BY scraped_date DESC
             LIMIT 10"
        ));

        if (empty($recent_docs)) {
            echo '<p>' . __('No recent activity', 'bkgt-swe3-scraper') . '</p>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Document', 'bkgt-swe3-scraper') . '</th>';
        echo '<th>' . __('Date', 'bkgt-swe3-scraper') . '</th>';
        echo '<th>' . __('Status', 'bkgt-swe3-scraper') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($recent_docs as $doc) {
            $status_class = $doc->status === 'active' ? 'bkgt-status-ok' : 'bkgt-status-error';
            echo '<tr>';
            echo '<td>' . esc_html($doc->title) . '</td>';
            echo '<td>' . esc_html($doc->scraped_date) . '</td>';
            echo '<td><span class="' . $status_class . '">' . esc_html(ucfirst($doc->status)) . '</span></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('tools.php?page=bkgt-swe3-scraper') . '">' . __('Settings', 'bkgt-swe3-scraper') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Add admin notices
     */
    public function add_admin_notices() {
        $failure_count = get_option('bkgt_swe3_failure_count', 0);

        if ($failure_count >= 3) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong><?php _e('SWE3 Scraper Alert', 'bkgt-swe3-scraper'); ?>:</strong>
                    <?php printf(
                        __('The SWE3 document scraper has failed %d times in a row. Please check the scraper settings and logs.', 'bkgt-swe3-scraper'),
                        $failure_count
                    ); ?>
                    <a href="<?php echo admin_url('tools.php?page=bkgt-swe3-scraper'); ?>">
                        <?php _e('Go to scraper settings', 'bkgt-swe3-scraper'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
}