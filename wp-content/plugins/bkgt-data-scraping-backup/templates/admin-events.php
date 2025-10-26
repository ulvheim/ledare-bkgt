<?php
/**
 * Events management template for BKGT Data Scraping plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Manage Events', 'bkgt-data-scraping'); ?></h1>

    <div class="bkgt-admin-header">
        <button type="button" class="button button-primary" id="bkgt-add-event">
            <?php _e('Add New Event', 'bkgt-data-scraping'); ?>
        </button>
        <button type="button" class="button" id="bkgt-scrape-events">
            <?php _e('Scrape Events from Source', 'bkgt-data-scraping'); ?>
        </button>
    </div>

    <table class="wp-list-table widefat fixed striped bkgt-events-table">
        <thead>
            <tr>
                <th><?php _e('ID', 'bkgt-data-scraping'); ?></th>
                <th><?php _e('Title', 'bkgt-data-scraping'); ?></th>
                <th><?php _e('Type', 'bkgt-data-scraping'); ?></th>
                <th><?php _e('Date', 'bkgt-data-scraping'); ?></th>
                <th><?php _e('Opponent', 'bkgt-data-scraping'); ?></th>
                <th><?php _e('Status', 'bkgt-data-scraping'); ?></th>
                <th><?php _e('Actions', 'bkgt-data-scraping'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($events)): ?>
                <?php foreach ($events as $event): ?>
                    <tr data-event-id="<?php echo esc_attr($event['id']); ?>">
                        <td><?php echo esc_html($event['event_id']); ?></td>
                        <td><?php echo esc_html($event['title']); ?></td>
                        <td><?php echo esc_html(ucfirst($event['event_type'])); ?></td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event['event_date']))); ?></td>
                        <td><?php echo esc_html($event['opponent']); ?></td>
                        <td>
                            <span class="bkgt-status bkgt-status-<?php echo esc_attr($event['status']); ?>">
                                <?php echo esc_html(ucfirst($event['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="button button-small bkgt-edit-event" data-event='<?php echo wp_json_encode($event); ?>'>
                                <?php _e('Edit', 'bkgt-data-scraping'); ?>
                            </button>
                            <button type="button" class="button button-small button-link-delete bkgt-delete-event" data-event-id="<?php echo esc_attr($event['id']); ?>">
                                <?php _e('Delete', 'bkgt-data-scraping'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center;">
                        <?php _e('No events found. Add events manually or scrape from the data source.', 'bkgt-data-scraping'); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Event Edit Modal -->
<div id="bkgt-event-modal" class="bkgt-modal" style="display: none;">
    <div class="bkgt-modal-content">
        <span class="bkgt-modal-close">&times;</span>
        <h3 id="bkgt-event-modal-title"><?php _e('Add/Edit Event', 'bkgt-data-scraping'); ?></h3>

        <form id="bkgt-event-form">
            <?php wp_nonce_field('bkgt_admin_nonce', 'nonce'); ?>
            <input type="hidden" id="bkgt-event-id" name="event_id" value="">

            <div class="bkgt-form-row">
                <label for="bkgt-event-event-id"><?php _e('Event ID:', 'bkgt-data-scraping'); ?></label>
                <input type="text" id="bkgt-event-event-id" name="event_id" required>
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-event-title"><?php _e('Title:', 'bkgt-data-scraping'); ?></label>
                <input type="text" id="bkgt-event-title" name="title" required>
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-event-type"><?php _e('Event Type:', 'bkgt-data-scraping'); ?></label>
                <select id="bkgt-event-type" name="event_type">
                    <option value="match"><?php _e('Match', 'bkgt-data-scraping'); ?></option>
                    <option value="training"><?php _e('Training', 'bkgt-data-scraping'); ?></option>
                    <option value="meeting"><?php _e('Meeting', 'bkgt-data-scraping'); ?></option>
                    <option value="other"><?php _e('Other', 'bkgt-data-scraping'); ?></option>
                </select>
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-event-date"><?php _e('Event Date & Time:', 'bkgt-data-scraping'); ?></label>
                <input type="datetime-local" id="bkgt-event-date" name="event_date" required>
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-event-location"><?php _e('Location:', 'bkgt-data-scraping'); ?></label>
                <input type="text" id="bkgt-event-location" name="location">
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-event-opponent"><?php _e('Opponent:', 'bkgt-data-scraping'); ?></label>
                <input type="text" id="bkgt-event-opponent" name="opponent">
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-event-home-away"><?php _e('Home/Away:', 'bkgt-data-scraping'); ?></label>
                <select id="bkgt-event-home-away" name="home_away">
                    <option value="home"><?php _e('Home', 'bkgt-data-scraping'); ?></option>
                    <option value="away"><?php _e('Away', 'bkgt-data-scraping'); ?></option>
                </select>
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-event-result"><?php _e('Result:', 'bkgt-data-scraping'); ?></label>
                <input type="text" id="bkgt-event-result" name="result" placeholder="e.g., 2-1">
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-event-status"><?php _e('Status:', 'bkgt-data-scraping'); ?></label>
                <select id="bkgt-event-status" name="status">
                    <option value="scheduled"><?php _e('Scheduled', 'bkgt-data-scraping'); ?></option>
                    <option value="completed"><?php _e('Completed', 'bkgt-data-scraping'); ?></option>
                    <option value="cancelled"><?php _e('Cancelled', 'bkgt-data-scraping'); ?></option>
                </select>
            </div>

            <div class="bkgt-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Save Event', 'bkgt-data-scraping'); ?></button>
                <button type="button" class="button bkgt-modal-cancel"><?php _e('Cancel', 'bkgt-data-scraping'); ?></button>
            </div>
        </form>
    </div>
</div>