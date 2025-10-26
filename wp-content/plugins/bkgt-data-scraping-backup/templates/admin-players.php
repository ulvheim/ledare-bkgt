<?php
/**
 * Players management template for BKGT Data Scraping plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Manage Players', 'bkgt-data-scraping'); ?></h1>

    <div class="bkgt-admin-header">
        <button type="button" class="button button-primary" id="bkgt-add-player">
            <?php _e('Add New Player', 'bkgt-data-scraping'); ?>
        </button>
        <button type="button" class="button" id="bkgt-scrape-players">
            <?php _e('Scrape Players from Source', 'bkgt-data-scraping'); ?>
        </button>
    </div>

    <table class="wp-list-table widefat fixed striped bkgt-players-table">
        <thead>
            <tr>
                <th><?php _e('ID', 'bkgt-data-scraping'); ?></th>
                <th><?php _e('Name', 'bkgt-data-scraping'); ?></th>
                <th><?php _e('Position', 'bkgt-data-scraping'); ?></th>
                <th><?php _e('Jersey #', 'bkgt-data-scraping'); ?></th>
                <th><?php _e('Status', 'bkgt-data-scraping'); ?></th>
                <th><?php _e('Actions', 'bkgt-data-scraping'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($players)): ?>
                <?php foreach ($players as $player): ?>
                    <tr data-player-id="<?php echo esc_attr($player['id']); ?>">
                        <td><?php echo esc_html($player['player_id']); ?></td>
                        <td><?php echo esc_html($player['first_name'] . ' ' . $player['last_name']); ?></td>
                        <td><?php echo esc_html($player['position']); ?></td>
                        <td><?php echo esc_html($player['jersey_number']); ?></td>
                        <td>
                            <span class="bkgt-status bkgt-status-<?php echo esc_attr($player['status']); ?>">
                                <?php echo esc_html(ucfirst($player['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="button button-small bkgt-edit-player" data-player='<?php echo wp_json_encode($player); ?>'>
                                <?php _e('Edit', 'bkgt-data-scraping'); ?>
                            </button>
                            <button type="button" class="button button-small button-link-delete bkgt-delete-player" data-player-id="<?php echo esc_attr($player['id']); ?>">
                                <?php _e('Delete', 'bkgt-data-scraping'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center;">
                        <?php _e('No players found. Add players manually or scrape from the data source.', 'bkgt-data-scraping'); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Player Edit Modal -->
<div id="bkgt-player-modal" class="bkgt-modal" style="display: none;">
    <div class="bkgt-modal-content">
        <span class="bkgt-modal-close">&times;</span>
        <h3 id="bkgt-player-modal-title"><?php _e('Add/Edit Player', 'bkgt-data-scraping'); ?></h3>

        <form id="bkgt-player-form">
            <?php wp_nonce_field('bkgt_admin_nonce', 'nonce'); ?>
            <input type="hidden" id="bkgt-player-id" name="player_id" value="">

            <div class="bkgt-form-row">
                <label for="bkgt-player-player-id"><?php _e('Player ID:', 'bkgt-data-scraping'); ?></label>
                <input type="text" id="bkgt-player-player-id" name="player_id" required>
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-player-first-name"><?php _e('First Name:', 'bkgt-data-scraping'); ?></label>
                <input type="text" id="bkgt-player-first-name" name="first_name" required>
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-player-last-name"><?php _e('Last Name:', 'bkgt-data-scraping'); ?></label>
                <input type="text" id="bkgt-player-last-name" name="last_name" required>
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-player-position"><?php _e('Position:', 'bkgt-data-scraping'); ?></label>
                <select id="bkgt-player-position" name="position">
                    <option value=""><?php _e('Select Position', 'bkgt-data-scraping'); ?></option>
                    <option value="Goalkeeper"><?php _e('Goalkeeper', 'bkgt-data-scraping'); ?></option>
                    <option value="Defender"><?php _e('Defender', 'bkgt-data-scraping'); ?></option>
                    <option value="Midfielder"><?php _e('Midfielder', 'bkgt-data-scraping'); ?></option>
                    <option value="Forward"><?php _e('Forward', 'bkgt-data-scraping'); ?></option>
                </select>
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-player-jersey"><?php _e('Jersey Number:', 'bkgt-data-scraping'); ?></label>
                <input type="number" id="bkgt-player-jersey" name="jersey_number" min="1" max="99">
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-player-birth-date"><?php _e('Birth Date:', 'bkgt-data-scraping'); ?></label>
                <input type="date" id="bkgt-player-birth-date" name="birth_date">
            </div>

            <div class="bkgt-form-row">
                <label for="bkgt-player-status"><?php _e('Status:', 'bkgt-data-scraping'); ?></label>
                <select id="bkgt-player-status" name="status">
                    <option value="active"><?php _e('Active', 'bkgt-data-scraping'); ?></option>
                    <option value="inactive"><?php _e('Inactive', 'bkgt-data-scraping'); ?></option>
                    <option value="injured"><?php _e('Injured', 'bkgt-data-scraping'); ?></option>
                    <option value="suspended"><?php _e('Suspended', 'bkgt-data-scraping'); ?></option>
                </select>
            </div>

            <div class="bkgt-form-actions">
                <button type="submit" class="button button-primary"><?php _e('Save Player', 'bkgt-data-scraping'); ?></button>
                <button type="button" class="button bkgt-modal-cancel"><?php _e('Cancel', 'bkgt-data-scraping'); ?></button>
            </div>
        </form>
    </div>
</div>