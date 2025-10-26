<?php
/**
 * Frontend players template for BKGT Data Scraping plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="bkgt-players-container">
    <?php if (!empty($atts['show_filters']) && $atts['show_filters'] === 'true') : ?>
    <div class="bkgt-players-filters">
        <div class="bkgt-filter-row">
            <div class="bkgt-search-input">
                <span class="dashicons dashicons-search" aria-hidden="true"></span>
                <input type="text" class="bkgt-search-players bkgt-player-filter" placeholder="<?php _e('Sök spelare...', 'bkgt-data-scraping'); ?>">
            </div>
            <div class="bkgt-filter-select">
                <select class="bkgt-filter-position bkgt-player-filter">
                    <option value=""><?php _e('Alla positioner', 'bkgt-data-scraping'); ?></option>
                    <option value="forward"><?php _e('Forward', 'bkgt-data-scraping'); ?></option>
                    <option value="midfielder"><?php _e('Mittfältare', 'bkgt-data-scraping'); ?></option>
                    <option value="defender"><?php _e('Försvarare', 'bkgt-data-scraping'); ?></option>
                    <option value="goalkeeper"><?php _e('Målvakt', 'bkgt-data-scraping'); ?></option>
                </select>
            </div>
            <div class="bkgt-filter-select">
                <select class="bkgt-filter-status bkgt-player-filter">
                    <option value="active"><?php _e('Aktiva', 'bkgt-data-scraping'); ?></option>
                    <option value="inactive"><?php _e('Inaktiva', 'bkgt-data-scraping'); ?></option>
                    <option value="all"><?php _e('Alla', 'bkgt-data-scraping'); ?></option>
                </select>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($players)) : ?>
        <div class="bkgt-players-<?php echo esc_attr($atts['layout']); ?>">
            <?php foreach ($players as $player) : ?>
                <div class="bkgt-player-card"
                     data-position="<?php echo esc_attr($player['position']); ?>"
                     data-status="<?php echo esc_attr($player['status'] ?: 'active'); ?>">

                    <div class="bkgt-player-avatar">
                        <?php echo esc_html(substr($player['first_name'], 0, 1) . substr($player['last_name'], 0, 1)); ?>
                    </div>

                    <h3 class="bkgt-player-name">
                        <?php echo esc_html($player['first_name'] . ' ' . $player['last_name']); ?>
                    </h3>

                    <div class="bkgt-player-position">
                        <?php echo esc_html($player['position'] ? ucfirst($player['position']) : __('Position ej angiven', 'bkgt-data-scraping')); ?>
                    </div>

                    <?php if (!empty($player['jersey_number'])) : ?>
                        <span class="bkgt-player-number">
                            #<?php echo esc_html($player['jersey_number']); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($atts['show_stats'] === 'true') : ?>
                        <?php
                        $stats = isset($player['stats']) ? $player['stats'] : array();
                        $total_games = count($stats);
                        $total_goals = array_sum(array_column($stats, 'goals'));
                        $total_assists = array_sum(array_column($stats, 'assists'));
                        ?>
                        <div class="bkgt-player-stats">
                            <div class="bkgt-player-stat">
                                <span class="bkgt-player-stat-value"><?php echo esc_html($total_games); ?></span>
                                <span class="bkgt-player-stat-label"><?php _e('Matcher', 'bkgt-data-scraping'); ?></span>
                            </div>
                            <div class="bkgt-player-stat">
                                <span class="bkgt-player-stat-value"><?php echo esc_html($total_goals); ?></span>
                                <span class="bkgt-player-stat-label"><?php _e('Mål', 'bkgt-data-scraping'); ?></span>
                            </div>
                            <div class="bkgt-player-stat">
                                <span class="bkgt-player-stat-value"><?php echo esc_html($total_assists); ?></span>
                                <span class="bkgt-player-stat-label"><?php _e('Assist', 'bkgt-data-scraping'); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p><?php _e('Inga spelare hittades.', 'bkgt-data-scraping'); ?></p>
    <?php endif; ?>
</div>