<?php
/**
 * Frontend player profile template for BKGT Data Scraping plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="bkgt-player-profile">
    <div class="bkgt-profile-header">
        <div class="bkgt-profile-avatar">
            <?php echo esc_html(substr($player['first_name'], 0, 1) . substr($player['last_name'], 0, 1)); ?>
        </div>
        <h1 class="bkgt-profile-name">
            <?php echo esc_html($player['first_name'] . ' ' . $player['last_name']); ?>
        </h1>
        <div class="bkgt-profile-position">
            <?php echo esc_html($player['position'] ? ucfirst($player['position']) : __('Position ej angiven', 'bkgt-data-scraping')); ?>
        </div>
        <?php if (!empty($player['jersey_number'])) : ?>
            <span class="bkgt-profile-number">
                #<?php echo esc_html($player['jersey_number']); ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="bkgt-profile-details">
        <?php if ($atts['show_stats'] === 'true' && !empty($stats)) : ?>
        <div class="bkgt-profile-section">
            <h3><?php _e('Statistik', 'bkgt-data-scraping'); ?></h3>
            <table class="bkgt-stats-table">
                <thead>
                    <tr>
                        <th><?php _e('Evenemang', 'bkgt-data-scraping'); ?></th>
                        <th><?php _e('Mål', 'bkgt-data-scraping'); ?></th>
                        <th><?php _e('Assist', 'bkgt-data-scraping'); ?></th>
                        <th><?php _e('Gula kort', 'bkgt-data-scraping'); ?></th>
                        <th><?php _e('Röda kort', 'bkgt-data-scraping'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_goals = 0;
                    $total_assists = 0;
                    $total_yellow = 0;
                    $total_red = 0;
                    ?>
                    <?php foreach ($stats as $stat) : ?>
                        <?php
                        $total_goals += $stat['goals'];
                        $total_assists += $stat['assists'];
                        $total_yellow += $stat['yellow_cards'];
                        $total_red += $stat['red_cards'];
                        ?>
                        <tr>
                            <td><?php echo esc_html($stat['event_title']); ?></td>
                            <td><?php echo esc_html($stat['goals']); ?></td>
                            <td><?php echo esc_html($stat['assists']); ?></td>
                            <td><?php echo esc_html($stat['yellow_cards']); ?></td>
                            <td><?php echo esc_html($stat['red_cards']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th><?php _e('Totalt', 'bkgt-data-scraping'); ?></th>
                        <th><?php echo esc_html($total_goals); ?></th>
                        <th><?php echo esc_html($total_assists); ?></th>
                        <th><?php echo esc_html($total_yellow); ?></th>
                        <th><?php echo esc_html($total_red); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($atts['show_events'] === 'true' && !empty($events)) : ?>
        <div class="bkgt-profile-section">
            <h3><?php _e('Kommande Evenemang', 'bkgt-data-scraping'); ?></h3>
            <div class="bkgt-events-list">
                <?php foreach ($events as $event) : ?>
                    <?php
                    $event_date = strtotime($event['event_date']);
                    if ($event_date < time()) continue; // Skip past events
                    ?>
                    <div class="bkgt-event-item">
                        <div class="bkgt-event-header">
                            <h4 class="bkgt-event-title"><?php echo esc_html($event['title']); ?></h4>
                            <span class="bkgt-event-type"><?php echo esc_html(ucfirst($event['event_type'])); ?></span>
                        </div>
                        <div class="bkgt-event-meta">
                            <div class="bkgt-event-date">
                                <span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $event_date)); ?>
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
</div>