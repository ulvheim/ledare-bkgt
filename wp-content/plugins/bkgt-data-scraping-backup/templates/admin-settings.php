<?php
/**
 * Settings template for BKGT Data Scraping plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('BKGT Data Scraping Settings', 'bkgt-data-scraping'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('bkgt_settings_nonce', '_wpnonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Automatic Scraping', 'bkgt-data-scraping'); ?></th>
                <td>
                    <label for="bkgt_scraping_enabled">
                        <input type="checkbox" id="bkgt_scraping_enabled" name="bkgt_scraping_enabled" value="1"
                               <?php checked(get_option('bkgt_scraping_enabled'), 'yes'); ?>>
                        <?php _e('Enable daily automatic data scraping', 'bkgt-data-scraping'); ?>
                    </label>
                    <p class="description">
                        <?php _e('When enabled, the plugin will automatically scrape data from the configured source daily.', 'bkgt-data-scraping'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Scraping Interval', 'bkgt-data-scraping'); ?></th>
                <td>
                    <select id="bkgt_scraping_interval" name="bkgt_scraping_interval">
                        <option value="daily" <?php selected(get_option('bkgt_scraping_interval'), 'daily'); ?>>
                            <?php _e('Daily', 'bkgt-data-scraping'); ?>
                        </option>
                        <option value="twicedaily" <?php selected(get_option('bkgt_scraping_interval'), 'twicedaily'); ?>>
                            <?php _e('Twice Daily', 'bkgt-data-scraping'); ?>
                        </option>
                        <option value="hourly" <?php selected(get_option('bkgt_scraping_interval'), 'hourly'); ?>>
                            <?php _e('Hourly', 'bkgt-data-scraping'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('How often to run automatic scraping (requires WP Cron to be enabled).', 'bkgt-data-scraping'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Data Source URL', 'bkgt-data-scraping'); ?></th>
                <td>
                    <input type="url" id="bkgt_scraping_source_url" name="bkgt_scraping_source_url"
                           value="<?php echo esc_attr(get_option('bkgt_scraping_source_url', 'https://www.svenskalag.se/bkgt')); ?>"
                           class="regular-text" required>
                    <p class="description">
                        <?php _e('The base URL of the data source (svenskalag.se). The scraper will look for players and events under this URL.', 'bkgt-data-scraping'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save Settings', 'bkgt-data-scraping')); ?>
    </form>

    <div class="bkgt-settings-section">
        <h2><?php _e('Manual Scraping', 'bkgt-data-scraping'); ?></h2>
        <p><?php _e('You can also trigger manual scraping from the main dashboard or individual management pages.', 'bkgt-data-scraping'); ?></p>

        <div class="bkgt-manual-scraping">
            <button type="button" class="button" id="bkgt-manual-scrape-players">
                <?php _e('Scrape Players Now', 'bkgt-data-scraping'); ?>
            </button>
            <button type="button" class="button" id="bkgt-manual-scrape-events">
                <?php _e('Scrape Events Now', 'bkgt-data-scraping'); ?>
            </button>
        </div>
    </div>

    <div class="bkgt-settings-section">
        <h2><?php _e('Database Information', 'bkgt-data-scraping'); ?></h2>
        <p><?php _e('Current database status and table information.', 'bkgt-data-scraping'); ?></p>

        <?php
        global $wpdb;
        $db = bkgt_data_scraping()->db;

        $tables_status = array();
        $tables = array('players', 'events', 'statistics', 'sources');

        foreach ($tables as $table) {
            $table_name = $db->get_table($table);
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
            $tables_status[$table] = array(
                'exists' => $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name,
                'count' => $count
            );
        }
        ?>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Table', 'bkgt-data-scraping'); ?></th>
                    <th><?php _e('Status', 'bkgt-data-scraping'); ?></th>
                    <th><?php _e('Records', 'bkgt-data-scraping'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tables_status as $table => $status): ?>
                    <tr>
                        <td><?php echo esc_html($db->get_table($table)); ?></td>
                        <td>
                            <span class="bkgt-status bkgt-status-<?php echo $status['exists'] ? 'success' : 'error'; ?>">
                                <?php echo $status['exists'] ? __('Exists', 'bkgt-data-scraping') : __('Missing', 'bkgt-data-scraping'); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($status['count']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="description">
            <?php _e('If tables are missing, try deactivating and reactivating the plugin to recreate them.', 'bkgt-data-scraping'); ?>
        </p>
    </div>
</div>