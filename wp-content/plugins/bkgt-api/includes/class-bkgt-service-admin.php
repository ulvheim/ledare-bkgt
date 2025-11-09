<?php
/**
 * BKGT API Service Key Admin Interface
 *
 * Provides admin interface for managing service API keys
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKGT_API_Service_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'BKGT Service API Keys',
            'BKGT Service Keys',
            'manage_options',
            'bkgt-service-keys',
            array($this, 'admin_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('bkgt_service_keys', 'bkgt_service_key_rotation_interval');

        add_settings_section(
            'bkgt_service_keys_main',
            'Service API Key Settings',
            array($this, 'settings_section_callback'),
            'bkgt_service_keys'
        );

        add_settings_field(
            'bkgt_service_key_rotation',
            'Key Rotation Interval (days)',
            array($this, 'rotation_interval_callback'),
            'bkgt_service_keys',
            'bkgt_service_keys_main'
        );
    }

    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>Configure automatic rotation settings for the service API key used for internal API calls.</p>';
    }

    /**
     * Rotation interval field callback
     */
    public function rotation_interval_callback() {
        $interval = get_option('bkgt_service_key_rotation_interval', 30 * DAY_IN_SECONDS) / DAY_IN_SECONDS;
        echo '<input type="number" name="bkgt_service_key_rotation_interval" value="' . esc_attr($interval) . '" min="1" max="365" /> days';
        echo '<p class="description">How often the service API key should be automatically rotated. Default is 30 days.</p>';
    }

    /**
     * Admin page
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Handle form submissions
        if (isset($_POST['rotate_service_key']) && check_admin_referer('bkgt_rotate_service_key')) {
            $this->rotate_service_key();
        }

        if (isset($_POST['bkgt_service_key_rotation_interval']) && check_admin_referer('bkgt_service_keys-options')) {
            $this->update_rotation_settings();
        }

        ?>
        <div class="wrap">
            <h1>BKGT Service API Keys</h1>

            <div class="notice notice-info">
                <p><strong>Service API Key:</strong> Used for internal API calls between WordPress components. This key has administrator privileges.</p>
            </div>

            <div class="card">
                <h2>Current Service Key</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <code><?php echo esc_html($this->mask_api_key($this->get_service_key())); ?></code>
                            <button type="button" class="button" onclick="toggleKeyVisibility()">Show/Hide</button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Last Rotation</th>
                        <td><?php echo esc_html($this->get_last_rotation_date()); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">Next Rotation</th>
                        <td><?php echo esc_html($this->get_next_rotation_date()); ?></td>
                    </tr>
                </table>

                <form method="post">
                    <?php wp_nonce_field('bkgt_rotate_service_key'); ?>
                    <p>
                        <input type="submit" name="rotate_service_key" class="button button-primary" value="Rotate Service Key Now">
                    </p>
                    <p class="description">Manually rotate the service API key. The old key will remain valid for 24 hours to allow for system updates.</p>
                </form>
            </div>

            <div class="card">
                <h2>Automatic Rotation Settings</h2>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('bkgt_service_keys');
                    do_settings_sections('bkgt_service_keys');
                    submit_button('Save Settings');
                    ?>
                </form>
            </div>

            <div class="card">
                <h2>API Key Testing</h2>
                <p>Test the service API key with a simple API call:</p>
                <button type="button" class="button" id="test-api-key">Test Service API Key</button>
                <div id="test-result" style="margin-top: 10px;"></div>
            </div>
        </div>

        <script>
        function toggleKeyVisibility() {
            var keyElement = document.querySelector('code');
            var currentText = keyElement.textContent;
            var fullKey = '<?php echo esc_js($this->get_service_key()); ?>';

            if (currentText.includes('*')) {
                keyElement.textContent = fullKey;
            } else {
                keyElement.textContent = '<?php echo esc_js($this->mask_api_key($this->get_service_key())); ?>';
            }
        }

        document.getElementById('test-api-key').addEventListener('click', function() {
            var resultDiv = document.getElementById('test-result');
            resultDiv.innerHTML = '<p>Testing...</p>';

            fetch('<?php echo esc_url(rest_url('bkgt/v1/health')); ?>', {
                method: 'GET',
                headers: {
                    'X-API-Key': '<?php echo esc_js($this->get_service_key()); ?>',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'healthy') {
                    resultDiv.innerHTML = '<p style="color: green;">✓ API key test successful! Authentication: ' + data.authentication.type + '</p>';
                } else {
                    resultDiv.innerHTML = '<p style="color: red;">✗ API key test failed.</p>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<p style="color: red;">✗ API key test failed: ' + error.message + '</p>';
            });
        });
        </script>
        <?php
    }

    /**
     * Rotate service key
     */
    private function rotate_service_key() {
        $auth = new BKGT_API_Auth();
        $auth->rotate_service_api_key();

        add_settings_error(
            'bkgt_service_keys',
            'key_rotated',
            'Service API key has been rotated successfully.',
            'updated'
        );
    }

    /**
     * Update rotation settings
     */
    private function update_rotation_settings() {
        // Settings are handled by WordPress settings API
    }

    /**
     * Get service key
     */
    private function get_service_key() {
        return get_option('bkgt_service_api_key');
    }

    /**
     * Mask API key for display
     */
    private function mask_api_key($key) {
        if (strlen($key) <= 8) {
            return $key;
        }
        return substr($key, 0, 8) . str_repeat('*', strlen($key) - 8);
    }

    /**
     * Get last rotation date
     */
    private function get_last_rotation_date() {
        $timestamp = get_option('bkgt_service_key_last_rotation', time());
        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
    }

    /**
     * Get next rotation date
     */
    private function get_next_rotation_date() {
        $last_rotation = get_option('bkgt_service_key_last_rotation', time());
        $interval = get_option('bkgt_service_key_rotation_interval', 30 * DAY_IN_SECONDS);
        $next_rotation = $last_rotation + $interval;
        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_rotation);
    }

    /**
     * Admin notices
     */
    public function admin_notices() {
        settings_errors('bkgt_service_keys');
    }
}

// Initialize the admin interface
new BKGT_API_Service_Admin();