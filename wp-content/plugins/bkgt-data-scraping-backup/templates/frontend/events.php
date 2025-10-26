<?php
/**
 * Frontend events template for BKGT Data Scraping plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="bkgt-events-container">
    <?php if (!empty($atts['show_filters']) && $atts['show_filters'] === 'true') : ?>
    <div class="bkgt-events-filters">
        <div class="bkgt-filter-row">
            <div class="bkgt-filter-select">
                <select class="bkgt-filter-type bkgt-event-filter">
                    <option value=""><?php _e('Alla typer', 'bkgt-data-scraping'); ?></option>
                    <option value="match"><?php _e('Match', 'bkgt-data-scraping'); ?></option>
                    <option value="training"><?php _e('Träning', 'bkgt-data-scraping'); ?></option>
                    <option value="meeting"><?php _e('Möte', 'bkgt-data-scraping'); ?></option>
                </select>
            </div>
            <label class="bkgt-filter-checkbox">
                <input type="checkbox" class="bkgt-filter-upcoming bkgt-event-filter" checked>
                <?php _e('Endast kommande', 'bkgt-data-scraping'); ?>
            </label>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($events)) : ?>
        <div class="bkgt-events-list">
            <?php foreach ($events as $event) : ?>
                <div class="bkgt-event-item"
                     data-type="<?php echo esc_attr($event['event_type']); ?>"
                     data-date="<?php echo esc_attr($event['event_date']); ?>">

                    <div class="bkgt-event-header">
                        <h3 class="bkgt-event-title"><?php echo esc_html($event['title']); ?></h3>
                        <span class="bkgt-event-type"><?php echo esc_html(ucfirst($event['event_type'])); ?></span>
                    </div>

                    <div class="bkgt-event-meta">
                        <div class="bkgt-event-date">
                            <span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event['event_date']))); ?>
                        </div>
                        <?php if (!empty($event['location'])) : ?>
                        <div class="bkgt-event-location">
                            <span class="dashicons dashicons-location" aria-hidden="true"></span>
                            <?php echo esc_html($event['location']); ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($event['description'])) : ?>
                        <div class="bkgt-event-description">
                            <p><?php echo esc_html($event['description']); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($atts['show_players'] === 'true') : ?>
                        <?php
                        $event_players = $this->db->get_event_players($event['id']);
                        ?>
                        <?php if (!empty($event_players)) : ?>
                        <div class="bkgt-event-players">
                            <h4><?php _e('Tilldelade spelare', 'bkgt-data-scraping'); ?></h4>
                            <div class="bkgt-players-assigned">
                                <?php foreach ($event_players as $player) : ?>
                                    <span class="bkgt-player-tag">
                                        <?php echo esc_html($player['first_name'] . ' ' . $player['last_name']); ?>
                                        <?php if (!empty($player['jersey_number'])) : ?>
                                            (#<?php echo esc_html($player['jersey_number']); ?>)
                                        <?php endif; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p><?php _e('Inga evenemang hittades.', 'bkgt-data-scraping'); ?></p>
    <?php endif; ?>
</div>