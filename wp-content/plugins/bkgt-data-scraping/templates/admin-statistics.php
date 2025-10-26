<?php
/**
 * Statistics management template for BKGT Data Scraping plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Manage Statistics', 'bkgt-data-scraping'); ?></h1>

    <div class="bkgt-admin-header">
        <button type="button" class="button button-primary" id="bkgt-add-statistics">
            <?php _e('Add Statistics', 'bkgt-data-scraping'); ?>
        </button>
    </div>

    <div class="bkgt-stats-container">
        <div class="bkgt-stats-filters">
            <select id="bkgt-stats-player-filter">
                <option value=""><?php _e('Select Player', 'bkgt-data-scraping'); ?></option>
                <?php foreach ($players as $player): ?>
                    <option value="<?php echo esc_attr($player['id']); ?>">
                        <?php echo esc_html($player['first_name'] . ' ' . $player['last_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="bkgt-stats-event-filter">
                <option value=""><?php _e('Select Event', 'bkgt-data-scraping'); ?></option>
                <?php foreach ($events as $event): ?>
                    <option value="<?php echo esc_attr($event['id']); ?>">
                        <?php echo esc_html($event['title'] . ' - ' . date_i18n(get_option('date_format'), strtotime($event['event_date']))); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="button" class="button" id="bkgt-load-player-stats">
                <?php _e('Load Player Statistics', 'bkgt-data-scraping'); ?>
            </button>
        </div>

        <div id="bkgt-player-statistics" class="bkgt-player-stats" style="display: none;">
            <h3 id="bkgt-player-stats-title"></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Event', 'bkgt-data-scraping'); ?></th>
                        <th><?php _e('Date', 'bkgt-data-scraping'); ?></th>
                        <th><?php _e('Goals', 'bkgt-data-scraping'); ?></th>
                        <th><?php _e('Assists', 'bkgt-data-scraping'); ?></th>
                        <th><?php _e('Minutes', 'bkgt-data-scraping'); ?></th>
                        <th><?php _e('Yellow Cards', 'bkgt-data-scraping'); ?></th>
                        <th><?php _e('Red Cards', 'bkgt-data-scraping'); ?></th>
                        <th><?php _e('Actions', 'bkgt-data-scraping'); ?></th>
                    </tr>
                </thead>
                <tbody id="bkgt-player-stats-body">
                    <!-- Statistics will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Statistics Edit Modal -->
<div id="bkgt-statistics-modal" class="bkgt-modal" style="display: none;">
    <div class="bkgt-modal-content">
        <span class="bkgt-modal-close">&times;</span>
        <h3><?php _e('Add/Edit Statistics', 'bkgt-data-scraping'); ?></h3>

        <form id="bkgt-statistics-form">
            <?php wp_nonce_field('bkgt_admin_nonce', 'nonce'); ?>

            <div class="bkgt-form-row">
                <label for="bkgt-stats-player"><?php _e('Player:', 'bkgt-data-scraping'); ?></label>
                <select id="bkgt-stats-player" name="player_id" required>
                    <option value=""><?php _e('Select Player', 'bkgt-data-scraping'); ?></option>
                    <?php foreach ($players as $player): ?>
                        <option value="<?php echo esc_attr($player['id']); ?>">
                            <?php echo esc_html($player['first_name'] . ' ' . $player['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-stats-event"><?php _e('Event:', 'bkgt-data-scraping'); ?></label>
                <select id="bkgt-stats-event" name="event_id" required>
                    <option value=""><?php _e('Select Event', 'bkgt-data-scraping'); ?></option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?php echo esc_attr($event['id']); ?>">
                            <?php echo esc_html($event['title'] . ' - ' . date_i18n(get_option('date_format'), strtotime($event['event_date']))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-stats-goals"><?php _e('Goals:', 'bkgt-data-scraping'); ?></label>
                <input type="number" id="bkgt-stats-goals" name="goals" min="0" value="0">
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-stats-assists"><?php _e('Assists:', 'bkgt-data-scraping'); ?></label>
                <input type="number" id="bkgt-stats-assists" name="assists" min="0" value="0">
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-stats-minutes"><?php _e('Minutes Played:', 'bkgt-data-scraping'); ?></label>
                <input type="number" id="bkgt-stats-minutes" name="minutes_played" min="0" max="120" value="0">
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-stats-yellow"><?php _e('Yellow Cards:', 'bkgt-data-scraping'); ?></label>
                <input type="number" id="bkgt-stats-yellow" name="yellow_cards" min="0" value="0">
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-stats-red"><?php _e('Red Cards:', 'bkgt-data-scraping'); ?></label>
                <input type="number" id="bkgt-stats-red" name="red_cards" min="0" value="0">
            </div>

            <div class="bkgt-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Save Statistics', 'bkgt-data-scraping'); ?></button>
                <button type="button" class="button bkgt-modal-cancel"><?php _e('Cancel', 'bkgt-data-scraping'); ?></button>
            </div>
        </form>
    </div>
</div>