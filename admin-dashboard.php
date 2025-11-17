<?php
/**
 * Admin dashboard template for BKGT Data Scraping plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <!-- Skip link for accessibility -->
    <a href="#bkgt-main-content" class="bkgt-skip-link"><?php _e('Hoppa till huvudinnehåll', 'bkgt-data-scraping'); ?></a>

    <h1><?php _e('BKGT Datahantering', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-groups" aria-hidden="true"></span></h1>

    <!-- Tab Navigation -->
    <nav class="bkgt-tabs-nav" role="tablist" aria-label="<?php _e('Huvudnavigering', 'bkgt-data-scraping'); ?>">
        <button class="bkgt-tab-button active" data-tab="overview" role="tab" aria-selected="true" aria-controls="bkgt-tab-overview" id="bkgt-tab-overview-btn">
            <span class="dashicons dashicons-dashboard" aria-hidden="true"></span>
            <?php _e('Översikt', 'bkgt-data-scraping'); ?>
        </button>
        <button class="bkgt-tab-button" data-tab="players" role="tab" aria-selected="false" aria-controls="bkgt-tab-players" id="bkgt-tab-players-btn">
            <span class="dashicons dashicons-groups" aria-hidden="true"></span>
            <?php _e('Spelare', 'bkgt-data-scraping'); ?>
        </button>
        <button class="bkgt-tab-button" data-tab="scraper" role="tab" aria-selected="false" aria-controls="bkgt-tab-scraper" id="bkgt-tab-scraper-btn">
            <span class="dashicons dashicons-update" aria-hidden="true"></span>
            <?php _e('Skrapning', 'bkgt-data-scraping'); ?>
        </button>
        <button class="bkgt-tab-button" data-tab="settings" role="tab" aria-selected="false" aria-controls="bkgt-tab-settings" id="bkgt-tab-settings-btn">
            <span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
            <?php _e('Inställningar', 'bkgt-data-scraping'); ?>
        </button>
    </nav>

    <!-- Tab Content -->
    <main class="bkgt-tabs-content" id="bkgt-main-content" role="main">

        <!-- Overview Tab -->
        <div id="bkgt-tab-overview" class="bkgt-tab-panel active" role="tabpanel" aria-labelledby="bkgt-tab-overview-btn" tabindex="0">
            <div class="bkgt-dashboard-grid">
                <!-- Data Overview Cards -->
                <div class="bkgt-dashboard-card bkgt-overview-card">
                    <h3><?php _e('Dataöversikt', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-chart-bar"></span></h3>
                    <div class="bkgt-stats-grid">
                        <?php
                        global $wpdb;
                        global $bkgt_db;
                        $db = $bkgt_db;

                        // Get counts
                        $players_count = $wpdb->get_var("SELECT COUNT(*) FROM {$db->get_table('players')} WHERE status = 'active'");
                        $events_count = $wpdb->get_var("SELECT COUNT(*) FROM {$db->get_table('events')}");
                        $stats_count = $wpdb->get_var("SELECT COUNT(*) FROM {$db->get_table('statistics')}");
                        $sources_count = $wpdb->get_var("SELECT COUNT(*) FROM {$db->get_table('sources')}");
                        ?>
                        <div class="bkgt-stat-card bkgt-stat-players">
                            <div class="bkgt-stat-icon">👥</div>
                            <div class="bkgt-stat-content">
                                <h4><?php echo esc_html($players_count); ?></h4>
                                <p><?php _e('Aktiva Spelare', 'bkgt-data-scraping'); ?></p>
                            </div>
                        </div>
                        <div class="bkgt-stat-card bkgt-stat-events">
                            <div class="bkgt-stat-icon">⚽</div>
                            <div class="bkgt-stat-content">
                                <h4><?php echo esc_html($events_count); ?></h4>
                                <p><?php _e('Matcher & Träningar', 'bkgt-data-scraping'); ?></p>
                            </div>
                        </div>
                        <div class="bkgt-stat-card bkgt-stat-stats">
                            <div class="bkgt-stat-icon">📊</div>
                            <div class="bkgt-stat-content">
                                <h4><?php echo esc_html($stats_count); ?></h4>
                                <p><?php _e('Statistikposter', 'bkgt-data-scraping'); ?></p>
                            </div>
                        </div>
                        <div class="bkgt-stat-card bkgt-stat-sources">
                            <div class="bkgt-stat-icon">🔄</div>
                            <div class="bkgt-stat-content">
                                <h4><?php echo esc_html($sources_count); ?></h4>
                                <p><?php _e('Datakällor', 'bkgt-data-scraping'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="bkgt-dashboard-card bkgt-actions-card">
                    <h3><?php _e('Snabbåtgärder', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-admin-tools"></span></h3>
                    <div class="bkgt-action-buttons">
                        <button type="button" class="button button-primary bkgt-action-btn" id="bkgt-scrape-players">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Skrapa Spelardata', 'bkgt-data-scraping'); ?>
                        </button>
                        <button type="button" class="button button-primary bkgt-action-btn" id="bkgt-scrape-events">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Skrapa Matchdata', 'bkgt-data-scraping'); ?>
                        </button>
                        <button type="button" class="button button-secondary bkgt-action-btn" id="bkgt-add-player">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e('Lägg till Spelare', 'bkgt-data-scraping'); ?>
                        </button>
                        <button type="button" class="button button-secondary bkgt-action-btn" id="bkgt-add-event">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e('Lägg till Match/Träning', 'bkgt-data-scraping'); ?>
                        </button>
                    </div>
                </div>

                <!-- Recent Activity Card -->
                <div class="bkgt-dashboard-card bkgt-activity-card">
                    <h3><?php _e('Senaste Aktivitet', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-clock"></span></h3>
                    <div class="bkgt-recent-activity">
                        <?php
                        // Get recent events
                        $recent_events = $db->get_events('all', 5);
                        if (!empty($recent_events)) {
                            echo '<div class="bkgt-activity-list">';
                            foreach ($recent_events as $event) {
                                $date = date_i18n(get_option('date_format'), strtotime($event['event_date']));
                                $event_type = $event['event_type'] === 'match' ? '⚽' : '🏃';
                                echo '<div class="bkgt-activity-item">';
                                echo '<span class="bkgt-activity-icon">' . $event_type . '</span>';
                                echo '<div class="bkgt-activity-content">';
                                echo '<strong>' . esc_html($event['title']) . '</strong>';
                                echo '<span class="bkgt-activity-date">' . esc_html($date) . '</span>';
                                echo '</div>';
                                echo '</div>';
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="bkgt-empty-state">';
                            echo '<span class="dashicons dashicons-calendar"></span>';
                            echo '<p>' . __('Inga matcher eller träningar hittades. Börja med att skrapa data eller lägga till händelser manuellt.', 'bkgt-data-scraping') . '</p>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>

                <!-- System Status Card -->
                <div class="bkgt-dashboard-card bkgt-status-card">
                    <h3><?php _e('Systemstatus', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-info"></span></h3>
                    <div class="bkgt-status-indicators">
                        <?php
                        $last_scrape = $wpdb->get_var("SELECT MAX(last_scraped) FROM {$db->get_table('sources')}");
                        $scrape_status = $last_scrape ? 'success' : 'warning';
                        $days_since = $last_scrape ? floor((time() - strtotime($last_scrape)) / (60*60*24)) : 'N/A';
                        ?>
                        <div class="bkgt-status-item">
                            <span class="bkgt-status-icon bkgt-status-<?php echo $scrape_status; ?>">
                                <?php echo $scrape_status === 'success' ? '✅' : '⚠️'; ?>
                            </span>
                            <div class="bkgt-status-content">
                                <strong><?php _e('Senaste Skrapning', 'bkgt-data-scraping'); ?></strong>
                                <p><?php echo $days_since === 'N/A' ? __('Aldrig', 'bkgt-data-scraping') : sprintf(__('%d dagar sedan', 'bkgt-data-scraping'), $days_since); ?></p>
                            </div>
                        </div>
                        <div class="bkgt-status-item">
                            <span class="bkgt-status-icon bkgt-status-success">🟢</span>
                            <div class="bkgt-status-content">
                                <strong><?php _e('Databas', 'bkgt-data-scraping'); ?></strong>
                                <p><?php _e('Alla tabeller OK', 'bkgt-data-scraping'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Players Tab -->
        <div id="bkgt-tab-players" class="bkgt-tab-panel" role="tabpanel" aria-labelledby="bkgt-tab-players-btn" tabindex="0" hidden>
            <div class="bkgt-tab-header">
                <h2><?php _e('Spelarhantering', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-groups"></span></h2>
                <div class="bkgt-tab-actions">
                    <button type="button" class="button" id="bkgt-export-players">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Exportera', 'bkgt-data-scraping'); ?>
                    </button>
                    <button type="button" class="button" id="bkgt-import-players">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Importera', 'bkgt-data-scraping'); ?>
                    </button>
                    <button type="button" class="button" id="bkgt-bulk-assign">
                        <span class="dashicons dashicons-groups"></span>
                        <?php _e('Mass-tilldelning', 'bkgt-data-scraping'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="bkgt-add-player">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Lägg till Spelare', 'bkgt-data-scraping'); ?>
                    </button>
                    <button type="button" class="button" id="bkgt-scrape-players">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Skrapa från Källa', 'bkgt-data-scraping'); ?>
                    </button>
                </div>
            </div>

            <!-- Players Search and Filters -->
            <div class="bkgt-search-filters">
                <div class="bkgt-search-row">
                    <div class="bkgt-search-input">
                        <label for="bkgt-players-search" class="bkgt-sr-only"><?php _e('Sök spelare', 'bkgt-data-scraping'); ?></label>
                        <span class="dashicons dashicons-search" aria-hidden="true"></span>
                        <input type="text" id="bkgt-players-search" placeholder="<?php _e('Sök spelare...', 'bkgt-data-scraping'); ?>" aria-describedby="bkgt-players-search-help">
                    </div>
                    <div class="bkgt-filter-select">
                        <label for="bkgt-players-status-filter" class="bkgt-sr-only"><?php _e('Filtrera efter status', 'bkgt-data-scraping'); ?></label>
                        <select id="bkgt-players-status-filter" aria-describedby="bkgt-players-status-help">
                            <option value=""><?php _e('Alla statusar', 'bkgt-data-scraping'); ?></option>
                            <option value="active"><?php _e('Aktiv', 'bkgt-data-scraping'); ?></option>
                            <option value="inactive"><?php _e('Inaktiv', 'bkgt-data-scraping'); ?></option>
                            <option value="injured"><?php _e('Skadad', 'bkgt-data-scraping'); ?></option>
                        </select>
                    </div>
                    <div class="bkgt-filter-select">
                        <label for="bkgt-players-position-filter" class="bkgt-sr-only"><?php _e('Filtrera efter position', 'bkgt-data-scraping'); ?></label>
                        <select id="bkgt-players-position-filter" aria-describedby="bkgt-players-position-help">
                            <option value=""><?php _e('Alla positioner', 'bkgt-data-scraping'); ?></option>
                            <option value="QB"><?php _e('Quarterback (QB)', 'bkgt-data-scraping'); ?></option>
                            <option value="RB"><?php _e('Running Back (RB)', 'bkgt-data-scraping'); ?></option>
                            <option value="WR"><?php _e('Wide Receiver (WR)', 'bkgt-data-scraping'); ?></option>
                            <option value="TE"><?php _e('Tight End (TE)', 'bkgt-data-scraping'); ?></option>
                            <option value="OL"><?php _e('Offensive Line (OL)', 'bkgt-data-scraping'); ?></option>
                            <option value="DL"><?php _e('Defensive Line (DL)', 'bkgt-data-scraping'); ?></option>
                            <option value="LB"><?php _e('Linebacker (LB)', 'bkgt-data-scraping'); ?></option>
                            <option value="CB"><?php _e('Cornerback (CB)', 'bkgt-data-scraping'); ?></option>
                            <option value="S"><?php _e('Safety (S)', 'bkgt-data-scraping'); ?></option>
                            <option value="K"><?php _e('Kicker (K)', 'bkgt-data-scraping'); ?></option>
                            <option value="P"><?php _e('Punter (P)', 'bkgt-data-scraping'); ?></option>
                        </select>
                    </div>
                    <button type="button" class="button" id="bkgt-clear-players-filters">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php _e('Rensa filter', 'bkgt-data-scraping'); ?>
                    </button>
                </div>
            </div>

            <div class="bkgt-players-grid" id="bkgt-players-container">
                <?php
                $players = $db->get_players('all');
                if (!empty($players)) {
                    foreach ($players as $player) {
                        $status_class = 'bkgt-status-' . $player['status'];
                        $status_icon = $player['status'] === 'active' ? '🟢' : ($player['status'] === 'inactive' ? '🔴' : '🟡');
                        ?>
                        <div class="bkgt-player-card <?php echo $status_class; ?>" data-player-id="<?php echo esc_attr($player['id']); ?>">
                            <div class="bkgt-player-header">
                                <div class="bkgt-player-avatar">
                                    <span class="bkgt-player-initials">
                                        <?php echo esc_html(substr($player['first_name'], 0, 1) . substr($player['last_name'], 0, 1)); ?>
                                    </span>
                                </div>
                                <div class="bkgt-player-info">
                                    <h4><?php echo esc_html($player['first_name'] . ' ' . $player['last_name']); ?></h4>
                                    <span class="bkgt-player-position"><?php echo esc_html($player['position']); ?></span>
                                </div>
                                <div class="bkgt-player-status">
                                    <span class="bkgt-status-indicator" title="<?php echo esc_attr(ucfirst($player['status'])); ?>">
                                        <?php echo $status_icon; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="bkgt-player-details">
                                <div class="bkgt-player-detail">
                                    <span class="bkgt-detail-label"><?php _e('Tröjnummer:', 'bkgt-data-scraping'); ?></span>
                                    <span class="bkgt-detail-value">#<?php echo esc_html($player['jersey_number']); ?></span>
                                </div>
                                <div class="bkgt-player-detail">
                                    <span class="bkgt-detail-label"><?php _e('Födelsedatum:', 'bkgt-data-scraping'); ?></span>
                                    <span class="bkgt-detail-value"><?php echo $player['birth_date'] ? date_i18n('Y-m-d', strtotime($player['birth_date'])) : '-'; ?></span>
                                </div>
                            </div>
                            <div class="bkgt-player-actions">
                                <button type="button" class="button button-small bkgt-edit-player" data-player='<?php echo wp_json_encode($player); ?>'>
                                    <span class="dashicons dashicons-edit"></span>
                                    <?php _e('Redigera', 'bkgt-data-scraping'); ?>
                                </button>
                                <button type="button" class="button button-small button-link-delete bkgt-delete-player" data-player-id="<?php echo esc_attr($player['id']); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php _e('Ta bort', 'bkgt-data-scraping'); ?>
                                </button>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="bkgt-empty-state bkgt-full-width">
                        <span class="dashicons dashicons-groups"></span>
                        <h3><?php _e('Inga spelare hittades', 'bkgt-data-scraping'); ?></h3>
                        <p><?php _e('Börja med att lägga till spelare manuellt eller skrapa data från källan.', 'bkgt-data-scraping'); ?></p>
                        <button type="button" class="button button-primary" id="bkgt-add-player-empty">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e('Lägg till Första Spelaren', 'bkgt-data-scraping'); ?>
                        </button>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>

        <!-- Events Tab -->
        <div id="bkgt-tab-events" class="bkgt-tab-panel" role="tabpanel" aria-labelledby="bkgt-tab-events-btn" tabindex="0" hidden>
            <div class="bkgt-tab-header">
                <h2><?php _e('Matcher & Träningar', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-calendar-alt"></span></h2>
                <div class="bkgt-tab-actions">
                    <button type="button" class="button" id="bkgt-export-events">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Exportera', 'bkgt-data-scraping'); ?>
                    </button>
                    <button type="button" class="button" id="bkgt-import-events">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Importera', 'bkgt-data-scraping'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="bkgt-add-event">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Lägg till Match/Träning', 'bkgt-data-scraping'); ?>
                    </button>
                    <button type="button" class="button" id="bkgt-scrape-events">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Skrapa från Källa', 'bkgt-data-scraping'); ?>
                    </button>
                </div>
            </div>

            <!-- Events Search and Filters -->
            <div class="bkgt-search-filters">
                <div class="bkgt-search-row">
                    <div class="bkgt-search-input">
                        <label for="bkgt-events-search" class="bkgt-sr-only"><?php _e('Sök matcher och träningar', 'bkgt-data-scraping'); ?></label>
                        <span class="dashicons dashicons-search" aria-hidden="true"></span>
                        <input type="text" id="bkgt-events-search" placeholder="<?php _e('Sök matcher/träningar...', 'bkgt-data-scraping'); ?>" aria-describedby="bkgt-events-search-help">
                    </div>
                    <div class="bkgt-filter-select">
                        <label for="bkgt-events-type-filter" class="bkgt-sr-only"><?php _e('Filtrera efter typ', 'bkgt-data-scraping'); ?></label>
                        <select id="bkgt-events-type-filter" aria-describedby="bkgt-events-type-help">
                            <option value=""><?php _e('Alla typer', 'bkgt-data-scraping'); ?></option>
                            <option value="match"><?php _e('Match', 'bkgt-data-scraping'); ?></option>
                            <option value="training"><?php _e('Träning', 'bkgt-data-scraping'); ?></option>
                            <option value="meeting"><?php _e('Möte', 'bkgt-data-scraping'); ?></option>
                        </select>
                    </div>
                    <div class="bkgt-filter-select">
                        <select id="bkgt-events-date-filter">
                            <option value=""><?php _e('Alla datum', 'bkgt-data-scraping'); ?></option>
                            <option value="upcoming"><?php _e('Kommande', 'bkgt-data-scraping'); ?></option>
                            <option value="past"><?php _e('Tidigare', 'bkgt-data-scraping'); ?></option>
                            <option value="today"><?php _e('Idag', 'bkgt-data-scraping'); ?></option>
                        </select>
                    </div>
                    <button type="button" class="button" id="bkgt-clear-events-filters">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php _e('Rensa filter', 'bkgt-data-scraping'); ?>
                    </button>
                </div>
            </div>

            <div class="bkgt-events-grid" id="bkgt-events-container">
                <?php
                $events = $db->get_events('all');
                if (!empty($events)) {
                    foreach ($events as $event) {
                        $event_type = $event['event_type'];
                        $event_icon = $event_type === 'match' ? '⚽' : '🏃';
                        $event_class = 'bkgt-event-' . $event_type;
                        $date = date_i18n(get_option('date_format'), strtotime($event['event_date']));
                        $time = date_i18n(get_option('time_format'), strtotime($event['event_date']));
                        ?>
                        <div class="bkgt-event-card <?php echo $event_class; ?>" data-event-id="<?php echo esc_attr($event['id']); ?>" data-date="<?php echo esc_attr($event['event_date']); ?>">
                            <div class="bkgt-event-header">
                                <div class="bkgt-event-icon">
                                    <?php echo $event_icon; ?>
                                </div>
                                <div class="bkgt-event-info">
                                    <h4><?php echo esc_html($event['title']); ?></h4>
                                    <span class="bkgt-event-type"><?php echo esc_html($event_type === 'match' ? 'Match' : 'Träning'); ?></span>
                                </div>
                            </div>
                            <div class="bkgt-event-details">
                                <div class="bkgt-event-detail">
                                    <span class="dashicons dashicons-calendar"></span>
                                    <span><?php echo esc_html($date); ?></span>
                                </div>
                                <div class="bkgt-event-detail">
                                    <span class="dashicons dashicons-clock"></span>
                                    <span><?php echo esc_html($time); ?></span>
                                </div>
                                <?php if ($event['location']) { ?>
                                <div class="bkgt-event-detail">
                                    <span class="dashicons dashicons-location"></span>
                                    <span><?php echo esc_html($event['location']); ?></span>
                                </div>
                                <?php } ?>
                            </div>
                            <div class="bkgt-event-actions">
                                <button type="button" class="button button-small bkgt-edit-event" data-event='<?php echo wp_json_encode($event); ?>'>
                                    <span class="dashicons dashicons-edit"></span>
                                    <?php _e('Redigera', 'bkgt-data-scraping'); ?>
                                </button>
                                <button type="button" class="button button-small bkgt-assign-players" data-event-id="<?php echo esc_attr($event['id']); ?>" data-event-title="<?php echo esc_attr($event['title']); ?>">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php _e('Tilldela Spelare', 'bkgt-data-scraping'); ?>
                                </button>
                                <button type="button" class="button button-small button-link-delete bkgt-delete-event" data-event-id="<?php echo esc_attr($event['id']); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php _e('Ta bort', 'bkgt-data-scraping'); ?>
                                </button>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="bkgt-empty-state bkgt-full-width">
                        <span class="dashicons dashicons-calendar"></span>
                        <h3><?php _e('Inga matcher eller träningar hittades', 'bkgt-data-scraping'); ?></h3>
                        <p><?php _e('Börja med att lägga till matcher manuellt eller skrapa data från källan.', 'bkgt-data-scraping'); ?></p>
                        <button type="button" class="button button-primary" id="bkgt-add-event-empty">
                            <span class="dashicons dashicons-plus"></span>
                            <?php _e('Lägg till Första Matchen', 'bkgt-data-scraping'); ?>
                        </button>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>

        <!-- Scraper Tab -->
        <div id="bkgt-tab-scraper" class="bkgt-tab-panel" role="tabpanel" aria-labelledby="bkgt-tab-scraper-btn" tabindex="0" hidden>
            <div class="bkgt-tab-header">
                <h2><?php _e('Dataskrapning', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-update"></span></h2>
            </div>

            <div class="bkgt-scraper-dashboard">
                <!-- Scraper Status Overview -->
                <div class="bkgt-dashboard-card bkgt-scraper-status-card">
                    <h3><?php _e('Skrapningsstatus', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-chart-line"></span></h3>
                    <div class="bkgt-scraper-stats">
                        <?php
                        $scraper_stats = $db->get_scraping_stats();
                        $last_run = $scraper_stats['last_run'] ? date_i18n('Y-m-d H:i:s', strtotime($scraper_stats['last_run'])) : __('Aldrig', 'bkgt-data-scraping');
                        $success_rate = $scraper_stats['total_runs'] > 0 ? round(($scraper_stats['successful_runs'] / $scraper_stats['total_runs']) * 100, 1) : 0;
                        ?>
                        <div class="bkgt-scraper-stat">
                            <span class="bkgt-stat-label"><?php _e('Senaste körning:', 'bkgt-data-scraping'); ?></span>
                            <span class="bkgt-stat-value"><?php echo esc_html($last_run); ?></span>
                        </div>
                        <div class="bkgt-scraper-stat">
                            <span class="bkgt-stat-label"><?php _e('Lyckades:', 'bkgt-data-scraping'); ?></span>
                            <span class="bkgt-stat-value"><?php echo esc_html($scraper_stats['successful_runs']); ?>/<?php echo esc_html($scraper_stats['total_runs']); ?> (<?php echo esc_html($success_rate); ?>%)</span>
                        </div>
                        <div class="bkgt-scraper-stat">
                            <span class="bkgt-stat-label"><?php _e('Totalt processade:', 'bkgt-data-scraping'); ?></span>
                            <span class="bkgt-stat-value"><?php echo esc_html($scraper_stats['total_records']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Manual Scraping Controls -->
                <div class="bkgt-dashboard-card bkgt-scraper-controls-card">
                    <h3><?php _e('Manuell Skrapning', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-admin-tools"></span></h3>
                    <div class="bkgt-scraper-controls">
                        <button type="button" class="button button-primary bkgt-scrape-btn" id="bkgt-scrape-all" data-type="all">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Skrapa Allt', 'bkgt-data-scraping'); ?>
                        </button>
                        <button type="button" class="button button-secondary bkgt-scrape-btn" id="bkgt-scrape-teams" data-type="teams">
                            <span class="dashicons dashicons-groups"></span>
                            <?php _e('Skrapa Lag', 'bkgt-data-scraping'); ?>
                        </button>
                        <button type="button" class="button button-secondary bkgt-scrape-btn" id="bkgt-scrape-players" data-type="players">
                            <span class="dashicons dashicons-admin-users"></span>
                            <?php _e('Skrapa Spelare', 'bkgt-data-scraping'); ?>
                        </button>
                        <button type="button" class="button button-secondary bkgt-scrape-btn" id="bkgt-scrape-events" data-type="events">
                            <span class="dashicons dashicons-calendar"></span>
                            <?php _e('Skrapa Matcher', 'bkgt-data-scraping'); ?>
                        </button>
                        <button type="button" class="button button-secondary bkgt-scrape-btn" id="bkgt-scrape-swe3" data-type="swe3">
                            <span class="dashicons dashicons-media-document"></span>
                            <?php _e('Skrapa SWE3 Dokument', 'bkgt-data-scraping'); ?>
                        </button>
                    </div>
                    <div id="bkgt-scraper-progress" class="bkgt-scraper-progress" style="display: none;">
                        <div class="bkgt-progress-bar">
                            <div class="bkgt-progress-fill" id="bkgt-progress-fill" style="width: 0%;"></div>
                        </div>
                        <div class="bkgt-progress-text" id="bkgt-progress-text"><?php _e('Förbereder skrapning...', 'bkgt-data-scraping'); ?></div>
                    </div>
                </div>

                <!-- Scheduling Settings -->
                <div class="bkgt-dashboard-card bkgt-scraper-schedule-card">
                    <h3><?php _e('Automatisk Schemaläggning', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-clock"></span></h3>
                    <form method="post" action="" class="bkgt-schedule-form">
                        <?php wp_nonce_field('bkgt_schedule_nonce'); ?>
                        <div class="bkgt-form-row">
                            <label for="bkgt_auto_scraping_enabled">
                                <input type="checkbox" id="bkgt_auto_scraping_enabled" name="bkgt_auto_scraping_enabled" value="yes" <?php checked(get_option('bkgt_auto_scraping_enabled'), 'yes'); ?> />
                                <?php _e('Aktivera automatisk skrapning', 'bkgt-data-scraping'); ?>
                            </label>
                        </div>
                        <div class="bkgt-form-row">
                            <label for="bkgt_scraping_schedule"><?php _e('Schemaläggning:', 'bkgt-data-scraping'); ?></label>
                            <select id="bkgt_scraping_schedule" name="bkgt_scraping_schedule">
                                <option value="daily" <?php selected(get_option('bkgt_scraping_schedule'), 'daily'); ?>><?php _e('Dagligen', 'bkgt-data-scraping'); ?></option>
                                <option value="weekly" <?php selected(get_option('bkgt_scraping_schedule'), 'weekly'); ?>><?php _e('Veckovis', 'bkgt-data-scraping'); ?></option>
                                <option value="monthly" <?php selected(get_option('bkgt_scraping_schedule'), 'monthly'); ?>><?php _e('Månadsvis', 'bkgt-data-scraping'); ?></option>
                            </select>
                        </div>
                        <div class="bkgt-form-row">
                            <label for="bkgt_scraping_time"><?php _e('Tid:', 'bkgt-data-scraping'); ?></label>
                            <input type="time" id="bkgt_scraping_time" name="bkgt_scraping_time" value="<?php echo esc_attr(get_option('bkgt_scraping_time', '02:00')); ?>" />
                        </div>
                        <div class="bkgt-form-row">
                            <input type="submit" name="bkgt_save_schedule" class="button button-primary" value="<?php _e('Spara Schemaläggning', 'bkgt-data-scraping'); ?>" />
                        </div>
                    </form>
                </div>

                <!-- Recent Scraping Logs -->
                <div class="bkgt-dashboard-card bkgt-scraper-logs-card">
                    <h3><?php _e('Senaste Skrapningsloggar', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-list-view"></span></h3>
                    <div class="bkgt-logs-container">
                        <?php
                        $recent_logs = $db->get_scraping_logs(10);
                        if (!empty($recent_logs)) {
                            echo '<div class="bkgt-logs-list">';
                            foreach ($recent_logs as $log) {
                                $status_class = $log->status === 'completed' ? 'success' : ($log->status === 'failed' ? 'error' : 'warning');
                                $status_icon = $log->status === 'completed' ? 'yes' : ($log->status === 'failed' ? 'no' : 'clock');
                                $duration = $log->duration ? gmdate('H:i:s', $log->duration) : '-';
                                echo '<div class="bkgt-log-item bkgt-log-' . esc_attr($status_class) . '">';
                                echo '<div class="bkgt-log-header">';
                                echo '<span class="dashicons dashicons-' . esc_attr($status_icon) . '"></span>';
                                echo '<strong>' . esc_html(ucfirst($log->data_type)) . '</strong>';
                                echo '<span class="bkgt-log-date">' . esc_html(date_i18n('Y-m-d H:i:s', strtotime($log->started_at))) . '</span>';
                                echo '</div>';
                                echo '<div class="bkgt-log-details">';
                                echo '<span>' . esc_html($log->records_processed) . ' ' . __('processade', 'bkgt-data-scraping') . '</span>';
                                echo '<span>' . esc_html($log->records_added) . ' ' . __('tillagda', 'bkgt-data-scraping') . '</span>';
                                echo '<span>' . __('Tid:', 'bkgt-data-scraping') . ' ' . esc_html($duration) . '</span>';
                                if ($log->error_message) {
                                    echo '<div class="bkgt-log-error">' . esc_html($log->error_message) . '</div>';
                                }
                                echo '</div>';
                                echo '</div>';
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="bkgt-empty-state">';
                            echo '<span class="dashicons dashicons-list-view"></span>';
                            echo '<p>' . __('Inga skrapningsloggar hittades än. Kör en skrapning för att se aktivitet här.', 'bkgt-data-scraping') . '</p>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Tab -->
        <div id="bkgt-tab-settings" class="bkgt-tab-panel" role="tabpanel" aria-labelledby="bkgt-tab-settings-btn" tabindex="0" hidden>
            <div class="bkgt-tab-header">
                <h2><?php _e('Inställningar', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-admin-settings"></span></h2>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('bkgt_settings_nonce'); ?>
                <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('bkgt_settings_nonce'); ?>" />

                <div class="bkgt-settings-grid">
                    <div class="bkgt-settings-card">
                        <h3><?php _e('Dataskrapning', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-download"></span></h3>
                        <div class="bkgt-form-row">
                            <label for="bkgt_scraping_enabled">
                                <input type="checkbox" id="bkgt_scraping_enabled" name="bkgt_scraping_enabled" value="yes" <?php checked(get_option('bkgt_scraping_enabled'), 'yes'); ?> />
                                <?php _e('Aktivera automatisk skrapning', 'bkgt-data-scraping'); ?>
                            </label>
                        </div>
                        <div class="bkgt-form-row">
                            <label for="bkgt_scraping_interval"><?php _e('Skrapningsintervall', 'bkgt-data-scraping'); ?></label>
                            <select id="bkgt_scraping_interval" name="bkgt_scraping_interval">
                                <option value="daily" <?php selected(get_option('bkgt_scraping_interval'), 'daily'); ?>><?php _e('Dagligen', 'bkgt-data-scraping'); ?></option>
                                <option value="twice_daily" <?php selected(get_option('bkgt_scraping_interval'), 'twice_daily'); ?>><?php _e('Två gånger dagligen', 'bkgt-data-scraping'); ?></option>
                                <option value="hourly" <?php selected(get_option('bkgt_scraping_interval'), 'hourly'); ?>><?php _e('Varje timme', 'bkgt-data-scraping'); ?></option>
                            </select>
                        </div>
                        <div class="bkgt-form-row">
                            <label for="bkgt_scraping_source_url"><?php _e('Käll-URL för skrapning', 'bkgt-data-scraping'); ?></label>
                            <input type="url" id="bkgt_scraping_source_url" name="bkgt_scraping_source_url" value="<?php echo esc_attr(get_option('bkgt_scraping_source_url')); ?>" placeholder="https://svenskalag.se/bkgt" />
                        </div>
                    </div>

                    <div class="bkgt-settings-card">
                        <h3><?php _e('Datahantering', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-admin-tools"></span></h3>
                        <div class="bkgt-form-row">
                            <button type="button" class="button button-secondary" id="bkgt-test-connection">
                                <span class="dashicons dashicons-admin-network"></span>
                                <?php _e('Testa Anslutning', 'bkgt-data-scraping'); ?>
                            </button>
                            <button type="button" class="button button-secondary" id="bkgt-clear-cache">
                                <span class="dashicons dashicons-trash"></span>
                                <?php _e('Rensa Cache', 'bkgt-data-scraping'); ?>
                            </button>
                        </div>
                        <div class="bkgt-form-row">
                            <button type="button" class="button button-link-delete" id="bkgt-reset-data">
                                <span class="dashicons dashicons-warning"></span>
                                <?php _e('Återställ All Data (Varning!)', 'bkgt-data-scraping'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="bkgt-settings-card">
                        <h3><?php _e('Datarensning', 'bkgt-data-scraping'); ?> <span class="dashicons dashicons-trash"></span></h3>
                        <p><?php _e('Rensa bort falska eller duplicerade lag som inte kommer från svenskalag.se', 'bkgt-data-scraping'); ?></p>
                        <div class="bkgt-form-row">
                            <button type="button" class="button button-secondary" id="bkgt-cleanup-teams">
                                <span class="dashicons dashicons-editor-removeformatting"></span>
                                <?php _e('Rensa Falska Lag', 'bkgt-data-scraping'); ?>
                            </button>
                        </div>
                        <div id="bkgt-cleanup-status" style="display: none; margin-top: 10px;">
                            <div class="bkgt-progress-bar">
                                <div class="bkgt-progress-fill"></div>
                            </div>
                            <p id="bkgt-cleanup-message"><?php _e('Rensar...', 'bkgt-data-scraping'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bkgt-form-actions">
                    <input type="submit" name="submit" class="button button-primary" value="<?php _e('Spara Inställningar', 'bkgt-data-scraping'); ?>" />
                </div>
            </form>
        </div>

    </div>
</div>

<!-- Player Modal -->
<div id="bkgt-player-modal" class="bkgt-modal" style="display: none;">
    <div class="bkgt-modal-content">
        <span class="bkgt-modal-close">&times;</span>
        <h3 id="bkgt-player-modal-title"><?php _e('Lägg till/redigera spelare', 'bkgt-data-scraping'); ?></h3>
        <form id="bkgt-player-form">
            <input type="hidden" id="bkgt-player-id" name="player_id" value="">
            <div class="bkgt-form-row">
                <label for="bkgt-player-first-name"><?php _e('Förnamn', 'bkgt-data-scraping'); ?> *</label>
                <input type="text" id="bkgt-player-first-name" name="first_name" required>
            </div>
            <div class="bkgt-form-row">
                <label for="bkgt-player-last-name"><?php _e('Efternamn', 'bkgt-data-scraping'); ?> *</label>
                <input type="text" id="bkgt-player-last-name" name="last_name" required>
            </div>
            <div class="bkgt-form-row">
                <label for="bkgt-player-position"><?php _e('Position', 'bkgt-data-scraping'); ?> *</label>
                <select id="bkgt-player-position" name="position" required>
                    <option value=""><?php _e('Välj position', 'bkgt-data-scraping'); ?></option>
                    <option value="QB"><?php _e('Quarterback (QB)', 'bkgt-data-scraping'); ?></option>
                    <option value="RB"><?php _e('Running Back (RB)', 'bkgt-data-scraping'); ?></option>
                    <option value="WR"><?php _e('Wide Receiver (WR)', 'bkgt-data-scraping'); ?></option>
                    <option value="TE"><?php _e('Tight End (TE)', 'bkgt-data-scraping'); ?></option>
                    <option value="OL"><?php _e('Offensive Line (OL)', 'bkgt-data-scraping'); ?></option>
                    <option value="DL"><?php _e('Defensive Line (DL)', 'bkgt-data-scraping'); ?></option>
                    <option value="LB"><?php _e('Linebacker (LB)', 'bkgt-data-scraping'); ?></option>
                    <option value="CB"><?php _e('Cornerback (CB)', 'bkgt-data-scraping'); ?></option>
                    <option value="S"><?php _e('Safety (S)', 'bkgt-data-scraping'); ?></option>
                    <option value="K"><?php _e('Kicker (K)', 'bkgt-data-scraping'); ?></option>
                    <option value="P"><?php _e('Punter (P)', 'bkgt-data-scraping'); ?></option>
                </select>
            </div>
            <div class="bkgt-form-row">
                <label for="bkgt-player-jersey"><?php _e('Tröjnummer', 'bkgt-data-scraping'); ?> *</label>
                <input type="number" id="bkgt-player-jersey" name="jersey_number" min="0" max="99" required>
            </div>
            <div class="bkgt-form-row">
                <label for="bkgt-player-birth-date"><?php _e('Födelsedatum', 'bkgt-data-scraping'); ?></label>
                <input type="date" id="bkgt-player-birth-date" name="birth_date">
            </div>
            <div class="bkgt-form-row">
                <label for="bkgt-player-status"><?php _e('Status', 'bkgt-data-scraping'); ?> *</label>
                <select id="bkgt-player-status" name="status" required>
                    <option value="active"><?php _e('Aktiv', 'bkgt-data-scraping'); ?></option>
                    <option value="inactive"><?php _e('Inaktiv', 'bkgt-data-scraping'); ?></option>
                    <option value="injured"><?php _e('Skadad', 'bkgt-data-scraping'); ?></option>
                </select>
            </div>
            <div class="bkgt-form-actions">
                <button type="button" class="button bkgt-modal-cancel"><?php _e('Avbryt', 'bkgt-data-scraping'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Spara spelare', 'bkgt-data-scraping'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Event Modal -->
<div id="bkgt-event-modal" class="bkgt-modal" style="display: none;">
    <div class="bkgt-modal-content">
        <span class="bkgt-modal-close">&times;</span>
        <h3 id="bkgt-event-modal-title"><?php _e('Lägg till/redigera match/träning', 'bkgt-data-scraping'); ?></h3>
        <form id="bkgt-event-form">
            <input type="hidden" id="bkgt-event-id" name="event_id" value="">
            <div class="bkgt-form-row">
                <label for="bkgt-event-title"><?php _e('Titel', 'bkgt-data-scraping'); ?> *</label>
                <input type="text" id="bkgt-event-title" name="title" required>
            </div>
            <div class="bkgt-form-row">
                <label for="bkgt-event-type"><?php _e('Typ', 'bkgt-data-scraping'); ?> *</label>
                <select id="bkgt-event-type" name="event_type" required>
                    <option value="match"><?php _e('Match', 'bkgt-data-scraping'); ?></option>
                    <option value="training"><?php _e('Träning', 'bkgt-data-scraping'); ?></option>
                    <option value="meeting"><?php _e('Möte', 'bkgt-data-scraping'); ?></option>
                </select>
            </div>
            <div class="bkgt-form-row">
                <label for="bkgt-event-date"><?php _e('Datum & tid', 'bkgt-data-scraping'); ?> *</label>
                <input type="datetime-local" id="bkgt-event-date" name="event_date" required>
            </div>
            <div class="bkgt-form-row">
                <label for="bkgt-event-location"><?php _e('Plats', 'bkgt-data-scraping'); ?></label>
                <input type="text" id="bkgt-event-location" name="location" placeholder="<?php _e('t.ex. BKGT Arena, Stockholm', 'bkgt-data-scraping'); ?>">
            </div>
            <div class="bkgt-form-row">
                <label for="bkgt-event-description"><?php _e('Beskrivning', 'bkgt-data-scraping'); ?></label>
                <textarea id="bkgt-event-description" name="description" rows="3" placeholder="<?php _e('Valfri beskrivning eller anteckningar', 'bkgt-data-scraping'); ?>"></textarea>
            </div>
            <div class="bkgt-form-actions">
                <button type="button" class="button bkgt-modal-cancel"><?php _e('Avbryt', 'bkgt-data-scraping'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Spara händelse', 'bkgt-data-scraping'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Player Assignment Modal -->
<div id="bkgt-assignment-modal" class="bkgt-modal" style="display: none;">
    <div class="bkgt-modal-content">
        <span class="bkgt-modal-close">&times;</span>
        <h3><?php _e('Tilldela spelare till match/träning', 'bkgt-data-scraping'); ?></h3>
        <div class="bkgt-assignment-container">
            <div class="bkgt-assignment-section">
                <h4><?php _e('Tillgängliga spelare', 'bkgt-data-scraping'); ?></h4>
                <div id="bkgt-available-players" class="bkgt-player-list bkgt-droppable">
                    <!-- Available players will be loaded here -->
                </div>
            </div>
            <div class="bkgt-assignment-section">
                <h4><?php _e('Tilldelade spelare', 'bkgt-data-scraping'); ?></h4>
                <div id="bkgt-assigned-players" class="bkgt-player-list bkgt-droppable">
                    <!-- Assigned players will be loaded here -->
                </div>
            </div>
        </div>
        <div class="bkgt-form-actions">
            <button type="button" class="button bkgt-modal-cancel"><?php _e('Avbryt', 'bkgt-data-scraping'); ?></button>
            <button type="button" class="button button-primary" id="bkgt-save-assignment"><?php _e('Spara tilldelning', 'bkgt-data-scraping'); ?></button>
        </div>
    </div>
</div>

<div id="bkgt-scraping-modal" class="bkgt-modal" style="display: none;">
    <div class="bkgt-modal-content">
        <h3><?php _e('Skrapar data', 'bkgt-data-scraping'); ?></h3>
        <p><?php _e('Vänta medan vi skrapar data från källan...', 'bkgt-data-scraping'); ?></p>
        <div class="bkgt-progress-bar">
            <div class="bkgt-progress-fill"></div>
        </div>
    </div>
</div>

<!-- Player Import Modal -->
<div id="bkgt-player-import-modal" class="bkgt-modal" style="display: none;">
    <div class="bkgt-modal-content">
        <span class="bkgt-modal-close">&times;</span>
        <h3><?php _e('Importera spelare från CSV', 'bkgt-data-scraping'); ?></h3>
        <form id="bkgt-player-import-form" enctype="multipart/form-data">
            <div class="bkgt-form-row">
                <label for="bkgt-player-csv-file"><?php _e('Välj CSV-fil', 'bkgt-data-scraping'); ?> *</label>
                <input type="file" id="bkgt-player-csv-file" name="csv_file" accept=".csv" required>
                <p class="description">
                    <?php _e('CSV-filen ska innehålla kolumner: förnamn,efternamn,position,tröjnummer,födelsedatum,status', 'bkgt-data-scraping'); ?><br>
                    <?php _e('Exempel: Erik,Eriksson,QB,12,1990-05-15,active', 'bkgt-data-scraping'); ?>
                </p>
            </div>
            <div class="bkgt-form-row">
                <label for="bkgt-import-skip-duplicates">
                    <input type="checkbox" id="bkgt-import-skip-duplicates" name="skip_duplicates" checked>
                    <?php _e('Hoppa över dubbletter (baserat på förnamn + efternamn)', 'bkgt-data-scraping'); ?>
                </label>
            </div>
            <div class="bkgt-form-actions">
                <button type="button" class="button bkgt-modal-cancel"><?php _e('Avbryt', 'bkgt-data-scraping'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Importera spelare', 'bkgt-data-scraping'); ?></button>
            </div>
        </form>
        <div id="bkgt-import-progress" style="display: none;">
            <div class="bkgt-progress-bar">
                <div class="bkgt-progress-fill"></div>
            </div>
            <p id="bkgt-import-status"><?php _e('Importerar...', 'bkgt-data-scraping'); ?></p>
        </div>
    </div>
</div>

<!-- Event Import Modal -->
<div id="bkgt-event-import-modal" class="bkgt-modal" style="display: none;">
    <div class="bkgt-modal-content">
        <span class="bkgt-modal-close">&times;</span>
        <h3><?php _e('Importera matcher/träningar från CSV', 'bkgt-data-scraping'); ?></h3>
        <form id="bkgt-event-import-form" enctype="multipart/form-data">
            <div class="bkgt-form-row">
                <label for="bkgt-event-csv-file"><?php _e('Välj CSV-fil', 'bkgt-data-scraping'); ?> *</label>
                <input type="file" id="bkgt-event-csv-file" name="csv_file" accept=".csv" required>
                <p class="description">
                    <?php _e('CSV-filen ska innehålla kolumner: titel,typ,datum_tid,plats,beskrivning', 'bkgt-data-scraping'); ?><br>
                    <?php _e('Exempel: "Träning 1",training,2024-01-15 18:00,BKGT Arena,"Veckans träning"', 'bkgt-data-scraping'); ?>
                </p>
            </div>
            <div class="bkgt-form-row">
                <label for="bkgt-event-import-skip-duplicates">
                    <input type="checkbox" id="bkgt-event-import-skip-duplicates" name="skip_duplicates" checked>
                    <?php _e('Hoppa över dubbletter (baserat på titel + datum)', 'bkgt-data-scraping'); ?>
                </label>
            </div>
            <div class="bkgt-form-actions">
                <button type="button" class="button bkgt-modal-cancel"><?php _e('Avbryt', 'bkgt-data-scraping'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Importera händelser', 'bkgt-data-scraping'); ?></button>
            </div>
        </form>
        <div id="bkgt-event-import-progress" style="display: none;">
            <div class="bkgt-progress-bar">
                <div class="bkgt-progress-fill"></div>
            </div>
            <p id="bkgt-event-import-status"><?php _e('Importerar...', 'bkgt-data-scraping'); ?></p>
        </div>
    </div>
</div>

<!-- Bulk Assignment Wizard Modal -->
<div id="bkgt-bulk-assignment-modal" class="bkgt-modal" style="display: none;">
    <div class="bkgt-modal-content bkgt-wizard-modal">
        <span class="bkgt-modal-close">&times;</span>
        <h3><?php _e('Mass-tilldelning av spelare', 'bkgt-data-scraping'); ?></h3>

        <div class="bkgt-wizard-steps">
            <div class="bkgt-wizard-step active" data-step="1">
                <h4><?php _e('Steg 1: Välj spelare', 'bkgt-data-scraping'); ?></h4>
                <div class="bkgt-player-selection">
                    <div class="bkgt-selection-controls">
                        <button type="button" class="button" id="bkgt-select-all-players"><?php _e('Välj alla', 'bkgt-data-scraping'); ?></button>
                        <button type="button" class="button" id="bkgt-clear-player-selection"><?php _e('Rensa val', 'bkgt-data-scraping'); ?></button>
                    </div>
                    <div id="bkgt-bulk-player-list" class="bkgt-player-grid">
                        <!-- Players will be loaded here -->
                    </div>
                </div>
            </div>

            <div class="bkgt-wizard-step" data-step="2">
                <h4><?php _e('Steg 2: Välj matcher/träningar', 'bkgt-data-scraping'); ?></h4>
                <div class="bkgt-event-selection">
                    <div class="bkgt-selection-controls">
                        <button type="button" class="button" id="bkgt-select-all-events"><?php _e('Välj alla', 'bkgt-data-scraping'); ?></button>
                        <button type="button" class="button" id="bkgt-clear-event-selection"><?php _e('Rensa val', 'bkgt-data-scraping'); ?></button>
                    </div>
                    <div id="bkgt-bulk-event-list" class="bkgt-event-grid">
                        <!-- Events will be loaded here -->
                    </div>
                </div>
            </div>

            <div class="bkgt-wizard-step" data-step="3">
                <h4><?php _e('Steg 3: Bekräfta och tilldela', 'bkgt-data-scraping'); ?></h4>
                <div class="bkgt-assignment-summary">
                    <div class="bkgt-summary-section">
                        <h5><?php _e('Valda spelare:', 'bkgt-data-scraping'); ?></h5>
                        <ul id="bkgt-selected-players-summary"></ul>
                    </div>
                    <div class="bkgt-summary-section">
                        <h5><?php _e('Valda händelser:', 'bkgt-data-scraping'); ?></h5>
                        <ul id="bkgt-selected-events-summary"></ul>
                    </div>
                    <div class="bkgt-assignment-options">
                        <label>
                            <input type="checkbox" id="bkgt-overwrite-existing">
                            <?php _e('Skriv över befintliga tilldelningar', 'bkgt-data-scraping'); ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="bkgt-wizard-navigation">
            <button type="button" class="button" id="bkgt-wizard-prev" style="display: none;"><?php _e('Tillbaka', 'bkgt-data-scraping'); ?></button>
            <div class="bkgt-wizard-indicators">
                <span class="bkgt-step-indicator active" data-step="1"></span>
                <span class="bkgt-step-indicator" data-step="2"></span>
                <span class="bkgt-step-indicator" data-step="3"></span>
            </div>
            <button type="button" class="button button-primary" id="bkgt-wizard-next"><?php _e('Nästa', 'bkgt-data-scraping'); ?></button>
        </div>
    </div>

    <!-- ARIA live region for status messages -->
    <div aria-live="polite" aria-atomic="true" class="bkgt-live-region" id="bkgt-status-messages"></div>

    <!-- Screen reader help text -->
    <div class="bkgt-sr-only" id="bkgt-players-search-help"><?php _e('Skriv för att söka efter spelare efter namn eller tröjnummer', 'bkgt-data-scraping'); ?></div>
    <div class="bkgt-sr-only" id="bkgt-players-status-help"><?php _e('Välj status för att filtrera spelare', 'bkgt-data-scraping'); ?></div>
    <div class="bkgt-sr-only" id="bkgt-players-position-help"><?php _e('Välj position för att filtrera spelare', 'bkgt-data-scraping'); ?></div>
    <div class="bkgt-sr-only" id="bkgt-events-search-help"><?php _e('Skriv för att söka efter matcher eller träningar', 'bkgt-data-scraping'); ?></div>
    <div class="bkgt-sr-only" id="bkgt-events-type-help"><?php _e('Välj typ för att filtrera evenemang', 'bkgt-data-scraping'); ?></div>

</div>