<?php
/**
 * Admin settings template for BKGT Data Scraping plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('BKGT Data Scraping Settings', 'bkgt-data-scraping'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('bkgt_settings_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Automated Scraping', 'bkgt-data-scraping'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="bkgt_scraping_enabled" value="1" <?php checked(get_option('bkgt_scraping_enabled'), 'yes'); ?>>
                        <?php _e('Enable automatic data scraping from svenskalag.se', 'bkgt-data-scraping'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Source URL', 'bkgt-data-scraping'); ?></th>
                <td>
                    <input type="url" name="bkgt_scraping_source_url" value="<?php echo esc_attr(get_option('bkgt_scraping_source_url', 'https://www.svenskalag.se/bkgt')); ?>" class="regular-text" required>
                    <p class="description"><?php _e('The base URL for BKGT data on svenskalag.se', 'bkgt-data-scraping'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Scraping Interval', 'bkgt-data-scraping'); ?></th>
                <td>
                    <select name="bkgt_scraping_interval">
                        <option value="hourly" <?php selected(get_option('bkgt_scraping_interval'), 'hourly'); ?>><?php _e('Hourly', 'bkgt-data-scraping'); ?></option>
                        <option value="daily" <?php selected(get_option('bkgt_scraping_interval'), 'daily'); ?></option><?php _e('Daily', 'bkgt-data-scraping'); ?></option>
                        <option value="weekly" <?php selected(get_option('bkgt_scraping_interval'), 'weekly'); ?>><?php _e('Weekly', 'bkgt-data-scraping'); ?></option>
                    </select>
                    <p class="description"><?php _e('How often to run automated scraping', 'bkgt-data-scraping'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><h3><?php _e('Authentication Settings', 'bkgt-data-scraping'); ?></h3></th>
                <td></td>
            </tr>

            <tr>
                <th scope="row"><?php _e('SvenskaLag.se Username', 'bkgt-data-scraping'); ?></th>
                <td>
                    <input type="text" name="bkgt_scraping_username" value="<?php
                        $encrypted_username = get_option('bkgt_scraping_username');
                        if (!empty($encrypted_username)) {
                            $admin = new BKGT_Admin($GLOBALS['bkgt_db']);
                            echo esc_attr($admin->decrypt_credential($encrypted_username));
                        }
                    ?>" class="regular-text" autocomplete="off">
                    <p class="description"><?php _e('Username for svenskalag.se authentication (stored encrypted)', 'bkgt-data-scraping'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('SvenskaLag.se Password', 'bkgt-data-scraping'); ?></th>
                <td>
                    <input type="password" name="bkgt_scraping_password" value="<?php
                        $encrypted_password = get_option('bkgt_scraping_password');
                        if (!empty($encrypted_password)) {
                            $admin = new BKGT_Admin($GLOBALS['bkgt_db']);
                            echo esc_attr($admin->decrypt_credential($encrypted_password));
                        }
                    ?>" class="regular-text" autocomplete="off">
                    <p class="description"><?php _e('Password for svenskalag.se authentication (stored encrypted)', 'bkgt-data-scraping'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Security Notice', 'bkgt-data-scraping'); ?></th>
                <td>
                    <div class="notice notice-info inline">
                        <p><strong><?php _e('Important:', 'bkgt-data-scraping'); ?></strong> <?php _e('Credentials are encrypted using WordPress salts before storage. The scraper only performs read-only operations and will not modify any data on svenskalag.se.', 'bkgt-data-scraping'); ?></p>
                    </div>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save Settings', 'bkgt-data-scraping')); ?>
    </form>

    <hr>

    <h2><?php _e('Test Authentication', 'bkgt-data-scraping'); ?></h2>
    <p><?php _e('Click the button below to test if your authentication credentials work correctly.', 'bkgt-data-scraping'); ?></p>

    <button type="button" id="bkgt-test-auth" class="button button-secondary">
        <?php _e('Test Authentication', 'bkgt-data-scraping'); ?>
    </button>

    <div id="bkgt-auth-test-result" style="margin-top: 10px;"></div>

    <script>
    jQuery(document).ready(function($) {
        $('#bkgt-test-auth').on('click', function() {
            var $button = $(this);
            var $result = $('#bkgt-auth-test-result');

            $button.prop('disabled', true).text('<?php _e('Testing...', 'bkgt-data-scraping'); ?>');
            $result.html('<p><?php _e('Testing authentication...', 'bkgt-data-scraping'); ?></p>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bkgt_test_auth',
                    nonce: '<?php echo wp_create_nonce('bkgt_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                    } else {
                        $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                    }
                },
                error: function() {
                    $result.html('<div class="notice notice-error inline"><p><?php _e('AJAX request failed', 'bkgt-data-scraping'); ?></p></div>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('<?php _e('Test Authentication', 'bkgt-data-scraping'); ?>');
                }
            });
        });
    });
    </script>
</div>