<?php
/**
 * Frontend team overview template for BKGT Data Scraping plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="bkgt-team-overview">
    <?php if ($atts['show_stats'] === 'true') : ?>
    <div class="bkgt-overview-stats">
        <div class="bkgt-stat-card">
            <span class="bkgt-stat-value"><?php echo esc_html($stats['total_players']); ?></span>
            <span class="bkgt-stat-label"><?php _e('Aktiva Spelare', 'bkgt-data-scraping'); ?></span>
        </div>
        <div class="bkgt-stat-card">
            <span class="bkgt-stat-value"><?php echo esc_html($stats['total_events']); ?></span>
            <span class="bkgt-stat-label"><?php _e('Evenemang', 'bkgt-data-scraping'); ?></span>
        </div>
        <div class="bkgt-stat-card">
            <span class="bkgt-stat-value">
                <?php echo esc_html(isset($stats['total_goals']) ? $stats['total_goals'] : 0); ?>
            </span>
            <span class="bkgt-stat-label"><?php _e('Totala Mål', 'bkgt-data-scraping'); ?></span>
        </div>
        <div class="bkgt-stat-card">
            <span class="bkgt-stat-value">
                <?php echo esc_html(isset($stats['total_games']) ? $stats['total_games'] : 0); ?>
            </span>
            <span class="bkgt-stat-label"><?php _e('Spelade Matcher', 'bkgt-data-scraping'); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($atts['show_upcoming'] === 'true' && !empty($stats['upcoming_events'])) : ?>
    <div class="bkgt-upcoming-events">
        <h3><?php _e('Kommande Evenemang', 'bkgt-data-scraping'); ?></h3>
        <div class="bkgt-events-list">
            <?php foreach ($stats['upcoming_events'] as $event) : ?>
                <div class="bkgt-event-item">
                    <div class="bkgt-event-header">
                        <h4 class="bkgt-event-title"><?php echo esc_html($event['title']); ?></h4>
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
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>